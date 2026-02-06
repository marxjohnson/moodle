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

namespace qbank_columnsortorder\local\bank;

use core\attribute\deprecated;
use core\deprecation;
use core\output\named_templatable;
use core\output\renderer_base;
use core\url;
use core_question\local\bank\view;
use core_question\output\question_row;
use core_question\output\question_table;
use qbank_columnsortorder\column_manager;
use qbank_columnsortorder\output\preview_table;

/**
 * Custom view for displaying a preview of the question bank
 *
 * @package   qbank_columnsortorder
 * @copyright 2023 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class preview_view extends view implements named_templatable {
    /**
     * Use global settings for the column manager.
     *
     * @return void
     */
    protected function init_column_manager(): void {
        $this->columnmanager = new column_manager(true);
    }

    /**
     * Prints the table row with the preview data for each column.
     *
     * @param \stdClass $question
     * @param int $rowcount
     */
    #[deprecated(
        replacement: question_row::class,
        since: 5.2,
        reason: 'Direct output functions have been replaced with templatables.',
        mdl: 'MDL-87103',
    )]
    public function print_table_row($question, $rowcount): void {
        deprecation::emit_deprecation([$this, __FUNCTION__]);
        $rowclasses = implode(' ', $this->get_row_classes($question, $rowcount));
        $attributes = [];
        if ($rowclasses) {
            $attributes['class'] = $rowclasses;
        }
        echo \html_writer::start_tag('tr', $attributes);
        foreach ($this->visiblecolumns as $column) {
            echo $column->render_preview($question, $rowclasses);
        }
        echo \html_writer::end_tag('tr');
        foreach ($this->extrarows as $row) {
            echo $row->render_preview($question, $rowclasses);
        }
    }

    /**
     * Get a dummy question containing valid data for the default question fields.
     *
     * @return \stdClass
     */
    protected function get_dummy_question(): \stdClass {
        return (object)[
            'id' => 1,
            'qtype' => 'truefalse',
            'questiontext' => 'Is the moon made of cheese?',
            'questiontextformat' => FORMAT_PLAIN,
            'createdby' => 2,
            'categoryid' => 1,
            'contextid' => 1,
            'status' => 'ready',
            'version' => 1,
            'versionid' => 1,
            'questionbankentryid' => 1,
            'name' => 'Lorem ipsum',
            'idnumber' => 123,
            'creatorfirstname' => 'Admin',
            'creatorlastname' => 'User',
            'timecreated' => 1691157311,
            'modifierfirstname' => 'Admin',
            'modifierlastname' => 'User',
            'timemodified' => 1691157311,
            'isdummy' => true,
        ];
    }

    /**
     * Generate a preview of the question bank table with a single dummy question.
     *
     * @return string An HTML table containing the column headings and a single question row.
     */
    #[deprecated(
        replacement: self::class . '::export_for_template',
        since: 5.2,
        reason: 'Made this class directly templatable.',
        mdl: 'MDL-87103',
    )]
    public function get_preview(): string {
        deprecation::emit_deprecation([$this, __FUNCTION__]);
        global $OUTPUT;
        return $OUTPUT->render($this);
    }

    #[\Override]
    public function get_template_name(renderer_base $renderer): string {
        return 'qbank_columnsortorder/column_sort_preview';
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $table = new preview_table($this, [$this->get_dummy_question()]);
        return [
            'backurl' => new url('/question/bank/columnsortorder/sortcolumns.php'),
            'previewtable' => $table->export_for_template($output),
        ];
    }

    #[\Override]
    public function get_question_count(): int {
        return 1;
    }

    #[\Override]
    public function get_aggregate_statistic(int $questionid, string $fieldname): ?float {
        return 1.0;
    }
}
