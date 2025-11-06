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
use stdClass;

/**
 * Base class for a cell in the question bank table.
 *
 * @package   core_question
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class question_cell implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param stdClass $question The question record.
     * @param string $columnclasses The CSS classes for all cells in this column.
     * @param string $rowclasses The CSS classes for this row.
     * @param string $columnid The internal ID for this column.
     * @param bool $isheading If true, display this cell as a heading.
     */
    public function __construct(
        /** @var stdClass The question record. */
        protected stdClass $question,
        /** @var string The CSS classes for all cells in this column. */
        protected string $columnclasses,
        /** @var string The CSS classes for this row. */
        protected string $rowclasses,
        /** @var string The internal ID for this column. */
        protected string $columnid,
        /** @var bool If true, display this cell as a heading. */
        protected bool $isheading = false,
    ) {
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        return [
            'class' => $this->columnclasses,
            'columnid' => $this->columnid,
            'heading' => $this->isheading,
        ];
    }
}
