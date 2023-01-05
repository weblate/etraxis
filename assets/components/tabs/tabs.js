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
 * Set of tabs.
 */
export default {
    props: {
        /**
         * @property {string} modelValue ID of the active tab
         */
        modelValue: {
            type: String,
            required: false
        }
    },

    emits: {
        /**
         * @param {string} value ID of the active tab
         */
        "update:modelValue": (value) => typeof value === "string"
    },

    computed: {
        /**
         * @property {Array<Object>} tabs List of tabs
         */
        tabs() {
            return this.$slots.default();
        }
    }
};
