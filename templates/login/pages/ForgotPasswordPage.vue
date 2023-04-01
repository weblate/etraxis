<template>
    <div class="box">
        <form @submit.prevent="submit">
            <div class="field">
                <div class="control">
                    <input class="input" type="email" :placeholder="i18n['user.email']" required autofocus v-model="email" />
                </div>
            </div>
            <div class="field is-grouped">
                <div class="control">
                    <button class="button" type="submit">{{ i18n["button.ok"] }}</button>
                </div>
                <div class="control">
                    <router-link to="/login" class="button">{{ i18n["button.cancel"] }}</router-link>
                </div>
            </div>
        </form>
    </div>
</template>

<script src="./ForgotPasswordPage.js"></script>
