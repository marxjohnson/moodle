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
 * Status column selector js.
 *
 * @module     qbank_editquestion/question_status
 * @copyright  2021 Catalyst IT Australia Pty Ltd
 * @author     Safat Shahin <safatshahin@catalyst-au.net>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';
import Notification from 'core/notification';

/**
 * Set the question status.
 *
 * @param {Number} questionId The question id.
 * @param {String} status The updated question status.
 * @return {Array} The modified question status
 */
const setQuestionStatus = (questionId, status) => Ajax.call([{
    methodname: 'qbank_editquestion_set_status',
    args: {
        questionid: questionId,
        status: status
    }
}])[0];

let changeListener = null;

let recoverListener = null;

const SELECTORS = {
    QUESTIONS_CONTAINER: '#questionscontainer',
    STATUS_DROPDOWN: "[name='question_status_dropdown']",
    RECOVER_BUTTON: '.recoverquestion',
};

/**
 * Entrypoint of the js.
 *
 * @method init
 */
export const init = () => {
    const questionsContainer = document.querySelector(SELECTORS.QUESTIONS_CONTAINER);
    if (!questionsContainer) {
        return;
    }
    if (changeListener === null) {
        changeListener = questionsContainer.addEventListener('change', (e) => {
            const statusDropDown = e.target.closest(SELECTORS.STATUS_DROPDOWN);
            if (statusDropDown) {
                e.preventDefault();
                const questionId = parseInt(statusDropDown.id.split('-')[1]);
                const questionStatus = e.target.value;
                setQuestionStatus(questionId, questionStatus)
                    .then((response) => {
                        if (response.error) {
                            Notification.addNotification({
                                type: 'error',
                                message: response.error
                            });
                        }
                        return;
                    }).catch();
            }
        });
    }
    if (recoverListener === null) {
        recoverListener = questionsContainer.addEventListener('click', (e) => {
            const recoverButton = e.target.closest(SELECTORS.RECOVER_BUTTON);
            if (recoverButton) {
                e.preventDefault();
                const questionId = parseInt(recoverButton.dataset.questionid);
                setQuestionStatus(questionId, 'ready')
                    .then((response) => {
                        if (response.error) {
                            Notification.addNotification({
                                type: 'error',
                                message: response.error
                            });
                        } else {
                            window.location.reload();
                        }
                    }).catch();
            }
        });
    }
};
