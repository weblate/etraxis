<template>
    <dialog>
        <form @submit.prevent="onSubmit">
            <div class="modal" :class="{ 'is-active': isActive }" :style="offset">
                <div class="modal-card">
                    <header class="modal-card-head is-clickable" @mousedown="onMouseDown">
                        <p class="modal-card-title">{{ header }}</p>
                        <span class="delete" :title="i18n['button.close']" @click="onCancel"></span>
                    </header>
                    <section class="modal-card-body">
                        <slot></slot>
                    </section>
                    <footer class="modal-card-foot is-justify-content-right">
                        <button v-show="false" type="submit"></button>
                        <slot name="footer">
                            <button class="button" type="button" @click="onSubmit">{{ i18n["button.ok"] }}</button>
                            <button class="button" type="button" @click="onCancel">{{ i18n["button.cancel"] }}</button>
                        </slot>
                    </footer>
                </div>
            </div>
        </form>
    </dialog>
</template>

<script src="./modal.js"></script>
