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

import axios from "axios";

import * as ui from "@utilities/blockui";
import url from "@utilities/url";

/**
 * "Forgot password" page.
 */
export default {
    data() {
        return {
            email: null
        };
    },

    computed: {
        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n
    },

    methods: {
        /**
         * Submits the form.
         */
        submit() {
            ui.block();

            const data = {
                email: this.email
            };

            axios
                .post(url("/api/forgot"), data)
                .then(() => {
                    alert(i18n["password.forgot.email_sent"]);
                    this.$router.push("/login");
                })
                .catch((error) => alert(error.response.data))
                .then(() => ui.unblock());
        }
    }
};
