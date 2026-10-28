<template>
  <div class="p-4 lg:p-6">
    <div class="flex items-center justify-between mb-6">
      <div>
        <h1 class="text-xl font-bold text-gray-900">Sprints</h1>
        <p class="text-sm text-gray-500">{{ project?.name }}</p>
      </div>
      <button @click="openCreateSprint" class="inline-flex items-center gap-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg transition">
        <Icon name="plus" size="sm" /> New Sprint
      </button>
    </div>

    <div v-if="loading" class="py-10"><Spinner /></div>
    <div v-else-if="sprints.length === 0" class="bg-white border border-gray-200 rounded-xl p-10 text-center">
      <div class="text-4xl mb-3">🏃</div>
      <h3 class="text-lg font-semibold text-gray-900 mb-1">No sprints yet</h3>
      <p class="text-sm text-gray-500 mb-4">Create a sprint to plan your work for the next iteration.</p>
      <button @click="openCreateSprint" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
        Create Sprint
      </button>
    </div>

    <div v-else class="space-y-4">
      <div v-for="sprint in sprints" :key="sprint.id"
           class="bg-white rounded-xl border border-gray-200 overflow-hidden"
           :class="{ 'border-indigo-300 ring-1 ring-indigo-100': sprint.status === 'active' }">
        <div class="px-5 py-4 flex items-center justify-between">
          <div class="flex items-center gap-3">
            <span class="text-xs px-2.5 py-1 rounded-full font-medium"
                  :class="{
                    'bg-indigo-50 text-indigo-600': sprint.status === 'active',
                    'bg-gray-100 text-gray-600': sprint.status === 'planned',
                    'bg-green-50 text-green-600': sprint.status === 'completed',
                  }">{{ sprint.status }}</span>
            <div>
              <h2 class="font-semibold text-gray-900">{{ sprint.name }}</h2>
              <p class="text-xs text-gray-500">
                {{ sprint.issues_count }} issues
                <template v-if="sprint.starts_at"> · Since {{ sprint.starts_at }}</template>
                <template v-if="sprint.ends_at"> · Until {{ sprint.ends_at }}</template>
              </p>
            </div>
          </div>
          <div class="flex items-center gap-2">
            <template v-if="sprint.status === 'planned'">
              <button @click="startSprint(sprint)" class="text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition">
                Start
              </button>
            </template>
            <template v-else-if="sprint.status === 'active'">
              <button @click="completeSprint(sprint)" class="text-sm bg-green-600 hover:bg-green-700 text-white px-3 py-1.5 rounded-lg transition">
                Complete
              </button>
            </template>
            <button @click="assignIssues(sprint)" class="text-sm bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-3 py-1.5 rounded-lg transition">
              Assign Issues
            </button>
            <button @click="deleteSprint(sprint)" class="text-gray-400 hover:text-red-500"><Icon name="trash" size="sm" /></button>
          </div>
        </div>

        <div v-if="sprint.goal" class="px-5 py-3 bg-gray-50 border-t border-gray-100">
          <p class="text-sm text-gray-600"><span class="font-medium text-gray-800">Goal:</span> {{ sprint.goal }}</p>
        </div>

        <div v-if="sprint.issues?.length" class="px-5 py-3 border-t border-gray-100 divide-y divide-gray-50">
          <router-link v-for="issue in sprint.issues" :key="issue.id" :to="`/issues/${issue.id}`"
               class="flex items-center gap-3 py-2 hover:bg-gray-50 px-2 -mx-2 rounded transition">
            <span class="text-xs font-mono text-gray-500 w-20">{{ issue.key }}</span>
            <span class="text-sm text-gray-800 truncate flex-1">{{ issue.title }}</span>
            <Badge :label="issue.status" :color="statusColor(issue.status)" />
          </router-link>
        </div>
      </div>
    </div>

    <!-- Create Sprint Modal -->
    <Modal :show="showCreateModal" title="Create Sprint" @close="showCreateModal = false">
      <form @submit.prevent="createSprint">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Sprint Name</label>
            <input v-model="form.name" type="text" required autofocus
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                   placeholder="Sprint 1">
          </div>
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Goal</label>
            <textarea v-model="form.goal" rows="2"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
          </div>
          <div class="grid grid-cols-2 gap-3">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Start Date</label>
              <input v-model="form.starts_at" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">End Date</label>
              <input v-model="form.ends_at" type="date" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
            </div>
          </div>
          <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition">
            Create Sprint
          </button>
        </div>
      </form>
    </Modal>

    <!-- Assign Issues Modal -->
    <Modal :show="showAssignModal" title="Assign Issues to Sprint" @close="showAssignModal = false">
      <form @submit.prevent="submitAssign">
        <div class="space-y-4">
          <p class="text-sm text-gray-500">Select issues from the backlog to include in <strong>{{ selectedSprint?.name }}</strong>.</p>
          <div class="max-h-64 overflow-y-auto border border-gray-200 rounded-lg divide-y divide-gray-100">
            <label v-for="issue in unassignedIssues" :key="issue.id"
                   class="flex items-center gap-3 px-4 py-2.5 hover:bg-gray-50 cursor-pointer">
              <input type="checkbox" :value="issue.id" v-model="selectedIssueIds" class="rounded text-indigo-600">
              <span class="text-xs font-mono text-gray-500">{{ issue.key }}</span>
              <span class="text-sm text-gray-800 truncate flex-1">{{ issue.title }}</span>
            </label>
            <div v-if="unassignedIssues.length === 0" class="px-4 py-6 text-center text-sm text-gray-500">
              No unassigned issues in the backlog.
            </div>
          </div>
          <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition">
            Assign Selected ({{ selectedIssueIds.length }})
          </button>
        </div>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute } from 'vue-router';
import { useProjectStore } from '../../stores/project';
import axios from 'axios';
import Icon from '../../shared/components/Icon.vue';
import Modal from '../../shared/components/Modal.vue';
import Spinner from '../../shared/components/Spinner.vue';
import Badge from '../../shared/components/Badge.vue';

const route = useRoute();
const projectStore = useProjectStore();

const sprints = ref([]);
const loading = ref(true);
const backlogIssues = ref([]);

const showCreateModal = ref(false);
const form = reactive({ name: '', goal: '', starts_at: '', ends_at: '' });

const showAssignModal = ref(false);
const selectedSprint = ref(null);
const selectedIssueIds = ref([]);

const project = computed(() => projectStore.currentProject);

const unassignedIssues = computed(() =>
  backlogIssues.value.filter((i) => !sprints.value.some((s) => s.issues?.some((si) => si.id === i.id)))
);

async function load() {
  loading.value = true;
  try {
    if (!projectStore.currentProject) {
      await projectStore.fetchProject(route.params.id);
    }
    const { data } = await axios.get(`/projects/${route.params.id}/sprints`);
    sprints.value = data.sprints;

    const res = await axios.get(`/projects/${route.params.id}/issues`, { params: { per_page: 100 } });
    backlogIssues.value = res.data.issues.data;
  } finally {
    loading.value = false;
  }
}

function openCreateSprint() {
  Object.assign(form, { name: '', goal: '', starts_at: '', ends_at: '' });
  showCreateModal.value = true;
}

async function createSprint() {
  await axios.post(`/projects/${route.params.id}/sprints`, form);
  showCreateModal.value = false;
  await load();
}

async function startSprint(sprint) {
  await axios.post(`/sprints/${sprint.id}/start`);
  await load();
}

async function completeSprint(sprint) {
  await axios.post(`/sprints/${sprint.id}/complete`);
  await load();
}

async function deleteSprint(sprint) {
  if (confirm(`Delete sprint "${sprint.name}"?`)) {
    await axios.delete(`/sprints/${sprint.id}`);
    await load();
  }
}

function assignIssues(sprint) {
  selectedSprint.value = sprint;
  selectedIssueIds.value = sprint.issues?.map((i) => i.id) || [];
  showAssignModal.value = true;
}

async function submitAssign() {
  await axios.post(`/sprints/${selectedSprint.value.id}/issues`, { issue_ids: selectedIssueIds.value });
  showAssignModal.value = false;
  await load();
}

function statusColor(status) {
  return {
    todo: '#6b7280',
    'in_progress': '#3b82f6',
    done: '#10b981',
  }[status] || '#64748b';
}

onMounted(load);
</script>