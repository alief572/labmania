<script setup lang="ts">
defineProps<{ active: boolean | number }>();
</script>

<template>
    <span class="badge" :class="active ? 'on' : 'off'">{{ active ? 'Aktif' : 'Nonaktif' }}</span>
</template>
