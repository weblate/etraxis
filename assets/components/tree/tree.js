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

import TreeNode from './treenode.vue';

/**
 * Tree.
 */
export default {
    props: {
        /**
         * @property {Array<Object>} nodes List of nodes
         */
        nodes: {
            type: Array,
            required: true
        }
    },

    emits: ['node-click', 'node-expand', 'node-collapse'],

    components: {
        'tree-node': TreeNode
    }
};
