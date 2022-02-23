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

use filter_translations\translator;

defined('MOODLE_INTERNAL') || die();

class filter_translations extends moodle_text_filter {

    /**
     * Apply the filter to the text
     *
     * @param string $text to be processed by the text
     * @param array $options filter options
     * @return string text after processing
     * @see filter_manager::apply_filter_chain()
     */
    public function filter($text, array $options = []) {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        // Prevent double translation when adding the button.
        if (strpos($text, self::ENCODEDSEPERATOR . self::ENCODEDSEPERATOR) !== false) {
            return $text;
        }

        $foundhash = $this->findandremovehash($text);
        $generatedhash = $this->generatehash($text);

        $cachekey = $generatedhash ?? $foundhash;

        $translatedtextcache = cache::make('filter_translations', 'translatedtext');
        $cachedtranslatedtext = $translatedtextcache->get($cachekey);

        if ($cachedtranslatedtext !== false) {
            return $cachedtranslatedtext;
        }

        if (empty($text)) {
            $translatedtext = '';
        } else {
            $translator = new translator();
            $targetlanguage = current_language();
            $translation = $translator->get_best_translation($targetlanguage, $generatedhash, $foundhash, $text);

            if (empty($translation)) {
                $translatedtext = $text;
            } else {
                $this->grantaccesstotranslationfiles($translation);

                $translatedtext = file_rewrite_pluginfile_urls(
                    $translation->get('substitutetext'),
                    'pluginfile.php',
                    context_system::instance()->id,
                    'filter_translations',
                    'substitutetext',
                    $translation->get('id')
                );
            }

            $translatedtext .= $this->addinlinetranslation($text, $generatedhash, $foundhash, $translation);
        }

        $translatedtextcache->set($cachekey, $translatedtext);

        return $translatedtext;
    }

    protected function generatehash($text) {
        return md5(trim($text));
    }

    protected function findandremovehash(&$text) {
        if (strpos($text, 'data-translationhash') === false) {
            return null;
        }

        $translationhashes = [];
        preg_match('/<span data-translationhash[ ]*=[ ]*[\'"]+([a-zA-Z0-9]+)[\'"]+[ ]*>[ ]*<\/span>/', $text, $translationhashes);

        if (empty($translationhashes[1])) {
            return null;
        }

        $text = preg_replace('/<span data-translationhash[ ]*=[ ]*[\'"]+([a-zA-Z0-9]+)[\'"]+[ ]*>[ ]*<\/span>/', '', $text);

        return $translationhashes[1];
    }

    protected function grantaccesstotranslationfiles($translation) {
        global $SESSION;

        if (!isset($SESSION->filter_translations_usedtranslations)) {
            $SESSION->filter_translations_usedtranslations = [];
        }
        $translationid = $translation->get('id');
        if (!in_array($translationid, $SESSION->filter_translations_usedtranslations)) {
            $SESSION->filter_translations_usedtranslations[] = $translation->get('id');
        }
    }

    private static $registeredtranslations = [];
    public static $translationstoinject = [];

    private static $inpagetranslationid = 0;
    private static function get_next_inpagetranslationid() {
        self::$inpagetranslationid++;
        return self::$inpagetranslationid;
    }

    protected function addinlinetranslation($rawtext, $generatedhash, $foundhash, $translation = null) {
        if (!self::checkinlinestranslation()) {
            return '';
        }

        $obj = (object) [
                'rawtext'          => $rawtext,
                'generatedhash'    => $generatedhash,
                'foundhash'        => $foundhash,
                'translationid'    => !empty($translation) ? $translation->get('id') : '',
                'staletranslation' => !empty($translation) && $generatedhash !== $translation->get('lastgeneratedhash'),
                'goodtranslation'  => !empty($translation) && $generatedhash === $translation->get('lastgeneratedhash'),
        ];
        $translationkey = md5(print_r($obj, true));
        $obj->inpagetranslationid = $translationkey;
        $jsobj = json_encode($obj);

        if (!key_exists($translationkey, self::$registeredtranslations)) {
            $id = self::get_next_inpagetranslationid();
            self::$translationstoinject[$id] = $jsobj;
            self::$registeredtranslations[$translationkey] = $id;
        } else {
            $id =  self::$registeredtranslations[$translationkey];
        }

        return self::ENCODEDSEPERATOR . self::ENCODEDSEPERATOR . $this->encodeintegerashiddenchars($id) . self::ENCODEDSEPERATOR . self::ENCODEDSEPERATOR;
    }

    const ENCODEDONE = "\u{200B}"; // Zero-Width Space
    const ENCODEDZERO = "\u{200C}"; // Zero-Width Non-Joiner
    const ENCODEDSEPERATOR = "\u{200D}"; // Zero-Width Joiner
    private function encodeintegerashiddenchars($int) {
        $bin = decbin($int);
        $bin = str_replace('1', self::ENCODEDONE, $bin);
        $bin = str_replace('0', self::ENCODEDZERO, $bin);
        return $bin;
    }

    public static function toggleinlinestranslation($state) {
        global $SESSION;
        $SESSION->filter_translations_toggleinlinestranslation = $state;
    }

    public static function checkinlinestranslation() {
        global $SESSION;
        return !empty($SESSION->filter_translations_toggleinlinestranslation);
    }
}
