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

define('CLI_SCRIPT', true);

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/clilib.php');

// Define the input options.
$longparams = [
    'mode' => '',
    'file' => '',
];

$shortparams = [
    'm' => 'mode',
    'f' => 'file'
];

// Now get cli options.
list($options, $unrecognized) = cli_get_params($longparams, $shortparams);

if ($unrecognized) {
    $unrecognized = implode("\n  ", $unrecognized);
    cli_error(get_string('cliunknowoption', 'admin', $unrecognized));
}

if (empty($options['mode'])) {
    cli_writeln(get_string('clihelptext_insertspans', 'filter_translations'));
    die();
}

$columnsbytable = [];

foreach ($DB->get_tables(false) as $table) {
    $columnnames = [];

    foreach ($DB->get_columns($table) as $column) {
        $columnnames[] = $column->name;
    }
    foreach ($columnnames as $column) {
        if (!in_array($column . 'format', $columnnames)) {
            continue;
        }

        if (empty($columnsbytable[$table])) {
            $columnsbytable[$table] = [];
        }
        $columnsbytable[$table][] = $column;
    }
}

if ($options['mode'] == 'listcolumns') {
    cli_writeln(json_encode($columnsbytable, JSON_PRETTY_PRINT));
    die();
} else if ($options['mode'] == 'process') {
    if (empty($options['file'])) {
        cli_writeln(get_string('columndefinitionfileerror', 'filter_translations'));
        die();
    }

    try {
        $file = file_get_contents($options['file']);
        $columnsbytabletoprocess = json_decode($file);
    } catch (Exception $ex) {
        cli_writeln(get_string('columndefinitionfileerror', 'filter_translations'));
        die();
    }

    $transaction = $DB->start_delegated_transaction();
    foreach ($columnsbytabletoprocess as $table => $columns) {
        $filter = new filter_translations(context_system::instance(), []);

        cli_writeln("Started processing table: $table");

        foreach ($columns as $column) {
            if (!isset($columnsbytable[$table]) || !in_array($column, $columnsbytable[$table])) {
                cli_writeln('Unknown column or table.');
                die();
            }

            foreach ($DB->get_records_select($table, "$column IS NOT NULL AND $column <> ''") as $row) {
                if (strpos($row->$column, 'data-translationhash') !== false) {
                    cli_write('X');
                } else {
                    $row->$column .= '<span data-translationhash="' . $filter->generatehash($row->$column) . '"></span>';
                    $DB->update_record($table, $row);
                    cli_write('.');
                }
            }
        }

        cli_writeln('');
        cli_writeln("Finished processing table: $table");
    }
    $transaction->allow_commit();
}
