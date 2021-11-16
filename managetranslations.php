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

use filter_translations\managetranslations_table;

require_once(dirname(__FILE__) . '/../../config.php');

$context = context_system::instance();
$PAGE->set_context($context);

$title = get_string('managetranslations', 'filter_translations');
$PAGE->set_title($title);
$PAGE->set_heading($title);
$PAGE->set_url(new moodle_url('/filter/translations/managetranslations.php'));

echo $OUTPUT->header();

$table = new managetranslations_table(null, 'translationsname');
$table->define_baseurl('');
$table->out(100, true);

echo $OUTPUT->single_button(new moodle_url('/filter/translations/edittranslation.php'), get_string('createtranslation', 'filter_translations'));

echo $OUTPUT->footer();
