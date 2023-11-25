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
 * Import translations
 *
 * @package    filter_translations
 * @copyright  2023 Rajneel Totaram <rajneel.totaram@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use filter_translations\translation;

define('REASON_LANGNOTFOUND', 1); // Language not found on the site.
define('REASON_RECORDEXISTS', 2); // Transaltion record already exsits.
define('REASON_MISSINGCSVDATA', 3); // Some transaltion data is missing.

require('../../config.php');
require_once($CFG->libdir . '/csvlib.class.php');

require_login();

$context = context_system::instance();

require_capability('filter/translations:bulkimporttranslations', $context);

$url = new moodle_url('/filter/translations/import.php', []);
$PAGE->set_url($url);
$PAGE->set_context($context);

$title = get_string('importtranslations', 'filter_translations');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$form = new \filter_translations\form\import_form();

if ($form->is_cancelled()) {
    redirect($CFG->wwwroot);
} if ($data = $form->get_data()) {

    $filecontents = $form->get_file_content('file');

    $importid = csv_import_reader::get_new_iid('translationimport');
    $csvimport = new csv_import_reader($importid, 'translationimport');

    $delimiter = 'comma';
    $encoding = 'UTF-8';

    $readcount = $csvimport->load_csv_content($filecontents, $encoding, $delimiter);

    if ($readcount === false) {
        throw new \moodle_exception('csvfileerror', 'error', $PAGE->url, $csvimport->get_error());
    } else if ($readcount == 0) {
        throw new \moodle_exception('csvemptyfile', 'error', $PAGE->url, $csvimport->get_error());
    } else if ($readcount == 1) {
        throw new \moodle_exception('csvnodata', 'error', $PAGE->url);
    }

    $csvimport->init();

    unset($filecontents); // Not needed anymore.

    // These are the fields, and they always should be in the same order (for simplicity).
    $requiredfields = ['md5key', 'rawtext', 'substitutetext', 'targetlanguage', 'contextid', ];

    // Using get_columns() ensures the Byte Order Mark is removed.
    $header = $csvimport->get_columns();

    // Check that the fields in the CSV file are in the expected/required order.
    // Same number of fields and always in the same order.
    if (count($header) != count($requiredfields)) {
        throw new \moodle_exception('fieldsmismatch', 'filter_translations', $PAGE->url);
    }

    foreach ($header as $i => $h) {
        if (!in_array($h, $requiredfields)) {
            throw new \moodle_exception('fieldrequired', 'filter_translations', $PAGE->url, $h);
        }

        if ($h != $requiredfields[$i]) {
            throw new \moodle_exception('fieldwrongorder', 'filter_translations', $PAGE->url, $h);
        }
    }

    $filter = new filter_translations($context, []);

    // Get list of languages on the site.
    $listoftranslations = get_string_manager()->get_list_of_translations(true);

    $skipped = [];
    $linenum = 2; // Since header is line 1.

    while ($line = $csvimport->next()) {
        $md5key = trim($line[0]);
        $rawtext = trim($line[1]);
        $substitutetext = trim($line[2]);
        $targetlanguage = trim($line[3]);
        $contextid = trim($line[4]);

        // Skip if any field is empty.
        if(empty($md5key) || empty($rawtext) || empty($substitutetext) || empty($targetlanguage) || empty($contextid)) {
            $row = new stdClass();
            $row->linenum = $linenum;
            $row->md5key = $md5key;
            $row->targetlanguage = $targetlanguage;
            $row->reason = get_string('reasonimportskipped' . REASON_MISSINGCSVDATA, 'filter_translations');

            $skipped[$linenum] = $row;

            $linenum++; // Increment line number before skipping.
            continue;
        }

        // Skip line if language is not installed.
        if (!isset($listoftranslations[$targetlanguage])) {
            $row = new stdClass();
            $row->linenum = $linenum;
            $row->md5key = $md5key;
            $row->targetlanguage = $targetlanguage;
            $row->reason = get_string('reasonimportskipped' . REASON_LANGNOTFOUND, 'filter_translations');

            $skipped[$linenum] = $row;

            $linenum++; // Increment line number before skipping.
            continue;
        }

        // Ready to import into the database.
        // You can only import new/missing translations.
        // Existing translations cannot be overwritten. Atleast not if overwrite is allowed.

        // Check records from filter_translations matching md5key and targetlanguage.
        $count = $DB->count_records('filter_translations', ['md5key' => $md5key, 'targetlanguage' => $targetlanguage]);

        if ($count == 0) {
            // Nothing found, good to import.
            $persistent = new translation();
            $persistent->set('md5key', trim($line[0]));
            $persistent->set('rawtext', trim($line[1]));
            $persistent->set('substitutetext', trim($line[2]));
            $persistent->set('targetlanguage', trim($line[3]));
            $persistent->set('contextid', trim($line[4]));

            $persistent->set('lastgeneratedhash', $filter->generatehash($persistent->get('rawtext')));
            $persistent->set('substitutetextformat', 1); // TODO: Set correct format.

            $persistent->create();
        } else {
            // If a record exists, skip this one, Do not update.
            $row = new stdClass();
            $row->linenum = $linenum;
            $row->md5key = $md5key;
            $row->targetlanguage = $targetlanguage;
            $row->reason = get_string('reasonimportskipped' . REASON_RECORDEXISTS, 'filter_translations');

            $skipped[$linenum] = $row;
        }

        $linenum++;
    }

    $csvimport->close();

    // Show the import summary.
    echo $OUTPUT->header();

    $processedcount = $linenum - 2;
    $skippedcount = count($skipped);

    $returnurl = new moodle_url('/course/view.php', ['id' => $COURSE->id]); // TODO: Link to the correct course.

    $data = (object) [
        'processedcount' => $processedcount,
        'skippedcount' => $skippedcount,
        'skipped' => array_values($skipped),
        'continueurl' => $returnurl,
    ];

    echo $OUTPUT->render_from_template('filter_translations/import_summary', $data);

    echo $OUTPUT->footer();

    exit;
}

echo $OUTPUT->header();

$form->display();

echo $OUTPUT->footer();
