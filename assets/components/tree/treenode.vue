<template>
    <li :class="nodeClass" @click.stop="onNodeClick">
        <span class="toggle" :title="toggleTitle" @click.stop="toggleNode">{{ toggleSymbol }}</span>
        <span class="title">{{ title }}</span>
        <ul>
            <tree-node v-for="node in nodes"
                       :key="node.id"
                       @node-click="$emit('node-click', $event)"
                       @node-expand="$emit('node-expand', $event)"
                       @node-collapse="$emit('node-collapse', $event)"
                       v-bind="node"
            ></tree-node>
        </ul>
    </li>
</template>

<script src="./treenode.js"></script>
