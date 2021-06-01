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
 * Backup steps for mod_swipe
 *
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Backup steps for mod_swipe
 *
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_swipe_activity_structure_step extends backup_activity_structure_step {

    /**
     * Get the structure of the backup.
     */
    protected function define_structure() {

        // To know if we are including userinfo.
        $userinfo = $this->get_setting_value('userinfo');

        // Define each element separated.
        $swipe = new backup_nested_element('swipe', array('id'), array(
            'course', 'name', 'intro', 'introformat', 'timecreated', 'timemodified'
        ));

        $userfeedbacks = new backup_nested_element('userfeedback');
        $userfeedback = new backup_nested_element('ufeedback', array('id'), array(
            'cardid', 'userid', 'liked', 'swipeid'
        ));

        $swipefeedbacks = new backup_nested_element('swipefeedback');
        $swipefeedback = new backup_nested_element('sfeedback', array('id'), array(
            'userid', 'swipeid', 'feedback', 'timecreated'
        ));

        $items = new backup_nested_element('items');
        $item = new backup_nested_element('item', array('id'), array(
            'swipeid', 'caption', 'description', 'sortorder', 'externalurl',
            'itemtype', 'timecreated'
        ));

        // Build the tree.
        $swipe->add_child($items);
        $items->add_child($item);

        $item->add_child($userfeedbacks);
        $userfeedbacks->add_child($userfeedback);

        $swipe->add_child($swipefeedbacks);
        $swipefeedbacks->add_child($swipefeedback);

        // Define sources.
        $swipe->set_source_table('swipe', array('id' => backup::VAR_ACTIVITYID));
        $item->set_source_table('swipe_item', array('swipeid' => backup::VAR_PARENTID));

        // All the rest of elements only happen if we are including user info.
        if ($userinfo) {
            $userfeedback->set_source_table('swipe_userfeedback', array('cardid' => backup::VAR_PARENTID));
            $userfeedback->set_source_table('swipe_swipefeedback', array('swipeid' => backup::VAR_ACTIVITYID));
        }

        // Define file annotations.
        $swipe->annotate_files('mod_swipe', 'card', null);

        $userfeedback->annotate_ids('user', 'userid');

        $swipefeedback->annotate_ids('user', 'userid');

        // Return the root element (swipe), wrapped into standard activity structure.
        return $this->prepare_activity_structure($swipe);
    }
}
