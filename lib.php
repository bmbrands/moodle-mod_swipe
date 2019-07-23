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
 * Returns a small object with summary information about what a
 * user has done with a given particular instance of this module
 * Used for user activity reports.
 * $return->time = the time they did it
 * $return->info = a short text description
 *
 * @param object $course
 * @param object $user
 * @param object $mod
 * @param object $swipe
 * @return stdClass|null
 */
function swipe_user_outline($course, $user, $mod, $swipe) {

    $return = new stdClass();
    $return->time = 0;
    $return->info = '';
    return $return;
}

/**
 * Prints a detailed representation of what a user has done with
 * a given particular instance of this module, for user activity reports.
 *
 * @param stdClass $course the current course record
 * @param stdClass $user the record of the user we are generating report for
 * @param cm_info $mod course module info
 * @param stdClass $swipe the module instance record
 * @return void, is supposed to echp directly
 */
function swipe_user_complete($course, $user, $mod, $swipe) {
}

/**
 * Given a course and a time, this module should find recent activity
 * that has occurred in swipe activities and print it out.
 * Return true if there was output, or false is there was none.
 *
 * @param object $course
 * @param bool $viewfullnames capability
 * @param int $timestart
 * @return boolean
 */
function swipe_print_recent_activity($course, $viewfullnames, $timestart) {
    return false;
}

/**
 * Prepares the recent activity data
 *
 * This callback function is supposed to populate the passed array with
 * custom activity records. These records are then rendered into HTML via
 * {@link swipe_print_recent_mod_activity()}.
 *
 * @param array $activities sequentially indexed array of objects with the 'cmid' property
 * @param int $index the index in the $activities to use for the next record
 * @param int $timestart append activity since this time
 * @param int $courseid the id of the course we produce the report for
 * @param int $cmid course module id
 * @param int $userid check for a particular user's activity only, defaults to 0 (all users)
 * @param int $groupid check for a particular group's activity only, defaults to 0 (all groups)
 * @return void adds items into $activities and increases $index
 */
function swipe_get_recent_mod_activity(&$activities, &$index, $timestart, $courseid, $cmid, $userid=0, $groupid=0) {
}

/**
 * Prints single activity item prepared by swipe_get_recent_mod_activity.
 *
 * @see swipe_get_recent_mod_activity()
 * @param stdClass $activity
 * @param int $courseid
 * @param bool $detail
 * @param array $modnames
 * @param object $viewfullnames
 * @return void
 */
function swipe_print_recent_mod_activity($activity, $courseid, $detail, $modnames, $viewfullnames) {
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @return boolean
 * @todo Finish documenting this function
 **/
function swipe_cron () {
    return false;
}

/**
 * Returns all other caps used in the module
 *
 * @return array
 */
function swipe_get_extra_capabilities() {
    return array();
}

// Gradebook API.

/**
 * Is a given scale used by the instance of swipe?
 *
 * This function returns if a scale is being used by one swipe
 * if it has support for grading and scales. Commented code should be
 * modified if necessary. See forum, glossary or journal modules
 * as reference.
 *
 * @param int $swipeid ID of an instance of this module
 * @param int $scaleid
 * @return bool true if the scale is used by the given swipe instance
 */
function swipe_scale_used($swipeid, $scaleid) {
    return false;
}

/**
 * Checks if scale is being used by any instance of swipe.
 *
 * This is used to find out if scale used anywhere.
 *
 * @param int $scaleid
 * @return boolean true if the scale is used by any swipe instance
 */
function swipe_scale_used_anywhere($scaleid) {
    return false;
}

/**
 * Creates or updates grade item for the give swipe instance
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $swipe instance object with extra cmidnumber and modname property
 * @return void
 */
function swipe_grade_item_update(stdClass $swipe) {
    global $CFG;
    return;
}

/**
 * Update swipe grades in the gradebook
 *
 * Needed by grade_update_mod_grades() in lib/gradelib.php
 *
 * @param stdClass $swipe instance object with extra cmidnumber and modname property
 * @param int $userid update grade of specific user only, 0 means all participants
 * @return void
 */
function swipe_update_grades(stdClass $swipe, $userid = 0) {
    global $CFG, $DB;
    return;
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
