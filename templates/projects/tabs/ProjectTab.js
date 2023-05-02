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

import { mapStores } from 'pinia';

import axios from 'axios';

import * as ui from '@utilities/blockui';
import * as msg from '@utilities/messagebox';
import parseErrors from '@utilities/parseErrors';
import url from '@utilities/url';

import { useProjectStore } from '../stores/ProjectStore';

import ProjectDialog from '../dialogs/ProjectDialog.vue';

/**
 * "Project" tab.
 */
export default {
    components: {
        'edit-project-dialog': ProjectDialog
    },

    data: () => ({
        /**
         * @property {Object} errors Dialog errors
         */
        errors: {}
    }),

    computed: {
        /**
         * @property {Object} projectStore Store for project data
         */
        ...mapStores(useProjectStore),

        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {Object} editProjectDialog "Edit project" dialog instance
         */
        editProjectDialog() {
            return this.$refs.dlgEditProject;
        }
    },

    methods: {
        /**
         * Redirects back to the projects list.
         */
        goBack() {
            location.href = url('/admin/projects');
        },

        /**
         * Opens "Edit project" dialog.
         */
        openEditProjectDialog() {
            const defaults = {
                name: this.projectStore.name,
                description: this.projectStore.description
            };

            this.errors = {};
            this.editProjectDialog.open(defaults);
        },

        /**
         * Updates project.
         *
         * @param {Object} event Submitted values
         */
        async updateProject(event) {
            const data = {
                name: event.name,
                description: event.description || null,
                suspended: this.projectStore.isSuspended
            };

            ui.block();

            try {
                await axios.put(url(`/api/projects/${this.projectStore.projectId}`), data);
                await this.projectStore.loadProject();
                document.title = this.projectStore.name;

                msg.info(this.i18n['text.changes_saved'], () => {
                    this.editProjectDialog.close();
                });
            } catch (exception) {
                this.errors = parseErrors(exception);
            } finally {
                ui.unblock();
            }
        },

        /**
         * Toggles the project's status.
         */
        async toggleStatus() {
            ui.block();

            try {
                await axios.post(url(`/api/projects/${this.projectStore.projectId}/${this.projectStore.isSuspended ? 'resume' : 'suspend'}`));
                await this.projectStore.loadProject();
            } catch (exception) {
                parseErrors(exception);
            } finally {
                ui.unblock();
            }
        },

        /**
         * Deletes the project.
         */
        deleteProject() {
            msg.confirm(this.i18n['confirm.project.delete'], async () => {
                ui.block();

                try {
                    await axios.delete(url(`/api/projects/${this.projectStore.projectId}`));
                    this.goBack();
                } catch (exception) {
                    parseErrors(exception);
                } finally {
                    ui.unblock();
                }
            });
        }
    }
};
