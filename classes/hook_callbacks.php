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

namespace filter_translations;

use core\hook\output\before_footer_html_generation;
use core\hook\after_config;
use filter_translations\text_filter as filter_translations;
/**
 * Class hook_callbacks
 *
 * @package    filter_translations
 * @copyright  2024 Rajneel Totaram <rajneel.totaram@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class hook_callbacks {
    /**
     *
     * If we're going in-line translation then call the some functions on the translation_button AMD module:
     * init - register click handlers for the button
     * translation_button.register - do this for all trans
     *
     * @param before_footer_html_generation $hook
     */
    public static function before_footer_html_generation(before_footer_html_generation $hook): void {
        global $CFG, $PAGE, $OUTPUT;

        require_once("$CFG->dirroot/filter/translations/filter.php");

        if (get_config('filter_translations', 'showperfdata')) {
            echo $OUTPUT->render_from_template('filter_translations/translationperfdata', (object)[
                'googletranslatefetches' => \filter_translations\translator::$googletranslatefetches,
                'langstringlookupfetches' => \filter_translations\translator::$langstringlookupfetches,
                'existingmanualtranslationsfound' => \filter_translations\translator::$existingmanualtranslationsfound,
                'existingautotranslationsfound' => \filter_translations\translator::$existingautotranslationsfound,
                'translationnotfound' => \filter_translations\translator::$translationnotfound,
                'cachehit' => \filter_translations\translator::$cachehit,
            ]);
        }

        if (empty(\filter_translations::$translationstoinject)) {
            return;
        }

        // Init - register click handlers for the button.
        $PAGE->requires->js_call_amd('filter_translations/translation_button', 'init', ['returnurl' => $PAGE->url->out()]);

        // Register - the objects required to inject and power the buttons.
        foreach (\filter_translations::$translationstoinject as $id => $jsobj) {
            $PAGE->requires->js_amd_inline(
                "require(['filter_translations/translation_button'],
                    function(translation_button) {
                        translation_button.register('$id', $jsobj);
                    });"
            );
        }

        // Find and inject buttons - add the actual buttons.
        $PAGE->requires->js_amd_inline(
            "require(['filter_translations/translation_button'],
                function(translation_button) {
                    translation_button.findandinjectbuttons();
                });"
        );
    }

    public static function after_config(after_config $hook) {
        global $CFG;

        require_once("$CFG->dirroot/filter/translations/filter.php");

        if (filter_translations::checkinlinestranslation(true)) {
            $CFG->formatstringstriptags = false;
        }
    }
}
