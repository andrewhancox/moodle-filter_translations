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

use filter_translations\translation;
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

        $cachekey = $this->generatehash($text);
        $translatedtextcache = cache::make('filter_translations', 'translatedtext');
        $renderedfilterscached = $translatedtextcache->get($cachekey);

        if ($renderedfilterscached !== false) {
            return $renderedfilterscached;
        }

        $foundhash = $this->findandremovehash($text);
        $generatedhash = $this->generatehash($text);

        $translator = new translator();
        $translation = $translator->get_best_translation(current_language(), $generatedhash, $foundhash);

        if (empty($translation)) {
            return $text . $this->addinlinetranslation($text, $generatedhash, $foundhash);
        }

        $this->grantaccesstotranslationfiles($translation);

        $translatedtext = file_rewrite_pluginfile_urls(
                $translation->get('substitutetext'),
                'pluginfile.php',
                context_system::instance()->id,
                'filter_translations',
                'substitutetext',
                $translation->get('id')
        );

        return $translatedtext . $this->addinlinetranslation($text, $generatedhash, $foundhash, $translation);
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

    private static $jsinited = false;
    private static $registeredtranslations = [];

    protected function addinlinetranslation($rawtext, $generatedhash, $foundhash, $translation = null) {
        global $OUTPUT, $PAGE;

        if (!self::checkinlinestranslation()) {
            return '';
        }

        if (!self::$jsinited) {
            $PAGE->requires->js_call_amd('filter_translations/translation_button', 'init');
            self::$jsinited = true;
        }

        $obj = (object) [
                'rawtext'          => urlencode($rawtext),
                'generatedhash'    => $generatedhash,
                'foundhash'        => $foundhash,
                'translationid'    => isset($translation) ? $translation->get('id') : '',
                'dirtytranslation' => isset($translation) && $generatedhash !== $translation->get('lastgeneratedhash'),
        ];
        $translationkey = md5(print_r($obj, true));
        $jsobj = json_encode($obj);

        if (!in_array($translationkey, self::$registeredtranslations)) {
            $PAGE->requires->js_amd_inline("require(['filter_translations/translation_button'], function(translation_button) { translation_button.register('$translationkey', $jsobj);});");
            self::$registeredtranslations[] = $translationkey;
        }

        return $OUTPUT->render_from_template('filter_translations/translatebutton', (object)[
                'translationkey' => $translationkey,
                'dirtytranslation' => isset($translation) && $generatedhash !== $translation->get('lastgeneratedhash'),
                'goodtranslation'  => isset($translation) && $generatedhash === $translation->get('lastgeneratedhash'),
                ]);
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
