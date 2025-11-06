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

use core\output\renderer_base;
use core_question\local\bank\question_action_base;
use stdClass;

/**
 * Content of the edit menu column for a question.
 *
 * Displays an action menu with a list of actions for this question.
 *
 * @package   core_question
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class edit_menu_cell extends question_cell {
    /**
     * Constructor.
     *
     * @param stdClass $question The question record.
     * @param string $columnclasses The CSS classes for all cells in this column.
     * @param string $rowclasses The CSS classes for this row.
     * @param string $columnid The internal ID for this column.
     * @param bool $isheading If true, display this cell as a heading.
     * @param question_action_base[] $actions The list of action links to display in the action menu.
     */
    public function __construct(
        stdClass $question,
        string $columnclasses,
        string $rowclasses,
        string $columnid,
        bool $isheading,
        /** @var question_action_base[] The list of action links to display in the action menu. */
        protected array $actions,
    ) {
        parent::__construct($question, $columnclasses, $rowclasses, $columnid, $isheading);
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $menu = new \action_menu();
        $menu->set_menu_trigger(get_string('edit'));
        $menu->set_boundary('window');
        foreach ($this->actions as $action) {
            $action = $action->get_action_menu_link($this->question);
            if ($action) {
                $menu->add($action);
            }
        }

        $qtypeactions = \question_bank::get_qtype($this->question->qtype, false)->get_extra_question_bank_actions($this->question);
        foreach ($qtypeactions as $action) {
            $menu->add($action);
        }

        $export = parent::export_for_template($output);
        $export['menu'] = $menu->export_for_template($output);
        return $export;
    }
}
