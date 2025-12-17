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

namespace qbank_editquestion;

use core\attribute\deprecated;
use core\deprecation;
use core_question\local\bank\column_base;
use core_question\local\bank\question_version_status;
use qbank_editquestion\output\question_status_cell;
use stdClass;

/**
 * A column to show the status of the question.
 *
 * @package    qbank_editquestion
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_status_column extends column_base {
    #[\Override]
    public function init(): void {
        global $PAGE;
        $PAGE->requires->js_call_amd('qbank_editquestion/question_status', 'init');
    }

    public function get_name(): string {
        return 'questionstatus';
    }

    public function get_title(): string {
        return get_string('questionstatus', 'qbank_editquestion');
    }

    #[deprecated(
        replacement: self::class . '::render',
        since: 5.2,
        reason: 'Direct output of HTML was replaced with functions to return the rendered HTML for display',
        mdl: 'MDL-87103',
    )]
    protected function display_content($question, $rowclasses): void {
        deprecation::emit_deprecation([$this, __FUNCTION__]);
        global $PAGE;
        if (question_has_capability_on($question, 'edit')
            && $question->status !== question_version_status::QUESTION_STATUS_HIDDEN) {
            $options = [];
            $options['questionid'] = $question->id;
            $statuslist = editquestion_helper::get_question_status_list();
            foreach ($statuslist as $value => $displaystatus) {
                $options['options'][] = [
                    'name' => $displaystatus,
                    'value' => $value,
                    'selected' => ($question->status) === $value ? true : false
                ];
            }
            echo $PAGE->get_renderer('qbank_editquestion')->render_status_dropdown($options);
            $PAGE->requires->js_call_amd('qbank_editquestion/question_status', 'init', [$question->id]);
        } else {
            $statuslist = editquestion_helper::get_question_status_list(true);
            echo $statuslist[$question->status];
        }
    }

    #[\Override]
    public function render(stdClass $question, string $rowclasses): string {
        global $OUTPUT;
        return $OUTPUT->render(
            new question_status_cell(
                $question,
                $this->get_classes(),
                $rowclasses,
                $this->get_column_id(),
                $this->isheading,
            ),
        );
    }

    public function get_extra_classes(): array {
        return ['pe-3'];
    }

}
