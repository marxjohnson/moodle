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

namespace qbank_usage;

use core_question\local\bank\view;
use mod_quiz\quiz_attempt;

/**
 * Unit tests for question_usage_column
 *
 * @package   qbank_usage
 * @copyright 2024 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \qbank_usage\question_usage_column
 */
class question_usage_column_test extends \advanced_testcase {
    use \mod_quiz\tests\question_helper_test_trait;

    /**
     * Test setup.
     */
    public function get_quiz_with_questions(): array {
        $layout = '1,2,0';
        // Make a user to do the quiz.
        $user = $this->getDataGenerator()->create_user();
        $course = $this->getDataGenerator()->create_course();
        // Make a quiz.
        $quizgenerator = $this->getDataGenerator()->get_plugin_generator('mod_quiz');
        $quiz = $quizgenerator->create_instance(['course' => $course->id,
            'grade' => 100.0, 'sumgrades' => 2, 'layout' => $layout]);

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        $cat = $questiongenerator->create_question_category();

        $page = 1;
        foreach (explode(',', $layout) as $slot) {
            if ($slot == 0) {
                $page += 1;
                continue;
            }

            $question = $questiongenerator->create_question('shortanswer', null, ['category' => $cat->id]);
            quiz_add_quiz_question($question->id, $quiz, $page);
            $questions[] = $question;
        }
        return [
            $user,
            $quiz,
            $questions,
            $questiongenerator,
            $cat,
        ];
    }

    /**
     * Return a column object with using a mocked qbank.
     *
     * This allows us to generate a column object with specific versions enabled, or not.
     *
     * @return question_usage_column
     */
    protected function get_column(bool $specificversion = false): question_usage_column {
        $qbank = $this->createMock(view::class);
        $qbank->expects(self::any())->method('is_listing_specific_versions')->willReturn($specificversion);
        return new question_usage_column($qbank);
    }

    /**
     * Generate SQL for verifying the usage count.
     *
     * This SQL will take the required fields and extra joins from the column, and join them on to the
     * question_versions, question_bank_entries and question tables. The query takes the question version
     * as a parameter, so we are getting the count for the question with that version. Whether we look
     * for that specific version or any version depends on the setup of $column {@see get_column()}.
     *
     * @param question_usage_column $column The column instance.
     * @return string
     */
    protected function get_usagecount_sql(question_usage_column $column): string {
        $fields = implode(',', $column->get_required_fields());
        $joins = implode(' ', $column->get_extra_joins());
        return "
            SELECT {$fields}
              FROM {question_versions} qv
                   JOIN {question_bank_entries} qbe ON qv.questionbankentryid = qbe.id
                   JOIN {question} q ON q.id = qv.questionid
                   {$joins}
             WHERE qv.id = ?";
    }

    /**
     * Record a quiz attempt.
     *
     * @return void
     */
    protected function attempt_quiz(\stdClass $user, \stdClass $quiz): void {
        $quizobj = \mod_quiz\quiz_settings::create($quiz->id, $user->id);

        $quba = \question_engine::make_questions_usage_by_activity('mod_quiz', $quizobj->get_context());
        $quba->set_preferred_behaviour($quizobj->get_quiz()->preferredbehaviour);
        $timenow = time();
        $attempt = quiz_create_attempt($quizobj, 1, false, $timenow, false, $user->id);
        quiz_start_new_attempt($quizobj, $quba, $attempt, 1, $timenow);
        quiz_attempt_save_started($quizobj, $quba, $attempt);
        quiz_attempt::create($attempt->id);
    }

    /**
     * Test test usage data.
     *
     * @covers ::get_question_entry_usage_count
     */
    public function test_get_question_entry_usage_count(): void {
        global $DB;
        $this->resetAfterTest();
        [, , , , $questions] = $this->get_quiz_with_questions();
        $sql = $this->get_usagecount_sql($this->get_column());
        foreach ($questions as $question) {
            $qdef = \question_bank::load_question($question->id);
            $count = $DB->count_records_sql($sql, [$qdef->versionid]);
            // Test that the attempt data matches the usage data for the count.
            $this->assertEquals(1, $count);
        }
    }

    /**
     * Test test usage data with filters added to the subquery.
     *
     * @covers ::get_question_entry_usage_count
     */
    public function test_get_question_entry_usage_count_with_filters(): void {
        global $DB;
        $this->resetAfterTest();
        [, , , , $questions, , $cat] = $this->get_quiz_with_questions();
        $column = $this->get_column();
        $column->set_filter_conditions(['qbe.questioncategoryid = :cat'], ['cat' => $cat->id]);
        $sql = $this->get_usagecount_sql($column);
        foreach ($questions as $question) {
            $qdef = \question_bank::load_question($question->id);
            $count = $DB->count_records_sql($sql, [$qdef->versionid]);
            // Test that the attempt data matches the usage data for the count.
            $this->assertEquals(1, $count);
        }
    }

    /**
     * Test test usage data with filters added to the subquery that do not match.
     *
     * @covers ::get_question_entry_usage_count
     */
    public function test_get_question_entry_usage_count_with_filters_no_match(): void {
        global $DB;
        $this->resetAfterTest();
        [, , , , $questions, $questiongenerator] = $this->get_quiz_with_questions();
        $newcat = $questiongenerator->create_question_category();
        $column = $this->get_column();
        $column->set_filter_conditions(['qbe.questioncategoryid = :cat'], ['cat' => $newcat->id]);
        $sql = $this->get_usagecount_sql($column);
        foreach ($questions as $question) {
            $qdef = \question_bank::load_question($question->id);
            $count = $DB->count_records_sql($sql, [$qdef->versionid]);
            // Test that no usages are found when the subquery is filtered on a different category.
            $this->assertEquals(0, $count);
        }
    }

    /**
     * If a question has been included via a random question attempt, this should be counted as a usage.
     *
     * @covers ::get_question_entry_usage_count
     * @return void
     */
    public function test_get_random_question_attempts_usage_count(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();
        [$user, $quiz, , $questiongenerator] = $this->get_quiz_with_questions();
        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('shortanswer', null, ['category' => $cat->id]);
        $qdef = \question_bank::load_question($question->id);
        $this->add_random_questions($quiz->id, 1, $cat->id, 1);

        $sql = $this->get_usagecount_sql($this->get_column());

        $count = $DB->count_records_sql($sql, [$qdef->versionid]);
        $this->assertEquals(0, $count);

        $this->attempt_quiz($user, $quiz);

        $count = $DB->count_records_sql($sql, [$qdef->versionid]);
        $this->assertEquals(1, $count);
    }

    /**
     * When a question referenced directly is edited, the usage count of all versions remains the same.
     *
     * When checking usage of separate versions, the new version should show usages but the original version should not.
     *
     * @covers ::get_question_entry_usage_count
     * @return void
     */
    public function test_edited_question_usage_counts(): void {
        global $DB;
        $this->resetAfterTest();
        [, , $questions, $questiongenerator] = $this->get_quiz_with_questions();
        $sql = $this->get_usagecount_sql($this->get_column());
        $versionsql = $this->get_usagecount_sql($this->get_column(specificversion: true));
        foreach ($questions as $question) {
            $qdef = \question_bank::load_question($question->id);
            $count1 = $DB->count_records_sql($sql, [$qdef->versionid]);

            // Each question should have 1 usage.
            $this->assertEquals(1, $count1);

            $newversion = $questiongenerator->update_question($question);
            $newqdef = \question_bank::load_question($newversion->id);

            // Either version should return the same count if not checking a specific version.
            $count2 = $DB->count_records_sql($sql, [$qdef->versionid]);
            $this->assertEquals(1, $count2);
            $count3 = $DB->count_records_sql($sql, [$newqdef->versionid]);
            $this->assertEquals(1, $count3);
            // Checking the specific version count should return the counts for each version.
            // The original version is no longer included in the quiz, so has 0 usages.
            $count4 = $DB->count_records_sql($versionsql, [$qdef->versionid]);
            $this->assertEquals(0, $count4);
            // The new version is now included in the quiz, so has 1 usage.
            $count5 = $DB->count_records_sql($versionsql, [$newqdef->versionid]);
            $this->assertEquals(1, $count5);
        }
    }

    /**
     * When a question referenced directly with attempts is edited, the usage count of all versions remains the same.
     *
     * When checking usage of separate versions, both versions should show usage.
     *
     * @covers ::get_question_entry_usage_count
     * @return void
     */
    public function test_edited_attempted_question_usage_counts(): void {
        global $DB;
        $this->resetAfterTest();
        [$user, $quiz, $questions, $questiongenerator] = $this->get_quiz_with_questions();
        $this->attempt_quiz($user, $quiz);
        $sql = $this->get_usagecount_sql($this->get_column());
        $versionsql = $this->get_usagecount_sql($this->get_column(specificversion: true));
        foreach ($questions as $question) {
            $qdef = \question_bank::load_question($question->id);
            $count1 = $DB->count_records_sql($sql, [$qdef->versionid]);
            // Each question should have 1 usage.
            $this->assertEquals(1, $count1);

            $newversion = $questiongenerator->update_question($question);
            $newqdef = \question_bank::load_question($newversion->id);

            // Either version should return the same count if not checking a specific version.
            $count2 = $DB->count_records_sql($sql, [$qdef->versionid]);
            $this->assertEquals(1, $count2);
            $count3 = $DB->count_records_sql($sql, [$newqdef->versionid]);
            $this->assertEquals(1, $count3);
            // Checking the specific version count should return the counts for each version.
            // The original version is no longer included in the quiz. However, the is still an attempt using this question version,
            // so it has 1 usage.
            $count4 = $DB->count_records_sql($versionsql, [$qdef->versionid]);
            $this->assertEquals(1, $count4);
            // The new version is now included in the quiz, so has 1 usage.
            $count5 = $DB->count_records_sql($versionsql, [$newqdef->versionid]);
            $this->assertEquals(1, $count5);
        }
    }

    /**
     * When a random question with attempts is edited, it should still have the same usage count.
     *
     * When checking usage of separate versions, the original version should still show usage but the new version should not.
     *
     * @covers ::get_question_entry_usage_count
     * @return void
     */
    public function test_edited_attempted_random_question_usage_count(): void {
        global $DB;
        $this->resetAfterTest();
        [$user, $quiz, , $questiongenerator] = $this->get_quiz_with_questions();
        $sql = $this->get_usagecount_sql($this->get_column());
        $versionsql = $this->get_usagecount_sql($this->get_column(specificversion: true));
        $this->setAdminUser();
        $cat = $questiongenerator->create_question_category();
        $question = $questiongenerator->create_question('shortanswer', null, ['category' => $cat->id]);
        $this->add_random_questions($quiz->id, 1, $cat->id, 1);

        $this->attempt_quiz($user, $quiz);

        $qdef = \question_bank::load_question($question->id);
        $count1 = $DB->count_records_sql($sql, [$qdef->versionid]);
        $this->assertEquals(1, $count1);

        $newversion = $questiongenerator->update_question($question);
        $newqdef = \question_bank::load_question($newversion->id);

        // Either version should return the same count if not checking a specific version.
        $count2 = $DB->count_records_sql($sql, [$qdef->versionid]);
        $this->assertEquals(1, $count2);
        $count3 = $DB->count_records_sql($sql, [$newqdef->versionid]);
        $this->assertEquals(1, $count3);
        // Checking the specific version count should return the counts for each version.
        // There is still an attempt of the original version has part of the random question attempt, so it has 1 usage.
        $count4 = $DB->count_records_sql($versionsql, [$qdef->versionid]);
        $this->assertEquals(1, $count4);
        // There is no attempt of the new version, so it has 0 usages.
        $count5 = $DB->count_records_sql($versionsql, [$newqdef->versionid]);
        $this->assertEquals(0, $count5);
    }
}