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

import * as FIELD from '@const/field';
import * as FIELD_TYPE from '@const/fieldType';

import FieldTypeEnum from '@enums/fieldType';

import * as convert from '@utilities/convert';
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
         * @property {number} MAX_NAME Input constraint
         */
        MAX_NAME: () => FIELD.MAX_NAME,

        /**
         * @property {number} MAX_DESCRIPTION Input constraint
         */
        MAX_DESCRIPTION: () => FIELD.MAX_DESCRIPTION,

        /**
         * @property {number} MAX_PARAMETER Input constraint
         */
        MAX_PARAMETER: () => FIELD.MAX_PARAMETER,

        /**
         * @property {number} MAX_STRING_LENGTH Input constraint
         */
        MAX_STRING_LENGTH: () => FIELD.MAX_STRING_LENGTH,

        /**
         * @property {number} MAX_TEXT_LENGTH Input constraint
         */
        MAX_TEXT_LENGTH: () => FIELD.MAX_TEXT_LENGTH,

        /**
         * @property {Object} fieldTypes Available field types
         */
        fieldTypes: () => Object.fromEntries(
            Object.entries(FieldTypeEnum)
                .map((x) => [x[0], window.i18n[x[1]]])
                .sort((x1, x2) => x1[1].localeCompare(x2[1]))
        ),

        /**
         * @property {boolean} isCheckbox Whether the current field type is a checkbox
         */
        isCheckbox() {
            return this.values.type === FIELD_TYPE.CHECKBOX;
        },

        /**
         * @property {boolean} isDate Whether the current field type is a date
         */
        isDate() {
            return this.values.type === FIELD_TYPE.DATE;
        },

        /**
         * @property {boolean} isDecimal Whether the current field type is a decimal
         */
        isDecimal() {
            return this.values.type === FIELD_TYPE.DECIMAL;
        },

        /**
         * @property {boolean} isDuration Whether the current field type is a duration
         */
        isDuration() {
            return this.values.type === FIELD_TYPE.DURATION;
        },

        /**
         * @property {boolean} isList Whether the current field type is a list
         */
        isList() {
            return this.values.type === FIELD_TYPE.LIST;
        },

        /**
         * @property {boolean} isNumber Whether the current field type is a number
         */
        isNumber() {
            return this.values.type === FIELD_TYPE.NUMBER;
        },

        /**
         * @property {boolean} isString Whether the current field type is a string
         */
        isString() {
            return this.values.type === FIELD_TYPE.STRING;
        },

        /**
         * @property {boolean} isText Whether the current field type is a text
         */
        isText() {
            return this.values.type === FIELD_TYPE.TEXT;
        },

        fieldTypeMinimumPlaceholder() {
            switch (this.values.type) {
                case FIELD_TYPE.DATE:
                    return '-1000000000';
                case FIELD_TYPE.DECIMAL:
                    return '-9999999999.9999999999';
                case FIELD_TYPE.DURATION:
                    return '0:00';
                case FIELD_TYPE.NUMBER:
                    return '-1000000000';
            }

            return null;
        },

        fieldTypeMaximumPlaceholder() {
            switch (this.values.type) {
                case FIELD_TYPE.DATE:
                    return '+1000000000';
                case FIELD_TYPE.DECIMAL:
                    return '+9999999999.9999999999';
                case FIELD_TYPE.DURATION:
                    return '999999:59';
                case FIELD_TYPE.NUMBER:
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
            const parameters = {};

            switch (this.values.type) {
                case FIELD_TYPE.DATE:
                case FIELD_TYPE.NUMBER:
                    parameters.minimum = convert.toNumber(this.values.minimum);
                    parameters.maximum = convert.toNumber(this.values.maximum);
                    parameters.default = convert.toNumber(this.values.default);
                    break;

                case FIELD_TYPE.DECIMAL:
                case FIELD_TYPE.DURATION:
                    parameters.minimum = convert.toString(this.values.minimum);
                    parameters.maximum = convert.toString(this.values.maximum);
                    parameters.default = convert.toString(this.values.default);
                    break;

                case FIELD_TYPE.STRING:
                case FIELD_TYPE.TEXT:
                    parameters.length = convert.toNumber(this.values.length);
                    parameters.default = convert.toString(this.values.default);
                    break;

                case FIELD_TYPE.CHECKBOX:
                    parameters.default = convert.toBoolean(this.values.default);
                    break;

                case FIELD_TYPE.LIST:
                    parameters.default = convert.toNumber(this.values.default);
                    break;
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
