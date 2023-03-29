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

import Tabs from '@components/tabs/tabs.vue';
import Tab from '@components/tabs/tab.vue';

import ProfileTab from './ProfileTab.vue';

/**
 * "Settings" page.
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
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n
    }
});

app.component('tabs', Tabs);
app.component('tab', Tab);
app.component('profile-tab', ProfileTab);

app.mount('#vue-settings');
