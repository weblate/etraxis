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
import loadAll from '@utilities/loadAll';
import parseErrors from '@utilities/parseErrors';
import url from '@utilities/url';

/**
 * Store for group members.
 */
export const useMembersStore = defineStore('members', {
    state: () => ({
        /**
         * @property {number} groupId Group ID
         */
        groupId: null,

        /**
         * @property {Array<Object>} allUsers List of all existing users
         */
        allUsers: [],

        /**
         * @property {Array<Object>} groupMembers List of current group members
         */
        groupMembers: []
    }),

    getters: {
        /**
         * @property {Array<Object>} otherUsers List of users who are NOT members of the group
         * @param {Object} state
         */
        otherUsers: (state) => {
            const ids = state.groupMembers.map((user) => user.id);

            return state.allUsers.filter((user) => !ids.includes(user.id));
        }
    },

    actions: {
        /**
         * Loads members of the group.
         *
         * @param {null|number} id Group ID
         */
        async loadGroupMembers(id = null) {
            ui.block();

            if (id) {
                this.groupId = id;
            }

            try {
                const response = await axios.get(url(`/api/groups/${this.groupId}/members`));

                /** @var {{ fullname: string, email: string }} user1 */
                /** @var {{ fullname: string, email: string }} user2 */
                this.groupMembers = response.data.sort((user1, user2) =>
                    user1.fullname !== user2.fullname
                        ? user1.fullname.localeCompare(user2.fullname)
                        : user1.email.localeCompare(user2.email)
                );
            } catch (exception) {
                parseErrors(exception);
            } finally {
                ui.unblock();
            }
        },

        /**
         * Loads all existing users.
         */
        async loadAllUsers() {
            ui.block();
            this.allUsers = await loadAll(url('/api/users'), null, { fullname: 'asc', email: 'asc' });
            ui.unblock();
        }
    }
});
