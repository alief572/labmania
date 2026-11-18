<script setup lang="ts">
// Banner navy berisi judul dan jadwal program (mockup .pub-head). Baris kosong dilewati.
defineProps<{ judul: string; lines: (string | null | undefined)[]; sisa?: number | null }>();
</script>

<template>
    <div class="pub-head">
        <div>
            <b>{{ judul }}</b>
            <template v-for="(line, i) in lines" :key="i"><small v-if="line">{{ line }}</small></template>
        </div>
        <span v-if="sisa !== undefined && sisa !== null" class="konf-sisa"><b>{{ sisa }}</b> kursi tersisa</span>
    </div>
</template>
