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
    public function __construct($filterparams, $sortcolumn) {
        parent::__construct('managetranslation_table');

        $this->filterparams = $filterparams;

        $this->define_columns(['md5key', 'targetlanguage', 'rawtext', 'actions']);
        $this->define_headers([
                get_string('md5key', 'filter_translations'),
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

        $this->set_sql('t.id, t.md5key, t.targetlanguage, t.rawtext',
                '{filter_translations} t',
        '1=1');
    }

    public function col_rawtext($row) {
        return shorten_text(strip_tags($row->rawtext));
    }

    public function col_actions($translation) {
        global $OUTPUT;

        $out = '';

        $icon = $OUTPUT->pix_icon('t/edit', get_string('edittranslation', 'filter_translations'));
        $url = new moodle_url('/filter/translations/edittranslation.php', ['id' => $translation->id]);
        $out .= $OUTPUT->action_link($url, $icon);

        return $out;
    }
}
