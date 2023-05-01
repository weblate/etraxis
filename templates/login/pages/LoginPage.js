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

import axios from 'axios';

import * as ui from '@utilities/blockui';
import * as msg from '@utilities/messagebox';
import url from '@utilities/url';

/**
 * Login page.
 */
export default {
    props: {
        /**
         * @property {string} csrf CSRF token for the form
         */
        csrf: {
            type: String,
            required: true
        }
    },

    data: () => ({
        // Form model
        email: null,
        password: null,
        remember: false
    }),

    computed: {
        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n
    },

    methods: {
        /**
         * Submits the login form.
         */
        async login() {
            ui.block();

            const data = {
                email: this.email,
                password: this.password,
                remember: this.remember,
                csrf: this.csrf
            };

            try {
                await axios.post(url('/login'), data);
                location.reload();
            } catch (error) {
                msg.alert(error.response.data);
                ui.unblock();
            }
        }
    }
};
