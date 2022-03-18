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

namespace filter_translations;

use core\persistent;

class translation_issue extends persistent {
    const TABLE = 'filter_translation_issues';

    const ISSUE_STALE = 10;
    const ISSUE_MISSING = 20;

    public static function get_issue_types() {
        return [
            self::ISSUE_STALE => get_string('issue_' . self::ISSUE_STALE, 'filter_translations'),
            self::ISSUE_MISSING => get_string('issue_' . self::ISSUE_MISSING, 'filter_translations'),
        ];
    }

    protected static function define_properties() {
        return array(
            'issue' => [
                'type' => PARAM_INT,
            ],
            'url' => [
                'type' => PARAM_URL,
            ],
            'md5key' => [
                'type' => PARAM_TEXT,
            ],
            'targetlanguage' => [
                'type' => PARAM_TEXT,
            ],
            'contextid' => [
                'type' => PARAM_INT,
            ],
            'generatedhash' => [
                'type' => PARAM_TEXT,
            ],
            'rawtext' => [
                'type' => PARAM_RAW,
            ],
            'translationid' => [
                'type' => PARAM_INT,
            ],
        );
    }

    public static function remove_records_for_translation($translation) {
        $issues = self::get_records([
            'md5key' => $translation->get('md5key'),
            'targetlanguage' => $translation->get('targetlanguage')
        ]);

        foreach ($issues as $issue) {
            $issue->delete();
        }
    }
}
