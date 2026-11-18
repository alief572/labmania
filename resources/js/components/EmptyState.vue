<script setup lang="ts">
// Keadaan kosong mockup (.empty-state): ikon emas, judul, penjelasan, dan slot untuk tombol aksi.
defineProps<{ title: string; description?: string }>();
</script>

<template>
    <div class="empty-state">
        <div class="icon" aria-hidden="true">
            <slot name="icon"><svg viewBox="0 0 24 24" width="28" height="28" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 7l9-4 9 4-9 4zM3 7v10l9 4 9-4V7M12 11v10" /></svg></slot>
        </div>
        <h2>{{ title }}</h2>
        <p v-if="description">{{ description }}</p>
        <div v-if="$slots.default" class="empty-actions"><slot /></div>
    </div>
</template>
