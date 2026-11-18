<script setup lang="ts">
// Kerangka halaman publik (mockup konfirmasi.html / survey.html): header navy, kartu 680px, footer.
defineProps<{ subtitle: string }>();
</script>

<template>
    <div class="konf-body">
        <header class="konf-top"><span class="konf-brand">LabMania</span><span class="konf-sub">{{ subtitle }}</span></header>
        <main class="konf-main"><slot /></main>
        <footer class="konf-foot">PT LabMania Indonesia Jaya</footer>
    </div>
</template>
