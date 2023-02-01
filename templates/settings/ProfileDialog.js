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

import Modal from "@components/modal/modal.vue";

import LocaleEnum from "@enums/locale";
import ThemeEnum from "@enums/theme";

import generateUid from "@utilities/uid";

/**
 * "Profile" dialog.
 */
export default {
    props: {
        /**
         * @property {string} header Dialog's header
         */
        header: {
            type: String,
            required: true
        },

        /**
         * @property {boolean} isExternal Whether the user's account is external
         */
        isExternal: {
            type: Boolean,
            required: true
        },

        /**
         * @property {Object} timezones List of all available timezones, grouped by country
         */
        timezones: {
            type: Object,
            required: true
        },

        /**
         * @property {Object} errors Form errors
         */
        errors: {
            type: Object,
            required: true
        }
    },

    emits: {
        /**
         * @param {Object} values Submitted form values
         */
        submit: (values) => typeof values === "object"
    },

    expose: ["open", "close"],

    components: {
        modal: Modal
    },

    data: () => ({
        /**
         * @property {string} uid Unique dialog ID
         */
        uid: generateUid(),

        /**
         * @property {Object} values Form values
         */
        values: {
            email: null,
            fullname: null,
            locale: null,
            theme: null,
            timezone: null
        },

        /**
         * @property {string} country Current country
         */
        country: null,

        /**
         * @property {string} city Current city
         */
        city: null
    }),

    computed: {
        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {Object} locales Available locales
         */
        locales: () => LocaleEnum,

        /**
         * @property {Object} themes Available themes
         */
        themes: () => ThemeEnum,

        /**
         * @property {Array<string>} countries Available countries
         */
        countries() {
            return Object.keys(this.timezones).sort();
        },

        /**
         * @property {Object} cities Available cities in the current country
         */
        cities() {
            return this.country === "UTC" ? { UTC: "UTC" } : this.timezones[this.country] ?? {};
        }
    },

    methods: {
        /**
         * @public Opens the dialog.
         *
         * @param {Object} defaults Default values
         */
        open(defaults = {}) {
            this.values = { ...defaults };
            this.country = "UTC";

            for (let country in this.timezones) {
                if (Object.keys(this.timezones[country]).includes(this.values.timezone)) {
                    this.country = country;
                    this.city = this.values.timezone;
                    break;
                }
            }

            this.$refs.modal.open();

            this.$nextTick(() => document.getElementById(`${this.uid}-fullname`).focus());
        },

        /**
         * @public Closes the dialog.
         */
        close() {
            this.$refs.modal.close();
        },

        /**
         * Submits the dialog's form.
         */
        submit() {
            this.values.timezone = this.city ?? "UTC";
            this.$emit("submit", this.values);
        }
    },

    watch: {
        /**
         * Selects default city when the current country is changed.
         *
         * @param value New country
         */
        country(value) {
            if (value === "UTC") {
                this.city = null;
            } else {
                let timezones = Object.keys(this.timezones[value] ?? {});
                this.city = timezones.includes(this.values.timezone) ? this.values.timezone : timezones[0];
            }
        }
    }
};
