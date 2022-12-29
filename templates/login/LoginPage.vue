<template>
    <div class="box">
        <form @submit.prevent="login">
            <div class="field">
                <div class="control">
                    <input class="input" type="email" :placeholder="i18n['user.email']" required autofocus v-model="email" />
                </div>
            </div>
            <div class="field">
                <div class="control">
                    <input class="input" type="password" :placeholder="i18n['user.password']" required v-model="password" />
                </div>
            </div>
            <div class="field">
                <div class="control">
                    <button class="button" type="submit">{{ i18n["security.login"] }}</button>
                </div>
            </div>
        </form>
    </div>
</template>

<script src="./LoginPage.js"></script>
