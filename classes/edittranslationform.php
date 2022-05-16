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

class edittranslationform extends persistent {
    const FORMTYPE_RICH = 10;
    const FORMTYPE_PLAINMULTILINE = 20;
    const FORMTYPE_PLAIN = 30;

    /** @var string Persistent class name. */
    protected static $persistentclass = 'filter_translations\\translation';

    protected static $foreignfields = ['substitutetext_plain', 'substitutetext_editor', 'substitutetext_format', 'substitutetexttrust', 'returnurl', 'deletebutton'];

    function definition() {
        global $PAGE, $CFG;

        $mform = $this->_form;

        $mform->addElement('hidden', 'rawtext');
        if ($this->_customdata['formtype'] == self::FORMTYPE_RICH) {
            $mform->setType('rawtext', PARAM_RAW);
        } else {
            $mform->setType('rawtext', PARAM_TEXT);
        }

        $mform->addElement('hidden', 'returnurl');
        $mform->setType('returnurl', PARAM_URL);

        $mform->addElement('text', 'md5key', get_string('md5key', 'filter_translations'), 'maxlength="32" size="32"');
        $mform->setType('md5key', PARAM_TEXT);

        if (!has_capability('filter/translations:edittranslationhashkeys', context_system::instance())) {
            $mform->hardFreeze(['md5key']);
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

        $this->addtablink($mform, 'rawtext', get_string('rawtext', 'filter_translations'), true);

        if (!empty($this->_customdata['showdiff'])) {
            $this->addtablink($mform, 'diff', get_string('diff', 'filter_translations'));
        }

        if (!empty($this->_customdata['old'])) {
            $this->addtablink($mform, 'old', get_string('old', 'filter_translations'));
        }

        if ($this->_customdata['formtype'] == self::FORMTYPE_RICH) {
            $this->addtablink($mform, 'rawhtml', get_string('rawhtml', 'filter_translations'));
        }

        $mform->addElement('html', '</ul>');

        $mform->addElement('html', '<div class="tab-content" id="">');

        $this->addtabcontents($mform, 'rawtext', $this->get_persistent()->get('rawtext'), true);

        if (!empty($this->_customdata['showdiff'])) {
            $this->addtabcontents($mform, 'diff', '<div class="translationdiff" id="translationdiff"></div>');
        }

        if (!empty($this->_customdata['old'])) {
            $this->addtabcontents($mform, 'old', $this->_customdata['old']);
        }

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
                    $this->get_substitute_test_editoroptions());
                $mform->setType('substitutetext_editor', PARAM_RAW);
                break;
            case self::FORMTYPE_PLAINMULTILINE:
                $mform->addElement('textarea', 'substitutetext_plain', get_string('substitutetext', 'filter_translations'));
                $mform->setType('substitutetext_plain', PARAM_TEXT);
                break;
            case self::FORMTYPE_PLAIN:
                $mform->addElement('text', 'substitutetext_plain', get_string('substitutetext', 'filter_translations'), 'size="48"');
                $mform->setType('substitutetext_plain', PARAM_TEXT);
                break;
            default:
                print_error('Unknown form type');
        }
        $mform->addElement('html', "</div>");

        $mform->addElement('html', "</div>");

        $mform->addElement('hidden', 'contextid');

        $mform->addElement('hidden', 'lastgeneratedhash');

        $buttonarray = [
            $mform->createElement('submit', 'submitbutton', get_string('savechanges'))
        ];

        $buttonarray[] = $mform->createElement('cancel');

        if (!empty($this->get_persistent()->get('id'))) {
            $buttonarray[] = $mform->createElement('submit', 'deletebutton', get_string('delete'));
        }

        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }

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

            $data = file_prepare_standard_editor($data, 'substitutetext', $this->get_substitute_test_editoroptions(), context_system::instance(),
                'filter_translations', 'substitutetext',
                $itemid);
        } else {
            $data->substitutetext_plain = $data->substitutetext['text'];
        }

        return $data;
    }

    private function addtablink($mform, $name, $label, $selected = false) {
        if ($selected) {
            $selectedariaattr = 'true';
            $selectedclass = 'active';
        } else {
            $selectedariaattr = 'false';
            $selectedclass = '';
        }
        $mform->addElement('html', '<li class="nav-item">
                                                    <a class="nav-link ' . $selectedclass . '" id="diff-tab" data-toggle="tab" href="#' . $name . '" role="tab" aria-selected="' . $selectedariaattr . '">
                                                        <h4>' . $label . '</h4>
                                                    </a>
                                                </li>');
    }

    private function addtabcontents($mform, $name, $contents, $selected = false) {
        if ($selected) {
            $selectedattr = 'show active';
        } else {
            $selectedattr = '';
        }
        $mform->addElement('html', '<div class="tab-pane fade ' . $selectedattr . '" id="' . $name . '" role="tabpanel" aria-labelledby="' . $name . '-tab">');
        $mform->addElement('html', $contents);
        $mform->addElement('html', "</div>");
    }

    // Override function visiblity.
    public function filter_data_for_persistent($data) {
        return parent::filter_data_for_persistent($data);
    }

    /**
     * @return array
     */
    public function get_substitute_test_editoroptions(): array {
        global $SITE;

        $context = context_system::instance();

        $editoroptions = ['maxfiles' => EDITOR_UNLIMITED_FILES,
            'maxbytes' => $SITE->maxbytes,
            'trusttext' => false,
            'noclean' => true,
            'context' => $context];
        return $editoroptions;
    }
}
