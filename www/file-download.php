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

/* file-download.php

    This script uses the GET method to download a file. We take the 'id' of
    a file_upload entity as the GET argument. The script checks access to the
    file before allowing it to be downloaded.
*/

// check general authentication mode
if (!abet_is_authenticated()) {
    http_response_code(UNAUTHORIZED);
    header('Content-Type: text/html');
    echo "<h1>Access to the specified object is unauthorized.</h1>";
    exit;
}

// check for correct GET variables
if (!array_key_exists('id',$_GET)) {
    http_response_code(BAD_REQUEST);
    header('Content-Type: text/html');
    echo "<h1>Bad request: try again...";
    exit;
}

// check access to specific file resource
if (!abet_is_admin_authenticated() && !abet_is_observer()
    && !check_general_content_item_access($_SESSION['id'],$_GET['id'],'file_upload',$found))
{
    header('Content-Type: text/html');
    if ($found) {
        http_response_code(UNAUTHORIZED);
        echo "<h1>Access to the specified object is unauthorized or it has been removed.</h1>";
    }
    else {
        http_response_code(NOT_FOUND);
        echo "<h1>The specified object was not found. It's possible it was removed.</h1>";
    }
    exit;
}

// call routine to output file
file_download($_GET['id']);
