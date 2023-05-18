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

import { defineStore } from 'pinia';

import axios from 'axios';

import * as ui from '@utilities/blockui';
import parseErrors from '@utilities/parseErrors';
import url from '@utilities/url';

/**
 * Store for template data.
 */
export const useTemplateStore = defineStore('template', {
    state: () => ({
        /**
         * @property {Object} template Template data
         */
        template: {
            id: null,
            name: null,
            prefix: null,
            description: null,
            locked: null,
            criticalAge: null,
            frozenTime: null,
            project: null,
            actions: {
                update: false,
                delete: false,
                lock: false,
                unlock: false
            }
        }
    }),

    getters: {
        /**
         * @property {number} templateId Template ID
         * @param {Object} state
         */
        templateId: (state) => state.template.id,

        /**
         * @property {string} name Template name
         * @param {Object} state
         */
        name: (state) => state.template.name,

        /**
         * @property {string} prefix Template prefix
         * @param {Object} state
         */
        prefix: (state) => state.template.prefix,

        /**
         * @property {string} description Description
         * @param {Object} state
         */
        description: (state) => state.template.description,

        /**
         * @property {boolean} isLocked Whether the template is locked
         * @param {Object} state
         */
        isLocked: (state) => state.template.locked,

        /**
         * @property {number} criticalAge Critical age
         * @param {Object} state
         */
        criticalAge: (state) => state.template.criticalAge,

        /**
         * @property {number} frozenTime Frozen time
         * @param {Object} state
         */
        frozenTime: (state) => state.template.frozenTime,

        /**
         * @property {Object} project Project of the template
         * @param {Object} state
         */
        project: (state) => state.template.project,

        /**
         * @property {boolean} canUpdate Whether the template can be updated
         * @param {Object} state
         */
        canUpdate: (state) => state.template.actions.update,

        /**
         * @property {boolean} canDelete Whether the template can be deleted
         * @param {Object} state
         */
        canDelete: (state) => state.template.actions.delete,

        /**
         * @property {boolean} canLock Whether the template can be locked
         * @param {Object} state
         */
        canLock: (state) => state.template.actions.lock,

        /**
         * @property {boolean} canUnlock Whether the template can be unlocked
         * @param {Object} state
         */
        canUnlock: (state) => state.template.actions.unlock
    },

    actions: {
        /**
         * Loads template data from the server.
         *
         * @param {null|number} id Template ID
         */
        async loadTemplate(id = null) {
            ui.block();

            try {
                const response = await axios.get(url(`/api/templates/${id || this.template.id}`));
                this.template = { ...response.data };
            } catch (exception) {
                parseErrors(exception);
            } finally {
                ui.unblock();
            }
        }
    }
});
