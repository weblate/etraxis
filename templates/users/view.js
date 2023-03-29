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

import axios from 'axios';

import * as ui from '@utilities/blockui';
import parseErrors from '@utilities/parseErrors';
import url from '@utilities/url';

import Tabs from '@components/tabs/tabs.vue';
import Tab from '@components/tabs/tab.vue';

import ProfileTab from './ProfileTab.vue';

/**
 * "View user" page.
 */
const app = createApp({
    data: () => ({
        /**
         * @property {string} tab ID of the current tab
         */
        tab: 'profile',

        /**
         * @property {Object} profile User's profile
         */
        profile: {
            id: null,
            email: null,
            fullname: null,
            description: null,
            admin: null,
            disabled: null,
            accountProvider: null,
            locale: null,
            timezone: null,
            actions: {
                update: false,
                delete: false,
                disable: false,
                enable: false
            }
        }
    }),

    computed: {
        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {number} userId User ID
         */
        userId() {
            return Number(this.$el.dataset.id);
        }
    },

    methods: {
        /**
         * Loads user's profile from the server.
         */
        loadProfile() {
            ui.block();

            axios
                .get(url(`/api/users/${this.userId}`))
                .then((response) => {
                    this.profile = { ...response.data };
                })
                .catch((exception) => parseErrors(exception))
                .then(() => ui.unblock());
        }
    },

    mounted() {
        this.loadProfile();
    }
});

app.component('tabs', Tabs);
app.component('tab', Tab);
app.component('profile-tab', ProfileTab);

app.mount('#vue-user');
