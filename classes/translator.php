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

use cache;
use filter_translations\translationproviders\googletranslate;
use filter_translations\translationproviders\languagestringreverse;

/**
 *
 */
class translator {
    public static $googletranslatefetches = 0;
    public static $langstringlookupfetches = 0;
    public static $existingmanualtranslationsfound = 0;
    public static $existingautotranslationsfound = 0;
    public static $translationnotfound = 0;
    public static $cachehit = 0;

    /**
     * Wrapper function to allow overriding in translator_testable.
     * @return \core_string_manager
     */
    protected function get_string_manager() {
        return get_string_manager();
    }

    /**
     * Get the 'best' translation to use.
     *
     * @param string $language
     * @param string $generatedhash
     * @param string $foundhash
     * @param string $text
     * @param context $context The context passed by the filter.
     * @return false|translation|mixed|null
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function get_best_translation($language, $generatedhash, $foundhash, $text, $context) {
        global $CFG;

        $translations = $this->get_string_manager()->get_list_of_translations(true);

        // Don't translate names of languages.
        if (in_array($text, array_values($translations))) {
            return null;
        }

        // Get a prioritised list of the languages we could translate into - including the target language, any parent languages etc.
        $prioritisedlanguages = $this->get_prioritised_languages($language, $translations);
        // Get all translations that fit any of the prioritised languages.
        $options = $this->get_usable_translations($prioritisedlanguages, $generatedhash, $foundhash);
        // Get the translation that fits the highest priority language.
        $optionsforbestlanguage = $this->filter_options_by_best_language($options, $prioritisedlanguages);
        // Pick the best translation based on it's hashes.
        $translation = $this->filter_options_by_best_hash($optionsforbestlanguage, $generatedhash, $foundhash);

        // Never use stale translations that were auto-generated.
        if (!empty($translation) && $generatedhash !== $translation->get('lastgeneratedhash') &&
                $translation->get('translationsource') != translation::SOURCE_MANUAL) {
            $translation = null;
        }

        // If no translation can be found, or the only translation is either stale or for a lower priority language then
        // try automated translations - unless its a manual translation. To retranslate manual translation automatically - delete it manually first.
        if (empty($translation) || 
                ($translation->get('translationsource') != translation::SOURCE_MANUAL && 
                 ($translation->get('lastgeneratedhash') != $generatedhash || $translation->get('targetlanguage') != $language)) 
            ) {
            // First try reverse language string look up.
            $languagestrings = new languagestringreverse();
            $languagestringtranslation = $languagestrings->createorupdate_translation($foundhash, $generatedhash, $text,
                $language, $translation);

            if (!empty($languagestringtranslation)) {
                // Got one, use it.
                self::$langstringlookupfetches++;
                $translation = $languagestringtranslation;
            } else {
                // No dice... try google translate.
                $google = new googletranslate();
                $googletranslation = $google->createorupdate_translation($foundhash, $generatedhash, $text, $language, $translation);

                if (!empty($googletranslation)) {
                    self::$googletranslatefetches++;
                    $translation = $googletranslation;
                }
            }
        } else if (!empty($translation)) {
            if ($translation->get('translationsource') == translation::SOURCE_MANUAL) {
                self::$existingmanualtranslationsfound++;
            } else if ($translation->get('translationsource') == translation::SOURCE_AUTOMATIC) {
                self::$existingautotranslationsfound++;
            }
        } else {
            self::$translationnotfound++;
        }

        // Check to see if there is an issue that needs logging (e.g. missing or stale translation).
        // Skip the site default language.
        if ($language != $CFG->lang && !$this->skiplanguage($language)) {
            $this->checkforandlogissue($foundhash, $generatedhash, $language, $text, $translation, $context);
        }
        return $translation;
    }

    /**
     * If there's an issue with the translation then log it.
     *
     * @param string $foundhash
     * @param string $generatedhash
     * @param string $targetlanguage
     * @param string $text
     * @param translation $translation
     * @param context $context The context passed by the filter.
     * @return void
     */
    private function checkforandlogissue($foundhash, $generatedhash, $targetlanguage, $text, $translation, $context) {
        global $PAGE;

        // Is the logging all disabled?
        $config = get_config('filter_translations');
        if (empty($config->logmissing) && empty($config->logstale)) {
            return;
        }

        $translationissuescache = cache::make('filter_translations', 'translationissues');

        // Build an array of properties for the issue we've encountered.
        $issueproperties = [
            'url' => '',
            'md5key' => empty($foundhash) ? $generatedhash : $foundhash,
            'targetlanguage' => $targetlanguage,
            //'contextid' => \context_system::instance()->id, // Default to system context.
            'contextid' => $context->id, // Use context provided by filter.
            'generatedhash' => $generatedhash,
        ];
/*
        if ($PAGE->state != $PAGE::STATE_BEFORE_HEADER) {
            // We can't be certain the context has been set so are not going to log it.
            // In a perfect world we'd be able to check directly to see if the context has been set yet...
            $issueproperties['contextid'] = $PAGE->context->id;
        }
*/
        if ($PAGE->has_set_url()) {
            // If the page has had it's url set then we can log it.
            $issueproperties['url'] = $PAGE->url->out_as_local_url(false);
        }

        if (!empty($config->logmissing) && empty($translation)) {
            // Log it as a missing translation.
            $issueproperties['issue'] = translation_issue::ISSUE_MISSING;
            $issueproperties['translationid'] = 0;
        } else if (!empty($config->logstale) && !empty($translation) && $generatedhash !== $translation->get('lastgeneratedhash')) {
            // It's a stale translations.
            $issueproperties['issue'] = translation_issue::ISSUE_STALE;
            $issueproperties['translationid'] = $translation->get('id');
        } else {
            // Nothing to log.
            return;
        }

        // Check in the cache and see if we've already logged the problem.
        $cachekey = md5(json_encode($issueproperties));
        $issue = $translationissuescache->get($cachekey);

        // Did we log it recently? - recently being defined by the logdebounce config setting.
        // If so don't log it again.
        if (!empty($issue) && $issue->get('timemodified') >= time() - $config->logdebounce) {
            return;
        }

        // Grab the existing issue record if it exists.
        if (empty($issue)) {
            $issues = translation_issue::get_records_sql_compare_text($issueproperties);
            $issue = reset($issues);
        }

        if (!empty($issue)) {
            // If it exists then just bump the last modified time.
            $issue->update();
        } else {
            // Otherwise create it.
            if ($issueproperties['url'] != '') { // Don't log it url is empty.
                $issueproperties['rawtext'] = $text;
                $issue = new translation_issue();
                $issue->from_record((object)$issueproperties);
                $issue->save();
            }
        }

        // Cache the issue.
        $translationissuescache->set($cachekey, $issue);
    }

    /**
     * Choose the option that has the most specific match by hash.
     *
     * @param translation[] $options
     * @param $generatedhash
     * @param $foundhash
     * @return false|mixed
     */
    private function filter_options_by_best_hash($options, $generatedhash, $foundhash) {
        // Does one of them match the hash found in the translation span tag.
        foreach ($options as $option) {
            if ($option->get('md5key') == $foundhash) {
                return $option;
            }
        }

        // Does one of them match the hash of the text to be translated.
        foreach ($options as $option) {
            if ($option->get('md5key') == $generatedhash) {
                return $option;
            }
        }
        // Was one of them created or last updated when translating text which generated the same hash.
        foreach ($options as $option) {
            if ($option->get('lastgeneratedhash') == $generatedhash) {
                return $option;
            }
        }

        return false;
    }

    /**
     * Get the translation that fits the highest priority language.
     *
     * @param translation[] $options
     * @param $prioritisedlanguages
     * @return array|mixed
     */
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

    /**
     * Get all translations that could be used against the supplied hashes to target any of the given languages.
     *
     * @param $prioritisedlanguages
     * @param $generatedhash
     * @param $foundhash
     * @return translation[]
     * @throws \coding_exception
     * @throws \dml_exception
     */
    private function get_usable_translations($prioritisedlanguages, $generatedhash, $foundhash) {
        global $DB;

        $hashor = ['md5key = :generatedhash'];
        $params = ['generatedhash' => $generatedhash, 'generatedhash2' => $generatedhash];

        if (isset($foundhash)) {
            $hashor[] = 'md5key = :foundhash';
            $params['foundhash'] = $foundhash;
        }
        $hashor = implode(' OR ', $hashor);

        list($langsql, $langparam) = $DB->get_in_or_equal($prioritisedlanguages, SQL_PARAMS_NAMED, 'lang1');
        list($langsql2, $langparam2) = $DB->get_in_or_equal($prioritisedlanguages, SQL_PARAMS_NAMED, 'lang2');

        return translation::get_records_sql("
                select * from {filter_translations} where ($hashor) AND targetlanguage $langsql
                                                      union
                select * from {filter_translations} where (lastgeneratedhash = :generatedhash2) AND targetlanguage $langsql2
                ", $params + $langparam + $langparam2
        );
    }

    /**
     * Based on a supplied language return a list of languages which could supply a usable translation
     * sorted by priority.
     *
     * @param string $language
     * @param array $translations Installed language packs.
     * @return array
     */
    public function get_prioritised_languages(string $language, array $translations) {
        global $CFG;

        $dependencies = $this->get_string_manager()->get_language_dependencies($language);

        // Workplace compatibility.
        if (isset($CFG->wphideparentlang) && $CFG->wphideparentlang) {
            // Parent language is hidden, so add dependency to WP language.
            if (isset($translations[$dependencies[0] . '_wp']) && !in_array($dependencies[0] . '_wp', $dependencies)) {
                array_splice($dependencies, 1, 0, $dependencies[0] . '_wp');
            }
        }

        return array_reverse(array_merge([$CFG->lang], $dependencies));
    }

    /**
     * Check if this language can be skipped from logging in the missing translations table.
     *
     * @param string $language
     * @return true if $language is found, false otherwise
     */
    public static function skiplanguage(string $language) {
        static $skiplanguage = null;

        if (!isset($skiplanguage)) {
            $languagestoskip = get_config('filter_translations', 'logexcludelang');
            if (!empty($languagestoskip)) {
                $languagestoskip = explode(",", $languagestoskip);
                $skiplanguage = in_array($language, $languagestoskip);
            }
        }

        return $skiplanguage;
    }
}
