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

namespace qbank_statistics\output;

use core\deprecation;
use qbank_statistics\columns\discrimination_index;
use qbank_statistics\columns\discriminative_efficiency;
use qbank_statistics\columns\facility_index;
use qbank_statistics\helper;
/**
 * Description
 *
 * @package    qbank_statistics
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Nathan Nguyen <nathannguyen@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[deprecated(
    replacement: statistic_cell::class,
    since: 5.2,
    reason: 'Replaced with a renderables',
    mdl: 'MDL-87103',
)]
class renderer extends \plugin_renderer_base {

    /**
     * Render facility index column.
     *
     * @param float|null $facility facility index
     * @return string
     */
    #[deprecated(
        replacement: facility_index::class . '::render',
        since: 5.2,
        reason: 'Replaced with a renderable',
        mdl: 'MDL-87103',
    )]
    public function render_facility_index(?float $facility): string {
        deprecation::emit_deprecation([$this, __FUNCTION__]);
        $displaydata['facility_index'] = helper::format_percentage($facility);
        return $this->render_from_template('qbank_statistics/facility_index', $displaydata);
    }

    /**
     * Render discriminative_efficiency column.
     *
     * @param float|null $discriminativeefficiency discriminative efficiency
     * @return string
     */
    #[deprecated(
        replacement: discriminative_efficiency::class . '::render',
        since: 5.2,
        reason: 'Replaced with a renderable',
        mdl: 'MDL-87103',
    )]
    public function render_discriminative_efficiency(?float $discriminativeefficiency): string {
        deprecation::emit_deprecation([$this, __FUNCTION__]);
        $displaydata['discriminative_efficiency'] = helper::format_percentage($discriminativeefficiency, false);
        return $this->render_from_template('qbank_statistics/discriminative_efficiency', $displaydata);
    }

    /**
     * Render discrimination index column.
     *
     * @param float|null $discriminationindex discrimination index
     * @return string
     */
    #[deprecated(
        replacement: discrimination_index::class . '::render',
        since: 5.2,
        reason: 'Replaced with a renderable',
        mdl: 'MDL-87103',
    )]
    public function render_discrimination_index(?float $discriminationindex): string {
        deprecation::emit_deprecation([$this, __FUNCTION__]);
        list($content, $classes) = helper::format_discrimination_index($discriminationindex);
        $displaydata['discrimination_index'] = $content;
        $displaydata['classes'] = $classes;
        return $this->render_from_template('qbank_statistics/discrimination_index', $displaydata);
    }
}
