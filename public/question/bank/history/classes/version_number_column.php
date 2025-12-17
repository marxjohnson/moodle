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

namespace qbank_history;

use core\attribute\deprecated;
use core\deprecation;
use core_question\local\bank\column_base;
use qbank_history\output\version_number_cell;

/**
 * Question bank column for the question version number.
 *
 * @package    qbank_history
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class version_number_column extends column_base {

    public function get_name(): string {
        return 'questionversionnumber';
    }

    public function get_title(): string {
        return get_string('questionversionnumber', 'qbank_history');
    }

    #[deprecated(
        replacement: self::class . '::render',
        since: 5.2,
        reason: 'Direct output of HTML was replaced with functions to return the rendered HTML for display',
        mdl: 'MDL-87103',
    )]
    protected function display_content($question, $rowclasses): void {
        deprecation::emit_deprecation([$this, __FUNCTION__]);
        print_string('questionversiondata', 'qbank_history', $question->version);
    }

    #[\Override]
    public function render(\stdClass $question, string $rowclasses): string {
        global $OUTPUT;
        return $OUTPUT->render(
            new version_number_cell(
                $question,
                $this->get_classes(),
                $rowclasses,
                $this->get_column_id(),
                $this->isheading,
            ),
        );
    }

    public function get_extra_classes(): array {
        return ['pe-3'];
    }

    #[\Override]
    public function is_sortable() {
        return 'qv.version';
    }

}
