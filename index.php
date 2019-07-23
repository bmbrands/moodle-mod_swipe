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
 * List the swipe activities in a course.
 *
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = required_param('id', PARAM_INT);   // Course id.

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

require_course_login($course);

$event = \mod_swipe\event\course_module_instance_list_viewed::create(array(
    'context' => $coursecontext
));
$event->add_record_snapshot('course', $course);
$event->trigger();

$PAGE->set_url('/mod/swipe/index.php', array('id' => $id));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($coursecontext);

echo $OUTPUT->header();

        $userid = 6;
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

        $res = $DB->get_records_sql($sql, $params);

        echo '<pre>' . print_r($res, true) . '</pre>';

if (! $swipedecks = get_all_instances_in_course('swipe', $course)) {
    notice(get_string('noswipedecks', 'swipe'), new moodle_url('/course/view.php', array('id' => $course->id)));
}

$table = new html_table();
if ($course->format == 'weeks') {
    $table->head  = array(get_string('week'), get_string('name'));
    $table->align = array('center', 'left');
} else if ($course->format == 'topics') {
    $table->head  = array(get_string('topic'), get_string('name'));
    $table->align = array('center', 'left', 'left', 'left');
} else {
    $table->head  = array(get_string('name'));
    $table->align = array('left', 'left', 'left');
}

foreach ($swipedecks as $swipe) {
    if (!$swipe->visible) {
        $link = html_writer::link(
            new moodle_url('/mod/swipe/view.php', array('id' => $swipe->coursemodule)),
            format_string($swipe->name, true),
            array('class' => 'dimmed'));
    }

    if ($course->format == 'weeks' or $course->format == 'topics') {
        $table->data[] = array($swipe->section, $link);
    } else {
        $table->data[] = array($link);
    }
}

echo $OUTPUT->heading(get_string('modulenameplural', 'swipe'), 2);
echo html_writer::table($table);
echo $OUTPUT->footer();
