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
 * Privacy Subsystem implementation for mod_swipe.
 *
 * @package    mod_swipe
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_swipe\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Implementation of the privacy subsystem plugin provider for the swipe activity module.
 *
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    // This plugin stores personal data.
    \core_privacy\local\metadata\provider,
    // This plugin is a core_user_data_provider.
    \core_privacy\local\request\plugin\provider,

    \core_privacy\local\request\core_userlist_provider {

    /**
     * Return the fields which contain personal data.
     *
     * @param collection $items a reference to the collection to use to store the metadata.
     * @return collection the updated collection of metadata items.
     */
    public static function get_metadata(collection $items) : collection {

        $items->add_database_table(
            'swipe_userfeedback',
            [
                'cardid' => 'privacy:metadata:swipe_userfeedback:cardid',
                'userid' => 'privacy:metadata:swipe_userfeedback:userid',
                'liked' => 'privacy:metadata:swipe_userfeedback:liked',
                'swipeid' => 'privacy:metadata:swipe_userfeedback:swipeid'
            ],
            'privacy:metadata:swipe_userfeedback'
        );

        $items->add_database_table(
            'swipe_swipefeedback',
            [
                'userid' => 'privacy:metadata:swipe_swipefeedback:userid',
                'swipeid' => 'privacy:metadata:swipe_swipefeedback:swipeid',
                'feedback' => 'privacy:metadata:swipe_swipefeedback:feedback',
                'timecreated' => 'privacy:metadata:swipe_swipefeedback:timecreated'
            ],
            'privacy:metadata:swipe_swipefeedback'
        );

        return $items;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid the userid.
     * @return contextlist the list of contexts containing user info for the user.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        // Fetch all swipe comments.

        $sql = "
            SELECT DISTINCT ctx.id
              FROM {swipe} s
              JOIN {modules} m
                ON m.name = :swipe
              JOIN {course_modules} cm
                ON cm.instance = s.id
               AND cm.module = m.id
              JOIN {context} ctx
                ON ctx.instanceid = cm.id
               AND ctx.contextlevel = :modulelevel
         LEFT JOIN {swipe_item} swi
                ON swi.swipeid = s.id
         LEFT JOIN {swipe_userfeedback} swu
                ON swu.cardid = swi.id
               AND swu.userid = :userid1
         LEFT JOIN {swipe_swipefeedback} swf
                ON swf.swipeid = s.id
               AND swf.userid = :userid2
             WHERE swi.id IS NOT NULL
                OR swu.id IS NOT NULL
                OR swf.id IS NOT NULL";

        $params = [
            'swipe' => 'swipe',
            'modulelevel' => CONTEXT_MODULE,
            'userid1'      => $userid,
            'userid2'      => $userid
        ];
        $contextlist = new contextlist();
        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Get the list of users who have data within a context.
     *
     * @param   userlist    $userlist   The userlist containing the list of users who have data in this context/plugin combination.
     *
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();

        if (!is_a($context, \context_module::class)) {
            return;
        }

        $params = [
            'modulename' => 'swipe',
            'modulelevel' => CONTEXT_MODULE,
            'instanceid'    => $context->instanceid,
        ];

        $sql = "SELECT d.userid
              FROM {course_modules} cm
              JOIN {modules} m ON m.id = cm.module AND m.name = :modulename
              JOIN {swipe} s ON s.id = cm.instance
              JOIN {swipe_item} swi
                ON swi.swipeid = s.id
         LEFT JOIN {swipe_userfeedback} swu
                ON swu.cardid = swi.id
             WHERE cm.id = :instanceid";

        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export personal data for the given approved_contextlist. User and context information is contained within the contextlist.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for export.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT cm.id AS cmid,
                       swu.liked as liked,
                       swi.caption,
                       swf.feedback
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {swipe} sw ON sw.id = cm.instance
            INNER JOIN {swipe_item} swi ON swi.swipeid = sw.id
            INNER JOIN {swipe_userfeedback} swu ON swu.cardid = swi.id
            INNER JOIN {swipe_swipefeedback} swf ON swf.swipeid = sw.id
                 WHERE c.id {$contextsql}
                       AND swu.userid = :userid
                       AND swf.userid = :userid2
              ORDER BY cm.id";

        $params = ['modname' => 'swipe', 'contextlevel' => CONTEXT_MODULE,
            'userid' => $user->id, 'userid2' => $user->id] + $contextparams;

        $lastcmid = null;
        $userfeedback = $DB->get_recordset_sql($sql, $params);
        $index = 0;
        foreach ($userfeedback as $feedback) {
            // If we've moved to a new choice, then write the last choice data and reinit the choice data array.
            if ($lastcmid != $feedback->cmid) {
                if (!empty($feedbackdata)) {
                    $context = \context_module::instance($lastcmid);
                    self::export_feedback_data_for_user($feedbackdata, $context, $user);
                }
                $feedbackdata = [
                    'liked' => [],
                    'feedback' => $feedback->feedback
                ];
            }
            $index++;
            $feedbackdata['liked'][$index . ' ' . $feedback->caption] = $feedback->liked;
            $lastcmid = $feedback->cmid;
        }
        $userfeedback->close();

        // The data for the last activity won't have been written yet, so make sure to write it now!
        if (!empty($feedbackdata)) {
            $context = \context_module::instance($lastcmid);
            self::export_feedback_data_for_user($feedbackdata, $context, $user);
        }
    }

    /**
     * Export the supplied personal data for a single choice activity, along with any generic data or area files.
     *
     * @param array $feedbackdata the personal data to export for the choice.
     * @param \context_module $context the context of the choice.
     * @param \stdClass $user the user record
     */
    protected static function export_feedback_data_for_user(array $feedbackdata, \context_module $context, \stdClass $user) {
        // Fetch the generic module data for the choice.
        $contextdata = helper::get_context_data($context, $user);

        // Merge with choice data and write it.
        $contextdata = (object)array_merge((array)$contextdata, $feedbackdata);
        writer::with_context($context)->export_data([], $contextdata);

        // Write generic module intro files.
        helper::export_context_files($context, $user);
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context the context to delete in.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if (!$context instanceof \context_module) {
            return;
        }

        if ($cm = get_coursemodule_from_id('swipe', $context->instanceid)) {
            $DB->delete_records('swipe_userfeedback', ['swipeid' => $cm->instance]);
            $DB->delete_records('swipe_swipefeedback', ['swipeid' => $cm->instance]);
        }
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist a list of contexts approved for deletion.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {

            if (!$context instanceof \context_module) {
                continue;
            }
            $instanceid = $DB->get_field('course_modules', 'instance', ['id' => $context->instanceid]);
            if (!$instanceid) {
                continue;
            }
            $DB->delete_records('swipe_userfeedback', ['swipeid' => $instanceid, 'userid' => $userid]);
            $DB->delete_records('swipe_swipefeedback', ['swipeid' => $instanceid, 'userid' => $userid]);
        }
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param   approved_userlist       $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();

        if (!$context instanceof \context_module) {
            return;
        }

        $cm = get_coursemodule_from_id('swipe', $context->instanceid);

        if (!$cm) {
            // Only choice module will be handled.
            return;
        }

        $userids = $userlist->get_userids();
        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);

        $select = "swipeid = :swipeid AND userid $usersql";
        $params = ['swipeid' => $cm->instance] + $userparams;
        $DB->delete_records_select('swipe_userfeedback', $select, $params);
        $DB->delete_records_select('swipe_swipefeedback', $select, $params);
    }
}
