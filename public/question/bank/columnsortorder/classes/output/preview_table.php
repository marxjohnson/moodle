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

namespace qbank_columnsortorder\output;

use core_question\local\bank\view_component;
use core_question\output\question_table;
use stdClass;

/**
 * Question bank table for previewing column settings.
 *
 * Displays a question bank table, using preview data for each row and column.
 *
 * @package   qbank_columnsortorder
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class preview_table extends question_table {
    #[\Override]
    protected function get_rendered_component(view_component $component, stdClass $question, string $classes): string {
        return $component->render_preview($question, $classes);
    }
}
