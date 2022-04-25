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

use core\notification;
use filter_translations\edittranslationform;
use filter_translations\translation;
use filter_translations\unifieddiff;

require_once(__DIR__ . '../../../config.php');

$id = optional_param('id', null, PARAM_INT);
$contextid = optional_param('contextid', null, PARAM_INT);
$generatedhash = optional_param('generatedhash', null, PARAM_TEXT);
$foundhash = optional_param('foundhash', null, PARAM_TEXT);
$rawtext = optional_param('rawtext', null, PARAM_RAW);
$returnurl = optional_param('returnurl', new moodle_url('/filter/translations/managetranslations.php'), PARAM_URL);

if (empty($id)) {
    $title = get_string('createtranslation', 'filter_translations');
} else {
    $title = get_string('edittranslation', 'filter_translations');
}

if (empty($contextid)) {
    $context = context_system::instance();
} else {
    $context = context::instance_by_id($contextid);
}

require_capability('filter/translations:edittranslations', $context);

$url = new moodle_url('/filter/translations/edittranslation.php');

$PAGE->set_context($context);

$coursecontext = $PAGE->context->get_course_context(false);
if (!empty($coursecontext)) {
    $PAGE->set_course(get_course($coursecontext->instanceid));
}

$PAGE->set_url($url);
$PAGE->set_title($title);
$PAGE->set_heading($title);

$persistent = null;
if (empty($id)) {
    $persistent = new translation();
    $persistent->set('md5key', empty($foundhash) ? $generatedhash : $foundhash);
    $persistent->set('targetlanguage', current_language());
    $persistent->set('substitutetext', $rawtext);
} else {
    $persistent = new translation($id);
    $url->param('id', $id);
}
$persistent->set('contextid', $contextid);

$istranslationstale = !empty($generatedhash) && !empty($persistent->get('id')) && $persistent->get('lastgeneratedhash') !== $generatedhash;
if (!empty($generatedhash)) {
    $persistent->set('lastgeneratedhash', $generatedhash);
}

$showdiff = false;
$old = false;
if (!empty($rawtext) && !empty($persistent->get('rawtext')) && $rawtext != $persistent->get('rawtext')) {
    $PAGE->requires->js_call_amd('filter_translations/diffrenderer', 'init',
        ['changeset' => unifieddiff::generatediff($rawtext, $persistent->get('rawtext'))]);
    $showdiff = true;
    $old = $persistent->get('rawtext');
}

if (!empty($rawtext)) {
    $persistent->set('rawtext', $rawtext);
}

if (
    (!isset($rawtext) && empty($persistent->get('substitutetext')))
    ||
    $rawtext != strip_tags($rawtext) || $persistent->get('substitutetext') != strip_tags($persistent->get('substitutetext'))
) {
    $formtype = edittranslationform::FORMTYPE_RICH;
} else if (strpos($rawtext, "\n") !== false) {
    $formtype = edittranslationform::FORMTYPE_PLAINMULTILINE;
} else {
    $formtype = edittranslationform::FORMTYPE_PLAIN;
}

$form = new edittranslationform($url->out(false), ['persistent' => $persistent, 'formtype' => $formtype, 'showdiff' => $showdiff, 'old' => $old]);

if ($data = $form->get_data()) {
    if (!empty($data->deletebutton)) {
        $persistent->delete();
        redirect($returnurl);
    }

    if ($formtype !== edittranslationform::FORMTYPE_RICH) {
        $persistent->set('substitutetext', $data->substitutetext_plain);
    }

    $persistent->from_record($form->filter_data_for_persistent($data));
    $persistent->save();

    if ($formtype == edittranslationform::FORMTYPE_RICH) {
        $data = file_postupdate_standard_editor($data, 'substitutetext', $form->get_substitute_test_editoroptions(), $context,
            'filter_translations',
            'substitutetext', $persistent->get('id'));

        $persistent->set('substitutetext', $data->substitutetext_editor['text']);
        $persistent->set('substitutetextformat', $data->substitutetext_editor['format']);
        $persistent->update();
    }

    redirect($returnurl);
} else if ($form->is_cancelled()) {
    redirect($returnurl);
}
$form->set_data(['returnurl' => $returnurl]);

$PAGE->requires->js(new moodle_url('/filter/translations/lib/diff2html.js'));
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/diff2html/bundles/css/diff2html.min.css'));

echo $OUTPUT->header();

if ($istranslationstale) {
    echo $OUTPUT->notification(get_string('staletranslation', 'filter_translations'), notification::WARNING);
}

echo html_writer::tag('h2', get_string('translation', 'filter_translations'));

$form->display();

echo $OUTPUT->footer();
