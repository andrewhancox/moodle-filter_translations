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
use filter_translations\translation_issue;
use filter_translations\translator_testable;

defined('MOODLE_INTERNAL') || die();

class translationissue_test extends advanced_testcase {

    public function setUp(): void {
        global $CFG, $PAGE;

        require_once("$CFG->dirroot/filter/translations/tests/fixtures/translator_testable.php");

        parent::setUp();

        $this->resetAfterTest(true);

        set_config('logmissing', true, 'filter_translations');
        set_config('logstale', true, 'filter_translations');
        set_config('logdebounce', 0, 'filter_translations');

        $PAGE->set_url('/my/index.php');
    }

    public function test_get_best_translation() {
        $translator = new translator_testable();

        $generatedhash = md5('generatedhash');
        $foundhash = md5('foundhash');

        $contextid = context_system::instance()->id;

        $translator->get_best_translation('de', $generatedhash, $foundhash, 'untranslated text');

        $issues = translation_issue::get_records();
        $this->assertCount(1, $issues);
        $this->assertEquals(translation_issue::ISSUE_MISSING, $issues[0]->get('issue'));
        $issues[0]->delete();

        $translation = new translation(0, (object)[
            'targetlanguage' => 'de',
            'lastgeneratedhash' => $generatedhash,
            'md5key' => $foundhash,
            'contextid' => $contextid,
            'substitutetext' => 'some text'
        ]);
        $translation->save();

        $translator->get_best_translation('de', $generatedhash, $foundhash, 'untranslated text');

        $issues = translation_issue::get_records();
        $this->assertCount(0, $issues);

        $translator->get_best_translation('de', md5('new hash'), $foundhash, 'new text');

        $issues = translation_issue::get_records();
        $this->assertCount(1, $issues);
        $translation_issue = $issues[0];
        $this->assertEquals(translation_issue::ISSUE_STALE, $translation_issue->get('issue'));

        $this->waitForSecond();
        $translator->get_best_translation('de', md5('new hash'), $foundhash, 'new text');
        $issues = translation_issue::get_records();
        $this->assertCount(1, $issues);
        $updatedissue = $issues[0];
        $this->assertGreaterThan($translation_issue->get('timemodified'), $updatedissue->get('timemodified'));

        set_config('logdebounce', 5, 'filter_translations');
        $this->waitForSecond();
        $translator->get_best_translation('de', md5('new hash'), $foundhash, 'new text');
        $issues = translation_issue::get_records();
        $this->assertCount(1, $issues);
        $this->assertEquals($updatedissue->get('timemodified'), $issues[0]->get('timemodified'));

        $translation->save();
        $issues = translation_issue::get_records();
        $this->assertCount(0, $issues);
    }
}
