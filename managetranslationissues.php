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
 * @package filter_translations
 * @author Andrew Hancox <andrewdchancox@googlemail.com>
 * @author Open Source Learning <enquiries@opensourcelearning.co.uk>
 * @link https://opensourcelearning.co.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2021, Andrew Hancox
 */

use filter_translations\managetranslationissues_filterform;
use filter_translations\managetranslationissues_table;

require_once(dirname(__FILE__) . '/../../config.php');

$rawtext = optional_param('rawtext', '', PARAM_TEXT);
$substitutetext = optional_param('substitutetext', '', PARAM_TEXT);
$issue = optional_param('issue', '', PARAM_INT);
$filterurl = optional_param('url', '', PARAM_URL);
$contextid = optional_param('contextid', 0, PARAM_INT);
$targetlanguage = optional_param('targetlanguage', current_language(), PARAM_TEXT);
$hash = optional_param('hash', '', PARAM_TEXT);

if (empty($contextid)) {
    $context = context_system::instance();
} else {
    list($context, $course, $cm) = get_context_info_array($contextid);
}
require_capability('filter/translations:edittranslations', $context);

$PAGE->set_context($context);

if (isset($cm)) {
    $PAGE->set_cm($cm, $course);
} else if (isset($course)) {
    $PAGE->set_course($course);
}

$title = get_string('managetranslationissues', 'filter_translations');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$form = new managetranslationissues_filterform();
$formdata = $form->get_data();

$baseurl = new moodle_url('/filter/translations/managetranslationissues.php');

if ($formdata) {
    $urlparams = array(
        'rawtext' => $rawtext,
        'substitutetext' => $substitutetext,
        'issue' => $issue,
        'url' => $filterurl,
        'contextid' => $contextid,
        'targetlanguage' => $targetlanguage,
        'hash' => $hash,
    );
    $baseurl->params($urlparams);
    redirect($baseurl);
}

$data = new stdClass();
$data->rawtext = $rawtext;
$data->substitutetext = $substitutetext;
$data->issue = $issue;
$data->url = $filterurl;
$data->contextid = $contextid;
$data->targetlanguage = $targetlanguage;
$data->hash = $hash;
$data->tsort = optional_param('tsort', 'id', PARAM_ALPHA);
$form->set_data($data);

$baseurl->params((array)$data);
$baseurl->param('page', optional_param('page', '', PARAM_INT));
$PAGE->set_url($baseurl);

echo $OUTPUT->header();

$table = new managetranslationissues_table($data, 'translationsname');
$table->define_baseurl($baseurl);
echo $form->render();
$table->out(100, true);

echo $OUTPUT->footer();
