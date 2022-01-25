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

namespace filter_translations\translationproviders;

use cache;
use core_component;

defined('MOODLE_INTERNAL') || die();

class languagestringreverse extends translationprovider {
    private function get_flipped_strings_by_component($lang) {
        $flippedstringscache = cache::make('filter_translations', 'flippedstringsbycomponent');
        $flippedstringsbycomponent = $flippedstringscache->get($lang);

        if ($flippedstringsbycomponent !== false) {
            return $flippedstringsbycomponent;
        }

        $flippedstringsbycomponent = [];
        foreach ($this->raw_strings_by_component($lang) as $component => $strings) {
            $flippedstringsbycomponent[$component] = array_flip($strings);
        }

        $flippedstringscache->set($lang, $flippedstringsbycomponent);
        return $flippedstringsbycomponent;
    }

    private function get_strings_by_component($lang) {
        $stringscache = cache::make('filter_translations', 'stringsbycomponent');
        $stringsbycomponent = $stringscache->get($lang);

        if ($stringsbycomponent !== false) {
            return $stringsbycomponent;
        }

        $stringsbycomponent = $this->raw_strings_by_component($lang);

        $stringscache->set($lang, $stringsbycomponent);
        return $stringsbycomponent;
    }

    private function raw_strings_by_component($lang) {
        $stringsbycomponent = [];
        foreach (core_component::get_component_list() as $type => $typecomponents) {
            foreach ($typecomponents as $component => $path) {
                $stringsbycomponent[$component] = $this->get_raw_lang_strings($component, $type, $path, $lang);
            }
        }
        return $stringsbycomponent;
    }

    private function get_raw_lang_strings($component, $type, $componentpath, $lang) {
        global $CFG;

        $string = [];

        $nonfrankenstyle = substr($component, strlen($type) + 1);
        if (file_exists("$CFG->langlocalroot/$lang/$nonfrankenstyle.php")) {
            include("$CFG->langlocalroot/$lang/$nonfrankenstyle.php");
        } else if (file_exists("$CFG->langlocalroot/$lang/$component.php")) {
            include("$CFG->langlocalroot/$lang/$component.php");
        } else if (file_exists("$CFG->langotherroot/$lang/$nonfrankenstyle.php")) {
            include("$CFG->langotherroot/$lang/$nonfrankenstyle.php");
        } else if (file_exists("$CFG->langotherroot/$lang/$component.php")) {
            include("$CFG->langotherroot/$lang/$nonfrankenstyle.php");
        } else if (file_exists("$componentpath/lang/$lang/$nonfrankenstyle.php")) {
            include("$componentpath/lang/$lang/$nonfrankenstyle.php");
        } else if (file_exists("$componentpath/lang/$lang/$component.php")) {
            include("$componentpath/lang/$lang/$component.php");
        }

        return $string;
    }

    protected function generate_translation($text, $targetlanguage) {
        $config = get_config('filter_translations');

        if (empty($config->languagestringreverse_enable)) {
            return null;
        }

        $strings_by_component = $this->get_strings_by_component($targetlanguage);

        $languages = get_string_manager()->get_list_of_translations();
        foreach ($languages as $sourcelanguage => $value) {
            foreach ($this->get_flipped_strings_by_component($sourcelanguage) as $component => $flipped_strings) {
                if (!key_exists($text, $flipped_strings)) {
                    continue;
                }

                if (key_exists($flipped_strings[$text], $strings_by_component[$component])) {
                    return $strings_by_component[$component][$flipped_strings[$text]];
                }
            }
        }
    }
}
