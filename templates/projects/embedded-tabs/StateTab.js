//----------------------------------------------------------------------
//
//  Copyright (C) 2017-2023 Artem Rodygin
//
//  This file is part of eTraxis.
//
//  You should have received a copy of the GNU General Public License
//  along with eTraxis. If not, see <https://www.gnu.org/licenses/>.
//
//----------------------------------------------------------------------

import { mapStores } from 'pinia';

import axios from 'axios';

import * as ui from '@utilities/blockui';
import * as msg from '@utilities/messagebox';
import parseErrors from '@utilities/parseErrors';
import url from '@utilities/url';

import { useStateStore } from '../stores/StateStore';

import StateDialog from '../dialogs/StateDialog.vue';

/**
 * "State" tab.
 */
export default {
    emits: {
        /**
         * The state is updated.
         *
         * @param {number} id State ID
         */
        update: (id) => typeof id === 'number',

        /**
         * The state is deleted.
         *
         * @param {number} id State ID
         */
        delete: (id) => typeof id === 'number'
    },

    components: {
        'edit-state-dialog': StateDialog
    },

    data: () => ({
        /**
         * @property {Object} errors Dialog errors
         */
        errors: {}
    }),

    computed: {
        /**
         * @property {Object} stateStore Store for state data
         */
        ...mapStores(useStateStore),

        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {Object} stateTypes List of possible state types
         */
        stateTypes: () => ({
            initial: window.i18n['state.initial'],
            intermediate: window.i18n['state.intermediate'],
            final: window.i18n['state.final']
        }),

        /**
         * @property {Object} stateResponsibles List of possible state responsibility values
         */
        stateResponsibles: () => ({
            assign: window.i18n['state.responsible.assign'],
            keep: window.i18n['state.responsible.keep'],
            remove: window.i18n['state.responsible.remove']
        }),

        /**
         * @property {Object} editStateDialog "Edit state" dialog instance
         */
        editStateDialog() {
            return this.$refs.dlgEditState;
        }
    },

    methods: {
        /**
         * Opens "Edit state" dialog.
         */
        openEditStateDialog() {
            const defaults = {
                name: this.stateStore.name,
                type: this.stateStore.type,
                responsible: this.stateStore.responsible
            };

            this.errors = {};
            this.editStateDialog.open(defaults);
        },

        /**
         * Updates state.
         *
         * @param {Object} event Submitted values
         */
        async updateState(event) {
            const data = {
                name: event.name,
                responsible: event.responsible
            };

            ui.block();

            try {
                await axios.put(url(`/api/states/${this.stateStore.stateId}`), data);

                this.$emit('update', this.stateStore.stateId);

                msg.info(this.i18n['text.changes_saved'], () => {
                    this.editStateDialog.close();
                });
            } catch (exception) {
                this.errors = parseErrors(exception);
            } finally {
                ui.unblock();
            }
        },

        /**
         * Deletes the state.
         */
        deleteState() {
            msg.confirm(this.i18n['confirm.state.delete'], async () => {
                ui.block();

                try {
                    await axios.delete(url(`/api/states/${this.stateStore.stateId}`));

                    this.$emit('delete', this.stateStore.stateId);
                } catch (exception) {
                    parseErrors(exception);
                } finally {
                    ui.unblock();
                }
            });
        }
    }
};
