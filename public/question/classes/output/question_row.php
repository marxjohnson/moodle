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

namespace core_question\output;

use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;

/**
 * Row of columns for a single question.
 *
 * @package   core_question
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_row implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param array $attributes Array of HTML attributes for the row element.
     * @param array $columns The cells for each column in the row.
     */
    public function __construct(
        /** @var string Array of HTML attributes for the row element. */
        protected array $attributes,
        /** @var string The HTML of the cell for each column in the row. */
        protected array $columns = [],
    ) {
    }

    /**
     * Add a rendered column to the table row.
     *
     * This content is provided by column_base classes in qbank plugins, so is provided as rendered HTML and output verbatim.
     *
     * @param string $renderedcolumn
     */
    public function add_column(string $renderedcolumn): void {
        $this->columns[] = ['column' => $renderedcolumn];
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $attributes = array_map(
            fn($name, $value) => (object) ['name' => $name, 'value' => $value],
            array_keys($this->attributes),
            array_values($this->attributes),
        );
        return [
            'attributes' => $attributes,
            'columns' => $this->columns,
        ];
    }
}
