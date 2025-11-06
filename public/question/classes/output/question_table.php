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

use core\output\paging_bar;
use core\output\renderable;
use core\output\templatable;
use core\output\renderer_base;
use core_question\local\bank\view;
use core_question\local\bank\view_component;
use stdClass;

/**
 * Renderable for the question bank table and paging bars.
 *
 * Creates a table row for each question, plus extras for each extra row provided by plugins.
 *
 * @package   core_question
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_table implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param view $qbank The question bank view the table is being displayed in.
     * @param stdClass[] $questions Data records for each question.
     * @param int $page The current page number.
     * @param int $perpage The number of questions to display on the page.
     */
    public function __construct(
        /** @var view The question bank view the table is being displayed in. */
        protected view $qbank,
        /** @var stdClass[] Data records for each question. */
        protected array $questions,
        /** @var int The current page number. */
        protected int $page = 0,
        /** @var int The number of questions to display on the page. */
        protected int $perpage = DEFAULT_QUESTIONS_PER_PAGE,
    ) {
    }

    /**
     * Return the rendered output of the provided component (column or row).
     *
     * @param view_component $component The view component to render.
     * @param stdClass $question The question data.
     * @param string $classes CSS classes to pass to the render method.
     * @return string The rendered HTML.
     */
    protected function get_rendered_component(view_component $component, stdClass $question, string $classes): string {
        return $component->render($question, $classes);
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        // Pagination.
        $pageingurl = new \moodle_url($this->qbank->base_url());
        $pagevars = $this->qbank->get_pagevars();
        // TODO MDL-82312: it really should not be necessary to set filter here, and not like this.
        // This should be handled in baseurl, but it isn't so we do this so Moodle basically works for now.
        $pageingurl->param('filter', json_encode($pagevars['filter']));
        $pagingbar = new paging_bar($this->qbank->get_question_count(), $this->page, $this->perpage, $pageingurl);
        $pagingbar->pagevar = 'qpage';
        $pagingbarcontext = $pagingbar->export_for_template($output);

        // Table of questions.

        // Prints the table header.
        $columnheaders = [];
        $defaultcolumnactions = $this->qbank->get_columnmanager()->get_column_actions($this->qbank);
        foreach ($this->qbank->get_visiblecolumns() as $column) {
            $width = $this->qbank->get_columnmanager()->get_column_width($column);
            $columnactions = $column->get_column_actions($defaultcolumnactions);
            $columnheader = new column_header($this->qbank, $column, $columnactions, $width);
            $columnheaders[] = $columnheader->export_for_template($output);
        }

        // Prints the table row or content.
        $rowcount = 0;
        $rows = [];
        foreach ($this->questions as $question) {
            $rowclasses = implode(' ', $this->qbank->get_row_classes($question, $rowcount));
            $attributes = [];

            // If the question type is invalid we highlight it red.
            if (!\question_bank::is_qtype_usable($question->qtype)) {
                $rowclasses .= ' table-danger';
            }
            if ($rowclasses) {
                $attributes['class'] = $rowclasses;
                // Add firstrow class to highlighted row if it does not already have it.
                if (str_contains($attributes['class'], 'highlight') && !str_contains($attributes['class'], 'firstrow')) {
                    $attributes['class'] .= ' firstrow';
                    if (
                        empty($this->extrarows)
                        || empty(array_filter($this->extrarows, fn($row) => $row->isvisible))
                    ) {
                        // If there are no other visible rows for this question, add class onlyhighlightrow.
                        $attributes['class'] .= ' onlyhighlightrow';
                    }
                }
            }
            $questionrow = new question_row($attributes);
            foreach ($this->qbank->get_visiblecolumns() as $column) {
                $questionrow->add_column($this->get_rendered_component($column, $question, $rowclasses));
            }
            $rows[] = $questionrow->export_for_template($output);
            foreach ($this->qbank->get_extrarows() as $row) {
                $rows[] = [
                    'row' => $this->get_rendered_component($row, $question, $rowclasses),
                    'extrarow' => true,
                ];
            }
            $rowcount += 1;
        }

        return [
            'hascategoryfilter' => isset($pagevars['filter']['category']),
            'pagingbartop' => $pagingbarcontext,
            'pagingbarbottom' => $pagingbarcontext,
            'defaultsort' => json_encode($this->qbank->get_sort()),
            'columnheaders' => $columnheaders,
            'rows' => $rows,
        ];
    }
}
