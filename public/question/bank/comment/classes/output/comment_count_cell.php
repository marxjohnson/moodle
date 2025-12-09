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

namespace qbank_comment\output;

use core\output\renderer_base;
use core_comment\manager;
use core_question\output\question_cell;
use stdClass;

/**
 * Content of the comment count column
 *
 * @package   qbank_comment
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comment_count_cell extends question_cell {
    /**
     * Constructor.
     *
     * @param stdClass $question The question record.
     * @param string $columnclasses The CSS classes for all cells in this column.
     * @param string $rowclasses The CSS classes for this row.
     * @param string $columnid The internal ID for this column.
     * @param bool $isheading If true, display this cell as a heading.
     * @param int $courseid The ID of the course this question's bank is in.
     */
    public function __construct(
        stdClass $question,
        string $columnclasses,
        string $rowclasses,
        string $columnid,
        bool $isheading,
        /** @var int The ID of the course this question's bank is in. */
        protected int $courseid,
    ) {
        parent::__construct($question, $columnclasses, $rowclasses, $columnid, $isheading);
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $export = parent::export_for_template($output);
        $syscontext = \context_system::instance();

        // Build a comment manager to see if we have correct permissions to post.
        $manager = new manager((object) [
            'contextid' => $syscontext->id,
            'courseid' => $this->courseid,
            'area' => 'question',
            'itemid' => $this->question->id,
            'component' => 'qbank_comment',
        ]);
        if (question_has_capability_on($this->question, 'comment') && $manager->can_post()) {
            $export['target'] = 'questioncommentpreview_' . $this->question->id;
            $export['questionid'] = $this->question->id;
            $export['courseid'] = $this->courseid;
            $export['contextid'] = $syscontext->id;
        }
        $export['commentcount'] = $manager->count();

        return $export;
    }
}
