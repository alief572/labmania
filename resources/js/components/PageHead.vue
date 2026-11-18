<script setup lang="ts">
// Kepala halaman sesuai mockup: judul, deskripsi singkat, dan slot aksi (tombol tambah, cetak, dst.).
defineProps<{ title: string; description?: string; reference?: string }>();
</script>

<template>
    <div class="page-head">
        <div>
            <h1>{{ title }}<span v-if="reference" class="ref">{{ reference }}</span></h1>
            <p v-if="description" class="page-desc">{{ description }}</p>
        </div>
        <slot />
    </div>
</template>
