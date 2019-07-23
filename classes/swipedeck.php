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

namespace mod_swipe;

defined('MOODLE_INTERNAL') || die();

/**
 * Mod swipe swipedeck class
 *
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class swipedeck  {

    public $context;
    protected $cards = null;
    public $cm;
    public $name;
    public $maxcards;

    public function __construct($cm, $context) {
        $this->cm = $cm;
        $this->context = $context;
        $this->name = $cm->name;
        $this->maxcards = 50;
    }

    /**
     * Get the cards for this carddeck
     *
     * @return Array of Card Objects
     */
    public function getcards() {
        global $DB;

        if (!is_null($this->cards)) {
            return $this->cards;
        }

        $sql = "SELECT i.*
                FROM {swipe_item} i
                JOIN {swipe} g ON g.id = i.swipeid
                WHERE i.swipeid = :swipeid
                ORDER BY i.sortorder ASC";

        $fs = get_file_storage();
        $filelist = array();

        $files = $fs->get_area_files($this->context->id, 'mod_swipe', 'item', false, 'id', false);
        foreach ($files as $file) {
            $filelist[$file->get_itemid()]['item'] = $file;
        }

        $cards = array();
        if ($records = $DB->get_records_sql($sql, array('swipeid' => $this->cm->instance))) {
            foreach ($records as $record) {
                $files = !empty($filelist[$record->id]) ? $filelist[$record->id] : false;
                $options = array(
                    'files' => $files,
                    'gallery' => $this,
                );

                // Replacing empty caption with image filename/video url for
                // all items in gallery on mouseover for better user experience.
                if (empty($record->caption)) {
                    if (!empty($filelist[$record->id])) {
                        $record->caption = $filelist[$record->id]['item']->get_filename();
                    } else if (!empty($record->externalurl)) {
                        $record->caption = $record->externalurl;
                    }
                }
                $cards[$record->id] = new card($record->id);
            }
        }
        $this->cards = $cards;
        return $this->cards;
    }

    /**
     * Check if the card deck has items
     *
     * @return Bool true/false
     */
    public function has_items() {
        global $DB;
        if (!is_null($this->cards)) {
            return !empty($this->cards);
        }
        $result = $DB->count_records('swipe_item', array('swipeid' => $this->cm->instance));
        return !empty($result);
    }

    /**
     * Update the card sortorder
     *
     * @param  String $cardid Id of the card that we are moving.
     * @param  String $cardtarget Id of the card wher the moving card
     * is placed before.
     * @return Bool true/false
     */
    public function update_sortorder($cardid, $cardtarget) {
        global $DB;

        $cards = $this->getcards();

        // Remove the moving card from array.
        $movingcard = false;
        foreach ($cards as $card) {
            if ($card->record->id == $cardid) {
                $movingcard = $card;
                unset($cards[$card->record->id]);
            }
        }

        // Reverse the array, then add the movingcard after the targetcard.
        $cardsreversed = array_reverse($cards);

        $reverseneworder = [];
        foreach ($cardsreversed as $card) {
            $reverseneworder[] = $card;
            if ($card->record->id == $cardtarget) {
                $reverseneworder[] = $movingcard;
            }
        }

        $neworder = array_reverse($reverseneworder);

        $order = 1;
        foreach ($neworder as $card) {
             $DB->set_field('swipe_item', 'sortorder', $order++, array('id' => $card->record->id));
        }
    }

    /**
     * Determines if a given user can edit this card deck.
     *
     * @return Bool true/false
     */
    public function user_can_edit() {
        if (has_capability('mod/swipe:manage', $this->context)) {
            return true;
        }
    }

    /**
     * Store user feedback about this card deck.
     *
     * @return int feedback DB id.
     */
    public function store_feedback($text) {
        global $USER, $DB;

        if (isguestuser()) {
            $withcomments = [];
            $withcomments[] = $this->cm->instance;
            if (isset($_COOKIE['hascomment'])) {
                $hascomments = json_decode($_COOKIE['hascomment']);
                if (is_array($hascomments)) {
                    $withcomments = $hascomments;
                    $withcomments[] = $this->cm->instance;
                }
            }
            setcookie('hascomment', json_encode($withcomments), time() + (86400 * 30), "/");
        }
        $feedback = (object) ['userid' => $USER->id,
            'swipeid' => $this->cm->instance,
            'feedback' => $text,
            'timecreated' => time()];

        return $DB->insert_record('swipe_swipefeedback', $feedback);
    }

    /**
     * Get the comments for this carddeck.
     *
     * @return Array of comments.
     */
    public function comments_for_carddeck() {
        global $DB, $OUTPUT;
        $ufields = \user_picture::fields('u');

        $sql = "SELECT c.id AS cid, $ufields, c.feedback AS feedback, c.timecreated AS feedbackcreated
                  FROM {swipe_swipefeedback} c
                  JOIN {user} u ON u.id = c.userid
                 WHERE c.swipeid = :swipeid
              ORDER BY c.timecreated DESC";
        $params['swipeid'] = $this->cm->instance;

        $comments = $DB->get_records_sql($sql, $params);

        if (count($comments)) {
            foreach ($comments as &$comment) {
                $comment->name = fullname($comment);
                $comment->avatar = $OUTPUT->user_picture($comment, array('size' => 18));
            }
        }
        return array_values($comments);
    }
}
