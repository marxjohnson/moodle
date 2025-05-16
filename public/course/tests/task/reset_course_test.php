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

use core\task\manager;
use stdClass;

/**
 * Unit tests for reset_course
 *
 * @package   core_course
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \core_course\task\reset_course
 */
final class reset_course_test extends \advanced_testcase {
    /**
     * Skip processing if the course does not exist.
     */
    public function test_execute_course_not_found(): void {
        $this->resetAfterTest();
        // Define a reset task with dummy data
        $resetdata = new stdClass();
        $resetdata->id = 100;
        $resetdata->reset_start_date_old = time();
        $resetdata->reset_start_date = time();
        $resetdata->reset_end_date = time();
        $resetdata->reset_end_date_old = time();
        $resetdata->reset_notes = true;
        $task = reset_course::create($resetdata);

        $this->expectOutputRegex("~Resetting course ID 100~");
        $this->expectOutputRegex("~Course with id 100 not found. It may have been deleted. Skipping reset.~");
        // Run the task.
        $task->execute();
    }

    /**
     * Reset a course.
     */
    public function test_execute(): void {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/notes/lib.php');

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();

        // Create test course and user, enrol one in the other.
        $course = $generator->create_course();
        $user = $generator->create_user();
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        $generator->enrol_user($user->id, $course->id, $roleid);

        // Define a reset task.
        $resetdata = new stdClass();
        $resetdata->id = $course->id;
        $resetdata->reset_start_date_old = $course->startdate;
        $resetdata->reset_start_date = $course->startdate;
        $resetdata->reset_end_date = $course->enddate;
        $resetdata->reset_end_date_old = $course->enddate;
        $resetdata->reset_notes = true;
        $task = reset_course::create($resetdata);

        // Create a note that will be deleted by the reset.
        $note = (object) ['content' => 'Note 1', 'courseid' => $course->id];
        note_save($note);

        $this->assertTrue($DB->record_exists('post', ['module' => 'notes', 'courseid' => $course->id]));

        $this->expectOutputRegex("~Resetting course ID {$course->id}~");
        $this->expectOutputRegex("~Found course {$course->shortname}. Starting reset.~");
        $this->expectOutputRegex("~Delete notes~");
        $this->expectOutputRegex("~Reset complete~");
        // Run the task.
        $task->execute();

        // Verify the course has been reset.
        $this->assertFalse($DB->record_exists('post', ['module' => 'notes', 'courseid' => $course->id]));
    }

    /**
     * Get the task ID for a course, from the database or the cache.
     */
    public function test_get_taskid_for_course(): void {
        global $DB;
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();

        // Create test course and user, enrol one in the other.
        $course = $generator->create_course();
        $user = $generator->create_user();
        $roleid = $DB->get_field('role', 'id', ['shortname' => 'student'], MUST_EXIST);
        $generator->enrol_user($user->id, $course->id, $roleid);

        // Define a reset task.
        $resetdata = new stdClass();
        $resetdata->id = $course->id;
        $resetdata->reset_start_date_old = $course->startdate;
        $resetdata->reset_start_date = $course->startdate;
        $resetdata->reset_end_date = $course->enddate;
        $resetdata->reset_end_date_old = $course->enddate;
        $resetdata->reset_notes = true;
        $task = reset_course::create($resetdata);
        manager::queue_adhoc_task($task);

        // There is no task ID cached for the course.
        $cache = \cache::make('core', 'course_reset_tasks');
        $this->assertFalse($cache->get($course->id));

        // Get the task ID from the database.
        $taskid = reset_course::get_taskid_for_course($course->id);
        $this->assertNotEmpty($taskid);

        // The cache now has the task ID.
        $this->assertEquals($taskid, $cache->get($course->id));

        manager::delete_adhoc_task($taskid);

        // The method now gets the task ID from the cache.
        $this->assertEquals($taskid, reset_course::get_taskid_for_course($course->id));
    }

    /**
     * When there is no queued task, return null.
     */
    public function test_get_null_taskid_for_course(): void {
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();

        // Create test course and user, enrol one in the other.
        $course = $generator->create_course();

        // There is no task ID cached for the course.
        $cache = \cache::make('core', 'course_reset_tasks');
        $this->assertFalse($cache->get($course->id));

        // Look for the task ID, but there isn't one so we should get null.
        $taskid = reset_course::get_taskid_for_course($course->id);
        $this->assertNull($taskid);

        // The cache now has null.
        $this->assertNull($cache->get($course->id));

        // Define a reset task.
        $resetdata = new stdClass();
        $resetdata->id = $course->id;
        $resetdata->reset_start_date_old = $course->startdate;
        $resetdata->reset_start_date = $course->startdate;
        $resetdata->reset_end_date = $course->enddate;
        $resetdata->reset_end_date_old = $course->enddate;
        $resetdata->reset_notes = true;
        $task = reset_course::create($resetdata);
        manager::queue_adhoc_task($task);

        // The method now gets null from the cache.
        $this->assertNull(reset_course::get_taskid_for_course($course->id));
    }
}
