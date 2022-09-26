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

namespace qbank_viewcreator;

use core_question\local\bank\condition;

/**
 * This class controls from which date to which date questions are listed.
 *
 * @package    qbank_viewcreator
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Ghaly Marc-Alexandre <marc-alexandreghaly@catalyst-ca.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class date_condition extends condition {
    /** @var array Questions filters */
    protected $filters;

    /** @var string SQL fragment to add to the where clause. */
    protected $where;

    /** @var array query param used in where. */
    protected $params;

    /**
     * Constructor to initialize the date filter condition.
     *
     * @param null $qbank qbank view
     */
    public function __construct($qbank = null) {
        if (!$qbank) {
            return;
        }

        $this->filters = $qbank->get_pagevars('filters');
        // Build where and params.
        list($this->where, $this->params) = self::build_query_from_filters($this->filters);
    }

    public function where() {
        return $this->where;
    }

    public function get_condition_key() {
        return 'date';
    }

    /**
     * Return parameters to be bound to the above WHERE clause fragment.
     * @return array parameter name => value.
     */
    public function params() {
        return $this->params;
    }

    /**
     * Display GUI for selecting criteria for this condition. Displayed when Show More is open.
     *
     * Compare display_options(), which displays always, whether Show More is open or not.
     * @return bool|string HTML form fragment
     * @deprecated since Moodle 4.0 MDL-72321 - please do not use this function any more.
     * @todo Final deprecation on Moodle 4.1 MDL-72572
     */
    public function display_options_adv() {
        debugging('Function display_options_adv() is deprecated, please use filtering objects', DEBUG_DEVELOPER);
        return false;
    }

    /**
     * Display GUI for selecting criteria for this condition. Displayed always, whether Show More is open or not.
     *
     * Compare display_options_adv(), which displays when Show More is open.
     * @return bool|string HTML form fragment
     * @deprecated since Moodle 4.0 MDL-72321 - please do not use this function any more.
     * @todo Final deprecation on Moodle 4.1 MDL-72572
     */
    public function display_options() {
        debugging('Function display_options() is deprecated, please use filtering objects', DEBUG_DEVELOPER);
        return false;
    }

    public function get_join_list(): array {
        return [
            self::JOINTYPE_ANY
        ];
    }

    /**
     * Build query from filter value
     *
     * @param array $filters filter objects
     * @return array where sql and params
     */
    public static function build_query_from_filters(array $filters): array {
        if (isset($filters['date'])) {
            $filter = (object) $filters['date'];
            if (empty($filter->values[0])) {
                return ['', []];
            }
            $filterverb = $filter->jointype ?? self::JOINTYPE_DEFAULT;
            $where = "";
            if ($filterverb === self::JOINTYPE_NONE) {
                $where = " NOT ";
            }

            $rangetype = $filter->filteroptions['rangetype'] ?? null;

            if ($rangetype === self::RANGETYPE_AFTER) {
                $timeafter = $filter->values[0];
                $where .= " ( q.timecreated >= :date_timeafter ) ";
                $params = ['date_timeafter' => $timeafter];
            }
            if ($rangetype === self::RANGETYPE_BEFORE) {
                $timebefore = $filter->values[0];
                $where .= " ( q.timecreated <= :date_timebefore ) ";
                $params = ['date_timebefore' => $timebefore];
            }
            if ($rangetype === self::RANGETYPE_BETWEEN) {
                $timeafter = $filter->values[0];
                $timebefore = $filter->values[1];
                $where .= " ( q.timecreated >= :date_timeafter AND q.timecreated <= :date_timebefore ) ";
                $params = [
                    'date_timeafter' => $timeafter,
                    'date_timebefore' => $timebefore,
                ];

            }
            return [$where, $params];
        }
        return ['', []];
    }

    public function get_title() {
        return get_string('filter_title', 'qbank_viewcreator');
    }

    public function get_filter_class() {
        return 'core/datafilter/filtertypes/date';
    }
}
