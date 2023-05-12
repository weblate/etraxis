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

import { useProjectStore } from '../stores/ProjectStore';
import { useGroupStore } from '../../groups/stores/GroupStore';
import { useMembersStore } from '../../groups/stores/MembersStore';

import GroupTab from '../embedded-tabs/GroupTab.vue';
import MembersTab from '../../groups/tabs/MembersTab.vue';

/**
 * "Groups" tab.
 */
export default {
    components: {
        'group-tab': GroupTab,
        'members-tab': MembersTab
    },

    data: () => ({
        /**
         * @property {string} tab ID of the current tab
         */
        tab: 'group',

        /**
         * @property {number} groupId ID of the selected group
         */
        groupId: null
    }),

    computed: {
        /**
         * @property {Object} projectStore Store for project data
         * @property {Object} groupStore   Store for group data
         * @property {Object} membersStore Store for group members
         */
        ...mapStores(useProjectStore, useGroupStore, useMembersStore),

        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n
    },

    methods: {
        /**
         * One of the groups is updated.
         *
         * @param {number} id Group ID
         */
        async onGroupUpdated(id) {
            const promises = [];

            if (id === this.groupId) {
                promises.push(this.groupStore.loadGroup());
            }

            if (this.groupStore.isGlobal) {
                promises.push(this.projectStore.loadAllGlobalGroups());
            } else {
                promises.push(this.projectStore.loadAllProjectGroups());
            }

            await Promise.all(promises);
        },

        /**
         * One of the groups is deleted.
         *
         * @param {number} id Group ID
         */
        async onGroupDeleted(id) {
            if (id === this.groupId) {
                this.groupId = null;
            }

            if (this.groupStore.isGlobal) {
                await this.projectStore.loadAllGlobalGroups();
            } else {
                await this.projectStore.loadAllProjectGroups();
            }
        }
    },

    watch: {
        /**
         * Loads group information from the server when another group is selected.
         *
         * @param {number} value ID of the selected group
         */
        groupId(value) {
            if (value) {
                this.groupStore.loadGroup(value);
                this.membersStore.loadGroupMembers(value);
            }
        }
    },

    mounted() {
        this.projectStore.loadAllGlobalGroups();
        this.membersStore.loadAllUsers();
    }
};
