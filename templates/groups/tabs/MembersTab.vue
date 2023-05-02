<template>
    <section>
        <div class="columns is-vcentered">
            <div class="column">
                <fieldset class="fieldset">
                    <legend>{{ i18n['group.members'] }}</legend>
                    <div class="select is-multiple is-fullwidth">
                        <select size="10" multiple="multiple" v-model="usersToRemove">
                            <option v-if="membersStore.groupMembers.length === 0" disabled></option>
                            <option v-for="user in membersStore.groupMembers" :key="user.id" :value="user.id">{{ user.fullname }} ({{ user.email }})</option>
                        </select>
                    </div>
                </fieldset>
            </div>
            <div class="column is-narrow">
                <div class="is-flex">
                    <button class="button is-fullwidth" :title="i18n['button.remove']" :disabled="usersToRemove.length === 0" @click="removeUsers">
                        <span class="icon">
                            <i class="is-hidden-tablet fa fa-arrow-down"></i>
                            <i class="is-hidden-mobile fa fa-arrow-right"></i>
                        </span>
                    </button>
                    <button class="button is-fullwidth" :title="i18n['button.add']" :disabled="usersToAdd.length === 0" @click="addUsers">
                        <span class="icon">
                            <i class="is-hidden-tablet fa fa-arrow-up"></i>
                            <i class="is-hidden-mobile fa fa-arrow-left"></i>
                        </span>
                    </button>
                </div>
            </div>
            <div class="column">
                <fieldset class="fieldset">
                    <legend>{{ i18n['users.others'] }}</legend>
                    <div class="select is-multiple is-fullwidth">
                        <select size="10" multiple="multiple" v-model="usersToAdd">
                            <option v-if="membersStore.otherUsers.length === 0" disabled></option>
                            <option v-for="user in membersStore.otherUsers" :key="user.id" :value="user.id">{{ user.fullname }} ({{ user.email }})</option>
                        </select>
                    </div>
                </fieldset>
            </div>
        </div>
    </section>
</template>

<script src="./MembersTab.js"></script>
