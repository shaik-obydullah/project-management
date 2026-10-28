<template>
  <div class="p-4 lg:p-8">
    <div class="mb-8 flex items-center justify-between">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
        <p class="text-gray-500 mt-1">Welcome back, {{ auth.user?.name?.split(' ')[0] }}!</p>
      </div>
      <button @click="openCreateProject" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
        <Icon name="plus" size="sm" /> New Project
      </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-8">
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">My Open Issues</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.my_open_issues ?? 0 }}</p>
          </div>
          <div class="p-3 rounded-lg bg-indigo-50 text-indigo-600"><Icon name="board" /></div>
        </div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Updated This Week</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.updated_this_week ?? 0 }}</p>
          </div>
          <div class="p-3 rounded-lg bg-green-50 text-green-600"><Icon name="clock" /></div>
        </div>
      </div>
      <div class="bg-white rounded-xl border border-gray-200 p-5">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-sm text-gray-500">Projects</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total_projects ?? 0 }}</p>
          </div>
          <div class="p-3 rounded-lg bg-purple-50 text-purple-600"><Icon name="project" /></div>
        </div>
      </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- My Issues -->
      <div class="lg:col-span-2">
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">My Issues</h2>
            <button @click="router.push('/search')" class="text-sm text-indigo-600 hover:text-indigo-800">View all</button>
          </div>
          <div v-if="loading" class="py-10">
            <Spinner />
          </div>
          <div v-else-if="myIssues.length === 0" class="py-10 text-center text-gray-500 text-sm">
            No issues assigned to you yet
          </div>
          <div v-else class="divide-y divide-gray-100">
            <router-link v-for="issue in myIssues" :key="issue.id"
                 :to="`/issues/${issue.id}`"
                 class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition">
              <span class="text-xs font-mono text-gray-500 w-20 shrink-0">{{ issue.key }}</span>
              <span class="text-sm text-gray-800 truncate flex-1">{{ issue.title }}</span>
              <span v-if="issue.priority === 'high'" class="text-xs px-2 py-0.5 rounded bg-red-50 text-red-600">High</span>
              <span v-else-if="issue.priority === 'urgent'" class="text-xs px-2 py-0.5 rounded bg-red-100 text-red-700 font-medium">Urgent</span>
              <span class="text-xs font-mono" :style="{ color: issue.project?.color }">{{ issue.project?.key }}</span>
            </router-link>
          </div>
        </div>
      </div>

      <!-- Recent Projects -->
      <div>
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-200">
            <h2 class="font-semibold text-gray-900">Recent Projects</h2>
          </div>
          <div v-if="loading" class="py-10"><Spinner /></div>
          <div v-else-if="projects.length === 0" class="py-10 text-center text-gray-500 text-sm">
            No projects yet
          </div>
          <div v-else class="divide-y divide-gray-100">
            <router-link v-for="project in projects" :key="project.id"
                 :to="`/projects/${project.id}`"
                 class="flex items-center gap-3 px-5 py-3.5 hover:bg-gray-50 transition">
              <span class="w-2.5 h-2.5 rounded-full shrink-0" :style="{ backgroundColor: project.color }"></span>
              <div class="flex-1 min-w-0">
                <div class="text-sm font-medium text-gray-800 truncate">{{ project.name }}</div>
                <div class="text-xs text-gray-500">{{ project.issues_count }} issues</div>
              </div>
              <Icon name="chevron-right" size="sm" class="text-gray-400" />
            </router-link>
          </div>
        </div>
      </div>
    </div>

    <!-- Create Project Modal -->
    <Modal :show="showCreateModal" title="Create Project" @close="showCreateModal = false">
      <form @submit.prevent="createProject">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Project Name</label>
            <input v-model="projectForm.name" type="text" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                   placeholder="e.g. Website Redesign">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Project Key</label>
            <input v-model="projectForm.key" type="text" required maxlength="10"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 uppercase"
                   placeholder="e.g. WEB">
            <p class="mt-1 text-xs text-gray-500">Uppercase letters and numbers only. Used in issue keys like WEB-1.</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Organization</label>
            <select v-model="projectForm.organization_id" required
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
              <option v-for="org in organizations" :key="org.id" :value="org.id">{{ org.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea v-model="projectForm.description" rows="3"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                      placeholder="What is this project about?"></textarea>
          </div>
          <button type="submit" :disabled="submitting"
                  class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition disabled:opacity-50">
            {{ submitting ? 'Creating...' : 'Create Project' }}
          </button>
        </div>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { onMounted, ref } from 'vue';
import { useRouter } from 'vue-router';
import axios from 'axios';
import { useAuthStore } from '../../stores/auth';
import { useOrganizationStore } from '../../stores/organization';
import Icon from '../../shared/components/Icon.vue';
import Modal from '../../shared/components/Modal.vue';
import Spinner from '../../shared/components/Spinner.vue';

const router = useRouter();
const auth = useAuthStore();
const orgStore = useOrganizationStore();

const loading = ref(true);
const myIssues = ref([]);
const projects = ref([]);
const stats = ref({});

const showCreateModal = ref(false);
const submitting = ref(false);
const projectForm = ref({ name: '', key: '', organization_id: null, description: '' });

const organizations = orgStore.organizations;

function openCreateProject() {
    projectForm.value = { name: '', key: '', organization_id: organizations.value[0]?.id || null, description: '' };
    showCreateModal.value = true;
}

async function createProject() {
    submitting.value = true;
    try {
        await axios.post(`/organizations/${projectForm.value.organization_id}/projects`, {
            name: projectForm.value.name,
            key: projectForm.value.key,
            description: projectForm.value.description,
        });
        showCreateModal.value = false;
        await loadData();
    } finally {
        submitting.value = false;
    }
}

async function loadData() {
    loading.value = true;
    try {
        const { data } = await axios.get('/dashboard');
        myIssues.value = data.my_issues;
        projects.value = data.projects;
        stats.value = data.stats;
    } finally {
        loading.value = false;
    }
}

onMounted(async () => {
    await orgStore.fetchOrganizations();
    await loadData();
});
</script>