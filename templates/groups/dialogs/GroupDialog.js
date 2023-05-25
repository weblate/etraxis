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

import Modal from '@components/modal/modal.vue';

import * as GROUP from '@const/group';

import generateUid from '@utilities/uid';

/**
 * Group dialog.
 */
export default {
    props: {
        /**
         * @property {string} header Dialog's header
         */
        header: {
            type: String,
            required: true
        },

        /**
         * @property {Array<Object>} projects List of all existing projects
         */
        projects: {
            type: Array,
            default: null
        },

        /**
         * @property {Object} errors Form errors
         */
        errors: {
            type: Object,
            required: true
        }
    },

    emits: {
        /**
         * :The dialog is submitted.
         *
         * @param {Object} values Submitted form values
         */
        submit: (values) => typeof values === 'object'
    },

    expose: ['open', 'close'],

    components: {
        modal: Modal
    },

    data: () => ({
        /**
         * @property {string} uid Unique dialog ID
         */
        uid: generateUid(),

        /**
         * @property {Object} values Form values
         */
        values: {
            name: null,
            project: 0,
            description: null
        }
    }),

    computed: {
        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {number} MAX_NAME Input constraint
         */
        MAX_NAME: () => GROUP.MAX_NAME,

        /**
         * @property {number} MAX_DESCRIPTION Input constraint
         */
        MAX_DESCRIPTION: () => GROUP.MAX_DESCRIPTION
    },

    methods: {
        /**
         * @public Opens the dialog.
         *
         * @param {Object} defaults Default values
         */
        open(defaults = {}) {
            this.values = { ...defaults };

            this.$refs.modal.open();

            this.$nextTick(() => document.getElementById(`${this.uid}-name`).focus());
        },

        /**
         * @public Closes the dialog.
         */
        close() {
            this.$refs.modal.close();
        },

        /**
         * Submits the dialog's form.
         */
        submit() {
            this.$emit('submit', this.values);
        }
    }
};
