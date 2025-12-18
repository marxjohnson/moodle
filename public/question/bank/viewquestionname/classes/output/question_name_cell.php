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

namespace qbank_viewquestionname\output;

use core\output\renderer_base;
use core_question\output\question_cell;
use core_tag\output\taglist;
use core_tag_tag;
use question_bank;
use stdClass;

/**
 * Content of the question name column.
 *
 * Displays a questions name, ID number and tags. Optionally, the question name can be marked up as a label for a form element,
 * for example the bulk action checkbox.
 *
 * @package   qbank_viewquestionname
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_name_cell extends question_cell {
    /**
     * Constructor.
     *
     * @param stdClass $question The question record.
     * @param string $columnclasses The CSS classes for all cells in this column.
     * @param string $rowclasses The CSS classes for this row.
     * @param string $columnid The internal ID for this column.
     * @param bool $isheading If true, display this cell as a heading.
     * @param string $labelfor The HTML ID of a field the question name is a label for.
     */
    public function __construct(
        stdClass $question,
        string $columnclasses,
        string $rowclasses,
        string $columnid,
        bool $isheading,
        /** @var string The HTML ID of a field the question name is a label for. */
        protected string $labelfor,
    ) {
        parent::__construct($question, $columnclasses, $rowclasses, $columnid, $isheading);
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $export = parent::export_for_template($output);
        $export['labelfor'] = $this->labelfor;
        $questiondisplay = new questionname($this->question);
        $export['questiondisplay'] = $questiondisplay->export_for_template($output);
        $export['idnumber'] = $this->question->idnumber;
        $export['tags'] = [];
        if (!empty($this->question->tags)) {
            $tags = core_tag_tag::get_item_tags('core_question', 'question', $this->question->id);
            $taglist = new taglist($tags, null, 'd-inline flex-shrink-1 text-truncate ms-1', 0, null, true);
            $export['tags'] = $taglist->export_for_template($output);
        }
        $export['invalidqtype'] = false;
        if (!question_bank::is_qtype_usable($this->question->qtype)) {
            $export['invalidqtype'] = $this->question->qtype;
        }
        return $export;
    }
}
