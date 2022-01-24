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

define('CLI_SCRIPT', true);

require(__DIR__.'/../../../config.php');
require_once($CFG->libdir.'/clilib.php');

// Define the input options.
$longparams = [
    'confirm' => '',
];

$shortparams = [
    'c' => 'confirm',
];

// now get cli options
list($options, $unrecognized) = cli_get_params($longparams, $shortparams);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if (empty($options['confirm'])) {
    cli_writeln(get_string('clihelptext', 'filter_translations'));
    die();
}

$systemcontext = context_system::instance();

$sql = "SELECT lang, sourcetext, translation, textformat FROM {filter_fulltranslate}";

raise_memory_limit(MEMORY_HUGE);
$rs = $DB->get_recordset('filter_fulltranslate', [], '',
    'lang, sourcetext, textformat, translation, automatic');

foreach ($rs as $record) {
    $hash = md5($record->sourcetext);
    $textformat = $record->textformat == 'html' ? FORMAT_HTML : FORMAT_PLAIN;
    $translationsource = $record->automatic ? translation::SOURCE_AUTOMATIC : translation::SOURCE_MANUAL;

    $translation = new translation();
    $translation->set('md5key', $hash);
    $translation->set('lastgeneratedhash', $hash);
    $translation->set('targetlanguage', $record->lang);
    $translation->set('contextid', $systemcontext->id);
    $translation->set('rawtext', $record->sourcetext);
    $translation->set('substitutetext', $record->translation);
    $translation->set('substitutetextformat', $textformat);
    $translation->set('translationsource', $translationsource);
    $translation->save();
}
