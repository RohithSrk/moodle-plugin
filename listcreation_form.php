<?php
// This file is part of mod_checklist for Moodle - http://moodle.org/
//
// It is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// It is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * listcreation_form.php Extends the moodleform class for checklist creation form
 *
 * @package    mod_checklist
 * @copyright  Rohith Singirikonda <rohithsingirikonda@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir.'/formslib.php');

/**
 * checklist_creation_form extends moodleform and defines checklists' creation form
 *
 * @package    mod_checklist
 * @copyright  Rohith Singirikonda <rohithsingirikonda@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checklist_creation_form extends moodleform {

    /**
     * Defines the form
     */
    public function definition() {
        global $PAGE, $DB, $OUTPUT;

        $mform =& $this->_form; // Don't forget the underscore!

        $checklistid = $this->_customdata->checklist;
        $maxitems = $this->_customdata->maxitems;


        $checklist_items = $DB->get_records('checklist_items', array('checklistid' => $checklistid), 'id ASC');

        if( ! empty($checklist_items)){

            foreach ($checklist_items as $i => $checklist_item) {
                $name = "checklistitems[{$checklistid}][{$i}]"; 
                $mform->addElement('textarea', $name, 'Checklist Item ' . $i, array('cols'=>'40', 'rows'=>'2', 'wrap'=>'virtual'));
                $mform->setDefault($name, $checklist_item->name);
            }

        } else {

            for ($i=1; $i <= $maxitems; $i++) { 
                $name = "checklistitems[{$checklistid}][{$i}]"; 
                $mform->addElement('textarea', $name, 'Checklist Item ' . $i, array('cols'=>'40', 'rows'=>'2', 'wrap'=>'virtual'));
            }

        }

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $mform->addElement('hidden', 'edit');
        $mform->setType('edit', PARAM_INT);

        $buttonarray = array();
        $buttonarray[] = &$mform->createElement('submit', 'submitbutton','Save Changes');
        $buttonarray[] = &$mform->createElement('reset', 'resetbutton', 'Revert', 
            array('class' => 'btn btn-secondary'));
        $buttonarray[] = &$mform->createElement('cancel');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
        $mform->closeHeaderBefore('buttonar');

        $this->set_data($this->_customdata);
    }
}
