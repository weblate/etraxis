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

import axios from 'axios';

import AccountProviderEnum from '@enums/accountprovider';
import LocaleEnum from '@enums/locale';

import * as ui from '@utilities/blockui';
import * as msg from '@utilities/messagebox';
import parseErrors from '@utilities/parseErrors';
import url from '@utilities/url';

import { useProfileStore } from '../stores/ProfileStore';

import EditUserDialog from '../dialogs/EditUserDialog.vue';
import SetPasswordDialog from '../dialogs/SetPasswordDialog.vue';

/**
 * "Profile" tab.
 */
export default {
    components: {
        'edit-user-dialog': EditUserDialog,
        'set-password-dialog': SetPasswordDialog
    },

    data: () => ({
        /**
         * @property {number} currentUser ID of the current user
         */
        currentUser: null,

        /**
         * @property {Array<string>} timezones List of all available timezones
         */
        timezones: [],

        /**
         * @property {Object} errors Dialog errors
         */
        errors: {}
    }),

    computed: {
        /**
         * @property {Object} profileStore Store for user profile data
         */
        ...mapStores(useProfileStore),

        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {Object} accountProviders Supported account providers
         */
        accountProviders: () => AccountProviderEnum,

        /**
         * @property {Object} locales Available locales
         */
        locales: () => LocaleEnum,

        /**
         * @property {boolean} isCurrentUser Whether the user is the current one
         */
        isCurrentUser() {
            return this.profileStore.userId === this.currentUser;
        },

        /**
         * @property {Object} editUserDialog "Edit user" dialog instance
         */
        editUserDialog() {
            return this.$refs.dlgEditUser;
        },

        /**
         * @property {Object} setPasswordDialog "Set password" dialog instance
         */
        setPasswordDialog() {
            return this.$refs.dlgSetPassword;
        }
    },

    methods: {
        /**
         * Redirects back to the users list.
         */
        goBack() {
            location.href = url('/admin/users');
        },

        /**
         * Opens "Edit user" dialog.
         */
        openEditUserDialog() {
            const defaults = {
                email: this.profileStore.email,
                fullname: this.profileStore.fullname,
                description: this.profileStore.description,
                admin: this.profileStore.isAdmin,
                disabled: this.profileStore.isDisabled,
                locale: this.profileStore.locale,
                timezone: this.profileStore.timezone
            };

            this.errors = {};
            this.editUserDialog.open(this.isCurrentUser, this.profileStore.isExternalUser, defaults);
        },

        /**
         * Updates user.
         *
         * @param {Object} event Submitted values
         */
        updateUser(event) {
            const data = {
                email: event.email,
                fullname: event.fullname,
                description: event.description || null,
                admin: event.admin,
                disabled: event.disabled,
                locale: event.locale,
                timezone: event.timezone
            };

            ui.block();

            axios
                .put(url(`/api/users/${this.profileStore.userId}`), data)
                .then(() => {
                    this.profileStore.loadProfile();
                    msg.info(this.i18n['text.changes_saved']).then(() => {
                        this.editUserDialog.close();
                    });
                })
                .catch((exception) => (this.errors = parseErrors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Toggles the user's status.
         */
        toggleStatus() {
            ui.block();

            axios
                .post(url(`/api/users/${this.profileStore.userId}/${this.profileStore.isDisabled ? 'enable' : 'disable'}`))
                .then(() => this.profileStore.loadProfile())
                .catch((exception) => parseErrors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Opens "Set password" dialog.
         */
        openSetPasswordDialog() {
            this.errors = {};
            this.setPasswordDialog.open();
        },

        /**
         * Sets new password for the user.
         *
         * @param {Object} event Submitted values
         */
        setPassword(event) {
            const data = {
                password: event
            };

            ui.block();

            axios
                .put(url(`/api/users/${this.profileStore.userId}/password`), data)
                .then(() => {
                    msg.info(this.i18n['password.changed']).then(() => {
                        this.setPasswordDialog.close();
                    });
                })
                .catch((exception) => (this.errors = parseErrors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Deletes the user.
         */
        deleteUser() {
            msg.confirm(this.i18n['confirm.user.delete']).then(() => {
                ui.block();

                axios
                    .delete(url(`/api/users/${this.profileStore.userId}`))
                    .then(() => this.goBack())
                    .catch((exception) => parseErrors(exception))
                    .then(() => ui.unblock());
            });
        }
    },

    created() {
        ui.block();

        axios
            .get(url('/timezones'))
            .then((response) => {
                this.timezones = Object.values(response.data)
                    .reduce((result, entry) => [...result, ...Object.keys(entry)], [])
                    .sort();
                this.timezones.unshift('UTC');
            })
            .catch((exception) => parseErrors(exception))
            .then(() => ui.unblock());
    },

    mounted() {
        this.currentUser = Number(this.$el.dataset.id);
    }
};
