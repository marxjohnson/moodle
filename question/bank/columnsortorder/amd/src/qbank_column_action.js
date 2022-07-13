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
 * Setting up resize, pin, move, and show/hide actions for the specified table
 * Columns on the same header should have same data id attribute as to identify if a column belong to a header.
 *
 *
 * @module     qbank_columnsortorder/qbank_column_action
 * @copyright  2022 Catalyst IT Australia Pty Ltd
 * @author     Nathan Nguyen <nathannguyen@catalyst-ca.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {exception as displayException} from 'core/notification';
import {get_string as getString} from 'core/str';
import * as repository from 'qbank_columnsortorder/repository';
import jQuery from 'jquery';
import ModalEvents from 'core/modal_events';
import ModalFactory from 'core/modal_factory';
import Notification from 'core/notification';
import Templates from 'core/templates';
import SortableList from 'core/sortable_list';

/** The table that we will add action */
let table;

/** Data attribute used to identify each colum */
let dataIdAttribute;

/** Data attribute used to display name of a column */
let dataNameAttribute;

/** To track mouse event on a table header */
let currentHeader;

/** Current mouse x postion, to track mouse event on a table header */
let currentX;

const SELECTORS = {
    MOVE_HANDLE: '[data-action="move"]',
    RESIZE_HANDLE: '[data-action="resize"]',
    tableHeader: identifier => `th[data-${dataIdAttribute}="${identifier.replace(/["\\]/g, '\\$&')}"]`,
    tableColumn: identifier => `td[data-${dataIdAttribute}="${identifier.replace(/["\\]/g, '\\$&')}"]`,
    tableHeaderSection: tableid => `#${tableid} thead tr`,
};

/**
 * Add handle
 * @param {Object} context data for each handle.
 * @param {Element} container container cthat will hold a action icon
 * @returns {Promise}
 */
const addHandle = (context, container) => {
    return Templates.renderForPromise('qbank_columnsortorder/action_handle', context)
        .then(({html, js}) => {
            Templates.appendNodeContents(container, html, js);
            return container;
        });
};

/**
 * Gets the newly reordered columns to display in the question bank view.
 * @returns {Array}
 */
const getColumnOrder = () => {
    const tableHeaders = table.querySelectorAll("th");
    const columns = Array.from(tableHeaders).map(column => column.dataset[dataIdAttribute]);
    return columns.filter((value, index) => columns.indexOf(value) === index);
};

/**
 * Set up move handle
 * @param {String} handleContainer container class that will hold the move icon.
 * @param {String} component The component to save preferences against.
 */
const setUpMoveHandle = (handleContainer, component = '') => {
    // Add "move icon" for each header.
    const tableHeaders = table.querySelectorAll("th:not(.checkbox)");
    tableHeaders.forEach(async(header) => {
        const context = {
            action: "move",
            target: header.dataset[dataIdAttribute],
            title: await getString('movecolumn', 'qbank_columnsortorder', header.dataset[dataNameAttribute]),
            pixicon: "i/dragdrop",
            pixcomponent: "core",
            popup: true
        };
        const container = header.querySelector(handleContainer);
        addHandle(context, container).catch(ex => displayException(ex));
    });

    const headerSectionSelector = SELECTORS.tableHeaderSection(table.id);
    const headerSection = jQuery(headerSectionSelector);

    // Implement drag and drop.
    new SortableList(headerSectionSelector, {
        moveHandlerSelector: SELECTORS.MOVE_HANDLE,
        isHorizontal: true
    });

    headerSection.on(SortableList.EVENTS.DRAGSTART, event => {
        if (event.target.classList.contains('header')) {
            event.target.classList.add('active');
        }
    });

    headerSection.on(SortableList.EVENTS.DRAGEND, event => {
        if (event.target.classList.contains('active')) {
            event.target.classList.remove('active');
        }
    });

    headerSection.on(SortableList.EVENTS.DROP, event => {
        // Current header.
        const header = event.target;
        // Find the previous sibling of the header, which will be used when moving columns.
        const insertAfter = header.previousElementSibling;

        // Move columns.
        const columns = table.querySelectorAll(SELECTORS.tableColumn(header.dataset[dataIdAttribute]));
        columns.forEach(column => {
            const row = column.parentElement;
            if (insertAfter) {
                // Find the column to insert after.
                const insertAfterColumn = row.querySelector(SELECTORS.tableColumn(insertAfter.dataset[dataIdAttribute]));
                // Insert the column.
                insertAfterColumn.after(column);
            } else {
                // Insert as the first child (first column in the table).
                row.insertBefore(column, row.firstChild);
            }
        });

        // Persist column order.
        repository.setColumnbankOrder(getColumnOrder(), component).catch(Notification.exception);
    });

};

/**
 * Get the size of each header.
 *
 * @return {Array}
 */
const saveColumnSizes = () => {
    let columnSizes = [];
    const tableHeaders = table.querySelectorAll("th");
    tableHeaders.forEach(header => {
        // Only get the width set via style attribute (set by pin or move action).
        let size = {
            column: header.dataset[dataIdAttribute],
            width: header.style.width
        };
        columnSizes.push(size);
    });
    return JSON.stringify(columnSizes);
};

/**
 * Show a modal containing a number input for changing a column width without click-and-drag.
 *
 * @param {Element} currentHeader The header element that is being resized.
 * @param {String} component The component to save preferences against.
 * @returns {Promise<void>}
 */
const showResizeModal = async(currentHeader, component = '') => {

    const initialWidth = currentHeader.offsetWidth;

    const modal = await ModalFactory.create({
        title: getString('resizecolumn', 'qbank_columnsortorder', currentHeader.textContent),
        type: ModalFactory.types.SAVE_CANCEL,
        body: Templates.render('qbank_columnsortorder/resize_modal', {})
    });
    const root = modal.getRoot();
    root.on(ModalEvents.cancel, () => {
        currentHeader.style.width = initialWidth + 'px';
    });
    root.on(ModalEvents.save, () => {
        repository.setColumnSize(saveColumnSizes(), component).catch(Notification.exception);
    });
    modal.show();

    const body = await modal.bodyPromise;
    const input = body.get(0).querySelector('input');
    input.value = initialWidth;

    input.addEventListener('change', e => {
        const newWidth = e.target.value;
        currentHeader.style.width = newWidth + 'px';
    });
};

/**
 * Set up resize handle
 * @param {String} handleContainer container class that will hold the move icon.
 * @param {String} component The component to save preferences against.
 */
const setUpResizeHandle = (handleContainer, component) => {
    // Add "move icon" for each header.
    const tableHeaders = table.querySelectorAll("th:not(.checkbox)");
    tableHeaders.forEach(async(header) => {
        const context = {
            action: "resize",
            target: header.dataset[dataIdAttribute],
            title: await getString('resizecolumn', 'qbank_columnsortorder', header.dataset[dataNameAttribute]),
            pixicon: 'resizehandle',
            pixcomponent: 'qbank_columnsortorder',
            popup: true
        };
        const container = header.querySelector(handleContainer);
        addHandle(context, container);
    });

    let moveTracker = false;
    let currentResizeHandle = null;
    // Start mouse event on headers.
    table.addEventListener('mousedown', e => {
        currentResizeHandle = e.target.closest(SELECTORS.RESIZE_HANDLE);
        // Return if it is not ' resize' button.
        if (!currentResizeHandle) {
            return;
        }
        // Save current position.
        currentX = e.pageX;
        // Find the header.
        const target = currentResizeHandle.dataset.target;
        currentHeader = table.querySelector(SELECTORS.tableHeader(target));
        moveTracker = false;
    });

    // Resize column as the mouse move.
    document.addEventListener('mousemove', e => {
        if (!currentHeader || !currentResizeHandle || currentX === 0) {
            return;
        }

        document.getSelection().removeAllRanges();

        // Offset.
        const offset = e.pageX - currentX;
        currentX = e.pageX;
        const newWidth = currentHeader.offsetWidth + offset;
        currentHeader.style.width = newWidth + 'px';
        moveTracker = true;
    });

    // Set new size when mouse is up.
    document.addEventListener('mouseup', () => {
        if (!currentHeader || !currentResizeHandle || currentX === 0) {
            return;
        }
        if (moveTracker) {
            // If the mouse moved, we are changing the size by drag, so save the change.
            repository.setColumnSize(saveColumnSizes(), component).catch(Notification.exception);
        } else {
            // If the mouse didn't move, display a modal to change the size using a form.
            showResizeModal(currentHeader, component);
        }
        currentHeader = null;
        currentResizeHandle = null;
        currentX = 0;
        moveTracker = false;
    });

    table.addEventListener('keypress', e => {
        if (e.key !== 'Enter' && e.key !== ' ') {
            return;
        }
        const resizeHandle = e.target.closest(SELECTORS.RESIZE_HANDLE);
        // Return if it is not 'resize' button.
        if (!resizeHandle) {
            return;
        }
        e.preventDefault();
        e.stopPropagation();
        const target = resizeHandle.dataset.target;
        const currentHeader = table.querySelector(SELECTORS.tableHeader(target));
        showResizeModal(currentHeader, component);
    });
};

/**
 * Set up hide/show dropdown
 * @param {String} dropdownContainer container class that will hold the hide/show dropdown.
 * @param {String} component The component to save preferences against.
 */
const setUpHideShowDropdown = async(dropdownContainer, component = '') => {
    const container = document.querySelector(dropdownContainer);
    let currentHiddenColumns = table.dataset.hiddencolumns;
    if (currentHiddenColumns) {
        currentHiddenColumns = JSON.parse(currentHiddenColumns);
    }
    let context = {
        columns: [],
        text: await getString('showhidecolumn', 'qbank_columnsortorder'),
        id: "showhidecolumn"
    };
    const tableHeaders = table.querySelectorAll("th:not(.checkbox)");
    tableHeaders.forEach(header => {
        const visible = !currentHiddenColumns || !currentHiddenColumns.includes(header.dataset[dataIdAttribute]);
        // Data for checkbox.
        const column = {
            id: header.dataset[dataIdAttribute],
            name: header.dataset[dataNameAttribute],
            checked: visible
        };
        context.columns.push(column);
    });

    return Templates.renderForPromise('qbank_columnsortorder/showhide_dropdown', context)
        .then(({html, js}) => {
            Templates.appendNodeContents(container, html, js);
            addDropdownEventListeners(container, component);
            return container;
        });
};

const toggleColumnHandler = (checkbox, container, component = '') => {
    const target = checkbox.value;
    const header = table.querySelector(SELECTORS.tableHeader(target));
    if (checkbox.checked === true) {
        // Show header.
        header.style.display = "";
        // Show column.
        const columns = table.querySelectorAll(SELECTORS.tableColumn(target));
        columns.forEach(column => {
            column.style.display = "";
        });
    } else {
        // Hide header.
        header.style.display = "none";
        // Hide column.
        const columns = table.querySelectorAll(SELECTORS.tableColumn(target));
        columns.forEach(column => {
            column.style.display = "none";
        });
    }

    const checkboxes = container.querySelectorAll('input[type=checkbox]');
    const hiddenColumns = [...checkboxes].filter(checkbox => !checkbox.checked).map(checkbox => checkbox.value);
    repository.setHiddenColumns(hiddenColumns, component).catch(Notification.exception);
};

/**
 * Add event listener for drop down item
 * @param {Element} container dropdown container.
 * @param {String} component The component to save preferences against.
 */
const addDropdownEventListeners = (container, component = '') => {
    // Click event when click on an item.
    container.addEventListener('click', event => {
        const item = event.target.closest('.dropdown-item');
        if (!item) {
            return;
        }
        toggleColumnHandler(item.querySelector('input[type=checkbox]'), container, component);
    });
    // Keypress inside the list.
    container.addEventListener('keydown', event => {
        const item = event.target.closest('.dropdown-item');
        if (event.key === 'Enter') {
            // Toggle current item.
            if (!item) {
                return;
            }
            const checkbox = item.querySelector('input[type=checkbox]');
            checkbox.checked = !checkbox.checked;
            toggleColumnHandler(checkbox, container, component);
        } else if (event.key === 'ArrowUp' || event.key === 'ArrowDown') {
            // Up or down - move up and down the list.
            const item = event.target.closest('.dropdown-item');
            let target = event.currentTarget.querySelector('.dropdown-item:first-of-type');
            if (item) {
                if (event.key === 'ArrowUp') {
                    target = item.previousElementSibling ?? event.currentTarget.querySelector('.dropdown-item:last-of-type');
                } else {
                    target = item.nextElementSibling ?? event.currentTarget.querySelector('.dropdown-item:first-of-type');
                }
            }
            target.querySelector('input[type=checkbox]').focus();
            event.preventDefault();
        } else {
            // Ignore all other keys.
            return;
        }
    });
};

/**
 * Current hidden columns
 */
const setUpCurrentHiddenColumns = () => {
    let currentHiddenColumns = table.dataset.hiddencolumns;
    if (currentHiddenColumns) {
        currentHiddenColumns = JSON.parse(currentHiddenColumns);
    }
    if (currentHiddenColumns.length > 0) {
        currentHiddenColumns.forEach(pluginname => {
            if (!pluginname) {
                return;
            }
            const header = table.querySelector(SELECTORS.tableHeader(pluginname));
            header.style.display = "none";
            const cells = table.querySelectorAll(SELECTORS.tableColumn(pluginname));
            cells.forEach(cell => {
                cell.style.display = "none";
            });
        });
    }
};

/**
 * Set up initial column sizes.
 *
 * If there is a saved column size for the column, use that. Otherwise, set it to the current width of the column on screen.
 */
const setUpCurrentColumnSizes = () => {
    const currentColumnSizes = table.dataset.colsize;
    let decodedSizes = [];
    if (currentColumnSizes) {
        decodedSizes = JSON.parse(currentColumnSizes);
        if (!Array.isArray(decodedSizes)) {
            decodedSizes = [];
        }
    }
    const headers = table.querySelectorAll('th');
    headers.forEach(header => {
        const colSize = decodedSizes.find(colSize => colSize.column === header.dataset.pluginname);
        if (colSize && colSize.width !== '') {
            header.style.width = colSize.width;
        } else {
            header.style.width = header.offsetWidth + 'px';
        }
    });
    // Set the width of the table to min-content, so that it can grow beyond the page width, and cells can shrink below their
    // content width.
    table.style.width = 'min-content';
};

/**
 * Initialize module
 *
 * @param {String} id unique id for columns.
 * @return {Boolean} True if the table was set up, false if it was already set up.
 */
const setUpTable = (id) => {
    table = document.getElementById(id);

    // Check if the table is already setup.
    if (table.dataset.setup == 'true') {
        return false;
    }

    dataIdAttribute = 'pluginname';
    dataNameAttribute = 'name';

    // Add class to each column as to identify them later.
    const tableHeaders = table.querySelectorAll("th");
    const rows = table.querySelectorAll("tr");
    rows.forEach(row => {
        const columns = row.querySelectorAll("td");
        for (let i = 0; i < columns.length; i++) {
            columns[i].dataset[dataIdAttribute] = tableHeaders[i].dataset[dataIdAttribute];
        }
    });

    // Prevent from setting up the table again.
    table.dataset.setup = 'true';
    return true;
};

/**
 * Initialise module.
 *
 * Set up the table based on the current settings, and add controls to each column header if
 * editing is enabled.
 *
 * @param {String} tableId ID of the question bank table
 * @param {Boolean} isEditing Should we show the move, resize and pin controls?
 * @param {String} component Component to save user preferences to (empty will save changes to admin settings)
 */
export const init = (tableId, isEditing, component = '') => {
    if (!setUpTable(tableId)) {
        // Table has already been set up, nothing more to do.
        return;
    }

    setUpCurrentHiddenColumns();
    setUpCurrentColumnSizes();

    if (isEditing) {
        setUpHideShowDropdown("#show-hide-dropdown", component).catch(Notification.exception);
        setUpMoveHandle(".move-handle", component);
        setUpResizeHandle(".resize-handle", component);
    }
};
