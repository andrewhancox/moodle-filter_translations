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

$issue = optional_param('issue', '', PARAM_INT);
$filterurl = optional_param('url', '', PARAM_URL);
$contextid = optional_param('contextid', 0, PARAM_INT);
$targetlanguage = optional_param('targetlanguage', '', PARAM_TEXT);

if (empty($contextid)) {
    $context = context_system::instance();
} else {
    $context = context::instance_by_id($contextid);
}
require_capability('filter/translations:edittranslations', $context);

$PAGE->set_context($context);

$coursecontext = $PAGE->context->get_course_context(false);
if (!empty($coursecontext)) {
    $PAGE->set_course(get_course($coursecontext->instanceid));
}

$title = get_string('managetranslationissues', 'filter_translations');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url(new moodle_url('/filter/translations/managetranslationissues.php'));

$form = new managetranslationissues_filterform();
$formdata = $form->get_data();

if ($formdata) {
    $urlparams = array(
        'issue' => $issue,
        'url' => $filterurl,
        'contextid' => $contextid,
        'targetlanguage' => $targetlanguage
    );
    $url = $PAGE->url;
    $url->params($urlparams);
    redirect($url);
}

echo $OUTPUT->header();

$data = new stdClass();
$data->issue = $issue;
$data->url = $filterurl;
$data->contextid = $contextid;
$data->targetlanguage = $targetlanguage;
$data->tsort = optional_param('tsort', 'id', PARAM_ALPHA);
$form->set_data($data);

$baseurl = $PAGE->url;
$baseurl->params((array)$data);

$table = new managetranslationissues_table($data, 'translationsname');
$table->define_baseurl($baseurl);
echo $form->render();
$table->out(100, true);

echo $OUTPUT->footer();
