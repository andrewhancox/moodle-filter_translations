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

class events_test extends \advanced_testcase {

    public function setUp(): void {
        parent::setUp();

        $this->resetAfterTest(true);
    }

    public function test_events() {
        $generatedhash = md5('generatedhash');

        $contextid = \context_system::instance()->id;

        $sink = $this->redirectEvents();

        $translation = new translation(0, (object) [
                'targetlanguage'    => 'de',
                'lastgeneratedhash' => $generatedhash,
                'md5key'            => $generatedhash,
                'contextid'         => $contextid,
                'substitutetext'    => 'some text',
        ]);
        $translation->save();

        $translation->set('substitutetext', 'changed text');
        $translation->save();

        $translation->delete();

        $events = $sink->get_events();
        $this->assertCount(3, $events);
    }
}
