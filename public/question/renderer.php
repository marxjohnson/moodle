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
 * Renderers for outputting parts of the question bank.
 *
 * @package    core_question
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\attribute\deprecated;
use core\deprecation;
use core_question\output\bulk_actions_ui;
use core_question\output\column_header;
use core_question\output\column_sort;
use qbank_viewquestiontext\output\question_text_format;

defined('MOODLE_INTERNAL') || die();


/**
 * This renderer outputs parts of the question bank.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class core_question_bank_renderer extends plugin_renderer_base {

    /**
     * Display additional navigation if needed.
     *
     * @param string $active
     * @return string
     */
    public function extra_horizontal_navigation($active = null) {
        // Horizontal navigation for question bank.
        if ($questionnode = $this->page->settingsnav->find("questionbank", \navigation_node::TYPE_CONTAINER)) {
            if ($children = $questionnode->children) {
                $tabs = [];
                foreach ($children as $key => $node) {
                    $tabs[] = new \tabobject($node->key, $node->action, $node->text);
                }
                if (empty($active) && $questionnode->find_active_node()) {
                    $active = $questionnode->find_active_node()->key;
                }
                return \html_writer::div(print_tabs([$tabs], $active, null, null, true),
                        'questionbank-navigation');
            }
        }
        return '';
    }

    /**
     * Output the icon for a question type.
     *
     * @param string $qtype the question type.
     * @return string HTML fragment.
     */
    public function qtype_icon($qtype) {
        $qtype = question_bank::get_qtype($qtype, false);
        $namestr = $qtype->local_name();

        return $this->image_icon('icon', $namestr, $qtype->plugin_name(), array('title' => $namestr));
    }

    /**
     * Render the column headers.
     *
     * @param array $qbankheaderdata
     * @return bool|string
     * @deprecated Since Moodle 5.2 MDL-87103.
     */
    #[deprecated(
        replacement: column_header::class,
        since: '5.2',
        reason: 'Replaced with a renderable',
        mdl: 'MDL-87103',
    )]
    public function render_column_header($qbankheaderdata) {
        deprecation::emit_deprecation([self::class, __FUNCTION__]);
        return $this->render_from_template('core_question/column_header', $qbankheaderdata);
    }

    /**
     * Render the column sort elements.
     *
     * @param array $sortdata
     * @return bool|string
     * @deprecated Since Moodle 5.2 MDL-87103.
     */
    #[deprecated(
        replacement: column_sort::class,
        since: '5.2',
        reason: 'Replaced with a renderable',
        mdl: 'MDL-87103',
    )]
    public function render_column_sort($sortdata) {
        deprecation::emit_deprecation([$this, __FUNCTION__]);
        return $this->render_from_template('core_question/column_sort', $sortdata);
    }

    /**
     * Render question pagination.
     *
     * @param array $displaydata
     * @return bool|string
     * @deprecated since Moodle 5.1 MDL-78091
     * @todo MDL-84648 Final deprecation on Moodle 6.0
     */
    #[\core\attribute\deprecated(null, since: '5.1', mdl: 'MDL-78091')]
    public function render_question_pagination($displaydata) {
        \core\deprecation::emit_deprecation([$this, __FUNCTION__]);
        // The template question_pagination should also be deleted with this function.
        return $this->render_from_template('core_question/question_pagination', $displaydata);
    }

    /**
     * Render the showtext option.
     *
     * It's not a checkbox any more! [Name your API after the purpose, not the implementation!]
     *
     * @param array $displaydata
     * @return string
     * @deprecated Since Moodle 5.2 MDL-87103.
     */
    #[deprecated(
        replacement: question_text_format::class,
        since: '5.2',
        reason: 'Replaced with a pluggable question bank control',
        mdl: 'MDL-87103',
    )]
    public function render_showtext_checkbox($displaydata) {
        \core\deprecation::emit_deprecation([$this, __FUNCTION__]);
        return $this->render_from_template('core_question/showtext_option',
                ['selected' . $displaydata['checked'] => true]);
    }

    /**
     * Render bulk actions ui.
     *
     * @param array $displaydata
     * @return bool|string
     */
    #[deprecated(
        replacement: bulk_actions_ui::class,
        since: '5.2',
        reason: 'Replaced with a renderable',
        mdl: 'MDL-87103',
    )]
    public function render_bulk_actions_ui($displaydata) {
        \core\deprecation::emit_deprecation([$this, __FUNCTION__]);
        return $this->render_from_template('core_question/bulk_actions_ui', $displaydata);
    }
}
