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
 * Restore steps for mod_swipe
 *
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Restore steps for mod_swipe
 *
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_swipe_activity_structure_step extends restore_activity_structure_step {

    /**
     * Define the structure of the restore steps.
     */
    protected function define_structure() {

        $paths = array();
        $userinfo = $this->get_setting_value('userinfo');

        $swipe = new restore_path_element('swipe', '/activity/swipe');
        $paths[] = $swipe;

        $item = new restore_path_element('swipe_item', '/activity/swipe/items/item');
        $paths[] = $item;

        if ($userinfo) {
            $userfeedback = new restore_path_element('swipe_userfeedback',
                '/activity/swipe/items/item/userfeedback/ufeedback');
            $paths[] = $userfeedback;

            $swipefeedback = new restore_path_element('swipe_userfeedback',
                '/activity/swipe/swipefeedback/sfeedback');
            $paths[] = $swipefeedback;
        }

        // Return the paths wrapped into standard activity structure.
        return $this->prepare_activity_structure($paths);
    }

    /**
     * Process the swipe backup data.
     *
     * @param array $data backup data.
     */
    protected function process_swipe($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();
        $data->timemodified = $this->apply_date_offset($data->timemodified);

        // Insert the swipe record.
        $newitemid = $DB->insert_record('swipe', $data);
        // Immediately after inserting "activity" record, call this.
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Process the userfeedback backup data.
     *
     * @param array $data backup data.
     */
    protected function process_swipe_userfeedback($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->cardid = $this->get_new_parentid('swipe_item');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $newfeedbackid = $DB->insert_record('swipe_userfeedback', $data);
    }

    /**
     * Process the swipfeedback backup data.
     *
     * @param array $data backup data.
     */
    protected function process_swipe_swipefeedback($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->swipeid = $this->get_new_parentid('swipe');
        $data->userid = $this->get_mappingid('user', $data->userid);
        $newfeedbackid = $DB->insert_record('swipe_userfeedback', $data);
    }

    /**
     * Process a swie item.
     *
     * @param array $data backup data.
     */
    protected function process_swipe_item($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;

        $data->swipeid = $this->get_new_parentid('swipe');
        $data->timecreated = $this->apply_date_offset($data->timecreated);
        $data->userid = $this->get_mappingid('user', $data->userid);
        $newitemid = $DB->insert_record('swipe_item', $data);
        $this->set_mapping('swipe_item', $oldid, $newitemid, true);
    }

    /**
     * Steps after restore.
     *
     */
    protected function after_execute() {
        global $DB;

        // Can't do thumbnail mapping before the item is restored, so we do it here.
        $mgid = $this->task->get_activityid();
        $this->add_related_files('mod_swipe', 'intro', null);
        $this->add_related_files('mod_swipe', 'card', 'swipe_item');
    }
}
