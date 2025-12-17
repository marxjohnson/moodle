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
use core_question\local\bank\column_base;
use mod_quiz\output\question_name_cell;
use stdClass;

/**
 * A column type for the name of the question name.
 *
 * @package   mod_quiz
 * @category  question
 * @copyright 2009 Tim Hunt
 * @author    2021 Safat Shahin <safatshahin@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_name_column extends \core_question\local\bank\column_base {

    /**
     * @var null $checkboxespresent
     */
    protected $checkboxespresent = null;

    public function get_name(): string {
        return 'questionname';
    }

    public function get_title(): string {
        return get_string('question');
    }

    protected function label_for($question): string {
        if (is_null($this->checkboxespresent)) {
            $this->checkboxespresent = $this->qbank->has_column('core_question\local\bank\checkbox_column');
        }
        if ($this->checkboxespresent) {
            return 'checkq' . $question->id;
        } else {
            return '';
        }
    }

    #[deprecated(
        replacement: self::class . '::render',
        since: 5.2,
        reason: 'Direct output of HTML was replaced with functions to return the rendered HTML for display',
        mdl: 'MDL-87103',
    )]
    protected function display_content($question, $rowclasses): void {
        deprecation::emit_deprecation([$this, __FUNCTION__]);
        $labelfor = $this->label_for($question);
        if ($labelfor) {
            echo \html_writer::start_tag('label', ['for' => $labelfor]);
        }
        echo format_string($question->name);
        if ($labelfor) {
            echo \html_writer::end_tag('label');
        }
    }

    #[\Override]
    public function render(stdClass $question, string $rowclasses): string {
        global $OUTPUT;
        return $OUTPUT->render(
            new question_name_cell(
                $question,
                $this->get_classes(),
                $rowclasses,
                $this->get_column_id(),
                $this->isheading,
                $this->label_for($question),
            ),
        );
    }

    public function get_required_fields(): array {
        return ['q.id', 'q.name'];
    }

    public function is_sortable() {
        return 'q.name';
    }
}
