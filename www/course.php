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

/* course.php - JSON transfer specification
    Supports: GET/POST

    GET:
    n/a

    response:
    *----------------------------------------------------------------------*
    | courses:[{id,title,course_number,coordinator,instructor,description, |
    |  textbook,credit_hours}]                                             |
    | profiles:[{id,name}]                                                 |
    *----------------------------------------------------------------------*

    POST: edit existing course (given id)
    *--------------------------------------------------------------------*
    | id title course_number coordinator instructor description textbook |
    | credit_hours                                                       |
    *--------------------------------------------------------------------*

    POST: create new course (not given id)
    *--------------------------------------------------------------------*
    | title course_number coordinator instructor description textbook    |
    | credit_hours                                                       |
    *--------------------------------------------------------------------*
*/

function get_profiles() {
    $query = new Query(new QueryBuilder(SELECT_QUERY,array(
        'tables' => array(
            'userprofile' => array('id','first_name','last_name')
        ),
        'joins' => array(
            'INNER JOIN userauth ON userauth.id = userprofile.fk_userauth'
        ),
        'where' => 'userauth.role = \'faculty\' OR userauth.role = \'admin\'',
        'orderby' => 'userprofile.last_name'
    )));

    $profiles = array();
    $query->for_each_ordered(function($row) use(&$profiles){
        $profiles[] = array(
            'id' => $row[0],
            'name' => "$row[1] $row[2]"
        );
    });
    return $profiles;
}

function get_courses() {
    $query = new Query(new QueryBuilder(SELECT_QUERY,array(
        'tables' => array(
            'course' => array(
                'id', 'title', 'course_number', 'fk_coordinator', 'instructor',
                'description', 'textbook', 'credit_hours'
            )
        ),
        'orderby' => 'title',
    )));

    return $query->get_rows_assoc();
}

function update_course($id,$title,$courseNumber,$coordinator,
    $instructor,$description,$textbook,$creditHours)
{
    $updates = array();
    if (!is_null($title)) {
        if ($title == "")
            page_fail_on_field(BAD_REQUEST,'title','must specify title');
        $updates['title'] = "s:$title";
    }
    if (!is_null($courseNumber)) {
        if ($courseNumber == "")
            page_fail_on_field(BAD_REQUEST,'course_number','must specify course number');
        $updates['course_number'] = "s:$courseNumber";
    }
    if (!is_null($coordinator)) {
        if ($coordinator == "")
            page_fail_on_field(BAD_REQUEST,'coordinator','must specify coordinator');
        $updates['fk_coordinator'] = "i:$coordinator";
    }
    if (!is_null($instructor)) {
        if ($instructor == "")
            page_fail_on_field(BAD_REQUEST,'instructor','must specify instructor');
        $updates['instructor'] = "s:$instructor";
    }
    if (!is_null($description)) {
        if ($description == "")
            page_fail_on_field(BAD_REQUEST,'description','must specify description');
        $updates['description'] = "s:$description";
    }
    if (!is_null($textbook)) {
        if ($textbook == "")
            page_fail_on_field(BAD_REQUEST,'textbook','must specify textbook');
        $updates['textbook'] = "s:$textbook";
    }
    if (!is_null($creditHours)) {
        if ($creditHours == "")
            page_fail_on_field(BAD_REQUEST,'creditHours','must specify credit hours');
        $updates['credit_hours'] = "s:$creditHours";
    }

    generic_update('course',$id,$updates);
    echo "{\"success\":true}";
}

function create_course($title,$courseNumber,$coordinator,$instructor,
    $description,$textbook,$creditHours)
{
    if (is_null($title) || $title == "")
        page_fail_on_field(BAD_REQUEST,'title','must be non-empty');
    if (is_null($courseNumber) || $courseNumber == "")
        page_fail_on_field(BAD_REQUEST,'course_number','must be non-empty');
    if (is_null($coordinator))
        page_fail_on_field(BAD_REQUEST,'coordinator','must be non-empty');
    if (is_null($instructor) || $instructor == "")
        page_fail_on_field(BAD_REQUEST,'instructor','must be non-empty');
    if (is_null($description) || $description == "")
        page_fail_on_field(BAD_REQUEST,'description','must be non-empty');
    if (is_null($textbook) || $textbook == "")
        page_fail_on_field(BAD_REQUEST,'textbook','must be non-empty');
    if (is_null($creditHours) || $creditHours == "")
        page_fail_on_field(BAD_REQUEST,'credit_hours','must be non-empty');

    $info = array(
        'table' => 'course',
        'fields' => array('title','course_number','fk_coordinator','instructor',
            'description','textbook','credit_hours'),
        'values' => array(array("s:$title","s:$courseNumber","i:$coordinator",
            "s:$instructor","s:$description","s:$textbook","s:$creditHours"))
    );

    list($code,$json) = Query::perform_transaction(function(&$rollback) use($info){
        $insert = new Query(new QueryBuilder(INSERT_QUERY,$info));
        if (!$insert->validate_update()) {
            $rollback = true;
            return array(SERVER_ERROR,"{\"success\":false}");
        }

        $query = new Query(new QueryBuilder(SELECT_QUERY,array(
            'tables' => array(
                'course' => array(
                    'id', 'title', 'fk_coordinator', 'instructor', 'description',
                    'textbook', 'credit_hours'
                )
            ),
            'where' => 'course.id = LAST_INSERT_ID()'
        )));
        if ($query->is_empty()) {
            $rollback = true;
            return array(SERVER_ERROR,"{\"success\":false}");
        }

        return array(OKAY,json_encode($query->get_row_assoc()));
    });

    http_response_code($code);
    return $json;
}

header('Content-Type: application/json');

if (!abet_is_admin_authenticated())
    page_fail(UNAUTHORIZED);

if ($_SERVER['REQUEST_METHOD'] == 'GET') {
    $obj = new stdClass;
    $obj->courses = get_courses();
    $obj->profiles = get_profiles();
    echo json_encode($obj);
}
else if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!array_key_exists('id',$_POST)) {
        // create new course
        echo create_course(
            isset($_POST['title']) ? $_POST['title'] : null,
            isset($_POST['course_number']) ? $_POST['course_number'] : null,
            isset($_POST['coordinator']) ? $_POST['coordinator'] : null,
            isset($_POST['instructor']) ? $_POST['instructor'] : null,
            isset($_POST['description']) ? $_POST['description'] : null,
            isset($_POST['textbook']) ? $_POST['textbook'] : null,
            isset($_POST['credit_hours']) ? $_POST['credit_hours'] : null
        );
    }
    else {
        // edit existing course
        echo update_course(
            $_POST['id'],
            isset($_POST['title']) ? $_POST['title'] : null,
            isset($_POST['course_number']) ? $_POST['course_number'] : null,
            isset($_POST['coordinator']) ? $_POST['coordinator'] : null,
            isset($_POST['instructor']) ? $_POST['instructor'] : null,
            isset($_POST['description']) ? $_POST['description'] : null,
            isset($_POST['textbook']) ? $_POST['textbook'] : null,
            isset($_POST['credit_hours']) ? $_POST['credit_hours'] : null
        );
    }
}
else
    page_fail(BAD_REQUEST);
