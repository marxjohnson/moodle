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
 * Javascript module handling ordering of categories.
 *
 * @module     qbank_managecategories
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Ghaly Marc-Alexandre <marc-alexandreghaly@catalyst-ca.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 */

import Ajax from 'core/ajax';
import Fragment from 'core/fragment';
import Notification from 'core/notification';
import Pending from 'core/pending';
import Templates from 'core/templates';
import Modal from 'core/modal';
import {get_string as getString} from 'core/str';

const SELECTORS = {
    CATEGORY_LIST: '.qbank_managecategories-categorylist',
    MODAL_CATEGORY_ITEM: '.modal_category_item[data-categoryid]',
    CATEGORY_RENDERED: '#categoriesrendered',
    ACTIONABLE_ELEMENT: 'a, [role="button"], [role="menuitem"]',
    SHOW_DESCRIPTION_CHECKBOX: '[name="qbshowdescr"]',
    MOVE_CATEGORY_MENU_ITEM: '[role="menuitem"][data-actiontype="move"]',
    LIST_ITEM: '.qbank_managecategories-item[data-categoryid]',
    CONTEXT: '.qbank_managecategories-categorylist[data-contextid]',
    NOT_DRAGGABLE: '[draggable=false]',
};

/**
 * Sets up sortable list in the column sort order page.
 * @param {number} pagecontextid Context id for fragment.
 */
const setupSortableLists = (pagecontextid) => {
    // Touch events do not have datatransfer property.
    // This variable is used to store id of first element that started the touch events.
    let categoryid;
    // Drag proxy element for touch events.
    let dragProxy;
    // Timeout before dragging starts on touch.
    let touchTimeout;
    // Interval for scrolling the page with touch.
    let touchScrollInterval;

    /**
     * Get touch target at touch point.
     * The target of all touch events is the first element that has been touched at 'touch start'.
     * So we need to infer the target from touch point for 'touch move' and 'touch end' events.
     *
     * @param {Object} e event
     * @returns {any | Element}
     */
    const getTouchTarget = (e) => {
        const target = document.elementFromPoint(
            e.changedTouches[0].clientX,
            e.changedTouches[0].clientY
        );
        // Check if the target is droppable.
        return target.closest(SELECTORS.LIST_ITEM);
    };

    /**
     * Decide if we are before or after the current drop target.
     *
     * Based on the current vertical position of the dragged element relative to the mid-point of the
     * current drop target, decide if dropping will place the dragged element before or after the target.
     *
     * @param {Event} event
     * @param {Element} dropTarget
     * @return {boolean}
     */
    const getInsertBefore = (event, dropTarget) => {

        const clientY = event.changedTouches ? event.changedTouches[0].clientY : event.clientY;

        // Get the current mouse position within the drop target
        const mouseY = clientY - dropTarget.getBoundingClientRect().top;

        // Get the height of the drop target
        const targetHeight = dropTarget.clientHeight;

        // Check if the mouse is over the top half of the drop target
        return mouseY < targetHeight / 2;
    };

    /**
     * Remove any drop target indicators currently displayed.
     */
    const clearTargetIndicators = () => {
        const dropTarget = document.querySelector('.qbank_managecategories-category-droptarget');
        if (dropTarget) {
            dropTarget.classList.remove('qbank_managecategories-category-droptarget');
        }
        const dropTargetBefore = document.querySelector('.qbank_managecategories-category-droptarget-before');
        if (dropTargetBefore) {
            dropTargetBefore.classList.remove('qbank_managecategories-category-droptarget-before');
        }
    };

    /**
     * Handle Drag start
     *
     * This will register the dragged element so it can be moved when dropped.
     *
     * @param {Object} e event
     */
    const handleDragStart = (e) => {
        const target = e.target.closest(SELECTORS.LIST_ITEM);

        // Return if target is not a draggable item.
        if (!target || e.target.closest(SELECTORS.NOT_DRAGGABLE)) {
            return;
        }
        // Save category ID of current moving item.
        // The datatransfer is not used as it is not a property of touch events.
        categoryid = target.dataset?.categoryid;

        if (e.type === 'touchstart') {
            touchTimeout = undefined;
            makeDragProxy(e, target);
        }
    };

    /**
     * Touch events don't create a drag proxy, so create one manually.
     *
     * @param {Event} event The touchstart event.
     * @param {Element} element The element being dragged, to create the proxy for.
     */
    const makeDragProxy = (event, element) => {
        if (dragProxy) {
            dragProxy.remove();
            dragProxy = null;
        }
        dragProxy = document.createElement('div');
        dragProxy.id = 'qbank_managecategories-dragproxy';
        dragProxy.classList.add('editing');
        dragProxy.style.width = element.getBoundingClientRect().width + 'px';
        dragProxy.style.height = element.getBoundingClientRect().height + 'px';
        dragProxy.style.top = Math.round(event.touches[0].clientY) + 'px';
        dragProxy.style.left = Math.round(event.touches[0].clientX) + 'px';
        dragProxy.innerHTML = element.innerHTML;
        document.body.appendChild(dragProxy);
    };

    /**
     * Handle Drag move
     *
     * Keep track of the current drop target, and move the drop indicator to the appropriate position.
     *
     * @param {Event} e event
     */
    const handleDrag = (e) => {
        let target;
        if (e.type === 'touchmove') {
            if (typeof touchTimeout === 'number') {
                clearTimeout(touchTimeout);
                touchTimeout = undefined;
                return;
            }
            target = getTouchTarget(e);
            touchMoveScroll(e);
            if (dragProxy) {
                dragProxy.style.top = Math.round(e.changedTouches[0].clientY) + 'px';
                dragProxy.style.left = Math.round(e.changedTouches[0].clientX) + 'px';
            }
        } else {
            target = e.target.closest(SELECTORS.LIST_ITEM);
        }
        // Return if target is not a droppable item or there is no sourceid.
        if (!target || !categoryid) {
            return;
        }

        // Return if target is a child of the dragged category, so we don't indicate this as a valid drop target.
        if (target.closest(`[data-categoryid="${categoryid}"]`)) {
            return;
        }

        const insertBefore = getInsertBefore(e, target);

        // Remove all target indicators.
        clearTargetIndicators();

        if (insertBefore && target === target.parentElement.firstElementChild) {
            // Show the indicator at the top of the list.
            target.classList.add('qbank_managecategories-category-droptarget-before');
            return;
        }

        if (!insertBefore && target === target.parentElement.lastElementChild) {
            // Show the indicator at the bottom of the list.
            target.classList.add('qbank_managecategories-category-droptarget');
            return;
        }

        const insertTarget = insertBefore ? target : target.nextElementSibling;

        // Show the indicator at the top of the target element.
        if (insertTarget) {
            insertTarget.classList.add('qbank_managecategories-category-droptarget-before');
        }
    };

    /**
     * When an item is dragged out of a list, remove the current drag indicators.
     *
     * @param {Event} e
     */
    const handleDragLeaveList = (e) => {
        if (e.target.classList.contains('qbank_managecategories-categorylist')) {
            clearTargetIndicators();
        }
    };

    /**
     * Handle Drag end
     *
     * When an item is dropped, find the target element and re-order the categories.
     * If the item is dropped at the top or bottom of a list, it will be moved before the first or after
     * the last item, respectively. Otherwise, if it is dropped on the top half of an item, it will be moved
     * before that item, and if on the bottom half, after that item.
     *
     * @param {Event} e event
     */
    const handleDragEnd = (e) => {
        let target;
        const pending = new Pending('qbank_managecategories/dragend');
        clearTargetIndicators();
        if (e.type === 'touchend') {
            if (typeof touchScrollInterval === 'number') {
                // Stop scrolling.
                window.clearInterval(touchScrollInterval);
                touchScrollInterval = undefined;
            }
            if (typeof touchTimeout === 'number') {
                // Cancel waiting on a long touch to start dragging.
                window.clearTimeout(touchTimeout);
                touchTimeout = undefined;
                return;
            }
            if (dragProxy) {
                dragProxy.remove();
                dragProxy = null;
            }
            target = getTouchTarget(e);
        } else {
            target = e.target.closest(SELECTORS.LIST_ITEM);
        }

        if (!target) {
            // Check if we're at the top or bottom of the list, and target the first or last element accordingly.
            const listTarget = e.target.closest(SELECTORS.CATEGORY_LIST);
            if (listTarget) {
                if (getInsertBefore(e, listTarget)) {
                    target = listTarget.firstElementChild;
                } else {
                    target = listTarget.lastElementChild;
                }
            }
        }

        // Return if target is not a droppable item or there is no sourceid.
        if (!target || !categoryid) {
            return;
        }

        // Get list item whose id is the same as current moving category id.
        const source = document.getElementById(`category-${categoryid}`);
        if (!source) {
            return;
        }

        e.preventDefault();

        // Reset sourceid for touch event.
        categoryid = null;

        let targetCategory;
        const insertBefore = getInsertBefore(e, target);
        let before = insertBefore;
        if (insertBefore && target === target.parentElement.firstElementChild) {
            targetCategory = target.dataset.categoryid;
            // Insert the category at the top of the list.
            target.closest(SELECTORS.CATEGORY_LIST).insertBefore(source, target);
        } else if (!insertBefore && target === target.parentElement.lastElementChild) {
            targetCategory = target.dataset.categoryid;
            // Insert the category at the end of the list.
            target.closest(SELECTORS.CATEGORY_LIST).appendChild(source);
        } else {
            const insertTarget = insertBefore ? target : target.nextElementSibling;
            targetCategory = insertTarget.dataset.categoryid;
            before = true; // We always insert before the selected target.

            // Move the source category to its new position.
            target.closest(SELECTORS.CATEGORY_LIST).insertBefore(source, insertTarget);
        }

        // Moved category.
        const originCategory = source.dataset.categoryid;

        // Insert the category after the target category
        setCatOrder(originCategory, targetCategory, before, pagecontextid, pending);
    };

    /**
     * If something is dragged near the top or bottom of the screen by touch, scroll until it is moved away.
     *
     * @param {Event} e
     */
    const touchMoveScroll = (e) => {
        if (!categoryid) {
            return;
        }
        const threshold = 50;
        const timeout = 5;
        const intervalRunning = typeof touchScrollInterval !== 'undefined';
        if (e.changedTouches[0].clientY < threshold && !intervalRunning) {
            touchScrollInterval = window.setInterval(
                () => {
                    window.scrollBy(0, -1);
                },
                timeout
            );
        } else if (window.innerHeight - e.changedTouches[0].clientY < threshold && !intervalRunning) {
            touchScrollInterval = window.setInterval(
                () => {
                    window.scrollBy(0, 1);
                },
                timeout
            );
        } else if (intervalRunning) {
            window.clearInterval(touchScrollInterval);
            touchScrollInterval = undefined;
        }
    };

    /**
     * Allow drop
     *
     * This allows elements to be used as a drop target.
     *
     * @param {Object} e event
     */
    const allowDrop = (e) => {
        e.preventDefault();
    };

    const categoryRoot = document.getElementById('categoriesrendered');
    categoryRoot.addEventListener('dragover', allowDrop);
    categoryRoot.addEventListener('dragenter', allowDrop);
    categoryRoot.addEventListener('dragstart', handleDragStart);
    categoryRoot.addEventListener('dragenter', handleDrag);
    categoryRoot.addEventListener('dragleave', handleDragLeaveList);
    categoryRoot.addEventListener('drop', handleDragEnd);
    categoryRoot.addEventListener('touchmove', handleDrag, false);
    categoryRoot.addEventListener('touchend', handleDragEnd, false);
    categoryRoot.addEventListener('touchstart', (e) => {
        // Delay before we start dragging on touch. This avoids accidental dragging when trying to scroll.
        touchTimeout = window.setTimeout(() => handleDragStart(e), 500);
    }, false);

    document.querySelectorAll(SELECTORS.LIST_ITEM + ' ' + SELECTORS.ACTIONABLE_ELEMENT).forEach(element => {
        // Prevent interactive elements inside a list item from being dragged.
        element.setAttribute('draggable', false);
    });
};

/**
 * Call categories fragment.
 *
 * @param {number} contextid String containing new ordered categories.
 * @returns {Promise}
 */
const getCategoriesFragment = (contextid) => {
    let params = {
        url: location.href,
    };
    return Fragment.loadFragment('qbank_managecategories', 'categories', contextid, params);
};

/**
 * Call external function update_category_order - inserts the updated column in the question_categories table.
 *
 * @param {number} originCategory Category which was dragged.
 * @param {number} targetCategory Context where category was dropped.
 * @param {boolean} isBeforeTarget True if the category was moved before the target category.
 * @param {number} pageContextId Context from which the category was dragged.
 * @param {Pending} pendingPromise Optional pending promise, will be resolved once the page fragment has been re-rendered.
 */
const setCatOrder = (originCategory, targetCategory, isBeforeTarget, pageContextId, pendingPromise = null) => {
    const call = {
        methodname: 'qbank_managecategories_update_category_order',
        args: {
            categoryid: originCategory,
            targetcategoryid: targetCategory,
            isbeforetarget: isBeforeTarget,
        }
    };
    Ajax.call([call])[0]
        .then(() => {
            return getCategoriesFragment(pageContextId);
        })
        .catch(error => {
            Notification.addNotification({
                message: error.message,
                type: 'error',
            });
            document.getElementsByClassName('alert-danger')[0]?.scrollIntoView();
            return getCategoriesFragment(pageContextId);
        })
        .then((html, js) => {
            Templates.replaceNode('#categoriesrendered', html, js);
            if (pendingPromise) {
                pendingPromise.resolve();
            }
            return;
        })
        .catch(error => {
            if (pendingPromise) {
                pendingPromise.reject(error);
            }
            Notification.exception(error);
        });
};


/**
 * Method to add listener on category arrow - descendants.
 *
 * @param {number} pageContextId Context id for fragment.
 */
const categoryParentListener = (pageContextId) => {
    document.querySelector(SELECTORS.CATEGORY_RENDERED).addEventListener('click', e => {
        // Ignore if there is no categories containers.
        if (!e.target.closest(SELECTORS.CATEGORY_RENDERED)) {
            return;
        }

        // Ignore if there is no action icon.
        const actionIcon = e.target.closest('.action-icon');
        if (!actionIcon) {
            return;
        }

        e.preventDefault();

        // Retrieve data from action icon.
        const data = actionIcon.dataset;

        let call;
        const targetParent = document.querySelector(`#category-${data.tocategory}`);
        if (!targetParent) {
            // Moving to the top level. Move after the current parent.
            const currentParent = actionIcon.closest(SELECTORS.CATEGORY_LIST).closest(SELECTORS.LIST_ITEM);
            call = {
                methodname: 'qbank_managecategories_update_category_order',
                args: {
                    categoryid: data.tomove,
                    targetcategoryid: currentParent.dataset.categoryid,
                    isbeforetarget: false,
                }
            };
        } else {
            const childList = targetParent.querySelector(SELECTORS.CATEGORY_LIST);
            if (childList) {
                // The new parent already has children. Move the category to the end of its list.
                call = {
                    methodname: 'qbank_managecategories_update_category_order',
                    args: {
                        categoryid: data.tomove,
                        targetcategoryid: childList.lastElementChild.dataset.categoryid,
                        isbeforetarget: false,
                    }
                };
            } else {
                // Move the category to the new parent.
                call = {
                    methodname: 'qbank_managecategories_move_category_to_new_parent',
                    args: {
                        categoryid: data.tomove,
                        newparentcategoryid: data.tocategory,
                    }
                };
            }
        }

        Ajax.call([call])[0]
            .then(() => getCategoriesFragment(pageContextId))
            .then((html, js) => {
                Templates.replaceNode(SELECTORS.CATEGORY_RENDERED, html, js);
                return;
            })
            .catch(Notification.exception);
    });
};

/**
 * Sets events listener for checkbox ticking change.
 */
const setupShowDescriptionCheckbox = () => {
    document.addEventListener('click', e => {
        const checkbox = e.target.closest(SELECTORS.SHOW_DESCRIPTION_CHECKBOX);
        if (!checkbox) {
            return;
        }
        checkbox.form.submit();
    });
};

const createMoveCategoryList = (item, movingCategory) => {
    const categories = [];
    if (item.children) {
        item.children.forEach(category => {
            let child = {
                categoryid: category.dataset.categoryid,
                categoryname: category.dataset.categoryname,
                categories: null,
                firstchild: category === item.children[0],
                current: category.dataset.categoryid === movingCategory,
            };

            const childList = category.querySelector(SELECTORS.CATEGORY_LIST);
            if (childList) {
                child.categories = createMoveCategoryList(childList, movingCategory);
            }
            categories.push(child);
        });
    }
    return categories;
};

/**
 * Sets events listener for move category using dragdrop icon.
 * @param {number} pagecontextid Context id to get all relevant categories.
 */
const setUpMoveMenuItem = (pagecontextid) => {
    document.querySelector(SELECTORS.CATEGORY_RENDERED).addEventListener('click', async(e) => {
        // Return if it is not menu item.
        const item = e.target.closest(SELECTORS.MOVE_CATEGORY_MENU_ITEM);
        if (!item) {
            return;
        }
        // Return if it is disabled.
        if (item.getAttribute('aria-disabled')) {
            return;
        }

        // Prevent addition click on the item.
        item.setAttribute('aria-disabled', true);

        let moveList = {contexts: []};
        const contexts = document.querySelectorAll(SELECTORS.CONTEXT);
        contexts.forEach(context => {
            const moveContext = {
                contextname: context.dataset.contextname,
                categories: [],
                hascategories: false,
            };
            moveContext.categories = createMoveCategoryList(context, item.dataset.categoryid);
            moveContext.hascategories = moveContext.categories.length > 0;
            moveList.contexts.push(moveContext);
        });

        const modal = await Modal.create({
            title: getString('movecategory', 'qbank_managecategories', item.dataset.categoryname),
            body: Templates.render('qbank_managecategories/move_context_list', moveList),
            footer: '',
            show: true,
            large: true,
        });
        // Show modal and add click event for list item.
        modal.getBody()[0].addEventListener('click', e => {
            const target = e.target.closest(SELECTORS.MODAL_CATEGORY_ITEM);
            if (!target) {
                return;
            }
            const pending = new Pending('qbank_managecategories/modal');
            setCatOrder(item.dataset.categoryid, target.dataset.categoryid, target.dataset.before, pagecontextid, pending);
            modal.destroy();
        });
        item.setAttribute('aria-disabled', false);
    });
};

export const init = (pagecontextid) => {
    categoryParentListener(pagecontextid);
    setupSortableLists(pagecontextid);
    setupShowDescriptionCheckbox();
    setUpMoveMenuItem(pagecontextid);
};
