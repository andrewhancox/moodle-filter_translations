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

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/tablelib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

class managetranslationissues_table extends table_sql {
    public function __construct($filterparams, $sortcolumn) {
        global $DB;

        parent::__construct('managetranslation_table');

        $this->filterparams = $filterparams;

        $this->define_columns(['issue', 'context', 'url', 'targetlanguage', 'rawtext', 'actions']);
        $this->define_headers([
            get_string('issue', 'filter_translations'),
            get_string('context', 'filter_translations'),
            get_string('url', 'filter_translations'),
            get_string('targetlanguage', 'filter_translations'),
            get_string('rawtext', 'filter_translations'),
            get_string('actions'),
            '',
        ]);
        $this->collapsible(false);
        $this->sortable(true);
        $this->pageable(true);
        $this->is_downloadable(true);
        $this->sort_default_column = $sortcolumn;

        $wheres = [];
        $params = [];

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
        }

        if (!empty($this->filterparams->hash)) {
            $params['hash'] = $this->filterparams->hash;
            $wheres[] = 'ti.generatedhash = :hash';
        }

        if (empty($wheres)) {
            $wheres[] = '1=1';
        }

        $this->set_sql('ti.id, ti.issue, ti.url, ti.targetlanguage, ti.rawtext, ti.contextid, ti.generatedhash, ti.md5key, ti.translationid',
            '{filter_translation_issues} ti',
            implode(' AND ', $wheres),
            $params
        );
    }

    public function col_url($row) {
        return \html_writer::link(new moodle_url($row->url), $row->url);
    }

    public function col_context($row) {
        $context = \context::instance_by_id($row->contextid);
        return \html_writer::link($context->get_url(), $context->get_context_name());
    }

    public function col_rawtext($row) {
        return shorten_text(strip_tags($row->rawtext));
    }

    public function col_issue($row) {
        return get_string('issue_' . $row->issue, 'filter_translations');
    }

    public function col_actions($row) {
        global $OUTPUT, $PAGE;

        return $OUTPUT->single_button(
            new moodle_url('/filter/translations/edittranslation.php', [
                'rawtext' => $row->rawtext,
                'generatedhash' => $row->generatedhash,
                'foundhash' => $row->md5key,
                'id' => $row->translationid,
                'contextid' => $row->contextid,
                'returnurl' => $this->baseurl
            ]),
            get_string('edittranslation', 'filter_translations'),
            'post'
        );
    }
}
