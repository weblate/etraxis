<template>
    <modal ref="modal" :header="header" auto-close @submit="submit">
        <fieldset class="fieldset">
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-password`">{{ i18n['password.new'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <input
                                class="input"
                                :class="{ 'is-danger': errors['password'] }"
                                type="password"
                                :id="`${uid}-password`"
                                placeholder="required"
                                v-model="password"
                            />
                        </div>
                        <p class="help is-danger">{{ errors['password'] }}</p>
                    </div>
                </div>
            </div>
            <div class="field is-horizontal">
                <div class="field-label is-normal">
                    <label class="label" :for="`${uid}-confirmation`">{{ i18n['password.confirmation'] }}:</label>
                </div>
                <div class="field-body">
                    <div class="field">
                        <div class="control">
                            <input
                                class="input"
                                :class="{ 'is-danger': errors['confirmation'] }"
                                type="password"
                                :id="`${uid}-confirmation`"
                                placeholder="required"
                                v-model="confirmation"
                            />
                        </div>
                        <p class="help is-danger">{{ errors['confirmation'] }}</p>
                    </div>
                </div>
            </div>
        </fieldset>
    </modal>
</template>

<script src="./SetPasswordDialog.js"></script>
