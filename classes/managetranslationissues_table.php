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

namespace filter_translations;

use moodle_url;
use table_sql;
use html_writer;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/tablelib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

/**
 * Table to list and manage translations.
 */
class managetranslationissues_table extends table_sql {
    /**
     * @var array
     */
    private $languages = null;

    /**
     * Set up the table, manage filters etc.
     *
     * @param $filterparams
     * @param $sortcolumn
     * @param $download
     * @throws \coding_exception
     * @throws \dml_exception
     */
    public function __construct($filterparams, $sortcolumn, $download) {
        global $DB, $PAGE, $CFG, $OUTPUT;

        parent::__construct('managetranslationissues_table');

        $this->languages = get_string_manager()->get_list_of_translations();

        $this->filterparams = $filterparams;

        $headers = [];
        $columns = [];

        $canbulkdelete = has_capability('filter/translations:bulkdeletetranslations', $PAGE->context);
        if ($canbulkdelete && empty($download)) {
            $mastercheckbox = new \core\output\checkbox_toggleall('translations-table', true, [
                'id' => 'select-all-translations',
                'name' => 'select-all-translations',
                'label' => get_string('selectall'),
                'labelclasses' => 'sr-only',
                'classes' => 'm-1',
                'checked' => false,
            ]);

            $headers[] = $OUTPUT->render($mastercheckbox);
            $columns[] = 'select';
        }

        $columns += ['issue', 'context', 'url', 'targetlanguage', 'rawtext', 'substitutetext'];
        $headers += [
            get_string('issue', 'filter_translations'),
            get_string('context', 'filter_translations'),
            get_string('url', 'filter_translations'),
            get_string('targetlanguage', 'filter_translations'),
            get_string('rawtext', 'filter_translations'),
            get_string('substitutetext', 'filter_translations')
        ];

        if (empty($download)) {
            $columns[] = 'actions';
            $headers[] = get_string('actions');
        }

        $this->define_columns($columns);
        $this->define_headers($headers);
        $this->collapsible(false);
        $this->sortable(true);
        $this->pageable(true);
        $this->is_downloadable(true);
        $this->no_sorting('select');
        $this->sort_default_column = $sortcolumn;

        $wheres = [];
        $params = [];

        if (!empty($this->filterparams->rawtext)) {
            $params['rawtext'] = '%' . $DB->sql_like_escape($this->filterparams->rawtext) . '%';
            $wheres[] = $DB->sql_like('ti.rawtext', ':rawtext', false);
        }

        if (!empty($this->filterparams->substitutetext)) {
            $params['substitutetext'] = '%' . $DB->sql_like_escape($this->filterparams->substitutetext) . '%';
            $wheres[] = $DB->sql_like('t.substitutetext', ':substitutetext', false);
        }

        if (!empty($this->filterparams->issue) && $this->filterparams->issue !== 'all') {
            $wheres[] = "ti.issue = :issue";
            $params['issue'] = $this->filterparams->issue;
        }

        if (!empty($this->filterparams->url)) {
            $wheres[] = "ti.url = :url";
            $params['url'] = $this->filterparams->url;
        }

        if (!empty($this->filterparams->contextid)) {
            $context = \context::instance_by_id($this->filterparams->contextid);

            if (is_a($context, \context_system::class)) {
                $contextids = [];
            } else {
                $contextids = array_keys($context->get_child_contexts(true));
            }
            $contextids[] = $context->id;

            list($insql, $inparams) = $DB->get_in_or_equal($contextids, SQL_PARAMS_NAMED);
            $wheres[] = "ti.contextid $insql";
            $params += $inparams;
        }

        if (!empty($this->filterparams->targetlanguage)) {
            $params['targetlanguage'] = $this->filterparams->targetlanguage;
            $wheres[] = 'ti.targetlanguage = :targetlanguage';
        } else if (!has_capability('filter/translations:editsitedefaulttranslations', $PAGE->context)) {
            $params['targetlanguage'] = $CFG->lang;
            $wheres[] = 'ti.targetlanguage <> :targetlanguage';
        }

        if (!empty($this->filterparams->hash)) {
            $params['hash'] = $this->filterparams->hash;
            $wheres[] = 'ti.generatedhash = :hash';
        }

        if (empty($wheres)) {
            $wheres[] = '1=1';
        }

        $this->set_sql('ti.id, ti.issue, ti.url, ti.targetlanguage, ti.rawtext, ti.contextid, ti.generatedhash, ti.md5key,
            ti.translationid, t.substitutetext',
            '{filter_translation_issues} ti LEFT JOIN {filter_translations} t on ti.translationid = t.id',
            implode(' AND ', $wheres),
            $params
        );
    }

    /**
     * Generate the select column.
     *
     * @param \stdClass $row
     * @return string
     */
    public function col_select($row) {
        global $OUTPUT;

        if ($this->is_downloading()) {
            return;
        }

        $checkbox = new \core\output\checkbox_toggleall('translations-table', false, [
            'classes' => 'translationcheckbox m-1',
            'id' => 'translation' . $row->id,
            'name' => 'translationid[]',
            'value' => $row->id,
            'checked' => false,
            'label' => get_string('selectitem', 'moodle', $row->id),
            'labelclasses' => 'accesshide',
        ]);

        return $OUTPUT->render($checkbox);
    }

    /**
     * Display a link to the page the missing/stale translation was found on.
     * Use the truncated URL as it's label.
     *
     * @param $row
     * @return string
     * @throws \moodle_exception
     */
    public function col_url($row) {
        return \html_writer::link(new moodle_url($row->url), shorten_text($row->url, 90));
    }

    /**
     * Display a link to the context the missing/stale translation was found on.
     * Use the context name as the label.
     *
     * @param $row
     * @return string
     * @throws \coding_exception
     */
    public function col_context($row) {
        $context = \context::instance_by_id($row->contextid);
        return \html_writer::link($context->get_url(), $context->get_context_name());
    }

    /**
     * Truncate and deHTML the raw text.
     *
     * @param $row
     * @return string
     */
    public function col_rawtext($row) {
        if ($this->is_downloading()) {
            return $row->rawtext;
        }

        return shorten_text(strip_tags($row->rawtext));
    }

    /**
     * Truncate and deHTML the subtitute text.
     *
     * @param $row
     * @return string
     */
    public function col_substitutetext($row) {
        if ($this->is_downloading()) {
            return $row->substitutetext;
        }

        return shorten_text(strip_tags($row->substitutetext));
    }

    /**
     * Get the full name for ISO language code.
     *
     * @param $row
     * @return mixed
     */
    public function col_targetlanguage($row) {
        if (isset($this->languages[$row->targetlanguage])) {
            return $this->languages[$row->targetlanguage];
        }

        return $row->targetlanguage;
    }

    /**
     * What was the problem (stale/missing).
     *
     * @param $row
     * @return \lang_string|string
     * @throws \coding_exception
     */
    public function col_issue($row) {
        return get_string('issue_' . $row->issue, 'filter_translations');
    }

    /**
     * Show actions - currenly just an edit button.
     * @param $row
     * @return string|void
     * @throws \coding_exception
     * @throws \moodle_exception
     */
    public function col_actions($row) {
        global $PAGE;

        if ($this->is_downloading()) {
            return;
        }

        return html_writer::link(
            new moodle_url('/filter/translations/edittranslation.php', [
                'rawtext' => $row->rawtext,
                'generatedhash' => $row->generatedhash,
                'foundhash' => $row->md5key,
                'id' => $row->translationid,
                'contextid' => $row->contextid,
                'targetlanguage' => $row->targetlanguage,
                'returnurl' => $PAGE->url,
            ]),
            get_string('edittranslation', 'filter_translations'),
            array('class' => 'btn btn-secondary')
        );
    }

    /**
     * Wrap in a form to power the select checkboxes and related buttons.
     *
     * @return void
     */
    public function wrap_html_start() {
        global $PAGE;

        // Begin the form.
        echo html_writer::start_tag('form', array('method' => 'post', 'id' => 'bulkdeleteform', 'action' => 'action_redir.php'));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'returnurl', 'value' => $PAGE->url));
    }

    /**
     * Finish wrapping the form.
     *
     * @return void
     */
    public function wrap_html_finish() {
        echo html_writer::start_tag('div', array('class' => 'actions my-1'));
        $this->submit_buttons();
        echo html_writer::end_tag('div');
        // Close the form.
        echo html_writer::end_tag('form');
    }

    /**
     * Output the submit button for the bulk delete action.
     */
    protected function submit_buttons() {
        global $PAGE;
        if (has_capability('filter/translations:bulkdeletetranslations', $PAGE->context)) {
            echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'action', 'value' => 'deleteissues'));

            $deletebuttonparams = [
                'type'  => 'submit',
                'class' => 'btn btn-secondary mr-1',
                'id'    => 'deletetranslationsbutton',
                'name'  => 'delete',
                'value' => get_string('deleteselectedentries', 'filter_translations'),
                'data-action' => 'toggle',
                'data-togglegroup' => 'translations-table',
                'data-toggle' => 'action',
                'disabled' => true
            ];
            echo html_writer::empty_tag('input', $deletebuttonparams);
            $PAGE->requires->event_handler('#deletetranslationsbutton', 'click', 'M.util.show_confirm_dialog',
                    array('message' => get_string('deleteissuesconfirmation', 'filter_translations')));
        }
    }

    /**
     * Download the data in the selected format.
     *
     * @param string $format The format to download the report.
     */
    public function download($format) {
        $filename = 'filter_translation_issues_' . userdate(time(), get_string('backupnameformat', 'langconfig'),
                99, false);

        $this->is_downloading($format, $filename, get_string('translationissues', 'filter_translations'));
        $this->out(100, false);
    }
}
