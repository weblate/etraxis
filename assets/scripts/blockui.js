//----------------------------------------------------------------------
//
//  Copyright (C) 2017-2023 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

const dialogId = '__etraxis_blockui';

/**
 * @type {number} Number of blocking calls.
 */
let blocks = 0;

/**
 * @type {Array<string>} Stack of previous blocking messages.
 */
const messageStack = [];

/**
 * Blocks UI from user interaction.
 *
 * @param {null|string} message Optional message
 */
export const block = (message = null) => {
    if (blocks++ === 0) {
        // This is a first time block.
        const template = `
            <dialog id="${dialogId}" class="blockui">
                <p class="has-text-centered">${message ?? window.i18n['text.please_wait']}</p>
            </dialog>`;

        document.querySelector('body').insertAdjacentHTML('beforeend', template);

        /** @type {Node} */
        const modal = document.getElementById(dialogId);

        modal.addEventListener('cancel', (event) => event.preventDefault());

        modal.showModal();
        modal.blur();
    } else {
        // This is a repeated block - push the current message to the stack and replace it with the new one.
        const element = document.querySelector(`#${dialogId} p`);
        messageStack.push(element.innerHTML);
        element.innerHTML = message ?? window.i18n['text.please_wait'];
    }
};

/**
 * Unblocks UI.
 */
export const unblock = () => {
    if (--blocks === 0) {
        // This was a last block.
        const modal = document.getElementById(dialogId);
        modal.close();
        modal.parentNode.removeChild(modal);
    } else {
        // There is a previous block there - restore its message from the stack.
        const element = document.querySelector(`#${dialogId} p`);
        element.innerHTML = messageStack.pop();
    }
};
