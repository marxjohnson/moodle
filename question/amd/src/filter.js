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
 * Question bank filter management.
 *
 * @module     core_question/filter
 * @copyright  2021 Tomo Tsuyuki <tomotsuyuki@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import ajax from 'core/ajax';
import CoreFilter from 'core/datafilter';
import Notification from 'core/notification';
import Selectors from 'core/datafilter/selectors';
import Templates from 'core/templates';

/**
 * Initialise the question bank filter on the element with the given id.
 *
 * @param {String} filterRegionId id of the filter region
 * @param {String} defaultcourseid default course id
 * @param {String} defaultcategoryid default category id
 * @param {int} perpage number of question per page
 * @param {int} contextId id of the context
 * @param {string} component name of the component for fragment
 * @param {string} callback name of the callback for the fragment
 * @param {string} pagevars name of the callback for the fragment
 * @param {string} extraparams json encoded extra params for the extended apis
 */
export const init = (filterRegionId, defaultcourseid, defaultcategoryid,
                     perpage, contextId, component, callback, pagevars, extraparams) => {

    const filterSet = document.querySelector(`#${filterRegionId}`);

    // Default filter params for WS function.
    let wsfilter = {
        // Default value filterset::JOINTYPE_DEFAULT.
        filters: [],
        filteroptions: {
            filterverb: 2,
        },
        displayoptions: {
            perpage: perpage,
        },
        sortdata: [
            {
                sortby: 'qbank_viewquestiontype\\question_type_column',
                sortorder: 4,
            }
        ],
        defaultcourseid: defaultcourseid,
        defaultcategoryid: defaultcategoryid,
    };

    // HTML <div> ID of question container.
    const SELECTORS = {
        QUESTION_CONTAINER_ID: '#questionscontainer',
        SORT_LINK: '#questionscontainer div.sorters a',
        PAGINATION_LINK: '#questionscontainer a[href].page-link',
    };

    // Init function with apply callback.
    const coreFilter = new CoreFilter(filterSet, function(filters, pendingPromise) {
        applyFilter(filters, pendingPromise);
    });
    coreFilter.init();

    /**
     * Ajax call to retrieve question via ws functions
     *
     * @param {Object} filter filter object
     * @returns {*}
     */
    const requestQuestions = filter => {
        const request = {methodname: 'core_question_filter', args: filter};
        return ajax.call([request])[0];
    };

    /**
     * Retrieve table data.
     *
     * @param {Object} filterdata data
     * @param {Promise} pendingPromise pending promise
     */
    const applyFilter = (filterdata, pendingPromise) => {
        // Getting filter data.
        // Otherwise, the ws function should retrieves question based on default courseid and cateogryid.
        if (filterdata) {
            // Main join types.
            wsfilter.filteroptions.filterverb = parseInt(filterSet.dataset.filterverb, 10);
            // Clean old filter
            wsfilter.filters = [];

            // Retrieve fitter info.
            for (const [key, value] of Object.entries(filterdata)) {
                let filter = {
                    'filtertype': key,
                    'conditionclass': value.conditionclass,
                    'jointype': value.jointype,
                    'rangetype': value.rangetype,
                    'values': value.values.toString()
                };
                wsfilter.filters.push(filter);
            }
            if (Object.keys(filterdata).length !== 0) {
                updateUrlParams(filterdata);
            }
        }
        // Load questions for first page.
        requestQuestions(wsfilter)
            .then((response) => {
                // Cleans any notifications if not needed.
                let element = document.getElementById('user-notifications');
                while (element.firstChild) {
                    element.removeChild(element.firstChild);
                }
                if (response.warnings[0] !== undefined) {
                    if (response.warnings[0].warningcode === 'nocategoryconditionspecified') {
                        Notification.addNotification({
                            message: response.warnings[0].message,
                            type: 'info'
                          });
                    }
                }
                return renderQuestiondata(response.filtercondition);
            })
            // Render questions for first page and pagination.
            .then((response) => {
                const questionscontainer = document.querySelector(SELECTORS.QUESTION_CONTAINER_ID);
                if (response.questionhtml === undefined) {
                    response.questionhtml = '';
                }
                if (response.jsfooter === undefined) {
                    response.jsfooter = '';
                }
                Templates.replaceNodeContents(questionscontainer, response.questionhtml, response.jsfooter);
                // Resolve filter promise.
                if (pendingPromise) {
                    pendingPromise.resolve();
                }
            })
            .fail(Notification.exception);
    };

    /**
     * Render question data using the fragment.
     * @param {object} filtercondition
     * @return {*}
     */
    const renderQuestiondata = (filtercondition) => {
        const viewData = {
            component: component,
            callback: callback,
            filtercondition: filtercondition,
            contextid: contextId,
            extraparams: extraparams,
        };
        const request = {methodname: 'core_question_view', args: viewData};
        return ajax.call([request])[0];
    };

    /**
     * Update URL Param based upon the current filter.
     *
     * @param {Object} filters Active filters.
     */
    const updateUrlParams = (filters) => {
        const url = new URL(location.href);
        const query = objectToQuery(filters);
        url.searchParams.set('filter', query);
        history.pushState(filters, '', url);
    };

    /**
     * Convert a nested object into query parameters.
     *
     * @param {Object} filters Active filters.
     * @return {String}
     */
    const objectToQuery = (filters) => {
        return Object.keys(filters).map(key => {
            let value = filters[key];
            if (value !== null && typeof value === 'object') {
                value = objectToQuery(value);
            }
            return `${key}=${encodeURIComponent(`${value}`.replace(/\s/g, '_'))}`;
        }).join('&');
    };

    /**
     * Load URL parameter.
     *
     * @return {Object} filters
     */
    const loadUrlParams = () => {
        const queryString = location.search;
        const urlParams = new URLSearchParams(queryString);
        if (urlParams.has('filter')) {
            const filters = queryToObject(urlParams.get('filter'));
            return filters;
        }
        return {};
    };

    /**
     * Convert query parameters into object.
     *
     * @param {string} query Query representing filter object.
     * @return {object}
     */
    const queryToObject = (query) => {
        const object = {};
        const params = new URLSearchParams(query);
        const entries = params.entries();
        entries.forEach((value) => {
            const param = value[0];
            object[param] = !isNaN(params.get(param)) ? parseInt(params.get(param)) : params.get(param);
            if (isNaN(object[param]) && object[param].includes('&')) {
                object[param] = queryToObject(object[param]);
            }
            if (param == 'values') {
                if (typeof object[param] == 'string' && object[param].includes('=')) {
                    object[param] = [object[param].split('=')[1]];
                } else if (typeof object[param] == 'number') {
                    object[param] = [object[param]];
                } else {
                    object[param] = Object.values(object[param]);
                }
            }
        });
        return object;
    };

    // Run apply filter at page load.
    pagevars = JSON.parse(pagevars);
    let initialFilters;
    if (pagevars.filters) {
        // Load initial filter based on page vars.
        initialFilters = pagevars.filters;
    } else {
        // Otherwise, load filter from URL.
        initialFilters = loadUrlParams();
    }

    if (Object.entries(initialFilters).length !== 0) {
        // Remove the default empty filter row.
        const emptyFilterRow = filterSet.querySelector(Selectors.filterset.regions.emptyFilterRow);
        if (emptyFilterRow) {
            emptyFilterRow.remove();
        }

        // Add fitlers.
        let rowcount = 0;
        for (const urlFilter in initialFilters) {
            if (urlFilter !== 'courseid') {
                // Add each filter row.
                rowcount += 1;
                const filterdata = {
                    filtertype: urlFilter,
                    values:  initialFilters[urlFilter].values,
                    jointype: initialFilters[urlFilter].jointype,
                    rangetype: initialFilters[urlFilter].rangetype,
                    rownum: rowcount
                };
                coreFilter.addFilterRow(filterdata);
            }
        }

        // Apply filter.
        applyFilter(initialFilters);
    }
};
