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
require_once($CFG->libdir . '/filelib.php');

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
    cli_writeln(get_string('clihelptext_copytranslations', 'filter_translations'));
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

    $languages = get_string_manager()->get_list_of_translations(); // Languages in used in the site.

    // Get all translations records.
    $alltranslations = $DB->get_records('filter_translations', null, '', 'id, md5key, lastgeneratedhash, targetlanguage');

    $copiedcount = 0;
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
                // Skip if no translation span tag found.
                if (strpos($row->$column, 'data-translationhash') === false) {
                    continue;
                }

                $formattedcolumn = '';

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

                if (empty($formattedcolumn)) {
                    // Generate hash of content.
                    $generatedhash = $filter->generatehash($row->$column);
                } else {
                    // Generate hash of content. Translation span tags are removed from $formattedcolumn.
                    $generatedhash = $filter->generatehash($formattedcolumn);
                }

                // Get all matching translations for this content.
                $foundhashtranslations = [];
                $generatedhashtranslations = [];

                $foundhashtranslations = filter_translations_findtranslations($alltranslations, $foundhash, 'md5key');
                $generatedhashtranslations = filter_translations_findtranslations($alltranslations, $generatedhash, 'lastgeneratedhash');

                // Copy over any translations not recorded under the found hash of this content.
                $shouldprint = true;
                foreach ($generatedhashtranslations as $tr) {
                    if (!isset($foundhashtranslations[$tr->targetlanguage]) && isset($languages[$tr->targetlanguage])) {
                        if ($shouldprint) {
                            cli_writeln("foundhash: $foundhash, content hash: $generatedhash");
                            $shouldprint = false;
                        }

                        // Get full translation record.
                        $record = $DB->get_record('filter_translations', ['id' => $tr->id]);

                        cli_writeln("  + copying translation from md5key: $record->md5key, lang: $record->targetlanguage");

                        if ($options['mode'] == 'process') {
                            $record->id = null; // Unset id.
                            $record->md5key = $foundhash; // Copy under this hash.

                            $DB->insert_record('filter_translations', $record);
                        }

                        $copiedcount++;
                    }
                }
            }
        }

        cli_writeln("Finished processing table: $table");
        cli_writeln('');
    }
    $transaction->allow_commit();
    cli_writeln("++ Copied translations: $copiedcount");
}

/**
 * Get a filtered list of translation records.
 *
 * @param array $alltranslations
 * @param string $hash value to look for
 * @param string $key key to search against
 * @return array filtered array
 */
function filter_translations_findtranslations(array $alltranslations, string $hash, string $key) {
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
