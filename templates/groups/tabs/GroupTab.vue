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
                            <p class="label">{{ i18n["group.name"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ groupStore.name }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["project"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ groupStore.projectName || "&mdash;" }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n["group.description"] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ groupStore.description || "&mdash;" }}</p>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
    </section>
</template>

<script src="./GroupTab.js"></script>
