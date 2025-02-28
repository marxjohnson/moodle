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
namespace mod_quiz\backup;

use mod_quiz\quiz_settings;
use mod_quiz\structure;

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/question/engine/lib.php');
require_once($CFG->dirroot . '/mod/quiz/locallib.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/mod/quiz/tests/quiz_question_helper_test_trait.php');

/**
 * Unit tests for
 *
 * @package   mod_quiz
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_question_selection_test extends \advanced_testcase {
    use \quiz_question_helper_test_trait;

    /**
     * Test that backing up a quiz only includes the questions actually used by the quiz.
     */
    public function test_backup_exclude_unused_questions(): void {
        global $DB;
        $this->resetAfterTest();
        $manager = $this->getDataGenerator()->create_user();
        $this->setUser($manager);
        $course = $this->getDataGenerator()->create_course();
        $sharedcourse = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($manager->id, $course->id, 'manager');
        $this->getDataGenerator()->enrol_user($manager->id, $sharedcourse->id, 'manager');
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        // Create some question banks and a quiz with 2 categories each.

        $courseqbank = self::getDataGenerator()->create_module('qbank', ['course' => $course->id]);
        $courseqbankcontext = \context_module::instance($courseqbank->cmid);
        $courseparentcat = $questiongenerator->create_question_category([
            'name' => 'courseparentcat',
            'contextid' => $courseqbankcontext->id,
        ]);
        $coursechildcat = $questiongenerator->create_question_category([
            'name' => 'coursechildcat',
            'contextid' => $courseqbankcontext->id,
            'parent' => $courseparentcat->id,
        ]);
        $sharedqbank = self::getDataGenerator()->create_module('qbank', ['course' => $sharedcourse->id]);
        $sharedqbankcontext = \context_module::instance($sharedqbank->cmid);
        $sharedparentcat = $questiongenerator->create_question_category([
            'name' => 'sharedparentcat',
            'contextid' => $sharedqbankcontext->id,
        ]);
        $sharedchildcat = $questiongenerator->create_question_category([
            'name' => 'sharedchildcat',
            'contextid' => $sharedqbankcontext->id,
            'parent' => $sharedparentcat->id,
        ]);
        $quiz = $this->create_test_quiz($course);
        $quizcontext = \context_module::instance($quiz->cmid);
        $quizparentcat = $questiongenerator->create_question_category([
            'name' => 'quizparentcat',
            'contextid' => $quizcontext->id,
        ]);
        $quizchildcat = $questiongenerator->create_question_category([
            'name' => 'quizchildcat',
            'contextid' => $quizcontext->id,
            'parent' => $quizparentcat->id,
        ]);
        // Add 2 questions to each category.
        $courseq1 = $questiongenerator->create_question(
            'shortanswer',
            null,
            ['name' => 'courseq1', 'category' => $courseparentcat->id],
        );
        $courseq2 = $questiongenerator->create_question(
            'shortanswer',
            null,
            ['name' => 'courseq2', 'category' => $courseparentcat->id],
        );
        $courseq3 = $questiongenerator->create_question(
            'shortanswer',
            null,
            ['name' => 'courseq3', 'category' => $coursechildcat->id],
        );
        $courseq4 = $questiongenerator->create_question(
            'shortanswer',
            null,
            ['name' => 'courseq4', 'category' => $coursechildcat->id],
        );
        $sharedq1 = $questiongenerator->create_question(
            'shortanswer',
            null,
            ['name' => 'sharedq1', 'category' => $sharedparentcat->id],
        );
        $sharedq2 = $questiongenerator->create_question(
            'shortanswer',
            null,
            ['name' => 'sharedq2', 'category' => $sharedparentcat->id],
        );
        $sharedq3 = $questiongenerator->create_question(
            'shortanswer',
            null,
            ['name' => 'sharedq3', 'category' => $sharedchildcat->id],
        );
        $sharedq4 = $questiongenerator->create_question(
            'shortanswer',
            null,
            ['name' => 'sharedq4', 'category' => $sharedchildcat->id],
        );
        $quizq1 = $questiongenerator->create_question(
            'shortanswer',
            null,
            ['name' => 'quizq1', 'category' => $quizparentcat->id],
        );
        $quizq2 = $questiongenerator->create_question(
            'shortanswer',
            null,
            ['name' => 'quizq2', 'category' => $quizparentcat->id],
        );
        $quizq3 = $questiongenerator->create_question(
            'shortanswer',
            null,
            ['name' => 'quizq3', 'category' => $quizchildcat->id],
        );
        $quizq4 = $questiongenerator->create_question(
            'shortanswer',
            null,
            ['name' => 'quizq4', 'category' => $quizchildcat->id],
        );
        // Add an additional category with 3 questions, 2 with a tag and one without.
        $tagcategory = $questiongenerator->create_question_category([
            'name' => 'tagcategory',
            'contextid' => $courseqbankcontext->id,
        ]);
        $tagq1 = $questiongenerator->create_question(
            'shortanswer',
            null,
            ['name' => 'tagq1', 'category' => $tagcategory->id],
        );
        $questiongenerator->create_question_tag(['questionid' => $tagq1->id, 'tag' => 'mytag']);
        $tagq2 = $questiongenerator->create_question(
            'shortanswer',
            null,
            ['name' => 'tagq2', 'category' => $tagcategory->id],
        );
        $questiongenerator->create_question_tag(['questionid' => $tagq2->id, 'tag' => 'mytag']);
        $tagq3 = $questiongenerator->create_question(
            'shortanswer',
            null,
            ['name' => 'tagq3', 'category' => $tagcategory->id],
        );
        $tags = \core_tag_tag::get_item_tags('core_question', 'question', $tagq1->id);
        $mytag = reset($tags);

        // Add a question from the shared bank child category.
        quiz_add_quiz_question($sharedq3->id, $quiz);
        // Add a question from the course bank parent category.
        quiz_add_quiz_question($courseq2->id, $quiz);
        // Add a questions from the quiz bank categories.
        quiz_add_quiz_question($quizq1->id, $quiz);
        quiz_add_quiz_question($quizq4->id, $quiz);
        // Add a random question to select tagged questions.
        $settings = quiz_settings::create($quiz->id);
        $structure = structure::create_for_quiz($settings);
        $structure->add_random_questions(1, 1, [
            'filter' => [
                'category' => [
                    'jointype' => \core\output\datafilter::JOINTYPE_ANY,
                    'values' => [$tagcategory->id],
                    'filteroptions' => ['includesubcategories' => false],
                ],
                'qtagids' => [
                    'jointype' => \core\output\datafilter::JOINTYPE_ANY,
                    'values' => [$mytag->id],
                ],
            ],
        ]);

        // Backup the quiz.
        $bc = new \backup_controller(
            \backup::TYPE_1ACTIVITY,
            $quiz->cmid,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_IMPORT,
            $manager->id,
        );
        $backupid = $bc->get_backupid();
        $bc->execute_plan();
        $bc->destroy();

        $course2 = $this->getDataGenerator()->create_course();
        $this->getDataGenerator()->enrol_user($manager->id, $course2->id, 'manager');
        $rc = new \restore_controller($backupid, $course2->id, \backup::INTERACTIVE_NO, \backup::MODE_IMPORT,
            $manager->id, \backup::TARGET_CURRENT_ADDING);
        $rc->execute_precheck();
        $backupquestions = $DB->get_records_menu('backup_ids_temp', ['itemname' => 'question'], '', 'id, itemid');
        // Backup should contain used questions from shared qbanks.
        $this->assertContains((string) $sharedq3->id, $backupquestions);
        $this->assertContains((string) $courseq2->id, $backupquestions);
        // Backup should contain all questions from quiz's bank.
        $this->assertContains((string) $quizq1->id, $backupquestions);
        $this->assertContains((string) $quizq2->id, $backupquestions);
        $this->assertContains((string) $quizq3->id, $backupquestions);
        $this->assertContains((string) $quizq4->id, $backupquestions);
        // Backup should contain questions matched by random question filter.
        $this->assertContains((string) $tagq1->id, $backupquestions);
        $this->assertContains((string) $tagq2->id, $backupquestions);
        // All other questions should be excluded.
        $this->assertNotContains((string) $sharedq1->id, $backupquestions);
        $this->assertNotContains((string) $sharedq2->id, $backupquestions);
        $this->assertNotContains((string) $sharedq4->id, $backupquestions);
        $this->assertNotContains((string) $courseq1->id, $backupquestions);
        $this->assertNotContains((string) $courseq3->id, $backupquestions);
        $this->assertNotContains((string) $courseq4->id, $backupquestions);
        $this->assertNotContains((string) $tagq3->id, $backupquestions);
        $this->assertCount(8, $backupquestions);
        // Clean up.
        $rc->execute_plan();
        $rc->destroy();
    }
}

