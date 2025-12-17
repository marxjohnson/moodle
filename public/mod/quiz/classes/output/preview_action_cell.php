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
 * Display a button for previewing the current question.
 *
 * @package   mod_quiz
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class preview_action_cell extends question_cell {
    /**
     * Constructor.
     *
     * @param stdClass $question The question record.
     * @param string $columnclasses The CSS classes for all cells in this column.
     * @param string $rowclasses The CSS classes for this row.
     * @param string $columnid The internal ID for this column.
     * @param bool $isheading If true, display this cell as a heading.
     * @param stdClass $quiz The quiz the question is being previewed in.
     */
    public function __construct(
        stdClass $question,
        string $columnclasses,
        string $rowclasses,
        string $columnid,
        bool $isheading,
        /** @var stdClass The quiz the question is being previewed in. */
        protected stdClass $quiz,
    ) {
        parent::__construct($question, $columnclasses, $rowclasses, $columnid, $isheading);
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        global $PAGE;
        $export = parent::export_for_template($output);
        $export['previewicon'] = '';
        if (
            question_has_capability_on($this->question, 'use')
            && \question_bank::is_qtype_usable($this->question->qtype)
        ) {
            $editrenderer = $PAGE->get_renderer('quiz', 'edit');
            $export['previewicon'] = $editrenderer->question_preview_icon($this->quiz, $this->question);
        }
        return $export;
    }
}
