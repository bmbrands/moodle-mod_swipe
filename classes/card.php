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
 * Mod swipe Card class
 *
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_swipe;

defined('MOODLE_INTERNAL') || die();

/**
 * Mod swipe Card class
 *
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class card {

    /**
     * @var object module context.
     */
    private $context;

    /**
     * @var object course module.
     */
    private $cm;

    /**
     * @var object card database record.
     */
    public $record;

    /**
     * Card class instance.
     *
     * @param int $id Card id
     */
    public function __construct($id = null) {
        global $DB;

        if ($id) {
            $this->record = $DB->get_record('swipe_item', ['id' => $id]);
            $this->cm = get_coursemodule_from_instance('swipe', $this->record->swipeid, 0, false, MUST_EXIST);
            $this->context = \context_module::instance($this->cm->id);
        }
    }

    /**
     * Create a new card.
     *
     * @param  \stdClass $data Form data
     * @param  Object $context Swipe deck context
     * @return Object Card Object
     */
    public static function create(\stdClass $data, $context) {
        global $DB;

        $data->timecreated = time();
        $maxorder = $DB->get_fieldset_sql('SELECT max(sortorder) FROM {swipe_item} WHERE swipeid = ?', [$data->swipeid]);

        $data->sortorder = $maxorder[0] + 1;
        $data->id = $DB->insert_record('swipe_item', $data);
        if (!isset($data->externalurl)) {
            $data->externalurl = '';
        }

        $params = array(
            'context' => $context,
            'objectid' => $data->id,
        );
        $event = \mod_swipe\event\card_created::create($params);
        $event->add_record_snapshot('swipe_item', $data);
        $event->trigger();

        $return = new \stdClass();
        $return->record = $data;
        return $return;
    }

    /**
     * Update a card.
     *
     * @param  \stdClass $data Form data
     * @param  Object $context Swipe deck context
     * @return Object Card Object
     */
    public function update(\stdClass $data, $context) {
        global $DB;

        $record = $this->record;

        $record->caption = $data->caption;

        if ($data->contenttype == 'text') {
            $record->description = $data->description;
        }
        if ($data->contenttype == 'video') {
            $record->externalurl = $data->externalurl;
        }

        if ($DB->update_record('swipe_item', $record)) {
            $params = array(
                'context' => $context,
                'objectid' => $record->id,
            );
            $event = \mod_swipe\event\card_updated::create($params);
            $event->add_record_snapshot('swipe_item', $record);
            $event->trigger();
        }

        return $record;
    }

    /**
     * Delete a card.
     */
    public function delete() {
        global $DB;

        $params = array(
            'context' => $this->get_context(),
            'objectid' => $this->record->id,
        );

        $event = \mod_swipe\event\card_deleted::create($params);
        $event->add_record_snapshot('swipe_item', $this->record);
        $event->trigger();

        $fs = get_file_storage();
        $fs->delete_area_files($this->get_context()->id, 'mod_swipe', 'card', $this->record->id);
        $DB->delete_records('swipe_item', ['id' => $this->record->id]);
        $DB->delete_records('swipe_userfeedback', ['cardid' => $this->record->id]);
    }

    /**
     * Get this swipedeck context id.
     *
     * @return Object swipe deck module context
     */
    public function get_context() {
        return $this->context;
    }

    /**
     * Get the card youtube video embed url.
     *
     * @return string embed url.
     */
    public function get_embed_url() {
        $embed = '';
        if ($id = $this->get_youtube_videoid()) {
            $embed = "https://www.youtube.com/embed/{$id}";
        }
        return $embed;
    }

    /**
     * Get the stored file for this card.
     *
     * @return Object Moodle file.
     */
    public function get_stored_file() {
        $fs = get_file_storage();
        $files = $fs->get_area_files($this->get_context()->id, 'mod_swipe', 'card', $this->record->id, 'id', false);

        return current($files);
    }

    /**
     * Get the video id from the stored external url.
     *
     * @return Int Youtube video Id.
     */
    private function get_youtube_videoid() {
        $id = null;
        if ($this->record->externalurl) {
            $url = $this->record->externalurl;
            if (strpos($this->record->externalurl, '#') !== false) {
                $url = substr($this->record->externalurl, 0, strpos($this->record->externalurl, '#'));
            }
            preg_match('/(youtu\.be\/|youtube\.com\/(watch\?(.*&)?v=|(embed|v)\/))([^\?&"\'>]+)/', $url, $matches);
            if (isset($matches[5])) {
                $id = $matches[5];
            }
        }
        return $id;
    }

    /**
     * Get the the card image url.
     *
     * @return String Card image url.
     */
    public function get_image_url() {
        global $CFG;

        if (!$file = $this->get_stored_file()) {
            return null;
        }

        $path = \moodle_url::make_pluginfile_url($this->get_context()->id,
            'mod_swipe', 'card', $this->record->id, '/', $file->get_filename());

        return $path;
    }

    /**
     * Get the number of likes for the Card.
     *
     * @return Int Number of likes
     */
    public function get_like_count() {
        global $DB;
        $select = 'liked = 1 AND cardid = :cardid';
        $count = $DB->count_records_select('swipe_userfeedback', $select, array('cardid' => $this->record->id));
        $count = is_null($count) ? 0 : $count;
        return $count;
    }

    /**
     * Get the like info for this card.
     *
     * For logged in users this can simply retrieve the information from the DB. We
     * also need to store likes / dislikes for guest users and use cookies to
     * retrieve the likes / dislikes.
     *
     * @return Object Like info
     */
    public function get_like_info() {
        global $DB, $SESSION;
        $info = new \stdClass();

        $context = $this->get_context();
        if (is_guest($context)) {
            $dislikedcards = [];
            if (isset($SESSION->dislikedcards)) {
                $value = clean_param($SESSION->dislikedcards, PARAM_RAW);
                if (!empty($value)) {
                    $dislikedcards = json_decode($value);
                }
            } else {
                if (isset($_COOKIE['rated'])) {
                    // We add all rated cards to disliked cards. It does not matter
                    // if they were liked or disliked. As long as they are skipped in the deck.
                    $SESSION->dislikedcards = $_COOKIE['rated'];
                    $dislikedcards = json_decode($_COOKIE['rated']);
                }
            }
            $likedcards = [];
            if (isset($SESSION->likedcards)) {
                $value = clean_param($SESSION->likedcards, PARAM_RAW);
                if (!empty($value)) {
                    $likedcards = json_decode($value);
                }
            }
            if (in_array($this->record->id, $dislikedcards)) {
                $info->rated = true;
            }
            if (in_array($this->record->id, $likedcards)) {
                $info->rated = true;
                $info->likedbyme = true;
            }
        } else {

            $info->likes = $DB->count_records('swipe_userfeedback', array('cardid' => $this->record->id, 'liked' => 1));
            $info->dislikes = $DB->count_records('swipe_userfeedback', array('cardid' => $this->record->id, 'liked' => 0));
            $info->likedbyme = false;

            if ($fb = $this->get_userfeedback()) {
                $info->rated = true;
                if ($fb->liked) {
                    $info->likedbyme = true;
                }
            }
        }
        return $info;
    }

    /**
     * Get the feedback record for this user (likes / dislikes)
     *
     * @return Object Like or dislike feedback record.
     */
    public function get_userfeedback() {
        global $DB, $USER;
        return $DB->get_record('swipe_userfeedback', array('cardid' => $this->record->id, 'userid' => $USER->id));
    }

    /**
     * Store a cookie that contains an array of rated items.
     *
     * @param  Int $id rated card id.
     */
    private function store_cookie_rated($id) {
        $rated = [];
        if (isset($_COOKIE['rated'])) {
            $rated = json_decode($_COOKIE['rated']);
        }
        $rated[] = $id;
        setcookie('rated', json_encode($rated), time() + (86400 * 30), "/");
    }

    /**
     * Like a card
     *
     * @return Bool [description]
     */
    public function like() {
        global $DB, $USER, $SESSION;

        if (has_capability('mod/swipe:grade', $this->get_context())) {
            return false;
        }

        $context = $this->get_context();
        if (is_guest($context)) {
            $likedcards = [];
            if (isset($SESSION->likedcards)) {
                $value = clean_param($SESSION->likedcards, PARAM_RAW);
                if (!empty($value)) {
                    $likedcards = is_array(json_decode($value)) ? json_decode($value) : [];
                }
            }
            $likedcards[] = $this->record->id;
            $cookievalue = json_encode($likedcards);
            $SESSION->likedcards = $cookievalue;
            $fb = (object) array(
                'cardid' => $this->record->id,
                'userid' => $USER->id,
                'liked' => 1,
                'swipeid' => $this->record->swipeid
            );
            $DB->insert_record('swipe_userfeedback', $fb);

            // Also store in cookie for later reference.
            $this->store_cookie_rated($this->record->id);
        } else if (has_capability('mod/swipe:like', $this->get_context())) {
            if ($fb = $this->get_userfeedback()) {
                $fb->liked = 1;
                $DB->update_record('swipe_userfeedback', $fb);
            } else {
                $fb = (object) array(
                    'cardid' => $this->record->id,
                    'userid' => $USER->id,
                    'liked' => 1,
                    'swipeid' => $this->record->swipeid
                );
                $DB->insert_record('swipe_userfeedback', $fb);
            }
        }
    }

    /**
     * Returns the card type
     *
     * @return int Card type id.
     */
    public function type() {
        return $this->record->itemtype;
    }

    /**
     * Unlike a card
     *
     */
    public function unlike() {
        global $DB, $USER, $SESSION;

        if (has_capability('mod/swipe:grade', $this->get_context())) {
            return false;
        }

        $context = $this->get_context();
        if (is_guest($context)) {
            $dislikedcards = [];
            if (isset($SESSION->dislikedcards)) {
                $value = clean_param($SESSION->dislikedcards, PARAM_RAW);
                if (!empty($value)) {
                    $dislikedcards = is_array(json_decode($value)) ? json_decode($value) : [];
                }
            }
            $dislikedcards[] = $this->record->id;
            $cookievalue = json_encode($dislikedcards);
            $SESSION->dislikedcards = $cookievalue;

            $fb = (object) array(
                'cardid' => $this->record->id,
                'userid' => $USER->id,
                'liked' => 0,
                'swipeid' => $this->record->swipeid
            );
            $DB->insert_record('swipe_userfeedback', $fb);
            $this->store_cookie_rated($this->record->id);
        } else if (has_capability('mod/swipe:like', $this->get_context())) {
            if ($fb = $this->get_userfeedback()) {
                $fb->liked = 0;
                $DB->update_record('swipe_userfeedback', $fb);
            } else {
                $fb = (object) array(
                    'cardid' => $this->record->id,
                    'userid' => $USER->id,
                    'liked' => 0,
                    'swipeid' => $this->record->swipeid
                );
                $DB->insert_record('swipe_userfeedback', $fb);
            }
        }
    }
}
