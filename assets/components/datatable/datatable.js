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

import { alert } from "@utilities/messagebox";

const DEFAULT_PAGE_SIZE = 10;
const DEFAULT_ORDER = "asc";
const REFRESH_DELAY = 400;

/**
 * DataTable.
 */
export default {
    mounted() {
        // Restore saved table state (paging).
        if (this.paging) {
            this.page = parseInt(this.loadState("page")) || 1;
            this.pageSize = parseInt(this.loadState("pageSize")) || DEFAULT_PAGE_SIZE;

            if (!this.allowedPageSizes.includes(this.pageSize)) {
                this.pageSize = DEFAULT_PAGE_SIZE;
            }
        } else {
            this.page = 1;
            this.pageSize = DEFAULT_PAGE_SIZE;

            this.refresh();
        }

        // Restore saved table state (search).
        this.search = this.loadState("search") || "";

        // Restore saved table state (filters).
        let filters = this.loadState("filters") || {};

        this.columns.forEach((column) => {
            if (filters.hasOwnProperty(column.props.id) && (column.props.filterable ?? true)) {
                this.filters[column.props.id] = filters[column.props.id];
            } else {
                this.filters[column.props.id] = "";
            }
        });

        // Restore saved table state (sorting order).
        let order = this.loadState("order") || {};

        this.columns.forEach((column) => {
            if (order.hasOwnProperty(column.props.id) && (column.props.sortable ?? true)) {
                this.order[column.props.id] = order[column.props.id];
            }
        });

        // Default sorting order.
        if (Object.keys(this.order).length === 0) {
            this.order[this.columns[0].props.id] = DEFAULT_ORDER;
        }
    },

    props: {
        /**
         * @property {string} id Table ID to save its state
         */
        id: {
            type: String,
            required: true
        },

        /**
         * @property {function} data Data provider
         *
         * The function takes the following parameters:
         *   {number} offset  - zero-based index of the first entry to return
         *   {number} limit   - maximum number of entries to return
         *   {string} search  - current value of the search
         *   {Object} filters - current values of the column filters ({ "column id": value })
         *   {Object} order   - current sorting order ({ "column id": "asc"|"desc" })
         *
         * The function must return an object with the following properties:
         *   {number} total - total number of entries in the source
         *   {Array}  rows  - returned entries
         *
         * In case of an error the function should throw an error message.
         */
        data: {
            type: Function,
            required: true
        },

        /**
         * @property {boolean} paging Whether to enable paging
         */
        paging: {
            type: Boolean,
            default: true
        },

        /**
         * @property {boolean} clickable Whether to emit an event when a table row is clicked
         */
        clickable: {
            type: Boolean,
            default: true
        },

        /**
         * @property {boolean} icons Whether to show a column with icons
         */
        icons: {
            type: Boolean,
            default: false
        },

        /**
         * @property {boolean} checkboxes Whether to show a column with checkboxes
         */
        checkboxes: {
            type: Boolean,
            default: true
        },

        /**
         * @property {Array<string>} checked Checked rows (array of associated IDs)
         */
        checked: {
            type: Array,
            default: () => []
        }
    },

    emits: ["cell-click", "icon-click", "update:checked"],

    expose: ["refresh"],

    data: () => ({
        /**
         * @property {boolean} blocked Whether the table is blocked from user's interaction
         */
        blocked: false,

        /**
         * @property {number} timer Table refresh timer
         */
        timer: null,

        /**
         * @property {number} total Total number of rows
         */
        total: 0,

        /**
         * @property {Array<Object>} rows Rows data
         */
        rows: [],

        /**
         * @property {number} userPage Manually entered page number, one-based
         */
        userPage: 0,

        /**
         * @property {number} page Current page number, one-based
         */
        page: 0,

        /**
         * @property {number} pageSize Page size
         */
        pageSize: 0,

        /**
         * @property {string} search "Search" value
         */
        search: "",

        /**
         * @property {Object} filters Column filters values
         */
        filters: {},

        /**
         * @property {Object} order Current sorting order ({ "column id": "asc"|"desc" })
         */
        order: {}
    }),

    computed: {
        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {Array<number>} allowedPageSizes Allowed page sizes
         */
        allowedPageSizes: () => [DEFAULT_PAGE_SIZE, 20, 50, 100],

        /**
         * @property {boolean} hasToolbar Whether the custom toolbar is present
         */
        hasToolbar() {
            return !!this.$slots["toolbar"];
        },

        /**
         * @property {Array<Object>} columns List of columns
         */
        columns() {
            return this.$slots.default();
        },

        /**
         * @property {string} status Status string
         */
        status() {
            if (this.blocked) {
                return i18n["text.please_wait"];
            }

            if (this.total === 0) {
                return null;
            }

            return !this.paging
                ? i18n["table.size"].replace("%size%", this.total)
                : i18n["table.status"]
                      .replace("%from%", (this.page - 1) * this.pageSize + 1)
                      .replace("%to%", Math.min(this.page * this.pageSize, this.total))
                      .replace("%total%", this.total);
        },

        /**
         * @property {number} pages Total number of pages
         */
        pages() {
            return this.paging ? Math.ceil(this.total / this.pageSize) : 1;
        },

        /**
         * @property {boolean} isCheckedAll Whether all visible rows are ticked
         */
        isCheckedAll: {
            get() {
                return this.checked.length === this.rows.filter((row) => this.isRowCheckable(row)).length;
            },
            set(value) {
                this.$emit("update:checked", value ? this.rows.filter((row) => this.isRowCheckable(row)).map((row) => row["DT_id"]) : []);
            }
        },

        /**
         * @property {number} totalFilters Number of filterable columns
         */
        totalFilters() {
            return this.columns.filter((column) => column.props.filterable ?? true).length;
        },

        /**
         * @property {Object} filters Column filters values, without empty ones and all typecasted
         */
        normalizedFilters() {
            return this.columns.reduce((result, column) => {
                let value = this.filters[column.props.id].trim();

                if (value.length === 0) {
                    return result;
                }

                if (Object.keys(this.columnFilterWith(column)).length !== 0) {
                    if (!Number.isNaN(Number(value))) {
                        value = parseFloat(value);
                    } else if (value === "true") {
                        value = true;
                    } else if (value === "false") {
                        value = false;
                    }
                }

                return { ...result, [column.props.id]: value };
            }, {});
        }
    },

    methods: {
        /**
         * @public Reloads the table data.
         */
        async refresh() {
            clearTimeout(this.timer);

            this.blocked = true;

            let offset = this.paging ? (this.page - 1) * this.pageSize : 0;
            let limit = this.paging ? this.pageSize : Number.MAX_SAFE_INTEGER;

            try {
                let response = await this.data(offset, limit, this.search, this.normalizedFilters, this.order);

                this.total = response.total;
                this.rows = response.rows;

                if (this.page > this.pages) {
                    this.page = this.pages || 1;
                }

                this.blocked = false;

                this.$emit("update:checked", []);
            } catch (error) {
                alert(error).then(() => (this.blocked = false));
            }
        },

        /**
         * Reloads the table data with delay.
         */
        refreshWithDelay() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.refresh(), REFRESH_DELAY);
        },

        /**
         * Saves specified value to the local storage.
         *
         * @param {string} name  Name to use in the storage
         * @param {*}      value Value to store
         */
        saveState(name, value) {
            if (typeof value === "object") {
                let values = Object.entries(value).reduce(
                    (result, entry) => ({
                        ...result,
                        [entry[0]]: entry[1]
                    }),
                    {}
                );

                localStorage[`DT_${this.id}_${name}`] = JSON.stringify(values);
            } else {
                localStorage[`DT_${this.id}_${name}`] = JSON.stringify(value);
            }
        },

        /**
         * Retrieves value from the local storage.
         *
         * @param {string} name Name used in the storage
         *
         * @return {*|null} Retrieved value
         */
        loadState(name) {
            return JSON.parse(localStorage[`DT_${this.id}_${name}`] || null);
        },

        /**
         * Determines whether the specified row can be ticked.
         */
        isRowCheckable(row) {
            return (row["DT_checkable"] || false) !== false;
        },

        /**
         * Toggles checkbox status of the specified row.
         *
         * @param {string} id ID of the row (`DT_id` property)
         */
        toggleCheck(id) {
            let checked = new Set(this.checked);

            if (checked.has(id)) {
                checked.delete(id);
            } else {
                checked.add(id);
            }

            this.$emit("update:checked", Array.from(checked));
        },

        /**
         * Clears current search and filters.
         */
        clearSearchAndFilters() {
            this.search = "";
            this.columns.forEach((column) => (this.filters[column.props.id] = ""));
        },

        /**
         * Returns list of allowed filtering options for specified columns.
         *
         * @param {Object} column Column object
         *
         * @returns {Object} Filtering options
         */
        columnFilterWith(column) {
            return column.props["filter-with"] ?? {};
        }
    },

    watch: {
        /**
         * User entered new page number.
         *
         * @param {number|string} value New page number
         */
        userPage(value) {
            if (this.paging) {
                if (typeof value === "number" && value >= 1 && value <= this.pages) {
                    this.userPage = this.page = Math.round(value);
                } else {
                    this.userPage = this.page;
                }
            }
        },

        /**
         * Current page is changed.
         *
         * @param {number} value New page number
         */
        page(value) {
            this.userPage = value;

            if (this.paging) {
                this.saveState("page", value);
                this.refreshWithDelay();
            }
        },

        /**
         * Page size is changed.
         *
         * @param {number} value New page size
         */
        pageSize(value) {
            if (this.paging) {
                if (this.allowedPageSizes.indexOf(value) === -1) {
                    this.pageSize = DEFAULT_PAGE_SIZE;
                    return;
                }

                this.saveState("pageSize", value);
                this.refreshWithDelay();
            }
        },

        /**
         * Search value is changed.
         *
         * @param {string} value New search
         */
        search(value) {
            this.saveState("search", value);
            this.refreshWithDelay();
        },

        /**
         * Column filters are changed.
         *
         * @param {Object} value New filters
         */
        filters: {
            handler(value) {
                this.saveState("filters", value);
                this.refreshWithDelay();
            },
            deep: true
        },

        /**
         * Sorting order is changed.
         *
         * @param {Object} value New order
         */
        order(value) {
            this.saveState("order", value);
            this.refresh();
        }
    }
};
