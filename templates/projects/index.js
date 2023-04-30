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

import axios from 'axios';

import * as ui from '@utilities/blockui';
import * as msg from '@utilities/messagebox';
import { date } from '@utilities/epoch';
import parseErrors from '@utilities/parseErrors';
import query from '@utilities/query';
import url from '@utilities/url';

import DataTable from '@components/datatable/datatable.vue';
import Column from '@components/datatable/column.vue';

import ProjectDialog from './dialogs/ProjectDialog.vue';

/**
 * "Projects" page.
 */
const app = createApp({
    data: () => ({
        /**
         * @property {Object} errors Dialog errors
         */
        errors: {}
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
        },

        /**
         * @property {Object} newProjectDialog "New project" dialog instance
         */
        newProjectDialog() {
            return this.$refs.dlgNewProject;
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
        },

        /**
         * Opens "New project" dialog.
         */
        openNewProjectDialog() {
            const defaults = {
                name: '',
                description: ''
            };

            this.errors = {};

            this.newProjectDialog.open(defaults);
        },

        /**
         * Creates new project.
         *
         * @param {Object} event Submitted values
         */
        createProject(event) {
            const data = {
                name: event.name,
                description: event.description || null,
                suspended: true
            };

            ui.block();

            axios
                .post(url('/api/projects'), data)
                .then(() => {
                    msg.info(this.i18n['project.successfully_created']).then(() => {
                        this.newProjectDialog.close();
                        this.projectsTable.refresh();
                    });
                })
                .catch((exception) => (this.errors = parseErrors(exception)))
                .then(() => ui.unblock());
        }
    }
});

app.component('datatable', DataTable);
app.component('column', Column);
app.component('new-project-dialog', ProjectDialog);

app.mount('#vue-projects');
