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
 * Base class for handling import and export of plugin data
 *
 * @package   core_question
 * @copyright 2023 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_question\local\bank;

class data_mapper_base {

    /**
     * Return array of additional question data stored by this plugin for export.
     *
     * @param int $questionid The question we are exporting.
     * @return ?array [$key => $value] pairs of data to export. Null if this plugin exports no data.
     */
    public function get_export_data(int $questionid): ?array {
        return null;
    }

    /**
     * Import additional data for this plugin.
     *
     * This function will only be called if an imported file contains data for this plugin.
     *
     * @param int $questionid The question we are importing data for
     * @param array $data Data to be imported. The format should match the output of {@see ::get_export_data()}.
     * @return array ['error', 'notice']
     */
    public function import_data(int $questionid, array $data): array {
        return ['error' => '', 'notice' => ''];
    }

}