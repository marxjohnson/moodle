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

namespace mod_quiz\question\bank;

use core\attribute\deprecated;
use core\deprecation;
use mod_quiz\output\preview_action_cell;
use stdClass;

/**
 * A column type for the preview question action.
 *
 * @package    mod_quiz
 * @category   question
 * @copyright  2023 Catalyst IT Europe Ltd.
 * @author     Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class preview_action_column extends \core_question\local\bank\column_base {

    public function get_extra_classes(): array {
        return ['iconcol'];
    }

    #[\Override]
    public function get_title(): string {
        return '&#160;';
    }

    #[\Override]
    public function get_name() {
        return 'previewquestionaction';
    }

    #[\Override]
    public function get_default_width(): int {
        return 45;
    }

    #[deprecated(
        replacement: self::class . '::render',
        since: 5.2,
        reason: 'Direct output of HTML was replaced with functions to return the rendered HTML for display',
        mdl: 'MDL-87103',
    )]
    #[\Override]
    protected function display_content($question, $rowclasses) {
        deprecation::emit_deprecation([$this, __FUNCTION__]);
        global $PAGE;
        if (!question_has_capability_on($question, 'use')) {
            return;
        }
        if (!\question_bank::is_qtype_usable($question->qtype)) {
            return;
        }
        $editrenderer = $PAGE->get_renderer('quiz', 'edit');
        echo $editrenderer->question_preview_icon($this->qbank->get_quiz(), $question);
    }

    #[\Override]
    public function render(stdClass $question, string $rowclasses): string {
        global $OUTPUT;
        return $OUTPUT->render(
            new preview_action_cell(
                $question,
                $this->get_classes(),
                $rowclasses,
                $this->get_column_id(),
                $this->isheading,
                $this->qbank->get_quiz(),
            ),
        );
    }
}
