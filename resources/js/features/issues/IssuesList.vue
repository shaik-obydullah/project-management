<template>
  <div class="p-4 lg:p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Issues</h1>
        <p class="text-sm text-gray-500">{{ project?.name }}</p>
      </div>
      <button @click="openCreate" class="inline-flex items-center gap-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition">
        <Icon name="plus" size="sm" /> New Issue
      </button>
    </div>

    <!-- Filters -->
    <div class="flex flex-wrap gap-2 mb-4 items-center">
      <select v-model="filters.type" @change="loadIssues" class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
        <option value="">All types</option>
        <option value="epic">Epic</option>
        <option value="story">Story</option>
        <option value="task">Task</option>
        <option value="bug">Bug</option>
        <option value="subtask">Subtask</option>
      </select>
      <select v-model="filters.priority" @change="loadIssues" class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white capitalize">
        <option value="">All priorities</option>
        <option value="low">Low</option>
        <option value="medium">Medium</option>
        <option value="high">High</option>
        <option value="urgent">Urgent</option>
      </select>
      <select v-model="filters.assignee" @change="loadIssues" class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
        <option value="">All assignees</option>
        <option v-for="member in project?.members || []" :key="member.id" :value="member.id">{{ member.name }}</option>
      </select>
      <select v-model="filters.sprint_id" @change="loadIssues" class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
        <option value="">All sprints</option>
        <option value="backlog">Backlog (no sprint)</option>
        <option v-for="sprint in project?.sprints || []" :key="sprint.id" :value="sprint.id">{{ sprint.name }}</option>
      </select>
      <select v-model="filters.label_id" @change="loadIssues" class="px-3 py-2 border border-gray-200 rounded-lg text-sm bg-white">
        <option value="">All labels</option>
        <option v-for="label in project?.labels || []" :key="label.id" :value="label.id">{{ label.name }}</option>
      </select>
    </div>

    <div v-if="issueStore.loading" class="py-10"><Spinner /></div>

    <div v-else class="bg-white rounded-xl border border-gray-200 overflow-hidden">
      <div class="overflow-x-auto">
        <table class="w-full text-sm">
          <thead>
            <tr class="text-left text-xs text-gray-500 border-b border-gray-200 bg-gray-50">
              <th class="px-5 py-3 font-medium">Key</th>
              <th class="px-5 py-3 font-medium">Title</th>
              <th class="px-5 py-3 font-medium">Type</th>
              <th class="px-5 py-3 font-medium">Priority</th>
              <th class="px-5 py-3 font-medium">Assignee</th>
              <th class="px-5 py-3 font-medium">Status</th>
              <th class="px-5 py-3 font-medium">Updated</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="issue in issues" :key="issue.id" @click="router.push(`/issues/${issue.id}`)"
                class="hover:bg-gray-50 cursor-pointer transition">
              <td class="px-5 py-3 text-xs font-mono text-gray-500">{{ issue.key }}</td>
              <td class="px-5 py-3">
                <div class="font-medium text-gray-800">{{ issue.title }}</div>
                <div v-if="issue.labels?.length" class="flex flex-wrap gap-1 mt-1">
                  <span v-for="label in issue.labels" :key="label.id" class="text-[10px] px-1.5 py-0.5 rounded"
                        :style="{ backgroundColor: `${label.color}1a`, color: label.color }">{{ label.name }}</span>
                </div>
              </td>
              <td class="px-5 py-3"><Badge :label="issue.type" :color="typeColor(issue.type)" /></td>
              <td class="px-5 py-3">
                <span class="inline-flex items-center gap-1 text-xs capitalize" :class="priorityClass(issue.priority)">
                  <Icon name="flag" size="xs" /> {{ issue.priority }}
                </span>
              </td>
              <td class="px-5 py-3">
                <div class="flex items-center gap-2">
                  <UserAvatar v-if="issue.assignee" :user="issue.assignee" size="xs" />
                  <span v-else class="text-xs text-gray-400">Unassigned</span>
                </div>
              </td>
              <td class="px-5 py-3"><Badge :label="issue.status" :color="statusColor(issue.status)" /></td>
              <td class="px-5 py-3 text-xs text-gray-500">{{ timeAgo(issue.updated_at) }}</td>
            </tr>
            <tr v-if="issues.length === 0">
              <td colspan="7" class="px-5 py-10 text-center text-gray-500">No issues match your filters.</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <div v-if="meta && meta.last_page > 1" class="px-5 py-3 border-t border-gray-200 flex items-center justify-between text-sm">
        <span class="text-gray-500">Showing {{ meta.from || 0 }}–{{ meta.to || 0 }} of {{ meta.total }}</span>
        <div class="flex gap-1">
          <button :disabled="!meta.prev_page_url" @click="page--; loadIssues()"
                  class="px-3 py-1.5 rounded border border-gray-200 text-gray-700 disabled:opacity-40">Prev</button>
          <button :disabled="!meta.next_page_url" @click="page++; loadIssues()"
                  class="px-3 py-1.5 rounded border border-gray-200 text-gray-700 disabled:opacity-40">Next</button>
        </div>
      </div>
    </div>

    <!-- Create Issue Modal -->
    <Modal :show="showCreateModal" title="Create Issue" @close="showCreateModal = false" v-if="project">
      <form @submit.prevent="createIssue">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
            <input v-model="form.title" type="text" required autofocus
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
              <select v-model="form.type" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="task">Task</option>
                <option value="story">Story</option>
                <option value="bug">Bug</option>
                <option value="epic">Epic</option>
                <option value="subtask">Subtask</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
              <select v-model="form.priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="medium">Medium</option><option value="low">Low</option>
                <option value="high">High</option><option value="urgent">Urgent</option>
              </select>
            </div>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Assignee</label>
            <select v-model="form.assigned_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
              <option :value="null">Unassigned</option>
              <option v-for="member in project.members" :key="member.id" :value="member.id">{{ member.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Sprint</label>
            <select v-model="form.sprint_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
              <option :value="null">No sprint</option>
              <option v-for="sprint in project.sprints" :key="sprint.id" :value="sprint.id">{{ sprint.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea v-model="form.description" rows="3"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
          </div>
          <button type="submit" :disabled="creating"
                  class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition disabled:opacity-50">
            {{ creating ? 'Creating...' : 'Create Issue' }}
          </button>
        </div>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useIssueStore } from '../../stores/issue';
import { useProjectStore } from '../../stores/project';
import axios from 'axios';
import Icon from '../../shared/components/Icon.vue';
import Badge from '../../shared/components/Badge.vue';
import Modal from '../../shared/components/Modal.vue';
import Spinner from '../../shared/components/Spinner.vue';
import UserAvatar from '../../shared/components/UserAvatar.vue';

const route = useRoute();
const router = useRouter();
const issueStore = useIssueStore();
const projectStore = useProjectStore();

const issues = ref([]);
const meta = ref(null);
const page = ref(1);
const filters = reactive({ type: '', priority: '', assignee: '', sprint_id: '', label_id: '' });

const showCreateModal = ref(false);
const creating = ref(false);
const form = reactive({ title: '', type: 'task', priority: 'medium', assigned_to: null, sprint_id: null, description: '' });

const project = computed(() => projectStore.currentProject);

async function loadIssues() {
  const params = { page: page.value, per_page: 25 };
  if (filters.type) params.type = filters.type;
  if (filters.priority) params.priority = filters.priority;
  if (filters.assignee) params.assignee = filters.assignee;
  if (filters.label_id) params.label_id = filters.label_id;
  if (filters.sprint_id === 'backlog') params.sprint_id = 0;
  else if (filters.sprint_id) params.sprint_id = filters.sprint_id;

  const result = await issueStore.fetchIssues(route.params.id, params);
  issues.value = result.data;
  meta.value = {
    last_page: result.last_page,
    prev_page_url: result.prev_page_url,
    next_page_url: result.next_page_url,
    from: result.from,
    to: result.to,
    total: result.total,
  };
}

function openCreate() {
  Object.assign(form, { title: '', type: 'task', priority: 'medium', assigned_to: null, sprint_id: null, description: '' });
  showCreateModal.value = true;
}

async function createIssue() {
  creating.value = true;
  try {
    await issueStore.createIssue(route.params.id, form);
    showCreateModal.value = false;
    await loadIssues();
  } finally {
    creating.value = false;
  }
}

function typeColor(type) {
  return { epic: '#8b5cf6', story: '#10b981', task: '#3b82f6', bug: '#ef4444', subtask: '#f59e0b' }[type] || '#64748b';
}

function priorityClass(priority) {
  return { urgent: 'text-red-600', high: 'text-orange-600', medium: 'text-yellow-600', low: 'text-gray-500' }[priority] || '';
}

function statusColor(status) {
  return { todo: '#6b7280', in_progress: '#3b82f6', done: '#10b981' }[status] || '#64748b';
}

function timeAgo(str) {
  const seconds = Math.floor((Date.now() - new Date(str)) / 1000);
  if (seconds < 60) return 'just now';
  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return `${minutes}m ago`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours}h ago`;
  return new Date(str).toLocaleDateString();
}

onMounted(async () => {
  if (!projectStore.currentProject) {
    await projectStore.fetchProject(route.params.id);
  }
  await loadIssues();
});
</script>