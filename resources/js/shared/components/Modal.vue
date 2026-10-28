<template>
  <div v-if="show" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm"
       @click.self="$emit('close')">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] flex flex-col overflow-hidden">
      <div class="flex items-center justify-between px-5 py-4 border-b border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900">{{ title }}</h3>
        <button @click="$emit('close')" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">&times;</button>
      </div>
      <div class="p-5 overflow-y-auto flex-1">
        <slot />
      </div>
      <div v-if="$slots.footer" class="px-5 py-3 border-t border-gray-200 bg-gray-50 flex justify-end gap-2">
        <slot name="footer" />
      </div>
    </div>
  </div>
</template>

<script setup>
defineProps({
    show: { type: Boolean, default: false },
    title: { type: String, default: '' },
});
defineEmits(['close']);
</script>