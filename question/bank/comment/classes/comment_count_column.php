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

namespace qbank_comment;

use core_question\local\bank\column_base;
use question_bank;

/**
 * A column to show the number of comments.
 *
 * @package    qbank_comment
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class comment_count_column extends column_base {

    /**
     * @var bool Comments enabled or not from config.
     */
    protected $commentsenabled = true;

    /**
     * Load javascript module if enabled.
     *
     * @return void
     */
    public function init(): void {
        parent::init();
        $this->check_comments_status();
        if ($this->commentsenabled) {
            global $PAGE;
            $PAGE->requires->js_call_amd('qbank_comment/comment', 'init');
        }
    }

    /**
     * Check if comments is turned on in the system or not.
     */
    protected function check_comments_status(): void {
        global $CFG;
        if (!$CFG->usecomments) {
            $this->commentsenabled = false;
        }
    }

    /**
     * Get the name of the column, used internally.
     *
     * @return string
     */
    public function get_name(): string {
        return 'commentcount';
    }

    /**
     * Get the title of the column that will be displayed.
     *
     * @return string
     */
    public function get_title(): string {
        return get_string('commentplural', 'qbank_comment');
    }

    /**
     * Generate the content to be displayed.
     *
     * @param object $question The question object.
     * @param string $rowclasses Classes that can be added.
     */
    protected function display_content($question, $rowclasses): void {
        $syscontext = \context_system::instance();
        $attributes = [];
        if (question_has_capability_on($question, 'comment')) {
            $target = 'questioncommentpreview_' . $question->id;
            $attributes = [
                'href' => '#',
                'data-target' => $target,
                'data-questionid' => $question->id,
                'data-courseid' => $this->qbank->course->id,
                'data-contextid' => $syscontext->id,
            ];
        }
        echo \html_writer::tag('a', $question->commentcount, $attributes);
    }

    public function get_extra_joins(): array {
        $syscontext = \context_system::instance();
        $conditionsql = '';
        foreach ($this->filterconditions as $filtercondition) {
            $conditionsql .= ' AND ' . $filtercondition;
        }
        $subquery = "
            SELECT COUNT('x') AS commentcount, itemid AS questionid
            FROM {comments} c
                 JOIN {question} q ON q.id = c.itemid
                 JOIN {question_versions} qv ON qv.questionid = q.id
                 JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
            WHERE c.component = 'qbank_comment'
                  AND commentarea = 'question'
                  AND contextid = {$syscontext->id}
                  {$conditionsql}
            GROUP BY c.itemid
        ";

        return [
            'comments' => "LEFT JOIN ({$subquery}) comments ON comments.questionid = q.id"
        ];
    }

    public function get_required_fields(): array {
        return ['COALESCE(comments.commentcount, 0) AS commentcount'];
    }

    public function get_extra_classes(): array {
        return ['pe-3'];
    }

    public function get_default_width(): int {
        return 150;
    }

    public function set_filter_conditions(array $filterconditions, array $filterparams): void {
        foreach ($filterparams as $name => $value) {
            $commentname = $name . 'comment';
            $this->filterparameters[$commentname] = $value;
            foreach ($filterconditions as $key => $filtercondition) {
                $filterconditions[$key] = str_replace($name, $commentname, $filtercondition);
            }
        }
        $this->filterconditions = $filterconditions;
    }

    public function get_extra_parameters(): array {
        return $this->filterparameters;
    }
}
