<template>
    <section>
        <div class="buttons">
            <button class="button" type="button" @click="goBack">{{ i18n['button.back'] }}</button>
        </div>
        <div class="columns">
            <div class="column is-half-tablet">
                <fieldset class="fieldset">
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["project.name"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ projectStore.name }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["project.start_date"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ projectStore.startDate }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["project.status"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p :class="{ 'has-text-danger': projectStore.isSuspended }">
                                    {{ projectStore.isSuspended ? i18n["project.suspended"] : i18n["project.active"] }}
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["project.description"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ projectStore.description || "&mdash;" }}</p>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
    </section>
</template>

<script src="./ProjectTab.js"></script>
