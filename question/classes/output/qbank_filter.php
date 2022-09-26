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

use core\output\datafilter;
use renderer_base;
use stdClass;

/**
 * Class for rendering filters on the base view.
 *
 * @package    core_question
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Tomo Tsuyuki <tomotsuyuki@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qbank_filter extends datafilter {

    /** @var array $searchconditions Searchconditions for the filter. */
    protected $searchconditions = array();

    /** @var int $perpage number of records per page. */
    protected $perpage = 0;

    /**
     * @var string Component for calling the fragment callback.
     */
    protected $component;

    /**
     * @var string Fragment callback.
     */
    protected $callback;

    /**
     * @var string View class name.
     */
    protected $view;

    /**
     * @var int|null if in an activity, the cmid.
     */
    protected $cmid;

    /**
     * @var array Parameters for the page URL.
     */
    protected $pagevars;

    /**
     * @var array Additional parameters used by view classes.
     */
    protected $extraparams;

    /**
     * @param \context $context The context of the course being displayed
     * @param string|null $tableregionid
     * @param array $searchconditions The context of the course being displayed
     * @param array $additionalparams Additional filter parameters
     * @param string $component the component for the fragment
     * @param string $callback the callback for the fragment
     * @param string $view the view class
     * @param ?int $cmid if in an activity, the cmid.
     * @param array $pagevars current filter parameters
     * @param array $extraparams additional parameters required for a particular view class.
     */
    public function __construct(\context $context, ?string $tableregionid = null, array $searchconditions, array $additionalparams,
            string $component, string $callback, string $view, ?int $cmid = null,
            array $pagevars = [], array $extraparams = []) {
        parent::__construct($context, $tableregionid);
        $this->searchconditions = $searchconditions;
        $this->additionalparams = $additionalparams;
        $this->component = $component;
        $this->callback = $callback;
        $this->view = $view;
        $this->cmid = $cmid;
        $this->extraparams = $extraparams;
        if (array_key_exists('sortData', $pagevars)) {
            foreach ($pagevars['sortData'] as $sortname => $sortorder) {
                unset($pagevars['sortData'][$sortname]);
                $pagevars['sortData'][str_replace('\\', '\\\\', $sortname)] = $sortorder;
            }
        }
        $this->pagevars = $pagevars;
    }

    protected function get_filter_object(
        string $name,
        string $title,
        bool $custom,
        bool $multiple,
        ?string $filterclass,
        array $values,
        bool $allowempty = false,
        ?stdClass $filteroptions = null,
        array $joinlist = [],
    ): ?stdClass {

        if (!$allowempty && empty($values)) {
            // Do not show empty filters.
            return null;
        }

        return (object) [
            'name' => $name,
            'title' => $title,
            'allowcustom' => $custom,
            'allowmultiple' => $multiple,
            'filtertypeclass' => $filterclass,
            'values' => $values,
            'filteroptions' => $filteroptions,
            'joinlist' => json_encode($joinlist)
        ];
    }

    /**
     * Get data for all filter types.
     *
     * @return array
     */
    protected function get_filtertypes(): array {

        $filtertypes = [];

        foreach ($this->searchconditions as $searchcondition) {
            $filtertypes[] = $this->get_filter_object(
                $searchcondition->get_condition_key(),
                $searchcondition->get_title(),
                $searchcondition->allow_custom(),
                $searchcondition->allow_multiple(),
                $searchcondition->get_filter_class(),
                $searchcondition->get_initial_values(),
                $searchcondition->allow_empty(),
                $searchcondition->get_filteroptions(),
                $searchcondition->get_join_list(),
            );
        }

        return $filtertypes;
    }

    /**
     * Export the renderer data in a mustache template friendly format.
     *
     * @param renderer_base $output Unused.
     * @return stdClass Data in a format compatible with a mustache template.
     */
    public function export_for_template(renderer_base $output): stdClass {
        $defaultcategory = question_get_default_category($this->context->id);
        $courseid = $this->context->instanceid;
        if ($courseid === 0) {
            $courseid = $this->searchconditions['category']->get_course_id();
        }
        return (object) [
            'tableregionid' => $this->tableregionid,
            'courseid' => $courseid,
            'filtertypes' => $this->get_filtertypes(),
            'selected' => 'category',
            'rownumber' => 1,
            'categoryid' => $defaultcategory->id,
            'perpage' => $this->additionalparams['perpage'] ?? 0,
            'contextid' => $this->context->id,
            'component' => $this->component,
            'callback' => $this->callback,
            'view' => str_replace('\\', '\\\\', $this->view),
            'cmid' => $this->cmid ?? 0,
            'pagevars' => json_encode($this->pagevars),
            'extraparams' => json_encode($this->extraparams),
        ];
    }
}
