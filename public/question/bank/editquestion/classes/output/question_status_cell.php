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

namespace qbank_editquestion\output;

use core\output\renderer_base;
use core_question\local\bank\question_version_status;
use core_question\output\question_cell;
use qbank_editquestion\editquestion_helper;

/**
 * Content of the question status column for a question.
 *
 * @package   qbank_editquestion
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_status_cell extends question_cell {
    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $options = [];
        $status = '';
        if (
            question_has_capability_on($this->question, 'edit')
            && $this->question->status !== question_version_status::QUESTION_STATUS_HIDDEN
        ) {
            $options['questionid'] = $this->question->id;
            $statuslist = editquestion_helper::get_question_status_list();
            foreach ($statuslist as $value => $displaystatus) {
                $options['options'][] = [
                    'name' => $displaystatus,
                    'value' => $value,
                    'selected' => $this->question->status === $value,
                ];
            }
        } else {
            $statuslist = editquestion_helper::get_question_status_list(true);
            $status = $statuslist[$this->question->status];
        }

        $export = parent::export_for_template($output);
        $export['statusoptions'] = $options;
        $export['status'] = $status;
        return $export;
    }
}
