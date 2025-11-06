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

use core\output\checkbox_toggleall;
use core\output\renderer_base;

/**
 * Content of the checkbox column for a question.
 *
 * @package   core_question
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class checkbox_cell extends question_cell {
    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $checkbox = new checkbox_toggleall('qbank', false, [
            'id' => "checkq{$this->question->id}",
            'name' => "q{$this->question->id}",
            'value' => '1',
            'label' => get_string('select'),
            'labelclasses' => 'accesshide',
        ]);

        $export = parent::export_for_template($output);
        $export['checkbox'] = $checkbox->export_for_template($output);
        return $export;
    }
}
