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
 * Helper class to to test column_base class.
 *
 * @package core_question
 * @copyright 2018 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\attribute\deprecated;
use core\deprecation;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper class to to test column_base class.
 *
 * @package core_question
 * @copyright 2018 Huong Nguyen <huongnv13@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class testable_core_question_column extends \core_question\local\bank\column_base {

    /** @var array sortable columns. */
    private $sortable = [];

    /**
     * Output the column header cell.
     */
    public function is_sortable() {
        return $this->sortable;
    }

    /**
     * Set the sortable columns for testing.
     *
     * @param array $sortable
     */
    public function set_sortable(array $sortable) {
        $this->sortable = $sortable;
    }

    #[deprecated(
        replacement: self::class . '::render',
        since: 5.2,
        reason: 'Direct output of HTML was replaced with functions to return the rendered HTML for display',
        mdl: 'MDL-87103',
    )]
    protected function display_content($question, $rowclasses) {
        deprecation::emit_deprecation([$this, __FUNCTION__]);
        echo 'Test Column';
    }

    #[\Override]
    public function render($question, $rowclasses): string {
        global $OUTPUT;
        return $OUTPUT->render_from_template(
            'core_question/question_cell',
            [
                'class' => $this->get_classes(),
                'data-columnid' => $this->get_column_id(),
                'content' => 'Test Column',
            ],
        );
    }

    public function get_name() {
        return 'test_column';
    }

    public function get_title() {
        return 'Test Column';
    }
}
