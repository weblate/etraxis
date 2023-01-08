<template>
    <modal ref="modal" :header="header" @submit="submit">
        <fieldset v-if="!isExternal" class="fieldset has-legend">
            <legend>{{ i18n["user.account"] }}</legend>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-fullname`">{{ i18n["user.fullname"] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <input
                                class="input"
                                :class="{ 'is-danger': errors['fullname'] }"
                                type="text"
                                :id="`${uid}-fullname`"
                                placeholder="required"
                                v-model="values.fullname"
                            />
                        </div>
                        <p class="help is-danger">{{ errors["fullname"] }}</p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-email`">{{ i18n["user.email"] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <input
                                class="input"
                                :class="{ 'is-danger': errors['email'] }"
                                type="email"
                                :id="`${uid}-email`"
                                placeholder="required"
                                v-model="values.email"
                            />
                        </div>
                        <p class="help is-danger">{{ errors["email"] }}</p>
                    </div>
                </div>
            </div>
        </fieldset>
        <fieldset class="fieldset has-legend">
            <legend>{{ i18n["user.appearance"] }}</legend>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-locale`">{{ i18n["user.language"] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <div class="select is-fullwidth" :class="{ 'is-danger': errors['locale'] }">
                                <select :id="`${uid}-locale`" v-model="values.locale">
                                    <option v-for="(locale, key) in locales" :key="key" :value="key">{{ locale }}</option>
                                </select>
                            </div>
                        </div>
                        <p class="help is-danger">{{ errors["locale"] }}</p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-theme`">{{ i18n["user.theme"] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <div class="select is-fullwidth" :class="{ 'is-danger': errors['theme'] }">
                                <select :id="`${uid}-theme`" v-model="values.theme">
                                    <option v-for="(theme, key) in themes" :key="key" :value="key">{{ theme }}</option>
                                </select>
                            </div>
                        </div>
                        <p class="help is-danger">{{ errors["theme"] }}</p>
                    </div>
                </div>
            </div>
        </fieldset>
        <fieldset class="fieldset has-legend">
            <legend>{{ i18n["user.timezone"] }}</legend>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-country`">{{ i18n["user.country"] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select :id="`${uid}-country`" v-model="country">
                                    <option value="UTC">UTC</option>
                                    <option v-for="country in countries" :value="country">{{ country }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-city`">{{ i18n["user.city"] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <div class="select is-fullwidth">
                                <select :id="`${uid}-city`" :disabled="country === 'UTC'" v-model="city">
                                    <option v-for="(city, timezone) in cities" :value="timezone">{{ city }}</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </fieldset>
    </modal>
</template>

<script src="./ProfileDialog.js"></script>
