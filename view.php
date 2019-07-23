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
 * Prints a particular instance of swipe
 *
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/lib.php');

$id = optional_param('id', 0, PARAM_INT); // A course_module id.
$editing = optional_param('editing', 0, PARAM_INT);
$page = optional_param('page', 0, PARAM_ALPHA);

if ($id) {
    $cm         = get_coursemodule_from_id('swipe', $id, 0, false, MUST_EXIST);
    $course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
}

$context = context_module::instance($cm->id);

$swipedeck = new \mod_swipe\swipedeck($cm, $context);

$pageurl = new moodle_url('/mod/swipe/view.php', array('id' => $id, 'page' => 0));
$PAGE->set_cm($cm, $course);
$PAGE->set_url($pageurl);
require_login($course, true, $cm);

$renderer = $PAGE->get_renderer('mod_swipe');

if ($page === 'exportxls') {
    $renderer->view_cards_report_xls($swipedeck);
    exit(0);
}

echo $OUTPUT->header(null, true);

if ($editing) {
    echo $renderer->edit_cards($swipedeck);
} else if ($page === 'report') {
    echo $renderer->view_cards_report($swipedeck);
} else {
    echo $renderer->view_cards($swipedeck);
}

echo $OUTPUT->footer();
