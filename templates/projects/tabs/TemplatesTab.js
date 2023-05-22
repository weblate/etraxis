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

import Tree from '@components/tree/tree.vue';
import TreeNode from '@components/tree/node';

import { useProjectStore } from '../stores/ProjectStore';
import { useTemplateStore } from '../stores/TemplateStore';
import { useStateStore } from '../stores/StateStore';
import { useFieldStore } from '../stores/FieldStore';

import TemplateTab from '../embedded-tabs/TemplateTab.vue';
import StateTab from '../embedded-tabs/StateTab.vue';
import FieldTab from '../embedded-tabs/FieldTab.vue';

import TemplateDialog from '../dialogs/TemplateDialog.vue';
import StateDialog from '../dialogs/StateDialog.vue';

const NODE_TEMPLATE = 'template';
const NODE_STATE = 'state';
const NODE_FIELD = 'field';
const NODE_NEW_TEMPLATE = 'newtemplate';
const NODE_NEW_STATE = 'newstate';

/**
 * "Templates" tab.
 */
export default {
    components: {
        tree: Tree,
        'template-tab': TemplateTab,
        'state-tab': StateTab,
        'field-tab': FieldTab,
        'new-template-dialog': TemplateDialog,
        'new-state-dialog': StateDialog
    },

    data: () => ({
        /**
         * @property {string} templateTab ID of the current tab for the currently selected template
         */
        templateTab: 'template',

        /**
         * @property {string} stateTab ID of the current tab for the currently selected state
         */
        stateTab: 'state',

        /**
         * @property {string} fieldTab ID of the current tab for the currently selected field
         */
        fieldTab: 'field',

        /**
         * @property {number} templateId ID of the selected template
         */
        templateId: null,

        /**
         * @property {number} stateId ID of the selected state
         */
        stateId: null,

        /**
         * @property {number} fieldId ID of the selected field
         */
        fieldId: null,

        /**
         * @property {Object} errors Dialog errors
         */
        errors: {}
    }),

    computed: {
        /**
         * @property {Object} projectStore Store for project data
         * @property {Object} templateStore Store for template data
         * @property {Object} stateStore Store for state data
         * @property {Object} fieldStore Store for field data
         */
        ...mapStores(useProjectStore, useTemplateStore, useStateStore, useFieldStore),

        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {Object} newTemplateDialog "New template" dialog instance
         */
        newTemplateDialog() {
            return this.$refs.dlgNewTemplate;
        },

        /**
         * @property {Object} newStateDialog "New state" dialog instance
         */
        newStateDialog() {
            return this.$refs.dlgNewState;
        },

        /**
         * @property {Array<Object>} nodes Tree of templates with states and fields
         */
        nodes() {
            const current = 'has-text-weight-bold';

            return [
                ...this.projectStore.getProjectTemplates.map((template) => new TreeNode(
                    `${NODE_TEMPLATE}-${template.id}`,
                    template.name,
                    true,
                    template.id === this.templateId ? [current] : [],
                    [
                        ...this.projectStore.getTemplateStates(template.id).map((state) => new TreeNode(
                            `${NODE_STATE}-${state.id}`,
                            state.name,
                            true,
                            state.id === this.stateId ? [current] : [],
                            this.projectStore.getStateFields(state.id).map((field) => new TreeNode(
                                `${NODE_FIELD}-${field.id}`,
                                field.name,
                                false,
                                field.id === this.fieldId ? [current] : []
                            ))
                        )),
                        new TreeNode(`${NODE_NEW_STATE}-${template.id}`, this.i18n['state.new'], false, ['has-text-primary'])
                    ]
                )),
                new TreeNode(`${NODE_NEW_TEMPLATE}-${this.projectStore.projectId}`, this.i18n['template.new'], false, ['has-text-primary'])
            ];
        }
    },

    methods: {
        /**
         * A node in the templates tree is expanded.
         *
         * @param {string} event ID associated with the node
         */
        onNodeExpand(event) {
            const [type, sid] = event.split('-');
            const id = parseInt(sid);

            if (type === NODE_TEMPLATE && !this.projectStore.templateStates.has(id)) {
                this.projectStore.loadTemplateStates(id);
            } else if (type === NODE_STATE && !this.projectStore.stateFields.has(id)) {
                this.projectStore.loadStateFields(id);
            }
        },

        /**
         * A node in the templates tree is clicked.
         *
         * @param {string} event ID associated with the node
         */
        async onNodeClick(event) {
            const [type, sid] = event.split('-');
            const id = parseInt(sid);

            switch (type) {
                case NODE_TEMPLATE:
                    await this.templateStore.loadTemplate(id);
                    this.fieldId = null;
                    this.stateId = null;
                    this.templateId = this.templateStore.templateId;
                    break;

                case NODE_NEW_TEMPLATE:
                    this.openNewTemplateDialog(id);
                    break;

                case NODE_STATE:
                    await this.stateStore.loadState(id);
                    this.fieldId = null;
                    this.stateId = this.stateStore.stateId;
                    this.templateId = this.stateStore.template.id;
                    break;

                case NODE_NEW_STATE:
                    if (this.projectStore.isTemplateLocked(id)) {
                        this.openNewStateDialog(id);
                    } else {
                        msg.warning(this.i18n['template.error.must_be_locked']);
                    }
                    break;

                case NODE_FIELD:
                    await this.fieldStore.loadField(id);
                    this.fieldId = this.fieldStore.fieldId;
                    this.stateId = this.fieldStore.state.id;
                    this.templateId = this.fieldStore.template.id;
                    break;

                default:
                    this.fieldId = null;
                    this.stateId = null;
                    this.templateId = null;
            }
        },

        /**
         * Opens "New template" dialog.
         *
         * @param {number} id Proejct ID
         */
        openNewTemplateDialog(id) {
            const defaults = {
                project: id,
                name: '',
                prefix: '',
                description: '',
                criticalAge: null,
                frozenTime: null
            };

            this.errors = {};

            this.newTemplateDialog.open(defaults);
        },

        /**
         * Creates new template.
         *
         * @param {Object} event Submitted values
         */
        async createTemplate(event) {
            const data = {
                project: event.project,
                name: event.name,
                prefix: event.prefix,
                description: event.description || null,
                criticalAge: event.criticalAge || null,
                frozenTime: event.frozenTime || null
            };

            ui.block();

            try {
                const response = await axios.post(url('/api/templates'), data);

                msg.info(this.i18n['template.successfully_created'], async () => {
                    this.newTemplateDialog.close();
                    await this.projectStore.loadAllProjectTemplates();
                    await this.onNodeClick(`${NODE_TEMPLATE}-${response.data.id}`);
                });
            } catch (exception) {
                this.errors = parseErrors(exception);
            } finally {
                ui.unblock();
            }
        },

        /**
         * One of the templates is updated.
         *
         * @param {number} id Template ID
         */
        async onTemplateUpdated(id) {
            const promises = [
                this.projectStore.loadAllProjectTemplates()
            ];

            if (id === this.templateId) {
                promises.push(this.templateStore.loadTemplate());
            }

            await Promise.all(promises);
        },

        /**
         * One of the templates is cloned.
         *
         * @param {number} id New template ID
         */
        async onTemplateCloned(id) {
            await this.projectStore.loadAllProjectTemplates();
            await this.onNodeClick(`${NODE_TEMPLATE}-${id}`);
        },

        /**
         * One of the templates is deleted.
         *
         * @param {number} id Template ID
         */
        async onTemplateDeleted(id) {
            if (id === this.templateId) {
                this.fieldId = null;
                this.stateId = null;
                this.templateId = null;
            }

            await this.projectStore.loadAllProjectTemplates();
        },

        /**
         * Opens "New state" dialog.
         *
         * @param {number} id Template ID
         */
        openNewStateDialog(id) {
            const defaults = {
                template: id,
                name: '',
                type: 'intermediate',
                responsible: 'keep'
            };

            this.errors = {};

            this.newStateDialog.open(defaults);
        },

        /**
         * Creates new state.
         *
         * @param {Object} event Submitted values
         */
        async createState(event) {
            const data = {
                template: event.template,
                name: event.name,
                type: event.type,
                responsible: event.responsible
            };

            ui.block();

            try {
                const response = await axios.post(url('/api/states'), data);

                msg.info(this.i18n['state.successfully_created'], async () => {
                    this.newStateDialog.close();
                    await this.projectStore.loadTemplateStates(event.template);
                    await this.onNodeClick(`${NODE_STATE}-${response.data.id}`);
                });
            } catch (exception) {
                this.errors = parseErrors(exception);
            } finally {
                ui.unblock();
            }
        },

        /**
         * One of the states is updated.
         *
         * @param {number} id State ID
         */
        async onStateUpdated(id) {
            const promises = [
                this.projectStore.loadTemplateStates(this.stateStore.template.id)
            ];

            if (id === this.stateId) {
                promises.push(this.stateStore.loadState());
            }

            await Promise.all(promises);
        },

        /**
         * One of the states is deleted.
         *
         * @param {number} id State ID
         */
        async onStateDeleted(id) {
            if (id === this.stateId) {
                this.fieldId = null;
                this.stateId = null;
            }

            await this.projectStore.loadTemplateStates(this.templateId);
        }
    }
};
