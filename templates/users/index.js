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

import AccountProviderEnum from '@enums/accountprovider';

import * as ui from '@utilities/blockui';
import * as msg from '@utilities/messagebox';
import parseErrors from '@utilities/parseErrors';
import query from '@utilities/query';
import url from '@utilities/url';

import DataTable from '@components/datatable/datatable.vue';
import Column from '@components/datatable/column.vue';
import Icon from '@components/datatable/icon';

import NewUserDialog from './NewUserDialog.vue';
import EditUserDialog from './EditUserDialog.vue';

const ICON_IMPERSONATE = 'impersonate';
const ICON_UPDATE = 'update';
const ICON_DISABLE = 'disable';
const ICON_ENABLE = 'enable';

/**
 * "Users" page.
 */
const app = createApp({
    data: () => ({
        /**
         * @property {Array<string>} timezones List of all available timezones
         */
        timezones: [],

        /**
         * @property {Array<number>} checked List of selected (checked) account IDs
         */
        checked: [],

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
         * @property {Object} permissions List of possible account permissions
         */
        permissions: () => ({
            true: window.i18n['role.admin'],
            false: window.i18n['role.user']
        }),

        /**
         * @property {Object} providers List of possible account providers
         */
        providers: () => AccountProviderEnum,

        /**
         * @property {number} currentUser ID of the current user
         */
        currentUser() {
            return Number(this.$el.dataset.id);
        },

        /**
         * @property {string} defaultLocale Default locale
         */
        defaultLocale() {
            return this.$el.dataset.locale;
        },

        /**
         * @property {string} defaultTimezone Default timezone
         */
        defaultTimezone() {
            return this.$el.dataset.timezone;
        },

        /**
         * @property {Object} usersTable DataTable instance
         */
        usersTable() {
            return this.$refs.users;
        },

        /**
         * @property {Object} newUserDialog "New user" dialog instance
         */
        newUserDialog() {
            return this.$refs.dlgNewUser;
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
         * Data provider for the table.
         *
         * @param {number} offset  Zero-based index of the first entry to return
         * @param {number} limit   Maximum number of entries to return
         * @param {string} search  Current value of the search
         * @param {Object} filters Current values of the column filters ({ "column id": value })
         * @param {Object} order   Current sorting order ({ "column id": "asc"|"desc" })
         *
         * @return {Object} Table data
         */
        async users(offset, limit, search, filters, order) {
            let data = await query(url('/api/users'), offset, limit, search, filters, order);

            return {
                total: data.total,
                rows: data.rows.map((user) => {
                    let icons = [
                        new Icon(ICON_IMPERSONATE, this.i18n['user.impersonate'], 'fa-user-circle-o', user.id === this.currentUser),
                        new Icon(ICON_UPDATE, this.i18n['user.edit'], 'fa-pencil'),
                        user.disabled
                            ? new Icon(ICON_ENABLE, this.i18n['button.enable'], 'fa-toggle-off')
                            : new Icon(ICON_DISABLE, this.i18n['button.disable'], 'fa-toggle-on', user.id === this.currentUser)
                    ];

                    return {
                        DT_id: user.id,
                        DT_class: user.disabled ? 'has-text-grey' : null,
                        DT_checkable: user.id !== this.currentUser,
                        DT_icons: icons,
                        fullname: user.fullname,
                        email: user.email,
                        admin: user.admin ? this.i18n['role.admin'] : this.i18n['role.user'],
                        accountProvider: this.providers[user.accountProvider],
                        description: user.description
                    };
                })
            };
        },

        /**
         * An icon is clicked.
         *
         * @param {MouseEvent} event Original event
         * @param {number}     id    Account ID
         * @param {string}     icon  Icon ID
         */
        onIcon(event, id, icon) {
            switch (icon) {
                case ICON_IMPERSONATE:
                    this.impersonateUser(id);
                    break;
                case ICON_UPDATE:
                    this.openEditUserDialog(id);
                    break;
                case ICON_DISABLE:
                    this.disableUser(id);
                    break;
                case ICON_ENABLE:
                    this.enableUser(id);
                    break;
            }
        },

        /**
         * A table row is clicked.
         *
         * @param {MouseEvent} event Original event
         * @param {number}     id    Account ID
         */
        viewUser(event, id) {
            if (event.ctrlKey) {
                window.open(url(`/admin/users/${id}`), '_blank');
            } else {
                location.href = url(`/admin/users/${id}`);
            }
        },

        /**
         * Impersonates specified account.
         *
         * @param {number} id Account ID
         */
        impersonateUser(id) {
            if (id !== this.currentUser) {
                ui.block();

                axios
                    .get(url(`/api/users/${id}`))
                    .then((response) => {
                        location.href = url(`/?_switch_user=${response.data.email}`);
                    })
                    .catch((exception) => parseErrors(exception))
                    .then(() => ui.unblock());
            }
        },

        /**
         * Opens "New user" dialog.
         */
        openNewUserDialog() {
            let defaults = {
                email: '',
                password: '',
                fullname: '',
                description: '',
                admin: false,
                disabled: false,
                locale: this.defaultLocale,
                timezone: this.defaultTimezone
            };

            this.errors = {};

            this.newUserDialog.open(defaults);
        },

        /**
         * Creates new user.
         *
         * @param {Object} event Submitted values
         */
        createUser(event) {
            let data = {
                email: event.email,
                password: event.password,
                fullname: event.fullname,
                description: event.description || null,
                admin: event.admin,
                disabled: event.disabled,
                locale: event.locale,
                timezone: event.timezone
            };

            ui.block();

            axios
                .post(url('/api/users'), data)
                .then(() => {
                    msg.info(this.i18n['user.successfully_created']).then(() => {
                        this.newUserDialog.close();
                        this.usersTable.refresh();
                    });
                })
                .catch((exception) => (this.errors = parseErrors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Opens "Edit user" dialog.
         *
         * @param {number} id Account ID
         */
        openEditUserDialog(id) {
            ui.block();

            axios
                .get(url(`/api/users/${id}`))
                .then((response) => {
                    this.errors = {};
                    this.editUserDialog.open(id === this.currentUser, response.data.accountProvider !== 'etraxis', response.data);
                })
                .catch((exception) => parseErrors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Updates user.
         *
         * @param {Object} event Submitted values
         */
        updateUser(event) {
            let data = {
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
                .put(url(`/api/users/${event.id}`), data)
                .then(() => {
                    msg.info(this.i18n['text.changes_saved']).then(() => {
                        this.editUserDialog.close();
                        this.usersTable.refresh();
                    });
                })
                .catch((exception) => (this.errors = parseErrors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Disables specified account.
         *
         * @param {number} id Account ID
         */
        disableUser(id) {
            ui.block();

            axios
                .post(url(`/api/users/${id}/disable`))
                .then(() => this.usersTable.refresh())
                .catch((exception) => parseErrors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Enables specified account.
         *
         * @param {number} id Account ID
         */
        enableUser(id) {
            ui.block();

            axios
                .post(url(`/api/users/${id}/enable`))
                .then(() => this.usersTable.refresh())
                .catch((exception) => parseErrors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Disables currently checked accounts.
         */
        disableMultipleUsers() {
            ui.block();

            axios
                .post(url('/api/users/disable'), { users: this.checked })
                .then(() => this.usersTable.refresh())
                .catch((exception) => parseErrors(exception))
                .then(() => ui.unblock());
        },

        /**
         * Enables currently checked accounts.
         */
        enableMultipleUsers() {
            ui.block();

            axios
                .post(url('/api/users/enable'), { users: this.checked })
                .then(() => this.usersTable.refresh())
                .catch((exception) => parseErrors(exception))
                .then(() => ui.unblock());
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
    }
});

app.component('datatable', DataTable);
app.component('column', Column);
app.component('new-user-dialog', NewUserDialog);
app.component('edit-user-dialog', EditUserDialog);

app.mount('#vue-users');
