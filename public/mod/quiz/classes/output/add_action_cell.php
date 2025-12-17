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

namespace mod_quiz\output;

use core\output\renderer_base;
use core\url;
use core_question\output\question_cell;
use stdClass;

/**
 * Displays a cell containing a link which will add a single question to the quiz.
 *
 * @package   mod_quiz
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_action_cell extends question_cell {
    /**
     * Constructor.
     *
     * @param stdClass $question The question record.
     * @param string $columnclasses The CSS classes for all cells in this column.
     * @param string $rowclasses The CSS classes for this row.
     * @param string $columnid The internal ID for this column.
     * @param bool $isheading If true, display this cell as a heading.
     * @param url $addurl The URL for adding this question to the quiz.
     * @param string $addstring The title of the link for adding this question to the quiz.
     */
    public function __construct(
        stdClass $question,
        string $columnclasses,
        string $rowclasses,
        string $columnid,
        bool $isheading,
        /** @var url The URL for adding this question to the quiz. */
        protected url $addurl,
        /** @var string The title of the link for adding this question to the quiz. */
        protected string $addstring,
    ) {
        parent::__construct($question, $columnclasses, $rowclasses, $columnid, $isheading);
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $export = parent::export_for_template($output);
        if (question_has_capability_on($this->question, 'use')) {
            $link = new \action_link(
                $this->addurl,
                '',
                null,
                ['title' => $this->addstring],
                new \pix_icon('t/add', $this->addstring)
            );
            $export['link'] = $link->export_for_template($output);
        }
        return $export;
    }
}
