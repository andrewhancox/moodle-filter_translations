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
 * Task schedule configuration for filter_translations.
 *
 * @package     filter_translations
 * @copyright   2023 Rajneel Totaram <rajneel.totaram@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$tasks = [
    [
        'classname' => 'filter_translations\task\replace_duplicate_hashes',
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*/6', // 4 times a day.
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => 1,
    ],
    [
        'classname' => 'filter_translations\task\copy_translations',
        'blocking' => 0,
        'minute' => '*',
        'hour' => '*/8', // 3 times a day.
        'day' => '*',
        'month' => '*',
        'dayofweek' => '*',
        'disabled' => 1,
    ],
    [
        'classname' => 'filter_translations\task\insert_spans',
        'blocking' => 0,
        'minute' => '5',
        'hour' => '4',
        'day' => '*',
        'month' => '*',
        'dayofweek' => '1,4',
        'disabled' => 1,
    ],
];
