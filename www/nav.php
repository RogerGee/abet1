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

/* nav.php - JSON transfer specification
    Supports: GET

    Fields: (GET)
     - if the node has children, it will look like this: [division node]
    *----------------*
    | label children |
    *----------------*
     - otherwise it will look like this: [terminal node]
    *---------------*
    | label type id |
    *---------------*
    - the total output will be a hierarchy of these data structures defined
    recursively

    This script creates navigation objects, which are recursively defined
    structures that represent the navigation items on the page. Each user can
    navigate only to user objects to which they have access. Access is controlled
    with the 'acl' and 'acl_entry' tables. We use the database ids to identify
    each user object. The 'type' field corresponds to a Javascript function on
    the client end.

    On success, a response will return the JSON object with the previously defined
    structure. On failure it will return {"success":false} with some other HTTP
    status code. We don't provide specific error feedback in this script.

    The JSON object will be an array. Each element corresponds to a navigation
    tree we want the user to have access to.
*/

function make_program_node($row,$parent) {
    global $isAdmin;

    $program = new stdClass;
    $program->label = "$row[name] - $row[semester] $row[year]";
    $program->children = array();

    // add admin tools for programs
    if ($isAdmin) {
        $editProgram = new stdClass;
        $editProgram->label = 'Edit Program';
        $editProgram->type = 'editProgram';
        $editProgram->id = $row['program.id'];
        $program->children[] = $editProgram;
    }

    $parent->children[] = $program;
    return $program;
}

function make_criterion_node($row,$program,$programId) {
    global $isAdmin;

    // make sure we haven't added the criteria before
    $label = "$row[rank]. $row[description]";
    foreach ($program->children as $crit) {
        if ($crit->label === $label)
            return $crit;
    }

    $criterion = new stdClass;
    $criterion->label = $label;
    $criterion->children = array();

    // add admin tools for criteria
    if ($isAdmin) {
        $createAssessment = new stdClass;
        $createAssessment->label = "Create Assessment";
        $createAssessment->type = 'createAssessment';
        $createAssessment->id = $programId;
        $criterion->children[] = $createAssessment;
    }

    $program->children[] = $criterion;
    return $criterion;
}

function make_characteristic_node($row,$criterion) {
    global $isAdmin;

    $characteristic = new stdClass;
    $characteristic->label = "$row[level]. $row[short_name]";
    if (!is_null($row['program_specifier']) && $row['program_specifier'] !== '')
         $characteristic->label .= "[$row[program_specifier]]";
    $characteristic->children = array();

    // add admin tools for characteristics
    // if ($isAdmin) {
    //     $editCharacteristic = new stdClass;
    //     $editCharacteristic->label = 'Edit Characteristic';
    //     $editCharacteristic->type = 'editCharacteristic';
    //     $editCharacteristic->id = $row['abet_characteristic.id'];
    //     $characteristic->children[] = $editCharacteristic;
    // }

    $criterion->children[] = $characteristic;
    return $characteristic;
}

function make_assessment_node($row,$parent) {
    global $isAdmin;

    $assessment = new stdClass;
    $assessment->label = 'Assessment';
    $assessment->children = array();

    // add admin tools for assessments
    if ($isAdmin) {
        $editAssessment = new stdClass;
        $editAssessment->label = 'Edit Assessment';
        $editAssessment->type = 'editAssessment';
        $editAssessment->id = $row['abet_assessment.id'];
        $assessment->children[] = $editAssessment;
    }

    $parent->children[] = $assessment;
    return $assessment;
}

header('Content-Type: application/json');

if (!abet_is_authenticated())
    page_fail(UNAUTHORIZED);

if ($_SERVER['REQUEST_METHOD'] != 'GET')
    page_fail(BAD_REQUEST);

// output is array of navigation trees
$navTrees = array();
$isAdmin = abet_is_admin_authenticated();

// design query to select all navigation for current user
$qbInfo = array(
    'tables' => array(
        'abet_assessment'=>'id',
        'program'=>array('id','name','semester','year'),
        'abet_criterion'=>array('id','rank','description'),
        'abet_characteristic'=>array('id','level','program_specifier','short_name'),
        'assessment_worksheet'=>array('id','activity'),
        'general_content'=>'id',
        'rubric'=>'id',
        'course'=>'course_number',
    ),
    'joins' => array(
        // join on all the content so we can fetch their ids
        "INNER JOIN program ON abet_assessment.fk_program = program.id",
        ($isAdmin ? "RIGHT OUTER" : "INNER") . " JOIN abet_criterion ON abet_assessment.fk_criterion = abet_criterion.id",
        "LEFT OUTER JOIN abet_characteristic ON abet_assessment.fk_characteristic = abet_characteristic.id",
        "LEFT OUTER JOIN assessment_worksheet ON abet_assessment.id = assessment_worksheet.fk_assessment",
        "LEFT OUTER JOIN general_content ON abet_assessment.id = general_content.fk_assessment",
        "LEFT OUTER JOIN rubric ON assessment_worksheet.fk_rubric = rubric.id",
        "LEFT OUTER JOIN course ON assessment_worksheet.fk_course = course.id"
    ),
    'orderby' => "program.year, program.semester, program.name, abet_criterion.rank, abet_characteristic.level, course.course_number"
);

// is the user is not an admin, restrict their access according to the ACLs
if (!$isAdmin) {
    // join on the acl tables to restrict access
    $qbInfo['joins'][] = "INNER JOIN acl ON abet_assessment.fk_acl = acl.id";
    $qbInfo['joins'][] = "INNER JOIN acl_entry ON acl_entry.fk_acl = acl.id AND acl_entry.fk_profile = '$_SESSION[id]'";
}

// grab all assessments that the user can access, along with their keys
$query = new Query(new QueryBuilder(SELECT_QUERY,$qbInfo));

// structure the navigation tree around the heirarchy of assessments to which the
// user has access; we present the same navigation structure to all kinds of users
$userTools = new stdClass;
$userTools->label = 'Content';
$userTools->children = array();

// mappings to remember content organizers as we go through results
$mappings = array();
$criteria = array();

// build main navigation subtree ('userTools')
for ($i = 1;$i <= $query->get_number_of_rows();$i++) {
    /* grab a result row: each row represents a potential content item; some
       fields may be null (from the outer joins); we build the navigation top-down:

        program -> criterion -> characteristic -> assessment -> content

        Since we bracket content under shared organizers we keep a mapping for
        each assessment within characteristic within criterion within program.
     */
    $row = $query->get_row_assoc($i);

    // get ids
    $a = $row['program.id'];
    $b = $row['abet_criterion.id'];
    $c = $row['abet_characteristic.id'];
    $d = $row['abet_assessment.id'];

    $idProgram = is_null($a) ? null : "$a";
    $idCriterion = is_null($b) ? null : "$a:$b";
    $idCharacteristic = is_null($c) ? null : "$a:$b:$c";
    $idAssessment = is_null($d) ? null : "$a:$b:$c:$d";

    // generate or retrieve program
    if (!is_null($idProgram)) {
        if (array_key_exists($idProgram,$mappings)) {
            $program = $mappings[$idProgram];
        }
        else {
            $program = make_program_node($row,$userTools);
            $mappings[$idProgram] = $program;
        }
    }

    // generate or retrieve criterion
    if (!is_null($idCriterion)) {
        if (isset($program)) {
            if (array_key_exists($idCriterion,$mappings)) {
                $criterion = $mappings[$idCriterion];
            }
            else {
                $criterion = make_criterion_node($row,$program,$row['program.id']);
                $mappings[$idCriterion] = $criterion;
            }
        }

        // remember all criterion descriptions uniquely
        if (!array_key_exists($row['abet_criterion.id'],$criteria))
            $criteria[$row['abet_criterion.id']] = $row;
    }

    // generate or retrieve characteristic
    if (!is_null($idCharacteristic)) {
        if (array_key_exists($idCharacteristic,$mappings)) {
            $characteristic = $mappings[$idCharacteristic];
        }
        else {
            $characteristic = make_characteristic_node($row,$criterion);
            $mappings[$idCharacteristic] = $characteristic;
        }
    }

    // generate or retrieve assessment
    if (!is_null($idAssessment)) {
        if (array_key_exists($idAssessment,$mappings)) {
            $assessment = $mappings[$idAssessment];
        }
        else {
            // note: characteristics are optional in which case we organize under criterion
            $assessment = make_assessment_node($row,isset($characteristic) ? $characteristic : $criterion);
            $mappings[$idAssessment] = $assessment;
        }
    }

    // generate content (if any)
    if (!is_null($row['assessment_worksheet.id'])) {
        // worksheet content item
        $content = new stdClass;
        $content->label = 'Worksheet';
        $content->type = 'getWorksheet';
        $content->id = $row['assessment_worksheet.id'];

        if (!is_null($row['course_number']) || !is_null($row['activity'])) {
            // create division for worksheet content; this will include the
            // worksheet and rubric
            $division = new stdClass;
            $division->label = is_null($row['course_number']) ? $row['activity']
                : $row['course_number'];
            $division->children = array();
            $assessment->children[] = $division;
        }
        else
            // this shouldn't happen, but in case it does just add the worksheet/rubric
            // pair to the assessment division
            $division = $assessment;

        // add content to assessment; there should always be a valid
        // assessment if there is content
        $division->children[] = $content;

        // (note: there should always be a rubric accompanying a worksheet)
        if (!is_null($row['rubric.id'])) {
            // create rubric node: we must use the assessment worksheet id so
            // that we can refer to the rubric AND its rubric_results counterpart
            $rubric = new stdClass;
            $rubric->label = 'Rubric';
            $rubric->type = 'getRubric';
            $rubric->id = $row['assessment_worksheet.id'];

            $division->children[] = $rubric;
        }
    }

    if (!is_null($row['general_content.id'])) {
        // general-content item
        $content = new stdClass;
        $content->label = 'Content';
        $content->type = 'getContent';
        $content->id = $row['general_content.id'];

        // add content to assessment; there should always be a valid
        // assessment if there is content
        $assessment->children[] = $content;
    }
}

$navTrees[] = $userTools;

// if the user is an admin, add admin commands; these commands exist in a second
// command subtree and are used for creating top-level user objects
if ($isAdmin) {
    // go through all programs and add any criteria that don't exist
    foreach ($userTools->children as $program) {
        foreach ($criteria as $crit) {
            // make criteria node for admin to use for creating assessments; getting
            // the id of the program is a hack but it works; make_criterion_node will
            // ensure the criteria doesn't already exist
            make_criterion_node($crit,$program,$program->children[0]->id);
        }

        // sort navigation items
        usort($program->children,function($a,$b){return strcmp($a->label,$b->label);});
    }

    $createProgram = new stdClass;
    $createProgram->label = 'Create New Program';
    $createProgram->type = 'createProgram';
    $createProgram->id = null;
    $userTools->children[] = $createProgram;

    $createUser = new stdClass;
    $createUser->label = "Create New User";
    $createUser->type = 'loadUserCreate';
    $createUser->id = null;

    $editUser = new stdClass;
    $editUser->label = "Remove User";
    $editUser->type = "removeUser";
    $editUser->id = null;

    $editCharacteristics = new stdClass;
    $editCharacteristics->label = "Characteristics...";
    $editCharacteristics->type = "editCharacteristics";
    $editCharacteristics->id = null;

    $editCourse = new stdClass;
    $editCourse->label = "Courses...";
    $editCourse->type = "editCourses";
    $editCourse->id = null;

    $adminTools = new stdClass;
    $adminTools->label = "Admin Tools";
    $adminTools->children = array(
        $createUser, $editUser, $editCharacteristics, $editCourse
    );

    $navTrees[] = $adminTools;
}

echo json_encode($navTrees);
