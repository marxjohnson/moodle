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

namespace qbank_viewquestiontype\output;

use core\output\image_icon;
use core\output\renderer_base;
use core_question\output\question_cell;
use question_bank;

/**
 * Displays a cell containing the icon for the question type.
 *
 * @package   qbank_viewquestiontype
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_type_cell extends question_cell {
    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $export = parent::export_for_template($output);
        $qtype = question_bank::get_qtype($this->question->qtype, false);
        $namestr = $qtype->local_name();
        $icon = new image_icon('icon', $namestr, $qtype->plugin_name(), ['title' => $namestr]);
        $export['icon'] = $icon->export_for_template($output);
        return $export;
    }
}
