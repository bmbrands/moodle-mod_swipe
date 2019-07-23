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
 * File description.
 *
 * @package    mod_swipe
 * @copyright  2021 Cambridge Assessment International Education
 * @author     Bas Brands <bas@sonsbeekmedia.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(

    'mod_swipe_like' => array(
        'classpath' => 'mod/swipe/classes/external.php',
        'classname'   => 'mod_swipe_external',
        'methodname'  => 'like',
        'description' => 'Like an item.',
        'type'        => 'read',
        'ajax'        => true,
        'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'mod_swipe_feedback' => array(
        'classpath' => 'mod/swipe/classes/external.php',
        'classname'   => 'mod_swipe_external',
        'methodname'  => 'feedback',
        'description' => 'Send feedback for a swipe deck.',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
    'mod_swipe_move_card' => array(
        'classpath' => 'mod/swipe/classes/external.php',
        'classname'   => 'mod_swipe_external',
        'methodname'  => 'movecard',
        'description' => 'Move a card in the card deck.',
        'type'        => 'write',
        'ajax'        => true,
        'services'    => array(MOODLE_OFFICIAL_MOBILE_SERVICE),
    ),
);
