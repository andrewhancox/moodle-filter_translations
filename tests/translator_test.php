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

class translator_test extends advanced_testcase {

    public function setUp(): void {
        global $CFG;

        require_once("$CFG->dirroot/filter/translations/tests/fixtures/translator_testable.php");

        parent::setUp();

        $this->resetAfterTest(true);
    }

    public function test_get_best_translation() {
        $translator = new translator_testable();

        $generatedhash = md5('generatedhash');
        $foundhash = md5('foundhash');

        $contextid = context_system::instance()->id;

        $translation = new translation(0, (object) [
                'targetlanguage'    => 'de',
                'lastgeneratedhash' => $generatedhash,
                'md5key'            => $generatedhash,
                'contextid'         => $contextid,
                'substitutetext'    => 'some text'
        ]);
        $translation->save();

        $this->assertEquals($translation->get('id'),
                $translator->get_best_translation('de_kids', $generatedhash, $foundhash, 'untranslated text')->get('id'));

        $kidstranslation = new translation(0, (object) [
                'targetlanguage'    => 'de_kids',
                'lastgeneratedhash' => $generatedhash,
                'md5key'            => $generatedhash,
                'contextid'         => $contextid,
                'substitutetext'    => 'some text for kids'
        ]);
        $kidstranslation->save();

        $this->assertEquals($kidstranslation->get('id'),
                $translator->get_best_translation('de_kids', $generatedhash, $foundhash, 'untranslated text')->get('id'));

        $kidstranslationmatchonfound = new translation(0, (object) [
                'targetlanguage'    => 'de_kids',
                'lastgeneratedhash' => $generatedhash,
                'md5key'            => $foundhash,
                'contextid'         => $contextid,
                'substitutetext'    => 'some text for kids'
        ]);
        $kidstranslationmatchonfound->save();

        $this->assertEquals($kidstranslationmatchonfound->get('id'),
                $translator->get_best_translation('de_kids', $generatedhash, $foundhash, 'untranslated text')->get('id'));
    }
}
