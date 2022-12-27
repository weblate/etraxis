//----------------------------------------------------------------------
//
//  Copyright (C) 2017-2022 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

import { createApp } from "vue";

/**
 * Main menu (navigation).
 */
const app = createApp({
    data() {
        return {
            /**
             * @property {boolean} isActive Whether the main menu is active or hidden
             */
            isActive: false
        };
    },

    methods: {
        /**
         * Toggles visibility of the main menu.
         */
        toggleMenu() {
            this.isActive = !this.isActive;
        }
    }
});

app.mount("#vue-navbar");
