<?php

// ABET object definitions: place functionality here that operates on ABET
// objects that is to be reused throughout the project

require_once('abet1-query.php');

function standard_fail($message) {
    throw new Exception($message);
}

// ABETAssessment - used for creating and editing assessments (not for selecting
// information from them)
class ABETAssessment {

    // failure point for the library
    static private $exitFunction = 'standard_fail';

    // cache id
    private $id;

    static function set_fail(callable $func) {
        self::$exitFunction = $func;
    }
    static private function fail($message) {
        call_user_func(self::$exitFunction,$message);
    }

    // create new assessment object with its own acl
    static function create($name,$programId,$characteristicId,$criterionId) {
        $params = array(
            'name' => $name,
            'programId' => $programId,
            'characteristicId' => is_null($characteristicId) ? "null" : $characteristicId,
            'criterionId' => $criterionId
        );
        return Query::perform_transaction(function(&$rollback) use($params) {
            // create new acl for the assessment
            $insertAcl = new Query(new QueryBuilder(INSERT_QUERY,array(
                'table' => 'acl',
                'fields' => array(), // empty row
                'values' => array()
            )));
            if (!$insertAcl->validate_update()) {
                $rollback = true;
                self::fail("fail insert - 'acl'");
            }

            // create new 'abet_assessment' entity
            $insertAssessment = new Query(new QueryBuilder(INSERT_QUERY,array(
                'table' => 'abet_assessment',
                'fields' => array(
                    'fk_program', 'fk_characteristic', 'fk_criterion', 'fk_acl',
                    'name'
                ),
                'values' => array(
                    array(
                        "i:$params[programId]",
                        "l:$params[characteristicId]", // can be nullable
                        "i:$params[criterionId]",
                        "l:LAST_INSERT_ID()",
                        "s:$params[name]"
                    )
                )
            )));
            if (!$insertAssessment->validate_update()) {
                $rollback = true;
                self::fail("fail insert - 'abet_assessment'");
            }

            // create object with id value
            $li = (new Query(new QueryBuilder(RAW_QUERY,array(
                'query' => 'SELECT LAST_INSERT_ID()'
            ))))->get_row_ordered();
            if (is_null($li)) {
                $rollback = true;
                self::fail("this shouldn't happen");
            }

            return new ABETAssessment($li[0]);
        });
    }

    function __construct($id) {
        $this->id = intval($id);
        if ($this->id == 0)
            self::fail("bad id in constructor");
    }

    // determine if a general content entity exists that references the assessment
    function has_general_content() {
        $query = new Query(new QueryBuilder(SELECT_QUERY,array(
            'tables' => array( 'general_content' => 'id' ),
            'where' => "general_content.fk_assessment = $this->id"
        )));
        return !$query->is_empty();
    }

    // adds a general content entity that references the assessment; we check
    // to make sure there is not another general content entity in place for
    // the assessment (we only allow one)
    function add_general_content() {
        Query::perform_transaction(function(&$rollback) {
            if (!$this->has_general_content()) {
                $insert = new Query(new QueryBuilder(INSERT_QUERY,array(
                    'table' => 'general_content',
                    'fields' => array(
                        'fk_assessment'
                    ),
                    'values' => array(array("i:$this->id"))
                )));
                if (!$insert->validate_update()) {
                    $rollback = true;
                    self::fail("fail insert - 'general_content'");
                }
            }
        });
    }

    // adds a worksheet to the assessment; we can have as many worksheets as we want
    function add_worksheet($activityCourse) {
        Query::perform_transaction(function(&$rollback) use($activityCourse){
            // create rubric description
            $rd = new Query(new QueryBuilder(INSERT_QUERY,array(
                'table' => 'rubric_description',
                'fields' => array(), // empty row
                'values' => array()
            )));
            if (!$rd->validate_update()) {
                $rollback = true;
                self::fail("fail insert - 'rubric_description'");
            }

            // create rubric
            $rw = new Query(new QueryBuilder(INSERT_QUERY,array(
                'table' => 'rubric',
                'fields' => array('name','fk_description','created'),
                'values' => array(
                    array(
                        "l:'New Rubric'",
                        "l:LAST_INSERT_ID()",
                        "l:NOW()"
                    )
                )
            )));
            if (!$rw->validate_update()) {
                $rollback = true;
                self::fail("fail insert - 'rubric'");
            }

            // we must select the id of the last inserted element
            $li = (new Query(new QueryBuilder(RAW_QUERY,array(
                'query' => 'SELECT LAST_INSERT_ID()'
            ))))->get_row_ordered();
            if (is_null($li)) {
                $rollback = true;
                self::fail("this shouldn't happen");
            }
            $rubricId = $li[0];

            // create rubric results
            $rr = new Query(new QueryBuilder(INSERT_QUERY,array(
                'table' => 'rubric_results',
                'fields' => array('total_students'),
                'values' => array(array("l:0"))
            )));
            if (!$rr->validate_update()) {
                $rollback = true;
                self::fail("fail insert - 'rubric_results'");
            }

            // create assessment_worksheet
            $activity = is_string($activityCourse) ? $activityCourse : "";
            $courseId = is_int($activityCourse) ? $activityCourse : null;
            $aw = new Query(new QueryBuilder(INSERT_QUERY,array(
                'table' => 'assessment_worksheet',
                'fields' => array(
                    'fk_assessment', 'activity', 'fk_course', 'fk_rubric', 'fk_rubric_results',
                    'created'
                ),
                'values' => array(
                    array(
                        "l:$this->id",
                        "s:$activity",
                        "l:$courseId",
                        "l:$rubricId",
                        "l:LAST_INSERT_ID()", // rubric_results
                        "l:NOW()"
                    )
                )
            )));
            if (!$aw->validate_update()) {
                $rollback = true;
                self::fail("fail insert - 'assessment_worksheet'");
            }
        });
    }
}
