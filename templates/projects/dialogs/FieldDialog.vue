<template>
    <modal ref="modal" :header="header" auto-close @submit="submit">
        <fieldset class="fieldset">
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-name`">{{ i18n['field.name'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <input
                                class="input"
                                :class="{ 'is-danger': errors['name'] }"
                                type="text"
                                :id="`${uid}-name`"
                                :maxlength="MAX_NAME"
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
                    <label class="label" :class="{ disabled: noType }" :for="`${uid}-type`">{{ i18n['field.type'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <div class="select is-fullwidth" :class="{ 'is-danger': errors['type'] }">
                                <select v-if="noType" :id="`${uid}-type`" disabled v-model="values.type">
                                    <option :value="values.type">{{ fieldTypes[values.type] }}</option>
                                </select>
                                <select v-else :id="`${uid}-type`" v-model="values.type">
                                    <option v-for="(value, key) in fieldTypes" :key="key" :value="key">{{ value }}</option>
                                </select>
                            </div>
                        </div>
                        <p class="help is-danger">{{ errors['type'] }}</p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-description`">{{ i18n['field.description'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <input
                                class="input"
                                :class="{ 'is-danger': errors['description'] }"
                                type="text"
                                :id="`${uid}-description`"
                                :maxlength="MAX_DESCRIPTION"
                                v-model="values.description"
                            />
                        </div>
                        <p class="help is-danger">{{ errors['description'] }}</p>
                    </div>
                </div>
            </div>
            <div v-if="isDate || isDecimal || isDuration || isNumber" class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-minimum`">{{ i18n['field.min_value'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <input
                                class="input"
                                :class="{ 'is-danger': errors['minimum'] }"
                                type="text"
                                :id="`${uid}-minimum`"
                                :maxlength="MAX_PARAMETER"
                                :placeholder="fieldTypeMinimumPlaceholder"
                                v-model="values.minimum"
                            />
                        </div>
                        <p class="help is-danger">{{ errors['minimum'] }}</p>
                    </div>
                </div>
            </div>
            <div v-if="isDate || isDecimal || isDuration || isNumber" class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-maximum`">{{ i18n['field.max_value'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <input
                                class="input"
                                :class="{ 'is-danger': errors['maximum'] }"
                                type="text"
                                :id="`${uid}-maximum`"
                                :maxlength="MAX_PARAMETER"
                                :placeholder="fieldTypeMaximumPlaceholder"
                                v-model="values.maximum"
                            />
                        </div>
                        <p class="help is-danger">{{ errors['maximum'] }}</p>
                    </div>
                </div>
            </div>
            <div v-if="isString || isText" class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-length`">{{ i18n['field.max_length'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <input
                                class="input"
                                :class="{ 'is-danger': errors['length'] }"
                                type="number"
                                min="1"
                                :max="isString ? MAX_STRING_LENGTH : MAX_TEXT_LENGTH"
                                :id="`${uid}-length`"
                                :placeholder="isString ? MAX_STRING_LENGTH : MAX_TEXT_LENGTH"
                                v-model="values.length"
                            />
                        </div>
                        <p class="help is-danger">{{ errors['length'] }}</p>
                    </div>
                </div>
            </div>
            <div v-if="isDate || isDecimal || isDuration || isNumber || isString" class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-default`">{{ i18n['field.default_value'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <input
                                class="input"
                                :class="{ 'is-danger': errors['default'] }"
                                type="text"
                                :id="`${uid}-default`"
                                :maxlength="isString ? MAX_STRING_LENGTH : MAX_PARAMETER"
                                v-model="values.default"
                            />
                        </div>
                        <p class="help is-danger">{{ errors['default'] }}</p>
                    </div>
                </div>
            </div>
            <div v-if="isText" class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-default`">{{ i18n['field.default_value'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <textarea
                                class="textarea"
                                :class="{ 'is-danger': errors['default'] }"
                                :id="`${uid}-default`"
                                :maxlength="MAX_TEXT_LENGTH"
                                v-model="values.default"
                            ></textarea>
                        </div>
                        <p class="help is-danger">{{ errors['default'] }}</p>
                    </div>
                </div>
            </div>
            <div v-if="isCheckbox" class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-default`">{{ i18n['field.default_value'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <div class="select is-fullwidth" :class="{ 'is-danger': errors['default'] }">
                                <select :id="`${uid}-default`" v-model="values.default">
                                    <option :value="true">{{ i18n['text.on'] }}</option>
                                    <option :value="false">{{ i18n['text.off'] }}</option>
                                </select>
                            </div>
                        </div>
                        <p class="help is-danger">{{ errors['default'] }}</p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label"></div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <label class="checkbox">
                                <input type="checkbox" v-model="values.required" />
                                <span>{{ i18n['field.required'] }}</span>
                            </label>
                        </div>
                        <p class="help is-danger">{{ errors['required'] }}</p>
                    </div>
                </div>
            </div>
        </fieldset>
    </modal>
</template>

<script src="./FieldDialog.js"></script>
