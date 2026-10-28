import axios from 'axios';
import { defineStore } from 'pinia';

export const useAuthStore = defineStore('auth', {
    state: () => ({
        user: JSON.parse(localStorage.getItem('user') || 'null'),
        token: localStorage.getItem('token'),
        loading: false,
    }),
    getters: {
        isAuthenticated: (state) => !!state.token,
    },
    actions: {
        async login(email, password) {
            this.loading = true;
            try {
                const { data } = await axios.post('/login', { email, password });
                this.user = data.user;
                this.token = data.token;
                localStorage.setItem('token', data.token);
                localStorage.setItem('user', JSON.stringify(data.user));
                return { success: true };
            } catch (error) {
                return {
                    success: false,
                    message: error.response?.data?.message || 'Login failed',
                };
            } finally {
                this.loading = false;
            }
        },
        async register(name, email, password, password_confirmation) {
            this.loading = true;
            try {
                const { data } = await axios.post('/register', {
                    name,
                    email,
                    password,
                    password_confirmation,
                });
                this.user = data.user;
                this.token = data.token;
                localStorage.setItem('token', data.token);
                localStorage.setItem('user', JSON.stringify(data.user));
                return { success: true };
            } catch (error) {
                return {
                    success: false,
                    message: error.response?.data?.errors?.email?.[0] || error.response?.data?.message || 'Registration failed',
                };
            } finally {
                this.loading = false;
            }
        },
        async logout() {
            try {
                await axios.post('/logout');
            } catch (e) {
                // ignore
            }
            this.user = null;
            this.token = null;
            localStorage.removeItem('token');
            localStorage.removeItem('user');
        },
        async fetchUser() {
            try {
                const { data } = await axios.get('/user');
                this.user = data.user;
                localStorage.setItem('user', JSON.stringify(data.user));
            } catch (e) {
                // 401 handled globally
            }
        },
        setUser(user) {
            this.user = user;
            localStorage.setItem('user', JSON.stringify(user));
        },
    },
});