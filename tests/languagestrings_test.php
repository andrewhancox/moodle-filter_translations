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
use filter_translations\translation_testable;
use filter_translations\translator_testable;

defined('MOODLE_INTERNAL') || die();

class languagestrings_test extends advanced_testcase {
    public function setUp() {
        parent::setUp();

        $this->resetAfterTest(true);
    }

    public function test_get_best_translation() {
        global $CFG;
        require_once("$CFG->dirroot/lib/tests/componentlib_test.php");

        set_config('languagestringreverse_enable', true, 'filter_translations');
        $CFG->langlocalroot = $CFG->dirroot . '/filter/translations/tests/fixtures/lang';

        $installer = new testable_lang_installer(array('de', 'de_kids'));
        $installer->run();

        $languagestrings = new \filter_translations\translationproviders\languagestringreverse();

        $text = 'Treat files as OK';
        $hash = md5($text);
        $translation = $languagestrings->createorupdate_translation(null, $hash, $text, 'de', null);

        $this->assertEquals('Dateien als OK behandeln', $translation->get('substitutetext'));
    }
}
