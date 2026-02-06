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

namespace qbank_columnsortorder\output;

use core\attribute\deprecated;
use core\deprecation;
use moodle_url;
use qbank_columnsortorder\local\bank\preview_view;
use templatable;
use renderable;
use qbank_columnsortorder\column_manager;

/**
 * Renderable for the question bank preview.
 *
 * This takes the HTML for a question bank preview, and displays in a page with a link to return to the admin screen.
 *
 * @package   qbank_columnsortorder
 * @copyright 2023 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[deprecated(
    replacement: preview_view::class,
    since: 5.2,
    reason: 'preview_view is now itself templatable, no need for another wrapper.',
    mdl: 'MDL-87103',
)]
class column_sort_preview implements renderable, templatable {
    /** @var string Rendered preview HTML. */
    protected string $preview;

    /**
     * Store rendered preview for template context.
     *
     * @param string $preview
     */
    #[deprecated(
        replacement: preview_view::class,
        since: 5.2,
        reason: 'preview_view is now itself templatable, no need for another wrapper.',
        mdl: 'MDL-87103',
    )]
    public function __construct(string $preview) {
        deprecation::emit_deprecation([$this, __FUNCTION__]);
        $this->preview = $preview;
    }

    #[deprecated(
        replacement: preview_view::class,
        since: 5.2,
        reason: 'preview_view is now itself templatable, no need for another wrapper.',
        mdl: 'MDL-87103',
    )]
    public function export_for_template(\renderer_base $output): array {
        deprecation::emit_deprecation([$this, __FUNCTION__]);
        $context = [
            'backurl' => new moodle_url('/question/bank/columnsortorder/sortcolumns.php'),
            'preview' => $this->preview,
        ];
        return $context;
    }
}
