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
import Icon from '@components/datatable/icon';

import ProjectDialog from './dialogs/ProjectDialog.vue';

const ICON_UPDATE = 'update';
const ICON_SUSPEND = 'suspend';
const ICON_RESUME = 'resume';

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
        },

        /**
         * @property {Object} editProjectDialog "Edit project" dialog instance
         */
        editProjectDialog() {
            return this.$refs.dlgEditProject;
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
                    const icons = [
                        new Icon(ICON_UPDATE, this.i18n['project.edit'], 'fa-pencil'),
                        project.suspended
                            ? new Icon(ICON_RESUME, this.i18n['button.resume'], 'fa-toggle-off')
                            : new Icon(ICON_SUSPEND, this.i18n['button.suspend'], 'fa-toggle-on')
                    ];

                    return {
                        DT_id: project.id,
                        DT_class: project.suspended ? 'has-text-grey' : null,
                        DT_icons: icons,
                        name: project.name,
                        createdAt: date(project.createdAt),
                        description: project.description
                    };
                })
            };
        },

        /**
         * An icon is clicked.
         *
         * @param {MouseEvent} event Original event
         * @param {number}     id    Project ID
         * @param {string}     icon  Icon ID
         */
        onIcon(event, id, icon) {
            switch (icon) {
                case ICON_UPDATE:
                    this.openEditProjectDialog(id);
                    break;
                case ICON_SUSPEND:
                    this.suspendProject(id);
                    break;
                case ICON_RESUME:
                    this.resumeProject(id);
                    break;
            }
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
        },

        /**
         * Opens "Edit project" dialog.
         *
         * @param {number} id Project ID
         */
        openEditProjectDialog(id) {
            ui.block();

            axios
                .get(url(`/api/projects/${id}`))
                .then((response) => {
                    this.errors = {};
                    this.editProjectDialog.open(response.data);
                })
                .catch((exception) => parseErrors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Updates project.
         *
         * @param {Object} event Submitted values
         */
        updateProject(event) {
            const data = {
                name: event.name,
                description: event.description || null,
                suspended: event.suspended
            };

            ui.block();

            axios
                .put(url(`/api/projects/${event.id}`), data)
                .then(() => {
                    msg.info(this.i18n['text.changes_saved']).then(() => {
                        this.editProjectDialog.close();
                        this.projectsTable.refresh();
                    });
                })
                .catch((exception) => (this.errors = parseErrors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Suspends specified project.
         *
         * @param {number} id Project ID
         */
        suspendProject(id) {
            ui.block();

            axios
                .post(url(`/api/projects/${id}/suspend`))
                .then(() => this.projectsTable.refresh())
                .catch((exception) => parseErrors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Resumes specified project.
         *
         * @param {number} id Project ID
         */
        resumeProject(id) {
            ui.block();

            axios
                .post(url(`/api/projects/${id}/resume`))
                .then(() => this.projectsTable.refresh())
                .catch((exception) => parseErrors(exception))
                .then(() => ui.unblock());
        }
    }
});

app.component('datatable', DataTable);
app.component('column', Column);
app.component('new-project-dialog', ProjectDialog);
app.component('edit-project-dialog', ProjectDialog);

app.mount('#vue-projects');
