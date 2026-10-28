<template>
  <div class="p-4 lg:p-8">
    <div class="flex items-center justify-between mb-8">
      <div>
        <h1 class="text-2xl font-bold text-gray-900">Organizations</h1>
        <p class="text-gray-500 mt-1">Teams you belong to</p>
      </div>
      <button @click="showCreateModal = true"
              class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium transition">
        <Icon name="plus" size="sm" /> New Organization
      </button>
    </div>

    <div v-if="orgStore.loading" class="py-10"><Spinner /></div>
    <div v-else-if="orgStore.organizations.length === 0" class="bg-white rounded-xl border border-gray-200 p-10 text-center">
      <div class="text-4xl mb-3">🏢</div>
      <h3 class="text-lg font-semibold text-gray-900 mb-1">No organizations yet</h3>
      <p class="text-sm text-gray-500 mb-4">Create your first organization to start managing projects.</p>
      <button @click="showCreateModal = true" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-lg text-sm font-medium inline-flex items-center gap-2">
        <Icon name="plus" size="sm" /> Create Organization
      </button>
    </div>
    <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <router-link v-for="org in orgStore.organizations" :key="org.id" :to="`/organizations/${org.id}`"
           class="bg-white rounded-xl border border-gray-200 p-6 hover:shadow-md transition group">
        <div class="flex items-center gap-4 mb-4">
          <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-500 to-purple-600 flex items-center justify-center text-white font-bold text-lg">
            {{ org.name.charAt(0).toUpperCase() }}
          </div>
          <div class="min-w-0">
            <h2 class="font-semibold text-gray-900 truncate group-hover:text-indigo-600">{{ org.name }}</h2>
            <p class="text-xs text-gray-500">{{ org.projects?.length || 0 }} projects</p>
          </div>
        </div>
      </router-link>
    </div>

    <!-- Create Modal -->
    <Modal :show="showCreateModal" title="Create Organization" @close="showCreateModal = false">
      <form @submit.prevent="createOrg">
        <div class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Organization Name</label>
            <input v-model="form.name" type="text" required autofocus
                   class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"
                   placeholder="e.g. Acme Corporation">
          </div>
          <button type="submit" class="w-full bg-indigo-600 hover:bg-indigo-700 text-white font-semibold py-2 px-4 rounded-lg transition">
            Create Organization
          </button>
        </div>
      </form>
    </Modal>
  </div>
</template>

<script setup>
import { onMounted, reactive, ref } from 'vue';
import { useOrganizationStore } from '../../stores/organization';
import Icon from '../../shared/components/Icon.vue';
import Modal from '../../shared/components/Modal.vue';
import Spinner from '../../shared/components/Spinner.vue';

const orgStore = useOrganizationStore();
const showCreateModal = ref(false);
const form = reactive({ name: '' });

async function createOrg() {
    await orgStore.createOrganization(form);
    showCreateModal.value = false;
    form.name = '';
    await orgStore.fetchOrganizations();
}

onMounted(() => orgStore.fetchOrganizations());
</script>