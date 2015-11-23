<?php

// include needed files; update the include path to find the libraries
$paths = array(
	'/usr/lib/abet1',
	'/usr/local/lib/abet1',
	'../lib',
	get_include_path()
);
set_include_path(implode(PATH_SEPARATOR,$paths));
require_once 'abet1-login.php';
require_once 'abet1-query.php';

/* profile.php - JSON transfer specification
    Supports: GET POST

    Fields: (GET/POST)
    *----------------------------------------------------------------*
    | username first_name middle_initial last_name suffix gender bio |
    | email_addr office_phone mobile_phone                           |
    *----------------------------------------------------------------*

    This script updates or retrieves a userprofile object (described by the
    fields above).

    If something bad happens the returned JSON object will have a member called
    'error' that maps to an error message. Additionally, the member 'errField'
    may be defined which indicates the field to which the error is attributed.

	HTTP 400 always is accompanied by a JSON object with 'error' and 'errField'.

    On success, a POST response will return the JSON object {"success":true}. On
    failure it will return {"success":false}.
*/

header('Content-Type: application/json');

if (!abet_is_authenticated()) {
    echo json_encode(array("error"=>"no authentication mode"));
    http_response_code(401); // Unauthorized
    exit;
}

// user has been authenticated; use $_SESSION[id] to find userprofile

// client is GETting userprofile information for user
if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    // grab all the user information, JSONify it and send it to client
    $result = (new Query(new QueryBuilder(SELECT_QUERY,array(
        'tables' => array(
            'userprofile' => array(
                'username', 'first_name', 'middle_initial', 'last_name',
                'suffix', 'gender', 'bio', 'email_addr', 'office_phone',
                'mobile_phone'
            )
        ),
        'where' => "id = ?",
        'where-params' => array("i:$_SESSION[id]") ))))->get_row_assoc();

    if (is_null($result)) {
        echo json_encode(array("error"=>"failed to retrieve user-profile"));
        http_response_code(500); // Server Error
    }
    else
        // encode selections from database
        echo json_encode($result);
}

// client is POSTing user-profile information for user
else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // update user information; we update the entire 'userprofile' entity for
    // the current user; we perform minimal validation on the data entries

    $info = array(
        'table' => 'userprofile',
        'updates' => array(),
        'where' => 'userprofile.id = ?',
        'where-params' => array("i:$_SESSION[id]")
    );

    // go through each possible field; the client doesn't have to specify
    // all of them; we just update the ones that exist within $_POST

    // handle the fields that require explicit validation
    if (array_key_exists('username',$_POST)) {
		$un = strtolower($_POST['username']);
		if ($un != $_POST['username']) {
			echo json_encode(array(
				"error" => "username must begin with alphabetic character",
				"errField"=>"username"
			));
			http_response_code(400);
			exit;
		}
		unset($un);
		if (!ctyle_alpha($_POST['username'][0])) {
			echo json_encode(array("error"=>"username must begin with alphabetic character",
				"errField"=>"username"));
			http_response_code(400); // Bad Request
			exit;
		}

        // make sure username does not already exist in database for
        // another user-profile
        $result = (new Query(new QueryBuilder(SELECT_QUERY,array(
            'tables' => array(
                'userprofile' => array(
                    'username'
                )
            ),
            'where' => "id <> ? AND username = ?",
            'where-params' => array("i:$_SESSION[id]", "s:$_POST[username]")
        ))))->get_row_ordered();

        if (!is_null($result)) {
            // another user is using the same username
            echo json_encode(array(
                "error" => "the requested username is unavailable",
                "errField" => "username"
            ));
            http_response_code(400); // Bad Request
            exit;
        }

        $info['updates']['username'] = "s:$_POST[username]";
    }
    if (array_key_exists('gender',$_POST)) {
        // must be either "male" OR "female"
        $gs = strtolower($_POST['gender']);
        if ($gs != 'male' && $gs != 'female') {
            echo json_encode(array(
                "error"=>"gender must be either 'male' or 'female'",
                "errField"=>"gender"));
            http_response_code(400); // Bad Request
            exit;
        }

        $info['updates']['gender'] = "s:$gs";
    }
    if (array_key_exists('email_addr',$_POST)) {
        if (!filter_var($_POST['email_addr'],FILTER_VALIDATE_EMAIL)) {
            echo json_encode(array(
                "error"=>"email address is invalid",
                "errField"=>"email_addr"));
            http_response_code(400); // Bad Request
            exit;
        }

        $info['updates']['email_addr'] = "s:$_POST[email_addr]";
    }

    // handle the rest of them that don't require validation
    foreach (array('first_name', 'middle_initial', 'last_name',
        'suffix', 'bio', 'office_phone', 'mobile_phone') as $f)
    {
        if (array_key_exists($f,$_POST))
            $info['updates'][$f] = "s:$_POST[$f]";
    }

    if (count($info['updates']) > 0) {
        // run an update query to update the user-profile
        $query = new Query(new QueryBuilder(UPDATE_QUERY,$info));
    }

    echo '{"success":true}';
}
