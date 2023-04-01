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

/**
 * @type {number} Number of blocking calls.
 */
let blocks = 0;

/**
 * Blocks UI from user interaction.
 *
 * @param {null|string} message Optional message
 */
export const block = (message = null) => {
    if (blocks++ === 0) {
        const id = '__etraxis_blockui';

        const template = `
            <dialog id="${id}" class="blockui">
                <p class="has-text-centered">${message ?? window.i18n['text.please_wait']}</p>
            </dialog>`;

        if (!document.getElementById(id)) {
            document.querySelector('body').insertAdjacentHTML('beforeend', template);

            /** @type {Node} */
            const modal = document.getElementById(id);

            modal.addEventListener('cancel', (event) => event.preventDefault());
            modal.addEventListener('close', () => modal.parentNode.removeChild(modal));

            modal.showModal();
        }
    }
};

/**
 * Unblocks UI.
 */
export const unblock = () => {
    if (--blocks === 0) {
        const modal = document.getElementById('__etraxis_blockui');

        if (modal) {
            modal.close();
        }
    }
};
