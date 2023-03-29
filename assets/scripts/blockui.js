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
 * Blocks UI from user interaction.
 *
 * @param {null|string} message Optional message
 */
export const block = (message = null) => {
    const id = '__etraxis_blockui';

    const template = `
        <dialog id="${id}" class="blockui">
            <p class="has-text-centered">${message ?? window.i18n['text.please_wait']}</p>
        </dialog>`;

    if (!document.getElementById(id)) {
        document.querySelector('body').insertAdjacentHTML('beforeend', template);

        let modal = document.getElementById(id);

        modal.addEventListener('cancel', (event) => event.preventDefault());
        modal.addEventListener('close', () => modal.parentNode.removeChild(modal));

        modal.showModal();
    }
};

/**
 * Unblocks UI.
 */
export const unblock = () => {
    let modal = document.getElementById('__etraxis_blockui');

    if (modal) {
        modal.close();
    }
};
