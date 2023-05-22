<template>
    <section>
        <div class="columns">
            <div class="column is-one-third-tablet is-one-quarter-desktop">
                <fieldset class="fieldset">
                    <tree :nodes="nodes" @node-expand="onNodeExpand" @node-click="onNodeClick"></tree>
                </fieldset>
                <new-template-dialog ref="dlgNewTemplate" :header="i18n['template.new']" :errors="errors" @submit="createTemplate"></new-template-dialog>
                <new-state-dialog ref="dlgNewState" :header="i18n['state.new']" :errors="errors" @submit="createState"></new-state-dialog>
            </div>
            <div class="column is-two-thirds-tablet is-three-quarters-desktop">
                <tabs v-if="fieldId" simplified v-model="fieldTab">
                    <tab id="field" :title="i18n['field']">
                        <field-tab></field-tab>
                    </tab>
                </tabs>
                <tabs v-else-if="stateId" simplified v-model="stateTab">
                    <tab id="state" :title="i18n['state']">
                        <state-tab @update="onStateUpdated"></state-tab>
                    </tab>
                </tabs>
                <tabs v-else-if="templateId" simplified v-model="templateTab">
                    <tab id="template" :title="i18n['template']">
                        <template-tab @update="onTemplateUpdated" @clone="onTemplateCloned" @delete="onTemplateDeleted"></template-tab>
                    </tab>
                </tabs>
            </div>
        </div>
    </section>
</template>

<script src="./TemplatesTab.js"></script>
