<template>
    <section>
        <div class="buttons">
            <button class="button" type="button" @click="goBack">{{ i18n["button.back"] }}</button>
            <button class="button" type="button" :disabled="!profileStore.canUpdate" @click="openEditUserDialog">{{ i18n["button.edit"] }}</button>
            <button v-show="!profileStore.isDisabled" class="button" type="button" :disabled="!profileStore.canDisable" @click="toggleStatus">{{ i18n["button.disable"] }}</button>
            <button v-show="profileStore.isDisabled" class="button" type="button" :disabled="!profileStore.canEnable" @click="toggleStatus">{{ i18n["button.enable"] }}</button>
            <button v-show="!profileStore.isExternalUser" class="button" type="button" @click="openSetPasswordDialog">{{ i18n["password.change"] }}</button>
            <button class="button is-danger" type="button" :disabled="!profileStore.canDelete" @click="deleteUser">{{ i18n["button.delete"] }}</button>
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
                                <p>{{ profileStore.fullname }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["user.email"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ profileStore.email }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["user.authentication"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ accountProviders[profileStore.accountProvider] ?? null }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["user.permissions"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ profileStore.isAdmin ? i18n["role.admin"] : i18n["role.user"] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["user.status"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p :class="{ 'has-text-danger': profileStore.isDisabled }">
                                    {{ profileStore.isDisabled ? i18n["user.disabled"] : i18n["user.enabled"] }}
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
                                <p>{{ profileStore.description || "&mdash;" }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["user.language"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ locales[profileStore.locale] ?? null }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["user.timezone"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ profileStore.timezone }}</p>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
            <edit-user-dialog
                ref="dlgEditUser"
                :header="i18n['user.edit']"
                :timezones="timezones"
                :errors="errors"
                @submit="updateUser"
            ></edit-user-dialog>
            <set-password-dialog
                ref="dlgSetPassword"
                :header="i18n['password.change']"
                :errors="errors"
                @submit="setPassword"
            ></set-password-dialog>
        </div>
    </section>
</template>

<script src="./ProfileTab.js"></script>
