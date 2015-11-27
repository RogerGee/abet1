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

/* change-passwd.php - JSON transfer specification
    Supports: POST

    Fields: (POST)
    *-----------------------*
    | old_passwd new_passwd |
    *-----------------------*

    This script allows an authenticated user to change their password. They must
    supply their current password for the new one to be accepted.

    On success, the JSON object {"success":true} will be returned. Otherwise the
    object {"success":false} will be returned with some non-200 http response code.
*/

if (!abet_is_authenticated()) {
    page_fail(401); // Unauthorized
}

if ($_SERVER['REQUEST_METHOD'] != 'POST' || !array_key_exists('old_passwd',$_POST)
        || !array_key_exists('new_passwd',$_POST)) {
    page_fail(400); // Bad Request
}

// verify old password
if (!abet_verify($_SESSION['user'],$_POST['old_passwd'],$id,$role)) {
    page_fail_on_field(400,"old_passwd","password was incorrect");
}

// attempt to update passwords; if this fails then the user used one of their
// old passwords
if (!abet_change_password($_SESSION['user'],$_POST['new_passwd'])) {
    page_fail_on_field(400,'new_passwd','password was previously used');
}

echo "{\"success\":true}";
