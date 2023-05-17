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

/**
 * "Templates" tab.
 */
export default {
    components: {
        tree: Tree
    },

    computed: {
        /**
         * @property {Object} projectStore Store for project data
         */
        ...mapStores(useProjectStore),

        /**
         * @property {Array<Object>} nodes Tree of templates with states and fields
         */
        nodes() {
            return this.projectStore.getProjectTemplates.map((template) => new TreeNode(
                `template-${template.id}`,
                template.name,
                [],
                this.projectStore.getTemplateStates(template.id).map((state) => new TreeNode(
                    `state-${state.id}`,
                    state.name,
                    [],
                    this.projectStore.getStateFields(state.id).map((field) => new TreeNode(
                        `field-${field.id}`,
                        field.name
                    ))
                ))
            ));
        }
    }
};
