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

import { createWebHistory } from "vue-router";

import LoginPage from "./LoginPage.vue";
import ForgotPasswordPage from "./ForgotPasswordPage.vue";
import ResetPasswordPage from "./ResetPasswordPage.vue";

export default {
    history: createWebHistory(),
    routes: [
        {
            path: "/login",
            component: LoginPage
        },
        {
            path: "/forgot",
            component: ForgotPasswordPage
        },
        {
            path: "/reset/:token",
            component: ResetPasswordPage
        }
    ]
};
