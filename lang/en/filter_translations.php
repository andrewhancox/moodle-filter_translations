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

$string['cachedef_translatedtext'] = 'Translated text';
$string['clihelptext_insertspans'] = 'This tool will append translation hashes to all rich text fields found in the database, USE WITH EXTREME CARE.
To execute the task run this command again specifying a mode e.g:
php cli/insert_spans.php --mode=listcolumns

Valid modes are:
process - Actually do it...
listcolumns - List the tables and columns that will be impacted

Suggested use is to list out the columns that will could be modified:
php filter/translations/cli/insert_spans.php --mode=listcolumns > /Users/moodleadmin/cols.json

Edit the file using a text editor to remove any that should be ignored.
Then run to process those columns:

php filter/translations/cli/insert_spans.php --mode=process --file=/Users/moodleadmin/cols.json
';
$string['clihelptext_migrate_filter_fulltranslate'] = 'This tool will copy translations from the filter_fulltranslate plugin into the filter_translations plugin.
Any entries previously copied using this tool will be removed.
To execute the task run this command again specifying --confirm e.g:
php cli/migrate_filter_fulltranslate.php --confirm';
$string['columndefinitionfileerror'] = 'Missing or invalid column definition file';
$string['createtranslation'] = 'Create translation';
$string['edittranslation'] = 'Edit translation';
$string['edittranslationsbutton'] = 'Edit translation';
$string['filtername'] = 'Content translations';
$string['filteroptions'] = 'Filter options';
$string['foundhash'] = 'Found hash';
$string['generatedhash'] = 'Generated hash';
$string['google_apiendpoint'] = 'API Endpoint';
$string['google_apikey'] = 'API key';
$string['google_enable'] = 'Use Google Translate API';
$string['googletranslate'] = 'Google Translate';
$string['issue'] = 'Issue';
$string['issue_10'] = 'Stale';
$string['issue_20'] = 'Missing';
$string['languagestringreverse'] = 'Reverse look up language strings';
$string['languagestringreverse_enable'] = 'Enable reverse look up language strings';
$string['logdebounce'] = 'Debounce log duration';
$string['logging'] = 'Logging';
$string['logmissing'] = 'Log missing translations';
$string['logstale'] = 'Log stale translations';
$string['managetranslationissues'] = 'Manage translation issues';
$string['managetranslations'] = 'Manage translations';
$string['md5key'] = 'Translation hash key';
$string['nohash'] = 'No translation hash key found';
$string['notranslation'] = 'No translation found';
$string['privacy:metadata'] = 'The content translations plugin does not store any personal data.';
$string['rawtext'] = 'Original content';
$string['staletranslation'] = 'Translation was created based on different source text. Please update the translation.';
$string['startinlinetranslation'] = 'Start in-line translation';
$string['stopinlinetranslation'] = 'Stop in-line translation';
$string['substitutetext'] = 'Substitute text';
$string['targetlanguage'] = 'Translation language';
$string['translate_none'] = 'Translate - no translation exists';
$string['translate_stale'] = 'Translate - translation needs updating';
$string['translate_good'] = 'Translate - translation is up to date';
$string['translation'] = 'Translation';
$string['translationissuesinpagemissing'] = 'Missing on this page';
$string['translationissuesinpagestale'] = 'Stale on this page';
$string['translationdetails'] = 'Translation details';
$string['translationid'] = 'Translation ID';
$string['url'] = 'Page';
