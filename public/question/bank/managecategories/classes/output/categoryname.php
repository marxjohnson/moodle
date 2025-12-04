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

use core\context;
use core\output\inplace_editable;
use core\output\named_templatable;
use core\output\renderable;
use core_external\external_api;
use core_question\category_manager;

/**
 * Category name inplace editable
 *
 * @package   qbank_managecategories
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class categoryname extends inplace_editable implements named_templatable, renderable {
    /**
     * Constructor.
     *
     * @param \stdClass $category The category record we are editing.
     * @param context $context The context the category belongs to.
     * @param bool $editable Whether the user has permission to edit this category.
     */
    public function __construct(\stdClass $category, context $context, bool $editable) {
        parent::__construct(
            'qbank_managecategories',
            'categoryname',
            $category->id,
            $editable,
            format_string($category->name, true, ['context' => $context, 'escape' => false]),
            $category->name,
            get_string('editcategorynamehint', 'qbank_managecategories'),
            get_string('editcategoryname', 'qbank_managecategories', $category->name),
        );
    }

    #[\Override]
    public function get_template_name(\renderer_base $renderer): string {
        return 'core/inplace_editable';
    }

    /**
     * Save the new name and return the updated output component.
     *
     * @param int $categoryid The ID of the category to update
     * @param string $newname The new name to save.
     * @return self
     */
    public static function callback(int $categoryid, string $newname): self {
        global $DB;

        $context = context::instance_by_id($DB->get_field('question_categories', 'contextid', ['id' => $categoryid]));
        external_api::validate_context($context);

        $manager = new category_manager();
        $manager->update_category($categoryid, '', $newname);

        $updatedcategory = $DB->get_record('question_categories', ['id' => $categoryid]);

        // Prepare the element for the output.
        // The $editable argument is always true because `update_category()` throws an exception otherwise.
        return new self($updatedcategory, $context, true);
    }
}
