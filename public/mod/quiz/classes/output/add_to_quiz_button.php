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

namespace mod_quiz\output;

use core\context\module;
use core\output\renderable;
use core\output\templatable;

/**
 * Button for adding questions to the quiz
 *
 * @package   mod_quiz
 * @copyright 2026 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_to_quiz_button implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param module $catcontext The question category context for capability checking.
     */
    public function __construct(
        /** @var module The question category context for capability checking. */
        protected module $catcontext,
    ) {
    }

    #[\Override]
    public function export_for_template(\renderer_base $output): array {
        return ['canuseall' => has_capability('moodle/question:useall', $this->catcontext)];
    }
}
