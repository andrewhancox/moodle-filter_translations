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

use filter_translations\translationproviders\googletranslate;
use filter_translations\translationproviders\languagestringreverse;

class translator {
    protected function get_string_manager() {
        return get_string_manager();
    }

    public function get_best_translation($language, $generatedhash, $foundhash, $text) {
        $translations = $this->get_string_manager()->get_list_of_translations(true);
        $translationnames = array_values($translations);
        if (in_array($text, $translationnames)) {
            return null;
        }

        $prioritisedlanguages =
                array_reverse(array_merge(['en'], $this->get_string_manager()->get_language_dependencies($language)));

        $options = $this->get_usable_translations($prioritisedlanguages, $generatedhash, $foundhash);
        $optionsforbestlanguage = $this->filter_options_by_best_language($options, $prioritisedlanguages);
        $translation = $this->filter_options_by_best_hash($optionsforbestlanguage, $generatedhash, $foundhash);

        if (empty($translation) || $translation->get('lastgeneratedhash') != $generatedhash || $translation->get('targetlanguage') != $language) {
            $languagestrings = new languagestringreverse();
            $translation = $languagestrings->createorupdate_translation($foundhash, $generatedhash, $text, $language, $translation);
        }

        if (empty($translation) || $translation->get('lastgeneratedhash') != $generatedhash || $translation->get('targetlanguage') != $language) {
            $google = new googletranslate();
            $translation = $google->createorupdate_translation($foundhash, $generatedhash, $text, $language, $translation);
        }

        return $translation;
    }

    private function filter_options_by_best_hash($options, $generatedhash, $foundhash) {
        foreach ($options as $option) {
            if ($option->get('md5key') == $foundhash) {
                return $option;
            }
        }
        foreach ($options as $option) {
            if ($option->get('md5key') == $generatedhash) {
                return $option;
            }
        }
        foreach ($options as $option) {
            if ($option->get('lastgeneratedhash') == $generatedhash) {
                return $option;
            }
        }

        return false;
    }

    private function filter_options_by_best_language($options, $prioritisedlanguages) {
        $translationsbylang = [];
        foreach ($options as $option) {
            if (!isset($translationsbylang[$option->get('targetlanguage')])) {
                $translationsbylang[$option->get('targetlanguage')] = [];
            }
            $translationsbylang[$option->get('targetlanguage')][] = $option;
        }

        foreach ($prioritisedlanguages as $language) {
            if (isset($translationsbylang[$language])) {
                return $translationsbylang[$language];
            }
        }

        return [];
    }

    private function get_usable_translations($prioritisedlanguages, $generatedhash, $foundhash) {
        global $DB;

        $hashor = ['md5key = :generatedhash', 'lastgeneratedhash = :generatedhash2'];
        $params = ['generatedhash' => $generatedhash, 'generatedhash2' => $generatedhash];

        if (isset($foundhash)) {
            $hashor[] = 'md5key = :foundhash';
            $params['foundhash'] = $foundhash;
        }
        $hashor = implode(' OR ', $hashor);

        list($langsql, $langparam) = $DB->get_in_or_equal($prioritisedlanguages, SQL_PARAMS_NAMED);

        $select = "($hashor) AND targetlanguage $langsql";

        return translation::get_records_select($select, $params + $langparam);
    }
}
