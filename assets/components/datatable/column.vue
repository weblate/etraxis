<template>
    <th :class="cssClass" @click="onClick">
        <div class="is-flex is-align-items-center">
            <span class="is-flex-grow-1"><slot></slot></span>
            <span v-if="sortable" class="icon is-small ml-2">
                <i class="fa" :class="{ 'fa-caret-up': isSortedAsc, 'fa-caret-down': isSortedDesc }"></i>
            </span>
        </div>
    </th>
</template>

<script src="./column.js"></script>
