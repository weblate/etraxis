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

import * as USER from '@const/user';

import * as ui from '@utilities/blockui';
import * as msg from '@utilities/messagebox';
import url from '@utilities/url';

/**
 * "Forgot password" page.
 */
export default {
    data: () => ({
        // Form model
        email: null
    }),

    computed: {
        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {number} MAX_EMAIL Input constraint
         */
        MAX_EMAIL: () => USER.MAX_EMAIL
    },

    methods: {
        /**
         * Submits the form.
         */
        async submit() {
            ui.block();

            const data = {
                email: this.email
            };

            try {
                await axios.post(url('/api/forgot'), data);

                msg.info(this.i18n['password.forgot.email_sent'], () => {
                    // noinspection JSUnresolvedFunction
                    this.$router.push('/login');
                });
            } catch (error) {
                msg.alert(error.response.data);
            } finally {
                ui.unblock();
            }
        }
    }
};
