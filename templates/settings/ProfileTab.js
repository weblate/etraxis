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

/**
 * "Profile" tab.
 */
export default {
    components: {
        "profile-dialog": ProfileDialog
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
         * @property {Object} errors "Profile" dialog errors
         */
        errors: {}
    }),

    computed: {
        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {Object} profileDialog "Profile" dialog instance
         */
        profileDialog() {
            return this.$refs.dlgProfile;
        },

        /**
         * @property {null|string} locale User's human-readable account provider
         */
        accountProvider() {
            return AccountProviderEnum[this.profile.accountProvider] ?? null;
        },

        /**
         * @property {null|string} locale User's human-readable locale
         */
        locale() {
            return LocaleEnum[this.profile.locale] ?? null;
        },

        /**
         * @property {null|string} locale User's human-readable theme
         */
        theme() {
            return ThemeEnum[this.profile.theme] ?? null;
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
                    msg.info(i18n["text.changes_saved"]).then(() => {
                        this.profileDialog.close();
                        location.reload();
                    });
                })
                .catch((exception) => (this.errors = parseErrors(exception)))
                .then(() => ui.unblock());
        }
    }
};
