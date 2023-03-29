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

import axios from 'axios';

import AccountProviderEnum from '@enums/accountprovider';
import LocaleEnum from '@enums/locale';

import * as ui from '@utilities/blockui';
import * as msg from '@utilities/messagebox';
import parseErrors from '@utilities/parseErrors';
import url from '@utilities/url';

import EditUserDialog from './EditUserDialog.vue';

/**
 * "Profile" tab.
 */
export default {
    props: {
        id: Number,
        email: String,
        fullname: String,
        description: String,
        admin: Boolean,
        disabled: Boolean,
        accountProvider: String,
        locale: String,
        timezone: String,
        actions: Object
    },

    emits: ['update:profile'],

    components: {
        'edit-user-dialog': EditUserDialog
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
         * @property {boolean} canUpdate Whether the user can be updated
         */
        canUpdate() {
            return this.actions.update;
        },

        /**
         * @property {boolean} canDelete Whether the user can be deleted
         */
        canDelete() {
            return this.actions.delete;
        },

        /**
         * @property {boolean} canDisable Whether the user can be disabled
         */
        canDisable() {
            return this.actions.disable;
        },

        /**
         * @property {boolean} canEnable Whether the user can be enabled
         */
        canEnable() {
            return this.actions.enable;
        },

        /**
         * @property {boolean} isCurrentUser Whether the user is the current one
         */
        isCurrentUser() {
            return this.id === this.currentUser;
        },

        /**
         * @property {Object} editUserDialog "Edit user" dialog instance
         */
        editUserDialog() {
            return this.$refs.dlgEditUser;
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
                email: this.email,
                fullname: this.fullname,
                description: this.description,
                admin: this.admin,
                disabled: this.disabled,
                locale: this.locale,
                timezone: this.timezone
            };

            this.errors = {};
            this.editUserDialog.open(this.isCurrentUser, this.accountProvider !== 'etraxis', defaults);
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
                .put(url(`/api/users/${this.id}`), data)
                .then(() => {
                    msg.info(this.i18n['text.changes_saved']).then(() => {
                        this.editUserDialog.close();
                        this.$emit('update:profile');
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
                .post(url(`/api/users/${this.id}/${this.disabled ? 'enable' : 'disable'}`))
                .then(() => this.$emit('update:profile'))
                .catch((exception) => parseErrors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Deletes the user.
         */
        deleteUser() {
            msg.confirm(this.i18n['confirm.user.delete']).then(() => {
                ui.block();

                axios
                    .delete(url(`/api/users/${this.id}`))
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
