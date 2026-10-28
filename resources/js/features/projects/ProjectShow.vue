<template>
  <div class="p-4 lg:p-8">
    <div v-if="projectStore.loading"><Spinner /></div>
    <template v-else-if="project">
      <!-- Header -->
      <div class="mb-8">
        <div class="flex items-center gap-4">
          <span class="w-11 h-11 rounded-xl flex items-center justify-center text-white text-lg font-bold shrink-0"
                :style="{ backgroundColor: project.color }">{{ project.name.charAt(0) }}</span>
          <div class="flex-1 min-w-0">
            <div class="flex items-center gap-2">
              <h1 class="text-2xl font-bold text-gray-900 truncate">{{ project.name }}</h1>
              <span class="text-xs font-mono bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ project.key }}</span>
            </div>
            <p class="text-sm text-gray-500 mt-0.5 truncate">{{ project.description || 'No description' }}</p>
          </div>
        </div>

        <!-- Navigation tabs -->
        <div class="mt-6 flex gap-1 border-b border-gray-200 overflow-x-auto">
          <button v-for="tab in tabs" :key="tab.name" @click="router.push(tab.path)"
                  class="px-4 py-2.5 text-sm font-medium whitespace-nowrap transition border-b-2"
                  :class="isActive(tab.path) ? 'text-indigo-600 border-indigo-600' : 'text-gray-500 hover:text-gray-700 border-transparent'">
            {{ tab.label }}
          </button>
        </div>
      </div>

      <!-- Stats -->
      <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-8">
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500">Total Issues</p>
          <p class="text-xl font-bold text-gray-900 mt-1">{{ stats.total_issues ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500">Open</p>
          <p class="text-xl font-bold text-gray-900 mt-1">{{ stats.open_issues ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500">Completed</p>
          <p class="text-xl font-bold text-gray-900 mt-1">{{ stats.completed_issues ?? 0 }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-4">
          <p class="text-xs text-gray-500">Members</p>
          <p class="text-xl font-bold text-gray-900 mt-1">{{ project.members?.length || 0 }}</p>
        </div>
      </div>

      <!-- Issue distribution -->
      <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h3 class="font-semibold text-gray-900 mb-4">Issue Distribution</h3>
          <div class="space-y-3">
            <div v-for="(count, key) in stats.by_type" :key="key">
              <div class="flex justify-between text-sm mb-1">
                <span class="text-gray-600 capitalize">{{ key }}</span>
                <span class="font-medium text-gray-800">{{ count }}</span>
              </div>
              <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full bg-indigo-500 rounded-full" :style="{ width: percentage(count) }"></div>
              </div>
            </div>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h3 class="font-semibold text-gray-900 mb-4">By Priority</h3>
          <div class="space-y-3">
            <div v-for="(count, key) in stats.by_priority" :key="key">
              <div class="flex justify-between text-sm mb-1">
                <span class="capitalize" :class="priorityColor(key)">{{ key }}</span>
                <span class="font-medium text-gray-800">{{ count }}</span>
              </div>
              <div class="h-2 bg-gray-100 rounded-full overflow-hidden">
                <div class="h-full rounded-full" :style="{ width: percentage(count), backgroundColor: priorityHex(key) }"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Recent issues -->
      <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mt-6">
        <div class="px-5 py-4 border-b border-gray-200">
          <h3 class="font-semibold text-gray-900">Recent Issues</h3>
        </div>
        <div v-if="stats.recent_issues?.length === 0" class="py-8 text-center text-gray-500 text-sm">
          No issues yet.
        </div>
        <div v-else class="divide-y divide-gray-100">
          <router-link v-for="issue in stats.recent_issues" :key="issue.id" :to="`/issues/${issue.id}`"
               class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition">
            <span class="text-xs font-mono text-gray-500 w-20">{{ issue.key }}</span>
            <span class="text-sm text-gray-800 truncate flex-1">{{ issue.title }}</span>
            <UserAvatar v-if="issue.assignee" :user="issue.assignee" size="xs" />
          </router-link>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useProjectStore } from '../../stores/project';
import axios from 'axios';
import Icon from '../../shared/components/Icon.vue';
import Spinner from '../../shared/components/Spinner.vue';
import UserAvatar from '../../shared/components/UserAvatar.vue';

const route = useRoute();
const router = useRouter();
const projectStore = useProjectStore();

const stats = ref({});
const project = computed(() => projectStore.currentProject);

const tabs = [
  { label: 'Issues', name: 'issues', path: `/projects/${route.params.id}/issues` },
  { label: 'Board', name: 'board', path: `/projects/${route.params.id}/board` },
  { label: 'Sprints', name: 'sprints', path: `/projects/${route.params.id}/sprints` },
  { label: 'Settings', name: 'settings', path: `/projects/${route.params.id}/settings` },
];

function isActive(path) {
  return path === route.path;
}

function percentage(count) {
  const total = Object.values(stats.value.by_type || {}).reduce((a, b) => a + b, 0)
               || Object.values(stats.value.by_priority || {}).reduce((a, b) => a + b, 0)
               || 1;
  return `${Math.round((count / total) * 100)}%`;
}

function priorityColor(key) {
  return {
    urgent: 'text-red-600 font-medium',
    high: 'text-orange-600',
    medium: 'text-yellow-600',
    low: 'text-gray-500',
  }[key] || '';
}

function priorityHex(key) {
  return { urgent: '#ef4444', high: '#f97316', medium: '#eab308', low: '#6b7280' }[key] || '#6b7280';
}

async function load() {
  projectStore.fetchProject(route.params.id);
  const { data } = await axios.get(`/projects/${route.params.id}/stats`);
  stats.value = data;
}

onMounted(load);
</script>