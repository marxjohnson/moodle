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

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_multiple_structure;
use core_external\external_value;
use core\context;

/**
 * Return a filtered list of question categories in a question bank.
 *
 * For use with core_question/question_banks_datasource as a source for autocomplete suggestions.
 *
 * @package   core_question
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class search_question_categories extends external_api {

    /**
     * @var int The maximum number of banks to return.
     */
    const MAX_RESULTS = 20;

    /**
     * Define parameters for external function.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters(
            [
                'cmid' => new external_value(PARAM_INT, 'The question bank course module ID.'),
                'filtercontextid' => new external_value(
                    PARAM_INT,
                    'The current context ID for applying text filters to category names.',
                ),
                'search' => new external_value(PARAM_TEXT, 'Search terms by which to filter the question categories.', default: ''),
            ]
        );
    }

    /**
     * Return formatted question categories within the specified course module, that match the search string.
     *
     * @param int $cmid The question bank course module ID
     * @param int $filtercontextid The current context ID for text filtering
     * @param string $search String to filter results by question bank name
     * @return array
     */
    public static function execute(int $cmid, int $filtercontextid, string $search = ''): array {
        global $DB;
        [
            'cmid' => $cmid,
            'filtercontextid' => $filtercontextid,
            'search' => $search,
        ] = self::validate_parameters(self::execute_parameters(), [
            'cmid' => $cmid,
            'filtercontextid' => $filtercontextid,
            'search' => $search,
        ]);

        $filtercontext = context::instance_by_id($filtercontextid);
        self::validate_context($filtercontext);
        $bankcontext = \context_module::instance($cmid);
        $namelike = $DB->sql_like('name', ':search', casesensitive: false);
        $categories = $DB->get_records_select(
            'question_categories',
            "contextid = :contextid AND parent != :top AND {$namelike}",
            [
                'contextid' => $bankcontext->id,
                'top' => 0,
                'search' => "%$search%",
            ],
            'id DESC',
            limitnum: self::MAX_RESULTS + 1,
        );

        $suggestions = array_map(
            fn($categories) => [
                'value' => $categories->id,
                'label' => format_string($categories->name, true, ['context' => $filtercontext]),
            ],
            $categories,
        );

        if (count($categories) > self::MAX_RESULTS) {
            // If there are too many results, replace the last one with a placeholder.
            $suggestions[array_key_last($categories)] = [
                'value' => 0,
                'label' => get_string('otherquestionbankstoomany', 'question', self::MAX_RESULTS),
            ];
        }

        return [
            'categories' => array_values($suggestions),
            'contextid' => $bankcontext->id,
        ];
    }

    /**
     * Define return values.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'categories' => new external_multiple_structure(
                new external_single_structure([
                    'value' => new external_value(PARAM_INT, 'ID of the question category.'),
                    'label' => new external_value(PARAM_TEXT, 'Formatted question category name'),
                ]),
                'List of shared banks',
            ),
            'contextid' => new external_value(PARAM_INT, 'Context ID of question categories.'),
        ]);
    }
}
