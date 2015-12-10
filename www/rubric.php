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

/* rubric.php - JSON transfer specification
    Supports: GET/POST

    Fields: GET
    *----*
    | id |  // id is of assessment_worksheet that references rubric
    *----*

    Fields: POST (update)
    *--------------------------------------------------------------------------------------*
    | id // assessment_worksheet                                                           |
    | name threshold threshold_desc // rubric                                              |
    | outstanding_desc expected_desc marginal_desc unacceptable_desc // rubric_description |
    | total_students // rubric_results                                                     |
    | competency:[id,description,outstanding_tally,expected_tally,marginal_tally,          |
    |               unacceptable_tally,pass_fail_type,comment]                             |
    *--------------------------------------------------------------------------------------*

    Fields: POST (create competency result row)
    *--------------*
    | add:"row" id |
    *--------------*

    This script handles getting, updating and expanding rubric user objects.
*/

function get_rubric($id) {
    // verify access to object
    if (!abet_is_admin_authenticated() && !abet_is_observer()
        && !check_assessment_access($_SESSION[id],$id,'assessment_worksheet'))
    {
        page_fail(UNAUTHORIZED);
    }

    // select required fields from rubric and rubric_result
    $rubric = new Query(new QueryBuilder(SELECT_QUERY,array(
        'tables' => array(
            'rubric' => array(
                'name', 'threshold', 'threshold_desc'
            ),
            'rubric_description' => array(
                'outstanding_desc',
                'expected_desc',
                'marginal_desc',
                'unacceptable_desc'
            ),
            'rubric_results' => 'total_students'
        ),
        'joins' => array(
            "INNER JOIN assessment_worksheet ON assessment_worksheet.fk_rubric = rubric.id",
            "INNER JOIN rubric_description ON rubric.fk_description = rubric_description.id",
            "INNER JOIN rubric_results ON assessment_worksheet.fk_rubric_results = rubric_results.id"
        ),
        'where' => 'assessment_worksheet.id = ?',
        'where-params' => array("i:$id")
    )));
    $row = $rubric->get_row_assoc();
    if (is_null($row)) {
        page_fail(NOT_FOUND);
    }

    // select competencies
    $comps = new Query(new QueryBuilder(SELECT_QUERY,array(
        'tables' => array(
            'competency_results' => array(
                'id', 'competency_desc', 'outstanding_tally', 'expected_tally',
                'marginal_tally', 'unacceptable_tally', 'pass_fail_type',
                'comment'
            )
        ),
        'aliases' => array(
            'competency_results.competency_desc' => 'description'
        ),
        'joins' => array(
            "INNER JOIN rubric_results ON rubric_results.id = competency_results.fk_rubric_results",
            "INNER JOIN assessment_worksheet ON assessment_worksheet.fk_rubric_results = rubric_results.id"
        ),
        'where' => 'assessment_worksheet.id = ?',
        'where-params' => array("i:$id")
    )));
    if ($comps->is_empty())
        page_fail(NOT_FOUND);

    // prepare json object
    $cs = array();
    $comps->for_each_assoc(function($row) use(&$cs) {$cs[] = $row;});
    $row['competency'] = $cs;
    return json_encode($row);
}

function grab_rubric_ids($id) {
    $query = new Query(new QueryBuilder(SELECT_QUERY,array(
        'tables' => array(
            'rubric' => 'id',
            'rubric_description' => 'id',
            'rubric_results' => 'id',
        ),
        'joins' => array(
            "INNER JOIN assessment_worksheet ON assessment_worksheet.fk_rubric = rubric.id",
            "INNER JOIN rubric_description ON rubric.fk_description = rubric_description.id",
            "INNER JOIN rubric_results ON assessment_worksheet.fk_rubric_results = rubric_results.id"
        ),
        'where' => 'assessment_worksheet.id = ?',
        'where-params' => array("i:$id")
    )));
    if ($query->is_empty())
        page_fail(NOT_FOUND);

    return $query->get_row_ordered();
}

function update_rubric($obj) {
    global $RUBRIC;
    global $RUBRIC_DESCRIPTION;
    global $RUBRIC_RESULTS;
    global $COMPETENCY;

    if (!array_key_exists('id',$obj))
        page_fail(BAD_REQUEST);
    $id = $obj['id'];

    // verify access to object
    if (!abet_is_admin_authenticated() && !check_assessment_access($_SESSION[id],$id,'assessment_worksheet'))
        page_fail(UNAUTHORIZED);

    list($rId,$rdId,$rrId) = grab_rubric_ids($id);

    // update 'rubric'
    $updates = array();
    if (array_key_exists('name',$obj))
        $updates['name'] = "s:$obj[name]";
    if (array_key_exists('threshold',$obj))
        $updates['threshold'] = "d:$obj[threshold]";
    if (array_key_exists('threshold_desc',$obj))
        $updates['threshold_desc'] = "s:$obj[threshold_desc]";
    generic_update('rubric',$rId,$updates);

    // update 'rubric_description'
    $updates = array();
    if (array_key_exists('outstanding_desc',$obj))
        $updates['outstanding_desc'] = "s:$obj[outstanding_desc]";
    if (array_key_exists('expected_desc',$obj))
        $updates['expected_desc'] = "s:$obj[expected_desc]";
    if (array_key_exists('marginal_desc',$obj))
        $updates['marginal_desc'] = "s:$obj[marginal_desc]";
    if (array_key_exists('unacceptable_desc',$obj))
        $updates['unacceptable_desc'] = "s:$obj[unacceptable_desc]";
    generic_update('rubric_description',$rdId,$updates);

    // update 'rubric_results'
    $updates = array();
    if (array_key_exists('total_students',$obj))
        $updates['total_students'] = "i:$obj[total_students]";
    generic_update('rubric_results',$rrId,$updates);

    // update each competency
    if (array_key_exists('competency',$obj)) {
        foreach ($obj['competency'] as $comp) {
            if (!array_key_exists('id',$comp))
                continue;
            $id = $comp['id'];

            // check access to competency result entity (silently fail if denied)
            if (!abet_is_admin_authenticated() && !check_competency_result_access($_SESSION['id'],$id,$found))
                continue;

            $updates = array();
            if (array_key_exists('description',$comp))
                $updates['competency_desc'] = "s:$comp[description]";
            if (array_key_exists('outstanding_tally',$comp))
                $updates['outstanding_tally'] = "s:$comp[outstanding_tally]";
            if (array_key_exists('expected_tally',$comp))
                $updates['expected_tally'] = "s:$comp[expected_tally]";
            if (array_key_exists('marginal_tally',$comp))
                $updates['marginal_tally'] = "s:$comp[marginal_tally]";
            if (array_key_exists('unacceptable_tally',$comp))
                $updates['unacceptable_tally'] = "s:$comp[unacceptable_tally]";
            if (array_key_exists('pass_fail_type',$comp))
                $updates['pass_fail_type'] = "s:$comp[pass_fail_type]";
            if (array_key_exists('comment',$comp))
                $updates['comment'] = "s:$comp[comment]";
            generic_update('competency_results',$id,$updates);
        }
    }

    return "{\"success\":true}";
}

function delete_competency($id) { // 'id' is competency id
    // check access to entity
    if (!abet_is_admin_authenticated() && !check_competency_result_access($_SESSION['id'],$id,$found)) {
        if (!$found)
            page_fail(NOT_FOUND);
        page_fail(UNAUTHORIZED);
    }

    // delete element
    $query = new Query(new QueryBuilder(DELETE_QUERY,array(
        'tables' => 'competency_results',
        'where' => "competency_results.id = ?",
        'where-params' => array("i:$id"),
        'limit' => 1
    )));

    return "{\"success\":true}";
}

function add_comp_row($id) { // 'id' is worksheet id
    return Query::perform_transaction(function(&$rollback) use($id){
        // select id of rubric_results entity
        $query = new Query(new QueryBuilder(SELECT_QUERY,array(
            'tables' => array('rubric_results' => 'id'),
            'joins' => array(
                'INNER JOIN assessment_worksheet ON assessment_worksheet.fk_rubric_results = rubric_results.id'
            ),
            'where' => 'assessment_worksheet.id = ?',
            'where-params' => array("i:$id")
        )));
        if ($query->is_empty())
            page_fail(NOT_FOUND);
        $rrId = $query->get_row_ordered()[0];

        // insert new competency_results entity
        $insert = new Query(new QueryBuilder(INSERT_QUERY,array(
            'table' => 'competency_results',
            'fields' => array(
                'outstanding_tally', 'expected_tally', 'marginal_tally',
                'unacceptable_tally', 'fk_rubric_results'
            ),
            'values' => array(array("l:0","l:0","l:0","l:0","l:$rrId")),
        )));

        // select the inserted row and return it
        $comp = new Query(new QueryBuilder(SELECT_QUERY,array(
            'tables' => array(
                'competency_results' => array(
                    'id', 'competency_desc', 'outstanding_tally', 'expected_tally',
                    'marginal_tally', 'unacceptable_tally', 'pass_fail_type',
                    'comment'
                )
            ),
            'aliases' => array(
                'competency_results.competency_desc' => 'description'
            ),
            'where' => 'id = LAST_INSERT_ID()',
        )));
        if ($comp->is_empty())
            page_fail(SERVER_ERROR); // shouldn't happen

        return json_encode($comp->get_row_assoc());
    });
}

header('Content-Type: application/json');

// verify logged in user
if (!abet_is_authenticated())
    page_fail(UNAUTHORIZED);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (abet_is_observer()) // observers cannot post data
        page_fail(UNAUTHORIZED);

    if (array_key_exists('add',$_POST) && $_POST['add'] == 'row'
            && array_key_exists('id',$_POST))
    {
        echo add_comp_row($_POST['id']);
    }
    else if (array_key_exists('delete',$_POST)) // delete case
        echo delete_competency($_POST['delete']);
    else // update case
        echo update_rubric($_POST);
}
else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    if (!array_key_exists('id',$_GET))
        page_fail(BAD_REQUEST);
    echo get_rubric($_GET['id']);
}
else
    page_fail(BAD_REQUEST);
