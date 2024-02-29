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

namespace mod_quiz\route\api;

use core\exception\moodle_exception;
use core\param;
use core\router\require_login;
use core\router\route;
use core\router\schema\objects\array_of_things;
use core\router\schema\objects\scalar_type;
use core\router\schema\objects\schema_object;
use core\router\schema\parameters\path_parameter;
use core\router\schema\parameters\query_parameter;
use core\router\schema\response\content\json_media_type;
use core\router\schema\response\payload_response;
use core\router\schema\response\response;
use dml_exception;
use mod_quiz\quiz_attempt;
use mod_quiz\quiz_attempt_datum;
use mod_quiz\quiz_attempt_question;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * API endpoints for quiz attempts
 *
 * @package   mod_quiz
 * @copyright 2026 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class attempts {
    /**
     * Get the review data for a quiz attempt, including the attempt record and the data for each question attempt.
     *
     * This will return all questions by default, or a single page of questions if the `page` query string parameter is used.
     *
     * @param ServerRequestInterface $request
     * @param ResponseInterface $response
     * @param int $attemptid
     * @return payload_response
     */
    #[route(
        path: '/attempts/{attemptid}/review',
        pathtypes: [
            new path_parameter(
                name: 'attemptid',
                type: param::INT,
                description: 'The ID of the attempt we are returning the review for',
                required: true,
            ),
        ],
        queryparams: [
            new query_parameter(
                name: 'page',
                description: 'Page number. If omitted, return all the questions in all the pages',
                type: param::INT,
            ),
        ],
        responses: [
            new response(
                statuscode: 200,
                description: 'Attempt was found, and the review was returned.',
                content: [
                    new json_media_type(
                        schema: new schema_object(
                            content: [
                                'attempt' => new schema_object(
                                    content: [
                                        'id' => new scalar_type(param::INT),
                                        'quiz' => new scalar_type(param::INT),
                                        'userid' => new scalar_type(param::INT),
                                        'attempt' => new scalar_type(param::INT),
                                        'uniqueid' => new scalar_type(param::INT),
                                        'layout' => new scalar_type(param::TEXT),
                                        'currentpage' => new scalar_type(param::INT),
                                        'preview' => new scalar_type(param::INT),
                                        'state' => new scalar_type(param::ALPHA),
                                        'timestart' => new scalar_type(param::INT),
                                        'timefinish' => new scalar_type(param::INT),
                                        'timemodified' => new scalar_type(param::INT),
                                        'timemodifiedoffline' => new scalar_type(param::INT),
                                        'timecheckstate' => new scalar_type(param::INT),
                                        'sumgrades' => new scalar_type(param::FLOAT),
                                        'gradenotificationsenttime' => new scalar_type(param::INT),
                                    ],
                                ),
                                'questions' => new array_of_things(quiz_attempt_question::class),
                                'additionaldata' => new array_of_things(quiz_attempt_datum::class),
                                'grade' => new scalar_type(param::FLOAT),
                            ],
                        ),
                    ),
                ],
            ),
            new response(
                statuscode: 404,
                description: 'The attempt does not exist.',
                content: [
                    new json_media_type(
                        schema: new schema_object(
                            content: [
                                'message' => new scalar_type(PARAM::TEXT),
                            ],
                        ),
                    ),
                ],
            ),
            new response(
                statuscode: 400,
                description: 'The attempt was found, but is not in a state where the review can be returned.',
                content: [
                    new json_media_type(
                        schema: new schema_object(
                            content: [
                                'message' => new scalar_type(PARAM::TEXT),
                            ],
                        ),
                    ),
                ],
            ),
            new response(
                statuscode: 403,
                description: 'The attempt was found, you are not allowed to see the review.',
                content: [
                    new json_media_type(
                        schema: new schema_object(
                            content: [
                                'message' => new scalar_type(PARAM::TEXT),
                            ],
                        ),
                    ),
                ],
            ),
            new response(
                statuscode: 500,
                description: 'An unhandled error occurred.',
                content: [
                    new json_media_type(
                        schema: new schema_object(
                            content: [
                                'message' => new scalar_type(PARAM::TEXT),
                            ],
                        ),
                    ),
                ],
            ),
        ],
        requirelogin: new require_login(true, false, 'course', false)
    )]
    public function get_review(
        ServerRequestInterface $request,
        ResponseInterface $response,
        int $attemptid,
    ): payload_response {
        $params = $request->getQueryParams();

        try {
            $attempt = quiz_attempt::create($attemptid);
        } catch (dml_exception $e) {
            return new payload_response(
                payload: ['message' => $e->getMessage()],
                request: $request,
                response: $response->withStatus(404),
            );
        }

        try {
            $review = $attempt->get_review($params['page'] ?: null);
            return new payload_response(
                payload: $review,
                request: $request,
                response: $response,
            );
        } catch (moodle_exception $e) {
            $statuscode = match ($e->errorcode) {
                'attemptclosed' => 400,
                'noreview', 'noreviewattempt' => 403,
                default => 500,
            };
            return new payload_response(
                payload: ['message' => $e->getMessage()],
                request: $request,
                response: $response->withStatus($statuscode),
            );
        }
    }
}
