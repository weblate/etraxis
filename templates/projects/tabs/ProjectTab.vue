<template>
    <section>
        <div class="buttons">
            <button class="button" type="button" @click="goBack">{{ i18n['button.back'] }}</button>
            <button class="button" type="button" :disabled="!projectStore.canUpdate" @click="openEditProjectDialog">{{ i18n['button.edit'] }}</button>
            <button v-show="!projectStore.isSuspended" class="button" type="button" :disabled="!projectStore.canSuspend" @click="toggleStatus">{{ i18n['button.suspend'] }}</button>
            <button v-show="projectStore.isSuspended" class="button" type="button" :disabled="!projectStore.canResume" @click="toggleStatus">{{ i18n['button.resume'] }}</button>
            <button class="button is-danger" type="button" :disabled="!projectStore.canDelete" @click="deleteProject">{{ i18n['button.delete'] }}</button>
        </div>
        <div class="columns">
            <div class="column is-half-tablet">
                <fieldset class="fieldset">
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["project.name"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ projectStore.name }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["project.start_date"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ projectStore.startDate }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["project.status"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p :class="{ 'has-text-danger': projectStore.isSuspended }">
                                    {{ projectStore.isSuspended ? i18n["project.suspended"] : i18n["project.active"] }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["project.description"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ projectStore.description || "&mdash;" }}</p>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
            <edit-project-dialog
                ref="dlgEditProject"
                :header="i18n['project.edit']"
                :errors="errors"
                @submit="updateProject"
            ></edit-project-dialog>
        </div>
    </section>
</template>

<script src="./ProjectTab.js"></script>
