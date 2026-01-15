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

namespace core_question\local\bank;

use advanced_testcase;
use core\context\course;
use core\context\module;

/**
 * Unit tests for \core_question\local\bank\question_counts
 *
 * @package   core_question
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \core_question\local\bank\question_counts
 */
final class question_counts_test extends advanced_testcase {
    /**
     * An empty bank should return a count of 0.
     */
    public function test_by_course_empty(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = self::getDataGenerator()->create_course();
        $qbank = self::getDataGenerator()->create_module('qbank', ['course' => $course->id]);

        $counts = new question_counts();

        $this->assertEquals([$qbank->cmid => 0], $counts->by_course(course::instance($course->id)));
    }

    /**
     * A bank should return the correct number of questions.
     */
    public function test_by_course_questions(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = self::getDataGenerator()->create_course();
        $qbank = self::getDataGenerator()->create_module('qbank', ['course' => $course->id]);
        $bankcontext = module::instance($qbank->cmid);
        $category = question_get_default_category($bankcontext->id, true);
        $questiongenerator = self::getDataGenerator()->get_plugin_generator('core_question');
        $questiongenerator->create_question('truefalse', overrides: ['category' => $category->id]);
        $questiongenerator->create_question('truefalse', overrides: ['category' => $category->id]);

        $counts = new question_counts();

        $this->assertEquals([$qbank->cmid => 2], $counts->by_course(course::instance($course->id)));

        $questiongenerator->create_question('truefalse', overrides: ['category' => $category->id]);

        $this->assertEquals([$qbank->cmid => 3], $counts->by_course(course::instance($course->id)));
    }

    /**
     * A question with multiple versions should only be counted once.
     */
    public function test_by_course_versions(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = self::getDataGenerator()->create_course();
        $qbank = self::getDataGenerator()->create_module('qbank', ['course' => $course->id]);
        $bankcontext = module::instance($qbank->cmid);
        $category = question_get_default_category($bankcontext->id, true);
        $questiongenerator = self::getDataGenerator()->get_plugin_generator('core_question');
        $q1 = $questiongenerator->create_question('truefalse', overrides: ['category' => $category->id]);
        $questiongenerator->update_question($q1, overrides: ['questiontext' => 'edited']);
        $questiongenerator->create_question('truefalse', overrides: ['category' => $category->id]);

        $counts = new question_counts();

        $this->assertEquals([$qbank->cmid => 2], $counts->by_course(course::instance($course->id)));
    }

    /**
     * Subquestions should not be included in the question bank's total
     */
    public function test_by_course_subquestions(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = self::getDataGenerator()->create_course();
        $qbank = self::getDataGenerator()->create_module('qbank', ['course' => $course->id]);
        $bankcontext = module::instance($qbank->cmid);
        $category = question_get_default_category($bankcontext->id, true);
        $questiongenerator = self::getDataGenerator()->get_plugin_generator('core_question');
        $questiongenerator->create_question('multianswer', 'twosubq', ['category' => $category->id]);

        $counts = new question_counts();

        $this->assertEquals([$qbank->cmid => 1], $counts->by_course(course::instance($course->id)));
    }

    /**
     * Hidden questions should not be included in the question bank's total
     */
    public function test_by_course_hidden_questions(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = self::getDataGenerator()->create_course();
        $qbank = self::getDataGenerator()->create_module('qbank', ['course' => $course->id]);
        $bankcontext = module::instance($qbank->cmid);
        $category = question_get_default_category($bankcontext->id, true);
        $questiongenerator = self::getDataGenerator()->get_plugin_generator('core_question');
        $questiongenerator->create_question('truefalse', overrides: ['category' => $category->id]);
        $hiddenquestion = $questiongenerator->create_question('truefalse', overrides: ['category' => $category->id]);
        $questiongenerator->update_question($hiddenquestion, null, ['status' => question_version_status::QUESTION_STATUS_HIDDEN]);

        $counts = new question_counts();

        $this->assertEquals([$qbank->cmid => 1], $counts->by_course(course::instance($course->id)));
    }

    /**
     * All course modules using the question bank should have their count returned.
     */
    public function test_question_count_multiple_banks(): void {
        $this->resetAfterTest();
        $this->setAdminUser();
        $course = self::getDataGenerator()->create_course();
        $qbank1 = self::getDataGenerator()->create_module('qbank', ['course' => $course->id]);
        $qbank2 = self::getDataGenerator()->create_module('qbank', ['course' => $course->id]);
        $qbank3 = self::getDataGenerator()->create_module('qbank', ['course' => $course->id]);
        // Quizzes can have questions too.
        $quiz = self::getDataGenerator()->create_module('quiz', ['course' => $course->id]);
        // Pages can't have questions. This cmid should not be in the list of counts.
        self::getDataGenerator()->create_module('page', ['course' => $course->id]);

        $questiongenerator = self::getDataGenerator()->get_plugin_generator('core_question');
        // Generate questions.
        // 1 in qbank 1.
        $bank1context = module::instance($qbank1->cmid);
        $category1 = question_get_default_category($bank1context->id, true);
        $questiongenerator->create_question('truefalse', overrides: ['category' => $category1->id]);
        // None in qbank 2.
        // 3 in qbank 3.
        $bank3context = module::instance($qbank3->cmid);
        $category3 = question_get_default_category($bank3context->id, true);
        $questiongenerator->create_question('truefalse', overrides: ['category' => $category3->id]);
        $questiongenerator->create_question('truefalse', overrides: ['category' => $category3->id]);
        $questiongenerator->create_question('truefalse', overrides: ['category' => $category3->id]);
        // 2 in the quiz.
        $quizcontext = module::instance($quiz->cmid);
        $category4 = question_get_default_category($quizcontext->id, true);
        $questiongenerator->create_question('truefalse', overrides: ['category' => $category4->id]);
        $questiongenerator->create_question('truefalse', overrides: ['category' => $category4->id]);

        $counts = new question_counts();

        $this->assertEquals(
            [
                $qbank1->cmid => 1,
                $qbank2->cmid => 0,
                $qbank3->cmid => 3,
                $quiz->cmid => 2,
            ],
            $counts->by_course(course::instance($course->id)),
        );
    }
}
