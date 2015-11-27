<?php

// misc. library functions goes here

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
