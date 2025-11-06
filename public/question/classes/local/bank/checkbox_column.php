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

/**
 * A column with a checkbox for each question with name q{questionid}.
 *
 * @package   core_question
 * @copyright 2009 Tim Hunt
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace core_question\local\bank;

use core\attribute\deprecated;
use core\deprecation;
use core\output\checkbox_toggleall;
use core_question\output\checkbox_cell;
use stdClass;

/**
 * A column with a checkbox for each question with name q{questionid}.
 *
 * @copyright 2009 Tim Hunt
 * @author    2021 Safat Shahin <safatshahin@catalyst-au.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checkbox_column extends column_base {

    public function get_name(): string {
        return 'checkbox';
    }

    public function get_title() {
        global $OUTPUT;

        $togglercheckbox = new checkbox_toggleall('qbank', true, [
            'id' => 'qbheadercheckbox',
            'name' => 'qbheadercheckbox',
            'value' => '1',
            'label' => get_string('selectall'),
            'labelclasses' => 'accesshide',
        ]);

        return $OUTPUT->render($togglercheckbox);
    }

    public function get_title_tip() {
        return get_string('selectquestionsforbulk', 'question');
    }

    public function display_header(array $columnactions = [], string $width = ''): void {
        global $PAGE;
        $renderer = $PAGE->get_renderer('core_question', 'bank');

        $data = [];
        $data['sortable'] = false;
        $data['extraclasses'] = $this->get_classes();
        $name = get_class($this);
        $data['sorttip'] = true;
        $data['tiptitle'] = $this->get_title();
        $data['tip'] = $this->get_title_tip();

        $data['colname'] = $this->get_column_name();
        $data['columnid'] = $this->get_column_id();
        $data['name'] = get_string('selectall');
        $data['class'] = $name;
        $data['width'] = $width;

        echo $renderer->render_column_header($data);
    }

    #[deprecated(
        replacement: self::class . '::render',
        since: 5.2,
        reason: 'Direct output of HTML was replaced with functions to return the rendered HTML for display',
        mdl: 'MDL-87103',
    )]
    #[\Override]
    protected function display_content($question, $rowclasses): void {
        deprecation::emit_deprecation([$this, __FUNCTION__]);
        global $OUTPUT;

        $checkbox = new checkbox_toggleall('qbank', false, [
            'id' => "checkq{$question->id}",
            'name' => "q{$question->id}",
            'value' => '1',
            'label' => get_string('select'),
            'labelclasses' => 'accesshide',
        ]);

        echo $OUTPUT->render($checkbox);
    }

    #[\Override]
    public function render(stdClass $question, string $rowclasses): string {
        global $OUTPUT;
        return $OUTPUT->render(
            new checkbox_cell(
                $question,
                $this->get_classes(),
                $rowclasses,
                $this->get_column_id(),
                $this->isheading,
            )
        );
    }

    public function get_required_fields(): array {
        return ['q.id'];
    }

    public function get_default_width(): int {
        return 30;
    }

    #[\Override]
    public function get_column_actions(array $columnactions): array {
        return [];
    }
}
