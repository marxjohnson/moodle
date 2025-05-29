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

namespace core_question\external;

use core\context\module;

/**
 * Unit tests for core_question\external\search_question_categories
 *
 * @package   core_question
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers \core_question\external\search_question_categories
 */
final class search_question_categories_test extends \advanced_testcase {

    /**
     * Create a set of question categories across 2 banks.
     *
     * Each bank contains one category that does not have "Test" in its name, so will not match searches for that string.
     *
     * @return array
     */
    protected function create_categories(): array {
        $generator = $this->getDataGenerator();
        $qbankgenerator = $this->getDataGenerator()->get_plugin_generator('mod_qbank');
        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');

        $course = $generator->create_course();
        $teacher = $generator->create_user();
        $generator->enrol_user($teacher->id, $course->id, 'teacher');

        $qbank1 = $qbankgenerator->create_instance(['name' => 'Test qbank 1', 'course' => $course->id]);
        $qbank1->context = module::instance($qbank1->cmid);
        $qbank2 = $qbankgenerator->create_instance(['name' => 'Test qbank 2', 'course' => $course->id]);
        $qbank2->context = module::instance($qbank2->cmid);
        $category1 = $questiongenerator->create_question_category(['name' => 'Test 1', 'contextid' => $qbank1->context->id]);
        $category2 = $questiongenerator->create_question_category(['name' => 'Test 2', 'contextid' => $qbank1->context->id]);
        $category3 = $questiongenerator->create_question_category(['name' => 'Different 1', 'contextid' => $qbank1->context->id]);
        $category4 = $questiongenerator->create_question_category(['name' => 'Test 4', 'contextid' => $qbank2->context->id]);
        $category5 = $questiongenerator->create_question_category(['name' => 'Different 2', 'contextid' => $qbank2->context->id]);

        return [
            $teacher,
            $qbank1,
            $qbank2,
            $category1,
            $category2,
            $category3,
            $category4,
            $category5,
        ];
    }

    /**
     * Call the function with no search string. All categories in the bank should be returned.
     */
    public function test_empty_search(): void {
        $this->resetAfterTest();
        [
            $teacher,
            $qbank1,
            ,
            $category1,
            $category2,
            $category3,

        ] = $this->create_categories();

        $this->setUser($teacher);
        $result = search_question_categories::execute($qbank1->cmid, $qbank1->context->id);

        $this->assertEquals(
            [
                [
                    'label' => $category3->name,
                    'value' => $category3->id,
                ],
                [
                    'label' => $category2->name,
                    'value' => $category2->id,
                ],
                [
                    'label' => $category1->name,
                    'value' => $category1->id,
                ],
            ],
            $result['categories'],
        );
        $this->assertEquals($qbank1->context->id, $result['contextid']);
    }

    /**
     * Call the function with a search string matching a subset of available categories. Only those matching should be returned.
     */
    public function test_search(): void {
        $this->resetAfterTest();
        [
            $teacher,
            $qbank1,
            ,
            $category1,
            $category2,
        ] = $this->create_categories();

        $this->setUser($teacher);
        $result = search_question_categories::execute($qbank1->cmid, $qbank1->context->id, 'Test');

        $this->assertEquals(
            [
                [
                    'label' => $category2->name,
                    'value' => $category2->id,
                ],
                [
                    'label' => $category1->name,
                    'value' => $category1->id,
                ],
            ],
            $result['categories'],
        );
        $this->assertEquals($qbank1->context->id, $result['contextid']);
    }

    /**
     * Call the function with a different bank. Only categories from that bank should be returned.
     */
    public function test_search_different_bank(): void {
        $this->resetAfterTest();
        [
            $teacher,
            $qbank1,
            $qbank2,
            ,
            ,
            ,
            $category4
        ] = $this->create_categories();

        $this->setUser($teacher);
        $result = search_question_categories::execute($qbank2->cmid, $qbank1->context->id, 'Test');

        $this->assertEquals(
            [
                [
                    'label' => $category4->name,
                    'value' => $category4->id,
                ],
            ],
            $result['categories'],
        );
        $this->assertEquals($qbank2->context->id, $result['contextid']);
    }

    /**
     * If there are more than the max number of results, a placeholder is returned at the end.
     */
    public function test_search_max_results(): void {
        $this->resetAfterTest();
        [
            $teacher,
            $qbank1,
        ] = $this->create_categories();

        $questiongenerator = $this->getDataGenerator()->get_plugin_generator('core_question');
        for ($i = 1; $i <= search_question_categories::MAX_RESULTS + 2; $i++) {
            $questiongenerator->create_question_category(['name' => "Extra Category {$i}", 'contextid' => $qbank1->context->id]);
        }

        $this->setUser($teacher);
        $result = search_question_categories::execute($qbank1->cmid, $qbank1->context->id, 'Extra');
        $this->assertCount(search_question_categories::MAX_RESULTS + 1, $result['categories']);
        $lastresult = end($result['categories']);
        $this->assertEquals(
            [
                'label' => get_string('otherquestionbankstoomany', 'question', search_question_categories::MAX_RESULTS),
                'value' => 0,
            ],
            $lastresult,
        );
    }
}
