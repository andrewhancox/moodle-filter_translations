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

use context_system;
use filter_translations;

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
                $updatedcount = 0;

                // Blocks are handled differently.
                if ($table == 'block_instances') {
                    if ($column == 'configdata') {
                        mtrace("Started processing column: $table -> $column");
                        $filter = new filter_translations(context_system::instance(), []);
                        $translationhashes = [];

                        // Get all html blocks only.
                        foreach ($DB->get_records($table, ['blockname' => 'html'], 'id ASC') as $row) {
                            // Extract the content text from the block config.
                            $blockinstance = block_instance('html', $row);
                            $blockcontent = $blockinstance->config->text;

                            // Skip if no translation span tag found.
                            if (strpos($blockcontent, 'data-translationhash') === false) {
                                continue;
                            }

                            // Extract translation hash from content.
                            $foundhash = $filter->findandremovehash($blockcontent);

                            if (!isset($translationhashes[$foundhash])) {
                                // This is a unique hash.
                                // Add this hash to the array of used hashes.
                                $translationhashes[$foundhash] = $foundhash;
                                continue; // Move to the next record.
                            }

                            mtrace('+ Replacing hash for id: ' . $row->id . ' hash: ' . $foundhash);
                            $newhash = md5(random_string(32)); // Generate a new hash.

                            // Add the new translation span tag.
                            $blockinstance->config->text = $blockcontent . '<span data-translationhash="' . $newhash . '"></span>';
                            $translationhashes[$newhash] = $newhash; // Add to array of used hashes.

                            // Encode and save block config data.
                            $row->configdata = base64_encode(serialize($blockinstance->config));
                            $DB->update_record($table, $row);
                            $updatedcount++;
                        }
                    }

                    mtrace('');
                    mtrace("  ++Updated: $updatedcount");

                    continue;
                } else if (!isset($columnsbytable[$table]) || !in_array($column, $columnsbytable[$table])) {
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
                    // "AND Q2.hash <> '' " .
                    "ORDER BY Q3.hash ASC, Q3.id ASC";
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
                    "ORDER BY Q3.hash ASC, Q3.id ASC";
                }

                $lasthash = '';

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
                        $lasthash = $hash;
                        continue; // Keep the first record for this hash, so skip.
                    }

                    // Generate new hash for all other duplicates.
                    mtrace('+ Replacing hash for id: ' . $row->id . ' hash: ' . $hash);

                    // Get the full record from DB.
                    $record = $DB->get_record($table, ['id' => $row->id]);

                    // Remove the old span tag from the text.
                    $text = preg_replace('/<span data-translationhash[ ]*=[ ]*[\'"]+([a-zA-Z0-9]+)[\'"]+[ ]*>[ ]*<\/span>/', '',
                                $record->$column);

                    // Generate and append new translation hash.
                    $record->$column = $text . '<span data-translationhash="' . md5(random_string(32)) . '"></span>';

                    $DB->update_record($table, $record);

                    $updatedcount++;
                }
                mtrace('');
                mtrace("  ++Updated: $updatedcount");
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
