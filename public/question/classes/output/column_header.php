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

use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use core_question\local\bank\column_action_base;
use core_question\local\bank\column_base;
use core_question\local\bank\view;

/**
 * Column header renderable.
 *
 * Displays the name of each column, the sorting links and the column actions menu.
 *
 * @package   core_question
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class column_header implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param view $qbank The question bank view the header is being displayed in.
     * @param column_base $column The column we are displaying the header for.
     * @param array $columnactions List of action links for the column actions menu.
     * @param string $width Initial CSS width for the column.
     */
    public function __construct(
        /** @var view The question bank view the header is being displayed in. */
        protected view $qbank,
        /** @var column_base The column we are displaying the header for. */
        protected column_base $column,
        /** @var column_action_base[] List of action links for the column actions menu. */
        protected array $columnactions,
        /** @var string Initial CSS width for the column. */
        protected string $width,
    ) {
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $data = [];
        $data['sortable'] = true;
        $data['extraclasses'] = $this->column->get_classes();
        $sortable = $this->column->is_sortable();
        $name = str_replace('\\', '__', get_class($this->column));
        $title = $this->column->get_title();
        $tip = $this->column->get_title_tip();
        $data['hassubsort'] = false;
        $sortlinks = [];
        if (is_array($sortable)) {
            $data['hassubsort'] = true;
            if ($title) {
                $data['title'] = $title;
            }
            foreach ($sortable as $subsort => $details) {
                $sortlinks[] = new column_sort(
                    $this->qbank,
                    $name . '-' . $subsort,
                    $details['title'],
                    isset($details['tip']) ? $details['tip'] : '',
                    empty($details['reverse']) ? SORT_ASC : SORT_DESC,
                );
            }
        } else if ($sortable) {
            $sortlinks = [new column_sort($this->qbank, $name, $title, $tip)];
        } else {
            $data['sortable'] = false;
            $data['tiptitle'] = $title;
            if ($tip) {
                $data['sorttip'] = true;
                $data['tip'] = $tip;
            }
        }
        $help = $this->column->help_icon();
        if ($help) {
            $data['help'] = $help->export_for_template($output);
        }

        $data['colname'] = $this->column->get_column_name();
        $data['columnid'] = $this->column->get_column_id();
        $data['name'] = $title;
        $data['class'] = $name;
        $data['width'] = $this->width;
        if (!empty($sortlinks)) {
            $lastsort = end($sortlinks);
            $lastsort->set_lastsort(true);
            $data['sortlinks'] = array_map(fn($sortlink) => $sortlink->export_for_template($output), $sortlinks);
        }
        if (!empty($this->columnactions)) {
            $actions = array_map(fn($columnaction) => $columnaction->get_action_menu_link($this->column), $this->columnactions);
            $actionmenu = new \action_menu($actions);
            $data['actionmenu'] = $actionmenu->export_for_template($output);
        }

        return $data;
    }
}
