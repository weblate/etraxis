<template>
    <div class="box">
        <form @submit.prevent="submit">
            <div class="field">
                <div class="control">
                    <input
                        class="input"
                        type="password"
                        :placeholder="i18n['password.new']"
                        required
                        autocomplete="new-password"
                        autofocus
                        v-model="password"
                    />
                </div>
            </div>
            <div class="field">
                <div class="control">
                    <input
                        class="input"
                        type="password"
                        :placeholder="i18n['password.confirmation']"
                        required
                        autocomplete="new-password"
                        v-model="confirmation"
                    />
                </div>
            </div>
            <div class="field">
                <div class="control">
                    <button class="button" type="submit">{{ i18n['button.save'] }}</button>
                </div>
            </div>
        </form>
    </div>
</template>

<script src="./ResetPasswordPage.js"></script>
