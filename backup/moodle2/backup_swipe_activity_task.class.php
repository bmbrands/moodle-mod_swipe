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
 * Backup activity for mod_swipe
 *
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/swipe/backup/moodle2/backup_swipe_stepslib.php');

/**
 * Backup activity for mod_swipe
 *
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_swipe_activity_task extends backup_activity_task {

    /**
     * Define (add) particular settings this activity can have
     */
    protected function define_my_settings() {
        // No particular settings for this activity.
    }

    /**
     * Define (add) particular steps this activity can have
     */
    protected function define_my_steps() {
        // Choice only has one structure step.
        $this->add_step(new backup_swipe_activity_structure_step('swipe_structure', 'swipe.xml'));
    }

    /**
     * Code the transformations to perform in the activity in
     * order to get transportable (encoded) links
     * @param string $content
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, "/");

        // Link to the list of pages.
        $search = "/(".$base."\/mod\/swipe\/index.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@SWIPEINDEX*$2@$', $content);

        // Link to page view by moduleid.
        $search = "/(".$base."\/mod\/swipe\/view.php\?id\=)([0-9]+)/";
        $content = preg_replace($search, '$@SWIPEVIEWBYID*$2@$', $content);

        return $content;
    }
}
