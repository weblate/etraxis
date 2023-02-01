<template>
    <div class="datatable">
        <div v-if="paging" class="header">
            <div :class="{ 'ml-2 mr-1': hasToolbar }">
                <slot name="toolbar"></slot>
            </div>
            <div v-if="paging" class="paging">
                <div class="field is-grouped">
                    <div class="control select">
                        <select class="page-size" :disabled="blocked" v-model="pageSize">
                            <option v-for="pageSize in allowedPageSizes" :key="pageSize" :value="pageSize">
                                {{ i18n["table.size"].replace("%size%", pageSize) }}
                            </option>
                        </select>
                    </div>
                    <div class="field has-addons">
                        <div class="control">
                            <button
                                class="button"
                                type="button"
                                :disabled="blocked || pages === 0 || page === 1"
                                :title="i18n['page.first']"
                                @click="page = 1"
                            >
                                <span class="icon"><i class="fa fa-fast-backward"></i></span>
                            </button>
                        </div>
                        <div class="control">
                            <button
                                class="button"
                                type="button"
                                :disabled="blocked || pages === 0 || page === 1"
                                :title="i18n['page.previous']"
                                @click="page -= 1"
                            >
                                <span class="icon"><i class="fa fa-step-backward"></i></span>
                            </button>
                        </div>
                        <div class="control page-number">
                            <input
                                class="input has-text-centered"
                                type="text"
                                :readonly="blocked"
                                :disabled="pages === 0"
                                :title="i18n['table.pages'].replace('%number%', pages)"
                                v-model.trim.lazy.number="userPage"
                            />
                        </div>
                        <div class="control">
                            <button
                                class="button"
                                type="button"
                                :disabled="blocked || pages === 0 || page === pages"
                                :title="i18n['page.next']"
                                @click="page += 1"
                            >
                                <span class="icon"><i class="fa fa-step-forward"></i></span>
                            </button>
                        </div>
                        <div class="control">
                            <button
                                class="button"
                                type="button"
                                :disabled="blocked || pages === 0 || page === pages"
                                :title="i18n['page.last']"
                                @click="page = pages"
                            >
                                <span class="icon"><i class="fa fa-fast-forward"></i></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="status">
                <p>{{ status }}</p>
            </div>
            <div class="search">
                <div class="field has-addons">
                    <div class="control">
                        <input class="input" type="text" :placeholder="i18n['table.search']" :readonly="blocked" v-model.trim="search" />
                    </div>
                    <div class="control">
                        <button class="button" type="button" :disabled="blocked" :title="i18n['table.clear_filters']" @click="clearSearchAndFilters">
                            <span class="icon"><i class="fa fa-times"></i></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <table class="table is-bordered is-fullwidth" :class="{ 'is-hoverable': clickable && !blocked }">
            <thead>
                <tr>
                    <th v-if="checkboxes && !hasFilters" class="is-narrow has-text-centered" @click.stop="isCheckedAll = !isCheckedAll">
                        <input
                            v-if="total !== 0"
                            type="checkbox"
                            :indeterminate.prop="!isCheckedAll && checked.length !== 0"
                            :disabled="blocked"
                            v-model="isCheckedAll"
                        />
                    </th>
                    <th v-if="checkboxes && hasFilters"></th>
                    <slot></slot>
                    <th v-if="icons && rows.length !== 0" class="is-narrow"></th>
                </tr>
            </thead>
            <tfoot v-if="hasFilters">
                <tr>
                    <td v-if="checkboxes" class="is-narrow has-text-centered" @click.stop="isCheckedAll = !isCheckedAll">
                        <input
                            v-if="total !== 0"
                            type="checkbox"
                            :indeterminate.prop="!isCheckedAll && checked.length !== 0"
                            :disabled="blocked"
                            v-model="isCheckedAll"
                        />
                    </td>
                    <td v-for="column in columns" :key="column.props.id">
                        <template v-if="isColumnFilterable(column)">
                            <input
                                v-if="Object.keys(columnFilterWith(column)).length === 0"
                                class="input is-fullwidth"
                                type="text"
                                :readonly="blocked"
                                v-model.trim="filters[column.props.id]"
                            />
                            <div v-else class="select is-fullwidth">
                                <select :disabled="blocked" v-model.trim="filters[column.props.id]">
                                    <option></option>
                                    <option v-for="(value, key) in columnFilterWith(column)" :key="key" :value="key">{{ value }}</option>
                                </select>
                            </div>
                        </template>
                    </td>
                    <td v-if="icons && rows.length !== 0"></td>
                </tr>
            </tfoot>
            <tbody>
                <tr v-if="rows.length === 0" class="empty">
                    <td :colspan="checkboxes ? columns.length + 1 : columns.length">{{ i18n["table.empty"] }}</td>
                </tr>
                <tr v-for="row in rows" :key="row['DT_id']">
                    <td
                        v-if="checkboxes"
                        class="has-text-centered"
                        @click.stop="(row['DT_checkable'] || false) !== false ? toggleCheck(row['DT_id']) : null"
                    >
                        <input type="checkbox" :disabled="blocked || !isRowCheckable(row)" :value="row['DT_id']" v-model="checked" />
                    </td>
                    <td
                        v-for="column in columns"
                        :key="column.props.id"
                        :class="[row['DT_class'] || '', { wrappable: column.props.wrappable }]"
                        @click="clickable ? $emit('cell-click', $event, row['DT_id'], column.props.id) : null"
                    >
                        <span>{{ row[column.props.id] || "&mdash;" }}</span>
                    </td>
                    <td v-if="icons">
                        <span
                            v-for="icon in row['DT_icons'] || []"
                            :key="icon.id"
                            class="icon"
                            :class="{ 'has-text-grey': icon.disabled }"
                            :title="!icon.disabled ? icon.title : null"
                            @click="!icon.disabled ? $emit('icon-click', $event, row['DT_id'], icon.id) : null"
                        >
                            <i class="fa" :class="icon.css"></i>
                        </span>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>

<script src="./datatable.js"></script>
