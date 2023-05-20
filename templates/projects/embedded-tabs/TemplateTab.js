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
         * The template is deleted.
         *
         * @param {number} id Template ID
         */
        delete: (id) => typeof id === 'number'
    },

    components: {
        'edit-template-dialog': TemplateDialog
    },

    data: () => ({
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
                criticalAge: event.criticalAge || null,
                frozenTime: event.frozenTime || null
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
