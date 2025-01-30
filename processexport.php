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
 * Export translations in CSV format.
 *
 * @package    filter_translations
 * @copyright  2023 Rajneel Totaram <rajneel.totaram@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use filter_translations\translation;

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/csvlib.class.php');

$courseid = required_param('course', PARAM_INT);
$targetlanguage = optional_param('targetlanguage', current_language(), PARAM_TEXT);

$context = context_system::instance();

require_login();

$PAGE->set_context($context);

require_capability('filter/translations:exporttranslations', $context);

$form = new \filter_translations\form\exporttranslations_form(new moodle_url('/filter/translations/processexport.php'));
if ($form->is_cancelled()) {
    if ($courseid > SITEID) {
        redirect(new moodle_url('/course/view.php', ['id' => $courseid]));
    }
} else if ($fromform = $form->get_data()) {
    $courseid = $fromform->course;
    $targetlanguage = $fromform->targetlanguage;
} else {
    // Doesn't look like the form was submitted.
    redirect($CFG->wwwroot);
}

if ($courseid <= SITEID) {
    // Cannot export site-level details.
    redirect($CFG->wwwroot);
}

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

$exportdata = [];
$filter = new filter_translations(context_system::instance(), []);

// Get all translations for this language so that we don't have to query the DB every time.
$alltranslations = $DB->get_records('filter_translations',
    ['targetlanguage' => $targetlanguage],
    '',
    'md5key,lastgeneratedhash'
);

// Course name.
$name = trim($course->fullname);
$generatedhash = $filter->generatehash($name);
// Check for existing translations.
//$count = $DB->count_records('filter_translations', ['md5key' => $generatedhash, 'targetlanguage' => $targetlanguage]);

if (!array_key_exists($generatedhash, $alltranslations)) {
    $exportdata[] = [$generatedhash, $name, '', $targetlanguage, $coursecontext->id];
}

// Course summary.
$text = trim($course->summary);
$foundhash = $filter->findandremovehash($text);
$generatedhash = $filter->generatehash($text);

// Check if a translation exists.
//$count = $DB->count_records('filter_translations', ['md5key' => $foundhash ?? $generatedhash, 'targetlanguage' => $targetlanguage]);

if (!array_key_exists($foundhash ?? $generatedhash, $alltranslations)) {
    $exportdata[] = [$foundhash ?? $generatedhash, $text, '', $targetlanguage, $coursecontext->id];
}

$modinfo = get_fast_modinfo($course);

$sections = $modinfo->get_section_info_all();

foreach ($sections as $section) {
    if ($section->uservisible) {
        // Section name.
        if (!empty($section->name)) {
            $name = trim($section->name);
            $generatedhash = $filter->generatehash($name);

            // Check if a translation exists.
            //$count = $DB->count_records('filter_translations', ['md5key' => $generatedhash, 'targetlanguage' => $targetlanguage]);

            if (!array_key_exists($generatedhash, $alltranslations)) {
                $exportdata[] = [$generatedhash, $name, '', $targetlanguage, $coursecontext->id];
            }
        }

        // Section summary.
        if (!empty($section->summary)) {
            $text = file_rewrite_pluginfile_urls(trim($section->summary), 'pluginfile.php', $coursecontext->id,
                        'course', 'section', $section->id);

            $foundhash = $filter->findandremovehash($text);
            $generatedhash = $filter->generatehash($text);

            // Check if a translation exists.
            // $count = $DB->count_records('filter_translations',
            //             ['md5key' => $foundhash ?? $generatedhash, 'targetlanguage' => $targetlanguage]);

            if (!array_key_exists($foundhash ?? $generatedhash, $alltranslations)) {
                $exportdata[] = [$foundhash ?? $generatedhash, $text, '', $targetlanguage, $coursecontext->id];
            }
        }
    }
}

foreach ($modinfo->cms as $cm) {
   // mtrace("mod: {$cm->modname}, instanceid: {$cm->instance}, cmid: {$cm->id}, context: {$cm->context->id}, visible: {$cm->uservisible}");

    // Only export visible activities.
    if ($cm->uservisible) {
        // Activity name.
        $name = trim($cm->name);
        $generatedhash = $filter->generatehash($name);

        // Check if a translation exists.
        //$count = $DB->count_records('filter_translations', ['md5key' => $generatedhash, 'targetlanguage' => $targetlanguage]);

        if (!array_key_exists($generatedhash, $alltranslations)) {
            $exportdata[] = [$generatedhash, $name,  '', $targetlanguage, $cm->context->id];
        }

        // Get the activity record from the database.
        $activity = $DB->get_record($cm->modname, ['id' => $cm->instance]);

        // Activity intro.
        if (!empty($cm->content)) {
            $text = format_module_intro($cm->modname, $activity, $cm->id);
            $foundhash = $filter->findandremovehash($text);
            $generatedhash = $filter->generatehash($text);

            // Check if a translation exists.
            //$count = $DB->count_records('filter_translations',
            //            ['md5key' => $foundhash ?? $generatedhash, 'targetlanguage' => $targetlanguage]);

            if (!array_key_exists($foundhash ?? $generatedhash, $alltranslations)) {
                $exportdata[] = [$foundhash ?? $generatedhash, $text, '', $targetlanguage, $cm->context->id];
            }
        }

        switch ($cm->modname) {
            case 'page':
                $text = file_rewrite_pluginfile_urls(trim($activity->content), 'pluginfile.php', $cm->context->id,
                            'mod_page', 'content', $activity->revision);

                $foundhash = $filter->findandremovehash($text);
                $generatedhash = $filter->generatehash($text);

                // Check if a translation exists.
                //$count = $DB->count_records('filter_translations',
                //            ['md5key' => $foundhash ?? $generatedhash, 'targetlanguage' => $targetlanguage]);

                if (!array_key_exists($foundhash ?? $generatedhash, $alltranslations)) {
                    $exportdata[] = [$foundhash ?? $generatedhash, $text, '', $targetlanguage, $cm->context->id];
                }

            break;
            case 'book':
                // Get the book chapters.
                $rs = $DB->get_recordset('book_chapters', ['bookid' => $cm->instance]);

                foreach ($rs as $bookchapter) {
                    // Chapter title.
                    $name = trim($bookchapter->title);
                    $generatedhash = $filter->generatehash($name);

                    // Check if a translation exists.
                    //$count = $DB->count_records('filter_translations',
                    //            ['md5key' => $generatedhash, 'targetlanguage' => $targetlanguage]);

                    if (!array_key_exists($generatedhash, $alltranslations)) {
                        $exportdata[] = [$generatedhash, $name, '', $targetlanguage, $cm->context->id];
                    }

                    // Content.
                    $text = file_rewrite_pluginfile_urls(trim($bookchapter->content), 'pluginfile.php', $cm->context->id,
                                'mod_book', 'chapter', $bookchapter->id);

                    $foundhash = $filter->findandremovehash($text);
                    $generatedhash = $filter->generatehash($text);

                    // Check if a translation exists.
                    //$count = $DB->count_records('filter_translations',
                    //            ['md5key' => $foundhash ?? $generatedhash, 'targetlanguage' => $targetlanguage]);

                    if (!array_key_exists($foundhash ?? $generatedhash, $alltranslations)) {
                        $exportdata[] = [$foundhash ?? $generatedhash, $text, '', $targetlanguage, $cm->context->id];
                    }
                }

                $rs->close();
            break;
            case 'lesson':
                // Get the lesson pages.
                $rs = $DB->get_recordset('lesson_pages', ['lessonid' => $cm->instance]);

                foreach ($rs as $lessonpage) {
                    // Page title.
                    $name = trim($lessonpage->title);
                    $generatedhash = $filter->generatehash($name);

                    // Check if a translation exists.
                    //$count = $DB->count_records('filter_translations',
                    //            ['md5key' => $generatedhash, 'targetlanguage' => $targetlanguage]);

                    if (!array_key_exists($generatedhash, $alltranslations)) {
                        $exportdata[] = [$generatedhash, $name, '', $targetlanguage, $cm->context->id];
                    }

                    // Content.
                    $text = file_rewrite_pluginfile_urls(trim($lessonpage->contents), 'pluginfile.php', $cm->context->id,
                                'mod_lesson', 'page_contents', $lessonpage->id);

                    $foundhash = $filter->findandremovehash($text);
                    $generatedhash = $filter->generatehash($text);

                    // Check if a translation exists.
                    //$count = $DB->count_records('filter_translations',
                    //            ['md5key' => $foundhash ?? $generatedhash, 'targetlanguage' => $targetlanguage]);

                    if (!array_key_exists($foundhash ?? $generatedhash, $alltranslations)) {
                        $exportdata[] = [$foundhash ?? $generatedhash, $text, '', $targetlanguage, $cm->context->id];
                    }
                }

                $rs->close();

                // Get the lesson answers and responses.
                $rs = $DB->get_recordset('lesson_answers', ['lessonid' => $cm->instance]);

                foreach ($rs as $lessonpage) {
                    // Answer.
                    $answer = trim($lessonpage->answer);
                    if ($lessonpage->answerformat == 1) {
                        $answer = file_rewrite_pluginfile_urls($answer, 'pluginfile.php', $cm->context->id,
                                'mod_lesson', 'page_answers', $lessonpage->id);
                    }

                    $foundhash = $filter->findandremovehash($answer); // May or may not have a translation hash.
                    $generatedhash = $filter->generatehash($answer);

                    // Check if a translation exists.
                    //$count = $DB->count_records('filter_translations',
                    //            ['md5key' => $foundhash ?? $generatedhash, 'targetlanguage' => $targetlanguage]);

                    if (!array_key_exists($foundhash ?? $generatedhash, $alltranslations)) {
                        $exportdata[] = [$foundhash ?? $generatedhash, $answer, '', $targetlanguage, $cm->context->id];
                    }

                    // Response.
                    // Response may be NULL as well.
                    if (!empty($lessonpage->response)) {
                        $text = file_rewrite_pluginfile_urls(trim($lessonpage->response), 'pluginfile.php', $cm->context->id,
                                    'mod_lesson', 'page_responses', $lessonpage->id);

                        $foundhash = $filter->findandremovehash($text);
                        $generatedhash = $filter->generatehash($text);

                        // Check if a translation exists.
                        //$count = $DB->count_records('filter_translations',
                        //            ['md5key' => $foundhash ?? $generatedhash, 'targetlanguage' => $targetlanguage]);

                        if (!array_key_exists($foundhash ?? $generatedhash, $alltranslations)) {
                            $exportdata[] = [$foundhash ?? $generatedhash, $text, '', $targetlanguage, $cm->context->id];
                        }
                    }
                }

                $rs->close();
            break;
            default:
                ;
            break;
        }
    }
}

// Text block.
$blocksrs = $DB->get_recordset('block_instances', ['blockname' => 'html', 'parentcontextid' => $coursecontext->id]);

foreach ($blocksrs as $block) {
    // Extract the content text from the block config.
    $blockinstance = block_instance('html', $block);
    $blockcontent = $blockinstance->config->text;

    // Generate the hash for the block title.
    if (!empty($blockinstance->config->title)) {
        $name = trim($blockinstance->config->title);
        $generatedhash = $filter->generatehash($name);

        if (!array_key_exists($generatedhash, $alltranslations)) {
                $exportdata[] = [$generatedhash, $name, '', $targetlanguage, $cm->context->id];
        }
    }

    // Block text.
    // Rewrite url.
    $text = file_rewrite_pluginfile_urls($blockinstance->config->text, 'pluginfile.php', $blockinstance->context->id,
                'block_html', 'content', null);
    $foundhash = $filter->findandremovehash($text);
    $generatedhash = $filter->generatehash($text);

    if (!array_key_exists($foundhash ?? $generatedhash, $alltranslations)) {
        $exportdata[] = [$foundhash ?? $generatedhash, $text, '', $targetlanguage, $blockinstance->context->id];
    }
}

$blocksrs->close();

// Questions.
// Get all question categories, based on course context.
$catrs = $DB->get_recordset('question_categories', ['contextid' => $coursecontext->id, 'parent' => 0]);

foreach ($catrs as $cat) {
    // TODO: When question bank is empty, get_questions_category() is undefined error is produced.
    $questions = function_exists('get_questions_category') ? get_questions_category($cat, true, true, true, true) : [];

    foreach ($questions as $q) {
        // Question text.
        // Use text as is. Do not replace url placeholders.
        // TODO: In the future, we will always use url placeholders, instead of actual urls.
        $text = $q->questiontext;

        $foundhash = $filter->findandremovehash($text); // May or may not have a translation hash.

        if (!empty($foundhash) && !array_key_exists($foundhash, $alltranslations)) {
            $exportdata[] = [$foundhash, $text, '', $targetlanguage, $coursecontext->id];
        }

        // General feedback.
        if (!empty($q->generalfeedback)) {
            $text = $q->generalfeedback;
            $foundhash = $filter->findandremovehash($text); // May or may not have a translation hash.

            if (!empty($foundhash) && !array_key_exists($foundhash, $alltranslations)) {
                $exportdata[] = [$foundhash, $text, '', $targetlanguage, $coursecontext->id];
            }
        }


        // Combined feedback.
        $fields = array('correctfeedback', 'partiallycorrectfeedback', 'incorrectfeedback');
        foreach ($fields as $field) {
            if (isset($q->options->$field) && !empty($q->options->$field)) {
                $text = $q->options->$field;
                $foundhash = $filter->findandremovehash($text); // May or may not have a translation hash.

                if (!empty($foundhash) && !array_key_exists($foundhash, $alltranslations)) {
                    $exportdata[] = [$foundhash, $text, '', $targetlanguage, $coursecontext->id];
                }
            }
        }

        if (isset($q->options->answers)) {
            foreach ($q->options->answers as $answer) {
                // Answers.
                $text = $answer->answer;
                $foundhash = $filter->findandremovehash($text); // May or may not have a translation hash.

                if (!empty($foundhash) && !array_key_exists($foundhash, $alltranslations)) {
                    $exportdata[] = [$foundhash, $text, '', $targetlanguage, $coursecontext->id];
                }

                // Feedback.
                if (!empty($answer->feedback)) {
                    $text = $answer->feedback;
                    $foundhash = $filter->findandremovehash($text); // May or may not have a translation hash.

                    if (!empty($foundhash) && !array_key_exists($foundhash, $alltranslations)) {
                        $exportdata[] = [$foundhash, $text, '', $targetlanguage, $coursecontext->id];
                    }
                }
            }
        }



        // Hints.
        foreach ($q->hints as $hint) {
            $text = $hint->hint;
            $foundhash = $filter->findandremovehash($text); // May or may not have a translation hash.

            if (!empty($foundhash) && !array_key_exists($foundhash, $alltranslations)) {
                $exportdata[] = [$foundhash, $text, '', $targetlanguage, $coursecontext->id];
            }
        }
    }
}

$catrs->close();

$downloadfilename = clean_filename("translations-course-{$course->id}");
$delimiter = 'comma';
$csvexport = new csv_export_writer($delimiter);
$csvexport->set_filename ($downloadfilename);

// Print names of all the fields.
$requiredfields = ['md5key', 'rawtext', 'substitutetext', 'targetlanguage', 'contextid', ];

$exporttitle = [];
foreach ($requiredfields as $field) {
    $exporttitle[] = $field;
}

// Add the header line to the data.
$csvexport->add_data($exporttitle);

// Print all the lines of data.
foreach ($exportdata as $datarow) {
    $csvexport->add_data ($datarow);
}

// Download the file.
$csvexport->download_file();
