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

import * as FIELD_TYPE from '@const/fieldType';

import FieldTypeEnum from '@enums/fieldType';

import { useFieldStore } from '../stores/FieldStore';

/**
 * "Field" tab.
 */
export default {
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
    }
};
