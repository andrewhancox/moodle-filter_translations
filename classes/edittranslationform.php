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

use context_system;
use core\form\persistent;

/**
 * Form for editing translations, includes some additional features for viewing the translation to be edited/created
 * and the raw text.
 */
class edittranslationform extends persistent {
    /**
     * Show rich text editing controls
     */
    const FORMTYPE_RICH = 10;
    /**
     * Show multi-line plain text editing controls
     */
    const FORMTYPE_PLAINMULTILINE = 20;
    /**
     * Show single-line plain text editing controls
     */
    const FORMTYPE_PLAIN = 30;

    protected static $persistentclass = 'filter_translations\\translation';

    /**
     * Fileds in the form that do not correspond to properties of the persistent class.
     * @var string[]
     */
    protected static $foreignfields = ['substitutetext_plain', 'substitutetext_editor', 'substitutetext_format',
        'substitutetexttrust', 'returnurl', 'deletebutton'];

    /**
     * Build the form.
     *
     * @return void
     * @throws \coding_exception
     * @throws \dml_exception
     * @throws \moodle_exception
     */
    public function definition() {
        global $PAGE, $CFG;

        $mform = $this->_form;
        $data = $this->get_default_data();

        $mform->addElement('hidden', 'rawtext');
        if ($this->_customdata['formtype'] == self::FORMTYPE_RICH) {
            $mform->setType('rawtext', PARAM_RAW);
        } else {
            $mform->setType('rawtext', PARAM_TEXT);
        }

        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_URL);

        if (has_capability('filter/translations:edittranslationhashkeys', context_system::instance())) {
            $mform->addElement('text', 'md5key', get_string('md5key', 'filter_translations'), 'maxlength="32" size="32"');
            $mform->setType('md5key', PARAM_TEXT);
        } else {
            $mform->addElement('static', 'md5keyval', get_string('md5key', 'filter_translations'), $data->md5key);
            $mform->addElement('hidden', 'md5key');
            $mform->setType('md5key', PARAM_TEXT);
        }

        $translations = get_string_manager()->get_list_of_translations();

        if (!has_capability('filter/translations:editsitedefaulttranslations', $PAGE->context) && isset($translations[$CFG->lang])) {
            unset($translations[$CFG->lang]);
        }

        $mform->addElement('select', 'targetlanguage', get_string('targetlanguage', 'filter_translations'), $translations);
        $mform->setDefault('targetlanguage', current_language());

        $mform->addElement('html', "<div class='row'>");

        $mform->addElement('html', "<div class='col-lg-6'>");

        $mform->addElement('html', '<ul class="nav nav-tabs" id="" role="tablist">');

        // Build a bunch of tab links.

        // View the raw source text.
        $this->addtablink($mform, 'rawtext', get_string('rawtext', 'filter_translations'), true);

        // For stale translations, a diff showing the text the translation was created for against the new text.
        if (!empty($this->_customdata['showdiff'])) {
            $this->addtablink($mform, 'diff', get_string('diff', 'filter_translations'));
        }

        // For stale translations, the text the translation was created for.
        if (!empty($this->_customdata['old'])) {
            $this->addtablink($mform, 'old', get_string('old', 'filter_translations'));
        }

        // When translating rich HTML show the raw HTML.
        if ($this->_customdata['formtype'] == self::FORMTYPE_RICH) {
            $this->addtablink($mform, 'rawhtml', get_string('rawhtml', 'filter_translations'));
        }

        $mform->addElement('html', '</ul>');

        // Build the contents of each tab.

        // The raw text.
        $mform->addElement('html', '<div class="tab-content" id="">');
        $this->addtabcontents($mform, 'rawtext', $this->get_persistent()->get('rawtext'), true);

        // The div that the diff2html.js will populate with a nice diff viewer.
        if (!empty($this->_customdata['showdiff'])) {
            $this->addtabcontents($mform, 'diff', '<div class="translationdiff" id="translationdiff"></div>');
        }

        // The old text.
        if (!empty($this->_customdata['old'])) {
            $this->addtabcontents($mform, 'old', $this->_customdata['old']);
        }

        // The raw HTML - run the HTML fragment through an HTML tidying function to indent it neatly etc. and do some
        // HTML entity encoding.
        if ($this->_customdata['formtype'] == self::FORMTYPE_RICH) {
            $this->addtabcontents($mform, 'rawhtml', \html_writer::tag('pre',
                str_replace('>', '&gt;', str_replace('<', '&lt;',
                    unifieddiff::tidyhtml($this->get_persistent()->get('rawtext'))
                )),
                ['class' => 'filter_translations_rawhtml']
            ));
        }

        $mform->addElement('html', "</div>");

        $mform->addElement('html', "</div>");

        $mform->addElement('html', "<div class='col-lg-6'>");
        $mform->addElement('html', "<div><h4 class='pb-3'>" . get_string('substitutetext', 'filter_translations') . "</h4></div>");
        switch ($this->_customdata['formtype']) {
            case self::FORMTYPE_RICH:
                $mform->addElement('editor', 'substitutetext_editor', get_string('substitutetext', 'filter_translations'), null,
                    $this->get_substitute_text_editoroptions());
                $mform->setType('substitutetext_editor', PARAM_RAW);
                $mform->addElement('advcheckbox', 'sameasrawtext', '', get_string('sameasrawcontent', 'filter_translations'));
                break;
            case self::FORMTYPE_PLAINMULTILINE:
                $mform->addElement('textarea', 'substitutetext_plain', get_string('substitutetext', 'filter_translations'));
                $mform->setType('substitutetext_plain', PARAM_TEXT);
                $mform->addElement('advcheckbox', 'sameasrawtext', '', get_string('sameasrawcontent', 'filter_translations'));
                break;
            case self::FORMTYPE_PLAIN:
                $mform->addElement('text', 'substitutetext_plain', get_string('substitutetext', 'filter_translations'), 'size="48"');
                $mform->setType('substitutetext_plain', PARAM_TEXT);
                $mform->addElement('advcheckbox', 'sameasrawtext', '', get_string('sameasrawcontent', 'filter_translations'));
                break;
            default:
                throw new \moodle_exception('unknownformtype');
        }
        $mform->addElement('html', "</div>");

        $mform->addElement('html', "</div>");

        $mform->addElement('hidden', 'contextid');

        $mform->addElement('hidden', 'lastgeneratedhash');

        $buttonarray = [
            $mform->createElement('submit', 'submitbutton', get_string('savechanges'))
        ];

        $buttonarray[] = $mform->createElement('cancel');

        if (!empty($this->get_persistent()->get('id')) && has_capability('filter/translations:deletetranslations', $PAGE->context)) {
            $buttonarray[] = $mform->createElement('submit', 'deletebutton', get_string('delete'));
        }

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }

    /**
     * Override the base class implementation to handle the rich text editor.
     *
     * @return \stdClass
     * @throws \coding_exception
     * @throws \dml_exception
     */
    protected function get_default_data() {
        $data = parent::get_default_data();

        if ($this->_customdata['formtype'] == self::FORMTYPE_RICH) {
            $data->substitutetextformat = $data->substitutetext['format'];
            $data->substitutetext = $data->substitutetext['text'];

            if (isset($data->id)) {
                $itemid = $data->id;
            } else {
                $itemid = null;
            }

            $data = file_prepare_standard_editor($data, 'substitutetext', $this->get_substitute_text_editoroptions(),
                context_system::instance(), 'filter_translations', 'substitutetext', $itemid);
        } else {
            $data->substitutetext_plain = $data->substitutetext['text'];
        }

        return $data;
    }

    /**
     * Add a standard Moodle tab.
     *
     * @param $mform
     * @param $name
     * @param $label
     * @param $selected
     * @return void
     */
    private function addtablink($mform, $name, $label, $selected = false) {
        if ($selected) {
            $selectedariaattr = 'true';
            $selectedclass = 'active';
        } else {
            $selectedariaattr = 'false';
            $selectedclass = '';
        }
        $mform->addElement('html',
            '<li class="nav-item">
            <a class="nav-link ' . $selectedclass . '" id="diff-tab" data-toggle="tab" href="#' . $name .
            '" role="tab" aria-selected="' . $selectedariaattr . '">
            <h4>' . $label . '</h4>
            </a>
            </li>');
    }

    /**
     * Add the contents of a tab.
     *
     * @param $mform
     * @param $name
     * @param $contents
     * @param $selected
     * @return void
     */
    private function addtabcontents($mform, $name, $contents, $selected = false) {
        if ($selected) {
            $selectedattr = 'show active';
        } else {
            $selectedattr = '';
        }
        $mform->addElement('html',
            '<div class="tab-pane fade ' . $selectedattr . '" id="' . $name . '" role="tabpanel" aria-labelledby="' . $name .
            '-tab">');
        $mform->addElement('html', $contents);
        $mform->addElement('html', "</div>");
    }

    /**
     * Override function visiblity.
     *
     * @param $data
     * @return object|\stdClass
     */
    public function filter_data_for_persistent($data) {
        return parent::filter_data_for_persistent($data);
    }

    /**
     * Get the configuration for the rich text editor.
     * @return array
     */
    public function get_substitute_text_editoroptions(): array {
        global $SITE;

        $context = context_system::instance();

        $editoroptions = ['maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $SITE->maxbytes,
            'trusttext' => false,
            'noclean' => true,
            'context' => $context];
        return $editoroptions;
    }

    /**
     * Extra validation.
     *
     * @param  stdClass $data Data to validate.
     * @param  array $files Array of files.
     * @param  array $errors Currently reported errors.
     * @return array of additional errors, or overridden errors.
     */
    protected function extra_validation($data, $files, array &$errors) {
        $newerrors = array();

        if ((isset($data->substitutetext_plain) && $data->rawtext === $data->substitutetext_plain) ||
                (isset($data->substitutetext_editor) && $data->rawtext === $data->substitutetext_editor['text'])) {
            if ($data->sameasrawtext == "0") {
                $newerrors['sameasrawtext'] = get_string('sameasrawcontentmessage', 'filter_translations');
            }
        }

        return $newerrors;
    }
}
