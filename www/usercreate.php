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

/* usercreate.php - JSON transfer specification
    Supports: POST

    Fields: (POST)
    *----------------------*
    | username passwd role |
    *----------------------*

    This script creates a new user profile on the system. It ONLY handles the
    assignment of the initial username, password and role. The client must call
    the other scripts to update user-profile information. This script checks
    to make sure the client is an authenticated admin before proceeding.

    If something bad happens the returned JSON object will have a member called
    'error' that maps to an error message. Additionally, the member 'errField'
    may be defined which indicates the field to which the error is attributed.

    On success, a response will return the JSON object {"success":true}. On
    failure it will return {"success":false}.
*/

header('Content-Type: application/json');

// the user must have an admin role in order to create user accounts
if (!abet_is_admin_authenticated()) {
    echo json_encode(array("error"=>"no admin authentication mode"));
    http_response_code(UNAUTHORIZED);
    exit;
}

// only POST requests are handled here
if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo '{"success":false}';
    http_response_code(BAD_REQUEST);
    exit;
}

// make sure correct fields were sent
if (!array_key_exists('username',$_POST) || !array_key_exists('passwd',$_POST)
    || !array_key_exists('role',$_POST))
{
    echo '{"success":false}';
    http_response_code(BAD_REQUEST);
    exit;
}

// validate user name: must be lowercase or numeric and start with letter
$un = strtolower($_POST['username']);
if ($un != $_POST['username']) {
    echo json_encode(array("error"=>"username must be lowercase",
        "errField"=>"username"));
    http_response_code(BAD_REQUEST);
    exit;
}
unset($un);
if (!ctype_alpha($_POST['username'][0])) {
    echo json_encode(array("error"=>"username must begin with alphabetic character",
        "errField"=>"username"));
    http_response_code(BAD_REQUEST);
    exit;
}

// perform a transaction that will atomically check the database and do an
// insert
list($code,$json) = Query::perform_transaction(function(&$rollback) {
    // make sure username is not already in use for another user
    $query = new Query(new QueryBuilder(SELECT_QUERY,array(
        'tables' => array('userprofile'=>'username'),
        'where' => 'username = ? AND id <> ?',
        'where-params' => array("s:$_POST[username]","s:$_SESSION[id]"),
        'limit' => 1 )));

    // check select result
    if (!$query->is_empty()) {
        $rollback = true;
        return array(BAD_REQUEST,json_encode(array(
            "error" => "the requested username is unavailable",
            "errField" => "username" )));
    }

    // insert new 'userauth' entity
    $hash = password_hash($_POST['passwd'],PASSWORD_DEFAULT);
    $query = new Query(new QueryBuilder(INSERT_QUERY,array(
        'table' => 'userauth',
        'fields' => array('passwd','role'),
        'values' => array(array("s:$hash","s:$_POST[role]")))));
    if (!$query->validate_update()) {
        $rollback = true;
        return array(SERVER_ERROR,"{\"success\":false}");
    }

    // insert new 'userprofile' entity with foreign key to the newly created
    // 'userauth' entity; we use the password hash to identify the userauth instance
    $query = new Query(new QueryBuilder(INSERT_QUERY,array(
        'table' => 'userprofile',
        'fields' => array('fk_userauth','username'),
        'select' => array(
            'tables' => array(
                'userauth'=>'id',
                1 => "'$_POST[username]'"), // literal username value
            'where' => "passwd = '$hash'" ))));
    if (!$query->validate_update()) {
        $rollback = true;
        return array(SERVER_ERROR,"{\"success\":false}");
    }

    return array(OKAY,"{\"success\":true}");
});

http_response_code($code);
echo $json;
