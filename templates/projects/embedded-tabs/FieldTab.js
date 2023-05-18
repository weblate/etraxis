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
        fieldTypes: () => ({
            checkbox: window.i18n['field.checkbox'],
            date: window.i18n['field.date'],
            decimal: window.i18n['field.decimal'],
            duration: window.i18n['field.duration'],
            issue: window.i18n['field.issue'],
            list: window.i18n['field.list'],
            number: window.i18n['field.number'],
            string: window.i18n['field.string'],
            text: window.i18n['field.text']
        })
    }
};
