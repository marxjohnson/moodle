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

namespace mod_quiz\tests;

use core\exception\moodle_exception;
use mod_quiz\quiz_attempt;
use mod_quiz\quiz_settings;

/**
 * Helper trait for quiz attempt tests
 *
 * @package   mod_quiz
 * @copyright 2026 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait attempt_helper_test_trait {
    /**
     * Create a quiz with questions including a started or finished attempt optionally
     *
     * @param int $courseid The course to create the quiz on.
     * @param int $studentid The student who will be attempting the quiz.
     * @param  bool $startattempt whether to start a new attempt
     * @param  bool $finishattempt whether to finish the new attempt
     * @param  string $behaviour the quiz preferredbehaviour, defaults to 'deferredfeedback'.
     * @param  bool $includeqattachments whether to include a question that supports attachments, defaults to false.
     * @param  array $extraoptions extra options for Quiz.
     * @return array array containing the quiz, context and the attempt
     */
    private function create_quiz_with_questions(
        int $courseid,
        int $studentid,
        bool $startattempt = false,
        bool $finishattempt = false,
        string $behaviour = 'deferredfeedback',
        bool $includeqattachments = false,
        array $extraoptions = []
    ) {

        // Create a new quiz with attempts.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $data = [
            'course' => $courseid,
            'sumgrades' => 2,
            'preferredbehaviour' => $behaviour,
        ];
        $data = array_merge($data, $extraoptions);
        $quiz = $quizgenerator->create_instance($data);
        $context = \context_module::instance($quiz->cmid);

        // Create a couple of questions.
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('numerical', null, ['category' => $cat->id]);
        quiz_add_quiz_question($question->id, $quiz);
        $question = $questiongenerator->create_question('numerical', null, ['category' => $cat->id]);
        quiz_add_quiz_question($question->id, $quiz);

        if ($includeqattachments) {
            $question = $questiongenerator->create_question(
                'essay',
                null,
                ['category' => $cat->id, 'attachments' => 1, 'attachmentsrequired' => 1],
            );
            quiz_add_quiz_question($question->id, $quiz);
        }

        $quizobj = quiz_settings::create($quiz->id, $studentid);

        // Set grade to pass.
        $item = \grade_item::fetch(
            [
                'courseid' => $courseid,
                'itemtype' => 'mod',
                'itemmodule' => 'quiz',
                'iteminstance' => $quiz->id,
                'outcomeid' => null,
            ],
        );
        $item->gradepass = 80;
        $item->update();

        if ($startattempt || $finishattempt) {
            [$attempt, $attemptobj, $quba] = $this->create_quiz_attempt_object(quizobj: $quizobj, userid: $studentid);
            if ($finishattempt) {
                $this->answer_attempt(attemptobj: $attemptobj, finish: true);
            }

            return [$quiz, $context, $quizobj, $attempt, $attemptobj, $quba];
        } else {
            return [$quiz, $context, $quizobj];
        }
    }

    /**
     * Create question attempt
     *
     * @param quiz_settings $quizobj
     * @param int|null $userid
     * @param bool|null $ispreview
     * @param bool|null $started Whether the attempt should be created as started or not.
     * @param int|null $time Timestamp to use as start time for the attempt.
     * @return array Array containing the attempt record, attempt object and QUBA.
     * @throws moodle_exception
     */
    private function create_quiz_attempt_object(
        quiz_settings $quizobj,
        int $userid,
        ?bool $ispreview = false,
        ?bool $started = true,
        ?int $time = null,
    ): array {
        $time = $time ?? time();

        // Now, do one attempt.
        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
        $attemptnumber = count(quiz_get_user_attempts($quizobj->get_quizid(), $userid, 'all')) + 1;
        $attempt = quiz_create_attempt($quizobj, $attemptnumber, false, $time, $ispreview, $userid);
        quiz_start_new_attempt($quizobj, $quba, $attempt, $attemptnumber, $time);

        if ($started) {
            quiz_attempt_save_started($quizobj, $quba, $attempt);
        } else {
            quiz_attempt_save_not_started($quba, $attempt);
        }

        $attemptobj = quiz_attempt::create($attempt->id);
        return [$attempt, $attemptobj, $quba];
    }

    /**
     * Add answers to an attempt, and optionally finish it.
     *
     * @param quiz_attempt $attemptobj The attempt object to answer.
     * @param array|null $answers The answers to submit.
     * @param bool|null $finish Whether to finish the attempt or not.
     * @param int|null $time Timestamp to use as answer/finish time.
     */
    private function answer_attempt(
        quiz_attempt $attemptobj,
        ?array $answers = [1 => ['answer' => '3.14']],
        ?bool $finish = false,
        ?int $time = null,
    ): void {
        $time = $time ?? time();

        $attemptobj->process_submitted_actions($time, false, $answers);

        if ($finish) {
            $attemptobj->process_submit($time, false);
            $attemptobj->process_grade_submission($time);
        }
    }
}
