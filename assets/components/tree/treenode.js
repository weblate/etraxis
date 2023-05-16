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

/**
 * Tree node.
 */
export default {
    name: 'tree-node',

    props: {
        /**
         * @property {number|string} id Unique ID
         */
        id: {
            type: [Number, String],
            required: true
        },

        /**
         * @property {string} title Title
         */
        title: {
            type: String,
            required: true
        },

        /**
         * @property {Array<string>} classes List of CSS classes to apply
         */
        classes: {
            type: Array,
            default: () => []
        },

        /**
         * @property {Array<Object>} nodes List of children
         */
        nodes: {
            type: Array,
            default: () => []
        }
    },

    emits: ['node-click', 'node-expand', 'node-collapse'],

    data: () => ({
        /**
         * @property {boolean} isExpanded Whether the node is expanded or collapsed
         */
        isExpanded: false
    }),

    computed: {
        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {boolean} hasChildren Whether this node has children
         */
        hasChildren() {
            return this.nodes.length !== 0;
        },

        /**
         * @property {Object} nodeClass List of CSS classes for the node
         */
        nodeClass() {
            return {
                'is-expanded': this.hasChildren && this.isExpanded,
                'is-collapsed': this.hasChildren && !this.isExpanded
            };
        },

        /**
         * @property {string} toggleSymbol A symbol to show for expand/collapse action
         */
        toggleSymbol() {
            return this.hasChildren
                ? (this.isExpanded ? '\u229F' : '\u229E')
                : '';
        },

        /**
         * @property {string} toggleTitle A hint to show for expand/collapse action
         */
        toggleTitle() {
            return this.hasChildren
                ? this.i18n[this.isExpanded ? 'button.collapse' : 'button.expand']
                : '';
        }
    },

    methods: {
        /**
         * Toggles expansion state of the node.
         */
        toggleNode() {
            if (this.hasChildren) {
                this.isExpanded = !this.isExpanded;
                this.$emit(this.isExpanded ? 'node-expand' : 'node-collapse', this.id);
            }
        },

        /**
         * The node is clicked.
         */
        onNodeClick() {
            this.$emit('node-click', this.id);

            if (!this.isExpanded) {
                this.toggleNode();
            }
        }
    }
};