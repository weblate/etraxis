<template>
    <modal ref="modal" :header="header" auto-close @submit="submit">
        <fieldset class="fieldset">
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-name`">{{ i18n['state.name'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <input
                                class="input"
                                :class="{ 'is-danger': errors['name'] }"
                                type="text"
                                :id="`${uid}-name`"
                                :placeholder="i18n['text.required']"
                                v-model="values.name"
                            />
                        </div>
                        <p class="help is-danger">{{ errors['name'] }}</p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :class="{ disabled: noType }" :for="`${uid}-type`">{{ i18n['state.type'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <div class="select is-fullwidth" :class="{ 'is-danger': errors['type'] }">
                                <select v-if="noType" :id="`${uid}-type`" disabled v-model="values.type">
                                    <option :value="values.type">{{ i18n[stateTypes[values.type]] }}</option>
                                </select>
                                <select v-else :id="`${uid}-type`" v-model="values.type">
                                    <option v-for="(value, key) in stateTypes" :key="key" :value="key">{{ i18n[value] }}</option>
                                </select>
                            </div>
                        </div>
                        <p class="help is-danger">{{ errors['type'] }}</p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-responsible`">{{ i18n['state.responsible'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <div class="select is-fullwidth" :class="{ 'is-danger': errors['responsible'] }">
                                <select :id="`${uid}-responsible`" v-model="values.responsible">
                                    <option v-for="(value, key) in stateResponsibles" :key="key" :value="key">{{ i18n[value] }}</option>
                                </select>
                            </div>
                        </div>
                        <p class="help is-danger">{{ errors['responsible'] }}</p>
                    </div>
                </div>
            </div>
        </fieldset>
    </modal>
</template>

<script src="./StateDialog.js"></script>
