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

namespace filter_translations\translationproviders;

use admin_setting_configcheckbox;
use admin_setting_configtext;
use curl;
use filter_translations\translation;
use moodle_url;

class googletranslate extends translationprovider {
    private static function config() {
        static $config = null;

        if (!isset($config)) {
            $config = get_config('filter_translations');

            if (!empty($config->google_backoffonerror) && $config->google_backoffonerror_time < time() - HOURSECS) {
                $config->google_backoffonerror = false;
                set_config('google_backoffonerror', false, 'filter_translations');
            }

            if (empty($config->google_enable)
                || empty($config->google_apikey)
                || empty($config->google_apiendpoint)
                || !empty($config->google_backoffonerror)) {
                $config = false;
            }
        }

        return $config;
    }

    protected function generate_translation($text, $targetlanguage) {
        $config = self::config();

        if (empty($config)) {
            return null;
        }

        global $CFG;
        require_once($CFG->libdir . "/filelib.php");

        $targetlanguage = str_replace('_wp', '', $targetlanguage);
        $curl = new curl();

        $params = [
                'target' => $targetlanguage,
                'key'    => $config->google_apikey,
                'q'      => $text
        ];

        $url = new moodle_url($config->google_apiendpoint, $params);

        try {
            $resp = $curl->post($url->out(false));
        } catch (\Exception $ex) {
            error_log("Error calling Google Translate: \n" . $ex->getMessage());
            $this->backoff();
            return null;
        }

        $info = $curl->get_info();
        if ($info['http_code'] != 200) {
            error_log("Error calling Google Translate: \n" . $info['http_code']);
            $this->backoff();
            return null;
        }

        $resp = json_decode($resp);

        if (empty($resp->data->translations[0]->translatedText)) {
            return null;
        }

        return $resp->data->translations[0]->translatedText;
    }

    private function backoff() {
        set_config('google_backoffonerror', true, 'filter_translations');
        set_config('google_backoffonerror_time', time(), 'filter_translations');
    }
}
