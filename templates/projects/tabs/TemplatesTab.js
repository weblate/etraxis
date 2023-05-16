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

import Tree from '@components/tree/tree.vue';

import { useProjectStore } from '../stores/ProjectStore';

/**
 * "Templates" tab.
 */
export default {
    components: {
        tree: Tree
    },

    computed: {
        /**
         * @property {Object} projectStore Store for project data
         */
        ...mapStores(useProjectStore)
    }
};
