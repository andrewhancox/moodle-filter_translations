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
 * @copyright 2023, Tina John <johnt.22.tijo@gmail.com> 
 */

namespace filter_translations\translationproviders;

use admin_setting_configcheckbox;
use admin_setting_configtext;
use curl;
use filter_translations\translation;
use moodle_url;

/**
 * Translation provider to fetch and then retain translations from Google translate.
 */
class deepltranslate extends translationprovider {
    /**
     * If deepl translate is enabled and configured return config, else return false.
     *
     * @return false|mixed|object|string|null
     * @throws \dml_exception
     */
    private static function config() {
        static $config = null;

        if (!isset($config)) {
            $config = get_config('filter_translations');

            if (!empty($config->deepl_backoffonerror) && $config->deepl_backoffonerror_time < time() - HOURSECS) {
                $config->deepl_backoffonerror = false;
                set_config('deepl_backoffonerror', false, 'filter_translations');
                $cache = \filter_translations::cache();
                $cache->purge();
            }

            if (empty($config->deepl_enable)
                || empty($config->deepl_apikey)
                || empty($config->deepl_apiendpoint)
                || !empty($config->deepl_backoffonerror)) {
                $config = false;
            }
        }

        return $config;
    }

    /**
     * Get a piece of text translated into a specific language.
     * The language of the source text is auto-detected by deepl.
     *
     * Either the translated text or if there is an error start backing off from the API and return null.
     *
     * @param $text
     * @param $targetlanguage
     * @return string|null
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    protected function generate_translation($text, $targetlanguage) {
        $config = self::config();

        if (empty($config)) {
            return null;
        }

        global $CFG;
        require_once($CFG->libdir . "/filelib.php");

        $targetlanguage = str_replace('_wp', '', $targetlanguage);

        // Look for any base64 encoded files, create an md5 of their content,
        // use the md5 as a placeholder while we send the text to deepl translate.
        $base64s = [];
        if (strpos($text, 'base64') !== false) {
            $text = preg_replace_callback(
                '/(data:[^;]+\/[^;]+;base64)([^"]+)/i',
                function ($m) use (&$base64s) {
                    $md5 = md5($m[2]);
                    $base64s[$md5] = $m[2];

                    return $m[1] . $md5;
                },
                $text
            );
        }


        // Added tinjohn logic for deepl.
        $authKey = $config->deepl_apikey;
        //$authKey = 'for_debugging_off';
        $apiUrl = $config->deepl_apiendpoint;
        
        // Data to be translated and target language
        $data = array(
            'text' => array($text),
            'target_lang' => $targetlanguage
        );
        
        // Convert data to JSON format
        $dataJson = json_encode($data);
        
        // Initialize cURL session
        $ch = curl_init();
        
        // Set cURL options
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $dataJson);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array(
            'Authorization: DeepL-Auth-Key ' . $authKey,
            'Content-Type: application/json'
        ));
        
        // Execute cURL session and get the response
        $response = curl_exec($ch);
        
        // Check for cURL errors
        if (curl_errno($ch)) {
            error_log("Error calling DeepL Translate: \n" . curl_error($ch));
            $this->backoff();
            return null;
            // echo 'cURL Error: ' . curl_error($ch);
        }
        
        // Close cURL session
        curl_close($ch);
        
        // Decode the JSON response
        $translatedData = json_decode($response, true);
        
        // Output the translated text
        if (isset($translatedData['translations'][0]['text'])) {
            $text = $translatedData['translations'][0]['text'];
        } else {
            return null;
        }
        
        // Swap the base 64 encoded images back in.
        foreach ($base64s as $md5 => $base64) {
            $text = str_replace($md5, $base64, $text);
        }

        return $text;
    }

    /**
     * Back off from API - used when errors are getting returned.
     *
     * @return void
     */
    private function backoff() {
        set_config('google_backoffonerror', true, 'filter_translations');
        set_config('google_backoffonerror_time', time(), 'filter_translations');
    }
}
