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
namespace qbank_managecategories\output;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/editlib.php');

use moodle_url;
use core_question\local\bank\question_edit_contexts;
use qbank_managecategories\question_category_object;

/**
 * Output fragments for qbank_managecategories
 *
 * @package   qbank_managecategories
 * @copyright 2024 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class fragment {
    /**
     * Return a rendered list of question categories.
     *
     * @param array $args Must contain context, url, cmid and courseid.
     * @return string The rendered HTML.
     */
    public static function categories(array $args): string {
        global $OUTPUT;
        $context = $args['context'];
        $contexts = new question_edit_contexts($context);
        $qcobject = new question_category_object(
            1,
            new moodle_url($args['url']),
            $contexts->having_one_edit_tab_cap('categories'),
            0,
            null,
            0,
            $contexts->having_cap('moodle/question:add'),
            $args['cmid'] ?? null,
            $args['courseid'] ?? null,
            $context->id
        );
        return $OUTPUT->render(new categories($qcobject));
    }
}
