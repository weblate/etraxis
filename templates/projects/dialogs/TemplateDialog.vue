<template>
    <modal ref="modal" :header="header" auto-close @submit="submit">
        <fieldset class="fieldset">
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-name`">{{ i18n['template.name'] }}:</label>
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
                    <label class="label" :for="`${uid}-prefix`">{{ i18n['template.prefix'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <input
                                class="input"
                                :class="{ 'is-danger': errors['prefix'] }"
                                type="text"
                                :id="`${uid}-prefix`"
                                :placeholder="i18n['text.required']"
                                v-model="values.prefix"
                            />
                        </div>
                        <p class="help is-danger">{{ errors['prefix'] }}</p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-description`">{{ i18n['template.description'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <input
                                class="input"
                                :class="{ 'is-danger': errors['description'] }"
                                type="text"
                                :id="`${uid}-description`"
                                v-model="values.description"
                            />
                        </div>
                        <p class="help is-danger">{{ errors['description'] }}</p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-critical-age`">{{ i18n['template.critical_age'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <input
                                class="input"
                                :class="{ 'is-danger': errors['criticalAge'] }"
                                type="number"
                                min="1"
                                :id="`${uid}-critical-age`"
                                v-model="values.criticalAge"
                            />
                        </div>
                        <p class="help is-danger">{{ errors['criticalAge'] }}</p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-frozen-time`">{{ i18n['template.frozen_time'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <input
                                class="input"
                                :class="{ 'is-danger': errors['frozenTime'] }"
                                type="number"
                                min="1"
                                :id="`${uid}-frozen-time`"
                                v-model="values.frozenTime"
                            />
                        </div>
                        <p class="help is-danger">{{ errors['frozenTime'] }}</p>
                    </div>
                </div>
            </div>
        </fieldset>
    </modal>
</template>

<script src="./TemplateDialog.js"></script>
