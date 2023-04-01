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

import { createWebHistory } from 'vue-router';

import LoginPage from './pages/LoginPage.vue';
import ForgotPasswordPage from './pages/ForgotPasswordPage.vue';
import ResetPasswordPage from './pages/ResetPasswordPage.vue';

export default {
    history: createWebHistory(),
    routes: [
        {
            path: '/login',
            component: LoginPage
        },
        {
            path: '/forgot',
            component: ForgotPasswordPage
        },
        {
            path: '/reset/:token',
            component: ResetPasswordPage
        }
    ]
};
