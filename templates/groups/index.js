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
import loadAll from '@utilities/loadAll';
import parseErrors from '@utilities/parseErrors';
import query from '@utilities/query';
import url from '@utilities/url';

import DataTable from '@components/datatable/datatable.vue';
import Column from '@components/datatable/column.vue';

import GroupDialog from './dialogs/GroupDialog.vue';

/**
 * "Groups" page.
 */
const app = createApp({
    data: () => ({
        /**
         * @property {Array<Object>} projects List of all projects
         */
        projects: [],

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
         * @property {Object} projectsFilter Object with all the projects, where key is a project ID and value is a project name
         */
        projectsFilter() {
            const projects = this.projects.map((project) => [project.id + '.', project.name]);

            return Object.fromEntries([[0, '—'], ...projects]);
        },

        /**
         * @property {Object} groupsTable DataTable instance
         */
        groupsTable() {
            return this.$refs.groups;
        },

        /**
         * @property {Object} newGroupDialog "New group" dialog instance
         */
        newGroupDialog() {
            return this.$refs.dlgNewGroup;
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
        },

        /**
         * A table row is clicked.
         *
         * @param {MouseEvent} event Original event
         * @param {number}     id    Group ID
         */
        viewGroup(event, id) {
            if (event.ctrlKey) {
                window.open(url(`/admin/groups/${id}`), '_blank');
            } else {
                location.href = url(`/admin/groups/${id}`);
            }
        },

        /**
         * Opens "New group" dialog.
         */
        openNewGroupDialog() {
            const defaults = {
                name: '',
                project: 0,
                description: ''
            };

            this.errors = {};

            this.newGroupDialog.open(defaults);
        },

        /**
         * Creates new group.
         *
         * @param {Object} event Submitted values
         */
        async createGroup(event) {
            const data = {
                name: event.name,
                project: event.project || null,
                description: event.description || null
            };

            ui.block();

            try {
                await axios.post(url('/api/groups'), data);

                msg.info(this.i18n['group.successfully_created'], () => {
                    this.newGroupDialog.close();
                    this.groupsTable.refresh();
                });
            } catch (exception) {
                this.errors = parseErrors(exception);
            } finally {
                ui.unblock();
            }
        }
    },

    async created() {
        ui.block();

        try {
            const projects = await loadAll(url('/api/projects'));
            this.projects = projects.sort((project1, project2) => project1.name.localeCompare(project2.name));
        } catch (exception) {
            parseErrors(exception);
        } finally {
            ui.unblock();
        }
    }
});

app.component('datatable', DataTable);
app.component('column', Column);
app.component('new-group-dialog', GroupDialog);

app.mount('#vue-groups');
