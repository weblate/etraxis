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

import * as USER from '@const/user';

import languages from '@utilities/languages';
import generateUid from '@utilities/uid';

/**
 * "New user" dialog.
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
         * @property {Array<string>} timezones List of all available timezones
         */
        timezones: {
            type: Array,
            required: true
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
            email: null,
            password: null,
            fullname: null,
            description: null,
            admin: null,
            disabled: null,
            locale: null,
            timezone: null
        },

        /**
         * @property {string} confirmation Password confirmation
         */
        confirmation: null
    }),

    computed: {
        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {number} MAX_EMAIL Input constraint
         */
        MAX_EMAIL: () => USER.MAX_EMAIL,

        /**
         * @property {number} MAX_PASSWORD Input constraint
         */
        MAX_PASSWORD: () => USER.MAX_PASSWORD,

        /**
         * @property {number} MAX_FULLNAME Input constraint
         */
        MAX_FULLNAME: () => USER.MAX_FULLNAME,

        /**
         * @property {number} MAX_DESCRIPTION Input constraint
         */
        MAX_DESCRIPTION: () => USER.MAX_DESCRIPTION,

        /**
         * @property {Object} languages Available languages
         */
        languages: () => languages()
    },

    methods: {
        /**
         * @public Opens the dialog.
         *
         * @param {Object} defaults Default values
         */
        open(defaults = {}) {
            this.values = { ...defaults };
            this.confirmation = '';

            this.$refs.modal.open();

            this.$nextTick(() => document.getElementById(`${this.uid}-fullname`).focus());
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
            if (this.values.password !== this.confirmation) {
                this.errors.confirmation = this.i18n['password.dont_match'];
            } else {
                this.errors.confirmation = null;
                this.$emit('submit', this.values);
            }
        }
    }
};
