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
 * Thin wrapper allowing us to load the lightbox.js
 *
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(
[
    'jquery',
    'core/custom_interaction_events',
    'core/notification',
    'core/ajax',
    'mod_swipe/transform',
    'mod_swipe/tinder',
    'mod_swipe/textFit'
],
function(
    $,
    CustomEvents,
    Notification,
    Ajax,
    transform_unused,
    jTinder,
    textFit
) {
    var SELECTORS = {
        WRAPPER: '[data-region="swipe-wrapper"]',
        LIKE: '[data-action="like"]',
        DISLIKE: '[data-action="dislike"]',
        VIEW_INFO: '[data-action="info"]',
        CLOSE_INFO: '[data-action="close-info"]',
        ACTION_CONTAINER: '[data-region="cardactions"]',
        INFO_CONTAINER: '[data-region="card-info"]',
        CARD_IMAGE: '[data-region="card-image"]',
        CARD_VIDEO: '[data-region="card-video"]',
        FEEDBACK_CONTAINER: '[data-region="feedback-container"]',
        FEEDBACK_INPUT: '[data-region="feedback-input"]',
        FEEDBACK_SEND: '[data-action="send-feedback"]',
        FEEDBACK_THANKS: '[data-region="feedback-thanks"]',
        FEEDBACK_TEXT: '[data-region="feedback-text"]'
    };

    var TYPE = {
        IMAGE: 1,
        VIDEO: 2,
        TEXT: 3
    };

    var storeLike = function(itemid, like) {
        var args = {
            itemid : itemid,
            like: like
        };
        var request = {
            methodname: 'mod_swipe_like',
            args: args
        };
        var promise = Ajax.call([request])[0];
        promise.fail(Notification.exception);

        return promise;
    };

    var storeFeedback = function(swipeid, feedback) {
        var args = {
            swipeid : swipeid,
            feedback: feedback
        };
        var request = {
            methodname: 'mod_swipe_feedback',
            args: args
        };
        var promise = Ajax.call([request])[0];
        promise.fail(Notification.exception);
        return promise;
    };

    var getCardId = function(root) {
        var container = root.find(SELECTORS.ACTION_CONTAINER);
        return container.attr('data-current-card');
    };

    var getCard = function(root, cardid) {
        if (cardid) {
            var card = root.find('[data-card-id=' + cardid + ']');
            return card;
        }
    };

    var showCardInfo = function(card) {
        var cardinfo = card.find(SELECTORS.INFO_CONTAINER);
        var status = cardinfo.attr('data-status');
        if (status == 'closed') {
            cardinfo.css('top', '0');
            cardinfo.attr('data-status', 'open');
        } else {
            cardinfo.css('top', '-420px');
            cardinfo.attr('data-status', 'closed');
        }
    };

    /**
     * Listen to and handle events for routing, showing and hiding the message drawer.
     *
     * @param {Object} root The message drawer container.
     */
    var registerEventListeners = function(root) {

        CustomEvents.define(root, [CustomEvents.events.activate]);

        root.on(CustomEvents.events.activate, SELECTORS.VIEW_INFO, function(e, data) {
            var cardid = getCardId(root);
            var card = getCard(root, cardid);
            showCardInfo(card);
            data.originalEvent.preventDefault();
        });

        root.on(CustomEvents.events.activate, SELECTORS.CLOSE_INFO, function(e, data) {
            var cardid = getCardId(root);
            var card = getCard(root, cardid);
            showCardInfo(card);
            data.originalEvent.preventDefault();
        });

        root.on(CustomEvents.events.activate, SELECTORS.LIKE, function(e, data) {
            $("#tinderslide").jTinder('like');
            data.originalEvent.preventDefault();
        });

        root.on(CustomEvents.events.activate, SELECTORS.DISLIKE, function(e, data) {
            $("#tinderslide").jTinder('dislike');
            data.originalEvent.preventDefault();
        });

        root.on(CustomEvents.events.activate, SELECTORS.FEEDBACK_SEND, function (e, data) {
            var fbContainer = root.find(SELECTORS.FEEDBACK_CONTAINER);
            var fbInput = fbContainer.find(SELECTORS.FEEDBACK_INPUT);
            var fbText = fbContainer.find(SELECTORS.FEEDBACK_TEXT);
            var fbThanks = fbContainer.find(SELECTORS.FEEDBACK_THANKS);
            var swipeid = fbContainer.attr('data-swipeid');
            var feedback = fbText.val();

            storeFeedback(swipeid, feedback).then( function() {
                fbInput.addClass('hidden');
                fbThanks.removeClass('hidden');
            });

            data.originalEvent.preventDefault();
        });
    };

    var nextCardActions = function(root, cardid) {

        var card = getCard(root, cardid);
        if (!card.length) {
            return;
        }
        var type = card.attr('data-card-type');

        // Stop video
        if (type == TYPE.VIDEO) {
            var iframe = card.find('iframe');
            if ( iframe.length ) {
                var video = iframe.attr('src');
                iframe.attr('src', '');
                iframe.attr('src', video);
            }
        }

        // Prepare the next cards on the deck.
        var preloadid = card.attr('data-preload');

        if (card.attr('data-last')) {
            root.find(SELECTORS.ACTION_CONTAINER).addClass('hidden');
            root.find(SELECTORS.FEEDBACK_CONTAINER).removeClass('hidden');
            root.find(SELECTORS.WRAPPER).addClass('hidden');
        }

        if (preloadid) {
            var preloadCard = getCard(root, preloadid);

            // Load card image
            if (preloadCard.attr('data-card-type') == TYPE.IMAGE) {
                var cardimage = preloadCard.attr('data-card-image');
                preloadCard.find(SELECTORS.CARD_IMAGE).attr('style', 'background-image: url(' + cardimage + ')');
            }

            // Load card video
            if (preloadCard.attr('data-card-type') == TYPE.VIDEO) {
                var video = preloadCard.attr('data-card-video');
                preloadCard.find(SELECTORS.CARD_VIDEO).attr('src', video);
            }
        }
    };

    /**
     * @method
     * @param {Object} options
     */
    var init = function(root) {
        var root = $(root);
        textFit(document.getElementsByClassName('innertext'), {multiLine: true});
        $("#tinderslide").jTinder({
            // dislike callback
            onDislike: function () {
                // set the status text
                var cardid = getCardId(root);
                storeLike(cardid, false);
                nextCardActions(root, cardid);
            },
            // like callback
            onLike: function () {
                var cardid = getCardId(root);
                storeLike(cardid, true);
                nextCardActions(root, cardid);
            },
            animationRevertSpeed: 200,
            animationSpeed: 400,
            threshold: 1,
            likeSelector: '.like',
            dislikeSelector: '.dislike'
        });
        registerEventListeners(root);
    };

    return {
        init: init
    };
});
