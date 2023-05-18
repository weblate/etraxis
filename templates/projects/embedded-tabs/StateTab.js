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

import { useStateStore } from '../stores/StateStore';

/**
 * "State" tab.
 */
export default {
    computed: {
        /**
         * @property {Object} stateStore Store for state data
         */
        ...mapStores(useStateStore),

        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {Object} stateTypes List of possible state types
         */
        stateTypes: () => ({
            initial: window.i18n['state.initial'],
            intermediate: window.i18n['state.intermediate'],
            final: window.i18n['state.final']
        }),

        /**
         * @property {Object} stateResponsibles List of possible state responsibility values
         */
        stateResponsibles: () => ({
            assign: window.i18n['state.responsible.assign'],
            keep: window.i18n['state.responsible.keep'],
            remove: window.i18n['state.responsible.remove']
        })
    }
};
