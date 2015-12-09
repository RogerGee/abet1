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

/* characteristics.php - JSON transfer specification
    Supports: GET/POST

    GET:
    n/a

    response:
    *-------------------------------------------------------------------*
    | chars:[{id,level,short_name,description,program_specifier} array] |
    | prog_spec :[string array]                                         |
    *-------------------------------------------------------------------*

    POST: edit existing characteristic (with id)
    *---------------------------------------------------*
    | id level short_name description program_specifier |
    *---------------------------------------------------*

    POST: create new characteristic (without id)
    *------------------------------------------------*
    | level short_name description program_specifier |
    *------------------------------------------------*
*/

// get program specifiers: we select these from the program abbreviations
function get_prog_specs() {
    $query = new Query(new QueryBuilder(SELECT_QUERY,array(
        'tables' => array(
            'program' => 'abbrv'
        ),
        'orderby' => 'program.abbrv'
    )));

    return call_user_func_array('array_merge',$query->get_rows_ordered());
}

// get list of characteristics
function get_characteristics() {
    $query = new Query(new QueryBuilder(SELECT_QUERY,array(
        'tables' => array(
            'abet_characteristic' => array(
                'id', 'level', 'short_name', 'description', 'program_specifier'
            )
        ),
        'orderby' => 'CHAR_LENGTH(level), level',
    )));

    return $query->get_rows_assoc();
}

// edit existing characteristic
function update_characteristic($id,$level,$shortName,$description,$programSpecifier) {
    $updates = array();
    if (!is_null($level)) {
        if ($level == "")
            page_fail_on_field(BAD_REQUEST,'level','must specify organizational level');
        $updates['level'] = "s:$level";
    }
    if (!is_null($shortName)) {
        if ($shortName == "")
            page_fail_on_field(BAD_REQUEST,'short_name','must specify short name');
        $updates['short_name'] = "s:$shortName";
    }
    if (!is_null($description)) {
        if ($description == "")
            page_fail_on_field(BAD_REQUEST,'description','must specify description');
        $updates['description'] = "s:$description";
    }
    if (!is_null($programSpecifier) && $programSpecifier != "")
        $updates['program_specifier'] = "s:$programSpecifier";

    generic_update('abet_characteristic',$id,$updates);
    echo "{\"success\":true}";
}

function create_characteristic($level,$shortName,$description,$programSpecifier) {
    if (is_null($level) || $level == "")
        page_fail_on_field(BAD_REQUEST,'level','must be non-empty');
    if (is_null($shortName) || $shortName == "")
        page_fail_on_field(BAD_REQUEST,'short_name','must be non-empty');
    if (is_null($description) || $description == "")
        page_fail_on_field(BAD_REQUEST,'description','must be non-empty');

    $info = array(
        'table' => 'abet_characteristic',
        'fields' => array('level','short_name','description'),
        'values' => array(array("s:$level","s:$shortName","s:$description"))
    );

    if (!is_null($programSpecifier) && $programSpecifier != "") {
        $info['fields'][] = 'program_specifier';
        $info['values'][0][] = "s:$programSpecifier";
    }

    list($code,$json) = Query::perform_transaction(function(&$rollback) use($info) {
        $insert = new Query(new QueryBuilder(INSERT_QUERY,$info));
        if (!$insert->validate_update()) {
            $rollback = true;
            return array(SERVER_ERROR,"{\"success\":false}");
        }

        $query = new Query(new QueryBuilder(SELECT_QUERY,array(
            'tables' => array(
                'abet_characteristic' => array(
                    'id', 'level', 'short_name', 'description', 'program_specifier'
                )
            ),
            'where' => 'abet_characteristic.id = LAST_INSERT_ID()'
        )));
        if ($query->is_empty()) {
            $rollback = true;
            return array(SERVER_ERROR,"{\"success\":false}");
        }

        return array(OKAY,json_encode($query->get_row_assoc()));
    });

    http_response_code($code);
    return $json;
}

header('Content-Type: application/json');

if (!abet_is_admin_authenticated())
    page_fail(UNAUTHORIZED);

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $obj = new stdClass;
    $obj->chars = get_characteristics();
    $obj->prog_spec = get_prog_specs();
    echo json_encode($obj);
}
else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!array_key_exists('id',$_POST)) {
        // create new characteristic
        echo create_characteristic(
            isset($_POST['level']) ? $_POST['level'] : null,
            isset($_POST['short_name']) ? $_POST['short_name'] : null,
            isset($_POST['description']) ? $_POST['description'] : null,
            isset($_POST['program_specifier']) ? $_POST['program_specifier'] : null
        );
    }
    else {
        // edit existing characteristic
        echo update_characteristic(
            $_POST['id'],
            isset($_POST['level']) ? $_POST['level'] : null,
            isset($_POST['short_name']) ? $_POST['short_name'] : null,
            isset($_POST['description']) ? $_POST['description'] : null,
            isset($_POST['program_specifier']) ? $_POST['program_specifier'] : null
        );
    }
}
else
    page_fail(BAD_REQUEST);
