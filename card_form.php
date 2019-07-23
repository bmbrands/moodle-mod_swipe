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
 * Form for creating/editing an item.
 *
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/formslib.php');
require_once($CFG->dirroot.'/mod/swipe/classes/quickform/limitedurl.php');
require_once($CFG->dirroot.'/mod/swipe/classes/quickform/uploader.php');
require_once($CFG->dirroot.'/mod/swipe/classes/quickform/uploader_standard.php');

/**
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_swipe_card_form extends moodleform {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $swipedeck = $this->_customdata['swipedeck'];
        $card = $this->_customdata['card'];

        $cardid = $card ? $card->record->id : 0;

        $mform->addElement('hidden', 'cardid', $cardid);
        $mform->setType('cardid', PARAM_INT);

        $mform->addElement('hidden', 's', $swipedeck->cm->instance);
        $mform->setType('s', PARAM_INT);

        // General settings.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        $mform->addElement('text', 'caption', get_string('caption', 'swipe'), array('size' => '64'));
        $mform->setType('caption', PARAM_TEXT);
        $mform->addRule('caption', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('caption', 'caption', 'swipe');

        $type = '';
        if ($card) {
            $type = $card->type();
            if (empty($type)) {
                $type = 3;
            }
            $mform->addElement('hidden', 'itemtype', $type);
        } else {
            $mform->addElement('hidden', 'itemtype', 0);
        }
        $mform->setType('itemtype', PARAM_RAW);

        $options = array(
            'text' => get_string('contenttype_text', 'swipe'),
            'image' => get_string('contenttype_image', 'swipe'),
            'video' => get_string('contenttype_video', 'swipe')
        );

        $pickeroptions = array(
            'maxbytes' => $CFG->maxbytes,
            'maxfiles' => 1,
            'return_types' => FILE_INTERNAL | FILE_REFERENCE,
            'subdirs' => false,
        );

        if ($card) {
            if ($type == 1) {
                $mform->addElement('hidden', 'contenttype', 'image');
                $mform->addElement('uploader_standard', 'content', get_string('contenttype_image', 'swipe'), '0',
                $pickeroptions);
            }
            if ($type == 2) {
                $mform->addElement('hidden', 'contenttype', 'video');
                $mform->addElement('limitedurl', 'externalurl', get_string('youtubeurl', 'swipe'), array('size' => '60'),
                array('usefilepicker' => true, 'repo' => 'youtube'));
                $mform->setType('externalurl', PARAM_TEXT);

            }
            if ($type == 3) {
                $mform->addElement('hidden', 'contenttype', 'text');
                $mform->addElement('editor', 'description', get_string('contenttype_text', 'swipe'), null, $options);
            }
            $mform->setType('contenttype', PARAM_ALPHA);
        } else {
            $mform->addElement('select', 'contenttype', get_string('contenttype', 'swipe'), $options);

            // Text type.
            $mform->addElement('editor', 'description', get_string('contenttype_text', 'swipe'), null, $options);
            $mform->disabledIf('description', 'contenttype', 'eq', 'video');
            $mform->disabledIf('description', 'contenttype', 'eq', 'image');

            // Image type.
            $mform->addElement('uploader_standard', 'content', get_string('contenttype_image', 'swipe'), '0',
                $pickeroptions);
            $mform->addHelpButton('content', 'content', 'swipe');
            $mform->disabledIf('content', 'contenttype', 'eq', 'video');
            $mform->disabledIf('content', 'contenttype', 'eq', 'text');

            // Video type.
            $mform->addElement('limitedurl', 'externalurl', get_string('youtubeurl', 'swipe'), array('size' => '60'),
                array('usefilepicker' => true, 'repo' => 'youtube'));
            $mform->setType('externalurl', PARAM_TEXT);
            $mform->addHelpButton('externalurl', 'externalurl', 'swipe');
            $mform->disabledIf('externalurl', 'contenttype', 'eq', 'image');
            $mform->disabledIf('externalurl', 'contenttype', 'eq', 'text');
        }

        $this->add_action_buttons();
    }

    /**
     * Validate user input.
     *
     * @param mixed $data The submitted form data.
     * @param mixed $files The submitted files.
     * @return array List of errors, if any.
     */
    public function validation($data, $files) {
        global $CFG;
        $errors = parent::validation($data, $files);
        $info = isset($data['content']) ? file_get_draft_area_info($data['content']) : array('filecount' => 0);
        $url = isset($data['externalurl']) ? trim($data['externalurl']) : '';

        if (get_config('swipe', 'swipeonly')) {
            if (empty($data['externalurl']) && empty($data['description']) && $info['filecount'] == 0) {
                $errors['filecheck'] = get_string('required');
            }
        }
    }

}
