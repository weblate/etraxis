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

import { useTemplateStore } from '../stores/TemplateStore';

/**
 * "Template" tab.
 */
export default {
    computed: {
        /**
         * @property {Object} templateStore Store for template data
         */
        ...mapStores(useTemplateStore),

        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n
    }
};
