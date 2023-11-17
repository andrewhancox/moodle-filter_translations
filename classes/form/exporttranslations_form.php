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

namespace filter_translations\form;

/**
 * Export translations form
 *
 * @package    filter_translations
 * @copyright  2023 Rajneel Totaram <rajneel.totaram@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

class exporttranslations_form  extends \moodleform {
    public function definition() {
        global $CFG, $DB, $PAGE, $USER;

        $mform =& $this->_form;

        //$mycourses = enrol_get_all_users_courses($USER->id, false, ['id', 'fullname']);

        if (has_capability('moodle/course:view', $PAGE->context)) {
            $mycourses = $DB->get_records_select('course', 'id > 1', null, 'fullname ASC', 'id,fullname');
        } else {
            $mycourses = enrol_get_my_courses(['id', 'fullname'], 'fullname ASC', 0, [], false);
        }

        $courses = [];
        $courses[0] = get_string('selectcourse', 'filter_translations');
        foreach ($mycourses as $course) {
            $courses[$course->id] = format_string($course->fullname);
        }

        $mform->addElement('select', 'course', get_string('course'), $courses);
        // TODO: Add validation. Course has to be selected.

        $languages = get_string_manager()->get_list_of_translations();

        if (!has_capability('filter/translations:editsitedefaulttranslations', $PAGE->context) && isset($languages[$CFG->lang])) {
            unset($languages[$CFG->lang]);
        }

        $mform->addElement('select', 'targetlanguage', get_string('targetlanguage', 'filter_translations'), $languages);

        $this->add_action_buttons(true, get_string('export', 'data'));
    }

}
