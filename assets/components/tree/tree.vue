<template>
    <div class="tree">
        <ul>
            <tree-node v-for="node in nodes"
                       :key="node.id"
                       @node-click="$emit('node-click', $event)"
                       @node-expand="$emit('node-expand', $event)"
                       @node-collapse="$emit('node-collapse', $event)"
                       v-bind="node"
            ></tree-node>
        </ul>
    </div>
</template>

<script src="./tree.js"></script>
