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

namespace mod_quiz;

/**
 * Question data within a quiz attempt.
 *
 * @package   mod_quiz
 * @copyright 2026 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz_attempt_question {
    /**
     * @var int If $this->questionnumber is numeric, the integer representation of that number.
     */
    public int $number;

    /**
     * @var string A string representation of the {@see \question_state} this question attempt is in.
     */
    public string $state;

    /**
     * @var string A CSS class name for the current state.
     */
    public string $stateclass;

    /**
     * @var string A brief textual description of the current state.
     */
    public string $status;

    /**
     * @var bool Whether the previous question must have been completed before this one can be seen.
     */
    public bool $blockedbyprevious;

    /** @var float The maximum mark possible for this question attempt. */
    public float $maxmark;

    /** @var string The formatted grade for this question, to the number of decimal places specified by the quiz. */
    public string $mark;

    /** @var string The question type name for this question attempt. */
    public string $type;

    /** @var string The rendered HTML for this question. */
    public string $html;

    /** @var array Response files for questions like essay that allows attachments. Nested array of areas with files. */
    public array $responsefileareas;

    /** @var string|null JSON-encoded string of question settings that define this question as structured data. */
    public ?string $settings;

    /**
     * Constructor. Save properties and parse the question number.
     *
     * @param int $slot The quiz slot number for this question.
     * @param int $page The quiz page the question appears on.
     * @param string $questionnumber The displayed question number for a slot (For example: 1, a, or i).
     * @param bool $flagged True if this is a real question, rather than something like a description.
     * @param int $sequencecheck The number of real steps in this question attempt.
     * @param int $lastactiontime The timestamp when the last step in this question attempt was created.
     * @param bool $hasautosavedstep Whether this question attempt has autosaved data from some time in the past.
     */
    public function __construct(
        /** @var int The quiz slot number for this question. */
        public int $slot,
        /** @var int The quiz page the question appears on. */
        public int $page,
        /** @var string The displayed question number for a slot (For example: 1, a, or i). */
        public string $questionnumber,
        /** @var bool True if this is a real question, rather than something like a description. */
        public bool $flagged,
        /** @var int The number of real steps in this question attempt. */
        public int $sequencecheck,
        /** @var int The timestamp when the last step in this question attempt was created. */
        public int $lastactiontime,
        /** @var bool Whether this question attempt has autosaved data from some time in the past. */
        public bool $hasautosavedstep,
    ) {
        if ($questionnumber === (string) (int) $questionnumber) {
            $this->number = $questionnumber;
        }
    }
}
