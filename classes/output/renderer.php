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
 * mod swipe rendrer
 *
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace mod_swipe\output;

defined('MOODLE_INTERNAL') || die;

use plugin_renderer_base;
use renderable;
use stdClass;
use moodle_url;

/**
 * mod swipe renderer
 *
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {


    /**
     * Get the embed url using the mediarenderer.
     *
     * @param Object $card
     * @return string
     */
    public function embed_html($card) {
        $mediarenderer = \core_media_manager::instance();
        return $mediarenderer->embed_url(new moodle_url($card->get_embed_url()), '', 670, 377);
    }

    /**
     * Render a carousel of cards.
     *
     * @param Object $swipedeck
     * @return string Rendered HTML.
     */
    public function view_cards($swipedeck) {

        $cm = $swipedeck->cm;
        $template = new stdClass();
        // Change to true to show card name and nr. of likes.
        $template->showcardfooter = false;
        $template->cancomment = true;
        $template->modurl = new moodle_url('/mod/swipe/img/');
        $template->swipeid = $cm->instance;
        $template->swipename = $cm->name;
        $template->endofdeck = false;
        $template->canedit = $swipedeck->user_can_edit();
        $template->editurl = new moodle_url('/mod/swipe/view.php', array('id' => $cm->id, 'editing' => 1));
        $template->cards = [];

        $showagain = optional_param('showagain', 0, PARAM_INT);

        $first = true;
        $previous = [];

        $cards = $swipedeck->getcards();

        if (count($cards) < 2) {
            $template->warning = get_string('notenoughcards', 'mod_swipe');
            $template->emptydeck = true;
            return $this->render_from_template('mod_swipe/swipe', $template);
        }

        $count = 0;

        $cards = array_reverse($cards);

        foreach ($cards as $card) {

            // The first card is the lowest on the deck.
            $card->first = $first ? true : false;
            $first = false;
            $card->like = $card->get_like_info();
            $card->id = $card->record->id;

            if (!has_capability('mod/swipe:grade', $swipedeck->context)) {
                if (isset($card->like->rated) && !$showagain) {
                    continue;
                }
            }

            if (count($previous) >= 2) {
                $card->preloadid = $previous[count($previous) - 2];
            }

            if ($card->record->itemtype == 1) { // Image.
                $card->isimage = true;
                $card->img = $card->get_image_url();
                $card->caption = $card->record->caption;
                $card->itemhtml = $this->embed_html($card);
            } else if ($card->record->itemtype == 2) { // Video.
                $card->isvideo = true;
                $card->caption = $card->record->caption;
                $card->embed = $card->get_embed_url();
            } else { // Text.
                $card->type = 3;
                $card->istext = true;
                $card->text = $card->record->description;
                $card->caption = $card->record->caption;
            }

            $template->cards[] = $card;
            $previous[] = $card->record->id;
            $count++;
        }

        // We need the last 2 cards (visually highest on the deck) to preload content.
        $template->hascards = false;

        if ($count > 2) {
            $template->hascards = true;
            $template->cards[($count - 2)]->preload = true;
            $template->cards[($count - 1)]->preload = true;
        } else if ($count == 2) {
            $template->hascards = true;
            $template->cards[0]->preload = true;
            $template->cards[1]->preload = true;
        }

        if ($count > 0) {
            $template->cards[0]->last = true;
        }

        return $this->render_from_template('mod_swipe/swipe', $template);
    }

    /**
     * Render a the cards editing and moving interface.
     *
     * @param Object $swipedeck
     * @return string Rendered HTML.
     */
    public function edit_cards($swipedeck) {

        $cm = $swipedeck->cm;

        $template = new stdClass();

        $template->swipeid = $cm->instance;

        $this->add_manage_links($template, $cm);

        $cards = $swipedeck->getcards();

        foreach ($cards as $card) {
            $card->id = $card->record->id;
            if ($card->record->itemtype == 1) { // Image.
                $card->isimage = true;
                $card->img = $card->get_image_url();
                $card->caption = $card->record->caption;
                $card->itemhtml = $this->embed_html($card);
            } else if ($card->record->itemtype == 2) { // Video.
                $card->isvideo = true;
                $card->caption = $card->record->caption;
                $card->embed = $card->get_embed_url();
            } else { // Text.
                $card->type = 3;
                $card->istext = true;
                $card->text = $card->record->description;
                $card->caption = $card->record->caption;
            }
            $card->editcard = new moodle_url('/mod/swipe/card.php', ['s' => $cm->instance, 'i' => $card->record->id]);
            $card->deletecard = new moodle_url('/mod/swipe/card.php',
                ['s' => $cm->instance, 'i' => $card->record->id, 'action' => 'delete']);
            $card->caption = $card->record->caption;
            $template->cards[] = $card;
        }

        return $this->render_from_template('mod_swipe/edit', $template);
    }

    /**
     * Render the report of cards, likes and comments.
     *
     * @param Object $swipedeck
     * @return string Rendered HTML.
     */
    public function view_cards_report($swipedeck) {

        $cm = $swipedeck->cm;

        $cards = $swipedeck->getcards();

        $template = new stdClass();

        $this->add_manage_links($template, $cm);

        $template->reportdownloadurl = new moodle_url('/mod/swipe/view.php',
            ['id' => $cm->id, 'page' => 'exportxls']);

        $sort = optional_param('sort', 'cardsasc', PARAM_ALPHA);
        $sortparams = ['id' => $cm->id, 'page' => 'report'];

        switch ($sort) {
            case 'cardsasc':
                $template->sortcards = true;
                $template->asc = true;
                $sortparams['sort'] = 'carddesc';
                break;
            case 'carddesc':
                $template->sortcards = true;
                $template->desc = true;
                $sortparams['sort'] = 'cardsasc';
                break;
            case 'dislikeasc':
                $template->sortdislike = true;
                $template->asc = true;
                $sortparams['sort'] = 'dislikedesc';
                break;
            case 'dislikedesc':
                $template->sortdislike = true;
                $template->desc = true;
                $sortparams['sort'] = 'dislikeasc';
                break;
            case 'likeasc':
                $template->sortlike = true;
                $template->asc = true;
                $sortparams['sort'] = 'likedesc';
                break;
            case 'likedesc':
                $template->sortlike = true;
                $template->desc = true;
                $sortparams['sort'] = 'likeasc';
                break;
        }

        $lsort = $sortparams;
        $lsort['sort'] = 'carddesc';
        $template->sortcardsurl = new moodle_url('/mod/swipe/view.php', $lsort);

        $lsort['sort'] = 'dislikedesc';
        $template->sortdislikeurl = new moodle_url('/mod/swipe/view.php', $lsort);

        $lsort['sort'] = 'likedesc';
        $template->sortlikeurl = new moodle_url('/mod/swipe/view.php', $lsort);

        $template->sortdir = new moodle_url('/mod/swipe/view.php', $sortparams);

        $sortcards = $sortdislike = $sortlike = $cardlist = $names = [];

        $count = 0;
        foreach ($cards as $card) {

            $card->id = $card->record->id;
            $like = $card->get_like_info();
            $card->likes = $like->likes;
            $card->dislikes = $like->dislikes;

            if ($card->record->itemtype == 1) { // Image.
                $card->isimage = true;
                $card->cardtype = get_string('contenttype_image', 'mod_swipe');
                $card->img = $card->get_image_url();
                $card->caption = $card->record->caption;
                $card->itemhtml = $this->embed_html($card);
            }
            if ($card->record->itemtype == 2) { // Image.
                $card->isvideo = true;
                $card->cardtype = get_string('contenttype_image', 'mod_swipe');
                $card->caption = $card->record->caption;
                $card->embed = $card->get_embed_url();
            } else if ($card->record->itemtype == 3) { // Text.
                $card->type = 3;
                $card->cardtype = get_string('contenttype_text', 'mod_swipe');
                $card->istext = true;
                $card->text = $card->record->description;
                $card->caption = $card->record->caption;
            }

            $cardlist[$count] = $card;
            $sortdislike[$count] = $like->dislikes;
            $sortlike[$count] = $like->likes;
            $names[$count] = $card->record->caption;

            $count++;
        }

        if (!empty($cardlist)) {
            switch ($sort) {
                case 'cardsasc':
                    // Do nothing.
                    break;
                case 'carddesc':
                    $cardlist = array_reverse($cardlist);
                    break;
                case 'dislikeasc':
                    array_multisort($sortdislike, SORT_ASC, $names, SORT_ASC, $cardlist);
                    break;
                case 'dislikedesc':
                    array_multisort($sortdislike, SORT_DESC, $names, SORT_ASC, $cardlist);
                    break;
                case 'likeasc':
                    array_multisort($sortlike, SORT_ASC, $names, SORT_ASC, $cardlist);
                    break;
                case 'likedesc':
                    array_multisort($sortlike, SORT_DESC, $names, SORT_ASC, $cardlist);
                    break;
            }
        }
        $template->cards = $cardlist;
        $template->comments = $swipedeck->comments_for_carddeck();

        return $this->render_from_template('mod_swipe/swipe_report', $template);
    }

    /**
     * Generate the XLS file containing cards, likes and comments.
     *
     * @param Object $swipedeck
     * @return string Rendered HTML.
     */
    public function view_cards_report_xls($swipedeck) {
        global $CFG;
        require_once($CFG->dirroot.'/lib/excellib.class.php');

        $cm = $swipedeck->cm;

        $cards = $swipedeck->getcards();

        $downloadfilename = $swipedeck->name . '.xls';
        // Creating a workbook.
        $workbook = new \MoodleExcelWorkbook("-");
        // Sending HTTP headers.
        $workbook->send($downloadfilename);
        // Adding the worksheet.
        $myxls = $workbook->add_worksheet('cards');

        // Create xls header.
        $myxls->set_column(0, 3, '40');
        $myxls->write_string(0, 0, get_string('card', 'mod_swipe'), array('bold' => 1, 'size' => 16));
        $myxls->write_string(0, 1, get_string('disliked', 'mod_swipe'), array('bold' => 1, 'color' => '#EB6E5A', 'size' => 16));
        $myxls->write_string(0, 2, get_string('liked', 'mod_swipe'), array('bold' => 1, 'color' => '#86BD97', 'size' => 16));

        $myxls->set_row(0, 20);

        $row = 1;

        $fs = get_file_storage();

        // Add cards to xls.
        foreach ($cards as $card) {

            if ($card->record->itemtype == 1) { // Image.
                $file = $card->get_stored_file();
                $filename = $file->get_filename();
                $myxls->write_string($row, 0, strip_tags($card->record->caption) . "( $filename )",
                    array('bold' => 1, 'size' => 15, 'text_wrap' => true, 'v_align' => 'top'));
                $myxls->set_row($row, 80);
            } else if ($card->record->itemtype == 2) { // Video.
                $videoname = get_string('contenttype_video', 'mod_swipe') . "\n" . $card->get_embed_url();
                $myxls->write_string($row, 0, $videoname,
                    array('text_wrap' => true, 'v_align' => 'top'));
                $myxls->set_row($row, 80);
            } else { // Text.
                $myxls->write_string($row, 0, strip_tags($card->record->description),
                    array('bold' => 1, 'size' => 15, 'text_wrap' => true, 'v_align' => 'top'));
                $myxls->set_row($row, 80);
            }

            // Write the likes.
            $like = $card->get_like_info();
            $myxls->write_string($row, 1, $like->dislikes,
                array('v_align' => 'top', 'color' => '#EB6E5A', 'size' => 15));
            $myxls->write_string($row, 2, $like->likes,
                array('v_align' => 'top', 'color' => '#86BD97', 'size' => 15));

            $row++;
        }

        $comments = $swipedeck->comments_for_carddeck();
        $row++;
        $myxls->write_string($row, 0, get_string('comments', 'mod_swipe'), array('bold' => 1, 'size' => 16));
        $row++;
        foreach ($comments as $comment) {
            $myxls->write_string($row, 0, userdate($comment->feedbackcreated, get_string('strftimedatetime', 'core_langconfig')),
                    array('bold' => 0, 'size' => 13, 'text_wrap' => true, 'v_align' => 'top'));
            $myxls->write_string($row, 1, $comment->name,
                    array('bold' => 0, 'size' => 13, 'text_wrap' => true, 'v_align' => 'top'));
            $myxls->write_string($row, 2, $comment->feedback,
                    array('bold' => 0, 'size' => 13, 'text_wrap' => true, 'v_align' => 'top'));
            $row++;
        }

        // Close the workbook.
        $workbook->close();
        exit;
    }

    /**
     * Add the management links to the template Object.
     * @param Object &$template Template object being rendered.
     * @param Object $cm Swipedeck course module instance.
     */
    private function add_manage_links(&$template, $cm) {
        $template->additem = new moodle_url('/mod/swipe/card.php', ['s' => $cm->instance]);
        $template->view = new moodle_url('/mod/swipe/view.php', ['id' => $cm->id]);
        $template->report = new moodle_url('/mod/swipe/view.php', ['id' => $cm->id, 'page' => 'report']);
    }
}
