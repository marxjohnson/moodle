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

use action_menu;
use action_menu_link;
use context;
use core\plugininfo\qbank;
use moodle_url;
use pix_icon;
use qbank_managecategories\helper;
use qbank_managecategories\question_category_object;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * Output component for categories
 *
 * @package   qbank_managecategories
 * @copyright 2024 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class categories implements renderable, templatable {
    /**
     * Constructor.
     *
     * @param question_category_object $categories Question categories for display.
     */
    public function __construct(
        protected question_category_object $categories,
    ) {
    }

    public function export_for_template(renderer_base $output): array {
        $categories = [];
        foreach ($this->categories->editlists as $contextid => $list) {
            // Get list elements.
            $context = context::instance_by_id($contextid);
            $itemstab = [];
            if (count($list->items)) {
                $previousitem = null;
                foreach ($list->items as $item) {
                    $itemstab['items'][] = $this->item_data($list, $item, $context, $previousitem);
                    $previousitem = $item;
                }
            }
            if (isset($itemstab['items'])) {
                $ctxlvl = "contextlevel" . $list->context->contextlevel;
                $contextname = $list->context->get_context_name();
                $heading = get_string('questioncatsfor', 'question', $contextname);

                // Get categories context.
                $categories[] = [
                    'ctxlvl' => $ctxlvl,
                    'contextid' => $list->context->id,
                    'contextname' => $contextname,
                    'heading' => $heading,
                    'items' => $itemstab['items'],
                ];
            }
        }
        $data = [
            'categoriesrendered' => $categories,
            'contextid' => $this->categories->contextid,
            'cmid' => $this->categories->cmid,
            'courseid' => $this->categories->courseid,
        ];
        return $data;
    }

    /**
     * Creates and returns each item data.
     *
     * @param stdClass $list
     * @param stdClass $category
     * @param context $context
     * @param ?stdClass $previousitem The previous item in the list.
     * @return array $itemdata item data
     */
    public function item_data(stdClass $list, stdClass $category, context $context, ?stdClass $previousitem): array {
        global $OUTPUT;
        $canmanagecategory = has_capability('moodle/question:managecategory', $context);
        if ($canmanagecategory) {
            $icons = $this->get_arrow_descendant($category, $previousitem);
        }
        $iconleft = isset($icons['left']) ? $icons['left'] : null;
        $iconright = isset($icons['right']) ? $icons['right'] : null;
        $params = $this->categories->pageurl->params();
        $cmid = $params['cmid'] ?? 0;
        $courseid = $params['courseid'] ?? 0;

        // Each section adds html to be displayed as part of this list item.
        $questionbankurl = new moodle_url('/question/edit.php', $params);
        $questionbankurl->param('cat', helper::combine_id_context($category));
        $categoryname = format_string($category->name, true, ['context' => $list->context]);
        $idnumber = null;
        if ($category->idnumber !== null && $category->idnumber !== '') {
            $idnumber = $category->idnumber;
        }
        $checked = get_user_preferences('qbank_managecategories_showdescr');
        if ($checked) {
            $categorydesc = format_text(
                $category->info,
                $category->infoformat,
                ['context' => $list->context, 'noclean' => true],
            );
        } else {
            $categorydesc = '';
        }
        $menu = new action_menu();
        $menu->set_kebab_trigger();
        $menu->prioritise = true;

        // Don't allow movement if only subcat.
        if ($canmanagecategory) {
            // This item display a modal for moving a category.
            if (!helper::question_is_only_child_of_top_category_in_context($category->id)) {
                // Drag and drop.
                $draghandle = true;
                // Move category modal.
                $menu->add(new action_menu_link(
                    new \moodle_url('#'),
                    new pix_icon(
                        't/move',
                        get_string('move'),
                        'moodle',
                        [
                            'class' => 'iconsmall',
                        ]
                    ),
                    get_string('move'),
                    false,
                    [
                        'data-categoryid' => $category->id,
                        'data-actiontype' => 'move',
                        'data-contextid' => (int) $category->contextid,
                        'data-categoryname' => $categoryname,
                        'title' => get_string('movecategory', 'qbank_managecategories', $categoryname),
                    ]
                ));
            }
        }

        // Sets up edit link.
        if ($canmanagecategory) {
            $thiscontext = (int) $category->contextid;
            $editurl = new moodle_url('#');
            $menu->add(new action_menu_link(
                $editurl,
                new pix_icon('t/edit', 'edit'),
                get_string('editsettings'),
                false,
                [
                    'data-action' => 'addeditcategory',
                    'data-actiontype' => 'edit',
                    'data-contextid' => $thiscontext,
                    'data-categoryid' => $category->id,
                    'data-cmid' => $cmid,
                    'data-courseid' => $courseid,
                ]
            ));
            // Don't allow delete if this is the top category, or the last editable category in this context.
            if (!helper::question_is_only_child_of_top_category_in_context($category->id)) {
                // Sets up delete link.
                $deleteurl = new moodle_url(
                    '/question/bank/managecategories/category.php',
                    ['delete' => $category->id, 'sesskey' => sesskey()]
                );
                if ($courseid !== 0) {
                    $deleteurl->param('courseid', $courseid);
                } else {
                    $deleteurl->param('cmid', $cmid);
                }
                $menu->add(new action_menu_link(
                    $deleteurl,
                    new pix_icon('t/delete', 'delete'),
                    get_string('delete'),
                    false,
                ));
            }
        }

        // Sets up export to XML link.
        if (qbank::is_plugin_enabled('qbank_exportquestions')) {
            $exporturl = new moodle_url(
                '/question/bank/exportquestions/export.php',
                ['cat' => helper::combine_id_context($category)]
            );
            if ($courseid !== 0) {
                $exporturl->param('courseid', $courseid);
            } else {
                $exporturl->param('cmid', $cmid);
            }

            $menu->add(new action_menu_link(
                $exporturl,
                new pix_icon('t/download', 'download'),
                get_string('exportasxml', 'question'),
                false,
            ));
        }

        // Menu to string/html.
        $menu = $OUTPUT->render($menu);

        $children = [];
        if (!empty($category->children)) {
            $previousitem = null;
            foreach ($category->children as $itm) {
                $children[] = $this->item_data($list, $itm, $context, $previousitem);
                $previousitem = $itm;
            }
        }
        $itemdata = [
            'categoryid' => $category->id,
            'contextid' => $category->contextid,
            'questionbankurl' => $questionbankurl,
            'categoryname' => $categoryname,
            'idnumber' => $idnumber,
            'questioncount' => $category->questioncount,
            'categorydesc' => $categorydesc,
            'editactionmenu' => $menu,
            'draghandle' => $draghandle ?? false,
            'iconleft' => $iconleft,
            'iconright' => $iconright,
            'haschildren' => !empty($children),
            'children' => $children,
        ];
        return $itemdata;
    }

    /**
     * Gets the arrow for category.
     *
     * @param stdClass $category The current category.
     * @param ?stdClass $previousitem The previous category in the list.
     * @return array $icons.
     */
    public function get_arrow_descendant(stdClass $category, ?stdClass $previousitem): array {
        global $OUTPUT;
        $icons = [];
        $strmoveleft = get_string('maketoplevelitem', 'question');
        // Exchange arrows on RTL.
        if (right_to_left()) {
            $rightarrow = 'left';
            $leftarrow = 'right';
        } else {
            $rightarrow = 'right';
            $leftarrow = 'left';
        }

        if (isset($category->parentitem)) {
            if (isset($category->parentitem->parentitem)) {
                $action = get_string('makechildof', 'question', $category->parentitem->parentitem->name);
            } else {
                $action = $strmoveleft;
            }
            $pix = new pix_icon('t/' . $leftarrow, $action);
            $icons['left'] = $OUTPUT->action_icon(
                '#',
                $pix,
                null,
                ['data-tomove' => $category->id, 'data-tocategory' => $category->parentitem->parent],
            );
        }

        if (!empty($previousitem)) {
            $makechildof = get_string('makechildof', 'question', $previousitem->name);
            $pix = new pix_icon('t/' . $rightarrow, $makechildof);
            $icons['right'] = $OUTPUT->action_icon(
                '#',
                $pix,
                null,
                ['data-tomove' => $category->id, 'data-tocategory' => $previousitem->id]
            );
        }
        return $icons;
    }
}
