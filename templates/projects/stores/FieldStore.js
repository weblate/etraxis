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

import { defineStore } from 'pinia';

import axios from 'axios';

import * as ui from '@utilities/blockui';
import parseErrors from '@utilities/parseErrors';
import url from '@utilities/url';

/**
 * Store for field data.
 */
export const useFieldStore = defineStore('field', {
    state: () => ({
        /**
         * @property {Object} field Field data
         */
        field: {
            id: null,
            name: null,
            type: null,
            description: null,
            position: null,
            required: null,
            parameters: null,
            state: null,
            actions: {
                update: false,
                delete: false
            }
        }
    }),

    getters: {
        /**
         * @property {number} fieldId Field ID
         * @param {Object} state
         */
        fieldId: (state) => state.field.id,

        /**
         * @property {string} name Field name
         * @param {Object} state
         */
        name: (state) => state.field.name,

        /**
         * @property {string} type Field type
         * @param {Object} state
         */
        type: (state) => state.field.type,

        /**
         * @property {string} description Description
         * @param {Object} state
         */
        description: (state) => state.field.description,

        /**
         * @property {number} position Field position
         * @param {Object} state
         */
        position: (state) => state.field.position,

        /**
         * @property {boolean} isRequired Whether the field is required
         * @param {Object} state
         */
        isRequired: (state) => state.field.required,

        /**
         * @property {Object} parameters Field parameters
         * @param {Object} state
         */
        parameters: (state) => state.field.parameters,

        /**
         * @property {Object} project Project of the field
         * @param {Object} state
         */
        project: (state) => state.field.state.template.project,

        /**
         * @property {Object} template Template of the field
         * @param {Object} state
         */
        template: (state) => state.field.state.template,

        /**
         * @property {Object} state State of the field
         * @param {Object} state
         */
        state: (state) => state.field.state,

        /**
         * @property {boolean} canUpdate Whether the field can be updated
         * @param {Object} state
         */
        canUpdate: (state) => state.field.actions.update,

        /**
         * @property {boolean} canDelete Whether the field can be deleted
         * @param {Object} state
         */
        canDelete: (state) => state.field.actions.delete
    },

    actions: {
        /**
         * Loads field data from the server.
         *
         * @param {null|number} id Field ID
         */
        async loadField(id = null) {
            ui.block();

            try {
                const response = await axios.get(url(`/api/fields/${id || this.field.id}`));
                this.field = { ...response.data };
            } catch (exception) {
                parseErrors(exception);
            } finally {
                ui.unblock();
            }
        }
    }
});
