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

const ORDER_ASC = "asc";
const ORDER_DESC = "desc";

/**
 * DataTable column.
 */
export default {
    props: {
        /**
         * @property {string} id Column ID
         */
        id: {
            type: String,
            required: true
        },

        /**
         * @property {boolean} wrappable Whether the contents of this column can be wrapped into multiple lines
         */
        wrappable: {
            type: Boolean,
            default: false
        },

        /**
         * @property {boolean} sortable Whether the table can be sorted by this column
         */
        sortable: {
            type: Boolean,
            default: false
        },

        /**
         * @property {boolean} filterable Whether the table can be filtered by this column
         */
        filterable: {
            type: Boolean,
            default: false
        },

        /**
         * @property {Object} filterWith List of allowed filtering options, if applicable
         */
        filterWith: {
            type: Object,
            default: () => ({})
        }
    },

    computed: {
        /**
         * @property {Object} cssClass CSS classes of the column
         */
        cssClass() {
            return {
                "is-narrow": !this.wrappable,
                "is-clickable": this.sortable
            };
        },

        /**
         * @property {boolean} isSortedAsc Whether the current sorting order is ascending
         */
        isSortedAsc() {
            return this.$parent.$data.order[this.id] === ORDER_ASC;
        },

        /**
         * @property {boolean} isSortedDesc Whether the current sorting order is descending
         */
        isSortedDesc() {
            return this.$parent.$data.order[this.id] === ORDER_DESC;
        }
    },

    methods: {
        /**
         * Toggles sorting order of the column.
         *
         * @param {MouseEvent} event Mouse event
         */
        onClick(event) {
            if (this.sortable) {
                let direction = (this.$parent.$data.order[this.id] || "") === ORDER_ASC ? ORDER_DESC : ORDER_ASC;

                if (event.ctrlKey) {
                    delete this.$parent.$data.order[this.id];
                    this.$parent.$data.order = { ...this.$parent.$data.order, [this.id]: direction };
                } else {
                    this.$parent.$data.order = { [this.id]: direction };
                }
            }
        }
    }
};
