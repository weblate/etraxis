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
 * A single tab.
 */
export default {
    props: {
        /**
         * @property {string} id Tab's unique ID
         */
        id: {
            type: String,
            required: true
        },

        /**
         * @property {string} title Tab's title
         */
        title: {
            type: String,
            required: true
        },

        /**
         * @property {number} counter Optional counter value to be displayed in the caption
         */
        counter: {
            type: Number,
            default: null
        }
    },

    computed: {
        /**
         * @property {boolean} isActive Whether the tab is active
         */
        isActive() {
            return this.$parent.modelValue === this.id;
        }
    }
};
