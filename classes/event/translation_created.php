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

namespace filter_translations\event;

use core\event\base;
use filter_translations\translation;
use moodle_url;

class translation_created extends translation_base {

    protected function init() {
        parent::init();
        $this->data['crud'] = 'c';
    }

    public static function get_name() {
        return get_string('translationcreated', 'filter_translations');
    }

    public function get_description() {
        return "The user with id '$this->userid' created the translation with id '$this->objectid'.";
    }

    public function get_url() {
        return new moodle_url('/mod/assign/edittranslation.php', array('id' => $this->objectid));
    }
}
