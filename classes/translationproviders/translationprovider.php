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

use admin_setting_configcheckbox;
use admin_setting_configtext;
use curl;
use filter_translations\translation;
use moodle_url;

abstract class translationprovider {
    public function createorupdate_translation($foundhash, $generatedhash, $text, $targetlanguage, $translationtoupdate) {
        if (empty($text)) {
            return false;
        }

        $besthash = $foundhash ?? $generatedhash;

        $translatedtext = $this->generate_translation($text, $targetlanguage);

        if (empty($translatedtext)) {
            return false;
        }

        if (!empty($translationtoupdate) && $translationtoupdate->get('targetlanguage') == $targetlanguage) {
            $translation = $translationtoupdate;
        } else {
            $translation = new translation();
            $translation->set('md5key', $besthash);
            $translation->set('targetlanguage', $targetlanguage);
            $translation->set('contextid', \context_system::instance()->id);
        }

        $translation->set('translationsource', translation::SOURCE_AUTOMATIC);
        $translation->set('lastgeneratedhash', $generatedhash);
        $translation->set('substitutetext', $translatedtext);
        $translation->set('rawtext', $text);
        $translation->save();

        return $translation;
    }

    protected abstract function generate_translation($text, $targetlanguage);
}
