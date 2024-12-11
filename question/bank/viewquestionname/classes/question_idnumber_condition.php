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

namespace qbank_viewquestionname;

/**
 * Filter condition for filtering on the question idnumber
 *
 * @package   qbank_viewquestionname
 * @copyright 2024 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_idnumber_condition extends question_name_condition {
    #[\Override]
    public function get_title() {
        return get_string('questionidnumbercondition', 'qbank_viewquestionname');
    }

    #[\Override]
    public static function get_condition_key() {
        return 'questionidnumber';
    }

    #[\Override]
    protected static function get_filter_field(): string {
        return 'qbe.idnumber';
    }
}
