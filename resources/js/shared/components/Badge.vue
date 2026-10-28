<template>
  <div class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-xs font-medium"
       :style="{ backgroundColor: `${color}1a`, color }">
    <span v-if="dot" class="w-1.5 h-1.5 rounded-full" :style="{ backgroundColor: color }"></span>
    <slot>{{ label }}</slot>
  </div>
</template>

<script setup>
defineProps({
    label: { type: String, default: '' },
    color: { type: String, default: '#64748b' },
    dot: { type: Boolean, default: false },
});
</script>