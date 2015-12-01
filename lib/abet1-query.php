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

    private $stmt; // mysqli_stmt
    private $fieldNames; // string field names
    private $isResultBased; // true if query result should have results

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

    static function perform_transaction(callable $func) {
        $con = self::connect_db();
        $doRollback = false;
        $con->query("START TRANSACTION");
        $ref = &$doRollback; // must create value of reference to variable
        $ret = call_user_func($func,$ref);
        if ($doRollback)
            $con->query("ROLLBACK");
        else
            $con->query("COMMIT");
        return $ret;
    }

    function __construct(QueryBuilder $builder) {
        $con = self::connect_db();

        // create a prepared statement, using query info from the QueryBuilder
        $this->stmt = $con->prepare($builder);
        if (!$this->stmt) {
            throw new Exception("database query failed: $con->error");
        }
        $builder->apply_preparations($this->stmt);
        if (!$this->stmt->execute()) {
            throw new Exception("database query failed: $this->stmt->error");
        }

        // cache results
        $this->stmt->store_result();
        $this->isResultBased = $builder->kind == SELECT_QUERY
            || strpos(strtolower("$builder"),'select') === 0;

        // get field names into array
        $metadata = $this->stmt->result_metadata();
        if ($metadata) {
            // grab field names
            $flds = $metadata->fetch_fields();
            $this->fieldNames = array_map(function($x){return $x->name;},$flds);
            $tableNames = array_map(function($x){return $x->table;},$flds);
            unset($flds);

            // if their are duplicates, then we must qualify them with their
            // table names; we do this for convenience so that if no duplicates
            // exist the table name is not required
            $seen = array();
            for ($i = 0;$i < count($this->fieldNames);$i++) {
                $name = $this->fieldNames[$i];
                if (array_key_exists($name,$seen)) {
                    $seen[$name][] = $i;
                }
                else {
                    $seen[$name] = array($i);
                }
            }
            foreach ($seen as $name => $indexList) {
                if (count($indexList) > 1) {
                    foreach ($indexList as $i) {
                        $this->fieldNames[$i] = "$tableNames[$i].$name";
                    }
                }
            }

            $metadata->close();
        }
    }

    function __destruct() {
        $this->stmt->free_result();
        $this->stmt->close();
    }

    // get_stmt() - gets the underlying mysqli_stmt object
    function get_stmt() {
        return $this->stmt;
    }

    // validate_update() - determines if a query modified the specified
    // number of rows; note that updates that don't change an existing database
    // value will not modify the row
    function validate_update($numRows = 1) {
        return $this->stmt->affected_rows == $numRows;
    }

    // is_empty() - determines if result had no rows
    function is_empty() {
        if ($this->isResultBased) {
            return $this->stmt->num_rows == 0;
        }
        throw new Exception("call to " . __FUNCTION__ . " is illegal for "
            . "non-result queries");
    }

    // these functions return number of rows/columns
    function get_number_of_rows() {
        if ($this->isResultBased)
            return $this->stmt->num_rows;
        throw new Exception("call to " . __FUNCTION__ . " is illegal for "
            . "non-result queries");
    }
    function get_number_of_columns() {
        if ($this->isResultBased)
            return $this->stmt->field_count;
        throw new Exception("call to " . __FUNCTION__ . " is illegal for "
            . "non-result queries");
    }

    // call the callback for each row (associative ordering); you can break by returning
    // false in $func on an arbitrary iteration
    function for_each_assoc(callable $func) {
        if ($this->isResultBased) {
            for ($i = 1;$i <= $this->get_number_of_rows();$i++)
                if (call_user_func($func,$this->get_row_assoc($i)) === false)
                    break;
        }
        else {
            throw new Exception("call to " . __FUNCTION__ . " is illegal for "
                . "non-result queries");
        }
    }

    // call the callback for each row (numeric ordering); you can break by returning
    // false in $func on an arbitrary iteration
    function for_each_ordered(callable $func) {
        if ($this->isResultBased) {
            for ($i = 1;$i <= $this->get_number_of_rows();$i++)
                if (call_user_func($func,$this->get_row_ordered($i)) === false)
                    break;
        }
        else {
            throw new Exception("call to " . __FUNCTION__ . " is illegal for "
                . "non-result queries");
        }
    }

    // get_row_assoc() - obtain associative array with field keys for a given
    // row; the row must be a 1-based row number; the result array is associative
    function get_row_assoc($rowNumber = 1) {
        if ($this->isResultBased) {
            foreach ($this->fieldNames as $__name) {
                // use variable variables to create unique variables for each field
                $$__name = null;
                $args[$__name] = &$$__name;
            }
            call_user_func_array(array($this->stmt,'bind_result'),$args);

            $this->stmt->data_seek($rowNumber-1);
            if ($this->stmt->fetch()) { // get the one row
                foreach ($args as $k => $v)
                    $ret[$k] = $v;
                return $ret;
            }

            return null;
        }
        throw new Exception("call to " . __FUNCTION__ . " is illegal for "
            . "non-result queries");
    }

    // get_row_ordered() - like get_row_assoc() but returns an ordered array
    // with fields keyed to indeces in the order specified by the query
    function get_row_ordered($rowNumber = 1) {
        if ($this->isResultBased) {
            foreach ($this->fieldNames as $__name) {
                // use variable variables to create unique variables for each field
                $$__name = null;
                $args[] = &$$__name;
            }
            call_user_func_array(array($this->stmt,'bind_result'),$args);

            $this->stmt->data_seek($rowNumber-1);
            if ($this->stmt->fetch()) { // get the one row
                foreach ($args as $a)
                    $ret[] = $a;
                return $ret;
            }

            return null;
        }
        throw new Exception("call to " . __FUNCTION__ . " is illegal for "
            . "non-result queries");
    }

    // get_row_json() - obtain JSON-encoded database row; this is just a trivial
    // wrapper for 'get_row_assoc()' with 'json_encode'
    function get_row_json($rowNumber = 1) {
        return json_encode($this->get_row_assoc($rowNumber));
    }

    // get_rows_*() - returns an array of row arrays; the range arguments
    // are inclusive
    function get_rows_assoc($start = 1,$end = -1) {
        if ($this->isResultBased) {
            if ($end < $start)
                $end = $this->stmt->num_rows;
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
        if ($this->isResultBased) {
            if ($end < $start)
                $end = $this->stmt->num_rows;
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
        if ($this->isResultBased) {
            $inner = '';

            // show bold field names as first row if specified
            if ($includeHeaders) {
                $row = '';
                foreach ($this->fieldNames as $field) {
                    $row .= "<td><b>$field</b></td>";
                }
                $inner .= "<tr>$row</tr>";
            }

            if ($end < $start)
                $end = $this->stmt->num_rows;
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
define('RAW_QUERY','raw');

class QueryBuilder {
    /*
        The QueryBuilder supports the following keys for the 'info' parameter:

            Note: every 'variable' is a string consisting of '<c>:<value>' where
            <c> is one of:
                i (integer)
                d (double)
                s (string)
                b (blob)
                l (literal SQL)

            [insert]
                'fields': array of field names for insert
                'values': array of arrays of variables
                'table': the table into which to insert
                'select': optional replacement for 'values'; will run select
                          statement to get values for insertion; if found any
                          'values' element will be ignored; the parameters are
                          the same as described under [select]

            [update]
                'updates': array of (column-name => variable)
                'table': the table to update
                'where': where clause for update (sql) [optional]
                'where-params': array of variables for where-clause [optional]
                'limit': limit (sql) [optional: default value is 1]

                Notes: you must specify at least 'where' or 'limit' for safe-mode
                update operations.

            [select]
                'tables': array of table-name => (array of field-name)
                'aliases': array of 'table.field' => alias-name [optional]
                'joins': array of table-name => sql [optional] (may be single string)
                'where': where-clause (sql) [optional]
                'where-params': array of variables for where-clause [optional]
                'orderby': order by clause (sql) [optional]
                'orderby-params': array of variables for orderby-clause [optional]
                'limit': limit (sql) [optional]

                Notes: the order of the elements in 'tables' determines order
                of columns; 'joins' should specify joins for all tables except
                the first one; 'joins' must specify the sql statement (e.g.
                INNER JOIN a ON a.id = b.id) using table-qualified field names;
                'orderby' just needs to specify the condition using
                table-qualified field names

            [delete]
                'tables': array of tables from which to delete (may be single string)
                'joins': array of table-name => sql [optional] (may be single string)
                'where': where clause for the delete (sql)
                'where-params': array of variables for where-clause [optional]
                'limit': limit (sql) [optional]

                Notes: you must specify at least one of 'where' or 'limit' due
                to mySQL safe-mode; 'joins' may reference other tables that
                are not to be deleted from

            [raw]
                'query': raw query string
                'variables': preparations for query string
    */

    public $kind;
    private $qstring; // string
    private $preps = array(); // array of stdClass

    function __construct($kind,array $info) {
        $this->kind = $kind;
        try {
            switch ($kind) {
            case 'insert':
                $this->qstring = $this->insert_string($info);
                break;
            case 'update':
                $this->qstring = $this->update_string($info);
                break;
            case 'select':
                $this->qstring = $this->select_string($info);
                break;
            case 'delete':
                $this->qstring = $this->delete_string($info);
                break;
            case 'raw':
                $this->qstring = $this->raw_string($info);
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

    // this function binds parameters in place for a prepared SQL statement
    function apply_preparations(mysqli_stmt $stmt) {
        if (count($this->preps) > 0) {
            $args = array("");
            foreach ($this->preps as $prep) {
                $args[0] .= $prep->type;
                $args[] = &$prep->value;
            }
            call_user_func_array(array($stmt,'bind_param'),$args);
        }
    }

    private function make_prep($var) {
        $a = explode(':',$var,2);
        if (count($a) < 2 || ($a[0] != 'i' && $a[0] != 'd' && $a[0] != 's' && $a[0] != 'b' && $a[0] != 'l'))
            throw new Exception("bad variable value in creation of QueryBuilder");
        if ($a[0] == "l")
            // return literal SQL code
            return $a[1];
        $o = new stdClass;
        $o->type = $a[0];
        $o->value = $a[1];
        $this->preps[] = $o;
        return '?';
    }

    private function insert_string($info) {
        if (!array_key_exists('fields',$info) || (!array_key_exists('values',$info)
            && !array_key_exists('select',$info)) || !array_key_exists('table',$info))
                throw new Exception("");

        $q = "INSERT INTO $info[table] (" .
            implode(',',$info['fields']) . ") ";

        // if 'values' was specified, then use a VALUES clause to insert values
        // directly
        if (array_key_exists('values',$info)) {
            if (is_null($info['values']) || count($info['values']) == 0)
                $q .= "VALUES()"; // inserting empty row
            else {
                $s = 'VALUES ';
                foreach ($info['values'] as $a) {
                    if (!is_array($a)) throw new Exception("");

                    // turn each value into a prepared parameter before adding
                    // to the query; this replaces each value with '?'
                    $q .= "$s(" . implode(',',array_map(array($this,'make_prep'),$a)) . ")";
                    $s = ", ";
                }
            }
        }

        // otherwise use a select statement to generate the insert values
        else /*if (array_key_exists('select',$info))*/ {
            // just use the QueryBuilder functionality to generate the string
            $sel = new QueryBuilder(SELECT_QUERY,$info['select']);
            $q .= substr($sel,0,strlen($sel)-1);
        }

        return $q;
    }

    private function update_string($info) {
        if (!array_key_exists('updates',$info) || !array_key_exists('table',$info)
                || (!array_key_exists('limit',$info) && !array_key_exists('where',$info)))
            throw new Exception("");

        $q = "UPDATE " . $info['table'] . " SET ";
        $s = '';
        foreach ($info['updates'] as $f => $v) {
            $v = $this->make_prep($v); // make value into prepared parameter
            $q .= "$s$f = $v";
            $s = ', ';
        }

        if (array_key_exists('where',$info)) {
            if (array_key_exists('where-params',$info)) {
                if (!is_array($info['where-params']))
                    throw new Exception("");
                // if the user specified this, then they used unbound variables
                // in the where-clause; now we must create the bindings
                array_walk($info['where-params'],array($this,'make_prep'));
            }

            $q .= " WHERE $info[where]";
        }

        $l = array_key_exists('limit',$info) ? $info['limit'] : 1;
        $q .= " LIMIT $l";

        return $q;
    }

    private function select_string($info) {
        if (!array_key_exists('tables',$info) || !is_array($info['tables']))
            throw new Exception("");

        // get lists of tables and fields; qualify each field name
        // with its table name
        $fields = array();
        $tables = array();
        foreach ($info['tables'] as $tbl => $a) {
            if (is_string($tbl))
                $tables[] = $tbl;
            if (is_array($a)) {
                foreach ($a as $fld) {
                    if ($fld === "") {
                        continue;
                    }
                    if (!is_string($tbl))
                        // this is a literal value in the select statement; we ignore
                        // the table in this instance because it is just a placeholder
                        $fields[] = "$fld";
                    else
                        $fields[] = "$tbl.$fld";
                }
            }
            else if ($a !== "") {
                // for convenience we allow the mapped element to be a string in
                // case the user wants to specify a single field
                if (!is_string($tbl))
                    $fields[] = "$a";
                else
                    $fields[] = "$tbl.$a";
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
            if (!is_array($info['joins'])) {
                $q .= " $info[joins]";
            }
            else {
                foreach ($info['joins'] as $join)
                    $q .= " $join";
            }
        }
        else if (count($tables) > 1) {
            // specify all tables in comma-separated list
            $list = implode(', ',array_slice($tables,1));
            $q .= ", $list";
        }
        // else only one table

        // handle order by, where and limit
        if (array_key_exists('orderby',$info)) {
            if (array_key_exists('orderby-params',$info)) {
                if (!is_array($info['orderby-params']))
                    throw new Exception("");
                // if the user specified this, then they used unbound variables
                // in the orderby-clause; now we must create the bindings
                array_walk($info['orderby-params'],array($this,'make_prep'));
            }

            $q .= " ORDER BY $info[orderby]";
        }
        if (array_key_exists('where',$info)) {
            if (array_key_exists('where-params',$info)) {
                if (!is_array($info['where-params']))
                    throw new Exception("");
                // if the user specified this, then they used unbound variables
                // in the where-clause; now we must create the bindings
                array_walk($info['where-params'],array($this,'make_prep'));
            }

            $q .= " WHERE $info[where]";
        }
        if (array_key_exists('limit',$info)) {
            $q .= " LIMIT $info[limit]";
        }

        return $q;
    }

    private function delete_string($info) {
        if (!array_key_exists('tables',$info) || !array_key_exists('where',$info)
                || (!array_key_exists('where',$info) && !array_key_exists('limit',$info)))
            throw new Exception("");

        // prepare table names
        $q = "DELETE FROM ";
        if (is_array($info['tables']))
            $q .= implode(', ',$info['tables']);
        else
            $q .= "$info[tables]";

        // prepare joins
        if (array_key_exists('joins',$info)) {
            if (is_array($info['joins'])) {
                foreach ($info['joins'] as $join)
                    $q .= " $join";
            }
            else
                $q .= "$info[joins]";
        }

        // prepare where clause
        if (array_key_exists('where',$info)) {
            if (array_key_exists('where-params',$info)) {
                if (!is_array($info['where-params']))
                    throw new Exception("");
                // if the user specified this, then they used unbound variables
                // in the where-clause; now we must create the bindings
                array_walk($info['where-params'],array($this,'make_prep'));
            }

            $q .= " WHERE $info[where]";
        }

        // prepare limit clause
        if (array_key_exists('limit',$info)) {
            $q .= " LIMIT $info[limit]";
        }

        return $q;
    }

    private function raw_string($info) {
        if (!array_key_exists('query',$info)) {
            throw new Exception("");
        }

        $q = "$info[query]";
        if (array_key_exists('variables',$info))
            array_wak($info['variables'],array($this,'make_prep'));

        return $q;
    }
}
