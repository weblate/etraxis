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

import LoginPage from "./LoginPage.vue";

import "./index.scss";

/**
 * Authentication page.
 */
const app = createApp({});

app.component("login-page", LoginPage);

app.mount("#vue-login");
