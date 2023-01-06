<template>
    <div class="dropdown" :class="{ 'is-active': isActive }">
        <div class="dropdown-trigger">
            <button class="button" :disabled="disabled" :aria-haspopup="!disabled" :aria-controls="`${uid}-dropdown-menu`" @click="toggle">
                <span>{{ caption }}</span>
                <span class="icon">
                    <i class="fa fa-angle-down" aria-hidden="true"></i>
                </span>
            </button>
        </div>
        <div v-if="!disabled" class="dropdown-menu" :id="`${uid}-dropdown-menu`" role="menu">
            <div class="dropdown-content">
                <slot></slot>
            </div>
        </div>
    </div>
</template>

<script src="./dropdown.js"></script>
