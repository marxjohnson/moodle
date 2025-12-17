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

namespace qbank_statistics\columns;

use core\attribute\deprecated;
use core\deprecation;
use core_question\local\bank\column_base;
use qbank_statistics\helper;
use qbank_statistics\output\discrimination_index_cell;
use qbank_statistics\output\statistic_cell;
use stdClass;

/**
 * This columns shows a message about whether this question is OK or needs revision.
 *
 * This is based on the average discrimination index.
 *
 * @package    qbank_statistics
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class discrimination_index extends column_base {

    public function get_title(): string {
        return get_string('discrimination_index', 'qbank_statistics');
    }

    public function help_icon(): ?\help_icon {
        return new \help_icon('discrimination_index', 'qbank_statistics');
    }

    public function get_name(): string {
        return 'discrimination_index';
    }

    public function get_required_statistics_fields(): array {
        return ['discriminationindex'];
    }

    #[deprecated(
        replacement: self::class . '::render',
        since: 5.2,
        reason: 'Direct output of HTML was replaced with functions to return the rendered HTML for display',
        mdl: 'MDL-87103',
    )]
    protected function display_content($question, $rowclasses) {
        deprecation::emit_deprecation([$this, __FUNCTION__]);
        global $PAGE;

        $discriminationindex = $this->qbank->get_aggregate_statistic($question->id, 'discriminationindex');
        echo $PAGE->get_renderer('qbank_statistics')->render_discrimination_index($discriminationindex);
    }

    #[\Override]
    public function render(stdClass $question, string $rowclasses): string {
        global $OUTPUT;
        $discriminationindex = $this->qbank->get_aggregate_statistic($question->id, 'discriminationindex');
        [$value, $classes] = helper::format_discrimination_index($discriminationindex);
        $classes .= ' discrimination_index';
        return $OUTPUT->render(
            new statistic_cell(
                $question,
                $this->get_classes(),
                $rowclasses,
                $this->get_column_id(),
                $this->isheading,
                $value,
                $classes,
            ),
        );
    }

    #[deprecated(
        replacement: self::class . '::render_preview',
        since: 5.2,
        reason: 'Direct output of HTML was replaced with functions to return the rendered HTML for display',
        mdl: 'MDL-87103',
    )]
    public function display_preview(\stdClass $question, string $rowclasses): void {
        deprecation::emit_deprecation([$this, __FUNCTION__]);
        echo $this->render_preview($question, $rowclasses);
    }

    #[\Override]
    public function render_preview(\stdClass $question, string $rowclasses): string {
        global $OUTPUT;
        [$value, $classes] = helper::format_discrimination_index(50.00);
        $classes .= ' discrimination_index';
        return $OUTPUT->render(
            new statistic_cell(
                $question,
                $this->get_classes(),
                $rowclasses,
                $this->get_column_id(),
                $this->isheading,
                $value,
                $classes,
            ),
        );
    }

    public function get_extra_classes(): array {
        return ['pe-3'];
    }

}
