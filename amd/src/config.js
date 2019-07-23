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
 *
 * @module      mod_swipe/swipe_wrapper
 * @package     mod_swipe
 * @copyright   2019 Bas Brands <bas@sonsbeekmedia.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define([], function() {
    window.requirejs.config({
        paths: {
            'transform' : M.cfg.wwwroot + '/mod/swipe/js/jquery_transform2d'
        },
        shim: {
            'transform': {exports: 'transform'}
        }
    });
});
