<?php

// misc. library functions goes here

define(OKAY,200);
define(BAD_REQUEST,400);
define(UNAUTHORIZED,401);
define(SERVER_ERROR,500);

function page_fail($code) {
    echo "{\"success\":false}";
    http_response_code($code);
    exit;
}

function page_fail_with_reason($code,$reason) {
    echo json_encode(array("error"=>$reason));
    http_response_code($code);
    exit;
}

function page_fail_on_field($code,$field,$error) {
    echo json_encode(array( "errField" => $field, "error" => $error ));
    http_response_code($code);
    exit;
}
