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
 * Store for user profile data.
 */
export const useProfileStore = defineStore('profile', {
    state: () => ({
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

    getters: {
        /**
         * @property {number} userId User ID
         * @param {Object} state
         */
        userId: (state) => state.profile.id,

        /**
         * @property {string} email Email address
         * @param {Object} state
         */
        email: (state) => state.profile.email,

        /**
         * @property {string} fullname Full name
         * @param {Object} state
         */
        fullname: (state) => state.profile.fullname,

        /**
         * @property {string} description Description
         * @param {Object} state
         */
        description: (state) => state.profile.description,

        /**
         * @property {boolean} isAdmin Whether the user has administration privileges
         * @param {Object} state
         */
        isAdmin: (state) => state.profile.admin,

        /**
         * @property {boolean} isDisabled Whether the account is disabled
         * @param {Object} state
         */
        isDisabled: (state) => state.profile.disabled,

        /**
         * @property {string} accountProvider Account provider
         * @param {Object} state
         */
        accountProvider: (state) => state.profile.accountProvider,

        /**
         * @property {string} locale User's locale
         * @param {Object} state
         */
        locale: (state) => state.profile.locale,

        /**
         * @property {string} timezone User's timezone
         * @param {Object} state
         */
        timezone: (state) => state.profile.timezone,

        /**
         * @property {boolean} isExternalUser Whether the user is an external
         * @param {Object} state
         */
        isExternalUser: (state) => state.profile.accountProvider !== 'etraxis',

        /**
         * @property {boolean} canUpdate Whether the user can be updated
         * @param {Object} state
         */
        canUpdate: (state) => state.profile.actions.update,

        /**
         * @property {boolean} canDelete Whether the user can be deleted
         * @param {Object} state
         */
        canDelete: (state) => state.profile.actions.delete,

        /**
         * @property {boolean} canDisable Whether the user can be disabled
         * @param {Object} state
         */
        canDisable: (state) => state.profile.actions.disable,

        /**
         * @property {boolean} canEnable Whether the user can be enabled
         * @param {Object} state
         */
        canEnable: (state) => state.profile.actions.enable
    },

    actions: {
        /**
         * Loads user's profile from the server.
         *
         * @param {null|number} id User ID
         */
        async loadProfile(id = null) {
            ui.block();

            try {
                const response = await axios.get(url(`/api/users/${id || this.profile.id}`));
                this.profile = { ...response.data };
            } catch (exception) {
                parseErrors(exception);
            } finally {
                ui.unblock();
            }
        }
    }
});
