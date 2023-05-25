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

import { mapStores } from 'pinia';

import axios from 'axios';

import * as FIELD_TYPE from '@const/fieldType';

import FieldTypeEnum from '@enums/fieldType';

import * as ui from '@utilities/blockui';
import * as msg from '@utilities/messagebox';
import parseErrors from '@utilities/parseErrors';
import url from '@utilities/url';

import { useFieldStore } from '../stores/FieldStore';

import FieldDialog from '../dialogs/FieldDialog.vue';

/**
 * "Field" tab.
 */
export default {
    emits: {
        /**
         * The field is updated.
         *
         * @param {number} id Field ID
         */
        update: (id) => typeof id === 'number',

        /**
         * The field is deleted.
         *
         * @param {number} id Field ID
         */
        delete: (id) => typeof id === 'number'
    },

    components: {
        'edit-field-dialog': FieldDialog
    },

    data: () => ({
        /**
         * @property {Object} errors Dialog errors
         */
        errors: {}
    }),

    computed: {
        /**
         * @property {Object} fieldStore Store for field data
         */
        ...mapStores(useFieldStore),

        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {Object} fieldTypes List of possible field types
         */
        fieldTypes: () => FieldTypeEnum,

        /**
         * @property {Object} editFieldDialog "Edit field" dialog instance
         */
        editFieldDialog() {
            return this.$refs.dlgEditField;
        },

        /**
         * @property {boolean} isCheckbox Whether the current field type is a checkbox
         */
        isCheckbox() {
            return this.fieldStore.type === FIELD_TYPE.CHECKBOX;
        },

        /**
         * @property {boolean} isDate Whether the current field type is a date
         */
        isDate() {
            return this.fieldStore.type === FIELD_TYPE.DATE;
        },

        /**
         * @property {boolean} isDecimal Whether the current field type is a decimal
         */
        isDecimal() {
            return this.fieldStore.type === FIELD_TYPE.DECIMAL;
        },

        /**
         * @property {boolean} isDuration Whether the current field type is a duration
         */
        isDuration() {
            return this.fieldStore.type === FIELD_TYPE.DURATION;
        },

        /**
         * @property {boolean} isList Whether the current field type is a list
         */
        isList() {
            return this.fieldStore.type === FIELD_TYPE.LIST;
        },

        /**
         * @property {boolean} isNumber Whether the current field type is a number
         */
        isNumber() {
            return this.fieldStore.type === FIELD_TYPE.NUMBER;
        },

        /**
         * @property {boolean} isString Whether the current field type is a string
         */
        isString() {
            return this.fieldStore.type === FIELD_TYPE.STRING;
        },

        /**
         * @property {boolean} isText Whether the current field type is a text
         */
        isText() {
            return this.fieldStore.type === FIELD_TYPE.TEXT;
        }
    },

    methods: {
        /**
         * Opens "Edit field" dialog.
         */
        openEditFieldDialog() {
            const defaults = {
                name: this.fieldStore.name,
                type: this.fieldStore.type,
                description: this.fieldStore.description,
                required: this.fieldStore.isRequired,
                length: this.fieldStore.parameters.length ?? null,
                minimum: this.fieldStore.parameters.minimum ?? null,
                maximum: this.fieldStore.parameters.maximum ?? null,
                default: this.fieldStore.parameters.default ?? null
            };

            this.errors = {};
            this.editFieldDialog.open(defaults);
        },

        /**
         * Updates field.
         *
         * @param {Object} event Submitted values
         */
        async updateField(event) {
            const data = {
                name: event.name,
                description: event.description || null,
                required: event.required,
                parameters: event.parameters
            };

            ui.block();

            try {
                await axios.put(url(`/api/fields/${this.fieldStore.fieldId}`), data);

                this.$emit('update', this.fieldStore.fieldId);

                msg.info(this.i18n['text.changes_saved'], () => {
                    this.editFieldDialog.close();
                });
            } catch (exception) {
                this.errors = parseErrors(exception);
            } finally {
                ui.unblock();
            }
        },

        /**
         * Deletes the field.
         */
        deleteField() {
            msg.confirm(this.i18n['confirm.field.delete'], async () => {
                ui.block();

                try {
                    await axios.delete(url(`/api/fields/${this.fieldStore.fieldId}`));

                    this.$emit('delete', this.fieldStore.fieldId);
                } catch (exception) {
                    parseErrors(exception);
                } finally {
                    ui.unblock();
                }
            });
        }
    }
};
