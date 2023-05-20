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

import Tree from '@components/tree/tree.vue';
import TreeNode from '@components/tree/node';

import { useProjectStore } from '../stores/ProjectStore';
import { useTemplateStore } from '../stores/TemplateStore';
import { useStateStore } from '../stores/StateStore';
import { useFieldStore } from '../stores/FieldStore';

import TemplateTab from '../embedded-tabs/TemplateTab.vue';
import StateTab from '../embedded-tabs/StateTab.vue';
import FieldTab from '../embedded-tabs/FieldTab.vue';

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
        'field-tab': FieldTab
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
        fieldId: null
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
         * @property {Array<Object>} nodes Tree of templates with states and fields
         */
        nodes() {
            const current = 'has-text-weight-bold';

            return this.projectStore.getProjectTemplates.map((template) => new TreeNode(
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
            ));
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
                    await this.templateStore.loadTemplate(parseInt(id));
                    this.fieldId = null;
                    this.stateId = null;
                    this.templateId = this.templateStore.templateId;
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
        }
    }
};
