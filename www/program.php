<?php

// include needed files; update the include path to find the libraries
$paths = array(
    get_include_path(),
    '/usr/lib/abet1',
    '/usr/local/lib/abet1'
);
set_include_path(implode(PATH_SEPARATOR,$paths));
require_once 'abet1-login.php';
require_once 'abet1-query.php';
require_once 'abet1-misc.php';

/* program.php - JSON transfer specification

    Fields: GET
     [Optional: if nothing sent then we create a new program]
    *----*
    | id |
    *----*

    Fields: POST
    *-----------------------------------------*
    | id name abbrv semester year description |
    *-----------------------------------------*

    This script creates/edits programs. The user must be an admin to do this. If
    the GET method is invoked the script either
        1) gets a specified course object given an 'id'
        2) creates a new course object and returns it (when no parameters are specified)

    If the method is POST, the script expects an object just like the one it returns
    on GET. It will make the necessary updates to the database. The only field validation
    performed is to ensure fields are non-empty and exist.
*/

header('Content-Type: application/json');

// verify login; user must be an admin
if (!abet_is_admin_authenticated())
    page_fail(UNAUTHORIZED);

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (array_key_exists('id',$_GET)) {
        // get existing course
        $query = new Query(new QueryBuilder(SELECT_QUERY,array(
            'tables' => array(
                'course' => array(
                    'id', 'name', 'abbrv', 'semester', 'year', 'description'
                )
            ),
            'where' => 'id = ?',
            'where-params' => array("s:$_GET[id]")
        )));
        $row = $query->get_row_assoc();
        if (is_null($row))
            page_fail(NOT_FOUND);

        return json_encode($row);
    }
    else {
        // create new course
        list($code,$json) = Query::perform_transaction(function(&$rollback){
            // insert new row for new course
            $insert = new Query(new QueryBuilder(INSERT_QUERY,array(
                'table' => 'course',
                'fields' => array('name'),
                'values' => array(array("l:New Program"))
            )));
            if (!$insert->validate_update()) {
                $rollback = true;
                return array(SERVER_ERROR,"an insertion operation failed");
            }

            // grab the new course object
            $query = new Query(new QueryBuilder(SELECT_QUERY,array(
                'tables' => array(
                    'course' => array(
                        'id', 'name', 'abbrv', 'semester', 'year', 'description'
                    )
                ),
                'where' => 'id = LAST_INSERT_ID()'
            )));
            $row = $query->get_row_assoc();
            if (is_null($row)) {
                $rollback = true;
                return array(SERVER_ERROR,"could not retrieve inserted row");
            }

            return array(OKAY,json_encode($row));
        });

        http_response_code($code);
        echo $json;
    }
}
else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // verify fields
    $a = array_map(
            function($x) {
                return array_key_exists($x,$_POST) && !is_null($_POST[$x])
                        && $_POST[$x] !== '';
            },
            array('id', 'name', 'semester', 'year', 'description')
    );
    if (in_array(false,$a))
        page_fail_with_reason(BAD_REQUEST,"missing or empty field name");

    // update the specified element
    $query = new Query(new QueryBuilder(UPDATE_QUERY,array(
        'table' => 'course',
        'updates' => array(
            'name' => "s:$_POST[name]",
            'abbrv' => "s:$_POST[abbrv]",
            'semester' => "s:$_POST[semester]",
            'year' => "i:$_POST[year]",
            'description' => "s:$_POST[description]"
        ),
        'where' => 'id = ?',
        'where-params' => array("i:$_POST[id]"),
        'limit' => 1
    )));
    if (!$query->validate_update())
        page_fail(NOT_FOUND); // assume not found if failed update

    echo "{\"success\":true}";
}
else
    page_fail(BAD_REQUEST);
