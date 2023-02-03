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
 * Dropdown button.
 */
export default {
    props: {
        /**
         * @property {string} caption Button text
         */
        caption: {
            type: String,
            required: true
        },

        /**
         * @property {boolean} disabled Whether the dropdown is disabled
         */
        disabled: {
            type: Boolean,
            default: false
        }
    },

    data: () => ({
        /**
         * @property {string} uid Unique dropdown ID
         */
        uid: generateUid(),

        /**
         * @property {boolean} isActive Whether the dropdown is active or hidden
         */
        isActive: false
    }),

    methods: {
        /**
         * Toggles the dropdown list state.
         */
        toggle() {
            if (!this.disabled) {
                this.isActive = !this.isActive;
            }
        },

        /**
         * Hides the dropdown menu when user clicks anywhere.
         *
         * @param {MouseEvent} event
         */
        eventListener(event) {
            if (!this.$el.querySelector(".dropdown-trigger").contains(event.target)) {
                this.isActive = false;
            }
        }
    },

    watch: {
        /**
         * State of the dropdown menu is changed.
         *
         * @param {boolean} value New state
         */
        isActive(value) {
            if (value) {
                document.addEventListener("click", this.eventListener);
            } else {
                document.removeEventListener("click", this.eventListener);
            }
        }
    },

    beforeUnmount() {
        document.removeEventListener("click", this.eventListener);
    }
};
