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
import { createRouter } from "vue-router";

import routerConfig from "./router";

import "./index.scss";

/**
 * Authentication page.
 */
const app = createApp({});
const router = createRouter(routerConfig);

app.use(router);
app.mount("#vue-login");
