import axios from 'axios';
import { defineStore } from 'pinia';

export const useProjectStore = defineStore('project', {
    state: () => ({
        projects: [],
        currentProject: null,
        loading: false,
    }),
    getters: {
        activeProject: (state) => state.currentProject,
    },
    actions: {
        async fetchProjects(organizationId) {
            this.loading = true;
            try {
                const { data } = await axios.get(`/organizations/${organizationId}/projects`);
                this.projects = data.projects;
            } finally {
                this.loading = false;
            }
        },
        async fetchProject(id) {
            this.loading = true;
            try {
                const { data } = await axios.get(`/projects/${id}`);
                this.currentProject = data.project;
                return data.project;
            } finally {
                this.loading = false;
            }
        },
        async createProject(organizationId, payload) {
            const { data } = await axios.post(`/organizations/${organizationId}/projects`, payload);
            return data.project;
        },
        async updateProject(id, payload) {
            const { data } = await axios.put(`/projects/${id}`, payload);
            this.currentProject = { ...this.currentProject, ...data.project };
            return data.project;
        },
        async deleteProject(id) {
            await axios.delete(`/projects/${id}`);
        },
        async fetchBoard(projectId) {
            const { data } = await axios.get(`/projects/${projectId}/board`);
            return data;
        },
        async addColumn(projectId, payload) {
            const { data } = await axios.post(`/projects/${projectId}/columns`, payload);
            return data.column;
        },
        async moveIssue(issueId, columnId, position) {
            const { data } = await axios.post(`/issues/${issueId}/move`, { column_id: columnId, position });
            return data.issue;
        },
    },
});