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

import Modal from "@components/modal/modal.vue";

import LocaleEnum from "@enums/locale";

import generateUid from "@utilities/uid";

/**
 * "Edit user" dialog.
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
         * @param {Object} values Submitted form values
         */
        submit: (values) => typeof values === "object"
    },

    expose: ["open", "close"],

    components: {
        modal: Modal
    },

    data: () => ({
        /**
         * @property {string} uid Unique dialog ID
         */
        uid: generateUid(),

        /**
         * @property {boolean} isCurrent Whether the account is of the current user
         */
        isCurrent: false,

        /**
         * @property {boolean} isExternal Whether the account is external
         */
        isExternal: false,

        /**
         * @property {Object} values Form values
         */
        values: {
            email: null,
            fullname: null,
            description: null,
            admin: null,
            disabled: null,
            locale: null,
            timezone: null
        }
    }),

    computed: {
        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {Object} locales Available locales
         */
        locales: () => LocaleEnum
    },

    methods: {
        /**
         * @public Opens the dialog.
         *
         * @param {boolean} isCurrent  Whether the account is of the current user
         * @param {boolean} isExternal Whether the account is external
         * @param {Object}  defaults   Default values
         */
        open(isCurrent, isExternal, defaults = {}) {
            this.isCurrent = isCurrent;
            this.isExternal = isExternal;
            this.values = { ...defaults };

            this.$refs.modal.open();

            this.$nextTick(() => document.getElementById(this.isExternal ? `${this.uid}-description` : `${this.uid}-fullname`).focus());
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
            this.$emit("submit", this.values);
        }
    }
};
