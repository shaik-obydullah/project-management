<template>
  <div v-if="user" class="relative inline-flex items-center justify-center rounded-full select-none"
       :class="[sizeClasses, bgColor]">
    <img v-if="user.avatar" :src="user.avatar" :alt="user.name" class="rounded-full w-full h-full object-cover">
    <span v-else class="font-semibold text-white">{{ initials }}</span>
  </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    user: { type: Object, default: null },
    size: { type: String, default: 'md' },
});

const colors = ['#6366f1', '#8b5cf6', '#ec4899', '#f59e0b', '#10b981', '#3b82f6', '#ef4444', '#14b8a6'];

const sizeClasses = {
    xs: 'w-6 h-6 text-xs',
    sm: 'w-8 h-8 text-sm',
    md: 'w-10 h-10 text-base',
    lg: 'w-14 h-14 text-xl',
    xl: 'w-20 h-20 text-2xl',
};

const sizeMap = { xs: 'w-6 h-6', sm: 'w-8 h-8', md: 'w-10 h-10', lg: 'w-14 h-14', xl: 'w-20 h-20' };

const initials = computed(() => {
    if (!props.user?.name) return '?';
    return props.user.name.split(' ').map((p) => p[0]).slice(0, 2).join('').toUpperCase();
});

const bgColor = computed(() => {
    const name = props.user?.name || '';
    let hash = 0;
    for (let i = 0; i < name.length; i++) {
        hash = (hash << 5) - hash + name.charCodeAt(i);
        hash |= 0;
    }
    return { backgroundColor: colors[Math.abs(hash) % colors.length] };
});
</script>

<style scoped>
.w-6\/8 { width: 24px; }
</style>