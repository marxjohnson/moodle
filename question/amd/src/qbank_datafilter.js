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
 * @module     core_question/qbank_datafilter
 * @copyright  2022 Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import CoreFilter from 'core/datafilter';
import Notification from 'core/notification';
import Pending from 'core/pending';
import Selectors from 'core/datafilter/selectors';
import Templates from 'core/templates';
import GenericFilter from 'core/datafilter/filtertype';

export default class extends CoreFilter {
    constructor(filterSet, applyCallback) {
        super(filterSet, applyCallback);
    }

    addFilterRow(filterdata = {}) {
        const pendingPromise = new Pending('core/datafilter:addFilterRow');
        const rownum = filterdata.rownum ?? 1 + this.getFilterRegion().querySelectorAll(Selectors.filter.region).length;
        return Templates.renderForPromise('core_question/qbank_filter_row', {"rownumber": rownum})
            .then(({html, js}) => {
                const newContentNodes = Templates.appendNodeContents(this.getFilterRegion(), html, js);

                return newContentNodes;
            })
            .then(filterRow => {
                // Note: This is a nasty hack.
                // We should try to find a better way of doing this.
                // We do not have the list of types in a readily consumable format, so we take the pre-rendered one and copy
                // it in place.
                const typeList = this.filterSet.querySelector(Selectors.data.typeList);

                filterRow.forEach(contentNode => {
                    const contentTypeList = contentNode.querySelector(Selectors.filter.fields.type);

                    if (contentTypeList) {
                        contentTypeList.innerHTML = typeList.innerHTML;
                    }
                });

                return filterRow;
            })
            .then(filterRow => {
                this.updateFiltersOptions();

                return filterRow;
            })
            .then(result => {
                pendingPromise.resolve();

                if (Object.keys(filterdata).length !== 0) {
                    result.forEach(filter => {
                        this.addFilter(filter, filterdata.filtertype, filterdata.values,
                            filterdata.jointype, filterdata.filteroptions);
                    });
                }
                return result;
            })
            .catch(Notification.exception);
    }

    async addFilter(filterRow, filterType, initialFilterValues, filterJoin, filterOptions) {
        // Name the filter on the filter row.
        filterRow.dataset.filterType = filterType;

        const filterDataNode = this.getFilterDataSource(filterType);

        // Instantiate the Filter class.
        let Filter = GenericFilter;
        if (filterDataNode.dataset.filterTypeClass) {
            Filter = await import(filterDataNode.dataset.filterTypeClass);
        }
        this.activeFilters[filterType] = new Filter(filterType, this.filterSet, initialFilterValues, filterOptions);
        // Disable the select.
        const typeField = filterRow.querySelector(Selectors.filter.fields.type);
        typeField.value = filterType;
        typeField.disabled = 'disabled';
        // Update the join list.
        this.updateJoinList(filterDataNode.dataset.joinList, filterRow);
        const joinField = filterRow.querySelector(Selectors.filter.fields.join);
        joinField.disabled = false;
        if (isNaN(filterJoin) === false) {
            joinField.value = filterJoin;
        }
        // Update the list of available filter types.
        this.updateFiltersOptions();

        return this.activeFilters[filterType];
    }

    updateJoinList(filterJoinData, filterRow) {
        const regularJoinList = [0, 1, 2];
        const filterJoinList = JSON.parse(filterJoinData);
        // Re-construct join type and list.
        if (filterJoinList.length !== 0) {
            const joinField = filterRow.querySelector(Selectors.filter.fields.join);
            regularJoinList.forEach((join) => {
                if (!filterJoinList.includes(join)) {
                    joinField.options[join].classList.add('hidden');
                    joinField.options[join].disabled = true;
                }
            });
            joinField.options.forEach((element, index) => {
                if (element.disabled) {
                    joinField.options[index] = null;
                }
            });
            if (joinField.options.length === 1) {
                joinField.hidden = true;
            }
        }
    }
}
