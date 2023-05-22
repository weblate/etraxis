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
 * Store for state data.
 */
export const useStateStore = defineStore('state', {
    state: () => ({
        /**
         * @property {Object} state State data
         */
        state: {
            id: null,
            name: null,
            type: null,
            responsible: null,
            template: null,
            actions: {
                update: false,
                delete: false,
                initial: false,
                transitions: false,
                groups: false
            }
        }
    }),

    getters: {
        /**
         * @property {number} stateId State ID
         * @param {Object} state
         */
        stateId: (state) => state.state.id,

        /**
         * @property {string} name State name
         * @param {Object} state
         */
        name: (state) => state.state.name,

        /**
         * @property {string} type State type
         * @param {Object} state
         */
        type: (state) => state.state.type,

        /**
         * @property {string} responsible State responsibility
         * @param {Object} state
         */
        responsible: (state) => state.state.responsible,

        /**
         * @property {Object} project Project of the state
         * @param {Object} state
         */
        project: (state) => state.state.template.project,

        /**
         * @property {Object} template Template of the state
         * @param {Object} state
         */
        template: (state) => state.state.template,

        /**
         * @property {boolean} isInitial Whether the state is initial.
         * @param {Object} state
         */
        isInitial: (state) => state.state.type === 'initial',

        /**
         * @property {boolean} canUpdate Whether the state can be updated
         * @param {Object} state
         */
        canUpdate: (state) => state.state.actions.update,

        /**
         * @property {boolean} canDelete Whether the state can be deleted
         * @param {Object} state
         */
        canDelete: (state) => state.state.actions.delete,

        /**
         * @property {boolean} canSetInitial Whether the state can be set as initial
         * @param {Object} state
         */
        canSetInitial: (state) => state.state.actions.initial,

        /**
         * @property {boolean} canManageTransitions Whether the state transitions can be updated
         * @param {Object} state
         */
        canManageTransitions: (state) => state.state.actions.transitions,

        /**
         * @property {boolean} canManageResponsibleGroups Whether the state responsible groups can be updated
         * @param {Object} state
         */
        canManageResponsibleGroups: (state) => state.state.actions.groups
    },

    actions: {
        /**
         * Loads state data from the server.
         *
         * @param {null|number} id State ID
         */
        async loadState(id = null) {
            ui.block();

            try {
                const response = await axios.get(url(`/api/states/${id || this.state.id}`));
                this.state = { ...response.data };
            } catch (exception) {
                parseErrors(exception);
            } finally {
                ui.unblock();
            }
        }
    }
});
