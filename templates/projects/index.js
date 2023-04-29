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

import { date } from '@utilities/epoch';
import query from '@utilities/query';
import url from '@utilities/url';

import DataTable from '@components/datatable/datatable.vue';
import Column from '@components/datatable/column.vue';

/**
 * "Projects" page.
 */
const app = createApp({
    data: () => ({
    }),

    computed: {
        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {Object} projectsTable DataTable instance
         */
        projectsTable() {
            return this.$refs.projects;
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
        async projects(offset, limit, search, filters, order) {
            const data = await query(url('/api/projects'), offset, limit, search, filters, order);

            return {
                total: data.total,
                rows: data.rows.map((project) => {
                    return {
                        DT_id: project.id,
                        DT_class: project.suspended ? 'has-text-grey' : null,
                        name: project.name,
                        createdAt: date(project.createdAt),
                        description: project.description
                    };
                })
            };
        },

        /**
         * A table row is clicked.
         *
         * @param {MouseEvent} event Original event
         * @param {number}     id    Project ID
         */
        viewProject(event, id) {
            if (event.ctrlKey) {
                window.open(url(`/admin/projects/${id}`), '_blank');
            } else {
                location.href = url(`/admin/projects/${id}`);
            }
        }
    }
});

app.component('datatable', DataTable);
app.component('column', Column);

app.mount('#vue-projects');
