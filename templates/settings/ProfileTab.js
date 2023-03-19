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

import axios from "axios";

import AccountProviderEnum from "@enums/accountprovider";
import LocaleEnum from "@enums/locale";
import ThemeEnum from "@enums/theme";

import * as ui from "@utilities/blockui";
import * as msg from "@utilities/messagebox";
import parseErrors from "@utilities/parseErrors";
import url from "@utilities/url";

import ProfileDialog from "./ProfileDialog.vue";
import PasswordDialog from "./PasswordDialog.vue";

/**
 * "Profile" tab.
 */
export default {
    components: {
        "profile-dialog": ProfileDialog,
        "password-dialog": PasswordDialog
    },

    data: () => ({
        /**
         * @property {Object} profile User's profile
         */
        profile: {
            email: null,
            fullname: null,
            accountProvider: null,
            locale: null,
            theme: null,
            timezone: null
        },

        /**
         * @property {Object} timezones List of all available timezones, grouped by country
         */
        timezones: {},

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
         * @property {Object} themes Available themes
         */
        themes: () => ThemeEnum,

        /**
         * @property {Object} profileDialog "Profile" dialog instance
         */
        profileDialog() {
            return this.$refs.dlgProfile;
        },

        /**
         * @property {Object} passwordDialog "Change password" dialog instance
         */
        passwordDialog() {
            return this.$refs.dlgPassword;
        },

        /**
         * @property {boolean} isExternal Whether the user's account is external
         */
        isExternal() {
            return this.profile.accountProvider !== "etraxis";
        }
    },

    methods: {
        /**
         * Opens "Profile" dialog.
         */
        openProfileDialog() {
            this.errors = {};
            this.profileDialog.open(this.profile);
        },

        /**
         * Updates user's profile.
         *
         * @param {Object} event Submitted values
         */
        updateProfile(event) {
            let data = {
                email: event.email,
                fullname: event.fullname,
                locale: event.locale,
                theme: event.theme,
                timezone: event.timezone
            };

            ui.block();

            axios
                .patch(url("/api/my/profile"), data)
                .then(() => {
                    msg.info(this.i18n["text.changes_saved"]).then(() => {
                        this.profileDialog.close();
                        location.reload();
                    });
                })
                .catch((exception) => (this.errors = parseErrors(exception)))
                .then(() => ui.unblock());
        },

        /**
         * Opens "Change password" dialog.
         */
        openPasswordDialog() {
            this.errors = {};
            this.passwordDialog.open();
        },

        /**
         * Changes user's password.
         *
         * @param {Object} event Submitted values
         */
        updatePassword(event) {
            if (event.new !== event.confirmation) {
                this.errors = {
                    confirmation: this.i18n["password.dont_match"]
                };
            } else {
                let data = {
                    current: event.current,
                    new: event.new
                };

                ui.block();

                axios
                    .put(url("/api/my/password"), data)
                    .then(() => {
                        msg.info(this.i18n["password.changed"]).then(() => {
                            ui.block();
                            location.href = url("/logout");
                        });
                    })
                    .catch((exception) => (this.errors = parseErrors(exception)))
                    .then(() => ui.unblock());
            }
        }
    },

    created() {
        const urls = [url("/api/my/profile"), url("/timezones")];

        ui.block();

        axios
            .all(urls.map((endpoint) => axios.get(endpoint)))
            .then(
                axios.spread((profile, timezones) => {
                    this.profile = { ...profile.data };
                    this.timezones = { ...timezones.data };
                })
            )
            .then(() => ui.unblock());
    }
};
