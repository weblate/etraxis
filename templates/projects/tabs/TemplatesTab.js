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

const NODE_TEMPLATE = 'template';
const NODE_STATE = 'state';
const NODE_FIELD = 'field';

/**
 * "Templates" tab.
 */
export default {
    components: {
        tree: Tree,
        'template-tab': TemplateTab,
        'state-tab': StateTab,
        'field-tab': FieldTab,
        'new-template-dialog': TemplateDialog
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
         * @property {Array<Object>} nodes Tree of templates with states and fields
         */
        nodes() {
            const current = 'has-text-weight-bold';

            return [
                ...this.projectStore.getProjectTemplates.map((template) => new TreeNode(
                    `template-${template.id}`,
                    template.name,
                    true,
                    template.id === this.templateId ? [current] : [],
                    this.projectStore.getTemplateStates(template.id).map((state) => new TreeNode(
                        `state-${state.id}`,
                        state.name,
                        true,
                        state.id === this.stateId ? [current] : [],
                        this.projectStore.getStateFields(state.id).map((field) => new TreeNode(
                            `field-${field.id}`,
                            field.name,
                            false,
                            field.id === this.fieldId ? [current] : []
                        ))
                    ))
                )),
                new TreeNode('template-new', this.i18n['template.new'], false, ['has-text-primary'])
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
            const [type, id] = event.split('-');

            switch (type) {
                case NODE_TEMPLATE:
                    if (id === 'new') {
                        this.openNewTemplateDialog();
                    } else {
                        await this.templateStore.loadTemplate(parseInt(id));
                        this.fieldId = null;
                        this.stateId = null;
                        this.templateId = this.templateStore.templateId;
                    }
                    break;

                case NODE_STATE:
                    await this.stateStore.loadState(parseInt(id));
                    this.fieldId = null;
                    this.stateId = this.stateStore.stateId;
                    this.templateId = this.stateStore.template.id;
                    break;

                case NODE_FIELD:
                    await this.fieldStore.loadField(parseInt(id));
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
         */
        openNewTemplateDialog() {
            const defaults = {
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
                project: this.projectStore.projectId,
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
                    await this.onNodeClick(`template-${response.data.id}`);
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
            await this.onNodeClick(`template-${id}`);
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
        }
    }
};
