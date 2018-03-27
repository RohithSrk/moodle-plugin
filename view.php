<?php
require_once("../../config.php");
require_once($CFG->dirroot . '/mod/checklist/locallib.php');
require_once($CFG->dirroot . '/mod/checklist/listcreation_form.php');
require_once($CFG->dirroot . '/mod/checklist/listsubmission_form.php');


$id = optional_param('id', 0, PARAM_INT);
$c  = optional_param('c', 0, PARAM_INT);

$url = new moodle_url('/mod/checklist/view.php');
list($cm, $checklist, $course) = \checklist::init_checks($id, $c, $url);

$checklistinstance = new checklist($cm->id, $checklist, $cm, $course);

$checklistinstance->view();
