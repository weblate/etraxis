<template>
    <section>
        <div class="buttons">
            <button class="button" type="button" @click="openProfileDialog">{{ i18n["button.edit"] }}</button>
            <button v-if="!isExternal" class="button" type="button" @click="openPasswordDialog">{{ i18n["password.change"] }}</button>
        </div>
        <div class="columns">
            <div class="column">
                <fieldset class="fieldset has-legend">
                    <legend>{{ i18n["user.account"] }}</legend>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["user.fullname"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ profile.fullname }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["user.email"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ profile.email }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["user.authentication"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ accountProviders[profile.accountProvider] ?? null }}</p>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
            <div class="column">
                <fieldset class="fieldset has-legend">
                    <legend>{{ i18n["user.appearance"] }}</legend>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["user.language"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ locales[profile.locale] ?? null }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["user.theme"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ themes[profile.theme] ?? null }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["user.timezone"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ profile.timezone }}</p>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
    </section>
    <profile-dialog
        ref="dlgProfile"
        :header="i18n['user.profile']"
        :is-external="isExternal"
        :timezones="timezones"
        :errors="errors"
        @submit="updateProfile"
    ></profile-dialog>
    <password-dialog
        v-if="!isExternal"
        ref="dlgPassword"
        :header="i18n['password.change']"
        :errors="errors"
        @submit="updatePassword"
    ></password-dialog>
</template>

<script src="./ProfileTab.js"></script>
