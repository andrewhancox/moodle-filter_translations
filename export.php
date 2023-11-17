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
 * Export translations landing page
 *
 * @package    filter_translations
 * @copyright  2023 Rajneel Totaram <rajneel.totaram@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use filter_translations\translation;

require(__DIR__ . '/../../config.php');

$courseid = optional_param('id', SITEID, PARAM_INT);
$targetlanguage = optional_param('targetlanguage', current_language(), PARAM_TEXT);

if ($courseid > 1) {
    $course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
}

$context = context_system::instance();

require_login();

require_capability('filter/translations:exporttranslations', $context);

$url = new moodle_url('/filter/translations/export.php', ['id' => $courseid, 'targetlanguage' => $targetlanguage]);
$PAGE->set_url($url);
$PAGE->set_context($context);

$title = get_string('exporttranslations', 'filter_translations');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_pagelayout('standard');

$form = new \filter_translations\form\exporttranslations_form(new moodle_url('/filter/translations/processexport.php'));

$data = new stdClass();
$data->targetlanguage = $targetlanguage;
$data->course = $courseid;
$form->set_data($data);

echo $OUTPUT->header();
echo 'You can export untranslated content for a course to translate offline.';
$form->display();

echo $OUTPUT->footer();
