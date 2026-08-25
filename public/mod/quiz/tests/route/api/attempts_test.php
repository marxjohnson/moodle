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

use core\context\course;
use core\router\route_loader_interface;
use core\tests\router\route_testcase;
use mod_quiz\output\grades\grade_out_of;
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
        // Prevent theme initialisation in quiz setup.
        filter_set_global_state('emoticon', TEXTFILTER_OFF);

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
        $this->assertEqualsWithDelta(time(), $review['attempt']['gradednotificationsenttime'], 1.0);
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
        $this->assertEquals(
            json_decode(json_encode(new grade_out_of($attemptobj->get_quiz(), 1.0, 1.0, 'Listening')), true),
            $review['attempt']['gradeitemmarks'][0],
        );
        $this->assertEquals(
            json_decode(json_encode(new grade_out_of($attemptobj->get_quiz(), 0, 1.0, 'Reading')), true),
            $review['attempt']['gradeitemmarks'][1],
        );
    }

    /**
     * Getting a review in submitted state succeeds, with null grades and feedback.
     *
     * @return void
     */
    public function test_get_review_submitted(): void {
        global $DB;
        $this->resetAfterTest();
        $this->add_class_routes_to_route_loader(
            attempts::class,
            route_loader_interface::ROUTE_GROUP_API,
        );
        // Prevent theme initialisation in quiz setup.
        filter_set_global_state('emoticon', TEXTFILTER_OFF);

        // Create a new quiz with two questions and one attempt submitted.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        [$quiz, , $quizobj] = $this->create_quiz_with_questions($course->id, $student->id);
        [, $attemptobj] = $this->create_quiz_attempt_object($quizobj, $student->id);
        $this->answer_attempt($attemptobj, [1 => ['answer' => 1]]);
        $attemptobj->process_submit(time(), true);

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

        // Two questions, one completed but ungraded, the other not complete.
        $this->assertNull($review['grade']);
        $this->assertEquals(1, $review['attempt']['attempt']);
        $this->assertEquals('submitted', $review['attempt']['state']);
        $this->assertNull($review['attempt']['gradednotificationsenttime']);
        $this->assertNull($review['attempt']['sumgrades']);
        $this->assertCount(2, $review['questions']);
        $this->assertEquals('complete', $review['questions'][0]['state']);
        $this->assertEquals(1, $review['questions'][0]['slot']);
        $this->assertEquals('todo', $review['questions'][1]['state']);
        $this->assertEquals(2, $review['questions'][1]['slot']);

        // No feedback.
        $this->assertEmpty($review['additionaldata']);

        // No additional grades.
        $this->assertEmpty($review['attempt']['gradeitemmarks']);
    }

    /**
     * Getting a review in submitted state succeeds, with null grades and feedback.
     *
     * @return void
     */
    public function test_get_review_paged(): void {
        $this->resetAfterTest();
        $this->add_class_routes_to_route_loader(
            attempts::class,
            route_loader_interface::ROUTE_GROUP_API,
        );
        // Prevent theme initialisation in quiz setup.
        filter_set_global_state('emoticon', TEXTFILTER_OFF);

        // Create a new quiz with two questions on the first page, one on the second, and one attempt finished.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        $this->setUser($student);
        [$quiz, , $quizobj] = $this->create_quiz_with_questions($course->id, $student->id);
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('numerical', null, ['category' => $cat->id]);
        quiz_add_quiz_question($question->id, $quiz, 1);
        [, $attemptobj] = $this->create_quiz_attempt_object($quizobj, $student->id);
        $this->answer_attempt($attemptobj, [1 => ['answer' => 1], 2 => ['answer' => 1], 3 => ['answer' => 1]], true);

        $response = $this->process_api_request('GET', "/attempts/{$attemptobj->get_attemptid()}/review");
        $this->assert_valid_response($response);
        $review = $this->decode_response($response, true);

        // No page parameter, we get all questions.
        $this->assertEquals(1, $review['attempt']['attempt']);
        $this->assertEquals('finished', $review['attempt']['state']);
        $this->assertCount(3, $review['questions']);
        $this->assertEquals(1, $review['questions'][0]['slot']);
        $this->assertEquals(2, $review['questions'][1]['slot']);
        $this->assertEquals(3, $review['questions'][2]['slot']);

        $response = $this->process_api_request('GET', "/attempts/{$attemptobj->get_attemptid()}/review?page=0");
        $this->assert_valid_response($response);
        $review = $this->decode_response($response, true);

        // Page 0, just the first 2 questions.
        $this->assertEquals(1, $review['attempt']['attempt']);
        $this->assertEquals('finished', $review['attempt']['state']);
        $this->assertCount(2, $review['questions']);
        $this->assertEquals(1, $review['questions'][0]['slot']);
        $this->assertEquals(2, $review['questions'][1]['slot']);


        $response = $this->process_api_request('GET', "/attempts/{$attemptobj->get_attemptid()}/review?page=1");
        $this->assert_valid_response($response);
        $review = $this->decode_response($response, true);

        // Page 1, just the third question.
        $this->assertEquals(1, $review['attempt']['attempt']);
        $this->assertEquals('finished', $review['attempt']['state']);
        $this->assertCount(1, $review['questions']);
        $this->assertEquals(3, $review['questions'][0]['slot']);
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
        // Prevent theme initialisation in quiz setup.
        filter_set_global_state('emoticon', TEXTFILTER_OFF);

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
        // Prevent theme initialisation in quiz setup.
        filter_set_global_state('emoticon', TEXTFILTER_OFF);

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
        // Prevent theme initialisation in quiz setup.
        filter_set_global_state('emoticon', TEXTFILTER_OFF);

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

    /**
     * Trying to get review your own attempt when you don't have permission to see the review returns 403 Forbidden.
     */
    public function test_get_review_no_capability(): void {
        global $DB;
        $this->resetAfterTest();
        $this->add_class_routes_to_route_loader(
            attempts::class,
            route_loader_interface::ROUTE_GROUP_API,
        );
        // Prevent theme initialisation in quiz setup.
        filter_set_global_state('emoticon', TEXTFILTER_OFF);

        $generator = $this->getDataGenerator();
        $course = $generator->create_course();
        $student = $generator->create_user();
        $generator->enrol_user($student->id, $course->id, 'student');
        // Create a new quiz with two questions and one attempt finished.
        [, , , , $attemptobj] = $this->create_quiz_with_questions($course->id, $student->id, true, true);
        // Prohibit students from reviewing their own attempts.
        $generator->create_role_capability(
            $DB->get_field('role', 'id', ['shortname' => 'student']),
            ['mod/quiz:attempt' => 'prohibit'],
            course::instance($course->id),
        );
        // Try to review the attempt as the student.
        $this->setUser($student);
        $response = $this->process_api_request('GET', "/attempts/{$attemptobj->get_attemptid()}/review");
        $this->assert_valid_response($response, 403);
    }
}
