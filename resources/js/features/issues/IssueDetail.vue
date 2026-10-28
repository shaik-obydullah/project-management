<template>
  <div v-if="loading || !issue" class="p-4 lg:p-8"><Spinner /></div>
  <div v-else class="p-4 lg:p-8 max-w-5xl mx-auto">
    <!-- Header -->
    <div class="flex items-center gap-3 mb-6">
      <button @click="router.back()" class="text-gray-400 hover:text-gray-600"><Icon name="chevron-left" /></button>
      <span class="text-sm font-mono text-gray-500">{{ issue.key }}</span>
      <Badge :label="issue.type" :color="typeColor(issue.type)" />
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
      <!-- Main column -->
      <div class="lg:col-span-2 space-y-6">
        <!-- Title & description -->
        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <div v-if="editingTitle">
            <input v-model="draft.title" type="text" @keyup.enter="saveTitle" @keyup.esc="editingTitle = false"
                   class="w-full text-xl font-bold text-gray-900 border-b-2 border-indigo-500 focus:outline-none pb-1">
          </div>
          <h1 v-else class="text-xl font-bold text-gray-900 mb-4 cursor-pointer hover:text-indigo-600" @click="startEditTitle">
            {{ issue.title }}
          </h1>

          <div v-if="editingDescription" class="space-y-3">
            <textarea v-model="draft.description" rows="6"
                      class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
            <div class="flex gap-2">
              <button @click="saveDescription" class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm px-4 py-1.5 rounded-lg">Save</button>
              <button @click="editingDescription = false" class="text-sm px-4 py-1.5 rounded-lg border border-gray-200 text-gray-600">Cancel</button>
            </div>
          </div>
          <div v-else-if="issue.description" @click="editingDescription = true"
               class="prose prose-sm max-w-none text-gray-700 cursor-text hover:bg-gray-50 rounded-lg -m-2 p-2">
            <pre class="whitespace-pre-wrap font-sans text-sm">{{ issue.description }}</pre>
          </div>
          <div v-else @click="editingDescription = true" class="text-gray-400 text-sm hover:text-gray-600 cursor-text border border-dashed border-gray-200 rounded-lg p-3 text-center">
            Add a description...
          </div>
        </div>

        <!-- Subtasks -->
        <div v-if="issue.subtasks?.length" class="bg-white rounded-xl border border-gray-200 p-5">
          <h3 class="font-semibold text-gray-900 mb-3">Sub-tasks ({{ issue.subtasks.length }})</h3>
          <div class="divide-y divide-gray-100">
            <router-link v-for="st in issue.subtasks" :key="st.id" :to="`/issues/${st.id}`"
                 class="flex items-center gap-3 py-2.5 hover:bg-gray-50 px-2 -mx-2 rounded transition">
              <span class="text-xs font-mono text-gray-500 w-20">{{ st.key }}</span>
              <span class="text-sm text-gray-800 truncate flex-1">{{ st.title }}</span>
              <Badge :label="st.status" :color="statusColor(st.status)" />
            </router-link>
          </div>
        </div>

        <!-- Comments -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Comments ({{ comments.length }})</h3>
          </div>

          <!-- New comment box -->
          <div class="p-5 border-b border-gray-100">
            <textarea v-model="newComment" rows="3" placeholder="Add a comment... Use @ to mention teammates."
                      class="w-full px-3 py-2.5 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
            <div class="flex justify-end mt-2">
              <button @click="postComment" :disabled="!newComment.trim() || postingComment"
                      class="bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium px-4 py-2 rounded-lg disabled:opacity-40 transition">
                {{ postingComment ? 'Posting...' : 'Comment' }}
              </button>
            </div>
          </div>

          <div v-if="comments.length" class="divide-y divide-gray-100">
            <CommentItem v-for="comment in comments" :key="comment.id" :comment="comment"
                         @deleted="loadIssue" @replied="loadIssue" @updated="loadIssue" :issue-members="issue.project?.members || []" />
          </div>
          <div v-else class="p-8 text-center text-sm text-gray-500">No comments yet.</div>
        </div>

        <!-- Attachments -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-200 flex items-center justify-between">
            <h3 class="font-semibold text-gray-900">Attachments ({{ attachments.length }})</h3>
            <label class="inline-flex items-center gap-1.5 text-sm bg-white border border-gray-200 hover:bg-gray-50 text-gray-700 px-3 py-1.5 rounded-lg cursor-pointer transition">
              <Icon name="paperclip" size="sm" /> Upload
              <input type="file" multiple class="hidden" @change="uploadFiles" />
            </label>
          </div>
          <div v-if="attachments.length" class="p-5 grid grid-cols-1 sm:grid-cols-2 gap-3">
            <div v-for="att in attachments" :key="att.id" class="flex items-center gap-3 rounded-lg border border-gray-200 p-3">
              <div class="p-2.5 bg-gray-100 rounded-lg text-gray-500"><Icon name="paperclip" size="sm" /></div>
              <div class="flex-1 min-w-0">
                <a :href="att.url" target="_blank" class="text-sm text-indigo-600 hover:underline truncate block">{{ att.filename }}</a>
                <p class="text-xs text-gray-500">{{ formatSize(att.size) }} · uploaded by {{ att.user?.name }}</p>
              </div>
              <button @click="deleteAttachment(att)" class="text-gray-400 hover:text-red-500"><Icon name="trash" size="sm" /></button>
            </div>
          </div>
          <div v-else class="p-6 text-center text-sm text-gray-500">No attachments.</div>
        </div>

        <!-- Activity -->
        <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">
          <div class="px-5 py-4 border-b border-gray-200">
            <h3 class="font-semibold text-gray-900">Activity</h3>
          </div>
          <div v-if="activity.length" class="divide-y divide-gray-100">
            <div v-for="item in activity" :key="item.id" class="px-5 py-3 flex items-start gap-3">
              <UserAvatar :user="item.causer" size="xs" />
              <div class="min-w-0">
                <p class="text-sm text-gray-700">
                  <span class="font-medium text-gray-900">{{ item.causer?.name || 'Someone' }}</span>
                  {{ item.event }} this issue
                </p>
                <p class="text-xs text-gray-400">{{ timeAgo(item.created_at) }}</p>
              </div>
            </div>
          </div>
          <div v-else class="p-6 text-center text-sm text-gray-500">No activity yet.</div>
        </div>
      </div>

      <!-- Sidebar -->
      <aside class="space-y-4">
        <div class="bg-white rounded-xl border border-gray-200 p-5 space-y-4">
          <h3 class="font-semibold text-gray-900 text-sm">Status</h3>
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Priority</label>
            <select v-model="issue.priority" @change="updateField('priority')" class="w-full px-2.5 py-2 border border-gray-200 rounded-lg text-sm capitalize">
              <option value="low">Low</option>
              <option value="medium">Medium</option>
              <option value="high">High</option>
              <option value="urgent">Urgent</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Assignee</label>
            <select v-model="issue.assigned_to" @change="updateField('assigned_to')" class="w-full px-2.5 py-2 border border-gray-200 rounded-lg text-sm">
              <option :value="null">Unassigned</option>
              <option v-for="member in project?.members || []" :key="member.id" :value="member.id">{{ member.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Sprint</label>
            <select v-model="issue.sprint_id" @change="updateField('sprint_id')" class="w-full px-2.5 py-2 border border-gray-200 rounded-lg text-sm">
              <option :value="null">No sprint</option>
              <option v-for="sprint in project?.sprints || []" :key="sprint.id" :value="sprint.id">{{ sprint.name }}</option>
            </select>
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Due Date</label>
            <input :value="dueDateValue" @change="updateField('due_at', $event.target.value || null)" type="date"
                   class="w-full px-2.5 py-2 border border-gray-200 rounded-lg text-sm">
          </div>
          <div>
            <label class="block text-xs font-medium text-gray-500 mb-1">Estimate (hours)</label>
            <input :value="issue.estimate" @change="updateField('estimate', $event.target.value)" type="number" min="0" step="0.5"
                   class="w-full px-2.5 py-2 border border-gray-200 rounded-lg text-sm">
          </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h3 class="font-semibold text-gray-900 text-sm mb-3">Labels</h3>
          <div v-if="project?.labels?.length" class="flex flex-wrap gap-2 mb-3">
            <button v-for="label in project.labels" :key="label.id" @click="toggleLabel(label)"
                    class="text-xs px-2.5 py-1 rounded-lg transition border"
                    :style="isLabelSelected(label) ? { backgroundColor: `${label.color}1a`, color: label.color, borderColor: `${label.color}40` } : { backgroundColor: 'transparent', color: '#6b7280', borderColor: '#e5e7eb' }">
              {{ label.name }}
            </button>
          </div>
          <p v-else class="text-xs text-gray-500">No labels in this project yet.</p>
        </div>

        <div class="bg-white rounded-xl border border-gray-200 p-5">
          <h3 class="font-semibold text-gray-900 text-sm mb-3">Details</h3>
          <dl class="space-y-2 text-sm">
            <div class="flex justify-between">
              <dt class="text-gray-500">Created by</dt>
              <dd class="text-gray-800 font-medium">{{ issue.creator?.name }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Created</dt>
              <dd class="text-gray-800">{{ formatDate(issue.created_at) }}</dd>
            </div>
            <div class="flex justify-between">
              <dt class="text-gray-500">Project</dt>
              <dd class="text-gray-800 font-medium">{{ issue.project?.key }}</dd>
            </div>
          </dl>
          <button @click="deleteIssue" class="mt-4 w-full text-sm text-red-600 hover:bg-red-50 border border-red-200 rounded-lg py-2 transition">
            Delete Issue
          </button>
        </div>
      </aside>
    </div>
  </div>
</template>

<script setup>
import { computed, onMounted, ref } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { useIssueStore } from '../../stores/issue';
import { useProjectStore } from '../../stores/project';
import axios from 'axios';
import Icon from '../../shared/components/Icon.vue';
import Badge from '../../shared/components/Badge.vue';
import Spinner from '../../shared/components/Spinner.vue';
import UserAvatar from '../../shared/components/UserAvatar.vue';
import CommentItem from './CommentItem.vue';

const route = useRoute();
const router = useRouter();
const issueStore = useIssueStore();
const projectStore = useProjectStore();

const issue = ref(null);
const loading = ref(true);
const comments = ref([]);
const attachments = ref([]);
const activity = ref([]);

const editingTitle = ref(false);
const editingDescription = ref(false);
const draft = ref({ title: '', description: '' });

const newComment = ref('');
const postingComment = ref(false);

const project = computed(() => projectStore.currentProject);

async function loadIssue() {
  loading.value = true;
  try {
    const data = await issueStore.fetchIssue(route.params.id);
    issue.value = data.issue;
    comments.value = data.issue.comments || [];
    attachments.value = data.issue.attachments || [];
    activity.value = data.activity || [];
    draft.value = { title: data.issue.title, description: data.issue.description || '' };

    if (!projectStore.currentProject || projectStore.currentProject.id !== data.issue.project_id) {
      await projectStore.fetchProject(data.issue.project_id);
    }
  } finally {
    loading.value = false;
  }
}

const dueDateValue = computed(() => (issue.value?.due_at ? issue.value.due_at.slice(0, 10) : ''));

function startEditTitle() {
  draft.value.title = issue.value.title;
  editingTitle.value = true;
}

async function saveTitle() {
  editingTitle.value = false;
  if (draft.value.title && draft.value.title !== issue.value.title) {
    await issueStore.updateIssue(route.params.id, { title: draft.value.title });
    issue.value.title = draft.value.title;
  }
}

async function saveDescription() {
  editingDescription.value = false;
  await issueStore.updateIssue(route.params.id, { description: draft.value.description });
  issue.value.description = draft.value.description;
}

async function updateField(field, value) {
  await issueStore.updateIssue(route.params.id, { [field]: value });
  issue.value = { ...issueStore.currentIssue };
  if (field === 'assigned_to' || field === 'sprint_id') {
    await projectStore.fetchProject(issue.value.project_id);
  }
}

function isLabelSelected(label) {
  return issue.value.labels?.some((l) => l.id === label.id);
}

async function toggleLabel(label) {
  const ids = issue.value.labels.map((l) => l.id);
  const next = ids.includes(label.id) ? ids.filter((id) => id !== label.id) : [...ids, label.id];
  const { data } = await axios.post(`/issues/${route.params.id}/labels`, { label_ids: next });
  issue.value.labels = data.labels;
}

async function postComment() {
  postingComment.value = true;
  try {
    const comment = await issueStore.addComment(route.params.id, { body: newComment.value });
    comments.value.unshift(comment);
    newComment.value = '';
  } finally {
    postingComment.value = false;
  }
}

async function uploadFiles(e) {
  const files = Array.from(e.target.files);
  if (!files.length) return;
  const uploaded = await issueStore.uploadAttachments(route.params.id, files);
  attachments.value = [...uploaded, ...attachments.value];
  e.target.value = '';
}

async function deleteAttachment(att) {
  if (confirm(`Delete "${att.filename}"?`)) {
    await axios.delete(`/attachments/${att.id}`);
    attachments.value = attachments.value.filter((a) => a.id !== att.id);
  }
}

async function deleteIssue() {
  if (confirm(`Delete issue ${issue.value.key}?`)) {
    await issueStore.deleteIssue(route.params.id);
    router.push(`/projects/${issue.value.project_id}/issues`);
  }
}

function typeColor(type) {
  return { epic: '#8b5cf6', story: '#10b981', task: '#3b82f6', bug: '#ef4444', subtask: '#f59e0b' }[type] || '#64748b';
}

function statusColor(status) {
  return { todo: '#6b7280', in_progress: '#3b82f6', done: '#10b981' }[status] || '#64748b';
}

function formatDate(str) {
  return str ? new Date(str).toLocaleDateString() : '—';
}

function formatSize(bytes) {
  if (bytes < 1024) return `${bytes} B`;
  if (bytes < 1024 * 1024) return `${(bytes / 1024).toFixed(1)} KB`;
  return `${(bytes / (1024 * 1024)).toFixed(1)} MB`;
}

function timeAgo(str) {
  const seconds = Math.floor((Date.now() - new Date(str)) / 1000);
  if (seconds < 60) return 'just now';
  const minutes = Math.floor(seconds / 60);
  if (minutes < 60) return `${minutes}m ago`;
  const hours = Math.floor(minutes / 60);
  if (hours < 24) return `${hours}h ago`;
  const days = Math.floor(hours / 24);
  return `${days}d ago`;
}

onMounted(loadIssue);
</script>