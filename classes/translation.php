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

use core\persistent;
use filter_translations\event\translation_created;
use filter_translations\event\translation_deleted;
use filter_translations\event\translation_updated;

class translation extends persistent {
    const TABLE = 'filter_translations';

    const SOURCE_MANUAL = 10;
    const SOURCE_AUTOMATIC = 20;

    protected static function define_properties() {
        return array(
                'md5key'               => [
                        'type' => PARAM_TEXT,
                ],
                'lastgeneratedhash'    => [
                        'type'    => PARAM_TEXT,
                        'default' => ''
                ],
                'targetlanguage'       => [
                        'type' => PARAM_TEXT,
                ],
                'contextid'            => [
                        'type' => PARAM_INT,
                ],
                'rawtext'       => [
                        'type'    => PARAM_RAW,
                        'default' => ''
                ],
                'substitutetext'       => [
                        'type'    => PARAM_RAW,
                        'default' => ''
                ],
                'substitutetextformat' => [
                        'choices' => array(FORMAT_HTML, FORMAT_MOODLE, FORMAT_PLAIN, FORMAT_MARKDOWN),
                        'type'    => PARAM_INT,
                        'default' => FORMAT_HTML
                ],
                'translationsource' => [
                    'type' => PARAM_INT,
                    'default' => self::SOURCE_MANUAL
                ]
        );
    }

    private $previous = null;

    protected function dropfromcache() {
        \filter_translations::cache()->delete($this->get('md5key'));
    }

    protected function before_update() {
        parent::before_update();
        $this->previous = self::get_record(['id' => $this->get('id')]);
    }

    protected function before_delete() {
        parent::before_delete();
        $this->previous = self::get_record(['id' => $this->get('id')]);
    }

    protected function after_create() {
        parent::after_create();
        translation_created::trigger_from_translation($this);
        translation_issue::remove_records_for_translation($this);
        $this->dropfromcache();
    }

    protected function after_delete($result) {
        parent::after_delete($result);
        translation_deleted::trigger_from_translation($this->previous);
        $this->dropfromcache();
    }

    protected function after_update($result) {
        parent::after_update($result);
        translation_updated::trigger_from_translation($this, $this->previous);
        translation_issue::remove_records_for_translation($this);
        $this->dropfromcache();
    }
}
