<template>
  <svg :class="sizeClass" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
    <path v-if="name === 'dashboard'" d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z" />
    <path v-if="name === 'project'" d="M3 7V5a2 2 0 012-2h6l2 2h6a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V7z" />
    <rect v-if="name === 'board'" x="3" y="3" width="18" height="18" rx="2" />
    <path v-if="name === 'board'" d="M3 9h18M9 3v18" />
    <path v-if="name === 'calendar'" d="M8 2v4M16 2v4M3 10h18M5 4h14a2 2 0 012 2v14a2 2 0 01-2 2H5a2 2 0 01-2-2V6a2 2 0 012-2z" />
    <circle v-if="name === 'bell'" cx="12" cy="18" r="4" />
    <path v-if="name === 'bell'" d="M12 2v2m0 0a8 8 0 00-8 8h16a8 8 0 00-8-8zm0 16c-3 0-5-2-5-4" />
    <path v-if="name === 'search'" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z" />
    <path v-if="name === 'settings'" d="M12 15a3 3 0 100-6 3 3 0 000 6z" />
    <path v-if="name === 'settings'" d="M19.4 15a1.65 1.65 0 00.33 1.82l.06.06a2 2 0 010 2.83 2 2 0 01-2.83 0l-.06-.06a1.65 1.65 0 00-1.82-.33 1.65 1.65 0 00-1 1.51V21a2 2 0 01-2 2 2 2 0 01-2-2v-.09a1.65 1.65 0 00-1-1.51 1.65 1.65 0 00-1.82.33l-.06.06a2 2 0 01-2.83 0 2 2 0 010-2.83l.06-.06a1.65 1.65 0 00.33-1.82 1.65 1.65 0 00-1.51-1H3a2 2 0 01-2-2 2 2 0 012-2h.09a1.65 1.65 0 001.51-1 1.65 1.65 0 00-.33-1.82l-.06-.06a2 2 0 010-2.83 2 2 0 012.83 0l.06.06a1.65 1.65 0 001.82.33h0a1.65 1.65 0 001-1.51V3a2 2 0 012-2 2 2 0 012 2v.09a1.65 1.65 0 001 1.51 1.65 1.65 0 001.82-.33l.06-.06a2 2 0 012.83 0 2 2 0 010 2.83l-.06.06a1.65 1.65 0 00-.33 1.82v0a1.65 1.65 0 001.51 1H21a2 2 0 012 2 2 2 0 01-2 2h-.09a1.65 1.65 0 00-1.51 1z" />
    <path v-if="name === 'users'" d="M17 21v-2a4 4 0 00-4-4H5a4 4 0 00-4 4v2" />
    <circle v-if="name === 'users'" cx="9" cy="7" r="4" />
    <path v-if="name === 'users'" d="M23 21v-2a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" />
    <path v-if="name === 'logout'" d="M9 21H5a2 2 0 01-2-2V5a2 2 0 012-2h4M16 17l5-5-5-5M21 12H9" />
    <path v-if="name === 'plus'" d="M12 5v14M5 12h14" />
    <path v-if="name === 'chevron-left'" d="M15 18l-6-6 6-6" />
    <path v-if="name === 'chevron-right'" d="M9 18l6-6-6-6" />
    <path v-if="name === 'trash'" d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z" />
    <path v-if="name === 'edit'" d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7" />
    <path v-if="name === 'edit'" d="M18.5 2.5a2.121 2.121 0 013 3L12 15l-4 1 1-4 9.5-9.5z" />
    <path v-if="name === 'user'" d="M20 21v-2a4 4 0 00-4-4H8a4 4 0 00-4 4v2" />
    <circle v-if="name === 'user'" cx="12" cy="7" r="4" />
    <path v-if="name === 'check'" d="M20 6L9 17l-5-5" />
    <path v-if="name === 'filter'" d="M22 3H2l8 9.46V19l4 2v-8.54L22 3z" />
    <path v-if="name === 'x'" d="M18 6L6 18M6 6l12 12" />
    <path v-if="name === 'paperclip'" d="M21.44 11.05l-9.19 9.19a6 6 0 01-8.49-8.49l9.19-9.19a4 4 0 015.66 5.66l-9.2 9.19a2 2 0 01-2.83-2.83l8.49-8.48" />
    <path v-if="name === 'menu'" d="M3 12h18M3 6h18M3 18h18" />
    <path v-if="name === 'flag'" d="M4 15s1-1 4-1 5 2 8 2 4-1 4-1V3s-1 1-4 1-5-2-8-2-4 1-4 1z" />
    <path v-if="name === 'flag'" d="M4 22v-7" />
    <circle v-if="name === 'clock'" cx="12" cy="12" r="10" />
    <path v-if="name === 'clock'" d="M12 6v6l4 2" />
    <path v-if="name === 'comment'" d="M21 15a2 2 0 01-2 2H7l-4 4V5a2 2 0 012-2h14a2 2 0 012 2z" />
    <path v-if="name === 'tag'" d="M20.59 13.41l-7.17 7.17a2 2 0 01-2.83 0L2 12V2h10l8.59 8.59a2 2 0 010 2.82z" />
    <path v-if="name === 'tag'" d="M7 7h.01" />
    <path v-if="name === 'external'" d="M18 13v6a2 2 0 01-2 2H5a2 2 0 01-2-2V8a2 2 0 012-2h6" />
    <path v-if="name === 'external'" d="M15 3h6v6M10 14L21 3" />
  </svg>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    name: { type: String, required: true },
    size: { type: String, default: 'md' },
});

const sizeClass = computed(() => ({
    xs: 'w-3.5 h-3.5',
    sm: 'w-4 h-4',
    md: 'w-5 h-5',
    lg: 'w-6 h-6',
    xl: 'w-8 h-8',
}[props.size] || 'w-5 h-5'));
</script>