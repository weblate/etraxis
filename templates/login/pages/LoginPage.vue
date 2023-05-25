<template>
    <div class="box">
        <form @submit.prevent="login">
            <div class="field">
                <div class="control">
                    <input class="input" type="email" :maxlength="MAX_EMAIL" :placeholder="i18n['user.email']" required autofocus v-model="email" />
                </div>
            </div>
            <div class="field">
                <div class="control">
                    <input class="input" type="password" :maxlength="MAX_PASSWORD" :placeholder="i18n['user.password']" required v-model="password" />
                </div>
            </div>
            <div class="field">
                <div class="control">
                    <label class="checkbox">
                        <input type="checkbox" v-model="remember" />
                        <span>{{ i18n['security.remember_me'] }}</span>
                    </label>
                </div>
            </div>
            <div class="columns is-mobile is-align-items-center">
                <div class="column is-narrow">
                    <div class="field">
                        <div class="control">
                            <button class="button" type="submit">{{ i18n['security.login'] }}</button>
                        </div>
                    </div>
                </div>
                <div class="column">
                    <div class="field">
                        <div class="control has-text-right">
                            <router-link to="/forgot">{{ i18n['password.forgot'] }}</router-link>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</template>

<script src="./LoginPage.js"></script>
