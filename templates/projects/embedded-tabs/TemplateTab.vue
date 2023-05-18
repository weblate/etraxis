<template>
    <section>
        <div class="columns">
            <div class="column is-two-thirds-desktop">
                <fieldset class="fieldset">
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n['template.name'] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ templateStore.name }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n['template.prefix'] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ templateStore.prefix }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n['template.description'] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ templateStore.description || '&mdash;' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n['template.status'] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ templateStore.isLocked ? i18n['template.locked'] : i18n['template.active'] }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n['template.critical_age'] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ templateStore.criticalAge || '&mdash;' }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="field is-horizontal">
                        <div class="field-label">
                            <p class="label">{{ i18n['template.frozen_time'] }}:</p>
                        </div>
                        <div class="field-body">
                            <div class="content">
                                <p>{{ templateStore.frozenTime || '&mdash;' }}</p>
                            </div>
                        </div>
                    </div>
                </fieldset>
            </div>
        </div>
    </section>
</template>

<script src="./TemplateTab.js"></script>
