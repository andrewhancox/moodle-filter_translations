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

use filter_translations\managetranslations_filterform;
use filter_translations\managetranslations_table;

require_once(dirname(__FILE__) . '/../../config.php');

$rawtext = optional_param('rawtext', '', PARAM_TEXT);
$substitutetext = optional_param('substitutetext', '', PARAM_TEXT);
$targetlanguage = optional_param('targetlanguage', current_language(), PARAM_TEXT);
$hash = optional_param('hash', '', PARAM_TEXT);
$usermodified = optional_param('usermodified', -1, PARAM_INT);
$download = optional_param('download', '', PARAM_ALPHA);

$context = context_system::instance();

require_login();

require_capability('filter/translations:edittranslations', $context);

$PAGE->set_context($context);

$title = get_string('managetranslations', 'filter_translations');
$baseurl = new moodle_url('/filter/translations/managetranslations.php');

$form = new managetranslations_filterform();
$formdata = $form->get_data();

if ($formdata) {
    $urlparams = [
        'rawtext' => $rawtext,
        'substitutetext' => $substitutetext,
        'targetlanguage' => $targetlanguage,
        'hash' => $hash,
        'usermodified' => $usermodified,
    ];
    $baseurl->params($urlparams);
    redirect($baseurl);
}

$data = new stdClass();
$data->rawtext = $rawtext;
$data->substitutetext = $substitutetext;
$data->targetlanguage = $targetlanguage;
$data->hash = $hash;

if ($usermodified > 0) {
    $data->usermodified = $usermodified;
}

$data->tsort = optional_param('tsort', 'id', PARAM_ALPHA);
$form->set_data($data);

$baseurl->params((array)$data);
$baseurl->param('page', optional_param('page', '', PARAM_INT));
$PAGE->set_url($baseurl);

$table = new managetranslations_table($data, 'translationsname', $download);
$table->define_baseurl($baseurl);

if ($download) {
    $table->download($download);
} else {
    // Only print headers if not asked to download data.
    $PAGE->set_title($title);
    $PAGE->set_heading($title);
    echo $OUTPUT->header();

    echo $form->render();

    $table->out(100, true);

    echo $OUTPUT->single_button(new moodle_url('/filter/translations/edittranslation.php', ['returnurl' => $PAGE->url]),
        get_string('createtranslation', 'filter_translations'));

    echo $OUTPUT->footer();
}
