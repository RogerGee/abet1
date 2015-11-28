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

/* check-passwd.php - JSON transfer specification
    Supports: POST

    Fields: (POST)
    *--------*
    | passwd |
    *--------*

    This script checks password for a currently authenticated user. If the password
    matches then {"success":true} is returned; otherwise {"success":false} is returned.
*/

if (!abet_is_authenticated()) {
    page_fail(UNAUTHORIZED);
}

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !array_key_exists('passwd',$_POST)) {
    page_fail(BAD_REQUEST);
}

if (!abet_verify($_SESSION['user'],$_POST['passwd'],$id,$role)) {
    page_fail(BAD_REQUEST);
}

echo "{\"success\":true}";
