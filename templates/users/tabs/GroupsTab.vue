<template>
    <section>
        <div class="columns is-vcentered">
            <div class="column">
                <fieldset class="fieldset">
                    <legend>{{ i18n['user.groups'] }}</legend>
                    <div class="select is-multiple is-fullwidth">
                        <select size="10" multiple="multiple" v-model="groupsToRemove">
                            <option v-if="groupsStore.userGroups.length === 0" disabled></option>
                            <option v-for="group in groupsStore.userGroups" :key="group.id" :value="group.id">{{ group.name }} ({{ group.global ? i18n['group.global'].toLowerCase() : group.project.name }})</option>
                        </select>
                    </div>
                </fieldset>
            </div>
            <div class="column is-narrow">
                <div class="is-flex">
                    <button class="button is-fullwidth" :title="i18n['button.remove']" :disabled="groupsToRemove.length === 0" @click="removeGroups">
                        <span class="icon">
                            <i class="is-hidden-tablet fa fa-arrow-down"></i>
                            <i class="is-hidden-mobile fa fa-arrow-right"></i>
                        </span>
                    </button>
                    <button class="button is-fullwidth" :title="i18n['button.add']" :disabled="groupsToAdd.length === 0" @click="addGroups">
                        <span class="icon">
                            <i class="is-hidden-tablet fa fa-arrow-up"></i>
                            <i class="is-hidden-mobile fa fa-arrow-left"></i>
                        </span>
                    </button>
                </div>
            </div>
            <div class="column">
                <fieldset class="fieldset">
                    <legend>{{ i18n['user.other_groups'] }}</legend>
                    <div class="select is-multiple is-fullwidth">
                        <select size="10" multiple="multiple" v-model="groupsToAdd">
                            <option v-if="groupsStore.otherGroups.length === 0" disabled></option>
                            <option v-for="group in groupsStore.otherGroups" :key="group.id" :value="group.id">{{ group.name }} ({{ group.global ? i18n['group.global'].toLowerCase() : group.project.name }})</option>
                        </select>
                    </div>
                </fieldset>
            </div>
        </div>
    </section>
</template>

<script src="./GroupsTab.js"></script>
