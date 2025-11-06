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

namespace core_question\output;

use core\context\module;
use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use core_question\local\bank\view;
use core\url;

/**
 * Renderable for question bank page bulk actions UI.
 *
 * @package   core_question
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bulk_actions_ui implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param view $qbank The question bank view the bulk UI is being displayed in.
     * @param module $catcontext The question category context for capability checks.
     */
    public function __construct(
        /** @var view The question bank view the bulk UI is being displayed in. */
        protected view $qbank,
        /** @var module The question category context for capability checks. */
        protected module $catcontext,
    ) {
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $caneditall = has_capability('moodle/question:editall', $this->catcontext);
        $canuseall = has_capability('moodle/question:useall', $this->catcontext);
        $canmoveall = has_capability('moodle/question:moveall', $this->catcontext);
        $bulkactiondatas = ['hasbulkactions' => false];
        if ($caneditall || $canmoveall || $canuseall) {
            $params = $this->qbank->base_url()->params();
            $returnurl = new url(
                $this->qbank->base_url(),
                ['filter' => json_encode($this->qbank->get_pagevars()['filter'])],
            );
            $params['returnurl'] = $returnurl;
            foreach ($this->qbank->bulkactions as $key => $action) {
                // Check capabilities.
                $capcount = 0;
                foreach ($action->get_bulk_action_capabilities() as $capability) {
                    if (has_capability($capability, $this->catcontext)) {
                        $capcount++;
                    }
                }
                // At least one cap need to be there.
                if ($capcount === 0) {
                    unset($this->qbank->bulkactions[$key]);
                    continue;
                }
                $actiondata = new \stdClass();
                $actiondata->actionname = $action->get_bulk_action_title();
                $actiondata->actionkey = $key;
                $actiondata->actionurl = new url($action->get_bulk_action_url(), $params);
                $actiondata->actionclasses = $action->get_bulk_action_classes();
                $bulkactiondata[] = $actiondata;

                $bulkactiondatas['bulkactionitems'] = $bulkactiondata;
            }
            // We dont need to show this section if none of the plugins are enabled.
            if (!empty($bulkactiondatas['bulkactionitems'])) {
                $bulkactiondatas['hasbulkactions'] = true;
            }
        }
        return $bulkactiondatas;
    }
}
