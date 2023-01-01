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

import url from "@utilities/url";

/**
 * "Reset password" page.
 */
export default {
    data() {
        return {
            password: null,
            confirmation: null
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
            if (this.password !== this.confirmation) {
                alert(i18n["password.dont_match"]);
                return;
            }

            const data = {
                token: this.$route.params.token,
                password: this.password
            };

            axios
                .post(url("/api/reset"), data)
                .then(() => {
                    alert(i18n["password.changed"]);
                    this.$router.push("/login");
                })
                .catch((error) => alert(error.response.data));
        }
    }
};
