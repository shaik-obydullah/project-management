import { createRouter, createWebHistory } from 'vue-router';

const routes = [
    {
        path: '/login',
        name: 'login',
        component: () => import('../features/auth/Login.vue'),
        meta: { guest: true, layout: 'auth' },
    },
    {
        path: '/register',
        name: 'register',
        component: () => import('../features/auth/Register.vue'),
        meta: { guest: true, layout: 'auth' },
    },
    {
        path: '/',
        component: () => import('../layouts/AppLayout.vue'),
        meta: { auth: true },
        children: [
            {
                path: '',
                name: 'dashboard',
                component: () => import('../features/dashboard/Dashboard.vue'),
            },
            {
                path: '/organizations',
                name: 'organizations.index',
                component: () => import('../features/organizations/OrganizationsIndex.vue'),
            },
            {
                path: '/organizations/:id',
                name: 'organizations.show',
                component: () => import('../features/organizations/OrganizationShow.vue'),
                props: true,
            },
            {
                path: '/projects/:id',
                name: 'projects.show',
                component: () => import('../features/projects/ProjectShow.vue'),
                props: true,
            },
            {
                path: '/projects/:id/board',
                name: 'projects.board',
                component: () => import('../features/board/Board.vue'),
                props: true,
            },
            {
                path: '/projects/:id/sprints',
                name: 'projects.sprints',
                component: () => import('../features/sprints/Sprints.vue'),
                props: true,
            },
            {
                path: '/projects/:id/issues',
                name: 'projects.issues',
                component: () => import('../features/issues/IssuesList.vue'),
                props: true,
            },
            {
                path: '/projects/:id/settings',
                name: 'projects.settings',
                component: () => import('../features/projects/ProjectSettings.vue'),
                props: true,
            },
            {
                path: '/issues/:id',
                name: 'issues.show',
                component: () => import('../features/issues/IssueDetail.vue'),
                props: true,
            },
            {
                path: '/search',
                name: 'search',
                component: () => import('../features/search/Search.vue'),
            },
        ],
    },
];

export default routes;