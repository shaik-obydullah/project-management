import './bootstrap';
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { createRouter, createWebHistory } from 'vue-router';
import App from './App.vue';
import routes from './router/index';
import { useAuthStore } from './stores/auth';
import '../css/app.css';

const app = createApp(App);
const pinia = createPinia();

const router = createRouter({
    history: createWebHistory(),
    routes,
});

router.beforeEach((to) => {
    const auth = useAuthStore(pinia);

    if (to.matched.some((r) => r.meta.auth) && !auth.isAuthenticated) {
        return { name: 'login' };
    }

    if (to.matched.some((r) => r.meta.guest) && auth.isAuthenticated) {
        return { name: 'dashboard' };
    }
});

app.use(pinia);
app.use(router);

app.mount('#app');