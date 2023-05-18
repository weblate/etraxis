<template>
    <section>
        <div class="columns">
            <div class="column is-one-third-tablet is-one-quarter-desktop">
                <fieldset class="fieldset">
                    <tree :nodes="nodes" @node-click="onNodeClick"></tree>
                </fieldset>
            </div>
            <div class="column is-two-thirds-tablet is-three-quarters-desktop">
                <tabs v-if="fieldId" simplified v-model="fieldTab">
                    <tab id="field" :title="i18n['field']">
                        <field-tab></field-tab>
                    </tab>
                </tabs>
                <tabs v-else-if="stateId" simplified v-model="stateTab">
                    <tab id="state" :title="i18n['state']">
                        <state-tab></state-tab>
                    </tab>
                </tabs>
                <tabs v-else-if="templateId" simplified v-model="templateTab">
                    <tab id="template" :title="i18n['template']">
                        <template-tab></template-tab>
                    </tab>
                </tabs>
            </div>
        </div>
    </section>
</template>

<script src="./TemplatesTab.js"></script>
