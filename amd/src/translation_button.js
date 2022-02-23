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
        'returnurl': '',
        'init': function (returnurl) {
            translation_button.returnurl = returnurl;
            $('body').on('click', '.filter_translations_btn_translate', translation_button.opentranslation);
            $('body').on('contextmenu', '.filter_translations_btn_translate', translation_button.opentranslation);
        },
        'findandinjectbuttons': function() {
            var encodedone = "\u{200B}"; // Zero-Width Space
            var encodedzero = "\u{200C}"; // Zero-Width Non-Joiner
            var encodedseperator = "\u{200D}"; // Zero-Width Joiner

            var elems = translation_button.findElementsDirectlyContainingText(document, encodedseperator + encodedseperator);
            elems.forEach(function(elem) {
                var matches = new RegExp(encodedseperator + encodedseperator + '([' + encodedone + encodedzero + ']*)');

                var regexzero = new RegExp(encodedzero, 'g');
                var regexone = new RegExp(encodedone, 'g');

                var binary = matches.exec($(elem).html())[1].replace(regexone, '1').replace(regexzero, '0');
                var key = parseInt(binary, 2);
                var translationinfo = translation_button.objects[key];

                templates.render('filter_translations/translatebutton', translationinfo).done(function (html) {
                    $(elem).append(html);
                });
            });
        },
        'opentranslation': function (event) {
            event.stopPropagation();
            event.preventDefault();

            var context = translation_button.objects[$(this).data('inpagetranslationid')];
            context.returnurl = translation_button.returnurl;

            Str.get_strings([{
                key: 'translationdetails',
                component: 'filter_translations'
            }]).then(function (langStrings) {
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

            if (translationinfo.staletranslation) {
                $('.filter_translations_btn_translate[data-inpagetranslationid=' + key + ']').addClass('alert-warning');
            } else if (translationinfo.goodtranslation) {
                $('.filter_translations_btn_translate[data-inpagetranslationid=' + key + ']').addClass('alert-success');
            }
        },
        'findElementsDirectlyContainingText': function (ancestor, text) {
            var elements = [];
            walk(ancestor);
            return elements;

            function walk(element) {
                var n = element.childNodes.length;
                for (var i = 0; i < n; i++) {
                    var child = element.childNodes[i];
                    if (child.nodeType === 3 && child.data.indexOf(text) !== -1) {
                        elements.push(element);
                        break;
                    }
                }
                for (var i = 0; i < n; i++) {
                    var child = element.childNodes[i];
                    if (child.nodeType === 1) {
                        walk(child);
                    }
                }
            }
        },
        objects: {}
    };

    return translation_button;
});
