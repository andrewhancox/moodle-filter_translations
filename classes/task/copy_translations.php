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

defined('MOODLE_INTERNAL') || die();

use context_course;
use context_module;
use context_system;
use filter_translations;

global $CFG; // This class is included inside existing functions.

require_once($CFG->libdir . '/filelib.php');

/**
 * Copy translations scheduled task.
 */
class copy_translations extends \core\task\scheduled_task {
    /** @var int the maximum length of time one instance of this task will run. */
    const TIME_LIMIT = 900;

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('copytranslations', 'filter_translations');
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

        $languages = get_string_manager()->get_list_of_translations(); // Languages in used in the site.
        $anyexception = null;

        // Get all translations records.
        $alltranslations = $DB->get_records('filter_translations', null, '', 'id, md5key, lastgeneratedhash, targetlanguage');

        $transaction = $DB->start_delegated_transaction();
        foreach ($columnsbytabletoprocess as $table => $columns) {
            if (time() >= $stoptime) {
                mtrace("This task has been running for more than " .
                        format_time(self::TIME_LIMIT) . ", so stopping this execution.");
                break;
            }

            $filter = new filter_translations(context_system::instance(), []);

            mtrace("Started processing table: $table");

            foreach ($columns as $column) {
                // Blocks not supported yet.
                if ($table == 'block_instances') {
                    mtrace("-- Table $table cannot be processed. Skipping...");
                    continue;
                } else if (!isset($columnsbytable[$table]) || !in_array($column, $columnsbytable[$table])) {
                    $ex = new \moodle_exception('unknowncolumn', 'filter_translations');
                    mtrace_exception($ex);
                    $anyexception = $ex;
                    continue; // Skip this and move to the next column.
                }

                $rs = $DB->get_recordset_select($table, "$column IS NOT NULL AND $column <> ''");
                foreach ($rs as $row) {
                    // Skip if no translation span tag found.
                    if (strpos($row->$column, 'data-translationhash') === false) {
                        continue;
                    }

                    $formattedcolumn = '';
                    $context = null;

                    // Rendered content may be different.
                    // Get rendered version of content.
                    if ($column == 'intro') {
                        $cm = get_coursemodule_from_instance($table, $row->id, $row->course, false, MUST_EXIST);

                        $formattedcolumn = format_module_intro($table, $row, $cm->id);
                    } else if (strpos($row->$column, '@@PLUGINFILE@@') !== false) {
                        // Need to get actual URIs.
                        // Attempt to generate correct URIs for each plugin.
                        switch ($table) {
                            case 'course_sections':
                                $context = context_course::instance($row->course);

                                $formattedcolumn = file_rewrite_pluginfile_urls($row->summary, 'pluginfile.php', $context->id,
                                    'course', 'section', $row->id);
                            break;
                            case 'book_chapters':
                                $cm = get_coursemodule_from_instance('book', $row->bookid, 0, false, MUST_EXIST);
                                $context = context_module::instance($cm->id);

                                $formattedcolumn = file_rewrite_pluginfile_urls($row->content, 'pluginfile.php', $context->id,
                                    'mod_book', 'chapter', $row->id);
                            break;
                            case 'page':
                                $cm = get_coursemodule_from_instance($table, $row->id, $row->course, false, MUST_EXIST);
                                $context = context_module::instance($cm->id);

                                $formattedcolumn = file_rewrite_pluginfile_urls($row->content, 'pluginfile.php', $context->id,
                                    'mod_page', 'content', $row->revision);
                            break;
                            // TODO: Add support for other content/resource types.
                            default:
                            break;
                        }
                    }

                    // Extract translation hash from content.
                    $foundhash = $filter->findandremovehash($row->$column);

                    // Double check before continuing.
                    if (empty($foundhash)) {
                        continue;
                    }

                    if (empty($formattedcolumn)) { // Use the "raw" column value.
                        // Generate hash of content.
                        $generatedhash = $filter->generatehash($row->$column);
                    } else {
                        // Generate hash of content. Translation span tags are removed from $formattedcolumn.
                        $generatedhash = $filter->generatehash($formattedcolumn);
                    }

                    // Get all matching translations for this content.
                    $foundhashtranslations = [];
                    $generatedhashtranslations = [];

                    $foundhashtranslations = $this->findtranslations($alltranslations, $foundhash, 'md5key');
                    $generatedhashtranslations = $this->findtranslations($alltranslations, $generatedhash, 'lastgeneratedhash');

                    // Copy over any translations not recorded under the found hash of this content.
                    $shouldprint = true;
                    foreach ($generatedhashtranslations as $tr) {
                        if (!isset($foundhashtranslations[$tr->targetlanguage]) && isset($languages[$tr->targetlanguage])) {
                            if ($shouldprint) {
                                mtrace("foundhash: $foundhash, content hash: $generatedhash");

                                if (empty($context) && !empty($row->course) && $row->course > 1) {
                                    // TODO: Use correct context ???
                                    $context = context_course::instance($row->course); // Use course context.
                                }

                                $shouldprint = false;
                            }

                            // Get full translation record.
                            $record = $DB->get_record('filter_translations', ['id' => $tr->id]);
                            mtrace("  + copying translation from md5key: $record->md5key, lang: $record->targetlanguage");

                            $record->id = null; // Unset id.
                            $record->md5key = $foundhash; // Copy under this hash.

                            if ($context !== null) {
                                $record->contextid = $context->id; // Contextid of new content.
                            }

                            $DB->insert_record('filter_translations', $record);
                        }
                    }
                }
                $rs->close();
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

    /**
     * Get a filtered list of translation records.
     *
     * @param array $alltranslations
     * @param string $hash value to look for
     * @param string $key key to search against
     * @return array filtered array
     */
    private function findtranslations(array $alltranslations, string $hash, string $key) {
        $matchedrecords = [];

        foreach ($alltranslations as $tr) {
            if ($tr->$key == $hash) {
                if (!isset($matchedrecords[$tr->targetlanguage])) {
                    $matchedrecords[$tr->targetlanguage] = $tr;
                }
            }
        }

        return $matchedrecords;
    }
}
