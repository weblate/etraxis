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

import { createApp } from "vue";

import axios from "axios";

import * as ui from "@utilities/blockui";
import * as msg from "@utilities/messagebox";
import url from "@utilities/url";

/**
 * Main menu (navigation).
 */
const app = createApp({
    created() {
        // Set default theme mode.
        this.lightStyleSheet.disabled = false;
        this.darkStyleSheet.disabled = true;

        // Detect current theme mode.
        this.isDarkMode = document.querySelector("html").classList.contains("dark");
        localStorage[this.themeModeStorage] = JSON.stringify(this.isDarkMode);
    },

    data: () => ({
        /**
         * @property {boolean} isActive Whether the main menu is active or hidden
         */
        isActive: false,

        /**
         * @property {boolean} isDarkMode Whether the theme mode is set to "dark"
         */
        isDarkMode: false
    }),

    computed: {
        /**
         * @property {Object} lightStyleSheet Stylesheet of the light theme
         */
        lightStyleSheet: () => Object.values(document.styleSheets).find((styleSheet) => styleSheet.href.includes("/light/")),

        /**
         * @property {Object} darkStyleSheet Stylesheet of the dark theme
         */
        darkStyleSheet: () => Object.values(document.styleSheets).find((styleSheet) => styleSheet.href.includes("/dark/")),

        /**
         * @property {string} themeModeStorage Name of the local storage variable to store the theme mode
         */
        themeModeStorage: () => "eTraxis.isDarkMode",

        /**
         * @property {string} themeModeClass Class name for the current theme mode
         */
        themeModeClass() {
            return this.isDarkMode ? "dark" : "light";
        },

        /**
         * @property {string} themeModeIcon Icon for the current theme mode
         */
        themeModeIcon() {
            return this.isDarkMode ? "fa-moon-o" : "fa-sun-o";
        }
    },

    methods: {
        /**
         * Toggles visibility of the main menu.
         */
        toggleMenu() {
            this.isActive = !this.isActive;
        },

        /**
         * Toggles theme mode.
         */
        toggleThemeMode() {
            let html = document.querySelector("html");

            html.classList.remove(this.themeModeClass);
            this.isDarkMode = !this.isDarkMode;
            html.classList.add(this.themeModeClass);

            axios.patch(url("/api/my/profile"), { darkMode: this.isDarkMode });
        },

        /**
         * Logs the current user out.
         *
         * @param {string} url Exit path
         */
        logout(url) {
            this.isActive = false;

            msg.confirm(i18n["confirm.logout"]).then(() => {
                ui.block();
                location.href = url;
            });
        }
    },

    watch: {
        /**
         * Toggles theme stylesheets when the theme mode is changed.
         *
         * @param {boolean} value New value of the theme mode
         */
        isDarkMode(value) {
            this.lightStyleSheet.disabled = value;
            this.darkStyleSheet.disabled = !value;

            localStorage[this.themeModeStorage] = JSON.stringify(value);
        }
    }
});

app.mount("#vue-navbar");
