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
    global $PAGE;

    $currentinlinetranslationstate = filter_translations::checkinlinestranslation();
    $inlinetransationtate = optional_param('inlinetransationtate', null, PARAM_BOOL);

    if (isset($inlinetransationtate)) {
        \filter_translations::toggleinlinestranslation($inlinetransationtate);
        redirect($PAGE->url);
    }

    return $renderer->render_from_template('filter_translations/toggleinlinestranslationstate', (object) [
            'url'                           => $PAGE->url->out(false, ['inlinetransationtate' => !$currentinlinetranslationstate]),
            'inlinetranslationstate' => $currentinlinetranslationstate
    ]);
}
