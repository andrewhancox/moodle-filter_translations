# To Install it manually #
- Unzip the plugin in the moodle .../filter/ directory.

# To Enable it #
- Go to "Site Administration &gt;&gt; Plugins &gt;&gt; Filters &gt;&gt; Manage filters" and enable the plugin there.

# To Use it #
Users with the 'filter/translations:edittranslations' capability will see an icon in the top right hand corner of the screen to enable the translator view of the course. At this point all translatable text will have an icon injected next to it to allow it to be translated.

# To migrate from filter_fulltranslate #
A CLI tool is available to migrate all translations across from the filter_fulltranslate, this can run as follows:

It is recommended that you clean out any unwanted translations that may have been generated as follows:
````
delete from mdl_filter_fulltranslate where sourcetext like '%{mlang%';
````

You can then copy the translations from filter_fulltranslate into filter_translations as follows:
````
php cli/migrate_filter_fulltranslate.php --confirm
````

Author
------

The module has been written and is currently maintained by Andrew Hancox on behalf of [Open Source Learning](https://opensourcelearning.co.uk).

Useful links
------------

* [Open Source Learning](https://opensourcelearning.co.uk)
* [Bug tracker](https://github.com/andrewhancox/moodle-filter_translations/issues)

License
-------

This program is free software: you can redistribute it and/or modify it under the
terms of the GNU General Public License as published by the Free Software Foundation,
either version 3 of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful, but WITHOUT ANY
WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
PARTICULAR PURPOSE.  See the GNU General Public License for more details.

You should have received a copy of the GNU General Public License along with this
program. If not, see <http://www.gnu.org/licenses/>.
