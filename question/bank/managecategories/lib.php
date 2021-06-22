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

/**
 * Categories related functions.
 *
 * This file was created just because Fragment API expects callbacks to be defined on lib.php.
 *
 * @package   qbank_managecategories
 * @copyright 2021 Catalyst IT Australia Pty Ltd
 * @author    Marc-Alexandre Ghaly <marc-alexandreghaly@catalyst-ca.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or late
 */


/**
 * Fragment for rendering categories.
 *
 * @param array $args Arguments to the form.
 * @return null|string The rendered form.
 */
function qbank_managecategories_output_fragment_categories(array $args): string {
    return qbank_managecategories\output\fragment::categories($args);
}
