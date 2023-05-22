<template>
    <section>
        <div class="buttons">
            <button class="button" type="button" :disabled="!stateStore.canUpdate" @click="openEditStateDialog">{{ i18n['button.edit'] }}</button>
        </div>
        <div class="columns">
            <div class="column is-two-thirds-desktop">
                <fieldset class="fieldset">
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n['state.name'] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ stateStore.name }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n['state.type'] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ stateTypes[stateStore.type] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n['state.responsible'] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ stateResponsibles[stateStore.responsible] }}</p>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
            <edit-state-dialog
                ref="dlgEditState"
                :header="i18n['state.edit']"
                :errors="errors"
                :no-type="true"
                @submit="updateState"
            ></edit-state-dialog>
        </div>
    </section>
</template>

<script src="./StateTab.js"></script>
