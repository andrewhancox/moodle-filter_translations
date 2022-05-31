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

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings->add(new admin_setting_heading('managetranslations', '',
        html_writer::link(new moodle_url('/filter/translations/managetranslations.php'),
            get_string('managetranslations', 'filter_translations'), ['class' => "btn btn-primary"])));

    $settings->add(new admin_setting_heading('managetranslationissues', '',
        html_writer::link(new moodle_url('/filter/translations/managetranslationissues.php'),
            get_string('managetranslationissues', 'filter_translations'), ['class' => "btn btn-primary"])));

    $options = [];
    foreach ([cache_store::MODE_REQUEST, cache_store::MODE_SESSION, cache_store::MODE_APPLICATION] as $mode) {
        $options[$mode] = get_string('mode_' . $mode, 'cache');
    }
    $settings->add(new admin_setting_configselect('filter_translations/cachingmode',
        get_string('cachingmode', 'filter_translations'), get_string('cachingmode_desc', 'filter_translations'), cache_store::MODE_REQUEST, $options));

    $settings->add(new admin_setting_heading('logging', get_string('logging', 'filter_translations'), ''));

    $settings->add(new admin_setting_configcheckbox('filter_translations/logmissing',
        get_string('logmissing', 'filter_translations'), '', false));

    $settings->add(new admin_setting_configcheckbox('filter_translations/logstale',
        get_string('logstale', 'filter_translations'), '', false));

    $settings->add(new admin_setting_configduration('filter_translations/logdebounce',
        get_string('logdebounce', 'filter_translations'), '', DAYSECS));

    $settings->add(new admin_setting_heading('languagestringreverseapi', get_string('languagestringreverse', 'filter_translations'), ''));

    $settings->add(new admin_setting_configcheckbox('filter_translations/languagestringreverse_enable',
        get_string('languagestringreverse_enable', 'filter_translations'), '', false));

    $settings->add(new admin_setting_heading('googletranslateapi', get_string('googletranslate', 'filter_translations'), ''));

    $settings->add(new admin_setting_configcheckbox('filter_translations/google_enable',
        get_string('google_enable', 'filter_translations'), '', false));

    $settings->add(new admin_setting_configcheckbox('filter_translations/google_backoffonerror',
        get_string('google_backoffonerror', 'filter_translations'), '', false));

    $settings->add(new admin_setting_configtext('filter_translations/google_apiendpoint',
        get_string('google_apiendpoint', 'filter_translations')
        , '', 'https://translation.googleapis.com/language/translate/v2',
        PARAM_URL));

    $settings->add(new admin_setting_configtext('filter_translations/google_apikey', get_string('google_apikey', 'filter_translations')
        , '', null, PARAM_RAW_TRIMMED, 40));
}
