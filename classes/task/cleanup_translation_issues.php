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

namespace filter_translations\task;

use filter_translations\translation_issue;

/**
 * Cleanup translation issues table scheduled task.
 */
class cleanup_translation_issues extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return get_string('cleanuptranslationissues', 'filter_translations');
    }

    /**
     * Execute the task.
     */
    public function execute() {
        global $DB;

        $transaction = $DB->start_delegated_transaction();

        // Delete missing translation records older than 14 days.
        $DB->delete_records_select('filter_translation_issues',
            "issue = ? AND timecreated < ?",
            [translation_issue::ISSUE_MISSING, strtotime('-14 day')]
        );

        $transaction->allow_commit();
    }
}
