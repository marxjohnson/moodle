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

namespace qbank_history\output;

use core\output\image_icon;
use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use core\url;
use question_bank;

/**
 * History view header.
 *
 * @package   qbank_history
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class history_header implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param string $questionname The name of the question we are displaying the history for.
     * @param string $qtype The question type, to display the appropriate icon.
     * @param url $returnurl The URL to return to the previous view of the question bank.
     */
    public function __construct(
        /** @var string The name of the question we are displaying the history for. */
        protected string $questionname,
        /** @var string The question type, to display the appropriate icon. */
        protected string $qtype,
        /** @var url The URL to return to the previous view of the question bank. */
        protected url $returnurl,
    ) {
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $qtype = question_bank::get_qtype($this->qtype, false);
        $namestr = $qtype->local_name();
        $icon = new image_icon('icon', $namestr, $qtype->plugin_name(), ['title' => $namestr]);
        return [
            'questionname' => $this->questionname,
            'returnurl' => $this->returnurl->out(),
            'questionicon' => $icon->export_for_template($output),
        ];
    }
}
