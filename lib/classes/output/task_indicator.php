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
namespace core\output;

use core\task\adhoc_task;
use core\task\stored_progress_task_trait;
use renderer_base;
use stdClass;
use core\task\task_base;
use core\stored_progress_bar;
use renderable;
use templatable;

/**
 * Indicator for displaying status and progress of a background task
 *
 * @package   core\output
 * @copyright 2024 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class task_indicator implements renderable, templatable {

    protected ?stdClass $taskrecord;

    protected ?stored_progress_bar $progressbar;

    public function __construct(
        protected adhoc_task $task,
        protected string $message,
    ) {
        if (!class_uses($task::class, stored_progress_task_trait::class)) {
            throw new \coding_exception('task_indicator can only be used for tasks using stored_progress_task_trait.');
        }
        $this->taskrecord = \core\task\manager::get_queued_adhoc_task_record($this->task) ?: null;
        if ($this->taskrecord) {
            $this->task->set_id($this->taskrecord->id);
            $idnumber = stored_progress_bar::convert_to_idnumber($this->task::class, $this->task->get_id());
            $this->progressbar = stored_progress_bar::get_by_idnumber($idnumber);
        }
    }

    /**
     * Return true if there is an existing record for this task.
     *
     * @return bool
     */
    public function has_task_record(): bool {
        return !is_null($this->taskrecord);
    }

    public function export_for_template(renderer_base $output): array {
        $export = [];
        if ($this->taskrecord) {
            $export['message'] = $this->message;
            $export['progress'] = $this->progressbar->get_content();
        }
        return $export;
    }

}