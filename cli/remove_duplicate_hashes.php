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
 * @package     filter_translations
 * @copyright   2023 Rajneel Totaram <rajneel.totaram@moodle.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
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
    cli_writeln(get_string('clihelptext_removeduplicatehashes', 'filter_translations'));
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
} else if ($options['mode'] == 'process' || $options['mode'] == 'dryrun') {
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
        cli_writeln("Started processing table: $table");

        foreach ($columns as $column) {
            if (!isset($columnsbytable[$table]) || !in_array($column, $columnsbytable[$table])) {
                cli_writeln('Unknown column or table.');
                die();
            }
            cli_writeln("Started processing column: $table -> $column");

            // TODO: Use cross-platform SQL, if possible.
            // 'pgsql', 'mariadb', 'mysqli', 'auroramysql', 'sqlsrv' or 'oci'
            if ($CFG->dbtype == 'pgsql') {
                $sql = "SELECT Q3.* FROM (" .
                    "SELECT hash, COUNT(*) " .
                    "FROM ( " .
                    "SELECT mp.id, " .
                    "REGEXP_MATCHES({$column}, '<span data-translationhash[ ]*=[ ]*[''\"]+([a-zA-Z0-9]+)[''\"]+[ ]*>[ ]*<\/span>') AS hash " .
                    "FROM {{$table}} mp " .
                ") AS Q1 " .
                "GROUP BY hash " .
                "HAVING count(*) > 1) AS Q2, " .
                "( " .
                    "SELECT mp.id, " .
                    "REGEXP_MATCHES({$column}, '<span data-translationhash[ ]*=[ ]*[''\"]+([a-zA-Z0-9]+)[''\"]+[ ]*>[ ]*<\/span>') AS hash " .
                "FROM {{$table}} mp) AS Q3 " .
                "WHERE Q2.hash = Q3.hash " .
                //"AND Q2.hash <> '' " .
                "ORDER BY Q3.hash";
            } else {
                $sql = "SELECT Q3.* FROM (" .
                    "SELECT hash, COUNT(*) " .
                    "FROM ( " .
                    "SELECT mp.id, " .
                    "REGEXP_SUBSTR({$column}, '<span data-translationhash[ ]*=[ ]*[\'\"]+([a-zA-Z0-9]+)[\'\"]+[ ]*>[ ]*<\/span>') AS hash " .
                    "FROM {{$table}} mp " .
                ") AS Q1 " .
                "GROUP BY hash " .
                "HAVING count(*) > 1) AS Q2, " .
                "( " .
                    "SELECT mp.id, " .
                    "REGEXP_SUBSTR({$column}, '<span data-translationhash[ ]*=[ ]*[\'\"]+([a-zA-Z0-9]+)[\'\"]+[ ]*>[ ]*<\/span>') AS hash " .
                "FROM {{$table}} mp) AS Q3 " .
                "WHERE Q2.hash = Q3.hash " .
                "AND Q2.hash <> '' " .
                "ORDER BY Q3.hash";
            }

            $lasthash = '';
            $hastranslations = false;
            $skippedcount = 0;
            $updatedcount = 0;

            foreach ($DB->get_records_sql($sql) as $row) {
                if (empty($row->hash)) {
                    continue; // Hash cannot be empty.
                }
                // Extract hash only.
                if ($CFG->dbtype == 'pgsql') {
                    // Hash is enclosed in {HASH_VALUE_32_CHARS}.
                    $hash = substr($row->hash, 1, 32);
                } else {
                    // <span data-translationhash="HASH_VALUE_32_CHARS"></span>.
                    $translationhashes = [];
                    preg_match('/<span data-translationhash[ ]*=[ ]*[\'"]+([a-zA-Z0-9]+)[\'"]+[ ]*>[ ]*<\/span>/', $row->hash,
                        $translationhashes);
                    $hash = $translationhashes[1];
                }

                if ($lasthash != $hash) {
                    // This is a different hash, so check if any translation record exists for this hash.
                    if ($DB->count_records('filter_translations', ['md5key' => $hash]) > 0) {
                        $hastranslations = true;
                    } else {
                        $hastranslations = false;
                    }

                    $lasthash = $hash;
                }

                // No translations exists, so generate new hash.
                if (!$hastranslations) {
                    // Get the full record from DB.
                    $record = $DB->get_record($table, ['id' => $row->id]);

                    // Remove the old span tag from the text.
                    $text = preg_replace('/<span data-translationhash[ ]*=[ ]*[\'"]+([a-zA-Z0-9]+)[\'"]+[ ]*>[ ]*<\/span>/', '',
                        $record->$column);

                    // Generate and append new translation hash.
                    $record->$column = $text . '<span data-translationhash="' . md5(random_string(32)) . '"></span>';

                    if ($options['mode'] == 'process') {
                        $DB->update_record($table, $record);
                    }

                    $updatedcount++;
                    cli_write('+');
                } else {
                    $skippedcount++;
                    cli_write('-');
                    cli_writeln('id: ' . $row->id . ' hash: ' . $hash);
                }
            }
            cli_writeln('');
            cli_writeln(" ++Updated: $updatedcount, --Skipped: $skippedcount");
            cli_writeln('');
        }

        cli_writeln("Finished processing table: $table");
        cli_writeln('');
    }
    $transaction->allow_commit();
}
