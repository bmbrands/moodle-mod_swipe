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
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute swipe upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_swipe_upgrade($oldversion) {
    global $CFG, $DB;

    $dbman = $DB->get_manager();

    if ($oldversion < 2019071902) {

        // Rename field swipeid on table swipe_userfeedback to NEWNAMEGOESHERE.
        $table = new xmldb_table('swipe_userfeedback');
        $field = new xmldb_field('rating', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'liked');

        // Launch rename field swipeid.
        $dbman->rename_field($table, $field, 'swipeid');

        // Swipe savepoint reached.
        upgrade_mod_savepoint(true, 2019071902, 'swipe');
    }

    return true;
}
