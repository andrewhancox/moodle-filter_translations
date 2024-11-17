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
use context_system;

class filter_test extends advanced_testcase {

    public function setUp(): void {
        global $CFG;

        parent::setUp();

        $this->resetAfterTest(true);

        require_once("$CFG->dirroot/lib/tests/componentlib_test.php");

        filter_set_global_state('translations', TEXTFILTER_ON);

        // Fake the installation of the spanish and german language packs.
        foreach (['de', 'es', 'de_kids'] as $lang) {
            $langconfig = "<?php\n\$string['decsep'] = 'X';";
            $langfolder = $CFG->dataroot . '/lang/' . $lang;
            check_dir_exists($langfolder);
            file_put_contents($langfolder . '/langconfig.php', $langconfig);
        }
    }

    public function test_filter_text() {
        global $SESSION;

        $generatedhash = md5('some english text');

        $contextid = context_system::instance()->id;

        $translation = new translation(0, (object)[
            'targetlanguage' => 'de',
            'lastgeneratedhash' => $generatedhash,
            'md5key' => $generatedhash,
            'contextid' => $contextid,
            'substitutetext' => 'some german text',
        ]);
        $translation->save();

        $kidstranslation = new translation(0, (object)[
            'targetlanguage' => 'es',
            'lastgeneratedhash' => $generatedhash,
            'md5key' => $generatedhash,
            'contextid' => $contextid,
            'substitutetext' => 'some spanish text',
        ]);
        $kidstranslation->save();

        $SESSION->lang = 'de';
        $this->assertEquals(
            'some german text',
            format_text(
                '<p class="translationhash"><span data-translationhash="' . $generatedhash .'"></span></p>Hello',
                FORMAT_MOODLE,
                ['noclean' => true, 'trusted' => true]
            )
        );

        $SESSION->lang = 'es';
        $this->assertEquals(
            'some spanish text',
            format_text(
                '<p class="translationhash"><span data-translationhash="' . $generatedhash .'"></span></p>Hello',
                FORMAT_HTML,
                ['noclean' => true, 'trusted' => true])
        );
    }

    public function test_findandremovehash() {
        $filter = new \filter_translations(context_system::instance(), []);

        $text = 'No hash in here';
        $this->assertNull($filter->findandremovehash($text));

        $text = '<span data-translationhash="thehash"></span>Some text';
        $this->assertEquals('thehash', $filter->findandremovehash($text));
        $this->assertEquals('Some text', $text);

        $text = '<p class="translationhash"><span data-translationhash="thenexthash"></span></p>Some more text<span data-translationhash="thenexthash"></span>';
        $this->assertEquals('thenexthash', $filter->findandremovehash($text));
        $this->assertEquals('Some more text', $text);

        $text = '<p class="translationhash"><span data-translationhash="thenexthash"></span></p>Some more text with <span>spans</span> in it<p class="translationhash"><span data-translationhash="thenexthash"></span></p>';
        $this->assertEquals('thenexthash', $filter->findandremovehash($text));
        $this->assertEquals('Some more text with <span>spans</span> in it', $text);
    }

    public function test_get_best_translation() {
        $translator = new translator();

        $generatedhash = md5('generatedhash');
        $foundhash = md5('foundhash');

        $context = context_system::instance();

        $translation = new translation(0, (object)[
            'targetlanguage' => 'de',
            'lastgeneratedhash' => $generatedhash,
            'md5key' => $generatedhash,
            'contextid' => $context->id,
            'substitutetext' => 'some text',
        ]);
        $translation->save();

        $kidstranslation = new translation(0, (object)[
            'targetlanguage' => 'de_kids',
            'lastgeneratedhash' => $generatedhash,
            'md5key' => $generatedhash,
            'contextid' => $context->id,
            'substitutetext' => 'some text for kids',
        ]);
        $kidstranslation->save();

        $this->assertEquals($kidstranslation->get('id'),
            $translator->get_best_translation('de_kids', $generatedhash, $foundhash, 'untranslated text', $context)->get('id'));

        $kidstranslationmatchonfound = new translation(0, (object)[
            'targetlanguage' => 'de_kids',
            'lastgeneratedhash' => $generatedhash,
            'md5key' => $foundhash,
            'contextid' => $context->id,
            'substitutetext' => 'some text for kids',
        ]);
        $kidstranslationmatchonfound->save();

        $this->assertEquals($kidstranslationmatchonfound->get('id'),
            $translator->get_best_translation('de_kids', $generatedhash, $foundhash, 'untranslated text', $context)->get('id'));
    }
}
