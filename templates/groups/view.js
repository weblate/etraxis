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

import GroupTab from './tabs/GroupTab.vue';

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
         * @property {Object} groupStore Store for group data
         */
        ...mapStores(useGroupStore)
    },

    mounted() {
        const groupId = Number(this.$el.dataset.id);

        this.groupStore.loadGroup(groupId);
    }
});

const pinia = createPinia();

app.component('tabs', Tabs);
app.component('tab', Tab);
app.component('group-tab', GroupTab);

app.use(pinia);
app.mount('#vue-group');
