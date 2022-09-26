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

import CoreFilter from 'core_question/qbank_datafilter';
import Notification from 'core/notification';
import Selectors from 'core/datafilter/selectors';
import Templates from 'core/templates';
import Fragment from 'core/fragment';

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
 * @param {string} view the view class for this page
 * @param {int} cmid if this is an activity, the course module ID.
 * @param {string} pagevars name of the callback for the fragment
 * @param {string} extraparams json encoded extra params for the extended apis
 */
export const init = (filterRegionId, defaultcourseid, defaultcategoryid,
                     perpage, contextId, component, callback, view, cmid, pagevars, extraparams) => {

    const SELECTORS = {
        QUESTION_CONTAINER_ID: '#questionscontainer',
        QUESTION_TABLE: '#questionscontainer table',
        SORT_LINK: '#questionscontainer div.sorters a',
        PAGINATION_LINK: '#questionscontainer a[href].page-link',
        LASTCHANGED_FIELD: '#questionsubmit input[name=lastchanged]',
        BULK_ACTIONS: '#bulkactionsui-container input',
    };

    const filterSet = document.querySelector(`#${filterRegionId}`);

    const filterCondition = {
        cat: defaultcategoryid,
        courseid: defaultcourseid,
        filters: {},
        filterverb: 0,
        qpage: 0,
        qperpage: perpage,
        sortdata: {},
        tabname: 'questions',
    };

    const defaultSort = document.querySelector(SELECTORS.QUESTION_TABLE)?.dataset?.defaultsort;
    if (defaultSort) {
        filterCondition.sortData = JSON.parse(defaultSort);
    }

    let filterQuery = '';

    // Init function with apply callback.
    const coreFilter = new CoreFilter(filterSet, function(filters, pendingPromise) {
        applyFilter(filters, pendingPromise);
    });
    coreFilter.init();

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
            filterCondition.filterverb = parseInt(filterSet.dataset.filterverb, 10);
            delete filterdata.filterverb;
            // Retrieve fitter info.
            filterCondition.filters = filterdata;
            if (Object.keys(filterdata).length !== 0) {
                if (isNaN(filterCondition.filterverb) === false) {
                    filterdata.filterverb = filterCondition.filterverb;
                }
                updateUrlParams(filterdata);
            }
        }
        // Load questions for first page.
        const viewData = {
            view: view,
            cmid: cmid,
            filtercondition: JSON.stringify(filterCondition),
            extraparams: extraparams,
            filterquery: filterQuery,
            lastchanged: document.querySelector(SELECTORS.LASTCHANGED_FIELD)?.value ?? null
        };
        Fragment.loadFragment(component, callback, contextId, viewData)
            // Render questions for first page and pagination.
            .then((questionhtml, jsfooter) => {
                const questionscontainer = document.querySelector(SELECTORS.QUESTION_CONTAINER_ID);
                if (questionhtml === undefined) {
                    questionhtml = '';
                }
                if (jsfooter === undefined) {
                    jsfooter = '';
                }
                Templates.replaceNodeContents(questionscontainer, questionhtml, jsfooter);
                // Resolve filter promise.
                if (pendingPromise) {
                    pendingPromise.resolve();
                }
            })
            .fail(Notification.exception);
    };

    /**
     * Update URL Param based upon the current filter.
     *
     * @param {Object} filters Active filters.
     */
    const updateUrlParams = (filters) => {
        const url = new URL(location.href);
        const filterQuery = JSON.stringify(filters);
        url.searchParams.set('filter', filterQuery);
        history.pushState(filters, '', url);
        document.querySelectorAll(SELECTORS.BULK_ACTIONS).forEach(bulkAction => {
            const actionUrl = new URL(bulkAction.formAction);
            const returnUrl = new URL(actionUrl.searchParams.get('returnurl'));
            returnUrl.searchParams.set('filter', filterQuery);
            actionUrl.searchParams.set('returnurl', returnUrl);
            bulkAction.formAction = actionUrl;
        });
    };

    /**
     * Cleans URL parameters.
     *
     */
    const cleanUrlParams = () => {
        const queryString = location.search;
        const urlParams = new URLSearchParams(queryString);
        if (urlParams.has('cmid')) {
            const cleanedUrl = new URL(location.href.replace(location.search, ''));
            cleanedUrl.searchParams.set('cmid', urlParams.get('cmid'));
            history.pushState({}, '', cleanedUrl);
        }

        if (urlParams.has('courseid')) {
            const cleanedUrl = new URL(location.href.replace(location.search, ''));
            cleanedUrl.searchParams.set('courseid', urlParams.get('courseid'));
            history.pushState({}, '', cleanedUrl);
        }
    };

    // Add listeners for the sorting actions.
    document.addEventListener('click', e => {
        const sortableLink = e.target.closest(SELECTORS.SORT_LINK);
        const paginationLink = e.target.closest(SELECTORS.PAGINATION_LINK);
        const clearLink = e.target.closest(Selectors.filterset.actions.resetFilters);
        if (sortableLink) {
            e.preventDefault();
            let oldSort = filterCondition.sortdata;
            filterCondition.sortdata = {};
            filterCondition.sortdata[sortableLink.dataset.sortname] = sortableLink.dataset.sortorder;
            for (const sortname in oldSort) {
                if (sortname !== sortableLink.dataset.sortname) {
                    filterCondition.sortdata[sortname] = oldSort[sortname];
                }
            }
            filterCondition.qpage = 0;
            coreFilter.updateTableFromFilter();
        }
        if (paginationLink) {
            e.preventDefault();
            let attr = paginationLink.getAttribute("href");
            if (attr !== '#') {
                const urlParams = new URLSearchParams(attr);
                filterCondition.qpage = urlParams.get('qpage');
                coreFilter.updateTableFromFilter();
            }
        }
        if (clearLink) {
            cleanUrlParams();
        }
    });

    // Run apply filter at page load.
    pagevars = JSON.parse(pagevars);
    let initialFilters;
    let filterverb = null;
    if (pagevars.filters) {
        // Load initial filter based on page vars.
        initialFilters = pagevars.filters;
        if (pagevars.filterverb) {
            filterverb = pagevars.filterverb;
        }
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
            if (urlFilter === 'filterverb') {
                filterverb = initialFilters[urlFilter];
                continue;
            }
            if (urlFilter !== 'courseid') {
                // Add each filter row.
                rowcount += 1;
                const filterdata = {
                    filtertype: urlFilter,
                    values:  initialFilters[urlFilter].values,
                    jointype: initialFilters[urlFilter].jointype,
                    filteroptions: initialFilters[urlFilter].filteroptions,
                    rownum: rowcount
                };
                coreFilter.addFilterRow(filterdata);
            }
        }
        coreFilter.filterSet.dataset.filterverb = filterverb;
        coreFilter.filterSet.querySelector(Selectors.filterset.fields.join).value = filterverb;
    }
};
