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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/filter/translations/lib/diff/src/Output/DiffOutputBuilderInterface.php');
require_once($CFG->dirroot . '/filter/translations/lib/diff/src/Exception/Exception.php');
require_once($CFG->dirroot . '/filter/translations/lib/diff/src/Exception/InvalidArgumentException.php');
require_once($CFG->dirroot . '/filter/translations/lib/diff/src/Exception/ConfigurationException.php');
require_once($CFG->dirroot . '/filter/translations/lib/diff/src/Output/AbstractChunkOutputBuilder.php');
require_once($CFG->dirroot . '/filter/translations/lib/diff/src/Output/DiffOnlyOutputBuilder.php');
require_once($CFG->dirroot . '/filter/translations/lib/diff/src/Output/StrictUnifiedDiffOutputBuilder.php');
require_once($CFG->dirroot . '/filter/translations/lib/diff/src/Output/UnifiedDiffOutputBuilder.php');
require_once($CFG->dirroot . '/filter/translations/lib/diff/src/Chunk.php');
require_once($CFG->dirroot . '/filter/translations/lib/diff/src/Diff.php');
require_once($CFG->dirroot . '/filter/translations/lib/diff/src/Differ.php');
require_once($CFG->dirroot . '/filter/translations/lib/diff/src/Line.php');
require_once($CFG->dirroot . '/filter/translations/lib/diff/src/LongestCommonSubsequenceCalculator.php');
require_once($CFG->dirroot . '/filter/translations/lib/diff/src/MemoryEfficientLongestCommonSubsequenceCalculator.php');
require_once($CFG->dirroot . '/filter/translations/lib/diff/src/Parser.php');
require_once($CFG->dirroot . '/filter/translations/lib/diff/src/TimeEfficientLongestCommonSubsequenceCalculator.php');

use DOMDocument;
use SebastianBergmann\Diff\Differ;

/**
 * Wrapper for the unified diff generator https://github.com/sebastianbergmann/diff
 */
class unifieddiff {
    /**
     * Create unified diff of two pieces of HTML.
     *
     * @param string $original
     * @param string $new
     * @return string
     */
    public static function generatediff($original, $new) {
        $differ = new Differ;
        return $differ->diff(self::tidyhtml($original), self::tidyhtml($new));
    }

    /**
     * Reformat HTML to align handling of whitespace etc. to allow creation of a clean diff.
     *
     * @param $buffer
     * @return false|string
     */
    public static function tidyhtml($buffer) {
        $dom = new DOMDocument();
        $dom->preserveWhiteSpace = false;
        $dom->loadHTML('<?xml encoding="utf-8" ?>' . $buffer);
        $dom->formatOutput = true;
        $htmlinbodytags = $dom->saveHTML($dom->getElementsByTagName('body')->item(0));
        return substr($htmlinbodytags, '6', strlen($htmlinbodytags) - 13);
    }
}
