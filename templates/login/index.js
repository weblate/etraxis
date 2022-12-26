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
import axios from "axios";

import url from "@utilities/url";

/**
 * Login page.
 */
const app = createApp({
    data() {
        return {
            email: null,
            password: null
        };
    },

    methods: {
        /**
         * Submits the login form.
         */
        login() {
            const data = {
                email: this.email,
                password: this.password,
                csrf: this.$refs.csrf.value
            };

            axios
                .post(url("/login"), data)
                .then(() => location.reload())
                .catch((error) => alert(error.response.data));
        }
    }
});

app.mount("#vue-login");
