<script setup lang="ts">
import { onBeforeUnmount, ref, watch } from 'vue';
// Pesan sukses melayang (mockup .toast). Hilang sendiri setelah 8 detik dan bisa ditutup manual (WCAG 2.2.1).
const props = defineProps<{ message?: string | null }>();
const shown = ref(false);
let timer: ReturnType<typeof setTimeout> | undefined;
watch(() => props.message, (value) => {
    clearTimeout(timer);
    shown.value = !!value;
    if (value) timer = setTimeout(() => { shown.value = false; }, 8000);
}, { immediate: true });
onBeforeUnmount(() => clearTimeout(timer));
</script>

<template>
    <div class="toasts" role="status" aria-live="polite">
        <p v-if="shown && message" class="toast">
            <span>{{ message }}</span>
            <button type="button" class="toast-x" aria-label="Tutup pesan" @click="shown = false">×</button>
        </p>
    </div>
</template>
