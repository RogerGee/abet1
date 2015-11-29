<?php

// misc. library functions/declarations go here

define('OKAY',200);
define('BAD_REQUEST',400);
define('UNAUTHORIZED',401);
define('SERVER_ERROR',500);
define('DEFAULT_CONTENT_TYPE','application/octet-stream');

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

function file_download($fileId) {
    // get file info and contents: the file should be big enough to hold in
    // memory until we can send it
    $result = (new Query(new QueryBuilder(SELECT_QUERY,array(
        'tables' => array(
            'file_upload' => array(
                'file_name', 'file_contents'
            ),
            1 => 'OCTET_LENGTH(file_contents) file_size'
        ),
        'where' => 'file_upload.id = ?',
        'where-params' => array("i:$fileId")
    ))))->get_row_assoc();

    if (is_null($result))
        return false;

    // set HTTP headers for download
    header("Pragma: public");
    header("Expires: -1");
    header("Cache-Control: public, must-revalidate, post-check=0, pre-check=0");
    header("Content-Disposition: attachment; filename=\"$result[file_name]\"");
    header("Content-Type: " . DEFAULT_CONTENT_TYPE);
    header("Content-Length: $result[file_size]");

    echo $result['file_contents'];
    return true;
}
