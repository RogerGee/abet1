<?php

// misc. library functions/declarations go here

define('OKAY',200);
define('BAD_REQUEST',400);
define('UNAUTHORIZED',401);
define('NOT_FOUND',404);
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
// user has access to the assessment that contains the specified entity; the
// entity must be one of 'general_content' OR 'assessment_worksheet'
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
    $query->for_each_ordered(function($row) use(&$found,$entityId) {
        if ($row[0] == $entityId) {
            $found = true;
            return false;
        }
    });

    return $found;
}

function check_general_content_item_access($userId,$entityId,$entityKind,&$found) {
    // select the general_content entity id given either a file_upload or user_comment
    $query = new Query(new QueryBuilder(SELECT_QUERY,array(
        'tables' => array(
            $entityKind => '',
            'general_content' => 'id'
        ),
        'joins' => "INNER JOIN general_content ON general_content.id = $entityKind.fk_content_set",
        'where' => "$entityKind.id = ?",
        'where-params' => array("i:$entityId")
    )));
    if ($query->is_empty()) {
        $found = false;
        return false;
    }
    $found = true;

    // then verify that we have access to the general content element for some assessment
    $gcId = $query->get_row_ordered()[0];
    return check_assessment_access($userId,$gcId,'general_content');
}

function check_competency_result_access($userId,$crId,&$found) {
    // select the first assessment_worksheet which (through several layers of
    // indirection) is referenced by the competency item
    $query = new Query(new QueryBuilder(SELECT_QUERY,array(
        'tables' => array(
            'assessment_worksheet' => 'id'
        ),
        'joins' => array(
            "INNER JOIN rubric_results ON rubric_results.id = assessment_worksheet.fk_rubric_results",
            "INNER JOIN competency_results ON competency_results.fk_rubric_results = rubric_results.id"
        ),
        'where' => "competency_results.id = ?",
        'where-params' => array("i:$crId")
    )));
    if ($query->is_empty()) {
        $found = false;
        return false;
    }
    $found = true;

    // then verify that we have access to the worksheet for some assessment
    $wkstId = $query->get_row_ordered()[0];
    return check_assessment_access($userId,$wkstId,'assessment_worksheet');
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

function generic_update($table,$id,array $updates) {
    if (count($updates) <= 0)
        return;

    $query = new Query(new QueryBuilder(UPDATE_QUERY,array(
        'table' => $table,
        'updates' => $updates,
        'where' => "$table.id = ?",
        'where-params' => array("i:$id"),
        'limit' => 1
    )));
}
