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

use filter_translations\filter_translations_testable;

defined('MOODLE_INTERNAL') || die();

class filter_test extends advanced_testcase {

    public function setUp() {
        global $CFG;

        parent::setUp();

        $this->resetAfterTest(true);

        require_once("$CFG->dirroot/filter/translations/tests/fixtures/filter_translations_testable.php");

        filter_set_global_state('translations', TEXTFILTER_ON);
    }

    public function test_findandremovehash() {
        $filter = new filter_translations_testable(context_system::instance(), []);

        $text = 'No hash in here';
        $this->assertNull($filter->findandremovehash($text));

        $text = '<span data-translationhash="thehash"></span>Some text';
        $this->assertEquals('thehash', $filter->findandremovehash($text));
        $this->assertEquals('Some text', $text);

        $text = '<span data-translationhash="thenexthash"></span>Some more text<span data-translationhash="thenexthash"></span>';
        $this->assertEquals('thenexthash', $filter->findandremovehash($text));
        $this->assertEquals('Some more text', $text);

        $text = '<span data-translationhash="thenexthash"></span>Some more text with <span>spans</span> in it<span data-translationhash="thenexthash"></span>';
        $this->assertEquals('thenexthash', $filter->findandremovehash($text));
        $this->assertEquals('Some more text with <span>spans</span> in it', $text);
    }
}
