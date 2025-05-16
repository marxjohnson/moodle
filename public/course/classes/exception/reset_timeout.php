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

namespace core_course\exception;

use core\exception\moodle_exception;

/**
 * Exception thrown when a course reset takes too long
 *
 * @package   core_course
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
class reset_timeout extends moodle_exception {
    /**
     * Set the timeout message including details of the course.
     *
     * @param string $shortname
     */
    public function __construct(string $shortname) {
        parent::__construct('resettimeout', 'course', a: $shortname);
    }

    /**
     * Throw an exception if the timeout has been exceeded.
     *
     * This uses the provided $progress object to check whether the progress of the task is being tracked. If not, and the provided
     * time limit has passed, then we throw an exception.
     *
     * @param int $courseid The course ID.
     * @param ?int $timelimit The unix timestamp of the time limit to check. null means no limit.
     */
    public static function check_reset_timeout(int $courseid, ?int $timelimit): void {
        global $DB;
        if (!is_null($timelimit) && time() > $timelimit) {
            $shortname = $DB->get_field('course', 'shortname', ['id' => $courseid]);
            throw new reset_timeout($shortname);
        }
    }
}
