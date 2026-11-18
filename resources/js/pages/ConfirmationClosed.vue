<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import PublicLayout from '../components/PublicLayout.vue';
defineProps<{message:string}>();
</script>
<template><Head title="Link Konfirmasi Ditutup"/><PublicLayout subtitle="Konfirmasi Kehadiran Peserta"><h1>Link konfirmasi ditutup</h1><div class="pub-closed" role="status"><p>{{message}}</p></div></PublicLayout></template>
