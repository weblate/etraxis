<template>
    <section>
        <div class="buttons">
            <button class="button" type="button" @click="goBack">{{ i18n["button.back"] }}</button>
            <button class="button" type="button" :disabled="!canUpdate" @click="openEditUserDialog">{{ i18n["button.edit"] }}</button>
            <button v-show="!disabled" class="button" type="button" :disabled="!canDisable" @click="toggleStatus">{{ i18n["button.disable"] }}</button>
            <button v-show="disabled" class="button" type="button" :disabled="!canEnable" @click="toggleStatus">{{ i18n["button.enable"] }}</button>
        </div>
        <div class="columns">
            <div class="column is-half-tablet">
                <fieldset class="fieldset">
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["user.fullname"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ fullname }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["user.email"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ email }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["user.authentication"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ accountProviders[accountProvider] ?? null }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["user.permissions"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ admin ? i18n["role.admin"] : i18n["role.user"] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["user.status"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p :class="{ 'has-text-danger': disabled }">
                                    {{ disabled ? i18n["user.disabled"] : i18n["user.enabled"] }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["user.description"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ description || "&mdash;" }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["user.language"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ locales[locale] ?? null }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["user.timezone"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ timezone }}</p>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
        <edit-user-dialog
            ref="dlgEditUser"
            :header="i18n['user.edit']"
            :timezones="timezones"
            :errors="errors"
            @submit="updateUser"
        ></edit-user-dialog>
    </section>
</template>

<script src="./ProfileTab.js"></script>
