<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
defineProps<{message:string}>();
</script>
<template><Head title="Link Konfirmasi Ditutup"/><main class="confirmation-page"><h1>Link konfirmasi ditutup</h1><p role="status">{{message}}</p></main></template>
