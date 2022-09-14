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

use advanced_testcase;
use cache_helper;
use context_system;
use filter_translations;

defined('MOODLE_INTERNAL') || die();

class translatorcaching_test extends advanced_testcase {

    public function setUp(): void {
        global $CFG;

        parent::setUp();

        $this->resetAfterTest(true);

        require_once("$CFG->dirroot/lib/tests/componentlib_test.php");

        filter_set_global_state('translations', TEXTFILTER_ON);

        // Fake the installation of the spanish and german language packs.
        foreach (['de', 'sp', 'de_kids'] as $lang) {
            $langconfig = "<?php\n\$string['decsep'] = 'X';";
            $langfolder = $CFG->dataroot . '/lang/' . $lang;
            check_dir_exists($langfolder);
            file_put_contents($langfolder . '/langconfig.php', $langconfig);
        }

        set_config('perfdebug', 15);
    }

    public function test_get_best_translation() {
        global $SESSION;

        $generatedhash = md5('originaltext');

        $contextid = context_system::instance()->id;

        $translation = new translation(0, (object)[
            'targetlanguage' => 'de',
            'lastgeneratedhash' => $generatedhash,
            'md5key' => $generatedhash,
            'contextid' => $contextid,
            'substitutetext' => 'translatedtext'
        ]);
        $translation->save();

        $filter = new \filter_translations(context_system::instance(), []);

        $SESSION->lang = 'en';
        $this->assertEquals('originaltext', $filter->filter('originaltext'));

        $SESSION->lang = 'de';
        $this->assertEquals('translatedtext', $filter->filter('originaltext'));

        $stats = cache_helper::get_stats();
        $this->assertEquals(0, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['hits']);
        $this->assertEquals(2, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['misses']);
        $this->assertEquals(2, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['sets']);

        $SESSION->lang = 'en';
        $this->assertEquals('originaltext', $filter->filter('originaltext'));

        $SESSION->lang = 'de';
        $this->assertEquals('translatedtext', $filter->filter('originaltext'));

        $stats = cache_helper::get_stats();
        $this->assertEquals(2, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['hits']);
        $this->assertEquals(2, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['misses']);
        $this->assertEquals(2, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['sets']);

        $unusedtranslation = new translation(0, (object)[
            'targetlanguage' => 'de',
            'lastgeneratedhash' => md5('unusedtranslation'),
            'md5key' => md5('unusedtranslation'),
            'contextid' => $contextid,
            'substitutetext' => 'unusedtranslatedtext'
        ]);
        $unusedtranslation->save();

        $this->assertEquals('translatedtext', $filter->filter('originaltext'));

        $stats = cache_helper::get_stats();
        $this->assertEquals(3, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['hits']);
        $this->assertEquals(2, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['misses']);
        $this->assertEquals(2, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['sets']);

        $translation->set('substitutetext', 'newtranslatedtext')->save();
        $this->assertEquals('newtranslatedtext', $filter->filter('originaltext'));

        $stats = cache_helper::get_stats();
        $this->assertEquals(3, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['hits']);
        $this->assertEquals(3, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['misses']);
        $this->assertEquals(3, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['sets']);

        $this->assertEquals('newtranslatedtext', $filter->filter('originaltext'));

        $stats = cache_helper::get_stats();
        $this->assertEquals(4, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['hits']);
        $this->assertEquals(3, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['misses']);
        $this->assertEquals(3, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['sets']);

        $SESSION->lang = 'en';
        $this->assertEquals('originaltext', $filter->filter('originaltext'));

        $stats = cache_helper::get_stats();
        $this->assertEquals(5, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['hits']);
        $this->assertEquals(3, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['misses']);
        $this->assertEquals(3, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['sets']);

        filter_translations::toggleinlinestranslation(true);

        $this->assertEquals('originaltext', $filter->filter('originaltext'));

        $stats = cache_helper::get_stats();
        $this->assertEquals(5, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['hits']);
        $this->assertEquals(3, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['misses']);
        $this->assertEquals(3, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['sets']);

        filter_translations::toggleinlinestranslation(false);

        $this->assertEquals('originaltext', $filter->filter('originaltext'));

        $stats = cache_helper::get_stats();
        $this->assertEquals(6, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['hits']);
        $this->assertEquals(3, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['misses']);
        $this->assertEquals(3, $stats["filter_translations/translatedtext_4"]["stores"]["default_request"]['sets']);
    }
}
