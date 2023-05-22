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

import FieldTypeEnum from '@enums/fieldType';

import generateUid from '@utilities/uid';

/**
 * Field dialog.
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
            type: null,
            description: null,
            required: null,
            length: null,
            minimum: null,
            maximum: null,
            default: null
        }
    }),

    computed: {
        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {Object} fieldTypes Available field types
         */
        fieldTypes: () => Object.fromEntries(
            Object.entries(FieldTypeEnum)
                .map((x) => [x[0], window.i18n[x[1]]])
                .sort((x1, x2) => x1[1].localeCompare(x2[1]))
        ),

        fieldTypeMinimumPlaceholder() {
            switch (this.values.type) {
                case 'date':
                    return '-1000000000';
                case 'decimal':
                    return '-9999999999.9999999999';
                case 'duration':
                    return '0:00';
                case 'number':
                    return '-1000000000';
            }

            return null;
        },

        fieldTypeMaximumPlaceholder() {
            switch (this.values.type) {
                case 'date':
                    return '+1000000000';
                case 'decimal':
                    return '+9999999999.9999999999';
                case 'duration':
                    return '999999:59';
                case 'number':
                    return '+1000000000';
            }

            return null;
        }
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
            let parameters = {};

            if (['date', 'number'].includes(this.values.type)) {
                parameters = {
                    minimum: this.values.minimum ?? null,
                    maximum: this.values.maximum ?? null,
                    default: this.values.default ?? null
                };
            } else if (['decimal', 'duration'].includes(this.values.type)) {
                parameters = {
                    minimum: this.values.minimum || null,
                    maximum: this.values.maximum || null,
                    default: this.values.default || null
                };
            } else if (['string', 'text'].includes(this.values.type)) {
                parameters = {
                    length: this.values.length ?? null,
                    default: this.values.default || null
                };
            } else if (['checkbox'].includes(this.values.type)) {
                parameters = {
                    default: !!this.values.default
                };
            } else if (['list'].includes(this.values.type)) {
                parameters = {
                    default: this.values.default ?? null
                };
            }

            const values = {
                state: this.values.state,
                name: this.values.name,
                type: this.values.type,
                description: this.values.description || null,
                required: this.values.required,
                parameters: Object.fromEntries(
                    Object.entries(parameters)
                        .filter((x) => x[1] !== null)
                )
            };

            this.$emit('submit', values);
        }
    },

    watch: {
        /**
         * Resets field parameters if the field type is changed.
         */
        'values.type'() {
            this.values.length = null;
            this.values.minimum = null;
            this.values.maximum = null;
            this.values.default = null;
        }
    }
};
