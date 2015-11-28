<?php

// misc. library functions/declarations go here

define('OKAY',200);
define('BAD_REQUEST',400);
define('UNAUTHORIZED',401);
define('SERVER_ERROR',500);

function page_fail($code) {
    echo "{\"success\":false}";
    http_response_code($code);
    exit;
}

function page_fail_with_reason($code,$reason) {
    echo json_encode(array("success"=>false,"error"=>$reason));
    http_response_code($code);
    exit;
}

function page_fail_on_field($code,$field,$error) {
    echo json_encode(array( "success"=>false, "errField" => $field, "error" => $error ));
    http_response_code($code);
    exit;
}

// this function checks the ACLs for assessments to verify that the specified
// user has access to the assessment that contains the specified entity
function check_assessment_access($userId,$entityId,$entityKind) {
    $query = new Query(new QueryBuilder(SELECT_QUERY,array(
        'tables' => array(
            "abet_assessment" => '',
            $entityKind => 'id'
        ),
        'joins' => array(
            "INNER JOIN $entityKind ON $entityKind.fk_assessment = abet_assessment.id",
            "INNER JOIN acl ON abet_assessment.fk_acl = acl.id",
            "INNER JOIN acl_entry ON acl_entry.fk_acl = acl.id AND acl_entry.fk_profile = $userId"
        )
    )));

    $found = false;
    $query->for_each_ordered(function($row) use(&$found) {
        if ($row[0] == $entityId) {
            $found = true;
            return false;
        }
    });

    return $found;
}
