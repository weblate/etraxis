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
import { createRouter } from "vue-router";

import routerConfig from "./router";

import "./index.scss";

/**
 * Authentication page.
 */
const app = createApp({
    created() {
        // Restore theme mode from local storage.
        let isDarkMode = !!JSON.parse(localStorage[this.themeModeStorage] || "false");
        document.querySelector("html").classList.add(isDarkMode ? "dark" : "light");
        this.lightStyleSheet.disabled = isDarkMode;
        this.darkStyleSheet.disabled = !isDarkMode;
    },

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
        themeModeStorage: () => "eTraxis.isDarkMode"
    }
});

const router = createRouter(routerConfig);

app.use(router);
app.mount("#vue-login");
