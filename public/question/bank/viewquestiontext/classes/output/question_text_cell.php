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

namespace qbank_viewquestiontext\output;

use core\output\renderer_base;
use core_question\output\question_cell;
use core_tag\output\taglist;
use qbank_usage\helper;
use question_utils;
use stdClass;

/**
 * Single-cell content of the question text row.
 *
 * Outputs a cell containing the text of a question, formatted or in plain text, spanning all the columns of the table.
 *
 * @package   qbank_viewquestiontext
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_text_cell extends question_cell {
    /**
     * Constructor.
     *
     * @param stdClass $question The question record.
     * @param string $columnclasses The CSS classes for all cells in this column.
     * @param string $rowclasses The CSS classes for this row.
     * @param string $columnid The internal ID for this column.
     * @param bool $isheading If true, display this cell as a heading.
     * @param int $colspan The number of columns the cell should span.
     * @param int $formatpreference The type of formatting to apply to the text, one of the `question_text_format` constants.
     * @param stdClass $formatoptions Additional options used by the specified type of formatting.
     */
    public function __construct(
        stdClass $question,
        string $columnclasses,
        string $rowclasses,
        string $columnid,
        bool $isheading,
        /** @var int The number of columns the cell should span. */
        protected int $colspan,
        /** @var int The type of formatting to apply to the text, one of the `question_text_format` constants. */
        protected int $formatpreference,
        /** @var stdClass Additional options used by the specified type of formatting. */
        protected stdClass $formatoptions,
    ) {
        parent::__construct($question, $columnclasses, $rowclasses, $columnid, $isheading);
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $export = parent::export_for_template($output);
        $export['colspan'] = $this->colspan;
        if ($this->formatpreference !== question_text_format::OFF) {
            $export['plaintext'] = '&nbsp;';
            if ($this->formatpreference === question_text_format::PLAIN) {
                $export['plaintext'] = question_utils::to_plain_text(
                    $this->question->questiontext,
                    $this->question->questiontextformat,
                    ['noclean' => true, 'para' => false, 'filter' => false]
                );
            } else if ($this->formatpreference === question_text_format::FULL) {
                $export['plaintext'] = '';
                $text = question_rewrite_question_preview_urls(
                    $this->question->questiontext,
                    $this->question->id,
                    $this->question->contextid,
                    'question',
                    'questiontext',
                    $this->question->id,
                    $this->question->contextid,
                    'core_question'
                );
                $export['fulltext'] = format_text(
                    $text,
                    $this->question->questiontextformat,
                    $this->formatoptions
                );
            }
        }
        return $export;
    }
}
