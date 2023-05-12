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

import { useGroupStore } from '../../groups/stores/GroupStore';

import GroupDialog from '../../groups/dialogs/GroupDialog.vue';

/**
 * "Group" tab.
 */
export default {
    emits: {
        /**
         * The group is updated.
         *
         * @param {number} id Group ID
         */
        update: (id) => typeof id === 'number',

        /**
         * The group is deleted.
         *
         * @param {number} id Group ID
         */
        delete: (id) => typeof id === 'number'
    },

    components: {
        'edit-group-dialog': GroupDialog
    },

    data: () => ({
        /**
         * @property {Object} errors Dialog errors
         */
        errors: {}
    }),

    computed: {
        /**
         * @property {Object} groupStore Store for group data
         */
        ...mapStores(useGroupStore),

        /**
         * @property {Object} i18n Translation resources
         */
        i18n: () => window.i18n,

        /**
         * @property {Object} editGroupDialog "Edit group" dialog instance
         */
        editGroupDialog() {
            return this.$refs.dlgEditGroup;
        }
    },

    methods: {
        /**
         * Opens "Edit group" dialog.
         */
        openEditGroupDialog() {
            const defaults = {
                name: this.groupStore.name,
                description: this.groupStore.description
            };

            this.errors = {};
            this.editGroupDialog.open(defaults);
        },

        /**
         * Updates group.
         *
         * @param {Object} event Submitted values
         */
        async updateGroup(event) {
            const data = {
                name: event.name,
                description: event.description || null
            };

            ui.block();

            try {
                await axios.put(url(`/api/groups/${this.groupStore.groupId}`), data);

                this.$emit('update', this.groupStore.groupId);

                msg.info(this.i18n['text.changes_saved'], () => {
                    this.editGroupDialog.close();
                });
            } catch (exception) {
                this.errors = parseErrors(exception);
            } finally {
                ui.unblock();
            }
        },

        /**
         * Deletes the group.
         */
        deleteGroup() {
            msg.confirm(this.i18n['confirm.group.delete'], async () => {
                ui.block();

                try {
                    await axios.delete(url(`/api/groups/${this.groupStore.groupId}`));

                    this.$emit('delete', this.groupStore.groupId);
                } catch (exception) {
                    parseErrors(exception);
                } finally {
                    ui.unblock();
                }
            });
        }
    }
};
