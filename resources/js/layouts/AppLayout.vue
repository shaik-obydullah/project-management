<template>
  <div class="min-h-screen flex flex-col lg:flex-row">
    <!-- Sidebar -->
    <aside class="bg-gray-900 text-gray-100 w-64 hidden lg:flex flex-col shrink-0 min-h-screen sticky top-0">
      <div class="px-5 py-4 flex items-center gap-2.5">
        <div class="w-8 h-8 rounded-lg bg-indigo-500 flex items-center justify-center">
          <Icon name="project" size="sm" />
        </div>
        <span class="font-bold text-lg">ProjectFlow</span>
      </div>

      <nav class="flex-1 px-3 py-4 space-y-1">
        <button @click="go('/')" class="nav-item" :class="{ 'active': route.name === 'dashboard' }">
          <Icon name="dashboard" /><span>Dashboard</span>
        </button>

        <div v-if="organizations.length > 0" class="pt-4">
          <div class="px-3 pb-2 flex items-center justify-between">
            <span class="text-xs font-semibold text-gray-400 uppercase tracking-wider">Organizations</span>
            <button @click="showOrgModal = true" class="text-gray-400 hover:text-white"><Icon name="plus" size="sm" /></button>
          </div>

          <div v-for="org in organizations" :key="org.id" class="mb-1">
            <router-link :to="`/organizations/${org.id}`"
                 class="flex items-center gap-2 px-3 py-2 rounded-lg text-sm hover:bg-gray-800 transition"
                 :class="{ 'bg-gray-800': isOrgActive(org.id) }">
              <span class="w-6 h-6 rounded-md bg-gradient-to-br from-indigo-500 to-purple-500 flex items-center justify-center text-xs font-bold">
                {{ org.name.charAt(0).toUpperCase() }}
              </span>
              <span class="truncate flex-1">{{ org.name }}</span>
            </router-link>

            <div v-if="org.projects" class="ml-4 space-y-0.5 mt-0.5">
              <router-link v-for="proj in org.projects.slice(0, 3)" :key="proj.id"
                   :to="`/projects/${proj.id}`"
                   class="flex items-center gap-2 px-3 py-1.5 rounded-md text-sm text-gray-400 hover:text-white hover:bg-gray-800 transition text-xs"
                   :class="{ '': false }">
                <span class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: proj.color }"></span>
                <span class="truncate">{{ proj.name }}</span>
              </router-link>
            </div>
          </div>
        </div>
      </nav>

      <div class="p-3 border-t border-gray-800">
        <div class="flex items-center gap-3 px-2 py-2">
          <UserAvatar :user="auth.user" size="sm" />
          <div class="flex-1 min-w-0">
            <div class="text-sm font-medium truncate">{{ auth.user?.name }}</div>
            <div class="text-xs text-gray-400 truncate">{{ auth.user?.email }}</div>
          </div>
          <button @click="handleLogout" class="text-gray-400 hover:text-red-400">
            <Icon name="logout" size="sm" />
          </button>
        </div>
      </div>
    </aside>

    <!-- Mobile sidebar -->
    <div v-if="mobileOpen" class="fixed inset-0 z-50 lg:hidden">
      <div class="absolute inset-0 bg-black/50" @click="mobileOpen = false"></div>
      <div class="absolute left-0 top-0 bottom-0 w-64 bg-gray-900 text-gray-100 flex flex-col">
        <div class="px-5 py-4 flex items-center justify-between">
          <div class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-indigo-500 flex items-center justify-center">
              <Icon name="project" size="sm" />
            </div>
            <span class="font-bold text-lg">ProjectFlow</span>
          </div>
          <button @click="mobileOpen = false" class="text-gray-400"><Icon name="x" /></button>
        </div>
      </div>
    </div>

    <button @click="mobileOpen = true" class="lg:hidden fixed bottom-4 right-4 z-40 w-12 h-12 rounded-full bg-indigo-600 text-white shadow-lg flex items-center justify-center">
      <Icon name="menu" />
    </button>

    <!-- Main content -->
    <div class="flex-1 flex flex-col min-w-0">
      <!-- Topbar -->
      <header class="bg-white border-b border-gray-200 sticky top-0 z-30">
        <div class="px-4 lg:px-6 h-14 flex items-center gap-4">
          <button @click="openSearch" class="flex items-center gap-2 flex-1 max-w-md h-9 px-3 rounded-lg bg-gray-100 hover:bg-gray-200 text-gray-500 text-sm">
            <Icon name="search" size="sm" />
            <span>Search issues...</span>
            <kbd class="ml-auto text-[10px] bg-white border border-gray-300 rounded px-1.5 py-0.5">Ctrl K</kbd>
          </button>

          <div class="ml-auto flex items-center gap-2">
            <button @click="openSearch" class="p-2 hover:bg-gray-100 rounded-lg text-gray-500 lg:hidden">
              <Icon name="search" size="sm" />
            </button>
            <button @click="fetchNotifications" class="relative p-2 hover:bg-gray-100 rounded-lg text-gray-500">
              <Icon name="bell" size="sm" />
              <span v-if="unreadCount > 0" class="absolute top-1 right-1 w-2 h-2 rounded-full bg-red-500"></span>
            </button>
          </div>
        </div>
      </header>

      <main class="flex-1 min-w-0">
        <router-view />
      </main>
    </div>

    <!-- Global Search Modal -->
    <Modal :show="searchOpen" title="Search" @close="searchOpen = false">
      <div class="space-y-4">
        <input v-model="searchQuery" type="text" placeholder="Search issues and projects..."
               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
               @keyup.enter="runSearch" autofocus>
        <div v-if="searchResults.issues?.length || searchResults.projects?.length" class="space-y-4">
          <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Issues</h4>
            <div v-for="issue in searchResults.issues" :key="issue.id"
                 class="flex items-center gap-2 px-2 py-2 hover:bg-gray-50 rounded-lg cursor-pointer"
                 @click="goToIssue(issue)">
              <span class="text-xs text-gray-500 font-mono">{{ issue.key }}</span>
              <span class="text-sm truncate flex-1">{{ issue.title }}</span>
              <span class="text-xs font-mono" :style="{ color: issue.project?.color }">{{ issue.project?.key }}</span>
            </div>
          </div>
          <div>
            <h4 class="text-xs font-semibold text-gray-500 uppercase mb-2">Projects</h4>
            <div v-for="project in searchResults.projects" :key="project.id"
                 class="flex items-center gap-2 px-2 py-2 hover:bg-gray-50 rounded-lg cursor-pointer"
                 @click="go(`/projects/${project.id}`)">
              <span class="w-2 h-2 rounded-full" :style="{ backgroundColor: project.color }"></span>
              <span class="text-sm">{{ project.name }}</span>
            </div>
          </div>
        </div>
        <p v-else-if="searchQuery && searched" class="text-sm text-gray-500 text-center py-4">No results found</p>
      </div>
    </Modal>

    <!-- Create Organization Modal -->
    <Modal :show="showOrgModal" title="Create Organization" @close="showOrgModal = false">
      <form @submit.prevent="createOrg">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Organization Name</label>
            <input v-model="orgForm.name" type="text" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                   placeholder="e.g. Acme Corp">
          </div>
          <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-medium py-2 px-4 rounded-lg transition">
            Create Organization
          </button>
        </div>
      </form>
    </Modal>

    <!-- Notifications panel -->
    <Modal :show="notificationOpen" title="Notifications" @close="notificationOpen = false">
      <div v-if="notifications.length === 0" class="py-8 text-center text-gray-500 text-sm">No notifications yet</div>
      <div v-else class="space-y-3">
        <button @click="markAllRead" class="text-xs text-indigo-600 hover:text-indigo-800 mb-2">Mark all as read</button>
        <div v-for="n in notifications" :key="n.id"
             class="p-3 rounded-lg border cursor-pointer"
             :class="n.read_at ? 'bg-white border-gray-200' : 'bg-indigo-50 border-indigo-200'"
             @click="openNotification(n)">
          <div class="text-sm font-medium">{{ n.data.title }}</div>
          <div class="text-xs text-gray-500 mt-0.5">{{ n.data.message }}</div>
          <div class="text-xs text-gray-400 mt-1">{{ timeAgo(n.created_at) }}</div>
        </div>
      </div>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useAuthStore } from '../stores/auth';
import { useOrganizationStore } from '../stores/organization';
import axios from 'axios';
import UserAvatar from '../shared/components/UserAvatar.vue';
import Icon from '../shared/components/Icon.vue';
import Modal from '../shared/components/Modal.vue';

const route = useRoute();
const router = useRouter();
const auth = useAuthStore();
const orgStore = useOrganizationStore();

const mobileOpen = ref(false);
const searchOpen = ref(false);
const searchQuery = ref('');
const searchResults = ref({ issues: [], projects: [] });
const searched = ref(false);

const showOrgModal = ref(false);
const orgForm = ref({ name: '' });

const notificationOpen = ref(false);
const notifications = ref([]);
const unreadCount = ref(0);

const organizations = computed(() => orgStore.organizations);

function go(path) {
    router.push(path);
    searchOpen.value = false;
}

function isOrgActive(id) {
    return route.name === 'organizations.show' && route.params.id === String(id);
}

function openSearch() {
    searchOpen.value = true;
    searchQuery.value = '';
    searchResults.value = { issues: [], projects: [] };
    searched.value = false;
}

async function runSearch() {
    if (!searchQuery.value.trim()) return;
    try {
        const { data } = await axios.get('/search', { params: { q: searchQuery.value } });
        searchResults.value = data;
        searched.value = true;
    } catch (e) {
        // ignore
    }
}

function goToIssue(issue) {
    window.location.href = `/issues/${issue.id}`;
}

async function createOrg() {
    await orgStore.createOrganization(orgForm.value);
    showOrgModal.value = false;
    orgForm.value = { name: '' };
    await orgStore.fetchOrganizations();
}

async function fetchNotifications() {
    notificationOpen.value = true;
    try {
        const { data } = await axios.get('/notifications', { params: { per_page: 10 } });
        notifications.value = data.notifications.data;
        unreadCount.value = data.unread_count;
    } catch (e) { /* ignore */ }
}

async function markAllRead() {
    await axios.put('/notifications/read-all');
    notifications.value = notifications.value.map((n) => ({ ...n, read_at: new Date().toISOString() }));
    unreadCount.value = 0;
}

function openNotification(n) {
    const issueId = n.data?.issue_id;
    if (issueId) {
        window.location.href = `/issues/${issueId}`;
    }
}

function timeAgo(date) {
    const seconds = Math.floor((new Date() - new Date(date)) / 1000);
    if (seconds < 60) return `${seconds}s ago`;
    const minutes = Math.floor(seconds / 60);
    if (minutes < 60) return `${minutes}m ago`;
    const hours = Math.floor(minutes / 60);
    if (hours < 24) return `${hours}h ago`;
    const days = Math.floor(hours / 24);
    return `${days}d ago`;
}

async function handleLogout() {
    await auth.logout();
    router.push('/login');
}

onMounted(() => {
    orgStore.fetchOrganizations();
});

// Global keyboard shortcut for search
window.addEventListener('keydown', (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        openSearch();
    }
});
</script>

<style scoped>
@reference "../../css/app.css";

.nav-item {
    @apply w-full flex items-center gap-3 px-3 py-2 rounded-lg text-sm text-gray-300 hover:bg-gray-800 hover:text-white transition;
}
.nav-item.active {
    @apply bg-gray-800 text-white;
}
</style>