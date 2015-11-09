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
            throw new Exception("database query failed");
        }
    }

    function __destruct() {
        if (is_a($this->result,'mysqli_result')) {
            $this->result->close();
        }
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
            $this->result->dataSeek($rowNumber-1);
            return $this->fetch_assoc();
        }
        throw new Exception("call to " . __FUNCTION__ . " is illegal for "
            . "non-result queries");
    }

    // get_row_ordered() - like get_row_assoc() but returns an ordered array
    // with fields keyed to indeces in the order specified by the query
    function get_row_ordered($rowNumber) {
        if (is_a($this->result,'mysqli_result')) {
            $this->result->dataSeek($rowNumber-1);
            return $this->fetch_row();
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
