<?php

namespace filter_translations;

class translator {
    protected function get_string_manager() {
        return get_string_manager();
    }

    public function get_best_translation($language, $generatedhash, $foundhash) {
        $prioritisedlanguages =
                array_reverse(array_merge(['en'], $this->get_string_manager()->get_language_dependencies($language)));

        $options = $this->get_usable_translations($prioritisedlanguages, $generatedhash, $foundhash);
        $optionsforbestlanguage = $this->filter_options_by_best_language($options, $prioritisedlanguages);
        return $this->filter_options_by_best_hash($optionsforbestlanguage, $generatedhash, $foundhash);
    }

    private function filter_options_by_best_hash($options, $generatedhash, $foundhash) {
        foreach ($options as $option) {
            if ($option->get('md5key') == $foundhash) {
                return $option;
            }
        }
        foreach ($options as $option) {
            if ($option->get('md5key') == $generatedhash) {
                return $option;
            }
        }
        foreach ($options as $option) {
            if ($option->get('lastgeneratedhash') == $generatedhash) {
                return $option;
            }
        }

        return false;
    }

    private function filter_options_by_best_language($options, $prioritisedlanguages) {
        $translationsbylang = [];
        foreach ($options as $option) {
            if (!isset($translationsbylang[$option->get('targetlanguage')])) {
                $translationsbylang[$option->get('targetlanguage')] = [];
            }
            $translationsbylang[$option->get('targetlanguage')][] = $option;
        }

        foreach ($prioritisedlanguages as $language) {
            if (isset($translationsbylang[$language])) {
                return $translationsbylang[$language];
            }
        }

        return [];
    }

    private function get_usable_translations($prioritisedlanguages, $generatedhash, $foundhash) {
        global $DB;

        $hashor = ['md5key = :generatedhash', 'lastgeneratedhash = :generatedhash2'];
        $params = ['generatedhash' => $generatedhash, 'generatedhash2' => $generatedhash];

        if (isset($foundhash)) {
            $hashor[] = 'md5key = :foundhash';
            $params['foundhash'] = $foundhash;
        }
        $hashor = implode(' OR ', $hashor);

        list($langsql, $langparam) = $DB->get_in_or_equal($prioritisedlanguages, SQL_PARAMS_NAMED);

        $select = "($hashor) AND targetlanguage $langsql";

        return translation::get_records_select($select, $params + $langparam);
    }
}
