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

use context_system;
use core\event\base;
use filter_translations\translation;

abstract class translation_base extends base {

    protected function init() {
        $this->data['objecttable'] = 'filter_translations';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    protected function validate_data() {
        parent::validate_data();

        if (!isset($this->relateduserid)) {
            throw new \coding_exception('The \'relateduserid\' must be set.');
        }

        if (!isset($this->objectid)) {
            throw new \coding_exception('The \'objectid\' value must be set.');
        }
    }

    public static function trigger_from_translation(translation $translation, translation $previous = null) {
        $data = [
            'context' => context_system::instance(),
            'objectid' => $translation->get('id'),
            'relateduserid' => $translation->get('usermodified'),
            'other' => [
                'previous' => isset($previous) ? json_encode($previous->to_record()) : '',
                'translation' => json_encode($translation->to_record()),
            ],
        ];
        $event = self::create($data);

        $event->trigger();
    }
}
