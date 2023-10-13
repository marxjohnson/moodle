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

namespace core_course\external;

use context_course;
use context_helper;
use context_user;
use external_api;
use external_function_parameters;
use external_multiple_structure;
use external_single_structure;
use external_value;
use invalid_parameter_exception;

/**
 * Web service to handle retrieving courses by timeline.
 *
 * Functionality moved from externallib.php to its own class.
 *
 * @package    core_course
 * @copyright  2023 onwards Catalyst IT {@link http://www.catalyst-eu.net/}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Conn Warwicker <conn.warwicker@catalyst-eu.net>
 */
class get_enrolled_courses_by_timeline_classification extends external_api {

    /**
     * This is the exporter class we are using.
     */
    const EXPORTER_CLASS = '\core_course\external\course_summary_exporter';

    /**
     * Get courses matching the given timeline classification.
     *
     * NOTE: The offset applies to the unfiltered full set of courses before the classification
     * filtering is done.
     * E.g.
     * If the user is enrolled in 5 courses:
     * c1, c2, c3, c4, and c5
     * And c4 and c5 are 'future' courses
     *
     * If a request comes in for future courses with an offset of 1 it will mean that
     * c1 is skipped (because the offset applies *before* the classification filtering)
     * and c4 and c5 will be return.
     *
     * @param  string $classification past, inprogress, or future
     * @param  int $limit Result set limit
     * @param  int $offset Offset the full course set before timeline classification is applied
     * @param  string $sort SQL sort string for results
     * @param  string $customfieldname
     * @param  string $customfieldvalue
     * @param  string $searchvalue
     * @return array list of courses and warnings
     * @throws  invalid_parameter_exception
     */
    public static function execute(
        string $classification,
        int $limit = 0,
        int $offset = 0,
        string $sort = null,
        string $customfieldname = null,
        string $customfieldvalue = null,
        string $searchvalue = null
    ) {

        global $CFG, $PAGE, $USER;
        require_once($CFG->dirroot . '/course/lib.php');

        $exporterclass = static::get_exporter_class();

        $params = self::validate_parameters(self::execute_parameters(),
            [
                'classification' => $classification,
                'limit' => $limit,
                'offset' => $offset,
                'sort' => $sort,
                'customfieldvalue' => $customfieldvalue,
                'searchvalue' => $searchvalue,
            ]
        );

        $classification = $params['classification'];
        $limit = $params['limit'];
        $offset = $params['offset'];
        $sort = $params['sort'];
        $customfieldvalue = $params['customfieldvalue'];
        $searchvalue = clean_param($params['searchvalue'], PARAM_TEXT);

        switch($classification) {
            case COURSE_TIMELINE_ALLINCLUDINGHIDDEN:
                break;
            case COURSE_TIMELINE_ALL:
                break;
            case COURSE_TIMELINE_PAST:
                break;
            case COURSE_TIMELINE_INPROGRESS:
                break;
            case COURSE_TIMELINE_FUTURE:
                break;
            case COURSE_FAVOURITES:
                break;
            case COURSE_TIMELINE_HIDDEN:
                break;
            case COURSE_TIMELINE_SEARCH:
                break;
            case COURSE_CUSTOMFIELD:
                break;
            default:
                throw new invalid_parameter_exception('Invalid classification');
        }

        self::validate_context(context_user::instance($USER->id));

        $requiredproperties = $exporterclass::define_properties();
        $fields = join(',', array_keys($requiredproperties));
        $hiddencourses = get_hidden_courses_on_timeline();
        $courses = [];

        // If the timeline requires really all courses, get really all courses.
        if ($classification == COURSE_TIMELINE_ALLINCLUDINGHIDDEN) {
            $courses = course_get_enrolled_courses_for_logged_in_user(0, $offset, $sort, $fields, COURSE_DB_QUERY_LIMIT);

            // Otherwise if the timeline requires the hidden courses then restrict the result to only $hiddencourses.
        } else if ($classification == COURSE_TIMELINE_HIDDEN) {
            $courses = course_get_enrolled_courses_for_logged_in_user(0, $offset, $sort, $fields,
                COURSE_DB_QUERY_LIMIT, $hiddencourses);

            // Otherwise get the requested courses and exclude the hidden courses.
        } else if ($classification == COURSE_TIMELINE_SEARCH) {
            // Prepare the search API options.
            $searchcriteria['search'] = $searchvalue;
            $options = ['idonly' => true];
            $courses = course_get_enrolled_courses_for_logged_in_user_from_search(
                0,
                $offset,
                $sort,
                $fields,
                COURSE_DB_QUERY_LIMIT,
                $searchcriteria,
                $options
            );
        } else {
            $courses = course_get_enrolled_courses_for_logged_in_user(0, $offset, $sort, $fields,
                COURSE_DB_QUERY_LIMIT, [], $hiddencourses);
        }

        $favouritecourseids = [];
        $ufservice = \core_favourites\service_factory::get_service_for_user_context(context_user::instance($USER->id));
        $favourites = $ufservice->find_favourites_by_type('core_course', 'courses');

        if ($favourites) {
            $favouritecourseids = array_map(
                function($favourite) {
                    return $favourite->itemid;
                }, $favourites);
        }

        if ($classification == COURSE_FAVOURITES) {
            list($filteredcourses, $processedcount) = course_filter_courses_by_favourites(
                $courses,
                $favouritecourseids,
                $limit
            );
        } else if ($classification == COURSE_CUSTOMFIELD) {
            list($filteredcourses, $processedcount) = course_filter_courses_by_customfield(
                $courses,
                $customfieldname,
                $customfieldvalue,
                $limit
            );
        } else {
            list($filteredcourses, $processedcount) = course_filter_courses_by_timeline_classification(
                $courses,
                $classification,
                $limit
            );
        }

        $renderer = $PAGE->get_renderer('core');
        $formattedcourses = array_map(function($course) use ($renderer, $favouritecourseids, $exporterclass) {
            if ($course == null) {
                return;
            }
            context_helper::preload_from_record($course);
            $context = context_course::instance($course->id);
            $isfavourite = false;
            if (in_array($course->id, $favouritecourseids)) {
                $isfavourite = true;
            }
            $exporter = new $exporterclass($course, ['context' => $context, 'isfavourite' => $isfavourite]);
            return $exporter->export($renderer);
        }, $filteredcourses);

        $formattedcourses = array_filter($formattedcourses, function($course) {
            if ($course != null) {
                return $course;
            }
        });

        return [
            'courses' => $formattedcourses,
            'nextoffset' => $offset + $processedcount,
        ];
    }

    /**
     * Returns description of method parameters
     *
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters(
            [
                'classification' => new external_value(PARAM_ALPHA, 'future, inprogress, or past'),
                'limit' => new external_value(PARAM_INT, 'Result set limit', VALUE_DEFAULT, 0),
                'offset' => new external_value(PARAM_INT, 'Result set offset', VALUE_DEFAULT, 0),
                'sort' => new external_value(PARAM_TEXT, 'Sort string', VALUE_DEFAULT, null),
                'customfieldname' => new external_value(PARAM_ALPHANUMEXT, 'Used when classification = customfield',
                    VALUE_DEFAULT, null),
                'customfieldvalue' => new external_value(PARAM_RAW, 'Used when classification = customfield',
                    VALUE_DEFAULT, null),
                'searchvalue' => new external_value(PARAM_RAW, 'The value a user wishes to search against',
                    VALUE_DEFAULT, null),
                'includesummary' => new external_value(PARAM_BOOL, 'Whether course summary should be returned or not in the data',
                    VALUE_DEFAULT, true),
            ]
        );
    }

    /**
     * Returns description of method result value
     *
     * @return external_description
     */
    public static function execute_returns() {

        $exporterclass = static::get_exporter_class();
        return new external_single_structure(
            [
                'courses' => new external_multiple_structure($exporterclass::get_read_structure(), 'Course'),
                'nextoffset' => new external_value(PARAM_INT, 'Offset for the next request'),
            ]
        );

    }

    /**
     * Return the class name to use for the exporter class.
     *
     * @return string
     */
    public static function get_exporter_class(): string {
        return static::EXPORTER_CLASS;
    }

}
