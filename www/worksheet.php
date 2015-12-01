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

/* worksheet.php - JSON transfer specification
    Supports: GET/POST

    GET -
    *----*
    | id |  // 'id' of assessment_worksheet
    *----*
    we return these fields
        *-----------------------------------------------------*
        | id faculty criterion characteristic activity course |
        | objective instrument course_of_action               |
        *-----------------------------------------------------*

    POST - we only care about the three editable fields on the wkst
    *---------------------------------------*
    | objective instrument course_of_action |
    *---------------------------------------*
*/

function get_wkst($id) {
    // verify access to worksheet
    if (!abet_is_admin_authenticated()
        && !check_assessment_access($_SESSION['id'],$id,'assessment_worksheet'))
    {
        page_fail(UNAUTHORIZED);
    }

    // select required data from db
    $query = new Query(new QueryBuilder(SELECT_QUERY,array(
        'tables' => array(
            'assessment_worksheet' => array(
                'id', 'activity', 'objective', 'instrument', 'course_of_action'
            ),
            'abet_assessment' => 'id',
            'course' => array(
                'title', 'course_number'
            ),
            'abet_characteristic' => array(
                'level', 'program_specifier', 'description'
            ),
            'abet_criterion' => array(
                'rank', 'description'
            )
        ),
        'joins' => array(
            'INNER JOIN abet_assessment ON assessment_worksheet.fk_assessment = abet_assessment.id',
            'LEFT OUTER JOIN course ON assessment_worksheet.fk_course = course.id',
            'LEFT OUTER JOIN abet_characteristic ON abet_assessment.fk_characteristic = abet_characteristic.id',
            'INNER JOIN abet_criterion ON abet_assessment.fk_criterion = abet_criterion.id'
        ),
        'where' => 'assessment_worksheet.id = ?',
        'where-params' => array("i:$id")
    )));
    if ($query->is_empty())
        page_fail(NOT_FOUND);
    $row = $query->get_row_assoc();

    // query the assessment personnel via the acl
    $assess = new ABETAssessment($row['abet_assessment.id']);
    $faculty = $assess->get_acl();

    // build object for client
    $obj = new stdClass;
    $obj->id = $row['assessment_worksheet.id'];
    $obj->faculty = count($faculty) == 0 ? "n/a" : implode(', ',array_map(function($x){return $x->full_name;},$faculty));
    $obj->criterion = "$row[rank] {$row['abet_criterion.description']}";
    if (!is_null($row['level'])) {
        $obj->characteristic = "$row[level] {$row['abet_characteristic.description']}";
        if (!is_null($row['program_specifier']) && $row['program_specifier'] != '')
            $obj->characteristic .= " $row[program_specifier]";
    }
    else
        $obj->characteristic = null;
    if (!is_null($row['title'])) {
        $obj->course = "$row[course_number]: $row[title]";
        $obj->activity = null;
    }
    else {
        $obj->course = null;
        $obj->activity = !is_null($row['activity']) && $row['activity'] != ''
                            ? $row['activity'] : 'not specified';
    }
    $obj->objective = $row['objective'];
    $obj->instrument = $row['instrument'];
    $obj->course_of_action = $row['course_of_action'];
    return json_encode($obj);
}

function update_wkst($id,$objec,$instr,$coa) {
    // verify access to worksheet
    if (!abet_is_admin_authenticated()
        && !check_assessment_access($_SESSION['id'],$id,'assessment_worksheet'))
    {
        page_fail(UNAUTHORIZED);
    }

    // prepare fields
    $us = array();
    if (!is_null($objec))
        $us['objective'] = "s:$objec";
    if (!is_null($instr))
        $us['instrument'] = "s:$instr";
    if (!is_null($coa))
        $fs['course_of_action'] = "s:$coa";

    if (count($us) > 0) {
        // update the three fields of importance
        $query = new Query(new QueryBuilder(UPDATE_QUERY,array(
            'table' => 'assessment_worksheet',
            'updates' => $us,
            'where' => 'id = ?',
            'where-params' => array("i:$id"),
            'limit' => 1
        )));
    }

    echo "{\"success\":true}";
}

header('Content-Type: application/json');

// verify logged in user
if (!abet_is_authenticated())
    page_fail(UNAUTHORIZED);

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!array_key_exists('id',$_GET))
        page_fail(BAD_REQUEST);
    echo get_wkst($_GET['id']);
}
else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!array_key_exists('id',$_POST))
        page_fail(BAD_REQUEST);
    if (!array_key_exists('objective',$_POST))
        $_POST['objective'] = null;
    if (!array_key_exists('instrument',$_POST))
        $_POST['instrument'] = null;
    if (!array_key_exists('course_of_action',$_POST))
        $_POST['course_of_action'] = null;
    echo update_wkst($_POST['id'],$_POST['objective'],$_POST['instrument'],$_POST['course_of_action']);
}
else
    page_fail(BAD_REQUEST);
