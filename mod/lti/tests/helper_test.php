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

/**
 * PHPunit tests for helper class
 *
 * @package   mod_lti
 * @copyright 2022 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_lti;

/**
 * PHPunit tests for helper class
 *
 * @covers \mod_lti\helper
 */
class helper_test extends \advanced_testcase {

    /**
     * Test the correct role vocab is selected for different tool configurations.
     *
     * @dataProvider get_role_vocab_for_tool_provider
     * @param object $tool The tool record
     * @param int $expected The expected helper::ROLE_VOCAB_* constant.
     * @return void
     */
    public function test_get_role_vocab_for_tool(object $tool, int $expected) : void {
        $this->assertEquals($expected, helper::get_role_vocab_for_tool($tool));
    }

    /**
     * Data provider for get_role_vocab_for_tool
     *
     * @return array[][]
     */
    public function get_role_vocab_for_tool_provider() : array {
        global $CFG;
        require_once($CFG->dirroot . '/mod/lti/locallib.php');
        return [
            'LTI 1' => [
                'tool' => (object)[
                    'ltiversion' => LTI_VERSION_1,
                ],
                'expected' => helper::ROLE_VOCAB_URN,
            ],
            'LTI 1.3' => [
                'tool' => (object)[
                    'ltiversion' => LTI_VERSION_1P3,
                ],
                'expected' => helper::ROLE_VOCAB_URI,
            ],
            'LTI 2' => [
                'tool' => (object)[
                    'ltiversion' => LTI_VERSION_2,
                ],
                'expected' => helper::ROLE_VOCAB_URI,
            ],
        ];
    }

    /**
     * Test the get_ims_role method.
     *
     * @dataProvider get_ims_role_provider
     *
     * @param int $rolevocab The role vocabulary to be used.
     * @param string $rolename the name of the role (student, teacher, admin)
     * @param null|string $switchedto the role to switch to, or false if not using the 'switch to' functionality.
     * @param string $expected the expected role name.
     */
    public function test_get_ims_role(int $rolevocab, string $rolename, ?string $switchedto, string $expected) {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $user = $rolename == 'admin' ? get_admin() : $this->getDataGenerator()->create_and_enrol($course, $rolename);

        if ($switchedto) {
            $this->setUser($user);
            $role = $DB->get_record('role', array('shortname' => $switchedto));
            role_switch($role->id, \context_course::instance($course->id));
        }

        $this->assertEquals($expected, helper::get_ims_role($user, 0, $course->id, $rolevocab));
    }

    /**
     * Data provider for testing lti_get_ims_role.
     *
     * @return array[] the test case data.
     */
    public function get_ims_role_provider(): array {
        return [
            'Student, URN vocab, no role switch' => [
                'rolevocab' => helper::ROLE_VOCAB_URN,
                'rolename' => 'student',
                'switchedto' => null,
                'expected' => 'urn:lti:role:ims/lis/Learner'
            ],
            'Student, URI vocab, no role switch' => [
                'rolevocab' => helper::ROLE_VOCAB_URI,
                'rolename' => 'student',
                'switchedto' => null,
                'expected' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner'
            ],
            'Teacher, URN vocab, no role switch' => [
                'rolevocab' => helper::ROLE_VOCAB_URN,
                'rolename' => 'editingteacher',
                'switchedto' => null,
                'expected' => 'urn:lti:role:ims/lis/Instructor'
            ],
            'Teacher, URI vocab, no role switch' => [
                'rolevocab' => helper::ROLE_VOCAB_URI,
                'rolename' => 'editingteacher',
                'switchedto' => null,
                'expected' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor'
            ],
            'Admin, URN vocab, no role switch' => [
                'rolevocab' => helper::ROLE_VOCAB_URN,
                'rolename' => 'admin',
                'switchedto' => null,
                'expected' => 'urn:lti:sysrole:ims/lis/Administrator,urn:lti:instrole:ims/lis/Administrator'
            ],
            'Admin, URI vocab, no role switch' => [
                'rolevocab' => helper::ROLE_VOCAB_URI,
                'rolename' => 'admin',
                'switchedto' => null,
                'expected' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Administrator,'
                        . 'http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator,'
                        . 'http://purl.imsglobal.org/vocab/lis/v2/system/institution/person#Administrator',
            ],
            'Admin, URN vocab, role switch student' => [
                'rolevocab' => helper::ROLE_VOCAB_URN,
                'rolename' => 'admin',
                'switchedto' => 'student',
                'expected' => 'urn:lti:role:ims/lis/Learner'
            ],
            'Admin, URI vocab, role switch student' => [
                'rolevocab' => helper::ROLE_VOCAB_URI,
                'rolename' => 'admin',
                'switchedto' => 'student',
                'expected' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner'
            ],
            'Admin, URN vocab, role switch teacher' => [
                'rolevocab' => helper::ROLE_VOCAB_URN,
                'rolename' => 'admin',
                'switchedto' => 'editingteacher',
                'expected' => 'urn:lti:role:ims/lis/Instructor'
            ],
            'Admin, URI vocab, role switch teacher' => [
                'rolevocab' => helper::ROLE_VOCAB_URI,
                'rolename' => 'admin',
                'switchedto' => 'editingteacher',
                'expected' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor'
            ],
            'Non-editing teacher, URN vocab, no role switch' => [
                'rolevocab' => helper::ROLE_VOCAB_URN,
                'rolename' => 'teacher',
                'switchedto' => null,
                'expected' => 'urn:lti:role:ims/lis/TeachingAssistant'
            ],
            'Non-editing teacher, URI vocab, no role switch' => [
                'rolevocab' => helper::ROLE_VOCAB_URI,
                'rolename' => 'teacher',
                'switchedto' => null,
                'expected' => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistant'
            ],
            'Manager, URN vocab, no role switch' => [
                'rolevocab' => helper::ROLE_VOCAB_URN,
                'rolename' => 'manager',
                'switchedto' => null,
                'expected' => 'urn:lti:role:ims/lis/Manager'
            ],
            'Manager, URI vocab, no role switch' => [
                'rolevocab' => helper::ROLE_VOCAB_URI,
                'rolename' => 'manager',
                'switchedto' => null,
                'expected' => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Manager'
            ],
        ];
    }

    /**
     * Test the get_ims_role method function with a custom role that has multiple LTI capabilities.
     *
     */
    public function test_get_ims_for_custom_role() {
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $customroleid = $this->getDataGenerator()->create_role([
            'shortname' => 'customrole',
            'archetype' => 'editingteacher'
        ]);
        $context = \context_course::instance($course->id);
        assign_capability('mod/lti:learner', CAP_ALLOW, $customroleid, $context->id);
        $user = $this->getDataGenerator()->create_and_enrol($course, 'customrole');

        $urnexpected = 'urn:lti:role:ims/lis/Learner,urn:lti:role:ims/lis/Instructor';
        $uriexpected = 'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner,'
                . 'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor';
        $this->assertEquals($urnexpected, helper::get_ims_role($user, 0, $course->id, helper::ROLE_VOCAB_URN));
        $this->assertEquals($uriexpected, helper::get_ims_role($user, 0, $course->id, helper::ROLE_VOCAB_URI));

    }

}
