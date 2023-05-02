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

import * as ui from '@utilities/blockui';
import parseErrors from '@utilities/parseErrors';
import url from '@utilities/url';

import { useMembersStore } from '../stores/MembersStore';

import './MembersTab.scss';

/**
 * "Groups" tab.
 */
export default {
    data: () => ({
        /**
         * @property {Array<number>} usersToAdd Users selected to add (IDs)
         */
        usersToAdd: [],

        /**
         * @property {Array<number>} usersToRemove Users selected to remove (IDs)
         */
        usersToRemove: []
    }),

    computed: {
        /**
         * @property {Object} membersStore Store for group members
         */
        ...mapStores(useMembersStore),

        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n
    },

    methods: {
        /**
         * Adds selected users to the group.
         */
        async addUsers() {
            ui.block();

            const data = {
                add: this.usersToAdd
            };

            try {
                await axios.patch(url(`/api/groups/${this.membersStore.groupId}/members`), data);
                await this.membersStore.loadGroupMembers();
                this.usersToAdd = [];
            } catch (exception) {
                parseErrors(exception);
            } finally {
                ui.unblock();
            }
        },

        /**
         * Removes selected users from the group.
         */
        async removeUsers() {
            ui.block();

            const data = {
                remove: this.usersToRemove
            };

            try {
                await axios.patch(url(`/api/groups/${this.membersStore.groupId}/members`), data);
                await this.membersStore.loadGroupMembers();
                this.usersToRemove = [];
            } catch (exception) {
                parseErrors(exception);
            } finally {
                ui.unblock();
            }
        }
    }
};
