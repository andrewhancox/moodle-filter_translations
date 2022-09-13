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

/**
 * Persistent object to handle CRUD operations for translations.
 */
class translation extends persistent {
    const TABLE = 'filter_translations';

    /**
     * Translation was manually performed.
     */
    const SOURCE_MANUAL = 10;
    /**
     * Translation was automatically generated - e.g. Google translate.
     */
    const SOURCE_AUTOMATIC = 20;

    protected static function define_properties() {
        return array(
                // The md5 hash that will be used to refer to this translation using span tags.
                'md5key'               => [
                        'type' => PARAM_TEXT,
                ],
                // The md5 hash of the raw text that was last seen when this translation was updated/created.
                'lastgeneratedhash'    => [
                        'type'    => PARAM_TEXT,
                        'default' => ''
                ],
                // Target language of this translation.
                'targetlanguage'       => [
                        'type' => PARAM_TEXT,
                ],
                // The context that this translation was intiailly created for.
                'contextid'            => [
                        'type' => PARAM_INT,
                ],
                // The raw text that was last seen when this translation was updated/created.
                'rawtext'       => [
                        'type'    => PARAM_RAW,
                        'default' => ''
                ],
                // Text to use as substitution.
                'substitutetext'       => [
                        'type'    => PARAM_RAW,
                        'default' => ''
                ],
                // Format of the text to use as subtitution.
                'substitutetextformat' => [
                        'choices' => array(FORMAT_HTML, FORMAT_MOODLE, FORMAT_PLAIN, FORMAT_MARKDOWN),
                        'type'    => PARAM_INT,
                        'default' => FORMAT_HTML
                ],
                // How the translation was obtained - manual or automatic.
                'translationsource' => [
                    'type' => PARAM_INT,
                    'default' => self::SOURCE_MANUAL,
                    'choices' => array(self::SOURCE_MANUAL, self::SOURCE_AUTOMATIC),
                ]
        );
    }

    /**
     * Snapshot of previous state for use in post update/delete handlers.
     * @var null
     */
    private $previous = null;

    /**
     * Drop the cached copy of the translation.
     *
     * @return void
     * @throws \coding_exception
     */
    protected function dropfromcache() {
        $cache = \filter_translations::cache();

        foreach ([
            $this->get('targetlanguage') . $this->get('md5key'),
            $this->get('targetlanguage') . $this->get('lastgeneratedhash'),
                 ] as $cachekey) {
            $cache->delete($cachekey);
        }
    }

    /**
     * Snapshot the translation for use in after_update
     *
     * @return void
     * @throws \coding_exception
     */
    protected function before_update() {
        parent::before_update();
        $this->previous = self::get_record(['id' => $this->get('id')]);
    }

    /**
     * Snapshot the translation for use in after_delete
     *
     * @return void
     * @throws \coding_exception
     */
    protected function before_delete() {
        parent::before_delete();
        $this->previous = self::get_record(['id' => $this->get('id')]);
    }

    /**
     * After creating new translation:
     * Trigger creation event
     * Purge any translation issues relating to this piece of text.
     *
     * @return void
     * @throws \coding_exception
     */
    protected function after_create() {
        parent::after_create();
        translation_created::trigger_from_translation($this);
        translation_issue::remove_records_for_translation($this);
        $this->dropfromcache();
    }

    /**
     * After deleteing a translation:
     * Trigger deleted event
     * Drop from cache
     *
     * @param $result
     * @return void
     * @throws \coding_exception
     */
    protected function after_delete($result) {
        parent::after_delete($result);
        translation_deleted::trigger_from_translation($this->previous);
        $this->dropfromcache();
    }

    /**
     * After updating a translation:
     * Trigger updated event
     * Purge any translation issues relating to this piece of text
     * Drop old version from cache
     *
     * @param $result
     * @return void
     * @throws \coding_exception
     */
    protected function after_update($result) {
        parent::after_update($result);
        translation_updated::trigger_from_translation($this, $this->previous);
        translation_issue::remove_records_for_translation($this);
        $this->dropfromcache();
    }
}
