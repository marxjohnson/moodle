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

namespace core_question\local\bank;

use core\context\course;
use core\context\module;
use core\di;
use core\exception\required_capability_exception;

/**
 * Methods for counting the questions in different contexts
 *
 * @package   core_question
 * @copyright 2026 onwards Catalyst IT EU {@link https://catalyst-eu.net}
 * @author    Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class question_counts {
    /**
     * Return a list of question counts for each module context within a course, that has question categories.
     *
     * @param course $coursecontext The context of the course to search within.
     * @return array [cmid => count]
     */
    public function by_course(course $coursecontext): array {
        $db = di::get(\moodle_database::class);
        $capabilities = array_merge(question_edit_contexts::$caps['editq'], question_edit_contexts::$caps['categories']);

        if (!has_any_capability($capabilities, $coursecontext)) {
            throw new required_capability_exception(
                $coursecontext,
                reset($capabilities),
                'missingcapability',
                'question',
            );
        }

        $modinfo = get_fast_modinfo($coursecontext->instanceid);

        $questioncontexts = [];
        $supportedmods = [];
        foreach ($modinfo->get_cms() as $cminfo) {
            if (array_key_exists($cminfo->modname, $supportedmods)) {
                $supported = $supportedmods[$cminfo->modname];
            } else {
                $supported = call_user_func($cminfo->modname . '_supports', FEATURE_USES_QUESTIONS)
                    || call_user_func($cminfo->modname . '_supports', FEATURE_PUBLISHES_QUESTIONS);
                $supportedmods[$cminfo->modname] = $supported;
            }
            if ($supported) {
                $questioncontexts[] = $cminfo->context->id;
            }
        }

        if (empty($questioncontexts)) {
            return [];
        }

        [$contextinsql, $contextinparams] = $db->get_in_or_equal($questioncontexts, SQL_PARAMS_NAMED);

        // Get a count of all questions in each module context, keyed by cmid.
        // Only look in contexts for those module which support FEATURE_PUBLISHES_QUESTIONS.
        // Return a count of 0 for those modules with no questions or question categories.
        // The double LEFT JOIN of question_versions ensures we only get the latest version for a question bank entry.
        $sql = "
            SELECT c.instanceid,
                   COUNT(
                       CASE
                           WHEN q.id IS NOT NULL THEN 1
                       END
                   ) AS count
              FROM {context} c
              JOIN {question_categories} qc ON qc.contextid = c.id
         LEFT JOIN {question_bank_entries} qbe ON qbe.questioncategoryid = qc.id
         LEFT JOIN {question_versions} qv ON qv.questionbankentryid = qbe.id
         LEFT JOIN {question_versions} qv1 ON qv1.questionbankentryid = qbe.id AND qv.version < qv1.version
         LEFT JOIN {question} q ON q.id = qv.questionid
             WHERE c.id {$contextinsql}
                   AND (qv.status != :hidden OR q.id IS NULL)
                   AND (q.parent = '0' OR q.id IS NULL)
                   AND (qv1.questionbankentryid IS NULL OR q.id IS NULL)
          GROUP BY c.instanceid
        ";
        $params = [
            ...$contextinparams,
            'hidden' => question_version_status::QUESTION_STATUS_HIDDEN,
        ];
        return $db->get_records_sql_menu($sql, $params);
    }
}
