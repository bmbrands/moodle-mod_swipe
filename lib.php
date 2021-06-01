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
 * Library of interface functions and constants for module swipe
 *
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Returns the information on whether the module supports a feature
 *
 * @see plugin_supports() in lib/moodlelib.php
 * @param string $feature FEATURE_xx constant for requested feature
 * @return mixed true if the feature is supported, null if unknown
 */
function swipe_supports($feature) {
    switch($feature) {
        case FEATURE_GROUPS:
            return false;
        case FEATURE_GROUPINGS:
            return false;
        case FEATURE_GROUPMEMBERSONLY:
            return false;
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_COMPLETION_TRACKS_VIEWS:
            return true;
        case FEATURE_COMPLETION_HAS_RULES:
            return false;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_GRADE_OUTCOMES:
            return false;
        case FEATURE_RATE:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_PLAGIARISM:
            return false;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the swipe into the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param stdClass $swipe An object from the form in mod_form.php
 * @param mod_swipe_mod_form $mform
 * @return int The id of the newly inserted swipe record
 */
function swipe_add_instance(stdClass $swipe, mod_swipe_mod_form $mform = null) {
    global $DB, $USER;

    $swipe->timecreated = time();

    $swipe->id = $DB->insert_record('swipe', $swipe);

    return $swipe->id;
}

/**
 * Updates an instance of the swipe in the database
 *
 * Given an object containing all the necessary data,
 * (defined by the form in mod_form.php) this function
 * will update an existing instance with new data.
 *
 * @param stdClass $swipe An object from the form in mod_form.php
 * @param mod_swipe_mod_form $mform
 * @return boolean Success/Fail
 */
function swipe_update_instance(stdClass $swipe, mod_swipe_mod_form $mform = null) {
    global $DB;

    $swipe->timemodified = time();
    $swipe->id = $swipe->instance;

    $result = $DB->update_record('swipe', $swipe);

    return $result;
}

/**
 * Removes an instance of the swipe from the database
 *
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance
 * and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 */
function swipe_delete_instance($id) {
    global $DB;

    if (! $swipe = $DB->get_record('swipe', array('id' => $id))) {
        return false;
    }

    // Todo.
    $collection = new \mod_swipe\collection($swipe);
    $collection->delete();

    return true;
}

/**
 * Serves the files from the swipe file areas
 *
 * @package mod_swipe
 * @category files
 *
 * @param stdClass $course the course object
 * @param stdClass $cm the course module object
 * @param stdClass $context the swipe's context
 * @param string $filearea the name of the file area
 * @param array $args extra arguments (itemid, path)
 * @param bool $forcedownload whether or not force download
 * @param array $options additional options affecting the file serving
 */
function swipe_pluginfile($course, $cm, $context, $filearea, array $args, $forcedownload, array $options=array()) {
    global $DB, $CFG;

    if ($context->contextlevel != CONTEXT_MODULE) {
        send_file_not_found();
    }

    require_login($course, true, $cm);

    $itemid = (int)array_shift($args);

    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/mod_swipe/$filearea/$itemid/$relativepath";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }

    send_stored_file($file, 0, 0, $forcedownload, $options); // Download MUST be forced - security!
}
