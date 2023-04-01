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

import generateUid from '@utilities/uid';

/**
 * "Set password" dialog.
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
        }
    },

    emits: {
        /**
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
         * @property {string} password New password
         */
        password: null,

        /**
         * @property {string} confirmation Password confirmation
         */
        confirmation: null
    }),

    computed: {
        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n
    },

    methods: {
        /**
         * @public Opens the dialog.
         */
        open() {
            this.password = '';
            this.confirmation = '';

            this.$refs.modal.open();

            this.$nextTick(() => document.getElementById(`${this.uid}-password`).focus());
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
            if (this.password !== this.confirmation) {
                this.errors.confirmation = this.i18n['password.dont_match'];
            } else {
                this.errors.confirmation = null;
                this.$emit('submit', this.password);
            }
        }
    }
};
