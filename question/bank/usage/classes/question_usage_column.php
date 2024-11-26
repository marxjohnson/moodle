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

namespace qbank_usage;

use core_question\local\bank\column_base;

/**
 * A column type for the name of the question type.
 *
 * @package    qbank_usage
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_usage_column extends column_base {

    /**
     * Include Javascript module.
     *
     * @return void
     */
    public function init(): void {
        parent::init();
        global $PAGE;
        $PAGE->requires->js_call_amd('qbank_usage/usage', 'init', [
            $this->qbank->is_listing_specific_versions()
        ]);
    }

    public function get_name(): string {
        return 'questionusage';
    }

    public function get_title(): string {
        return get_string('questionusage', 'qbank_usage');
    }

    public function help_icon(): ?\help_icon {
        return new \help_icon('questionusage', 'qbank_usage');
    }

    protected function display_content($question, $rowclasses): void {
        $attributes = [];
        if (question_has_capability_on($question, 'view')) {
            $target = 'questionusagepreview_' . $question->id;
            $attributes = [
                'href' => '#',
                'data-target' => $target,
                'data-questionid' => $question->id,
                'data-courseid' => $this->qbank->course->id,
                'data-contextid' => $question->contextid,
            ];
        }
        echo \html_writer::tag('a', $question->usagecount, $attributes);
    }

    /**
     * Join on a subquery counting the usages of each question, by the ID of the current question.
     *
     * @return string[]
     */
    public function get_extra_joins(): array {
        $specificversion = $this->qbank->is_listing_specific_versions();
        $joinid = $specificversion ? 'q.id' : 'qbe.id';
        $filters = [];
        $conditions1sql = '';
        $conditions2sql = '';
        // Duplicate filter parameters for each subquery.
        $paramnames1 = array_combine(
            array_keys($this->filterparameters),
            array_map(fn($name) => $name . 'usage1', array_keys($this->filterparameters))
        );
        $paramnames2 = array_combine(
            array_keys($this->filterparameters),
            array_map(fn($name) => $name . 'usage2', array_keys($this->filterparameters))
        );
        foreach ($this->filterparameters as $name => $param) {
            $filters[$paramnames1[$name]] = $param;
            $filters[$paramnames2[$name]] = $param;
        }
        foreach ($this->filterconditions as $condition) {
            $condition1 = str_replace(array_keys($paramnames1), array_values($paramnames1), $condition);
            $conditions1sql = ' AND ' . $condition1;
            $condition2 = str_replace(array_keys($paramnames2), array_values($paramnames2), $condition);
            $conditions2sql = ' AND ' . $condition2;
        }
        $this->filterparameters = $filters;
        $subquery = "
            SELECT COUNT('x') AS usagecount,
                   quizusages.questionid AS id
              FROM (" . helper::get_question_attempt_usage_sql($specificversion, false) . "
                   {$conditions1sql}
                   UNION
                   " . helper::get_question_bank_usage_sql($specificversion, false) . "
                   {$conditions2sql}) quizusages
          GROUP BY quizusages.questionid";
        return [
            'usages' => "LEFT JOIN ({$subquery}) usages ON usages.id = {$joinid}",
        ];
    }

    public function get_required_fields(): array {
        return [
            'COALESCE(usages.usagecount, 0) as usagecount',
        ];
    }

    public function get_extra_classes(): array {
        return ['pe-3'];
    }

    /**
     * Return the parameters required for the subqueries returned by get_extra_joins().
     *
     * @return array
     */
    public function get_extra_parameters(): array {
        return array_merge($this->filterparameters, $this->filterparameters);
    }

}
