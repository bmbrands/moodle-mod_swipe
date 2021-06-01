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
 * Form element class for uploading cards.
 *
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir.'/form/filepicker.php');

/**
 *  Form element class for uploading cards.
 *
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class MoodleQuickForm_uploader extends MoodleQuickForm_filepicker {

    /**
     * Returns HTML for this form element.
     *
     * @return string
     */
    public function tohtml() {
        global $CFG, $COURSE, $USER, $PAGE, $OUTPUT;
        $id     = $this->_attributes['id'];
        $elname = $this->_attributes['name'];

        if ($this->_flagFrozen) {
            return $this->getFrozenHtml();
        }
        if (!$draftitemid = (int)$this->getValue()) {
            // No existing area info provided - let's use fresh new draft area.
            $draftitemid = file_get_unused_draft_itemid();
            $this->setValue($draftitemid);
        }

        if ($COURSE->id == SITEID) {
            $context = context_system::instance();
        } else {
            $context = context_course::instance($COURSE->id);
        }

        $args = new stdClass();
        // Need these three to filter repositories list.
        $args->accepted_types = $this->_options['accepted_types'] ? $this->_options['accepted_types'] : '*';
        $args->return_types = $this->_options['return_types'];
        $args->itemid = $draftitemid;
        $args->maxbytes = $this->_options['maxbytes'];
        $args->context = $PAGE->context;
        $args->buttonname = $elname.'choose';
        $args->elementname = $elname;
        $args->disable_types = array('thebox');

        $html = $this->_getTabs();
        $fp = new file_picker($args);
        $options = $fp->options;
        $options->context = $PAGE->context;
        $html .= $OUTPUT->render($fp);
        $html .= '<input type="hidden" name="'.$elname.'" id="'.$id.'" value="'.$draftitemid.'" class="filepickerhidden"/>';

        $module = [
            'name' => 'form_filepicker',
            'fullpath' => '/lib/form/filepicker.js',
            'requires' => ['core_filepicker', 'node', 'node-event-simulate', 'core_dndupload'],
        ];
        $PAGE->requires->js_init_call('M.form_filepicker.init', array($fp->options), true, $module);

        $nonjsfilepicker = new moodle_url('/repository/draftfiles_manager.php', array(
            'env' => 'filepicker',
            'action' => 'browse',
            'itemid' => $draftitemid,
            'subdirs' => 0,
            'maxbytes' => $options->maxbytes,
            'maxfiles' => 1,
            'ctx_id' => $PAGE->context->id,
            'course' => $PAGE->course->id,
            'sesskey' => sesskey(),
            )
        );

        // Non js file picker.
        $html .= '<noscript>';
        $html .= "<div>
            <object type='text/html' data='$nonjsfilepicker' height='160' width='600' style='border:1px solid #000'></object>
        </div>";
        $html .= '</noscript>';

        return $html;
    }
}

MoodleQuickForm::registerElementType('uploader',
    $CFG->dirroot."/mod/swipe/classes/quickform/uploader.php", 'MoodleQuickForm_uploader');
