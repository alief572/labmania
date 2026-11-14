<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ConfirmDialog from './ConfirmDialog.vue';
import { money, type Order } from '../types/salesOrder';
defineProps<{ so: Order; kepada: string; count: number; total: string }>();
const open = ref(false);
const form = useForm({});
</script>
<template>
    <button type="button" @click="open = true">Siap dibuatkan invoice</button>
    <ConfirmDialog v-if="open" title="Siap dibuatkan invoice?" confirm-label="Tandai Siap invoice" :busy="form.processing" @cancel="open = false" @confirm="form.post(`/sales-order/${so.id}/siap-invoice`, { preserveScroll: true, onSuccess: () => open = false })">
        <p>{{ so.nomor }} · {{ kepada }}</p><p>{{ count }} peserta Deal · {{ money(total) }}</p><p>Status Siap invoice tidak dapat dibatalkan.</p><p v-for="error in form.errors" :key="error" class="error" role="alert">{{ error }}</p>
    </ConfirmDialog>
</template>
