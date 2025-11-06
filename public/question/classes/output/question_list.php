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

use core\context;
use core\output\renderable;
use core\output\templatable;
use core\output\renderer_base;
use core_question\local\bank\view;
use qbank_managecategories\category_condition;

/**
 * Renderable to display the list of questions in a question bank.
 *
 * Displays controls from each plugin, the table containing the questions selected by the current filters, and the list of
 * bulk actions.
 *
 * @package core_question
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_list implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param view $qbank The question bank view the questions are displayed in.
     */
    public function __construct(
        /** @var view The question bank view the questions are displayed in. */
        protected view $qbank,
    ) {
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        // This function can be moderately slow with large question counts and may time out.
        // We probably do not want to raise it to unlimited, so randomly picking 5 minutes.
        \core_php_time_limit::raise(300);
        raise_memory_limit(MEMORY_EXTRA);
        $pagevars = $this->qbank->get_pagevars();
        $baseurl = $this->qbank->base_url();
        [$categoryid, $contextid] = category_condition::validate_category_param($pagevars['cat']);
        $catcontext = context::instance_by_id($contextid);
        // Update the question in the list with correct category context when we have selected category filter.
        if (isset($pagevars['filter']['category']['values'])) {
            $categoryid = $pagevars['filter']['category']['values'][0];
            foreach ($this->qbank->contexts->all() as $context) {
                if ($context->instanceid === (int) $categoryid) {
                    $catcontext = $context;
                    break;
                }
            }
        }
        $urlparams = array_map(
            fn($key, $value): array => ['name' => $key, 'value' => $value],
            array_keys($baseurl->params()),
            array_values($baseurl->params()),
        );

        $questions = $this->qbank->load_questions();
        $totalcount = $this->qbank->get_question_count();
        $questiontable = null;

        if ($totalcount > 0) {
            // Bulk load any required statistics.
            $this->qbank->load_required_statistics($questions);
            $questiontable = new question_table($this->qbank, $questions, $pagevars['qpage'], $pagevars['qperpage']);
        }

        return [
            'component' => $this->qbank->component,
            'callback' => $this->qbank->callback,
            'contextid' => $this->qbank->get_most_specific_context()->id,
            'plugincontrols' => $this->qbank->get_plugin_controls($catcontext, $categoryid),
            'baseurl' => $baseurl,
            'sesskey' => sesskey(),
            'urlparams' => $urlparams,
            'filtercondition' => json_encode($pagevars),
            'questiontable' => $questiontable?->export_for_template($output),
            'bottomcontrols' => $this->qbank->render_bottom_controls($catcontext),
        ];
    }
}
