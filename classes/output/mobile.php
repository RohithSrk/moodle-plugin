<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Mobile output class for certificate
 *
 * @package    mod_checklist
 * @copyright  2018 Rohith Singirikonda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_checklist\output;

require_once($CFG->dirroot . '/mod/checklist/locallib.php');

defined('MOODLE_INTERNAL') || die();

use context_module;

/**
 * Mobile output class for checklist
 *
 * @package    mod_checklist
 * @copyright  2018 Rohith Singirikonda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mobile {

    /**
     * Returns the checklist course view for the mobile app.
     * @param  array $args Arguments from tool_mobile_get_content WS
     *
     * @return array       HTML, javascript and otherdata
     */
    public static function mobile_course_view($args) {
        global $OUTPUT, $USER, $DB;

        $args = (object) $args;
        $cm = get_coursemodule_from_id('checklist', $args->cmid);

        // Capabilities check.
        require_login($args->courseid , false , $cm, true, true);

        $context = context_module::instance($cm->id);
        $checklistid = $cm->instance;

        if( is_enrolled($context, $USER, 'mod/checklist:submit')  ){

            $checklist_items = $DB->get_records('checklist_items', array('checklistid' => $checklistid), 'id ASC');

            if(empty($checklist_items)){
                // TODO: display error msg
            }

            $vars = array();

            foreach ($checklist_items as $i => $checklist_item) {
                $vname = "cl_{$checklist_item->checklistid}_{$i}"; 

                $vars[] = array( 'vname' => $vname, 'name' => $checklist_item->name );
            }

            $data = array(
                'cmid' => $cm->id,
                'courseid' => $args->courseid,
                'vars' => $vars
            ); 

            $checklist_data = $DB->get_records('checklist_data', array('checklistid' => $checklistid, 
                'userid' => $USER->id, 'checked' => 1 ), 'id ASC');

            $checked = array();

            foreach ($checklist_data as $id => $checklist_data_item ) {
                $checked[ "cl_{$checklist_data_item->checklistid}_{$checklist_data_item->checklistitemid}" ] = true;
            }

            return array(
                'templates' => array(
                    array(
                        'id' => 'main',
                        'html' => $OUTPUT->render_from_template('mod_checklist/mobile_view_page', $data),
                    ),
                ),
                'javascript' => '',
                'otherdata' => $checked,
                'files' => array()
            );

        } else if( has_capability('mod/checklist:addinstance', $context) ) {

            $summary = self::getsummary( $cm->instance, $context );

            $data = array(
                'cmid' => $cm->id,
                'courseid' => $args->courseid,
                'summary' => $summary
            );

            return array(
                'templates' => array(
                    array(
                        'id' => 'main',
                        'html' => $OUTPUT->render_from_template('mod_checklist/mobile_view_summary', $data),
                    ),
                ),
                'javascript' => '',
                'otherdata' => array(),
                'files' => array()
            );

        } 

    }

    public static function mobile_checklist_submit($args=[]){
        global $DB, $USER, $OUTPUT;

        $dberror = false;
        $matches = [];
        foreach ( $args as $argk => $argv ) {
            if(preg_match("/^cl_(\d+)_(\d+)$/", $argk, $matches)){
                if( $argv == 'true' ){

                    $clitem = $DB->get_record('checklist_data', array('checklistid' => $matches[1], 'checklistitemid' => $matches[2], 'userid' => $USER->id ) );


                    if( ! empty($clitem) ) {
                        $dataobject = (object)[ 'checklistid' => $matches[1], 
                                                            'checklistitemid' => $matches[2],
                                                            'userid' => $USER->id, 
                                                            'id' => $clitem->id, 
                                                            'checked'=> true ];
                       if( ! $DB->update_record('checklist_data', $dataobject) ) {
                            $dberror = true;
                       }
                    } else {
                        if( ! $DB->insert_record('checklist_data', (object)[ 'checklistid' => $matches[1], 
                                                            'checklistitemid' => $matches[2],
                                                            'userid' => $USER->id, 
                                                            'checked'=> true ]) ) {
                            $dberror = true;
                        }
                    }

                } else if ( $argv == 'false' ) {
                    if(! $DB->delete_records('checklist_data', array('checklistid' => $matches[1], 'checklistitemid' => $matches[2], 'userid' => $USER->id ) )) {
                        $dberror = true;
                    }
                }
            }
        }

        $data = array(
            'cmid' => $cm->id,
            'courseid' => $args->courseid,
            'dberror' => $dberror
        );

        return array(
            'templates' => array(
                array(
                    'id' => 'main',
                    'html' => $OUTPUT->render_from_template('mod_checklist/mobile_view_success', $data),
                ),
            ),
            'javascript' => '',
            'otherdata' => ''
        );
    }

    public static function getsummary($checklistid, $context){
        global $DB;
        $checklist_items = $DB->get_records('checklist_items', array('checklistid' => $checklistid), 'id ASC');
        $submissioncandidates = get_enrolled_users($context, 'mod/assignment:submit');

        $summary = array();

        foreach ($checklist_items as $id => $checklist_item) {
            $checkedcount = $DB->count_records('checklist_data', array('checklistitemid' => $id) );
            $summary[] = array( 'name' => $checklist_item->name, 'enrolled' => count($submissioncandidates), 
                'checked' => $checkedcount );;
        }

        return $summary;
    }
}
