<?php


class checklist
{

    public $cm;

    public $course;

    public $checklist;

    public $context;

    public function __construct($cmid, $checklist=null, $cm=null, $course=null) {
        global $COURSE, $DB, $USER;

        if ($cm) {
            $this->cm = $cm;
        } else if (! $this->cm = get_coursemodule_from_id('checklist', $cmid)) {
            print_error('invalidcoursemodule');
        }

        if ($course) {
            $this->course = $course;
        } else if ($this->cm->course == $COURSE->id) {
            $this->course = $COURSE;
        } else if (! $this->course = $DB->get_record('course', array('id' => $this->cm->course))) {
            print_error('invalidid', 'checklist');
        }

        if ($checklist) {
            $this->checklist = $checklist;
        } else if (! $this->checklist = $DB->get_record('checklist', array('id' => $this->cm->instance))) {
            print_error('invalidid', 'checklist');
        }
    }

    /**
    * Standardizes course module, checklist and course data objects and checks for login state!
    *
    * @param int $id course module id or 0 (either $id or $c have to be set!)
    * @param int $c checklist instance id or 0 (either $id or $c have to be set!)
    * @param moodle_url $url current url of the viewed page
    * @return object[] Returns array with coursemodule, checklist and course objects
    */
    public static function init_checks($id, $c, $url) {
        global $PAGE, $DB;

        if ($id) {
            if (!$cm = get_coursemodule_from_id('checklist', $id)) {
                print_error('invalidcoursemodule');
            }
            if (!$checklist = $DB->get_record('checklist', array('id' => $cm->instance))) {
                print_error('invalidid', 'checklist');
            }
            if (!$course = $DB->get_record('course', array('id' => $checklist->course))) {
                print_error('coursemisconf', 'checklist');
            }
            $url->param('id', $id);
        } else {
            if (!$checklist = $DB->get_record('checklist', array('id' => $c))) {
                print_error('invalidcoursemodule');
            }
            if (!$course = $DB->get_record('course', array('id' => $checklist->course))) {
                print_error('coursemisconf', 'checklist');
            }
            if (!$cm = get_coursemodule_from_instance('checklist', $checklist->id, $course->id)) {
                print_error('invalidcoursemodule');
            }
            $url->param('id', $cm->id);
        }

        $PAGE->set_url($url);
        require_login($course->id, false, $cm);

        return array($cm, $checklist, $course);
    }

    /**
    * Every view for checklist (teacher/student/etc.)
    */
    public function view() {
        global $OUTPUT, $USER, $PAGE, $DB;

        $saved = optional_param('saved', 0, PARAM_BOOL);
        $edit  = optional_param('edit', 0, PARAM_BOOL);
        $this->context = context_module::instance($this->cm->id);

        if( is_enrolled($this->context, $USER, 'mod/checklist:submit') ) {
            $this->studentview();
            return;
        }

        if( has_capability('mod/checklist:addinstance', $this->context) ) {

            if ($saved) {
                $this->view_header();
                echo $OUTPUT->box_start('generalbox', 'notification');
                echo $OUTPUT->notification(get_string('submissionsaved', 'checklist'), 'notifywarning');
                echo $OUTPUT->box_end();
                $this->view_footer();
                return;
            }

            if( ! $edit ){
                $this->view_header();
                $formaction = new moodle_url('/mod/checklist/view.php', array('id' => $this->cm->id));
                $mform = new MoodleQuickForm('createchecklist', 'post', $formaction);
                $mform->addElement('hidden', 'edit');
                $mform->setType('edit', PARAM_INT);
                $mform->setDefault('edit', 1);                    

                $buttonarray = array();

                if( $this->isnewchecklist() ){
                    $buttonarray[] = &$mform->createElement('submit', 'submitbutton', 'Create Checklist');
                } else {
                    echo $OUTPUT->heading('Checklist Summary');
                    $mform->addElement('html', $this->getsummaryhtml());
                    $buttonarray[] = &$mform->createElement('submit', 'submitbutton', 'Edit Checklist');
                }

                $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
                $mform->closeHeaderBefore('buttonar');

                echo $OUTPUT->box_start('generalbox boxaligncenter', 'checklistform');
                $mform->display();
                echo $OUTPUT->box_end();

            } else {
                $data = new stdClass();
                $data->id           = $this->cm->id;
                $data->checklist    = $this->checklist->id;
                $data->edit         = $edit;
                $data->maxitems     = $this->checklist->maxitems;

                $mform = new checklist_creation_form(null, $data);

                if ($formdata = $mform->get_data()) {

                    $checklistitems = $formdata->checklistitems[ $this->checklist->id ];

                    if ( ! $this->isnewchecklist() ){
                        // delete existing checklist items and data of this->checklist->id
                        // TODO: delete checklist data
                        $DB->delete_records('checklist_items', array('checklistid' => $this->checklist->id));
                    }

                    foreach ($checklistitems as $itemid => $itemvalue) {
                        $DB->insert_record('checklist_items', (object)[ 'checklistid' => $this->checklist->id, 
                            'name' => $itemvalue ]);
                    }                    

                    redirect(new moodle_url($PAGE->url, array('id' => $this->cm->id, 'saved' => 1)));
                } else {

                    if ($mform->is_cancelled()) {
                        redirect(new moodle_url($PAGE->url, array('id' => $this->cm->id)));
                    }

                    $this->view_header(get_string('editchecklist', 'checklist'));
                    echo $OUTPUT->box_start('generalbox boxaligncenter', 'checklistform');

                    if ( ! $this->isnewchecklist() ){
                        echo $OUTPUT->notification(get_string('editwarn', 'checklist'), 'notifywarning');
                    }

                    $mform->display();
                    echo $OUTPUT->box_end();
                }
            }

        }

        $this->view_footer();
    }

    public function studentview(){
        global $OUTPUT, $USER, $PAGE, $DB;

        $edit  = optional_param('edit', 0, PARAM_BOOL);
        $saved = optional_param('saved', 0, PARAM_BOOL);

        $data = new stdClass();
        $data->id         = $this->cm->id;
        $data->checklist  = $this->checklist->id;
        $data->edit       = 1;

        $checklist_items = $DB->get_records('checklist_data', array('checklistid' => $this->checklist->id, 
            'userid' => $USER->id, 'checked' => 1 ), 'id ASC');

        $checked = array();

        foreach ($checklist_items as $id => $checklist_item) {
            $checked[ $checklist_item->checklistitemid ] = 1;
        }

        $data->checklistitems = array( $this->checklist->id => $checked );

        $mform = new checklist_submission_form(null, $data);

        if( $edit ){

            if ($mform->is_cancelled()) {
                redirect(new moodle_url($PAGE->url, array('id' => $this->cm->id)));
            }

            if ($formdata = $mform->get_data()) {

                $checked_cl_items = $formdata->checklistitems[ $this->checklist->id ];

                $dberror = false;

                $checklist_items = $DB->get_records('checklist_items', array('checklistid' => $this->checklist->id), 'id ASC');

                foreach ($checklist_items as $id => $checklist_item) {
                    if(! array_key_exists($id, $checked_cl_items)){
                        $checked_cl_items[ $id ] = false;
                    }
                }

                foreach ($checked_cl_items as $itemid => $itemvalue) {

                    $clitem = $DB->get_record('checklist_data', array('checklistid' => $this->checklist->id, 'checklistitemid' => $itemid, 'userid' => $USER->id ) );

                    if( ! empty($clitem) ) {
                        $dataobject = (object)[ 'checklistid' => $this->checklist->id, 
                        'checklistitemid' => $itemid,
                        'userid' => $USER->id, 
                        'id' => $clitem->id, 
                        'checked'=> $itemvalue ];
                        if( ! $DB->update_record('checklist_data', $dataobject) ) {
                            $dberror = true;
                        }
                    } else {
                        if( ! $DB->insert_record('checklist_data', (object)[ 'checklistid' => $this->checklist->id, 
                            'checklistitemid' => $itemid,
                            'userid' => $USER->id, 
                            'checked'=> $itemvalue ]) ) {
                            $dberror = true;
                    }
                }

            }

            if( ! $dberror ){
                redirect(new moodle_url($PAGE->url, array('id' => $this->cm->id, 'saved' => 1)));
            } else {
                redirect(new moodle_url($PAGE->url, array('id' => $this->cm->id, 'error' => 1)));
            }

        }

    } else {
        $this->view_header(get_string('editchecklist', 'checklist'));
    }

    if ($saved) {
        echo $OUTPUT->box_start('generalbox', 'notification');
        echo $OUTPUT->notification(get_string('submissionsaved', 'checklist'), 'notifysuccess');
        echo $OUTPUT->box_end();
    } else {
        $mform->display();
    }

    $this->view_footer();
    }

    /**
    * Display the header and top of a page
    *
    * This is used by the view() method to print the header of view.php but
    * it can be used on other pages in which case the string to denote the
    * page in the navigation trail should be passed as an argument
    *
    * @param string $subpage Description of subpage to be used in navigation trail
    */
    public function view_header($subpage='') {
        global $CFG, $PAGE, $OUTPUT;

        if ($subpage) {
            $PAGE->navbar->add($subpage);
        }

        $pagetitle = strip_tags($this->course->shortname.': '.get_string('modulename', 'checklist').
            ': '.format_string($this->checklist->name, true));
        $PAGE->set_title($pagetitle);
        $PAGE->set_heading($this->course->fullname);

        echo $OUTPUT->header();
    }

    /**
    * Display the bottom and footer of a page
    *
    * This default method just prints the footer.
    */
    public function view_footer() {
        global $OUTPUT;
        echo $OUTPUT->footer();
    }


    public function isnewchecklist(){
        global $DB;
        $count = $DB->count_records('checklist_items', array('checklistid' => $this->checklist->id) );

        return ($count == 0)? true : false;
    }


    public function getsummary(){
        global $DB;
        $checklist_items = $DB->get_records('checklist_items', array('checklistid' => $this->checklist->id), 'id ASC');
        $submissioncandidates = get_enrolled_users($this->context, 'mod/assignment:submit');

        $summary = array();

        foreach ($checklist_items as $id => $checklist_item) {
            $checkedcount = $DB->count_records('checklist_data', array('checklistitemid' => $id) );
            $summary[] = array( 'name' => $checklist_item->name, 'enrolled' => count($submissioncandidates), 
                'checked' => $checkedcount );;
        }

        return $summary;
    }

    public function getsummaryhtml(){

        $summary = $this->getsummary();

        ob_start(); ?>
        <table class="flexible generaltable generalbox">
            <tr><th>Checklist Item</th><th>Completed Students</th></tr>
            <?php foreach( $summary as $summary_item ): ?>
                <tr>
                    <td><?php echo $summary_item['name']; ?></td>
                    <td><?php echo $summary_item['checked']. "/" . $summary_item['enrolled']; ?></td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php return ob_get_clean();
    }

}