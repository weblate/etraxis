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
 * Store for group data.
 */
export const useGroupStore = defineStore('group', {
    state: () => ({
        /**
         * @property {Object} group Group data
         */
        group: {
            id: null,
            name: null,
            description: null,
            global: null,
            project: null,
            actions: {
                update: false,
                delete: false
            }
        }
    }),

    getters: {
        /**
         * @property {number} groupId Group ID
         * @param {Object} state
         */
        groupId: (state) => state.group.id,

        /**
         * @property {string} name Group name
         * @param {Object} state
         */
        name: (state) => state.group.name,

        /**
         * @property {string} description Description
         * @param {Object} state
         */
        description: (state) => state.group.description,

        /**
         * @property {boolean} isGlobal Whether the group is suspended
         * @param {Object} state
         */
        isGlobal: (state) => state.group.global,

        /**
         * @property {string} projectName Project of the group
         * @param {Object} state
         */
        projectName: (state) => state.group.project ? state.group.project.name : null,

        /**
         * @property {boolean} canUpdate Whether the group can be updated
         * @param {Object} state
         */
        canUpdate: (state) => state.group.actions.update,

        /**
         * @property {boolean} canDelete Whether the group can be deleted
         * @param {Object} state
         */
        canDelete: (state) => state.group.actions.delete
    },

    actions: {
        /**
         * Loads group data from the server.
         *
         * @param {null|number} id Group ID
         */
        async loadGroup(id = null) {
            ui.block();

            try {
                const response = await axios.get(url(`/api/groups/${id || this.group.id}`));
                this.group = { ...response.data };
            } catch (exception) {
                parseErrors(exception);
            } finally {
                ui.unblock();
            }
        }
    }
});
