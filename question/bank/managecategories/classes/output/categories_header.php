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
namespace qbank_managecategories\output;

use context;
use renderable;
use renderer_base;
use templatable;
use qbank_managecategories\question_category_object;

/**
 * Output component for category page header.
 *
 * @package   qbank_managecategories
 * @copyright 2024 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class categories_header implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param question_category_object $categories Question categories for display.
     */
    public function __construct(
        protected question_category_object $categories,
    ) {
    }

    public function export_for_template(renderer_base $output): array {
        $helpstringhead = $output->heading_with_help(
            get_string('editcategories', 'question'),
            'editcategories',
            'question',
        );
        $hascapability = has_capability(
            'moodle/question:managecategory',
            context::instance_by_id($this->categories->contextid),
        );

        $data = [
            'helpstringhead' => $helpstringhead,
            'checkbox' => $this->categories->checkboxform->render(),
            'hascapability' => $hascapability,
            'contextid' => $this->categories->contextid,
            'cmid' => $this->categories->cmid,
            'courseid' => $this->categories->courseid,
        ];
        return $data;
    }
}
