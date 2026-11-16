<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
defineProps<{ page: { current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null }; label: string }>();
</script>

<template>
    <nav class="pagination" :aria-label="label">
        <Link v-if="page.prev_page_url" :href="page.prev_page_url">Sebelumnya</Link>
        <span>Halaman {{ page.current_page }} / {{ page.last_page }}</span>
        <Link v-if="page.next_page_url" :href="page.next_page_url">Berikutnya</Link>
    </nav>
</template>
