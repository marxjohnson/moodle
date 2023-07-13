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

namespace core_question\local\bank;

/**
 * Base class for handling import and export of plugin data
 *
 * Any plugin that stores additional data for a question should return an extension of this class from
 * plugin_feature::get_data_mapper().
 *
 * @package   core_question
 * @copyright 2023 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class data_mapper_base {

    /**
     * Return array of additional question data stored by this plugin.
     *
     * This function returns a multi-dimentional array of key => value pairs, keyed by question id.
     * It should always return a key for each question id passed in $questionids, even if there is no data for that question.
     *
     * @param int[] $questionids The questions we are fetching data for.
     * @return array [$questionid][$key => $value] pairs of data.
     */
    public function get_question_data(array $questionids): array {
        return array_fill_keys($questionids, []);
    }

    /**
     * Save additional question data for this plugin.
     *
     * @param int $questionid The question we are importing data for
     * @param array $data Data to be imported as [$key => $value]
     * @return array ['error', 'notice']
     */
    public function save_question_data(int $questionid, array $data): array {
        return ['error' => '', 'notice' => ''];
    }

}
