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

namespace core_course\task;

use core\task\adhoc_task;

/**
 * Asynchronously reset a course
 *
 * @package   core_course
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
class reset_course extends adhoc_task {
    use \core\task\logging_trait;
    use \core\task\stored_progress_task_trait;

    /**
     * @var int The course weight above which an ad-hoc reset will be used.
     */
    const ADHOC_THRESHOLD = 1000;

    /**
     * Create and return an instance of this task for a given course ID.
     *
     * @param \stdClass $data
     * @return self
     */
    public static function create(\stdClass $data): self {
        $task = new reset_course();
        $task->set_custom_data($data);
        $task->set_component('core_course');
        return $task;
    }

    /**
     * Get the customdata for an instance of this task for the given course ID.
     *
     * We save all the options selected on the reset form in the task's custom data, which makes it difficult to reconstruct
     * for the purposes of displaying a task indicator. As we shouldn't be resetting the same course multiple times at once,
     * this lets us search for an instance of the task containing the course ID in the customdata, then returns the whole field
     * to be passed to {@see self::create()}.
     *
     * @param int $courseid
     * @return \stdClass|null
     * @throws \dml_exception
     */
    public static function get_data_by_courseid(int $courseid): ?\stdClass {
        global $DB;
        $where = 'classname = ? AND ' . $DB->sql_like('customdata', '?');
        $params = [
            '\\' . self::class,
            '%"id":' . $courseid . ',%',
        ];
        $customdata = $DB->get_field_select('task_adhoc', 'customdata', $where, $params);
        return $customdata ? json_decode($customdata) : null;
    }

    /**
     * Run reset_course_userdata for the provided course id.
     *
     * @return void
     * @throws \dml_exception
     */
    public function execute(): void {
        $data = $this->get_custom_data();
        $this->start_stored_progress();
        $this->log_start("Resetting course ID {$data->id}");
        // Ensure the course exists.
        try {
            $course = get_course($data->id);
        } catch (\dml_missing_record_exception $e) {
            $this->log("Course with id {$data->id} not found. It may have been deleted. Skipping reset.");
            return;
        }
        $this->log("Found course {$course->shortname}. Starting reset.");
        $results = reset_course_userdata($data, $this->get_progress());

        // Work out the max length of each column for nicer formatting.
        $done = get_string('statusdone');
        $lengths = array_reduce(
            $results,
            function($carry, $result) {
                foreach ($carry as $key => $length) {
                    $carry[$key] = max(strlen($result[$key]), $length);
                }
                return $carry;
            },
            ['component' => 0, 'item' => 0, 'error' => 0]
        );
        $lengths['error'] = max(strlen($done), $lengths['error']);

        $this->log(
            str_pad(get_string('resetcomponent'), $lengths['component']) . ' | ' .
                str_pad(get_string('resettask'), $lengths['item']) . ' | ' .
                str_pad(get_string('resetstatus'), $lengths['error'])
        );
        foreach ($results as $result) {
            $this->log(
                str_pad($result['component'], $lengths['component']) . ' | ' .
                    str_pad($result['item'], $lengths['item']) . ' | ' .
                    str_pad($result['error'] ?: $done, $lengths['error'])
            );
        }
        $this->log_finish('Reset complete.');
    }
}
