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

import { createApp } from "vue";

import AccountProviderEnum from "@enums/accountprovider";

import query from "@utilities/query";
import url from "@utilities/url";

import DataTable from "@components/datatable/datatable.vue";
import Column from "@components/datatable/column.vue";

/**
 * "Users" page.
 */
const app = createApp({
    computed: {
        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {Object} permissions List of possible account permissions
         */
        permissions: () => ({
            true: i18n["role.admin"],
            false: i18n["role.user"]
        }),

        /**
         * @property {Object} providers List of possible account providers
         */
        providers: () => AccountProviderEnum
    },

    methods: {
        /**
         * Data provider for the table.
         *
         * @param {number} offset  Zero-based index of the first entry to return
         * @param {number} limit   Maximum number of entries to return
         * @param {string} search  Current value of the search
         * @param {Object} filters Current values of the column filters ({ "column id": value })
         * @param {Object} order   Current sorting order ({ "column id": "asc"|"desc" })
         *
         * @return {Object} Table data
         */
        async users(offset, limit, search, filters, order) {
            let data = await query(url("/api/users"), offset, limit, search, filters, order);

            return {
                total: data.total,
                rows: data.rows.map((user) => {
                    return {
                        DT_id: user.id,
                        DT_class: user.disabled ? "has-text-grey" : null,
                        fullname: user.fullname,
                        email: user.email,
                        admin: user.admin ? i18n["role.admin"] : i18n["role.user"],
                        accountProvider: this.providers[user.accountProvider],
                        description: user.description
                    };
                })
            };
        }
    }
});

app.component("datatable", DataTable);
app.component("column", Column);

app.mount("#vue-users");
