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

namespace mod_quiz\local;

use MoodleQuickForm;
use context_module;
use context;

/**
 * Class access_override_rule_base
 *
 * @package    mod_quiz
 * @copyright  2025 Michael Kotlyar <michael.kotlyar@catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class access_override_rule_base {
    /**
     * Add fields to the quiz override form. To remain consistent with other access rules, you must
     * create a header element at the top and the header should only be expanded if the override for
     * this rule has been configured.
     * E.g. $mform->addElement('header', 'accessrule', $title);
     * $mform->setExpanded('accessrule', $override && $override->enabled);
     *
     * @param context_module $context
     * @param int $overrideid
     * @param object $quiz
     * @param MoodleQuickForm $mform
     */
    public static function add_form_fields(context_module $context, int $overrideid, object $quiz, MoodleQuickForm $mform): void {
        // Do nothing.
    }

    /**
     * Validate the data from any form fields added using {@see add_form_fields()}.
     *
     * @param array $errors the errors found so far.
     * @param array $data the submitted form data.
     * @param array $files information about any uploaded files.
     * @param context_module $context
     * @return array the updated $errors array.
     */
    public static function validate_form_fields(
        array $errors,
        array $data,
        array $files,
        context_module $context,
    ): array {
        return [];
    }

    /**
     * Save any submitted settings when the quiz override settings form is submitted.
     *
     * @param array $override data from the override form.
     */
    public static function save_settings(array $override): void {
        // Do nothing.
    }

    /**
     * Delete any rule-specific override settings when the quiz override is deleted.
     *
     * @param int $quizid all overrides being deleted should belong to the same quiz.
     * @param array $overrides an array of override objects to be deleted.
     */
    public static function delete_settings(int $quizid, array $overrides): void {
        // Do nothing.
    }

    /**
     * Provide form field keys in the override form as a string array
     *
     * @return array e.g. ['rule_enabled', 'rule_password'].
     */
    public static function get_settings(): array {
        return [];
    }

    /**
     * Provide required form field keys in the override form as a string array
     *
     * @return array e.g. ['rule_enabled'].
     */
    public static function get_required_settings(): array {
        return [];
    }

    /**
     * Get components of the SQL query to fetch the access rule components' override
     * settings. To be used as part of a quiz_override query to reference.
     *
     * @param string $overridetablename Name of the table to reference for joins.
     * @return array [$selects, $joins, $params']
     */
    public static function get_settings_sql(string $overridetablename): array {
        return [];
    }

    /**
     * Update fields and values of the override table using the override settings.
     *
     * @param object $override the override data to use to update the $fields and $values.
     * @param array $fields the fields to populate.
     * @param array $values the fields to populate.
     * @param context $context the context of which the override is being applied to.
     * @return array [$fields, $values]
     */
    public static function add_table_fields(object $override, array $fields, array $values, context $context): array {
        return [];
    }

    /**
     * Clean override form data.
     *
     * @param array $formdata
     * @return array
     */
    public static function clean_form_data(array $formdata): array {
        return $formdata;
    }
}
