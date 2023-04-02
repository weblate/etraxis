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

const QUERY_LIMIT = 100;

/**
 * Store for user groups.
 */
export const useGroupsStore = defineStore('groups', {
    state: () => ({
        /**
         * @property {number} userId User ID
         */
        userId: null,

        /**
         * @property {Array<Object>} allGroups List of all existing groups
         */
        allGroups: [],

        /**
         * @property {Array<Object>} userGroups List of groups the user is a member of
         */
        userGroups: []
    }),

    getters: {
        /**
         * @property {Array<Object>} otherGroups List of groups the user is NOT a member of
         * @param {Object} state
         */
        otherGroups: (state) => {
            const ids = state.userGroups.map((group) => group.id);

            return state.allGroups.filter((group) => !ids.includes(group.id));
        }
    },

    actions: {
        /**
         * Loads groups the user is a member of.
         *
         * @param {null|number} id User ID
         */
        loadUserGroups(id = null) {
            ui.block();

            if (id) {
                this.userId = id;
            }

            axios
                .get(url(`/api/users/${this.userId}/groups`))
                .then((response) => {
                    /** @var {{ project: { name: string } }} group1 */
                    /** @var {{ project: { name: string } }} group2 */
                    this.userGroups = response.data.sort((group1, group2) =>
                        (group1.project === null && group2.project !== null ? -1 : 0) ||
                        (group1.project !== null && group2.project === null ? +1 : 0) ||
                        (group1.project !== null && group2.project !== null && group1.project.name.localeCompare(group2.project.name)) ||
                        (group1.name.localeCompare(group2.name))
                    );
                })
                .catch((exception) => parseErrors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Loads all existing groups.
         *
         * @param {number} offset Skip this number of groups
         */
        loadAllGroups(offset = 0) {
            ui.block();

            if (offset === 0) {
                this.allGroups = [];
            }

            axios
                .get(url('/api/groups'), { params: { offset, limit: QUERY_LIMIT } })
                .then((response) => {
                    for (const group of response.data.items) {
                        this.allGroups.push(group);
                    }

                    if (offset + QUERY_LIMIT < response.data.total) {
                        this.loadAllGroups(offset + QUERY_LIMIT);
                    }
                })
                .catch((exception) => parseErrors(exception))
                .then(() => ui.unblock());
        }
    }
});
