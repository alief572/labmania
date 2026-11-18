<script setup lang="ts">
import { computed } from 'vue';
// Sorot kata yang dicari di menu sidebar (mockup .nav-item mark). Dirender sebagai teks, bukan HTML.
const props = defineProps<{ text: string; query?: string }>();
const parts = computed(() => {
    const q = (props.query ?? '').trim().toLowerCase();
    const at = q ? props.text.toLowerCase().indexOf(q) : -1;
    if (at < 0) return [{ t: props.text, hit: false }];
    return [
        { t: props.text.slice(0, at), hit: false },
        { t: props.text.slice(at, at + q.length), hit: true },
        { t: props.text.slice(at + q.length), hit: false },
    ].filter(p => p.t);
});
</script>

<template><template v-for="(p, i) in parts" :key="i"><mark v-if="p.hit">{{ p.t }}</mark><template v-else>{{ p.t }}</template></template></template>
