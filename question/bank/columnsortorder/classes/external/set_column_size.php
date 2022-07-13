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
use core_external\external_value;
use qbank_columnsortorder\column_manager;

/**
 * External qbank_columnsortorder_set_column_size API
 *
 * @package    qbank_columnsortorder
 * @category   external
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     2022, Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class set_column_size extends external_api {
    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'sizes' => new external_value(PARAM_TEXT, 'Size for each column', VALUE_REQUIRED),
            'component' => new external_value(PARAM_COMPONENT, 'Component where user preference is saved', VALUE_DEFAULT, ''),
        ]);
    }

    /**
     * Returns description of method result value.
     */
    public static function execute_returns(): void {
    }

    /**
     * Set sizes for columns
     * Save against user preference if component is specified
     *
     * @param string $sizes json string representing [column => size].
     * @param string $component where user preference is saved.
     */
    public static function execute(string $sizes, string $component = ''): void {
        [
            'sizes' => $sizes,
            'component' => $component,
        ]
            = self::validate_parameters(self::execute_parameters(),
        [
            'sizes' => $sizes,
            'component' => $component,
        ]);

        $context = context_system::instance();
        self::validate_context($context);
        if (empty($component)) {
            require_capability('moodle/site:config', $context);
        }

        column_manager::set_column_size($sizes, $component);
    }
}
