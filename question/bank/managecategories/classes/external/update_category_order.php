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

namespace qbank_managecategories\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_value;
use core_external\external_single_structure;
use qbank_managecategories\helper;

/**
 * External class used for category reordering using drag and drop.
 *
 * @package    qbank_managecategories
 * @category   external
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     2021, Ghaly Marc-Alexandre <marc-alexandreghaly@catalyst-ca.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_category_order extends external_api {
    /**
     * Describes the parameters for update_category_order webservice.
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'categoryid' => new external_value(PARAM_INT, 'Category being moved'),
            'targetcategoryid' => new external_value(PARAM_INT, 'ID of the category to move next to.'),
            'isbeforetarget' => new external_value(PARAM_BOOL, 'Move before the target category?', VALUE_DEFAULT, 1),
        ]);
    }

    /**
     * Move category to new location.
     *
     * @param int $categoryid ID of the category to move.
     * @param int $targetcategoryid ID of the category to move next to.
     * @param bool $isbeforetarget If true, move the category before the target category. If false, move it after.
     * @return array contains result message
     */
    public static function execute(int $categoryid, int $targetcategoryid, bool $isbeforetarget = true): array {

        [
            'categoryid' => $categoryid,
            'targetcategoryid' => $targetcategoryid,
            'isbeforetarget' => $isbeforetarget,
        ] = self::validate_parameters(self::execute_parameters(), [
            'categoryid' => $categoryid,
            'targetcategoryid' => $targetcategoryid,
            'isbeforetarget' => $isbeforetarget,
        ]);

        // Update category location.
        helper::update_category_location($categoryid, $targetcategoryid, $isbeforetarget);

        return ['message' => get_string('categorymoved', 'qbank_managecategories')];
    }

    /**
     * Returns description of method result value.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'message' => new external_value(PARAM_TEXT, 'Message', VALUE_OPTIONAL),
        ]);
    }
}
