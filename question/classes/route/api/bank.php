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

namespace core_question\route\api;

use core\param;
use core\context\module;
use core\exception\required_capability_exception;
use core\router\route;
use core\router\schema\parameters\path_parameter;
use core\router\schema\response\payload_response;
use core_question\local\bank\question_edit_contexts;
use core_question\local\bank\question_version_status;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Web service functions related to question banks
 *
 * @package   core_question
 * @copyright 2025 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class bank {
    /**
     * Return the total number of questions in the question bank for the given course module.
     *
     * This will count all top-level questions (no subquestions) that are not hidden.
     *
     * @param int $cmid
     */
    #[route(
        path: '/bank/{cmid}/question_count',
        method: ['GET'],
        pathtypes: [
            new path_parameter(
                name: 'cmid',
                type: param::INT,
                description: 'The course module ID the questions belong to',
                required: true,
            ),
        ],
    )]
    public function question_count(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $cmid,
    ): payload_response {
        global $DB;
        $modulecontext = module::instance($cmid);
        $coursecontext = $modulecontext->get_course_context();
        require_login($coursecontext->instanceid, preventredirect: true);
        $capabilities = array_merge(question_edit_contexts::$caps['editq'], question_edit_contexts::$caps['categories']);

        if (!has_any_capability($capabilities, $modulecontext)) {
            throw new required_capability_exception(
                $modulecontext,
                reset($capabilities),
                'missingcapability',
                'question',
            );
        }

        $sql = "
            SELECT COUNT(1)
              FROM {question} q
              JOIN {question_versions} qv ON qv.questionid = q.id
              JOIN {question_bank_entries} qbe ON qbe.id = qv.questionbankentryid
              JOIN {question_categories} qc ON qc.id = qbe.questioncategoryid
             WHERE qc.contextid = :contextid
                   AND q.parent = '0'
                   AND (
                       qv.version = (
                           SELECT MAX(qv1.version)
                             FROM {question_versions} qv1
                             JOIN {question_bank_entries} qbe1 ON qbe1.id = qv1.questionbankentryid
                            WHERE qbe1.id = qbe.id
                                  AND qv1.status != :hidden
                       )
                   )
        ";
        $params = [
            'contextid' => $modulecontext->id,
            'hidden' => question_version_status::QUESTION_STATUS_HIDDEN,
        ];
        $count = $DB->count_records_sql($sql, $params);
        return new payload_response(
            payload: [
                'count' => $count,
            ],
            request: $request,
            response: $response,
        );
    }
}