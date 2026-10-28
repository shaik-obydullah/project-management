<template>
  <div class="p-4 lg:p-8 max-w-4xl mx-auto">
    <h1 class="text-2xl font-bold text-gray-900 mb-6">Search</h1>

    <div class="relative mb-8">
      <Icon name="search" size="md" class="absolute left-4 top-1/2 -translate-y-1/2 text-gray-400" />
      <input v-model="q" @input="debouncedSearch" type="text" autofocus placeholder="Search issues, projects..."
             class="w-full pl-12 pr-4 py-3.5 border border-gray-200 rounded-xl focus:outline-none focus:ring-2 focus:ring-indigo-500 shadow-sm text-base">
    </div>

    <div v-if="loading" class="py-10"><Spinner /></div>

    <template v-else-if="q">
      <!-- Issues -->
      <section v-if="issues.length" class="mb-8">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Issues ({{ issues.length }})</h2>
        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100 overflow-hidden">
          <router-link v-for="issue in issues" :key="issue.id" :to="`/issues/${issue.id}`"
               class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition">
            <span class="text-xs font-mono text-gray-500 w-20">{{ issue.key }}</span>
            <span class="text-sm text-gray-800 truncate flex-1">{{ issue.title }}</span>
            <span class="text-xs font-mono px-2 py-0.5 rounded" :style="{ color: issue.project?.color, backgroundColor: `${issue.project?.color}1a` }">{{ issue.project?.key }}</span>
            <UserAvatar v-if="issue.assignee" :user="issue.assignee" size="xs" />
          </router-link>
        </div>
      </section>

      <!-- Projects -->
      <section v-if="projects.length" class="mb-8">
        <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wide mb-3">Projects ({{ projects.length }})</h2>
        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100 overflow-hidden">
          <router-link v-for="project in projects" :key="project.id" :to="`/projects/${project.id}`"
               class="flex items-center gap-3 px-5 py-3 hover:bg-gray-50 transition">
            <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: project.color }"></span>
            <span class="text-sm font-medium text-gray-800 flex-1">{{ project.name }}</span>
            <span class="text-xs font-mono text-gray-500">{{ project.key }}</span>
          </router-link>
        </div>
      </section>

      <div v-if="!loading && issues.length === 0 && projects.length === 0" class="bg-white border border-gray-200 rounded-xl p-10 text-center">
        <div class="text-gray-300 mb-2"><Icon name="search" size="xl" /></div>
        <p class="text-gray-500">No results for "{{ q }}"</p>
      </div>
    </template>

    <div v-else class="bg-white border border-gray-200 rounded-xl p-10 text-center text-gray-500">
      Type to search across your projects and issues.
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue';
import axios from 'axios';
import Icon from '../../shared/components/Icon.vue';
import Spinner from '../../shared/components/Spinner.vue';
import UserAvatar from '../../shared/components/UserAvatar.vue';

const q = ref('');
const issues = ref([]);
const projects = ref([]);
const loading = ref(false);

let timer = null;

function debouncedSearch() {
  clearTimeout(timer);
  if (!q.value.trim()) {
    issues.value = [];
    projects.value = [];
    return;
  }
  timer = setTimeout(search, 300);
}

async function search() {
  loading.value = true;
  try {
    const { data } = await axios.get('/search', { params: { q: q.value.trim() } });
    issues.value = data.issues;
    projects.value = data.projects;
  } finally {
    loading.value = false;
  }
}
</script>