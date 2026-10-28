<template>
  <div class="p-4 lg:p-8">
    <div v-if="orgStore.loading"><Spinner /></div>
    <template v-else-if="org">
      <div class="mb-8">
        <div class="flex items-center gap-3 mb-4">
          <button @click="router.push('/organizations')" class="text-gray-400 hover:text-gray-600"><Icon name="chevron-left" /></button>
          <div class="w-10 h-10 rounded-lg bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold">
            {{ org.name.charAt(0).toUpperCase() }}
          </div>
          <div>
            <h1 class="text-2xl font-bold text-gray-900">{{ org.name }}</h1>
            <p class="text-sm text-gray-500">{{ slug }}</p>
          </div>
        </div>

        <div class="flex gap-2">
          <button @click="showMembers = false"
                  class="px-4 py-2 rounded-lg text-sm font-medium transition"
                  :class="!showMembers ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-200'">
            Projects
          </button>
          <button @click="showMembers = true"
                  class="px-4 py-2 rounded-lg text-sm font-medium transition"
                  :class="showMembers ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 border border-gray-200'">
            Members
          </button>
        </div>
      </div>

      <!-- Projects tab -->
      <div v-if="!showMembers">
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-gray-900">Projects</h2>
          <button @click="showCreateModal = true" class="inline-flex items-center gap-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition">
            <Icon name="plus" size="sm" /> New Project
          </button>
        </div>

        <div v-if="org.projects.length === 0" class="bg-white border border-gray-200 rounded-xl p-10 text-center text-gray-500 text-sm">
          No projects in this organization yet. Create one to get started.
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          <router-link v-for="project in org.projects" :key="project.id" :to="`/projects/${project.id}`"
               class="bg-white rounded-xl border border-gray-200 p-5 hover:shadow-md transition group">
            <div class="flex items-start justify-between mb-3">
              <span class="w-3 h-3 rounded-full mt-0.5" :style="{ backgroundColor: project.color }"></span>
              <span class="text-xs font-mono bg-gray-100 text-gray-600 px-2 py-0.5 rounded">{{ project.key }}</span>
            </div>
            <h3 class="font-semibold text-gray-900 group-hover:text-indigo-600 mb-1">{{ project.name }}</h3>
            <p class="text-sm text-gray-500 line-clamp-2">{{ project.description || 'No description' }}</p>
          </router-link>
        </div>
      </div>

      <!-- Members tab -->
      <div v-else>
        <div class="flex items-center justify-between mb-4">
          <h2 class="font-semibold text-gray-900">Members ({{ org.members?.length || 0 }})</h2>
          <button @click="showInviteModal = true" class="inline-flex items-center gap-1.5 text-sm bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg transition">
            <Icon name="user" size="sm" /> Invite Member
          </button>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 divide-y divide-gray-100">
          <div v-for="member in org.members" :key="member.id" class="flex items-center gap-3 px-5 py-3">
            <UserAvatar :user="member" size="sm" />
            <div class="flex-1 min-w-0">
              <div class="text-sm font-medium text-gray-800">{{ member.name }}</div>
              <div class="text-xs text-gray-500 truncate">{{ member.email }}</div>
            </div>
            <span class="text-xs px-2 py-0.5 rounded-full capitalize"
                  :class="{
                    'bg-indigo-50 text-indigo-600': member.pivot.role === 'owner',
                    'bg-gray-100 text-gray-600': member.pivot.role === 'member',
                  }">{{ member.pivot.role }}</span>
            <button v-if="isOwner && member.id !== auth.user.id" @click="removeMember(member)"
                    class="text-gray-400 hover:text-red-500"><Icon name="trash" size="sm" /></button>
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
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Project Key</label>
              <input v-model="projectForm.key" type="text" required maxlength="10"
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 uppercase"
                     placeholder="ABC">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Description</label>
              <textarea v-model="projectForm.description" rows="3"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition">
              Create Project
            </button>
          </div>
        </form>
      </Modal>

      <!-- Invite Member Modal -->
      <Modal :show="showInviteModal" title="Invite Member" @close="showInviteModal = false">
        <form @submit.prevent="inviteMember">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
              <input v-model="inviteForm.email" type="email" required
                     class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                     placeholder="teammate@example.com">
            </div>
            <div>
              <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
              <select v-model="inviteForm.role" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500">
                <option value="member">Member</option>
                <option value="admin">Admin</option>
              </select>
            </div>
            <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition">
              Send Invite
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
import { useOrganizationStore } from '../../stores/organization';
import { useAuthStore } from '../../stores/auth';
import axios from 'axios';
import Icon from '../../shared/components/Icon.vue';
import Modal from '../../shared/components/Modal.vue';
import Spinner from '../../shared/components/Spinner.vue';
import UserAvatar from '../../shared/components/UserAvatar.vue';

const route = useRoute();
const router = useRouter();
const orgStore = useOrganizationStore();
const auth = useAuthStore();

const showMembers = ref(false);
const showCreateModal = ref(false);
const showInviteModal = ref(false);

const projectForm = reactive({ name: '', key: '', description: '' });
const inviteForm = reactive({ email: '', role: 'member' });

const org = computed(() => orgStore.currentOrganization);
const slug = computed(() => org.value?.slug || '');
const isOwner = computed(() => org.value?.owner_id === auth.user?.id);

async function createProject() {
  await axios.post(`/organizations/${route.params.id}/projects`, projectForm);
  showCreateModal.value = false;
  Object.assign(projectForm, { name: '', key: '', description: '' });
  await orgStore.fetchOrganization(route.params.id);
}

async function inviteMember() {
  await orgStore.addMember(route.params.id, inviteForm);
  showInviteModal.value = false;
  inviteForm.email = '';
  await orgStore.fetchOrganization(route.params.id);
}

async function removeMember(member) {
  if (confirm(`Remove ${member.name} from this organization?`)) {
    await orgStore.removeMember(route.params.id, member.id);
    await orgStore.fetchOrganization(route.params.id);
  }
}

onMounted(() => orgStore.fetchOrganization(route.params.id));
</script>