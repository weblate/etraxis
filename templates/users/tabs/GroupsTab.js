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

import { useGroupsStore } from '../stores/GroupsStore';

import './GroupsTab.scss';

/**
 * "Groups" tab.
 */
export default {
    data: () => ({
        /**
         * @property {Array<number>} groupsToAdd Groups selected to add (IDs)
         */
        groupsToAdd: [],

        /**
         * @property {Array<number>} groupsToRemove Groups selected to remove (IDs)
         */
        groupsToRemove: []
    }),

    computed: {
        /**
         * @property {Object} groupsStore Store for user groups
         */
        ...mapStores(useGroupsStore),

        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n
    },

    methods: {
        /**
         * Adds the user to selected groups.
         */
        addGroups() {
            ui.block();

            const data = {
                add: this.groupsToAdd
            };

            this.groupsToAdd = [];

            axios.patch(url(`/api/users/${this.groupsStore.userId}/groups`), data)
                .then(() => this.groupsStore.loadUserGroups())
                .then(() => (this.groupsToAdd = []))
                .catch((exception) => parseErrors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Removes the user from selected groups.
         */
        removeGroups() {
            ui.block();

            const data = {
                remove: this.groupsToRemove
            };

            axios.patch(url(`/api/users/${this.groupsStore.userId}/groups`), data)
                .then(() => this.groupsStore.loadUserGroups())
                .then(() => (this.groupsToRemove = []))
                .catch((exception) => parseErrors(exception))
                .then(() => ui.unblock());
        }
    }
};
