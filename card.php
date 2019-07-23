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
 * card editing page.
 *
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(dirname(dirname(dirname(__FILE__))).'/config.php');
require_once(dirname(__FILE__).'/card_form.php');
require_once($CFG->dirroot.'/repository/lib.php');

$s = optional_param('s', 0, PARAM_INT);
$i = optional_param('i', 0, PARAM_INT);
$bulk = optional_param('bulk', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT);

if (!$s) {
    print_error('missingparameter');
}

$cm  = get_coursemodule_from_instance('swipe', $s, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);
$course     = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);

if ($i) {
    $card = new \mod_swipe\card($i);
    if ($action == 'delete') {
        $card->delete();
        redirect(new moodle_url('/mod/swipe/view.php', ['id' => $cm->id, 'editing' => 1]));
    }
} else {
    $card = false;
}

$swipedeck = new \mod_swipe\swipedeck($cm, $context);

require_login($course, true, $cm);

$pageurl = new moodle_url('/mod/swipe/card.php', array('s' => $s));

$PAGE->set_url($pageurl);
$PAGE->set_title(format_string($cm->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

if ($swipedeck) {
    $pageurl = new moodle_url('/mod/swipe/view.php', array('id' => $cm->instance));

    $navnode = $PAGE->navigation->find($cm->id, navigation_node::TYPE_ACTIVITY);
    if (empty($navnode)) {
        $navnode = $PAGE->navbar;
    }
    $node = $navnode->add(format_string($swipedeck->name), $pageurl);
    $node->make_active();
}

$mform = new mod_swipe_card_form(null,
    array('swipedeck' => $swipedeck, 'card' => $card));

$fs = get_file_storage();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/mod/swipe/view.php', array('id' => $cm->id, 'editing' => 1)));
} else if ($data = $mform->get_data()) {
    if ($bulk) {
        $draftid = file_get_submitted_draft_cardid('content');
        $files = $fs->get_area_files(context_user::instance($USER->id)->id, 'user', 'draft', $draftid, 'id DESC', false);
        $storedfile = reset($files);
        \mod_swipe\card::create_from_archive($swipedeck, $storedfile, $data);
    } else {
        $data->description = $data->description['text'];
        $data->swipeid = $swipedeck->cm->instance;

        if ($data->cardid) {
            $card = new \mod_swipe\card($data->cardid);
            $card->update($data, $context);
        } else {
            if ($data->contenttype == 'text') {
                $data->itemtype = 3;
            }
            if ($data->contenttype == 'video') {
                $data->itemtype = 2;
            }
            if ($data->contenttype == 'image') {
                $data->itemtype = 1;
            }
            $card = \mod_swipe\card::create($data, $context);
        }

        if ($data->contenttype == 'image') {
            $info = file_get_draft_area_info($data->content);
            $pickeroptions = array(
                'maxbytes' => $CFG->maxbytes,
                'maxfiles' => 1,
                'return_types' => FILE_INTERNAL | FILE_REFERENCE,
                'subdirs' => false,
            );
            file_save_draft_area_files($data->content, $context->id, 'mod_swipe', 'card', $card->record->id, $pickeroptions);
        }
    }

    redirect(new moodle_url('/mod/swipe/view.php', ['id' => $cm->id, 'editing' => 1]));
} else if ($card) {
    $data = $card->record;

    $draftcardid = file_get_submitted_draft_itemid('content');
    file_prepare_draft_area($draftcardid, $context->id, 'mod_swipe', 'card', $data->id);

    $draftideditor = file_get_submitted_draft_itemid('description');
    $currenttext = file_prepare_draft_area($draftideditor, $context->id, 'mod_swipe',
            'description', empty($data->id) ? null : $data->id,
            array('subdirs' => 0), empty($data->description) ? '' : $data->description);

    $data->content = $draftcardid;
    $data->description = array('text' => $currenttext,
                           'format' => editors_get_preferred_format(),
                           'cardid' => $draftideditor);

    $mform->set_data($data);
}

$maxcards = $swipedeck->maxcards;
if (!$card && $maxcards != 0 && count($swipedeck->getcards()) >= $maxcards) {
    print_error('errortoomanycards', 'swipe', '', $maxcards);
}

echo $OUTPUT->header();

$mform->display();

echo $OUTPUT->footer();
