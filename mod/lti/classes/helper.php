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

namespace mod_lti;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class for LTI activity.
 *
 * @package    mod_lti
 * @author     Andrew Madden <andrewmadden@catalyst-au.net>
 * @copyright  2020 Catalyst IT
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class helper {

    /**
     * @var int Indicates usage of the old URN-based IMS role vocabulary (for LTI 1.0-1.1).
     */
    public const ROLE_VOCAB_URN = 1;

    /**
     * @var int Indicates usage of the newer URI-based IMS role vocabulary (for LTI 1.3 and 2.0).
     */
    public const ROLE_VOCAB_URI = 2;

    /**
     * Get SQL to query DB for LTI tool proxy records.
     *
     * @param bool $orphanedonly If true, return SQL to get orphaned proxies only.
     * @param bool $count If true, return SQL to get the count of the records instead of the records themselves.
     * @return string SQL.
     */
    public static function get_tool_proxy_sql(bool $orphanedonly = false, bool $count = false): string {
        if ($count) {
            $select = "SELECT count(*) as type_count";
            $sort = "";
        } else {
            // We only want the fields from lti_tool_proxies table. Must define every column to be compatible with mysqli.
            $select = "SELECT ltp.id, ltp.name, ltp.regurl, ltp.state, ltp.guid, ltp.secret, ltp.vendorcode,
                              ltp.capabilityoffered, ltp.serviceoffered, ltp.toolproxy, ltp.createdby,
                              ltp.timecreated, ltp.timemodified";
            $sort = " ORDER BY ltp.name ASC, ltp.state DESC, ltp.timemodified DESC";
        }
        $from = " FROM {lti_tool_proxies} ltp";
        if ($orphanedonly) {
            $join = " LEFT JOIN {lti_types} lt ON ltp.id = lt.toolproxyid";
            $where = " WHERE lt.toolproxyid IS null";
        } else {
            $join = "";
            $where = "";
        }

        return $select . $from . $join . $where . $sort;
    }

    /**
     * Determine which IMS role vocabulary should be used for the tool.
     *
     * LTI version 1.0 and 1.1 use the old URN vocabulary.
     * Versions 2.0 and 1.3 use the new URI vocabulary.
     *
     * @param object $tool
     * @return int One of the self::ROLE_VOCAB_* constants.
     */
    public static function get_role_vocab_for_tool(object $tool): int {
        return $tool->ltiversion == LTI_VERSION_1 ? self::ROLE_VOCAB_URN : self::ROLE_VOCAB_URI;
    }

    /**
     * Gets the IMS role string for the specified user and LTI course module.
     *
     * @param mixed    $user      User object or user id
     * @param int      $cmid      The course module id of the LTI activity
     * @param int      $courseid  The course id of the LTI activity
     * @param string   $rolevocab Which role vocab version should be used? One of the LTI_ROLE_VOCAB_* constants
     *
     * @return string A role string suitable for passing with an LTI launch
     */
    public static function get_ims_role($user, $cmid, $courseid, $rolevocab): string {
        $roles = [];

        if (empty($cmid)) {
            $context = \context_course::instance($courseid);
        } else {
            $context = \context_module::instance($cmid);
        }

        // Mapping of Moodle capabilities to LTI roles.
        $rolemap = [
            'mod/lti:learner' => [
                self::ROLE_VOCAB_URN => 'urn:lti:role:ims/lis/Learner',
                self::ROLE_VOCAB_URI => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Learner',
            ],
            'mod/lti:teachingassistant' => [
                self::ROLE_VOCAB_URN => 'urn:lti:role:ims/lis/TeachingAssistant',
                self::ROLE_VOCAB_URI => 'http://purl.imsglobal.org/vocab/lis/v2/membership/Instructor#TeachingAssistant',
            ],
            'mod/lti:contentdeveloper' => [
                self::ROLE_VOCAB_URN => 'urn:lti:role:ims/lis/ContentDeveloper',
                self::ROLE_VOCAB_URI => 'http://purl.imsglobal.org/vocab/lis/v2/membership#ContentDeveloper',
            ],
            'mod/lti:member' => [
                self::ROLE_VOCAB_URN => 'urn:lti:role:ims/lis/Member',
                self::ROLE_VOCAB_URI => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Member',
            ],
            'mod/lti:manager' => [
                self::ROLE_VOCAB_URN => 'urn:lti:role:ims/lis/Manager',
                self::ROLE_VOCAB_URI => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Manager',
            ],
            'mod/lti:mentor' => [
                self::ROLE_VOCAB_URN => 'urn:lti:role:ims/lis/Mentor',
                self::ROLE_VOCAB_URI => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Mentor',
            ],
            'mod/lti:instructor' => [
                self::ROLE_VOCAB_URN => 'urn:lti:role:ims/lis/Instructor',
                self::ROLE_VOCAB_URI => 'http://purl.imsglobal.org/vocab/lis/v2/membership#Instructor',
            ],
        ];

        foreach ($rolemap as $capability => $ltirole) {
            if (has_capability($capability, $context, $user, false)) {
                array_push($roles, $ltirole[$rolevocab]);
            }
        }

        if (!is_role_switched($courseid) && (is_siteadmin($user)) || has_capability('mod/lti:admin', $context)) {
            // Make sure admins do not have the Learner role, then set admin role.
            $roles = array_diff($roles, [$rolemap['mod/lti:learner'][$rolevocab]]);
            if ($rolevocab == self::ROLE_VOCAB_URN) {
                array_push($roles, 'urn:lti:sysrole:ims/lis/Administrator', 'urn:lti:instrole:ims/lis/Administrator');
            } else {
                array_push($roles, 'http://purl.imsglobal.org/vocab/lis/v2/membership#Administrator',
                        'http://purl.imsglobal.org/vocab/lis/v2/system/person#Administrator',
                        'http://purl.imsglobal.org/vocab/lis/v2/system/institution/person#Administrator');
            }
        }

        // If the user has no other roles, just give them the Learner role.
        if (empty($roles)) {
            array_push($roles, $rolemap['mod/lti:learner'][$rolevocab]);
        }

        return join(',', $roles);
    }

}
