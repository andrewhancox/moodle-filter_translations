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

class managetranslations_table extends table_sql {
    private $languages = null;

    public function __construct($filterparams, $sortcolumn) {
        global $DB, $PAGE, $CFG;

        parent::__construct('managetranslation_table');

        $this->languages = get_string_manager()->get_list_of_translations();

        $this->filterparams = $filterparams;

        $this->define_columns(['md5key', 'targetlanguage', 'rawtext', 'substitutetext', 'usermodified', 'actions']);
        $this->define_headers([
                get_string('md5key', 'filter_translations'),
                get_string('targetlanguage', 'filter_translations'),
                get_string('rawtext', 'filter_translations'),
                get_string('substitutetext', 'filter_translations'),
                get_string('translatedby', 'filter_translations'),
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

        if (!empty($this->filterparams->rawtext)) {
            $params['rawtext'] = '%' . $DB->sql_like_escape($this->filterparams->rawtext) . '%';
            $wheres[] = $DB->sql_like('t.rawtext', ':rawtext', false);
        }

        if (!empty($this->filterparams->substitutetext)) {
            $params['substitutetext'] = '%' . $DB->sql_like_escape($this->filterparams->substitutetext) . '%';
            $wheres[] = $DB->sql_like('t.substitutetext', ':substitutetext', false);
        }

        if (!empty($this->filterparams->targetlanguage)) {
            $params['targetlanguage'] = $this->filterparams->targetlanguage;
            $wheres[] = 't.targetlanguage = :targetlanguage';
        } else if (!has_capability('filter/translations:editsitedefaulttranslations', $PAGE->context)) {
            $params['targetlanguage'] = $CFG->lang;
            $wheres[] = 't.targetlanguage <> :targetlanguage';
        }

        if (!empty($this->filterparams->hash)) {
            $params['hash'] = $this->filterparams->hash;
            $params['hash2'] = $this->filterparams->hash;
            $wheres[] = '(t.lastgeneratedhash = :hash OR t.md5key = :hash2)';
        }

        if (!empty($this->filterparams->usermodified)) {
            $params['userid'] = $this->filterparams->usermodified;
            $wheres[] = '(t.usermodified = :userid)';
        }

        if (empty($wheres)) {
            $wheres[] = '1=1';
        }

        $userfieldsapi = \core_user\fields::for_name()->including('username', 'deleted');
        $userfields = $userfieldsapi->get_sql('u', false, '', '', false)->selects;

        $this->set_sql('t.id, t.md5key, t.targetlanguage, t.rawtext, t.substitutetext, t.usermodified, ' . $userfields,
                '{filter_translations} t LEFT JOIN {user} u on t.usermodified = u.id',
            implode(' AND ', $wheres),
            $params);
    }

    public function col_rawtext($row) {
        return shorten_text(strip_tags($row->rawtext));
    }

    public function col_substitutetext($row) {
        return shorten_text(strip_tags($row->substitutetext));
    }

    public function col_targetlanguage($row) {
        if (isset($this->languages[$row->targetlanguage])) {
            return $this->languages[$row->targetlanguage];
        }

        return $row->targetlanguage;
    }

    public function col_usermodified($row) {
        return \html_writer::link(
            new moodle_url('/user/view.php',
            array('id' => $row->usermodified)), fullname($row)
        );
    }

    public function col_actions($row) {
        global $OUTPUT, $PAGE;

        return $OUTPUT->single_button(
            new moodle_url('/filter/translations/edittranslation.php', [
                'id' => $row->id,
                'targetlanguage' => $row->targetlanguage,
                'returnurl' => $PAGE->url
            ]),
            get_string('edittranslation', 'filter_translations'),
            'post'
        );
    }
}
