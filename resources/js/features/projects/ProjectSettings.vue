<template>
  <div class="p-4 lg:p-8">
    <div v-if="projectStore.loading"><Spinner /></div>
    <template v-else-if="project">
      <div class="max-w-3xl">
        <div class="flex items-center gap-4 mb-8">
          <span class="w-11 h-11 rounded-xl flex items-center justify-center text-white text-lg font-bold"
                :style="{ backgroundColor: project.color }">{{ project.name.charAt(0) }}</span>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">Project Settings</h1>
            <p class="text-sm text-gray-500">{{ project.key }}</p>
          </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-6 space-y-6">
          <h2 class="font-semibold text-gray-900 text-lg">General</h2>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Project Name</label>
            <input v-model="form.name" type="text"
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
            <textarea v-model="form.description" rows="4"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
          </div>

          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Accent Color</label>
            <div class="flex items-center gap-3">
              <input v-model="form.color" type="color" class="w-12 h-10 rounded border border-gray-300 cursor-pointer">
              <span class="text-sm text-gray-500">{{ form.color }}</span>
            </div>
          </div>

          <div class="pt-2">
            <button @click="saveSettings" :disabled="saving"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white font-medium px-5 py-2 rounded-lg transition disabled:opacity-50">
              {{ saving ? 'Saving...' : 'Save Changes' }}
            </button>
          </div>
        </div>

        <!-- Members -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mt-6">
          <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Members ({{ project.members?.length || 0 }})</h2>
            <button @click="showInviteModal = true" class="inline-flex items-center gap-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition">
              <Icon name="user" size="sm" /> Add Member
            </button>
          </div>
          <div class="divide-y divide-gray-100">
            <div v-for="member in project.members" :key="member.id" class="flex items-center gap-3 px-6 py-3">
              <UserAvatar :user="member" size="sm" />
              <div class="flex-1 min-w-0">
                <div class="text-sm font-medium text-gray-800">{{ member.name }}</div>
                <div class="text-xs text-gray-500 truncate">{{ member.email }}</div>
              </div>
              <span class="text-xs px-2 py-0.5 rounded-full bg-gray-100 text-gray-600 capitalize">{{ member.pivot.role }}</span>
              <button @click="removeMember(member)" class="text-gray-400 hover:text-red-500"><Icon name="trash" size="sm" /></button>
            </div>
          </div>
        </div>

        <!-- Labels -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden mt-6">
          <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
            <h2 class="font-semibold text-gray-900">Labels</h2>
            <button @click="showLabelModal = true" class="inline-flex items-center gap-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition">
              <Icon name="tag" size="sm" /> New Label
            </button>
          </div>
          <div class="px-6 py-4 flex flex-wrap gap-2">
            <span v-for="label in project.labels" :key="label.id"
                  class="inline-flex items-center gap-2 px-3 py-1 rounded-lg text-sm"
                  :style="{ backgroundColor: `${label.color}1a`, color: label.color }">
              {{ label.name }}
              <button @click="deleteLabel(label)" class="hover:text-red-600"><Icon name="x" size="xs" /></button>
            </span>
            <span v-if="!project.labels?.length" class="text-sm text-gray-500">No labels yet</span>
          </div>
        </div>

        <!-- Danger zone -->
        <div class="bg-red-50 rounded-xl border border-red-200 p-6 mt-6">
          <h2 class="font-semibold text-red-800 mb-2">Danger Zone</h2>
          <p class="text-sm text-red-600 mb-4">Deleting this project will remove all associated issues, sprints, and comments. This action cannot be undone.</p>
          <button @click="archiveProject" class="bg-red-600 hover:bg-red-700 text-white font-medium px-5 py-2 rounded-lg transition mr-2">
            {{ project.is_archived ? 'Unarchive' : 'Archive' }}
          </button>
          <button @click="deleteProject" class="border border-red-300 text-red-600 hover:bg-red-100 font-medium px-5 py-2 rounded-lg transition">
            Delete Project
          </button>
        </div>
      </div>

      <!-- Invite Modal -->
      <Modal :show="showInviteModal" title="Add Member" @close="showInviteModal = false">
        <form @submit.prevent="inviteMember">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
              <input v-model="inviteForm.email" type="email" required
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
              <select v-model="inviteForm.role" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                <option value="member">Member</option>
                <option value="admin">Admin</option>
                <option value="viewer">Viewer</option>
              </select>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition">
              Add Member
            </button>
          </div>
        </form>
      </Modal>

      <!-- Label Modal -->
      <Modal :show="showLabelModal" title="Create Label" @close="showLabelModal = false">
        <form @submit.prevent="createLabel">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Label Name</label>
              <input v-model="labelForm.name" type="text" required
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Color</label>
              <div class="flex items-center gap-3">
                <input v-model="labelForm.color" type="color" class="w-12 h-10 rounded border border-gray-300 cursor-pointer">
                <span class="text-sm text-gray-500">{{ labelForm.color }}</span>
              </div>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition">
              Create Label
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
import { useProjectStore } from '../../stores/project';
import axios from 'axios';
import Icon from '../../shared/components/Icon.vue';
import Modal from '../../shared/components/Modal.vue';
import Spinner from '../../shared/components/Spinner.vue';
import UserAvatar from '../../shared/components/UserAvatar.vue';

const route = useRoute();
const router = useRouter();
const projectStore = useProjectStore();
const project = computed(() => projectStore.currentProject);

const saving = ref(false);
const form = reactive({ name: '', description: '', color: '#6366f1' });

const showInviteModal = ref(false);
const inviteForm = reactive({ email: '', role: 'member' });

const showLabelModal = ref(false);
const labelForm = reactive({ name: '', color: '#6366f1' });

function syncForm() {
  if (project.value) {
    form.name = project.value.name;
    form.description = project.value.description || '';
    form.color = project.value.color || '#6366f1';
  }
}

async function saveSettings() {
  saving.value = true;
  try {
    await projectStore.updateProject(route.params.id, {
      name: form.name,
      description: form.description,
      color: form.color,
    });
  } finally {
    saving.value = false;
  }
}

async function inviteMember() {
  await axios.post(`/projects/${route.params.id}/members`, inviteForm);
  showInviteModal.value = false;
  inviteForm.email = '';
  await projectStore.fetchProject(route.params.id);
}

async function removeMember(member) {
  if (confirm(`Remove ${member.name} from this project?`)) {
    await axios.delete(`/projects/${route.params.id}/members/${member.id}`);
    await projectStore.fetchProject(route.params.id);
  }
}

async function createLabel() {
  await axios.post(`/projects/${route.params.id}/labels`, labelForm);
  showLabelModal.value = false;
  Object.assign(labelForm, { name: '', color: '#6366f1' });
  await projectStore.fetchProject(route.params.id);
}

async function deleteLabel(label) {
  if (confirm(`Delete label "${label.name}"?`)) {
    await axios.delete(`/labels/${label.id}`);
    await projectStore.fetchProject(route.params.id);
  }
}

async function archiveProject() {
  await projectStore.updateProject(route.params.id, { is_archived: !project.value.is_archived });
}

async function deleteProject() {
  if (confirm(`Are you sure you want to delete "${project.value.name}"? This cannot be undone.`)) {
    await projectStore.deleteProject(route.params.id);
    router.push('/');
  }
}

onMounted(async () => {
  await projectStore.fetchProject(route.params.id);
  syncForm();
});
</script>