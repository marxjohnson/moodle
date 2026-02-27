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

use core\output\pix_icon;
use core\output\renderable;
use core\output\renderer_base;
use core\output\templatable;
use core_question\local\bank\view;

/**
 * Question bank column sort widget.
 *
 * Displays a sorting link for a column, with an icon showing the sort direction if this is the current sort.
 *
 * @package   core_question
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class column_sort implements renderable, templatable {
    /**
     * @var bool $lastsort Is this is last sort link in its column header? This controls whether a separator is displayed after.
     */
    protected bool $lastsort = false;

    /**
     * Constructor.
     *
     * @param view $qbank The question bank view the header is being displayed in.
     * @param string $sortname The internal name of this sort.
     * @param string $title The display name of this sort.
     * @param string $tip The tooltip describing the sort direction.
     * @param int $defaultsort What order should this link sort in by default?
     */
    public function __construct(
        /** @var view The question bank view the header is being displayed in. */
        protected view $qbank,
        /** @var string The internal name of this sort. */
        protected string $sortname,
        /** @var string The display name of this sort. */
        protected string $title,
        /** @var string The tooltip describing the sort direction. */
        protected string $tip,
        /** @var int What order should this link sort in by default? */
        protected int $defaultsort = SORT_ASC,
    ) {
    }

    /**
     * Set the lastsort flag for this sort link.
     *
     * @param bool $lastsort True if this is the last link in its column header, false otherwise.
     */
    public function set_lastsort(bool $lastsort): void {
        $this->lastsort = $lastsort;
    }

    #[\Override]
    public function export_for_template(renderer_base $output): array {
        $sortdata = [];
        $currentsort = $this->qbank->get_primary_sort_order($this->sortname);
        $newsortreverse = $this->defaultsort == SORT_DESC;
        if ($currentsort) {
            $newsortreverse = $currentsort == SORT_ASC;
        }
        $tip = $this->tip != '' ? $this->tip : $this->title;
        if ($newsortreverse) {
            $tip = get_string('sortbyxreverse', '', $tip);
        } else {
            $tip = get_string('sortbyx', '', $tip);
        }

        $link = $this->title;
        $sorticon = null;
        if ($currentsort) {
            if ($currentsort == SORT_DESC) {
                $sorticon = new pix_icon('t/sort_desc', get_string('desc'));
            } else {
                $sorticon = new pix_icon('t/sort_asc', get_string('asc'));
            }
        }

        $sortdata['sorturl'] = $this->qbank->new_sort_url($this->sortname, $newsortreverse);
        $sortdata['sortname'] = $this->sortname;
        $sortdata['sortcontent'] = $link;
        if ($sorticon) {
            // Render the icon using the icon system.
            $sortdata['sorticon'] = $output->render($sorticon);
        }
        $sortdata['sorttip'] = $tip;
        $sortdata['sortorder'] = $newsortreverse ? SORT_DESC : SORT_ASC;
        $sortdata['lastsort'] = $this->lastsort;

        return $sortdata;
    }
}
