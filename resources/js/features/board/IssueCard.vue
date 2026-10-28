<template>
  <div class="w-80 shrink-0 bg-white rounded-xl border border-gray-200 shadow-sm overflow-hidden cursor-grab active:cursor-grabbing hover:shadow-md transition"
       @click="router.push(`/issues/${issue.id}`)">
    <div class="p-3.5">
      <div class="flex items-center justify-between mb-2">
        <span class="text-xs font-mono text-gray-500">{{ issue.key }}</span>
        <Badge :label="issue.type" :color="typeColor(issue.type)" />
      </div>
      <p class="text-sm text-gray-800 font-medium leading-snug line-clamp-3">{{ issue.title }}</p>
      <div v-if="issue.labels?.length" class="flex flex-wrap gap-1 mt-2">
        <span v-for="label in issue.labels" :key="label.id"
              class="text-[10px] px-1.5 py-0.5 rounded"
              :style="{ backgroundColor: `${label.color}1a`, color: label.color }">{{ label.name }}</span>
      </div>
      <div class="flex items-center justify-between mt-3">
        <div class="flex items-center gap-1">
          <Icon v-if="issue.priority === 'urgent'" name="flag" size="sm" class="text-red-500" />
          <Icon v-else-if="issue.priority === 'high'" name="flag" size="sm" class="text-orange-500" />
          <Icon v-else name="flag" size="sm" class="text-gray-300" />
          <span v-if="issue.subtasks?.length" class="flex items-center gap-0.5 text-xs text-gray-400">
            <Icon name="board" size="xs" /> {{ issue.subtasks.length }}
          </span>
        </div>
        <UserAvatar v-if="issue.assignee" :user="issue.assignee" size="xs" />
      </div>
    </div>
  </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import Icon from '../../shared/components/Icon.vue';
import Badge from '../../shared/components/Badge.vue';
import UserAvatar from '../../shared/components/UserAvatar.vue';

defineProps({
    issue: { type: Object, required: true },
});

const router = useRouter();

function typeColor(type) {
    return {
        epic: '#8b5cf6',
        story: '#10b981',
        task: '#3b82f6',
        bug: '#ef4444',
        subtask: '#f59e0b',
    }[type] || '#64748b';
}
</script>