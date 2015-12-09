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
require_once 'abet1-object.php';

/* assessment.php - JSON transfer specification
    Supports: POST/GET

    GET:
    *----*
    | id |
    *----*

    response:
    *-------------------------------------------------------------------*
    | name, characteristic(id), acl:[{id} array], has_content           |
    | worksheets:[{id, name} array],                                    |
    | characteristics:[{id, name} array], profiles:[{id, name} array]   |
    *-------------------------------------------------------------------*

    POST:
     type: add ACL entry
    *------------------------------*
    | type:'acl-add' id profile_id | // 'id' is assessment id
    *------------------------------*

     type: remove ACL entry
    *------------------------------*
    | type:'acl-rm' id profile_id  | // 'id' is assessment id
    *------------------------------*

     type: add general content
    *-------------------*
    | type:'gc-add' id  | // 'id' is assessment id
    *-------------------*

     type: add worksheet/rubric
    *---------------------*
    | type:'wkst-add' id  | // 'id' is assessment id
    *---------------------*
*/

function get_assessment($id) {
    // get general assessment information
    $query = new Query(new QueryBuilder(SELECT_QUERY,array(
        'tables' => array(
            'abet_assessment' => 'name',
            'abet_characteristic' => 'id',
            'general_content' => 'id'
        ),
        'joins' => array(
            'INNER JOIN abet_characteristic ON abet_characteristic.id = abet_assessment.fk_characteristic',
            'LEFT OUTER JOIN general_content ON general_content.fk_assessment = abet_assessment.id'
        ),
        'where' => 'abet_assessment.id = ?',
        'where-params' => array("i:$id"),
        'limit' => 1
    )));
    if ($query->is_empty())
        page_fail(NOT_FOUND);

    // get acl and profile information
    $aclQuery = new Query(new QueryBuilder(SELECT_QUERY,array(
        'tables' => array(
            'userprofile' => array(
                'id', 'first_name', 'last_name'
            ),
            'abet_assessment' => 'id'
        ),
        'joins' => array(
            'LEFT OUTER JOIN acl_entry ON acl_entry.fk_profile = userprofile.id',
            'LEFT OUTER JOIN acl ON acl.id = acl_entry.fk_acl',
            'LEFT OUTER JOIN abet_assessment ON abet_assessment.fk_acl = acl.id '
                . 'AND abet_assessment.id = ' . intval($id)
        ),
        'orderby' => 'userprofile.last_name'
    )));
    if ($aclQuery->is_empty()) // this shouldn't happen
        page_fail(NOT_FOUND);

    // get worksheet information
    $contentQuery = new Query(new QueryBuilder(SELECT_QUERY,array(
        'tables' => array(
            'assessment_worksheet' => array('id','activity'),
            'course' => 'course_number'
        ),
        'joins' => array(
            'INNER JOIN abet_assessment ON abet_assessment.id = assessment_worksheet.fk_assessment',
            'LEFT OUTER JOIN course ON course.id = assessment_worksheet.fk_course'
        ),
        'where' => 'abet_assessment.id = ?',
        'where-params' => array("i:$id")
    )));

    // get characteristics information
    $charsQuery = new Query(new QueryBuilder(SELECT_QUERY,array(
        'tables' => array(
            'abet_characteristic' => array('id','level','program_specifier','short_name')
        ),
        'orderby' => 'CHAR_LENGTH(level), level'
    )));

    // get single entity rows
    $genInfo = $query->get_row_assoc();

    // prepare assessment object
    $obj = new stdClass;
    $obj->name = $genInfo['name'];
    $obj->characteristic = $genInfo['abet_characteristic.id'];
    $obj->has_content = !is_null($genInfo['general_content.id']);
    $obj->acl = array();
    $obj->profiles = array();
    $obj->worksheets = array();
    $obj->characteristics = array();

    // assign profile and acl information
    $m = array();
    $aclQuery->for_each_assoc(function($row) use($obj,&$m) {
        if (array_key_exists($row['userprofile.id'],$m))
            return;
        $m[$row['userprofile.id']] = null;
        if (!is_null($row['abet_assessment.id']))
            $obj->acl[] = $row['userprofile.id'];
        $p = new stdClass;
        $p->id = $row['userprofile.id'];
        $p->name = "$row[first_name] $row[last_name]";
        $obj->profiles[] = $p;
    });

    // assign worksheet info
    $contentQuery->for_each_assoc(function($row) use($obj) {
        $w = new stdClass;
        $w->id = $row['id'];
        if (!is_null($row['activity']))
            $w->name = $row['activity'];
        else
            $w->name = $row['course_number'];
        $obj->worksheets[] = $w;
    });

    // assign characteristics
    $charsQuery->for_each_assoc(function($row) use($obj) {
        $c = new stdClass;
        $c->id = $row['id'];
        $c->name = "$row[level]. $row[short_name]";
        if (!is_null($row['program_specifier']))
            $c->name .= " [$row[program_specifier]]";
        $obj->characteristics[] = $c;
    });

    return json_encode($obj);
}

header('Content-Type: application/json');

// use must be admin to work to edit/create assessments
if (!abet_is_admin_authenticated()) {
    page_fail(UNAUTHORIZED);
}

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!array_key_exists('id',$_GET))
        page_fail(BAD_REQUEST);

    echo get_assessment($_GET['id']);
}
else if ($_SEVER['REQUEST_METHOD'] == 'POST') {

}
else
    page_fail(BAD_REQUEST);
