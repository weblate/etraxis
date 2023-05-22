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

import { defineStore } from 'pinia';

import axios from 'axios';

import * as ui from '@utilities/blockui';
import { date } from '@utilities/epoch';
import loadAll from '@utilities/loadAll';
import parseErrors from '@utilities/parseErrors';
import url from '@utilities/url';

/**
 * Store for project data.
 */
export const useProjectStore = defineStore('project', {
    state: () => ({
        /**
         * @property {Object} project Project data
         */
        project: {
            id: null,
            name: null,
            description: null,
            createdAt: null,
            suspended: null,
            actions: {
                update: false,
                delete: false,
                suspend: false,
                resume: false
            }
        },

        /**
         * @property {Array<Object>} projectGroups List of all project groups
         */
        projectGroups: [],

        /**
         * @property {Array<Object>} globalGroups List of all global groups
         */
        globalGroups: [],

        /**
         * @property {Array<Object>} projectTemplates List of all project templates
         */
        projectTemplates: [],

        /**
         * @property {Map} templateStates Map of all project states (key - template ID, value - array of template states)
         */
        templateStates: new Map(),

        /**
         * @property {Map} stateFields Map of all project fields (key - state ID, value - array of state fields)
         */
        stateFields: new Map()
    }),

    getters: {
        /**
         * @property {number} projectId Project ID
         * @param {Object} state
         */
        projectId: (state) => state.project.id,

        /**
         * @property {string} name Project name
         * @param {Object} state
         */
        name: (state) => state.project.name,

        /**
         * @property {string} description Description
         * @param {Object} state
         */
        description: (state) => state.project.description,

        /**
         * @property {string} startDate Human-readable start date
         * @param {Object} state
         */
        startDate: (state) => date(state.project.createdAt),

        /**
         * @property {boolean} isSuspended Whether the project is suspended
         * @param {Object} state
         */
        isSuspended: (state) => state.project.suspended,

        /**
         * @property {boolean} canUpdate Whether the project can be updated
         * @param {Object} state
         */
        canUpdate: (state) => state.project.actions.update,

        /**
         * @property {boolean} canDelete Whether the project can be deleted
         * @param {Object} state
         */
        canDelete: (state) => state.project.actions.delete,

        /**
         * @property {boolean} canSuspend Whether the project can be suspended
         * @param {Object} state
         */
        canSuspend: (state) => state.project.actions.suspend,

        /**
         * @property {boolean} canResume Whether the project can be resumed
         * @param {Object} state
         */
        canResume: (state) => state.project.actions.resume,

        /**
         * @property {Array<Object>} getProjectTemplates List of all project templates
         * @param {Object} state
         */
        getProjectTemplates: (state) => state.projectTemplates,

        /**
         * @property {Array<Object>} getTemplateStates List of all states of the specified template
         * @param {Object} state
         */
        getTemplateStates: (state) => (templateId) => {
            return state.templateStates.has(templateId)
                ? state.templateStates.get(templateId)
                : [];
        },

        /**
         * @property {Array<Object>} getStateFields List of all fields of the specified state
         * @param {Object} state
         */
        getStateFields: (state) => (stateId) => {
            return state.stateFields.has(stateId)
                ? state.stateFields.get(stateId)
                : [];
        },

        /**
         * @property {Object} getTemplate Finds the specified template
         * @param {Object} state
         */
        getTemplate: (state) => (templateId) => {
            return state.projectTemplates.find((template) => template.id === templateId);
        },

        /**
         * @property {Object} getState Finds the specified state
         * @param {Object} state
         */
        getState: (state) => (stateId) => {
            return Array.from(state.templateStates.values())
                .flat()
                .find((state) => state.id === stateId);
        },

        /**
         * @property {Object} getField Finds the specified field
         * @param {Object} state
         */
        getField: (state) => (fieldId) => {
            return Array.from(state.stateFields.values())
                .flat()
                .find((field) => field.id === fieldId);
        }
    },

    actions: {
        /**
         * Loads project data from the server.
         *
         * @param {null|number} id Project ID
         */
        async loadProject(id = null) {
            ui.block();

            try {
                const response = await axios.get(url(`/api/projects/${id || this.project.id}`));
                this.project = { ...response.data };
            } catch (exception) {
                parseErrors(exception);
            } finally {
                ui.unblock();
            }
        },

        /**
         * Loads all existing project groups.
         *
         * @param {null|number} id Project ID
         */
        async loadAllProjectGroups(id = null) {
            ui.block();
            this.projectGroups = await loadAll(url('/api/groups'), { project: id || this.project.id }, { name: 'asc' });
            ui.unblock();
        },

        /**
         * Loads all existing global groups.
         */
        async loadAllGlobalGroups() {
            ui.block();
            this.globalGroups = await loadAll(url('/api/groups'), { global: true }, { name: 'asc' });
            ui.unblock();
        },

        /**
         * Loads all existing project templates.
         *
         * @param {null|number} id Project ID
         */
        async loadAllProjectTemplates(id = null) {
            ui.block();
            this.projectTemplates = await loadAll(url('/api/templates'), { project: id || this.project.id }, { name: 'asc' });
            ui.unblock();
        },

        /**
         * Loads all states of the specified template.
         *
         * @param {number} id Template ID
         */
        async loadTemplateStates(id) {
            ui.block();
            const states = await loadAll(url('/api/states'), { template: id }, { name: 'asc' });
            this.templateStates.set(id, states);
            ui.unblock();
        },

        /**
         * Loads all fields of the specified state.
         *
         * @param {number} id State ID
         */
        async loadStateFields(id) {
            ui.block();
            const fields = await loadAll(url('/api/fields'), { state: id }, { name: 'asc' });
            this.stateFields.set(id, fields);
            ui.unblock();
        }
    }
});
