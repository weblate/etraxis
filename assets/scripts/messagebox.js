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

import generateUid from "@utilities/uid";

/**
 * Displays error message box (alternative to JavaScript "alert").
 *
 * @param {string} message Error message
 *
 * @return {Promise} Promise is resolved when the message box is closed
 */
export const alert = (message) => messageBox(i18n["text.error"], message, "fa-times-circle", "has-text-danger", true);

/**
 * Displays informational message box (alternative to JavaScript "alert").
 *
 * @param {string} message Informational message
 *
 * @return {Promise} Promise is resolved when the message box is closed
 */
export const info = (message) => messageBox("eTraxis", message, "fa-info-circle", "has-text-info", true);

/**
 * Displays confirmation message box (alternative to JavaScript "confirm").
 *
 * @param {string} message Confirmation message
 *
 * @return {Promise} Promise is resolved when the message box is closed with confirmation
 */
export const confirm = (message) => messageBox("eTraxis", message, "fa-question-circle", "has-text-info", false);

/**
 * @internal Displays modal message box.
 *
 * @param {string}  header       Text of the message box header
 * @param {string}  message      Text of the message box body
 * @param {string}  iconGlyph    FontAwesome icon class
 * @param {string}  iconClass    Additional class to apply to the icon
 * @param {boolean} singleButton Whether to create one-button ("Close") or two-buttons ("Yes"/"No") box
 */
const messageBox = (header, message, iconGlyph, iconClass, singleButton) =>
    new Promise((resolve) => {
        // Unique ID of the "<dialog>" element.
        const uid = generateUid();

        const buttons = singleButton
            ? `<button class="button" type="button" data-id="yes">${i18n["button.close"]}</button>`
            : `<button class="button" type="button" data-id="yes">${i18n["button.yes"]}</button>` +
              `<button class="button" type="button" data-id="no">${i18n["button.no"]}</button>`;

        const template = `
            <dialog id="${uid}" class="messagebox">
                <div class="modal is-active">
                    <div class="modal-card">
                        <header class="modal-card-head">
                            <p class="modal-card-title">${header}</p>
                            <span class="delete" title="${i18n["button.close"]}"></span>
                        </header>
                        <section class="modal-card-body">
                            <div class="columns is-mobile is-align-items-center">
                                <div class="column is-narrow">
                                    <span class="icon is-large">
                                        <span class="fa-stack fa-lg">
                                            <i class="fa fa-stack-2x fa-circle fa-inverse"></i>
                                            <i class="fa fa-stack-2x ${iconGlyph} ${iconClass}"></i>
                                        </span>
                                    </span>
                                </div>
                                <p class="column">${message}</p>
                            </div>
                        </section>
                        <footer class="modal-card-foot is-justify-content-right">
                            ${buttons}
                        </footer>
                    </div>
                </div>
            </dialog>`;

        document.querySelector("body").insertAdjacentHTML("beforeend", template);

        let modal = document.getElementById(uid);

        let btnYes = modal.querySelector('footer button[data-id="yes"]');
        let btnNo = modal.querySelector('footer button[data-id="no"]');
        let btnClose = modal.querySelector("header .delete");

        // Button "Yes" is clicked.
        btnYes.addEventListener("click", () => modal.close("yes"));

        // Button "No" is clicked.
        if (btnNo) {
            btnNo.addEventListener("click", () => modal.close("no"));
        }

        // The "x" button in the header is clicked.
        btnClose.addEventListener("click", () => modal.close("no"));

        // "Esc" is pressed.
        modal.addEventListener("cancel", () => (modal.returnValue = "no"));

        // Dialog is closed.
        modal.addEventListener("close", () => {
            modal.parentNode.removeChild(modal);

            if (singleButton || modal.returnValue === "yes") {
                resolve();
            } else {
                Promise.resolve();
            }
        });

        modal.showModal();
    });