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

/**
 * The actual filter class...
 */
class filter_translations extends moodle_text_filter {

    /**
     * Get the cache that will be used to cache translations.
     * Caching can be handled at application, session or request based on a config setting to allow tuning for
     * based on likely cardinality.
     *
     * @return cache_application|cache_session|cache_store|null
     * @throws dml_exception
     */
    public static function cache() {
        static $cache = null;

        if (!isset($cache)) {
            $mode = get_config('filter_translations', 'cachingmode');

            if (empty($mode)) {
                $mode = cache_store::MODE_REQUEST;
            }

            $cache = cache::make('filter_translations', 'translatedtext_' . $mode);
        }
        return $cache;
    }

    /**
     * Should the current page be translated?
     * Must be based on the script name as PAGE->url will not be set yet.
     *
     * @return bool
     * @throws dml_exception
     */
    public static function skiptranslations() {
        global $SCRIPT;

        static $skip = null;

        if (isset($skip)) {
            return $skip;
        }

        $skip = false;

        if (in_array(current_language(),
            explode(
                ',',
                get_config('filter_translations', 'excludelang')
            ))) {
            $skip = true;
        }

        if (!$skip && in_array($SCRIPT,
            preg_split(
                "/\r?\n/",
                get_config('filter_translations', 'untranslatedpages')
            ))) {
            $skip = true;
        }

        return $skip;
    }

    /**
     * Should inline-translation be disabled for this language?
     *
     * @return bool
     * @throws dml_exception
     */
    public static function skiplanguage() {
        static $skipthislang = null;

        if (isset($skipthislang)) {
            return $skipthislang;
        }

        $skipthislang = false;

        if (in_array(current_language(),
            explode(
                ',',
                get_config('filter_translations', 'excludelang')
            ))) {
            $skipthislang = true;
        }

        return $skipthislang;
    }

    /**
     * Apply the filter to the text
     *
     * @param string $text to be processed by the filter
     * @param array $options filter options
     * @return string text after processing
     * @see filter_manager::apply_filter_chain()
     */
    public function filter($text, array $options = []) {
        global $CFG;
        require_once($CFG->libdir . '/filelib.php');

        // Check to see if the text being filtered has either been translated at some other point in the filter stack
        // or is on a page that should not be translated.
        if (self::skiptranslations() || strpos($text, self::ENCODEDSEPERATOR . self::ENCODEDSEPERATOR) !== false) {
            return $text;
        }

        $context = $this->context; // Use the context value that the filter has.

        // Look for a hash in a span tag and remove the span tags.
        $foundhash = $this->findandremovehash($text);
        // Generate a hash based on the text to be translated.
        $generatedhash = $this->generatehash($text);
        $targetlanguage = current_language();

        $cachekey = $targetlanguage . ($generatedhash ?? $foundhash);

        // Look for a cached translation and return it, unless we're doing in-line translations.
        if (!self::checkinlinestranslation(true)) {
            $translatedtextcache = self::cache();
            $cachedtranslatedtext = $translatedtextcache->get($cachekey);

            if ($cachedtranslatedtext !== false) {
                translator::$cachehit++;
                return $cachedtranslatedtext;
            }
        }

        if (empty($text)) {
            // If there's nothing to translate then just give back an empty string.
            $translatedtext = '';
        } else {
            // Get the best translation (object) to use.
            $translator = new translator();
            $translation = $translator->get_best_translation($targetlanguage, $generatedhash, $foundhash, $text, $context);

            if (empty($translation)) {
                // No translation so we'll just return the text unaltered.
                $translatedtext = $text;
                $translationforbutton = null;
            } else {
                // Grant the user access to any files included in the translation and rewrite file URLs.
                $this->grantaccesstotranslationfiles($translation);
                $translatedtext = file_rewrite_pluginfile_urls(
                    $translation->get('substitutetext'),
                    'pluginfile.php',
                    context_system::instance()->id,
                    'filter_translations',
                    'substitutetext',
                    $translation->get('id')
                );

                // If we're using a translation for a different language then when creating the in-line translation button
                // make it go to a fresh translation.
                if ($translation->get('targetlanguage') != current_language() && $translation->get('targetlanguage') == 'en') {
                    $translationforbutton = null;
                } else {
                    $translationforbutton = $translation;
                }
            }

            // If we're doing in-line translation then add the button.
            $translatedtext .= $this->addinlinetranslation($text, $generatedhash, $foundhash, $translationforbutton);
        }

        // Cache the result - unless we're doing in-line translation.
        if (!self::checkinlinestranslation(true)) {
            $translatedtextcache->set($cachekey, $translatedtext);
        }

        return $translatedtext;
    }

    /**
     * Trim and MD5 hash a string.
     *
     * @param $text
     * @return string
     */
    public function generatehash($text) {
        return md5(trim($text));
    }

    /**
     * Look for an empty span tag which can be used to link a piece of text to a specific translation.
     *
     * @param $text
     * @return mixed|null
     */
    public function findandremovehash(&$text) {
        // Quick and dirty check.
        if (strpos($text, 'data-translationhash') === false) {
            return null;
        }

        // In TinyMCE translation span tags need to be inside <p> tags.
        // So we handle both formats for now. Eventually everything will move to the new format.
        // TODO: Update the old format into the new format, during upgrade???
        // New format: <p class="translationhash"><span data-translationhash="abcxxx">/span></p>
        // Old format: <span data-translationhash="abcxxx">/span>
        $translationhashregex = '/(?:<p>|<p class="translationhash">)\s*'
            . '<span\s*data-translationhash\s*=\s*[\'"]+([a-zA-Z0-9]+)[\'"]+\s*><\/span>\s*<\/p>|'
            . '<span\s*data-translationhash\s*=\s*[\'"]+([a-zA-Z0-9]+)[\'"]\s*><\/span>/';


        // Get the actual hash.
        $translationhashes = [];
        //preg_match('/<span data-translationhash[ ]*=[ ]*[\'"]+([a-zA-Z0-9]+)[\'"]+[ ]*>[ ]*<\/span>/', $text, $translationhashes);
        preg_match($translationhashregex, $text, $translationhashes);

        if (empty($translationhashes[1])) {
            return null;
        }

        // Remove the span tag from the text.
        //$text = preg_replace('/<span data-translationhash[ ]*=[ ]*[\'"]+([a-zA-Z0-9]+)[\'"]+[ ]*>[ ]*<\/span>/', '', $text);
        $text = preg_replace($translationhashregex, '', $text);

        return $translationhashes[1];
    }

    /**
     * As translations can be used in a wide variety of contexts and we do not want all related files to be
     * accessible to everyone we maintain a list of translations that have been used to render pages for a given
     * user in a session variable which we check in filter_translations_pluginfile to allow/deny access.
     *
     * @param $translation
     * @return void
     */
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

    // De duped list of parameters to be used to call translation_button.register javascript function.
    public static $translationstoinject = [];

    /**
     * Do work required to power the in-line translation button.
     *
     * @param string $rawtext
     * @param string $generatedhash
     * @param string $foundhash
     * @param translation $translation
     * @return string
     * @throws coding_exception
     * @throws dml_exception
     */
    protected function addinlinetranslation($rawtext, $generatedhash, $foundhash, $translation = null) {
        global $PAGE;

        static $registeredtranslations = [];
        static $inpagetranslationid = 0;

        // If we're not doing in-line translation then do nothing.
        if (!self::checkinlinestranslation()) {
            return '';
        }

        // Get the context of the translation, page if possible, or fall back to system.
        if (!empty($translation) && !empty($translation->get('contextid'))) {
            $contextid = $translation->get('contextid');
        } else {
            $contextid = $this->context->id;
        }

        // Build an object containing all the data that the AMD module will need to render the button.
        $obj = (object)[
            'rawtext' => $rawtext,
            'generatedhash' => $generatedhash,
            'foundhash' => $foundhash,
            'contextid' => $contextid,
            'translationid' => !empty($translation) ? $translation->get('id') : '',
            'staletranslation' => !empty($translation) && $generatedhash != $translation->get('lastgeneratedhash'), // Is it stale?
            'goodtranslation' => !empty($translation) && $generatedhash == $translation->get('lastgeneratedhash'), // Is it fresh?
            'notranslation' => empty($translation), // Is it not found?
        ];
        // Hash the object as a key to dedupe.
        $translationkey = md5(print_r($obj, true));

        // Check to see if we've already got this translation in the list. If not, add it.
        if (!key_exists($translationkey, $registeredtranslations)) {
            $inpagetranslationid++;
            $id = $inpagetranslationid;
            $obj->inpagetranslationid = $id;
            $jsobj = json_encode($obj);
            self::$translationstoinject[$id] = $jsobj;
            $registeredtranslations[$translationkey] = $id;
        } else {
            $id = $registeredtranslations[$translationkey];
        }

        // Return the encoded inpagetranslationid to be appended to the text ready for it to be found by the javascript
        // and turned in to a button when cross-referenced with the data from $registeredtranslations.
        return self::ENCODEDSEPERATOR . self::ENCODEDSEPERATOR . $this->encodeintegerashiddenchars($id) . self::ENCODEDSEPERATOR .
            self::ENCODEDSEPERATOR;
    }

    /**
     * Zero-width characters used to hide information about the translation in plain text.
     */
    const ENCODEDONE = "\u{200B}"; // Zero-Width Space - used to encode 1.
    const ENCODEDZERO = "\u{200C}"; // Zero-Width Non-Joiner - used to encode 0.
    const ENCODEDSEPERATOR = "\u{200D}"; // Zero-Width Joiner - used to delimit the encoded text.

    /**
     * Binary encode an integer and then encode it using zero-width characters.
     *
     * @param $int
     * @return array|string|string[]
     */
    private function encodeintegerashiddenchars($int) {
        $bin = decbin($int);
        $bin = str_replace('1', self::ENCODEDONE, $bin);
        $bin = str_replace('0', self::ENCODEDZERO, $bin);
        return $bin;
    }

    /**
     * Enable/disable in-line translation.
     *
     * @param $state
     * @return void
     */
    public static function toggleinlinestranslation($state) {
        global $SESSION;
        $SESSION->filter_translations_toggleinlinestranslation = $state;
    }

    /**
     * Is the current user session doing in-line translation?
     *
     * If $skipcapabilitycheck is true then we check to see if they should be able to do translations as well as if
     * the session variable is currently set.
     *
     * If $skipcapabilitycheck is false then we check they should be able to do translations.
     *
     * You need to skip capability checks if you make this call early in the page life-cycle as the context may not be
     * available.
     *
     * @param $skipcapabilitycheck
     * @return bool
     * @throws coding_exception
     * @throws dml_exception
     */
    public static function checkinlinestranslation($skipcapabilitycheck = false) {
        global $SESSION, $CFG, $PAGE;

        static $hascapability;

        if ($skipcapabilitycheck) {
            return !empty($SESSION->filter_translations_toggleinlinestranslation);
        }

        if (!isset($hascapability)) {
            $targetlanguage = current_language();

            if ($PAGE->state == $PAGE::STATE_BEFORE_HEADER) {
                $contextid = context_system::instance();
            } else {
                $contextid = $PAGE->context;
            }

            if ($targetlanguage == $CFG->lang) {
                $hascapability = has_capability('filter/translations:editsitedefaulttranslations', $contextid);
            } else {
                $hascapability = has_capability('filter/translations:edittranslations', $contextid);
            }
        }

        $val = !empty($hascapability) && !empty($SESSION->filter_translations_toggleinlinestranslation);

        if ($PAGE->state == $PAGE::STATE_BEFORE_HEADER) {
            // Don't hold the result in the static variable as it may change later in the page life-cycle.
            unset($hascapability);
        }

        return $val;
    }
}
