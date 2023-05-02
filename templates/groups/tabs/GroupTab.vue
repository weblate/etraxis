<template>
    <section>
        <div class="buttons">
            <button class="button" type="button" @click="goBack">{{ i18n['button.back'] }}</button>
            <button class="button" type="button" :disabled="!groupStore.canUpdate" @click="openEditGroupDialog">{{ i18n['button.edit'] }}</button>
            <button class="button is-danger" type="button" :disabled="!groupStore.canDelete" @click="deleteGroup">{{ i18n['button.delete'] }}</button>
        </div>
        <div class="columns">
            <div class="column is-half-tablet">
                <fieldset class="fieldset">
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["group.name"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ groupStore.name }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["project"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ groupStore.projectName || "&mdash;" }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["group.description"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ groupStore.description || "&mdash;" }}</p>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
            <edit-group-dialog
                ref="dlgEditGroup"
                :header="i18n['group.edit']"
                :errors="errors"
                @submit="updateGroup"
            ></edit-group-dialog>
        </div>
    </section>
</template>

<script src="./GroupTab.js"></script>
