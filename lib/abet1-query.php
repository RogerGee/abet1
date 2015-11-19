<?php

/* abet1-query.php - abstract database functionality

    This file abstracts database functionality so that it doesn't have to
    go in the Web root. Please do not put this file in the Web root: it is a
    library.
*/

require_once('abet1-config.php');

class Query {
    static private $databaseName = "abet1";
    static private $user = null;
    static private $passwd = null;

    private $result; // mysqli_result

    static private function connect_db() {
        static $connection = null;

        // only attempt connect if not already connected
        if (is_null($connection)) {
            // get configuration; throw on error
            $config = new AbetConfig;
            if (!$config->offsetExists('DATABASE_HOST')
                || !$config->offsetExists('DATABASE_USER')
                || !$config->offsetExists('DATABASE_PASSWD'))
            {
                throw new Exception("database configuration is incorrect");
            }

            // connect to MySQL database; throw on error
            $connection = new mysqli($config['DATABASE_HOST'],
                            $config['DATABASE_USER'],
                            $config['DATABASE_PASSWD'],
                            self::$databaseName);
            if ($connection->connect_error) {
                throw new Exception("database configuration is incorrect");
            }
        }

        return $connection;
    }

    function __construct($queryString) {
        $con = self::connect_db();
        $this->result = $con->query($queryString);
        if ($this->result === FALSE) {
            // we throw QueryException only when a query fails so the implementation
            // can decide what to do; everything else is truly exceptional
            throw new Exception("database query failed: $con->error");
        }
    }

    function __destruct() {
        if (is_a($this->result,'mysqli_result')) {
            $this->result->close();
        }
    }

    // is_empty() - determines if result had no rows
    function is_empty() {
        if (is_a($this->result,'mysqli_result')) {
            return $this->result->num_rows == 0;
        }
        throw new Exception("call to " . __FUNCTION__ . " is illegal for "
            . "non-result queries");
    }

    // get_result() - returns the wrapped object; this will be boolean(true) if
    // a non-result query was issued; otherwise it will be a mysqli_result
    function get_result() {
        return $this->result;
    }

    // these functions return number of rows/columns
    function get_number_of_rows() {
        if (is_a($this->result,'mysqli_result'))
            return $this->result->num_rows;
        throw new Exception("call to " . __FUNCTION__ . " is illegal for "
            . "non-result queries");
    }
    function get_number_of_columns() {
        if (is_a($this->result,'mysqli_result'))
            return $this->result->field_count;
        throw new Exception("call to " . __FUNCTION__ . " is illegal for "
            . "non-result queries");
    }

    // get_row_assoc() - obtain associative array with field keys for a given
    // row; the row must be a 1-based row number; the result array is associative
    function get_row_assoc($rowNumber) {
        if (is_a($this->result,'mysqli_result')) {
            if ($this->result->data_seek($rowNumber-1))
                return $this->result->fetch_assoc();
            return null;
        }
        throw new Exception("call to " . __FUNCTION__ . " is illegal for "
            . "non-result queries");
    }

    // get_row_ordered() - like get_row_assoc() but returns an ordered array
    // with fields keyed to indeces in the order specified by the query
    function get_row_ordered($rowNumber) {
        if (is_a($this->result,'mysqli_result')) {
            if ($this->result->data_seek($rowNumber-1))
                return $this->result->fetch_row();
            return null;
        }
        throw new Exception("call to " . __FUNCTION__ . " is illegal for "
            . "non-result queries");
    }

    // get_row_json() - obtain JSON-encoded database row; this is just a trivial
    // wrapper for 'get_row_assoc()' with 'json_encode'
    function get_row_json($rowNumber) {
        return json_encode($this->get_row_assoc($rowNumber));
    }

    // get_rows_*() - returns an array of row arrays; the range arguments
    // are inclusive
    function get_rows_assoc($start = 1,$end = -1) {
        if (is_a($this->result,'mysqli_result')) {
            if ($end < $start)
                $end = $this->result->num_rows;
            $arr = array();
            for ($i = $start;$i <= $end;++$i) {
                $arr[] = $this->get_row_assoc($i);
            }
            return $arr;
        }
        throw new Exception("call to " . __FUNCTION__ . " is illegal for "
            . "non-result queries");
    }
    function get_rows_ordered($start = 1,$end = -1) {
        if (is_a($this->result,'mysqli_result')) {
            if ($end < $start)
                $end = $this->result->num_rows;
            $arr = array();
            for ($i = $start;$i <= $end;++$i) {
                $arr[] = $this->get_row_ordered($i);
            }
            return $arr;
        }
        throw new Exception("call to " . __FUNCTION__ . " is illegal for "
            . "non-result queries");
    }

    // htmlify() - turn database result into HTML table; the range arguments are
    // inclusive
    function htmlify($includeHeaders,$class,$start = 1,$end = -1) {
        if (is_a($this->result,'mysqli_result')) {
            $inner = '';

            // show bold field names as first row if specified
            if ($includeHeaders) {
                $row = '';
                $fieldNames = $this->result->fetch_fields();
                foreach ($fieldNames as $field) {
                    $row .= "<td><b>$field</b></td>";
                }
                $inner .= "<tr>$row</tr>";
            }

            if ($end < $start)
                $end = $this->result->num_rows;
            for ($i = $start;$i <= $end;++$i) {
                $arr = $this->get_row_ordered($i);
                $row = '';
                foreach ($arr as $field) {
                    $row .= "<td><b>$field</b></td>";
                }
                $inner .= "<tr>$row</tr>";
            }

            $tableAttr = 'border="1"';
            if (is_string($class))
                $tableAttr .= " class=\"$class\"";
            return "<table $tableAttr>$inner</table>";
        }
        throw new Exception("call to " . __FUNCTION__ . " is illegal for "
            . "non-result queries");
    }
}

define('INSERT_QUERY','insert');
define('UPDATE_QUERY','update');
define('SELECT_QUERY','select');
define('DELETE_QUERY','delete');

class QueryBuilder {
    private $qstring;

    /*
        The QueryBuilder supports the following keys for the 'info' parameter:
            [insert]
                'fields': array of field names for update
                'values': array of arrays of values
                'table': the table into which to insert

            [update]
                'updates': array of column-name => value
                'table': the table to update
                'where': where clause for update (sql) [optional]
                'limit': limit (sql) [optional: default value is 1]

            [select]
                'tables': array of table-name => (array of field-name)
                'aliases': array of 'table.field' => alias-name [optional]
                'joins': array of table-name => sql [optional]
                'where': where clause (sql) [optional]
                'orderby': order by clause (sql) [optional]
                'limit': limit (sql) [optional]

                notes: the order of the elements in 'tables' determines order
                of columns; 'joins' should specify joins for all tables except
                the first one; 'joins' must specify the sql statement (e.g.
                INNER JOIN a ON a.id = b.id) using table-qualified field names;
                'orderby' just needs to specify the condition using
                table-qualified field names

            [delete]
                'tables': the tables from which to delete
                'where': where clause for the delete (sql) using table-qualified
                         field names
    */

    function __construct($kind,array $info) {
        try {
            switch ($kind) {
            case 'insert':
                $this->qstring = self::insert_string($info);
                break;
            case 'update':
                $this->qstring = self::update_string($info);
                break;
            case 'select':
                $this->qstring = self::select_string($info);
                break;
            case 'delete':
                $this->qstring = self::delete_string($info);
                break;
            default:
                throw new Exception("parameter 'kind' was incorrect in call "
                    . "to " . __FUNCTION__);
            }
        } catch (Exception $e) {
            if (strlen($e->getMessage()) == 0) {
                // create generic exception
                throw new Exception("parameters were not correct for "
                        . "'$kind' operation in call to " . __FUNCTION__);
            }
            else {
                // pass the exception along
                throw $e;
            }
        }

        $this->qstring .= ";";
    }

    // this allows the language to automatically cast to string
    function __toString() {
        return $this->qstring;
    }

    static private function insert_string($info) {
        if (!array_key_exists('fields',$info) || !array_key_exists('values',$info)
            || !array_key_exists('table',$info)) throw new Exception("");

        $q = "INSERT INTO $info[table] (" .
            implode(',',$info['fields']) . ") VALUES ";
        $s = '';
        foreach ($info['values'] as $a) {
            if (!is_array($a)) throw new Exception("");

            $q .= "$s(" . implode(',',$a) . ")";
            $s = ", ";
        }

        return $q;
    }

    static private function update_string($info) {
        if (!array_key_exists('updates',$info) || !array_key_exists('table',$info))
            throw new Exception("");

        $q = "UPDATE " . $info['table'] . " SET ";
        $s = '';
        foreach ($updates as $f => $v) {
            $q .= "$s$f=$v";
            $s = ', ';
        }

        if (array_key_exists('where',$info)) {
            $q .= "WHERE " . $info['where'];
        }

        $l = array_key_exists('limit',$info) ? $info['limit'] : 1;
        $q .= "LIMIT $l";

        return $q;
    }

    static private function select_string($info) {
        // 'tables': array of table-name => (array of field-name)
        // 'aliases': array of 'table.field' => alias-name [optional]
        // 'joins': array of table-name => sql [optional]
        // 'orderby': order by clause (sql) [optional]
        // 'limit': limit (sql) [optional]

        if (!array_key_exists('tables',$info) || !is_array($info['tables']))
            throw new Exception("");

        // get lists of tables and fields; qualify each field name
        // with its table name
        $fields = array();
        $tables = array();
        foreach ($info['tables'] as $tbl => $a) {
            if (!is_array($a))
                throw new Exception("");
            $tables[] = $tbl;
            foreach ($a as $fld) {
                $fields[] = "$tbl.$fld";
            }
        }

        // apply any aliases
        if (array_key_exists('aliases',$info)) {
            if (!is_array($info['aliases']))
                throw new Exception("");
            foreach ($info['aliases'] as $name => $alias) {
                $k = array_search($name,$fields);
                if ($k !== false) {
                    $fields[$k] .= " $alias";
                }
            }
        }

        // create first part of select string
        $q = "SELECT " . implode(', ',$fields) . " FROM $tables[0]";

        // handle joins: they should be specified for all tables except first
        if (array_key_exists('joins',$info)) {
            if (!is_array($info['joins']))
                throw new Exception("");
            foreach ($info['joins'] as $join) {
                $q .= " $join";
            }
        }
        else {
            // specify all tables in comma-separated list
            $list = implode(', ',array_slice($tables,1));
            $q .= ", $list";
        }

        // handle order by, where and limit
        if (array_key_exists('orderby',$info)) {
            $q .= " ORDER BY $info[orderby]";
        }
        if (array_key_exists('where',$info)) {
            $q .= " WHERE $info[where]";
        }
        if (array_key_exists('limit',$info)) {
            $q .= " LIMIT $info[limit]";
        }

        return $q;
    }

    static private function delete_string($info) {

    }
}
