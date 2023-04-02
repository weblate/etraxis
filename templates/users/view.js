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

import { useProfileStore } from './stores/ProfileStore';

import ProfileTab from './tabs/ProfileTab.vue';

/**
 * "View user" page.
 */
const app = createApp({
    data: () => ({
        /**
         * @property {string} tab ID of the current tab
         */
        tab: 'profile'
    }),

    computed: {
        /**
         * @property {Object} profileStore Store for user profile data
         */
        ...mapStores(useProfileStore)
    },

    mounted() {
        const userId = Number(this.$el.dataset.id);

        this.profileStore.loadProfile(userId);
    }
});

const pinia = createPinia();

app.component('tabs', Tabs);
app.component('tab', Tab);
app.component('profile-tab', ProfileTab);

app.use(pinia);
app.mount('#vue-user');
