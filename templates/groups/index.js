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

import { createApp } from 'vue';

import * as ui from '@utilities/blockui';
import loadAll from '@utilities/loadAll';
import parseErrors from '@utilities/parseErrors';
import query from '@utilities/query';
import url from '@utilities/url';

import DataTable from '@components/datatable/datatable.vue';
import Column from '@components/datatable/column.vue';

/**
 * "Groups" page.
 */
const app = createApp({
    data: () => ({
        /**
         * @property {Object} projects List of all projects
         */
        projects: {}
    }),

    computed: {
        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {Object} groupsTable DataTable instance
         */
        groupsTable() {
            return this.$refs.groups;
        }
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
        async groups(offset, limit, search, filters, order) {
            if (filters.project === 0) {
                filters.global = true;
                delete filters.project;
            }

            const data = await query(url('/api/groups'), offset, limit, search, filters, order);

            return {
                total: data.total,
                rows: data.rows.map((group) => {
                    return {
                        DT_id: group.id,
                        name: group.name,
                        project: group.global ? '—' : group.project.name,
                        description: group.description
                    };
                })
            };
        }
    },

    async created() {
        ui.block();

        try {
            let projects = await loadAll(url('/api/projects'));

            projects = projects
                .sort((project1, project2) => project1.name.localeCompare(project2.name))
                .map((project) => [project.id + '.', project.name]);

            this.projects = Object.fromEntries([[0, '—'], ...projects]);
        } catch (exception) {
            parseErrors(exception);
        } finally {
            ui.unblock();
        }
    }
});

app.component('datatable', DataTable);
app.component('column', Column);

app.mount('#vue-groups');
