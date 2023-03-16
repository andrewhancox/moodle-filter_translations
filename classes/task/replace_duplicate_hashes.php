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

namespace filter_translations\task;

/**
 * Replace duplicate translation hashes scheduled task.
 */
class replace_duplicate_hashes extends \core\task\scheduled_task {
    /** @var int the maximum length of time one instance of this task will run. */
    const TIME_LIMIT = 900;

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('replaceduplicatehashes', 'filter_translations');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        $stoptime = time() + self::TIME_LIMIT; // Time to stop execution.

        // Get the columns JSON string.
        $json = get_config('filter_translations', 'columndefinition');

        if (empty($json)) {
            return; // Nothing to process.
        }

        $columnsbytabletoprocess = json_decode($json);

        if ($columnsbytabletoprocess === null) {
            mtrace(get_string('columndefinitionjsonerror', 'filter_translations'));
            throw new \moodle_exception('columndefinitionjsonerror', 'filter_translations');
        }

        // Get all tables/columns in DB.
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

        $anyexception = null;
        $transaction = $DB->start_delegated_transaction();
        foreach ($columnsbytabletoprocess as $table => $columns) {
            if (time() >= $stoptime) {
                mtrace("This task has been running for more than " .
                        format_time(self::TIME_LIMIT) . ", so stopping this execution.");
                break;
            }

            mtrace("Started processing table: $table");

            foreach ($columns as $column) {
                if (!isset($columnsbytable[$table]) || !in_array($column, $columnsbytable[$table])) {
                   // mtrace(get_string('unknowncolumn', 'filter_translations'));
                    $ex = new \moodle_exception('unknowncolumn', 'filter_translations');
                    mtrace_exception($ex);
                    $anyexception = $ex;
                    continue; // Skip this and move to the next column.
                }
                mtrace("Started processing column: $table -> $column");

                // TODO: Use cross-platform SQL, if possible.
                if ($DB->get_dbfamily() == 'postgres') {
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
                        "SUBSTRING({$column} from POSITION('<span data-translationhash=' in {$column}) for 69) AS hash " .
                        "FROM {{$table}} mp " .
                        "WHERE {$column} REGEXP '<span data-translationhash[ ]*=[ ]*[\'\"]+([a-zA-Z0-9]+)[\'\"]+[ ]*>[ ]*<\/span>' " .
                    ") AS Q1 " .
                    "GROUP BY hash " .
                    "HAVING count(*) > 1) AS Q2, " .
                    "( " .
                        "SELECT mp.id, " .
                        "SUBSTRING({$column} from POSITION('<span data-translationhash=' in {$column}) for 69) AS hash " .
                    "FROM {{$table}} mp " .
                    "WHERE {$column} REGEXP '<span data-translationhash[ ]*=[ ]*[\'\"]+([a-zA-Z0-9]+)[\'\"]+[ ]*>[ ]*<\/span>' " .
                    ") AS Q3 " .
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
                    if ($DB->get_dbfamily() == 'postgres') {
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

                        $DB->update_record($table, $record);

                        $updatedcount++;
                        mtrace('+', '');
                    } else {
                        $skippedcount++;
                        mtrace('-', '');
                        mtrace('id: ' . $row->id . ' hash: ' . $hash);
                    }
                }
                mtrace('');
                mtrace(" ++Updated: $updatedcount, --Skipped: $skippedcount");
                mtrace('');
            }

            mtrace("Finished processing table: $table");
            mtrace('');
        }
        $transaction->allow_commit();

        if ($anyexception) {
            // If there was any error, ensure the task fails.
            throw $anyexception;
        }
    }
}
