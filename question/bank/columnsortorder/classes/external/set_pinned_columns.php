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

namespace qbank_columnsortorder\external;

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_value;
use qbank_columnsortorder\column_manager;

/**
 * External qbank_columnsortorder_set_pinned_columns API
 *
 * @package    qbank_columnsortorder
 * @category   external
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     2022, Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_pinned_columns extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'columns' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Plugin name for the pinned column', VALUE_REQUIRED)
            ),
            'component' => new external_value(PARAM_COMPONENT, 'Componet where user preference is saved', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Returns description of method result value.
     *
     */
    public static function execute_returns(): void {
    }

    /**
     * Set sticky Columns.
     * Save against user preference if specified
     *
     * @param array $columns list of pinned columns.
     * @param string $component where user preference is saved.
     */
    public static function execute(array $columns, string $component = ''): void {
        [
            'columns' => $columns,
            'component' => $component,
        ]
            = self::validate_parameters(self::execute_parameters(),
        [
            'columns' => $columns,
            'component' => $component,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        if (empty($component)) {
            require_capability('moodle/site:config', $context);
        }

        column_manager::set_pinned_columns($columns, $component);
    }
}
