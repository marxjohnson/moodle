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

// The majority of this class is copied from the get_enrolled_courses_by_timeline_classification_*
// functions in course/externallib.php with some minor changes.

namespace block_myoverview\external;

use core_course\external\get_enrolled_courses_by_timeline_classification;

/**
 * Webservice class for get_courses for the myoverview block.
 *
 * @package    block_myoverview
 * @copyright  2023 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Conn Warwicker <conn.warwicker@catalyst-eu.net>
 */
class get_courses extends get_enrolled_courses_by_timeline_classification {

    /**
     * This is the exporter class we are using.
     */
    const EXPORTER_CLASS = '\core_course\external\course_info_exporter';

}
