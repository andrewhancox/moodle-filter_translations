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
use filter_translations\translation_issue;

/**
 * @package filter_translations
 * @author Andrew Hancox <andrewdchancox@googlemail.com>
 * @author Open Source Learning <enquiries@opensourcelearning.co.uk>
 * @link https://opensourcelearning.co.uk
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @copyright 2021, Andrew Hancox
 */

function filter_translations_pluginfile($course, $cm, context $context, $filearea, $args, $forcedownload) {
    global $SESSION;

    $itemid = array_shift($args); // Ignore revision - designed to prevent caching problems only...

    if (!isset($SESSION->filter_translations_usedtranslations) ||
        !in_array($itemid, $SESSION->filter_translations_usedtranslations)) {
        return false;
    }

    $relativepath = implode('/', $args);
    $fullpath = "/{$context->id}/filter_translations/$filearea/$itemid/$relativepath";
    $fs = get_file_storage();
    if (!($file = $fs->get_file_by_hash(sha1($fullpath))) || $file->is_directory()) {
        return false;
    }

    // Force download.
    send_stored_file($file, 0, 0, true);
}

/**
 * Renders the popup.
 *
 * @param renderer_base $renderer
 * @return string The HTML
 */
function filter_translations_render_navbar_output(\renderer_base $renderer) {
    global $PAGE, $CFG, $DB;

    if (!filter_is_enabled('translations')) {
        return '';
    }

    if (!has_capability('filter/translations:edittranslations', $PAGE->context)) {
        return '';
    }

    $targetlanguage = current_language();

    if ($targetlanguage == $CFG->lang && !has_capability('filter/translations:editsitedefaulttranslations', $PAGE->context)) {
        return '';
    }

    require_once("$CFG->dirroot/filter/translations/filter.php");

    $currentinlinetranslationstate = filter_translations::checkinlinestranslation();
    $inlinetransationtate = optional_param('inlinetransationtate', null, PARAM_BOOL);

    if (isset($inlinetransationtate)) {
        require_capability('filter/translations:edittranslations', $PAGE->context);
        \filter_translations::toggleinlinestranslation($inlinetransationtate);
        redirect($PAGE->url);
    }

    if (!empty($PAGE->cm->id)) {
        $context = context_module::instance($PAGE->cm->id);
    } else if (!empty($PAGE->course->id) && $PAGE->course->id != SITEID) {
        $context = context_course::instance($PAGE->course->id);
    } else {
        $context = $PAGE->context->get_course_context(false);
    }
    if (empty($context)) {
        $context = context_system::instance();
    }

    $missingtranslationsurl = new moodle_url("/filter/translations/managetranslationissues.php", [
        'url' => $PAGE->url->out_as_local_url(false),
        'issue' => translation_issue::ISSUE_MISSING,
        'targetlanguage' => $targetlanguage,
        'contextid' => $context->id,
    ]);

    $staletranslationsurl = new moodle_url("/filter/translations/managetranslationissues.php", [
        'url' => $PAGE->url->out_as_local_url(false),
        'issue' => translation_issue::ISSUE_STALE,
        'targetlanguage' => $targetlanguage,
        'contextid' => $context->id,
    ]);

    $contextmissingtranslationsurl = new moodle_url("/filter/translations/managetranslationissues.php", [
        'contextid' => $context->id,
        'issue' => translation_issue::ISSUE_MISSING,
        'targetlanguage' => $targetlanguage,
    ]);

    $contextstaletranslationsurl = new moodle_url("/filter/translations/managetranslationissues.php", [
        'contextid' => $context->id,
        'issue' => translation_issue::ISSUE_STALE,
        'targetlanguage' => $targetlanguage,
    ]);

    $allmissingtranslationsurl = new moodle_url("/filter/translations/managetranslationissues.php", [
        'issue' => translation_issue::ISSUE_MISSING,
        'targetlanguage' => $targetlanguage,
    ]);

    $allstaletranslationsurl = new moodle_url("/filter/translations/managetranslationissues.php", [
        'issue' => translation_issue::ISSUE_STALE,
        'targetlanguage' => $targetlanguage,
    ]);

    $alltranslationsurl = new moodle_url("/filter/translations/managetranslations.php");

    return $renderer->render_from_template('filter_translations/toggleinlinestranslationstate', (object)[
        'toogleinlinetranslationurl' => $PAGE->url->out(false, ['inlinetransationtate' => !$currentinlinetranslationstate]),
        'coursemissingtranslationsurl' => $contextmissingtranslationsurl->out(false),
        'coursestaletranslationsurl' => $contextstaletranslationsurl->out(false),
        'missingtranslationsurl' => $missingtranslationsurl->out(false),
        'staletranslationsurl' => $staletranslationsurl->out(false),
        'allmissingtranslationsurl' => $allmissingtranslationsurl->out(false),
        'allstaletranslationsurl' => $allstaletranslationsurl->out(false),
        'inlinetranslationstate' => $currentinlinetranslationstate,
        'missingcount' => ($currentinlinetranslationstate && $targetlanguage != $CFG->lang) ? filter_translations_cap_count(translation_issue::count_records(['issue' => translation_issue::ISSUE_MISSING, 'targetlanguage' => $targetlanguage])) : '-',
        'stalecount' => ($currentinlinetranslationstate) ? filter_translations_cap_count(translation_issue::count_records(['issue' => translation_issue::ISSUE_STALE, 'targetlanguage' => $targetlanguage])) : '-',
        'alltranslationsurl' => $alltranslationsurl->out(false),
        'translateall' => (has_capability('filter/translations:editsitedefaulttranslations', $context)) ? true : false,
    ]);
}

function filter_translations_cap_count($count) {
    if ($count < 100) {
        return $count;
    } else {
        return '99+';
    }
}

function filter_translations_after_config() {
    global $CFG;
    require_once("$CFG->dirroot/filter/translations/filter.php");

    if (filter_translations::checkinlinestranslation(true)) {
        $CFG->formatstringstriptags = false;
    }
}

function filter_translations_before_footer() {
    global $PAGE, $CFG;

    require_once("$CFG->dirroot/filter/translations/filter.php");

    if (empty(\filter_translations::$translationstoinject)) {
        return;
    }

    $PAGE->requires->js_call_amd('filter_translations/translation_button', 'init', ['returnurl' => $PAGE->url->out()]);

    foreach (\filter_translations::$translationstoinject as $id => $jsobj) {
        $PAGE->requires->js_amd_inline("require(['filter_translations/translation_button'], function(translation_button) { translation_button.register('$id', $jsobj);});");
    }

    $PAGE->requires->js_amd_inline("require(['filter_translations/translation_button'], function(translation_button) { translation_button.findandinjectbuttons();});");
}
