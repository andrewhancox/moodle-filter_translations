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
            $('.filter_translations_btn_translate').each(function() {
                $(this).attr('role', 'button'); // Purify_html will have removed this attribute.
            });

            $('body').on('click', '.filter_translations_btn_translate', translation_button.opentranslation);
            $('body').on('contextmenu', '.filter_translations_btn_translate', translation_button.opentranslation);
        },
        'opentranslation': function (event) {
            event.stopPropagation();
            event.preventDefault();

            var clickedbutton = $(this);

            var keys = [
                {
                    key: 'translationdetails',
                    component: 'filter_translations'
                },
            ];

            var classList = clickedbutton.prop('classList');
            var context = null;

            for (var i = 0, l = classList.length; i < l; ++i) {
                var matches = classList[i].match(/translationkey_([a-zA-Z0-9]+)/);
                if (matches && matches.length == 2) {
                    context = translation_button.objects[matches[1]];
                    break;
                }
            }

            if (!context) {
                return;
            }

            context.rawtext_unprocessed = context.rawtext;
            context.rawtext = decodeURIComponent(context.rawtext_unprocessed.replace(/\+/g, ' '));

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
        },
        'register': function (key, translationinfo) {
            translation_button.objects[key] = translationinfo;
        },
        objects: {}
    };

    return translation_button;
});
