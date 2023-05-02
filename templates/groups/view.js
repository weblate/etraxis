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

import { createApp } from 'vue';
import { createPinia, mapStores } from 'pinia';

import Tabs from '@components/tabs/tabs.vue';
import Tab from '@components/tabs/tab.vue';

import { useGroupStore } from './stores/GroupStore';
import { useMembersStore } from './stores/MembersStore';

import GroupTab from './tabs/GroupTab.vue';
import MembersTab from './tabs/MembersTab.vue';

/**
 * "View group" page.
 */
const app = createApp({
    data: () => ({
        /**
         * @property {string} tab ID of the current tab
         */
        tab: 'group'
    }),

    computed: {
        /**
         * @property {Object} groupStore   Store for group data
         * @property {Object} membersStore Store for group members
         */
        ...mapStores(useGroupStore, useMembersStore)
    },

    mounted() {
        const groupId = Number(this.$el.dataset.id);

        this.groupStore.loadGroup(groupId);
        this.membersStore.loadGroupMembers(groupId);
        this.membersStore.loadAllUsers();
    }
});

const pinia = createPinia();

app.component('tabs', Tabs);
app.component('tab', Tab);
app.component('group-tab', GroupTab);
app.component('members-tab', MembersTab);

app.use(pinia);
app.mount('#vue-group');
