<script setup lang="ts">
import { appUrl } from '../composables/appUrl';
// Link unduh berkas (mockup core.js fileLink): nama berkas bergaya kode + ikon unduh. Endpoint /documents memeriksa hak dan path.
defineProps<{ href: string; name: string }>();
</script>
<template>
    <a :href="appUrl(href)" class="file-dl code" :title="`Unduh ${name}`">{{ name }}<svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 4v11M7 10l5 5 5-5M5 20h14" /></svg></a>
</template>
<style>
.file-dl { display: inline-flex; align-items: center; gap: 4px; text-decoration: none; cursor: pointer; }
.file-dl:hover, .file-dl:focus-visible { text-decoration: underline; color: var(--accent, var(--navy)); }
.file-dl svg { flex: none; opacity: .75; }
</style>
