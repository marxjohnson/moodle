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
 * Question count badge with asynchronous loading.
 *
 * @module     core_question/questioncount
 * @copyright  2024 Catalyst IT Europe Ltd.
 * @author     Mark Johnson <mark.johnson@catalyst-eu.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import {getString} from 'core/str';
import Fetch from 'core/fetch';

const SELECTORS = {
    COUNT_CONTAINER: (cmid) => `.questioncount[data-cmid='${cmid}']`,
    LOADING: '.loading',
    COUNT_BADGE: '.badge',
};

export const fetchCount = async(cmid) => {
    const countContainer = document.querySelector(SELECTORS.COUNT_CONTAINER(cmid));
    const countBadge = countContainer.querySelector(SELECTORS.COUNT_BADGE);
    const loading = countContainer.querySelector(SELECTORS.LOADING);
    loading.classList.remove('d-none');
    const endpoint = ['bank', cmid, 'question_count'];
    const response = await Fetch.performGet('core_question', endpoint.join('/'));
    const questionCount = await response.json();
    countBadge.innerText = await getString('questioncount', 'question', questionCount.count);
    loading.classList.add('d-none');
    countBadge.classList.remove('d-none');
};
