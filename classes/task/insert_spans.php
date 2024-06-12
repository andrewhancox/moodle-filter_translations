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

use context_course;
use context_module;
use context_system;
use filter_translations;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/filelib.php');

/**
 * Insert translation spans scheduled task.
 */
class insert_spans extends \core\task\scheduled_task {
    /** @var int the maximum length of time one instance of this task will run. */
    const TIME_LIMIT = 900;

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('insertspans', 'filter_translations');
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
            $filter = new filter_translations(context_system::instance(), []);

            if (time() >= $stoptime) {
                mtrace("This task has been running for more than " .
                        format_time(self::TIME_LIMIT) . ", so stopping this execution.");
                break;
            }

            mtrace("Started processing table: $table");
            $updated = false;
            foreach ($columns as $column) {
                // Blocks content need to be handled differently.
                if ($table == 'block_instances') {
                    // Only check/process configdata field.
                    if ($column == 'configdata') {
                        // Get all html blocks only.
                        foreach ($DB->get_records($table, ['blockname' => 'html']) as $row) {
                            // Extract the content text from the block config.
                            $blockinstance = block_instance('html', $row);
                            $blockcontent = $blockinstance->config->text;

                            // Skip if a translation span tag found.
                            if (strpos($blockcontent, 'data-translationhash') !== false) {
                                continue;
                            }

                            // Add the translation span tag.
                            $blockinstance->config->text .= '<span data-translationhash="' . md5(random_string(32)) . '"></span>';

                            // Encode and save block config data.
                            $row->configdata = base64_encode(serialize($blockinstance->config));
                            $DB->update_record($table, $row);

                            $updated = true;
                            mtrace('+', '');
                        }
                    }

                    continue; // Done with blocks.
                } else if (!isset($columnsbytable[$table]) || !in_array($column, $columnsbytable[$table])) {
                    $ex = new \moodle_exception('unknowncolumn', 'filter_translations');
                    mtrace_exception($ex);
                    $anyexception = $ex;
                    continue; // Skip this and move to the next column.
                }

                foreach ($DB->get_records_select($table, "$column IS NOT NULL AND $column <> ''") as $row) {
                    // Skip if a translation span tag found.
                    if (strpos($row->$column, 'data-translationhash') !== false) {
                        continue;
                    }

                    $row->$column .= '<span data-translationhash="' . md5(random_string(32)) . '"></span>';
                    $DB->update_record($table, $row);
                    $updated = true;
                    mtrace('+', '');
                }
            }

            if ($updated) {
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
