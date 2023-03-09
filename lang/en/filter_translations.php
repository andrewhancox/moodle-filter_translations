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

$string['bulkdeleteconfirmation'] = 'Are you absolutely sure you want to completely delete these translations?';
$string['cachingmode'] = 'Caching mode';
$string['cachingmode_desc'] = 'For sites with a relatively small number of courses and high volume of users we recommend Application level, for high number of courses we recommend Session, to minimise caching we recommend Request.';
$string['cachedef_translatedtext_1'] = 'Translated text (Request level)';
$string['cachedef_translatedtext_2'] = 'Translated text (Session level)';
$string['cachedef_translatedtext_4'] = 'Translated text (Application level)';
$string['cannottranslatelang'] = 'Translation in the selected language is disabled.';
$string['clihelptext_copytranslations'] = 'This tool will copy over translations matching the hash of a content and save it under the found hash of the content for each specified table/field. USE WITH EXTREME CARE.
To execute the task run this command again specifying a mode e.g:
php cli/copy_translations.php --mode=listcolumns

Valid modes are:
process - Actually do it...
dryrun - See how many records will be affected. It does not write any changes to the database.
listcolumns - List the tables and columns that will be impacted

Suggested use is to list out the columns that will could be modified:
php filter/translations/cli/copy_translations.php --mode=listcolumns > /Users/moodleadmin/cols.json

Edit the file using a text editor to remove any that should be ignored.
Then run to process those columns:

php filter/translations/cli/copy_translations.php --mode=process --file=/Users/moodleadmin/cols.json

You can do a dryrun to check how many records will be affected when this process runs.
php filter/translations/cli/copy_translations.php --mode=dryrun --file=/Users/moodleadmin/cols.json
';
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
Any entries previously copied using this tool will be duplicated.
To execute the task run this command again specifying --confirm e.g:
php cli/migrate_filter_fulltranslate.php --confirm';
$string['clihelptext_removeduplicatehashes'] = 'This tool will find and remove duplicate translation hashes found in each specified table/field. USE WITH EXTREME CARE.
To execute the task run this command again specifying a mode e.g:
php cli/remove_duplicate_hashes.php --mode=listcolumns

Valid modes are:
process - Actually do it...
dryrun - See how many records will be affected. It does not write any changes to the database.
listcolumns - List the tables and columns that will be impacted

Suggested use is to list out the columns that will could be modified:
php filter/translations/cli/remove_duplicate_hashes.php --mode=listcolumns > /Users/moodleadmin/cols.json

Edit the file using a text editor to remove any that should be ignored.
Then run to process those columns:

php filter/translations/cli/remove_duplicate_hashes.php --mode=process --file=/Users/moodleadmin/cols.json

You can do a dryrun to check how many records will be affected when this process runs.
php filter/translations/cli/remove_duplicate_hashes.php --mode=dryrun --file=/Users/moodleadmin/cols.json
';
$string['columndefinitionfileerror'] = 'Missing or invalid column definition file';
$string['context'] = 'Context';
$string['createtranslation'] = 'Create translation';
$string['current'] = 'Current';
$string['deleteissuesconfirmation'] = 'Are you absolutely sure you want to completely delete these entires?';
$string['deleteselected'] = 'Delete selected translations';
$string['deleteselectedentries'] = 'Delete selected entries';
$string['diff'] = 'Diff';
$string['edittranslation'] = 'Edit translation';
$string['edittranslationsbutton'] = 'Edit translation';
$string['excludelang'] = 'Languages to exclude from translation';
$string['excludelang_desc'] = 'List of languages to entirely exclude from translation.';
$string['filtername'] = 'Content translations';
$string['filteroptions'] = 'Filter options';
$string['foundhash'] = 'Found hash';
$string['generatedhash'] = 'Generated hash';
$string['google_apiendpoint'] = 'API Endpoint';
$string['google_apikey'] = 'API key';
$string['google_backoffonerror'] = 'Back off from erroring API';
$string['google_enable'] = 'Use Google Translate API';
$string['googletranslate'] = 'Google Translate';
$string['hash'] = 'Hash';
$string['issue'] = 'Status';
$string['issue_10'] = 'Stale';
$string['issue_20'] = 'Missing';
$string['languagestringreverse'] = 'Reverse look up language strings';
$string['languagestringreverse_enable'] = 'Enable reverse look up language strings';
$string['logdebounce'] = 'Debounce log duration';
$string['logging'] = 'Logging';
$string['logexcludelang'] = 'Languages to exclude from log';
$string['logexcludelang_desc'] = 'List of languages to skip from logging into missing translations table.';
$string['loghistory'] = 'Keep translation history';
$string['logmissing'] = 'Log missing translations';
$string['logstale'] = 'Log stale translations';
$string['managetranslationissues'] = 'Manage pending translations';
$string['managetranslations'] = 'Manage translations';
$string['md5key'] = 'Translation hash key';
$string['nohash'] = 'No translation hash key found';
$string['notranslation'] = 'No translation found';
$string['old'] = 'Old content';
$string['pluginname'] = 'Content translations';
$string['privacy:metadata'] = 'The content translations plugin does not store any personal data.';
$string['rawtext'] = 'Original content';
$string['rawhtml'] = 'Original HTML';
$string['sameasrawcontent'] = 'Same text as original content';
$string['sameasrawcontentmessage'] = 'Translated content is the same as original content';
$string['showperfdata'] = 'Show performance data in footer';
$string['staletranslation'] = 'Translation was created based on different source text. Please update the translation.';
$string['startinlinetranslation'] = 'Start in-line translation';
$string['stopinlinetranslation'] = 'Stop in-line translation';
$string['substitutetext'] = 'Translated content';
$string['targetlanguage'] = 'Translation language';
$string['translate_none'] = 'Translate - no translation exists';
$string['translate_stale'] = 'Translate - translation needs updating';
$string['translate_good'] = 'Translate - translation is up to date';
$string['translatedby'] = 'Translated by';
$string['translation'] = 'Translation';
$string['translations'] = 'Translations';
$string['translationalreadyexists'] = 'Translation cannot be saved. A Translation for language "{$a}" already exists.';
$string['translationissuesinpagemissing'] = 'Missing on this page';
$string['translationissuesincoursemissing'] = 'Missing in this course';
$string['translationissuesinpagemissingall'] = 'All missing translations';
$string['translationissuesinpagestale'] = 'Stale on this page';
$string['translationissuesincoursestale'] = 'Stale in this course';
$string['translationissuesinpagestaleall'] = 'All stale translations';
$string['translationdetails'] = 'Translation details';
$string['translationid'] = 'Translation ID';
$string['translationissues'] = 'Translation issues';
$string['translations:bulkdeletetranslations'] = 'Bulk delete translations';
$string['translations:deletetranslations'] = 'Delete translations';
$string['translations:edittranslationhashkeys'] = 'Edit hash keys';
$string['translations:editsitedefaulttranslations'] = 'Edit site default language translations';
$string['translations:edittranslations'] = 'Edit translations';
$string['unknownformtype'] = 'Unknown form type';
$string['untranslatedpages'] = 'Pages to leave untranslated';
$string['untranslatedpages_desc'] = 'One per line.';
$string['url'] = 'Page';
$string['userid'] = 'User ID';
