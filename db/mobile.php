<?php
// This file is part of the Certificate module for Moodle - http://moodle.org/
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
 * Checklist module
 *
 * @package    mod_checklist
 * @copyright  2018 Rohith Singirikonda <rohithsingirikonda@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$addons = array(
    "mod_checklist" => array(
        "handlers" => array( // Different places where the add-on will display content.
            'coursechecklist' => array( // Handler unique name (can be anything)
                'displaydata' => array(
                    'title' => 'checklist',
                    'icon' => $CFG->wwwroot . '/mod/checklist/pix/icon.svg',
                    'class' => '',
                ),
                'delegate' => 'CoreCourseModuleDelegate', // Delegate (where to display the link to the add-on)
                'method' => 'mobile_course_view', // Main function 
                'offlinefunctions' => array(
                ), // Function needs caching for offline.
            )
        ),
        'lang' => array(
            array('pluginname', 'checklist')
        )
    )
);
