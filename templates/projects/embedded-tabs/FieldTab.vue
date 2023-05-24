<template>
    <section>
        <div class="columns">
            <div class="column is-two-thirds-desktop">
                <fieldset class="fieldset">
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n['field.name'] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ fieldStore.name }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n['field.type'] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ i18n[fieldTypes[fieldStore.type]] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n['field.description'] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ fieldStore.description || '&mdash;' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n['field.required'] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ fieldStore.isRequired ? i18n['button.yes'] : i18n['button.no'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="isString || isText" class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n['field.max_length'] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ fieldStore.parameters.length || '&mdash;' }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="isDate || isDecimal || isDuration || isNumber" class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n['field.min_value'] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ fieldStore.parameters.minimum || '&mdash;' }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="isDate || isDecimal || isDuration || isNumber" class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n['field.max_value'] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ fieldStore.parameters.maximum || '&mdash;' }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="isDate || isDecimal || isDuration || isList || isNumber || isString || isText" class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n['field.default_value'] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ fieldStore.parameters.default || '&mdash;' }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-if="isCheckbox" class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n['field.default_value'] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ fieldStore.parameters.default ? i18n['text.on'] : i18n['text.off'] }}</p>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
    </section>
</template>

<script src="./FieldTab.js"></script>
