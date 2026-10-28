<template>
  <div class="px-5 py-4">
    <div class="flex items-start gap-3">
      <UserAvatar :user="comment.user" size="sm" />
      <div class="flex-1 min-w-0">
        <div class="flex items-baseline gap-2 mb-1">
          <span class="text-sm font-medium text-gray-900">{{ comment.user?.name }}</span>
          <span class="text-xs text-gray-400">{{ timeAgo(comment.created_at) }}</span>
          <template v-if="canEdit">
            <button @click="startEdit" class="text-xs text-gray-400 hover:text-indigo-600">Edit</button>
            <button @click="startReply" class="text-xs text-gray-400 hover:text-indigo-600">Reply</button>
            <button @click="remove" class="text-xs text-gray-400 hover:text-red-500">Delete</button>
          </template>
          <template v-else>
            <button @click="startReply" class="text-xs text-gray-400 hover:text-indigo-600">Reply</button>
          </template>
        </div>

        <template v-if="editing">
          <textarea v-model="draftBody" rows="3"
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm"></textarea>
          <div class="flex gap-2 mt-2">
            <button @click="saveEdit" class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3 py-1.5 rounded">Save</button>
            <button @click="editing = false" class="text-xs px-3 py-1.5 rounded border border-gray-200 text-gray-600">Cancel</button>
          </div>
        </template>
        <template v-else>
          <div class="text-sm text-gray-700 whitespace-pre-wrap leading-relaxed">{{ comment.body }}</div>
        </template>

        <!-- Reply box -->
        <div v-if="replying" class="mt-3">
          <textarea v-model="replyBody" rows="2" placeholder="Write a reply..." 
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-500 text-sm resize-none"></textarea>
          <div class="flex gap-2 mt-2">
            <button @click="submitReply" :disabled="!replyBody.trim()"
                    class="bg-indigo-600 hover:bg-indigo-700 text-white text-xs px-3 py-1.5 rounded disabled:opacity-40">Reply</button>
            <button @click="replying = false" class="text-xs px-3 py-1.5 rounded border border-gray-200 text-gray-600">Cancel</button>
          </div>
        </div>

        <!-- Replies -->
        <div v-if="comment.replies?.length" class="mt-4 space-y-3 border-l-2 border-gray-100 pl-4">
          <div v-for="reply in comment.replies" :key="reply.id" class="flex items-start gap-3">
            <UserAvatar :user="reply.user" size="xs" />
            <div class="flex-1 min-w-0">
              <div class="flex items-baseline gap-2">
                <span class="text-sm font-medium text-gray-900">{{ reply.user?.name }}</span>
                <span class="text-xs text-gray-400">{{ timeAgo(reply.created_at) }}</span>
                <button v-if="canDelete(reply)" @click="remove(reply)" class="text-xs text-gray-400 hover:text-red-500">Delete</button>
              </div>
              <div class="text-sm text-gray-700 whitespace-pre-wrap">{{ reply.body }}</div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed, ref } from 'vue';
import { useIssueStore } from '../../stores/issue';
import { useAuthStore } from '../../stores/auth';
import UserAvatar from '../../shared/components/UserAvatar.vue';

const props = defineProps({
    comment: { type: Object, required: true },
});

const emit = defineEmits(['deleted', 'replied', 'updated']);

const issueStore = useIssueStore();
const auth = useAuthStore();

const editing = ref(false);
const replying = ref(false);
const draftBody = ref('');
const replyBody = ref('');

const isMine = computed(() => props.comment.user_id === auth.user?.id);
const isAdmin = computed(() => props.comment.isAdmin);
const canEdit = computed(() => isMine.value || isAdmin.value);

function canDelete(comment) {
    return comment.user_id === auth.user?.id;
}

function startEdit() {
    draftBody.value = props.comment.body;
    editing.value = true;
}

async function saveEdit() {
    await issueStore.updateComment(props.comment.id, { body: draftBody.value });
    props.comment.body = draftBody.value;
    editing.value = false;
    emit('updated');
}

function startReply() {
    replyBody.value = '';
    replying.value = true;
}

async function submitReply() {
    const comment = await issueStore.addComment(props.comment.issue_id || props.comment.id, { body: replyBody.value, parent_id: props.comment.id });
    props.comment.replies = [...(props.comment.replies || []), comment];
    replying.value = false;
    emit('replied');
}

async function remove(comment) {
    if (confirm('Delete this comment?')) {
        await issueStore.deleteComment(comment.id);
        emit('deleted');
    }
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
</script>