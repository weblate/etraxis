<template>
    <div class="tabs" :class="{ simplified }">
        <ul>
            <li v-for="tab in tabs" :key="tab.props.id" :id="tab.props.id" :class="{ 'is-active': tab.props.id === modelValue }">
                <a @click="$emit('update:modelValue', tab.props.id)">
                    <span>{{ tab.props.title }}</span>
                    <span v-if="tab.props.counter != null" class="tag is-rounded">{{ tab.props.counter }}</span>
                </a>
            </li>
        </ul>
    </div>
    <slot></slot>
</template>

<script src="./tabs.js"></script>
