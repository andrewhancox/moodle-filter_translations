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

/**
 * Persistent object to handle CRUD operations for translation issues.
 */
class translation_issue extends persistent {
    const TABLE = 'filter_translation_issues';

    /**
     * The translation referenced was used to replace text that does not match the text the translation was
     * created/updated for. This can happen when the translation has been chosen based on a span tag rather
     * than hash of the raw text.
     */
    const ISSUE_STALE = 10;
    /**
     * No translation was found that could be used to replace the raw text.
     */
    const ISSUE_MISSING = 20;

    /**
     * Lookup array of issue types.
     *
     * @return array
     * @throws \coding_exception
     */
    public static function get_issue_types() {
        return [
            self::ISSUE_STALE => get_string('issue_' . self::ISSUE_STALE, 'filter_translations'),
            self::ISSUE_MISSING => get_string('issue_' . self::ISSUE_MISSING, 'filter_translations'),
        ];
    }

    /**
     * @return array[]
     */
    protected static function define_properties() {
        return array(
            // Type of issue that occurred.
            'issue' => [
                'type' => PARAM_INT,
                'choices' => array_keys(self::get_issue_types())
            ],
            // Page URL - as set at PAGE->url - where the issue occurred.
            'url' => [
                'type' => PARAM_URL,
            ],
            // Hash from span tag if found, otherwise the generated hash.
            'md5key' => [
                'type' => PARAM_TEXT,
            ],
            // Target language when issue occurred.
            'targetlanguage' => [
                'type' => PARAM_TEXT,
            ],
            // Context where the issue occurred.
            'contextid' => [
                'type' => PARAM_INT,
            ],
            // Generated MD5 hash of the raw text that triggered the issue.
            'generatedhash' => [
                'type' => PARAM_TEXT,
            ],
            // Raw text that triggered the issue.
            'rawtext' => [
                'type' => PARAM_RAW,
            ],
            // Translation id that triggered the issue - will be 0 for missing translations.
            'translationid' => [
                'type' => PARAM_INT,
            ],
        );
    }

    /**
     * Delete all issues relating to the translation
     *
     * @param translation $translation
     * @return void
     * @throws \coding_exception
     */
    public static function remove_records_for_translation($translation) {
        $issues = self::get_records_select(
            'targetlanguage = :targetlanguage AND (md5key = :md5key OR generatedhash = :generatedhash OR translationid = translationid)',
            [
                'md5key' => $translation->get('md5key'),
                'generatedhash' => $translation->get('lastgeneratedhash'),
                'translationid' => $translation->get('id'),
                'targetlanguage' => $translation->get('targetlanguage')
            ]);

        foreach ($issues as $issue) {
            $issue->delete();
        }
    }

    /**
     * Helper function to handle searching by url.
     *
     * @param $filters
     * @param $sort
     * @param $order
     * @param $skip
     * @param $limit
     * @return translation_issue[]
     */
    public static function get_records_sql_compare_text($filters = array(), $sort = '', $order = 'ASC', $skip = 0, $limit = 0) {
        global $DB;

        $whereclauses = [];
        $params = [];

        foreach ($filters as $key => $value) {
            if ($key == 'url') {
                $whereclauses[] = $DB->sql_compare_text($key) . " = " . $DB->sql_compare_text(":$key");
            } else {
                $whereclauses[] = "$key = :$key";
            }
            $params[$key] = $value;
        }

        return translation_issue::get_records_select(implode(' AND ', $whereclauses), $params, $sort, '*', $skip, $limit);
    }
}
