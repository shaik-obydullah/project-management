<template>
  <div class="p-4 lg:p-6 h-full flex flex-col">
    <div v-if="projectStore.loading && !loaded"><Spinner /></div>
    <template v-else>
      <!-- Header -->
      <div class="flex items-center justify-between mb-5">
        <div>
          <h1 class="text-xl font-bold text-gray-900">Board</h1>
          <p class="text-sm text-gray-500">{{ project?.name }}</p>
        </div>
        <div class="flex items-center gap-2">
          <button @click="showAddColumn = true"
                  class="inline-flex items-center gap-1.5 text-sm bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-3 py-1.5 rounded-lg transition">
            <Icon name="plus" size="sm" /> Add Column
          </button>
          <button @click="openCreateIssue(null)"
                  class="inline-flex items-center gap-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition">
            <Icon name="plus" size="sm" /> New Issue
          </button>
        </div>
      </div>

      <!-- Board -->
      <div class="flex gap-4 overflow-x-auto pb-4 flex-1 items-start">
        <!-- Backlog column -->
        <div class="w-80 shrink-0 bg-gray-100/60 rounded-xl border border-gray-200 flex flex-col">
          <div class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full bg-gray-400"></span>
              <span class="text-sm font-semibold text-gray-700">Backlog</span>
              <span class="text-xs bg-white border border-gray-200 rounded-full px-1.5 text-gray-500">{{ backlog.length }}</span>
            </div>
            <button @click="openCreateIssue(null)" class="text-gray-400 hover:text-gray-700"><Icon name="plus" size="sm" /></button>
          </div>
          <VueDraggable v-model="backlog" group="issues" item-key="id" :animation="150" class="flex-1 px-2 pb-2 space-y-2 min-h-[60px]" @end="onDragEnd('backlog')">
            <template #item="{ element }">
              <IssueCard :issue="element" />
            </template>
          </VueDraggable>
        </div>

        <!-- Board columns -->
        <div v-for="column in columns" :key="column.id" class="w-80 shrink-0 bg-gray-100/60 rounded-xl border border-gray-200 flex flex-col">
          <div class="flex items-center justify-between px-4 py-3">
            <div class="flex items-center gap-2">
              <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: column.color || '#9ca3af' }"></span>
              <span class="text-sm font-semibold text-gray-700">{{ column.name }}</span>
              <span class="text-xs bg-white border border-gray-200 rounded-full px-1.5 text-gray-500">{{ column.issues.length }}{{ column.wip_limit ? ` / ${column.wip_limit}` : '' }}</span>
            </div>
            <div class="flex items-center gap-1">
              <button @click="openCreateIssue(column.id)" class="text-gray-400 hover:text-gray-700"><Icon name="plus" size="sm" /></button>
              <button @click="renameColumn(column)" class="text-gray-400 hover:text-gray-700"><Icon name="edit" size="sm" /></button>
              <button @click="deleteColumn(column)" class="text-gray-400 hover:text-red-500"><Icon name="trash" size="sm" /></button>
            </div>
          </div>
          <VueDraggable v-model="column.issues" group="issues" item-key="id" :animation="150" class="flex-1 px-2 pb-2 space-y-2 min-h-[60px]" @end="onDragEnd(column.id)">
            <template #item="{ element }">
              <IssueCard :issue="element" />
            </template>
          </VueDraggable>
        </div>
      </div>

      <!-- Create Issue Modal -->
      <Modal :show="showCreateModal" title="Create Issue" @close="showCreateModal = false" v-if="project">
        <form @submit.prevent="createIssue">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Title</label>
              <input v-model="issueForm.title" type="text" required autofocus
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div class="grid grid-cols-2 gap-3">
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Type</label>
                <select v-model="issueForm.type" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                  <option value="task">Task</option>
                  <option value="story">Story</option>
                  <option value="bug">Bug</option>
                  <option value="epic">Epic</option>
                  <option value="subtask">Subtask</option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Priority</label>
                <select v-model="issueForm.priority" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                  <option value="medium">Medium</option>
                  <option value="low">Low</option>
                  <option value="high">High</option>
                  <option value="urgent">Urgent</option>
                </select>
              </div>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Assignee</label>
              <select v-model="issueForm.assigned_to" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option :value="null">Unassigned</option>
                <option v-for="member in project.members" :key="member.id" :value="member.id">{{ member.name }}</option>
              </select>
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <textarea v-model="issueForm.description" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>
            <button type="submit" :disabled="creating"
                    class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition disabled:opacity-50">
              {{ creating ? 'Creating...' : 'Create Issue' }}
            </button>
          </div>
        </form>
      </Modal>

      <!-- Add Column Modal -->
      <Modal :show="showAddColumn" title="Add Column" @close="showAddColumn = false">
        <form @submit.prevent="addColumn">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Column Name</label>
              <input v-model="columnForm.name" type="text" required autofocus
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">WIP Limit (optional)</label>
              <input v-model.number="columnForm.wip_limit" type="number" min="1"
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition">
              Add Column
            </button>
          </div>
        </form>
      </Modal>
    </template>
  </div>
</template>

<script setup>
import { computed, onMounted, reactive, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { VueDraggable } from 'vue-draggable-plus';
import { useProjectStore } from '../../stores/project';
import axios from 'axios';
import Icon from '../../shared/components/Icon.vue';
import Modal from '../../shared/components/Modal.vue';
import Spinner from '../../shared/components/Spinner.vue';
import IssueCard from './IssueCard.vue';

const route = useRoute();
const router = useRouter();
const projectStore = useProjectStore();

const columns = ref([]);
const backlog = ref([]);
const loaded = ref(false);
const snapshot = ref({});

const showCreateModal = ref(false);
const creating = ref(false);
const issueForm = reactive({ title: '', type: 'task', priority: 'medium', assigned_to: null, description: '', column_id: null });

const showAddColumn = ref(false);
const columnForm = reactive({ name: '', wip_limit: null });

const project = computed(() => projectStore.currentProject);

async function loadBoard() {
  if (!projectStore.currentProject) {
    await projectStore.fetchProject(route.params.id);
  }
  const data = await projectStore.fetchBoard(route.params.id);
  columns.value = data.columns;
  backlog.value = data.backlog;
  snapshot.value = {
    backlog: data.backlog.map((i) => i.id),
    columns: data.columns.map((c) => ({ id: c.id, ids: c.issues.map((i) => i.id) })),
  };
  loaded.value = true;
}

function arraysEqual(a, b) {
  return a.length === b.length && a.every((v, i) => v === b[i]);
}

async function onDragEnd() {
  // Persist any changed column / backlog
  for (const col of columns.value) {
    const orig = snapshot.value.columns.find((c) => c.id === col.id);
    const now = col.issues.map((i) => i.id);
    if (orig && !arraysEqual(orig.ids, now)) {
      await persistColumn(col, now);
    }
  }
  const backOrig = snapshot.value.backlog;
  const backNow = backlog.value.map((i) => i.id);
  if (!arraysEqual(backOrig, backNow)) {
    await persistBacklog(backNow);
  }
  await loadBoard();
}

async function persistColumn(col, ids) {
  for (let i = 0; i < ids.length; i++) {
    await projectStore.moveIssue(ids[i], col.id, i);
  }
}

async function persistBacklog(ids) {
  const backLogCol = columns.value[0];
  if (backLogCol) {
    for (let i = 0; i < ids.length; i++) {
      await projectStore.moveIssue(ids[i], backLogCol.id, i);
    }
  }
}

function openCreateIssue(columnId) {
  issueForm.column_id = columnId;
  Object.assign(issueForm, { title: '', type: 'task', priority: 'medium', assigned_to: null, description: '' });
  showCreateModal.value = true;
}

async function createIssue() {
  creating.value = true;
  try {
    await axios.post(`/projects/${route.params.id}/issues`, issueForm);
    showCreateModal.value = false;
    await loadBoard();
  } finally {
    creating.value = false;
  }
}

async function addColumn() {
  await projectStore.addColumn(route.params.id, columnForm);
  showAddColumn.value = false;
  columnForm.name = '';
  columnForm.wip_limit = null;
  await loadBoard();
}

function renameColumn(column) {
  const name = prompt('Column name:', column.name);
  if (name && name !== column.name) {
    axios.put(`/columns/${column.id}`, { name }).then(loadBoard);
  }
}

function deleteColumn(column) {
  if (confirm(`Delete column "${column.name}"? Its issues will move to backlog.`)) {
    axios.delete(`/columns/${column.id}`).then(loadBoard);
  }
}

onMounted(loadBoard);
</script>