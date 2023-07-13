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

namespace qbank_customfields;

use qbank_customfields\customfield\question_handler;

/**
 * Class to handle import and export of customfield data
 *
 * @package   qbank_customfields
 * @copyright 2023 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class data_mapper extends \core_question\local\bank\data_mapper_base {

    public function get_question_data(array $questionids): array {
        $questiondata = parent::get_question_data($questionids);
        $customfieldhandler = question_handler::create();
        $qdatacontrollers = $customfieldhandler->get_instances_data($questionids, true);
        foreach ($qdatacontrollers as $qid => $datacontrollers) {
            foreach ($datacontrollers as $datacontroller) {
                $questiondata[$qid][$datacontroller->get_field()->get('shortname')] = $datacontroller->get_value();
            }
        }
        return $questiondata;
    }

    public function save_question_data(int $questionid, array $data): array {
        $return = parent::save_question_data($questionid, $data);
        try {
            $customfieldhandler = question_handler::create();
            foreach ($data as $field => $value) {
                $importdata['customfield_' . $field] = $value;
            }
            $importdata['id'] = $questionid;
            $customfieldhandler->instance_form_save((object)$importdata);
        } catch (\Throwable $e) {
            $return['error'] = $e->getMessage();
        }
        return $return;
    }
}
