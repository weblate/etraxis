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

import AccountProviderEnum from "@enums/accountprovider";
import LocaleEnum from "@enums/locale";

import url from "@utilities/url";

/**
 * "Profile" tab.
 */
export default {
    props: {
        email: String,
        fullname: String,
        description: String,
        admin: Boolean,
        disabled: Boolean,
        accountProvider: String,
        locale: String,
        timezone: String
    },

    computed: {
        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {Object} accountProviders Supported account providers
         */
        accountProviders: () => AccountProviderEnum,

        /**
         * @property {Object} locales Available locales
         */
        locales: () => LocaleEnum
    },

    methods: {
        /**
         * Redirects back to the users list.
         */
        goBack() {
            location.href = url("/admin/users");
        }
    }
};
