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
 * @copyright 2022 Rajneel Totaram <rajneel.totaram@moodle.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use filter_translations\translation;

require_once("../../config.php");

$action = required_param('action', PARAM_TEXT);
$translationids = optional_param_array('translationid', array(), PARAM_INT);
$default = new moodle_url('/filter/translations/managetranslations.php');
$returnurl = new moodle_url(optional_param('returnurl', $default, PARAM_URL));

require_login();

$context = context_system::instance();

$PAGE->set_url('/filter/translations/action_redir.php', array('action' => $action));

require_capability('filter/translations:bulkdeletetranslations', $context);

if (!confirm_sesskey()) {
    print_error('confirmsesskeybad');
}

if (($data = data_submitted()) && $action == 'bulkdelete') {
    // Delete each selected translation. This will log delete event.
    foreach ($translationids as $id) {
        $persistent = new translation($id);
        $persistent->delete();
    }
}

redirect($returnurl);
