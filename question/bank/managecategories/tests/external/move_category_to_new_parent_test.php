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

namespace qbank_managecategories;

defined('MOODLE_INTERNAL') || die;
global $CFG;
require_once($CFG->dirroot . '/question/bank/managecategories/tests/manage_category_test_base.php');

use qbank_managecategories\external\move_category_to_new_parent;

/**
 * Unit tests for qbank_managecategories enhancememt component.
 *
 * @package    qbank_managecategories
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     2021, Ghaly Marc-Alexandre <marc-alexandreghaly@catalyst-ca.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @coversDefaultClass \qbank_managecategories\external\move_category_to_new_parent
 */
final class move_category_to_new_parent_test extends manage_category_test_base {
    /**
     * Tests updating a category parent.
     *
     * @covers ::execute
     */
    public function test_update_category_parent(): void {
        $this->setAdminUser();
        $this->resetAfterTest();

        // Create a course category.
        $coursecategory = $this->create_course_category();

        // Create a question category for the course category.
        $categoryqcat = $this->create_question_category_for_a_course_category($coursecategory);

        // Create a question category for the system.
        $systemqcat = $this->create_question_category_for_the_system();

        // The question category of "course category" is not on the system.
        $parent = $this->get_parent_of_a_question_category($categoryqcat->id);
        $this->assertNotEquals($systemqcat->id, $parent);

        // Move question category of "course category" to system.
        move_category_to_new_parent::execute($categoryqcat->id, $systemqcat->id);
        $newparent = $this->get_parent_of_a_question_category($categoryqcat->id);
        $this->assertEquals($systemqcat->id, $newparent);
    }
}
