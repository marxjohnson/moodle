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

namespace mod_quiz\route\api;

use core\router\route_loader_interface;
use core\tests\router\route_testcase;
use mod_quiz\quiz_attempt;
use mod_quiz\tests\attempt_helper_test_trait;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for attempts
 *
 * @package   mod_quiz
 * @copyright 2026 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(className: attempts::class)]
final class attempts_test extends route_testcase {
    use attempt_helper_test_trait;

    /**
     * Test getting a review with feedback and additional grade items via the route, and validate the response.
     */
    public function test_get_review(): void {
        global $DB;
        $this->resetAfterTest();
        $this->add_class_routes_to_route_loader(
            attempts::class,
            route_loader_interface::ROUTE_GROUP_API,
        );

        // Create a new quiz with two questions and one attempt finished.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        [$quiz, , , , $attemptobj] = $this->create_quiz_with_questions($course->id, $student->id, true, true);

        // Add feedback.
        $feedback = new \stdClass();
        $feedback->quizid = $quiz->id;
        $feedback->feedbacktext = 'Feedback text 1';
        $feedback->feedbacktextformat = 1;
        $feedback->mingrade = 49;
        $feedback->maxgrade = 100;
        $feedback->id = $DB->insert_record('quiz_feedback', $feedback);

        // Add some extra grade items.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $listeninggrade = $quizgenerator->create_grade_item(['quizid' => $attemptobj->get_quizid(), 'name' => 'Listening']);
        $readinggrade = $quizgenerator->create_grade_item(['quizid' => $attemptobj->get_quizid(), 'name' => 'Reading']);
        $structure = $attemptobj->get_quizobj()->get_structure();
        $structure->update_slot_grade_item($structure->get_slot_by_number(1), $listeninggrade->id);
        $structure->update_slot_grade_item($structure->get_slot_by_number(2), $readinggrade->id);
        // Reload the attempt object with the new grade items.
        $attemptobj = quiz_attempt::create($attemptobj->get_attemptid());

        $response = $this->process_api_request('GET', "/attempts/{$attemptobj->get_attemptid()}/review");
        $this->assert_valid_response($response);
        $review = $this->decode_response($response, true);

        // Two questions, one completed and correct, the other gave up.
        $this->assertEquals(50, $review['grade']);
        $this->assertEquals(1, $review['attempt']['attempt']);
        $this->assertEquals('finished', $review['attempt']['state']);
        $this->assertEquals(1, $review['attempt']['sumgrades']);
        $this->assertCount(2, $review['questions']);
        $this->assertEquals('gradedright', $review['questions'][0]['state']);
        $this->assertEquals(1, $review['questions'][0]['slot']);
        $this->assertEquals('gaveup', $review['questions'][1]['state']);
        $this->assertEquals(2, $review['questions'][1]['slot']);

        // Feedback.
        $this->assertCount(1, $review['additionaldata']);
        $this->assertEquals('feedback', $review['additionaldata'][0]['id']);
        $this->assertEquals('Feedback', $review['additionaldata'][0]['title']);
        $this->assertEquals('Feedback text 1', $review['additionaldata'][0]['content']);

        // Additional grades.
        $this->assertEquals(['name' => 'Listening', 'grade' => 1, 'maxgrade' => 1], $review['attempt']['gradeitemmarks'][0]);
        $this->assertEquals(['name' => 'Reading', 'grade' => 0, 'maxgrade' => 1], $review['attempt']['gradeitemmarks'][1]);
    }

    /**
     * Trying to get the review for a non-existent attempt returns 404 Not found.
     */
    public function test_get_review_no_attempt(): void {
        $this->resetAfterTest();
        $this->add_class_routes_to_route_loader(
            attempts::class,
            route_loader_interface::ROUTE_GROUP_API,
        );

        // Create a new quiz with two questions and one attempt finished.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);

        $response = $this->process_api_request('GET', "/attempts/1/review");
        $this->assert_valid_response($response, 404);
    }

    /**
     * Trying to get a review for an attempt that hasn't been submitted returns 400 Bad request.
     */
    public function test_get_review_not_submitted(): void {
        $this->resetAfterTest();
        $this->add_class_routes_to_route_loader(
            attempts::class,
            route_loader_interface::ROUTE_GROUP_API,
        );

        // Create a new quiz with two questions and one attempt finished.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        [, , , , $attemptobj] = $this->create_quiz_with_questions($course->id, $student->id, true);
        $response = $this->process_api_request('GET', "/attempts/{$attemptobj->get_attemptid()}/review");
        $this->assert_valid_response($response, 400);
    }

    /**
     * Trying to get a review you aren't allowed to see returns 403 Forbidden.
     */
    public function test_get_review_not_allowed(): void {
        $this->resetAfterTest();
        $this->add_class_routes_to_route_loader(
            attempts::class,
            route_loader_interface::ROUTE_GROUP_API,
        );

        // Create a new quiz with two questions and one attempt finished.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $student2 = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $generator->enrol_user($student2->id, $course->id, 'student');
        [, , , , $attemptobj] = $this->create_quiz_with_questions($course->id, $student->id, true, true);
        // Try to review a different user's attempt.
        $this->setUser($student2);
        $response = $this->process_api_request('GET', "/attempts/{$attemptobj->get_attemptid()}/review");
        $this->assert_valid_response($response, 403);
    }
}
