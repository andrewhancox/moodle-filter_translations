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

function xmldb_filter_translations_upgrade($oldversion) {

    global $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2021110908) {
        $table = new xmldb_table('filter_translations');
        $field = new xmldb_field('rawtext', XMLDB_TYPE_TEXT, null, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        upgrade_plugin_savepoint(true, 2021110908, 'filter', 'translations');
    }

    if ($oldversion < 2022012400) {
        $table = new xmldb_table('filter_translations');
        $field = new xmldb_field('translationsource', XMLDB_TYPE_INTEGER, 10, null, null, null, null);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            $DB->execute("UPDATE {filter_translations} SET translationsource = :manual", ['manual' => translation::SOURCE_MANUAL]);
        }

        upgrade_plugin_savepoint(true, 2022012400, 'filter', 'translations');
    }

    if ($oldversion < 2022022312) {
        $table = new xmldb_table('filter_translation_issues');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE);
        $table->add_field('issue', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('url', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL);
        $table->add_field('md5key', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);
        $table->add_field('targetlanguage', XMLDB_TYPE_CHAR, '30', null, XMLDB_NOTNULL);
        $table->add_field('contextid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('translationid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('rawtext', XMLDB_TYPE_TEXT);
        $table->add_field('timecreated', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('timemodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('usermodified', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL);
        $table->add_field('generatedhash', XMLDB_TYPE_CHAR, '32', null, XMLDB_NOTNULL);

        $table->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));

        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

        upgrade_plugin_savepoint(true, 2022022312, 'filter', 'translations');
    }

    if ($oldversion < 2022022319) {
        $table = new xmldb_table('filter_translation_issues');
        $field = new xmldb_field('url', XMLDB_TYPE_TEXT);
        $dbman->change_field_type($table, $field);

        upgrade_plugin_savepoint(true, 2022022319, 'filter', 'translations');
    }

    if ($oldversion < 2022042709) {

        // Define index targetlanguage_md5key (not unique) to be dropped from filter_translation_issues.
        $table = new xmldb_table('filter_translation_issues');
        $index = new xmldb_index('targetlanguage_md5key', XMLDB_INDEX_NOTUNIQUE, ['targetlanguage', 'md5key']);

        // Conditionally launch drop index targetlanguage_md5key.
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index targetlanguage_issue (not unique) to be added to filter_translation_issues.
        $index = new xmldb_index('targetlanguage_issue', XMLDB_INDEX_NOTUNIQUE, ['targetlanguage', 'issue']);

        // Conditionally launch add index targetlanguage_issue.
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Translations savepoint reached.
        upgrade_plugin_savepoint(true, 2022042709, 'filter', 'translations');
    }

    if ($oldversion < 2022042711) {
        // Context id cannot be 0.
        // Update context id to 1.
        $DB->execute("UPDATE {filter_translations} SET contextid = :contextid WHERE contextid=0",
            ['contextid' => \context_system::instance()->id]);
        $DB->execute("UPDATE {filter_translation_issues} SET contextid = :contextid WHERE contextid=0",
            ['contextid' => \context_system::instance()->id]);

        // Translations savepoint reached.
        upgrade_plugin_savepoint(true, 2022042711, 'filter', 'translations');
    }

    return true;
}
