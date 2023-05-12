<template>
    <section>
        <div class="columns">
            <div class="column is-one-third-tablet is-one-quarter-desktop">
                <fieldset class="fieldset">
                    <div class="select is-multiple is-fullwidth">
                        <select size="10" v-model="groupId">
                            <option v-for="group in projectStore.projectGroups" :key="group.id" :value="group.id">{{ group.name }}</option>
                            <option v-if="projectStore.projectGroups.length !== 0 && projectStore.globalGroups.length !== 0" class="delimiter" disabled></option>
                            <option v-for="group in projectStore.globalGroups" :key="group.id" :value="group.id">{{ group.name }} ({{ i18n['group.global'] }})</option>
                        </select>
                    </div>
                </fieldset>
            </div>
            <div class="column is-two-thirds-tablet is-three-quarters-desktop">
                <tabs v-if="groupId" simplified v-model="tab">
                    <tab id="group" :title="i18n['group']">
                        <group-tab @update="onGroupUpdated" @delete="onGroupDeleted"></group-tab>
                    </tab>
                    <tab id="members" :title="i18n['group.membership']" :counter="membersStore.groupMembers.length">
                        <members-tab></members-tab>
                    </tab>
                </tabs>
            </div>
        </div>
    </section>
</template>

<script src="./GroupsTab.js"></script>
