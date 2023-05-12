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

import { useProjectStore } from './stores/ProjectStore';

import ProjectTab from './tabs/ProjectTab.vue';
import GroupsTab from './tabs/GroupsTab.vue';

/**
 * "View project" page.
 */
const app = createApp({
    data: () => ({
        /**
         * @property {string} tab ID of the current tab
         */
        tab: 'project'
    }),

    computed: {
        /**
         * @property {Object} projectStore Store for project data
         */
        ...mapStores(useProjectStore)
    },

    mounted() {
        const projectId = Number(this.$el.dataset.id);

        this.projectStore.loadProject(projectId);
        this.projectStore.loadAllProjectGroups(projectId);
    }
});

const pinia = createPinia();

app.component('tabs', Tabs);
app.component('tab', Tab);
app.component('project-tab', ProjectTab);
app.component('groups-tab', GroupsTab);

app.use(pinia);
app.mount('#vue-project');
