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

define(['jquery', 'core/modal_factory', 'core/str', 'core/templates'], function ($, ModalFactory, Str, templates) {
    var translation_button = {
        'init': function () {
            $('body').on('click', '.filter_translations_btn_translate', translation_button.opentranslation);
        },
        'opentranslation': function () {
            var clickedbutton = $(this);

            var keys = [
                {
                    key: 'translationdetails',
                    component: 'filter_translations'
                },
            ];

            var context = {
                'rawtext': decodeURIComponent(clickedbutton.data('rawtext').replace(/\+/g, ' ')),
                'rawtext_unprocessed': clickedbutton.data('rawtext'),
                'generatedhash': clickedbutton.data('generatedhash'),
                'foundhash': clickedbutton.data('foundhash'),
                'translationid': clickedbutton.data('translationid'),
                'dirtytranslation': clickedbutton.data('dirtytranslation')
            };

            Str.get_strings(keys).then(function (langStrings) {
                return templates.render('filter_translations/translationdetailsmodalbody', context).done(function (html) {
                    ModalFactory.create({
                        title: langStrings[0],
                        body: html,
                        type: ModalFactory.types.ALERT
                    }).then(function (modal) {
                        modal.show();
                    });
                });
            }).fail(Notification.exception);
        }
    };

    return translation_button;
});
