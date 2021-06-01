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
 * Mod swipe external API
 *
 * @package    mod_swipe
 * @category   external
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/externallib.php');

/**
 * Mod swipe external functions.
 *
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_swipe_external extends external_api {

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.6
     */
    public static function like_parameters() {
        return new external_function_parameters([
            'itemid' => new external_value(PARAM_INT, 'item id', VALUE_DEFAULT, 0),
            'like' => new external_value(PARAM_BOOL, 'is this item liked ? (1 - default) otherwise (0)', VALUE_DEFAULT, 1)
        ]);
    }

    /**
     * Set liked item.
     *
     * @param int $itemid item id
     * @param bool $like true if liked
     *
     * @return  array list of courses and warnings
     */
    public static function like($itemid, $like) {
        global $USER, $PAGE;

        $params = self::validate_parameters(self::like_parameters(), [
            'itemid' => $itemid
        ]);

        $itemid = $params['itemid'];

        $warnings = array();
        $usercontext = context_user::instance($USER->id);
        try {
            $card = new \mod_swipe\card($itemid);
            if ($like) {
                $card->like();
            } else {
                $card->unlike();
            }
        } catch (Exception $e) {
            $warning = array();
            $warning['item'] = 'swipe';
            $warning['itemid'] = $itemid;
            if ($e instanceof moodle_exception) {
                $warning['warningcode'] = $e->errorcode;
            } else {
                $warning['warningcode'] = $e->getCode();
            }
            $warning['message'] = $e->getMessage();
            $warnings[] = $warning;
        }

        $result = array();
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.6
     */
    public static function like_returns() {
        return new external_single_structure(
            array(
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.6
     */
    public static function feedback_parameters() {
        return new external_function_parameters([
            'swipeid' => new external_value(PARAM_INT, 'swipeid', VALUE_DEFAULT, 0),
            'feedback' => new external_value(PARAM_RAW, 'The feedback text')
        ]);
    }

    /**
     * Add gallery feedback.
     *
     * @param int $swipeid Swipe deck id
     * @param str $feedback The feedback text
     *
     * @return  array list of courses and warnings
     */
    public static function feedback($swipeid, $feedback) {

        $params = self::validate_parameters(self::feedback_parameters(), [
            'swipeid' => $swipeid,
            'feedback' => $feedback
        ]);

        $warnings = array();

        $cm = get_coursemodule_from_instance('swipe', $swipeid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);

        try {
            $swipedeck = new \mod_swipe\swipedeck($cm, $context);
            if ($feedback) {
                $swipedeck->store_feedback($feedback);
            }
        } catch (Exception $e) {
            $warning = array();
            $warning['item'] = 'swipe';
            $warning['itemid'] = $swipeid;
            if ($e instanceof moodle_exception) {
                $warning['warningcode'] = $e->errorcode;
            } else {
                $warning['warningcode'] = $e->getCode();
            }
            $warning['message'] = $e->getMessage();
            $warnings[] = $warning;
        }

        $result = array();
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.6
     */
    public static function feedback_returns() {
        return new external_single_structure(
            array(
                'warnings' => new external_warnings()
            )
        );
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     * @since Moodle 3.6
     */
    public static function movecard_parameters() {
        return new external_function_parameters([
            'cardid' => new external_value(PARAM_INT, 'Card ID', VALUE_DEFAULT, 0),
            'cardtarget' => new external_value(PARAM_INT, 'Card target', VALUE_DEFAULT, 0),
            'swipeid' => new external_value(PARAM_INT, 'Swipe ID', VALUE_DEFAULT, 0)
        ]);
    }

    /**
     * Move course section.
     *
     * @param int $cardid Section Number
     * @param int $cardtarget Section Target Number
     * @param int $swipeid Course ID
     *
     * @return  array of warnings
     */
    public static function movecard($cardid, $cardtarget, $swipeid) {
        global $DB;

        $params = self::validate_parameters(self::movecard_parameters(), [
            'cardid' => $cardid,
            'cardtarget' => $cardtarget,
            'swipeid' => $swipeid
        ]);

        $cardid = $params['cardid'];
        $cardtarget = $params['cardtarget'];
        $swipeid = $params['swipeid'];

        if ($cardid == 0) {
            throw new moodle_exception('Bad card number ' . $cardid);
        }

        if (!$DB->record_exists('swipe', array('id' => $swipeid))) {
            throw new moodle_exception('Bad swipe deck number ' . $swipeid);
        }

        $maxorder = $DB->get_fieldset_sql('SELECT max(sortorder) FROM {swipe_item} WHERE swipeid = ?', [$swipeid]);

        $cm  = get_coursemodule_from_instance('swipe', $swipeid, 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        require_capability('mod/swipe:manage', $context);

        $warnings = [];

        if (!$cardtarget) {
            $destination = $maxorder;
        } else if ($cardtarget == '1000') {
            $destination = $maxorder;
        } else {
            $destination = $cardtarget;
        }
        if ($destination <= 0 || $destination > $maxorder) {
            throw new moodle_exception('Bad target card number ' . $cardtarget);
        }

        $swipedeck = new \mod_swipe\swipedeck($cm, $context);

        if (!$swipedeck->update_sortorder($cardid, $destination)) {
            $warnings[] = array(
                'item' => 'section',
                'itemid' => $cardid,
                'warningcode' => 'movesectionfailed',
                'message' => 'Section: ' . $cardid . ' SectionTarget: ' . $cardtarget . ' CourseID: ' . $swipeid
            );
        }

        $result = [];
        $result['warnings'] = $warnings;
        return $result;
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     * @since Moodle 3.6
     */
    public static function movecard_returns() {
        return new external_single_structure(
            array(
                'warnings' => new external_warnings()
            )
        );
    }
}
