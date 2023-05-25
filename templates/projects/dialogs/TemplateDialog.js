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

import * as TEMPLATE from '@const/template';

import generateUid from '@utilities/uid';

/**
 * Template dialog.
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
         * @property {Object} errors Form errors
         */
        errors: {
            type: Object,
            required: true
        },

        /**
         * @property {Array<Object>} projects Projects to select from
         */
        projects: {
            type: Array,
            default: () => []
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
            project: null,
            name: null,
            prefix: null,
            description: null,
            criticalAge: null,
            frozenTime: null
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
        MAX_NAME: () => TEMPLATE.MAX_NAME,

        /**
         * @property {number} MAX_PREFIX Input constraint
         */
        MAX_PREFIX: () => TEMPLATE.MAX_PREFIX,

        /**
         * @property {number} MAX_DESCRIPTION Input constraint
         */
        MAX_DESCRIPTION: () => TEMPLATE.MAX_DESCRIPTION,

        /**
         * @property {number} MIN_CRITICAL_AGE Input constraint
         */
        MIN_CRITICAL_AGE: () => TEMPLATE.MIN_CRITICAL_AGE,

        /**
         * @property {number} MIN_FROZEN_TIME Input constraint
         */
        MIN_FROZEN_TIME: () => TEMPLATE.MIN_FROZEN_TIME
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
