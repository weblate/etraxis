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

import url from '@utilities/url';

import { useGroupStore } from '../stores/GroupStore';

/**
 * "Group" tab.
 */
export default {
    computed: {
        /**
         * @property {Object} groupStore Store for group data
         */
        ...mapStores(useGroupStore),

        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n
    },

    methods: {
        /**
         * Redirects back to the groups list.
         */
        goBack() {
            location.href = url('/admin/groups');
        }
    }
};
