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
use core\task\manager;
use core\task\logging_trait;
use core\task\stored_progress_task_trait;

/**
 * Asynchronously reset a course
 *
 * @package   core_course
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later */
class reset_course extends adhoc_task {
    use logging_trait;
    use stored_progress_task_trait;

    /**
     * Create and return an instance of this task.
     *
     * @param \stdClass $data The form data submitted for the task.
     * @return self
     */
    public static function create(\stdClass $data): self {
        $task = new reset_course();
        $task->set_custom_data($data);
        $task->set_component('core_course');
        return $task;
    }

    /**
     * Load an existing task from the database.
     *
     * @param int $id The task ID.
     * @return self
     */
    public static function load(int $id): self {
        global $DB;
        $customdata = $DB->get_field('task_adhoc', 'customdata', ['id' => $id], strictness: MUST_EXIST);
        $task = new reset_course();
        $task->set_id($id);
        $task->set_custom_data(json_decode($customdata));
        $task->set_component('core_course');
        return $task;
    }

    /**
     * Get the cache-accelerated task ID for the given course.
     *
     * @param int $courseid
     * @return ?int The task ID if a reset is pending, or null if no reset is pending.
     */
    public static function get_taskid_for_course(int $courseid): ?int {
        $taskcache = \cache::make('core', 'course_reset_tasks');
        $taskid = $taskcache->get($courseid);
        if ($taskid === false) { // We may have `null` in the cache if there is no task. If so, we want to return that.
            $tasks = manager::get_adhoc_tasks('\\' . self::class);
            $taskid = null;
            foreach ($tasks as $task) {
                if ($task->get_custom_data()->id == $courseid) {
                    $taskid = $task->get_id();
                    break;
                }
            }
            $taskcache->set($courseid, $taskid);
        }
        return $taskid;
    }

    /**
     * If resetting the course fails, there's probably something seriously broken that needs fixing before we try again.
     *
     * @return bool
     */
    public function retry_until_success(): bool {
        return false;
    }

    /**
     * Run reset_course_userdata for the provided course id.
     *
     * @return void
     */
    public function execute(): void {
        $data = $this->get_custom_data();
        $taskcache = \cache::make('core', 'course_reset_tasks');
        $this->start_stored_progress();
        $this->log_start(get_string('resettingcourse', 'course', $data->id));
        // Ensure the course exists.
        try {
            $course = get_course($data->id);
        } catch (\dml_missing_record_exception $e) {
            $this->log(get_string('resettingcoursenotfound', 'course', $data->id));
            // The course doesn't exist anymore, we dont need the cache entry.
            $taskcache->delete($data->id);
            return;
        }
        $this->log(get_string('resettingcoursefound', 'course', $course->shortname));
        try {
            $results = reset_course_userdata($data, $this->get_progress(), maxseconds: null);
            // Work out the max length of each column for nicer formatting.
            $done = get_string('statusdone');
            $lengths = array_reduce(
                $results,
                function ($carry, $result) {
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
            $this->log_finish(get_string('resetcomplete', 'course'));
        } finally {
            // Even if the task fails, clear the cached task ID.
            $taskcache->set($course->id, null);
        }
    }
}
