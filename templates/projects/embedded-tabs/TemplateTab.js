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

import * as convert from '@utilities/convert';
import * as ui from '@utilities/blockui';
import * as msg from '@utilities/messagebox';
import loadAll from '@utilities/loadAll';
import parseErrors from '@utilities/parseErrors';
import url from '@utilities/url';

import { useTemplateStore } from '../stores/TemplateStore';

import TemplateDialog from '../dialogs/TemplateDialog.vue';

/**
 * "Template" tab.
 */
export default {
    emits: {
        /**
         * The template is updated.
         *
         * @param {number} id Template ID
         */
        update: (id) => typeof id === 'number',

        /**
         * The template is cloned.
         *
         * @param {number} id New template ID
         */
        clone: (id) => typeof id === 'number',

        /**
         * The template is deleted.
         *
         * @param {number} id Template ID
         */
        delete: (id) => typeof id === 'number'
    },

    components: {
        'edit-template-dialog': TemplateDialog,
        'clone-template-dialog': TemplateDialog
    },

    data: () => ({
        /**
         * @property {Array<Object>} projects All existing projects
         */
        projects: [],

        /**
         * @property {Object} errors Dialog errors
         */
        errors: {}
    }),

    computed: {
        /**
         * @property {Object} templateStore Store for template data
         */
        ...mapStores(useTemplateStore),

        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {Object} editTemplateDialog "Edit template" dialog instance
         */
        editTemplateDialog() {
            return this.$refs.dlgEditTemplate;
        },

        /**
         * @property {Object} cloneTemplateDialog "Clone template" dialog instance
         */
        cloneTemplateDialog() {
            return this.$refs.dlgCloneTemplate;
        }
    },

    methods: {
        /**
         * Opens "Edit template" dialog.
         */
        openEditTemplateDialog() {
            const defaults = {
                name: this.templateStore.name,
                prefix: this.templateStore.prefix,
                description: this.templateStore.description,
                criticalAge: this.templateStore.criticalAge,
                frozenTime: this.templateStore.frozenTime
            };

            this.errors = {};
            this.editTemplateDialog.open(defaults);
        },

        /**
         * Updates template.
         *
         * @param {Object} event Submitted values
         */
        async updateTemplate(event) {
            const data = {
                name: event.name,
                prefix: event.prefix,
                description: event.description || null,
                criticalAge: convert.toNumber(event.criticalAge),
                frozenTime: convert.toNumber(event.frozenTime)
            };

            ui.block();

            try {
                await axios.put(url(`/api/templates/${this.templateStore.templateId}`), data);

                this.$emit('update', this.templateStore.templateId);

                msg.info(this.i18n['text.changes_saved'], () => {
                    this.editTemplateDialog.close();
                });
            } catch (exception) {
                this.errors = parseErrors(exception);
            } finally {
                ui.unblock();
            }
        },

        /**
         * Opens "Clone template" dialog.
         */
        async openCloneTemplateDialog() {
            const defaults = {
                project: this.templateStore.project.id,
                name: this.templateStore.name,
                prefix: this.templateStore.prefix,
                description: this.templateStore.description,
                criticalAge: this.templateStore.criticalAge,
                frozenTime: this.templateStore.frozenTime
            };

            this.errors = {};

            if (this.projects.length === 0) {
                this.projects = await loadAll(url('/api/projects'), {}, { name: 'asc' });
            }

            this.cloneTemplateDialog.open(defaults);
        },

        /**
         * Clones template.
         *
         * @param {Object} event Submitted values
         */
        async cloneTemplate(event) {
            const data = {
                project: event.project,
                name: event.name,
                prefix: event.prefix,
                description: event.description || null,
                criticalAge: convert.toNumber(event.criticalAge),
                frozenTime: convert.toNumber(event.frozenTime)
            };

            ui.block();

            try {
                const response = await axios.post(url(`/api/templates/${this.templateStore.templateId}`), data);

                if (data.project === this.templateStore.project.id) {
                    this.$emit('clone', response.data.id);
                }

                msg.info(this.i18n['template.successfully_created'], () => {
                    this.cloneTemplateDialog.close();
                });
            } catch (exception) {
                this.errors = parseErrors(exception);
            } finally {
                ui.unblock();
            }
        },

        /**
         * Toggles the template's status.
         */
        async toggleStatus() {
            ui.block();

            try {
                await axios.post(url(`/api/templates/${this.templateStore.templateId}/${this.templateStore.isLocked ? 'unlock' : 'lock'}`));
                await this.templateStore.loadTemplate();
            } catch (exception) {
                parseErrors(exception);
            } finally {
                ui.unblock();
            }
        },

        /**
         * Deletes the template.
         */
        deleteTemplate() {
            msg.confirm(this.i18n['confirm.template.delete'], async () => {
                ui.block();

                try {
                    await axios.delete(url(`/api/templates/${this.templateStore.templateId}`));

                    this.$emit('delete', this.templateStore.templateId);
                } catch (exception) {
                    parseErrors(exception);
                } finally {
                    ui.unblock();
                }
            });
        }
    }
};
