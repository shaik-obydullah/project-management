import axios from 'axios';
import { defineStore } from 'pinia';

export const useOrganizationStore = defineStore('organization', {
    state: () => ({
        organizations: [],
        currentOrganization: null,
        loading: false,
    }),
    actions: {
        async fetchOrganizations() {
            this.loading = true;
            try {
                const { data } = await axios.get('/organizations');
                this.organizations = data.organizations;
            } finally {
                this.loading = false;
            }
        },
        async fetchOrganization(id) {
            this.loading = true;
            try {
                const { data } = await axios.get(`/organizations/${id}`);
                this.currentOrganization = data.organization;
                return data.organization;
            } finally {
                this.loading = false;
            }
        },
        async createOrganization(payload) {
            const { data } = await axios.post('/organizations', payload);
            return data.organization;
        },
        async updateOrganization(id, payload) {
            const { data } = await axios.put(`/organizations/${id}`, payload);
            this.currentOrganization = { ...this.currentOrganization, ...data.organization };
            return data.organization;
        },
        async deleteOrganization(id) {
            await axios.delete(`/organizations/${id}`);
        },
        async addMember(id, payload) {
            const { data } = await axios.post(`/organizations/${id}/members`, payload);
            return data;
        },
        async removeMember(orgId, userId) {
            await axios.delete(`/organizations/${orgId}/members/${userId}`);
        },
    },
});