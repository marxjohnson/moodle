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

use moodle_url;
use templatable;
use renderable;
use qbank_columnsortorder\column_manager;

/**
 * Class renderer.
 * @package    qbank_columnsortorder
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Ghaly Marc-Alexandre <marc-alexandreghaly@catalyst-ca.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class column_sort_ui implements templatable, renderable {

    /**
     * @var bool $editing Are we displaying the UI in editing mode?
     */
    protected bool $editing;

    public function __construct(bool $editing = false) {
        $this->editing = $editing;
    }

    public function export_for_template(\renderer_base $output): array {
        $columnsortorder = new column_manager();
        $enabledcolumns = $columnsortorder->get_columns($this->editing);
        $disabledcolumns = $columnsortorder->get_disabled_columns();
        $params = [];
        foreach ($enabledcolumns as $columnname) {
            $name = $columnname->name;
            $colname = get_string('qbankcolumnname', 'qbank_columnsortorder', $columnname->colname);
            if ($columnname->class === 'qbank_customfields\custom_field_column') {
                $columnname->class .= "\\$columnname->colname";
            }
            $params['names'][] = ['name' => $name, 'tiptitle' => $name, 'colname' => $colname, 'class' => $columnname->class];
        }

        $params['disabled'] = $disabledcolumns;
        $params['pinnedcolumns'] = json_encode($columnsortorder->pinnedcolumns);
        $params['hiddencolumns'] = json_encode($columnsortorder->hiddencolumns);
        $params['colsize'] = json_encode($columnsortorder->colsize);
        $params['columnsdisabled'] = (!empty($params['disabled'])) ? true : false;
        $params['extraclasses'] = 'pr-1';
        $urltoredirect = new moodle_url('/admin/settings.php', ['section' => 'manageqbanks']);

        $params['urltomanageqbanks'] = get_string('qbankgotomanageqbanks', 'qbank_columnsortorder', $urltoredirect->out());

        return $params;
    }
}
