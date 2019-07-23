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
 * Thin wrapper for ordering cards
 *
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
[
    'jquery',
    'core/sortable_list',
    'core/custom_interaction_events',
    'core/notification',
    'core/ajax'
],
function(
    $,
    Sortablelist,
    CustomEvents,
    Notification,
    Ajax
) {
    var SELECTORS = {
        WRAPPER: '[data-region="editcards"]',
        CARD: '.edit-card',
    };

    /**
     * Move a course module.
     *
     * @param {Object} args Arguments to pass to webservice
     *
     * Valid args are:t
     * int moduleid      id of module to move
     * int moduletarget  id of module to position after
     * int sectionnumber number of section to move module to
     * int courseid.     id of course this section belongs to
     *
     * @return {promise} Resolved with void or array of warnings
     */
    var moveCard = function(args) {
        var request = {
            methodname: 'mod_swipe_move_card',
            args: args
        };
        var promise = Ajax.call([request])[0];
        promise.fail(Notification.exception);
        return promise;
    };

    /**
     * Listen to and handle events for routing, showing and hiding the message drawer.
     *
     * @param {Object} root The message drawer container.
     */
    var registerEventListeners = function(root) {

        // Variables for moving cards.
        var cardsContainer = root.find(SELECTORS.WRAPPER);

        var swipeid = cardsContainer.attr('data-swipeid');

        var cardsSortable = new Sortablelist(cardsContainer, {moveHandlerSelector: '.movecard > [data-drag-type=move]'});

        cardsSortable.getDestinationName = function(parentElement, afterElement) {
            if (!afterElement.length) {
                return 'Top of deck';
            } else {
                return 'After next';
            }
        };

        cardsContainer.on(Sortablelist.EVENTS.DROP, function(e, info) {
            e.stopPropagation();
            var args;
            if (info.positionChanged) {
                if (info.element.attr('data-card-id')) {
                    var cardid = info.element.attr('data-card-id');
                    var cardtarget = info.targetNextElement.attr('data-card-id');
                    args = {
                        cardid: cardid,
                        cardtarget: cardtarget,
                        swipeid: swipeid
                    };
                    if (typeof cardid !== 'undefined' && cardid !== 0) {
                        moveCard(args).catch(Notification.exception);
                    }
                }
            }
        });
    };

    /**
     * @method
     * @param {Object} options
     */
    var init = function(root) {
        var root = $(root);
        registerEventListeners(root);
    };

    return {
        init: init
    };
});
