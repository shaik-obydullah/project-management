import axios from 'axios';
import { defineStore } from 'pinia';

export const useIssueStore = defineStore('issue', {
    state: () => ({
        issues: [],
        currentIssue: null,
        loading: false,
    }),
    actions: {
        async fetchIssues(projectId, params = {}) {
            this.loading = true;
            try {
                const { data } = await axios.get(`/projects/${projectId}/issues`, { params });
                this.issues = data.issues.data || [];
                return data.issues;
            } finally {
                this.loading = false;
            }
        },
        async fetchIssue(id) {
            this.loading = true;
            try {
                const { data } = await axios.get(`/issues/${id}`);
                this.currentIssue = data.issue;
                return data;
            } finally {
                this.loading = false;
            }
        },
        async createIssue(projectId, payload) {
            const { data } = await axios.post(`/projects/${projectId}/issues`, payload);
            return data.issue;
        },
        async updateIssue(id, payload) {
            const { data } = await axios.put(`/issues/${id}`, payload);
            this.currentIssue = { ...this.currentIssue, ...data.issue };
            return data.issue;
        },
        async deleteIssue(id) {
            await axios.delete(`/issues/${id}`);
        },
        async addComment(issueId, payload) {
            const { data } = await axios.post(`/issues/${issueId}/comments`, payload);
            return data.comment;
        },
        async updateComment(id, payload) {
            const { data } = await axios.put(`/comments/${id}`, payload);
            return data.comment;
        },
        async deleteComment(id) {
            await axios.delete(`/comments/${id}`);
        },
        async uploadAttachments(issueId, files) {
            const formData = new FormData();
            files.forEach((file) => formData.append('files[]', file));
            const { data } = await axios.post(`/issues/${issueId}/attachments`, formData, {
                headers: { 'Content-Type': 'multipart/form-data' },
            });
            return data.attachments;
        },
    },
});