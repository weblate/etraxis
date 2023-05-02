<template>
    <modal ref="modal" :header="header" auto-close @submit="submit">
        <fieldset class="fieldset">
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-fullname`">{{ i18n['user.fullname'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <input
                                class="input"
                                :class="{ 'is-danger': errors['fullname'] }"
                                type="text"
                                :id="`${uid}-fullname`"
                                :readonly="isExternal"
                                placeholder="required"
                                v-model="values.fullname"
                            />
                        </div>
                        <p class="help is-danger">{{ errors['fullname'] }}</p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-email`">{{ i18n['user.email'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <input
                                class="input"
                                :class="{ 'is-danger': errors['email'] }"
                                type="email"
                                :id="`${uid}-email`"
                                :readonly="isExternal"
                                placeholder="required"
                                v-model="values.email"
                            />
                        </div>
                        <p class="help is-danger">{{ errors['email'] }}</p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-description`">{{ i18n['user.description'] }}:</label>
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
                    <label class="label" :for="`${uid}-locale`">{{ i18n['user.language'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <div class="select is-fullwidth" :class="{ 'is-danger': errors['locale'] }">
                                <select :id="`${uid}-locale`" v-model="values.locale">
                                    <option v-for="(language, locale) in languages" :key="locale" :value="locale">{{ language }}</option>
                                </select>
                            </div>
                        </div>
                        <p class="help is-danger">{{ errors['locale'] }}</p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-timezone`">{{ i18n['user.timezone'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <div class="select is-fullwidth" :class="{ 'is-danger': errors['timezone'] }">
                                <select :id="`${uid}-timezone`" v-model="values.timezone">
                                    <option v-for="timezone in timezones" :key="timezone" :value="timezone">{{ timezone }}</option>
                                </select>
                            </div>
                        </div>
                        <p class="help is-danger">{{ errors['timezone'] }}</p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label"></div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <label class="checkbox" :class="{ disabled: isCurrent }">
                                <input type="checkbox" :disabled="isCurrent" v-model="values.admin" />
                                <span>{{ i18n['role.admin'] }}</span>
                            </label>
                        </div>
                        <p class="help is-danger">{{ errors['admin'] }}</p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label"></div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <label class="checkbox" :class="{ disabled: isCurrent }">
                                <input type="checkbox" :disabled="isCurrent" v-model="values.disabled" />
                                <span>{{ i18n['user.disabled'] }}</span>
                            </label>
                        </div>
                        <p class="help is-danger">{{ errors['disabled'] }}</p>
                    </div>
                </div>
            </div>
        </fieldset>
    </modal>
</template>

<script src="./EditUserDialog.js"></script>
