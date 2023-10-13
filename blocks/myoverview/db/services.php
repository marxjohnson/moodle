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
 * Custom webservice for myoverview block to change how the courses are retrieved (MDL-78370).
 *
 * @package    block_myoverview
 * @copyright  2023 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Conn Warwicker <conn.warwicker@catalyst-eu.net>
 */

defined('MOODLE_INTERNAL') || die;

$functions = [
    'block_myoverview_get_courses' => [
        'classname'   => 'block_myoverview\external\get_courses',
        'description' => 'Gets the courses for the myoverview block',
        'type'        => 'read',
        'ajax'        => true,
        'services' => [],
        'readonlysession' => true,
    ],
];
