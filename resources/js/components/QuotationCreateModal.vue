<script setup lang="ts">
import { onBeforeUnmount, ref } from 'vue';
import { appUrl } from '../composables/appUrl';
import ConfirmDialog from './ConfirmDialog.vue';
import QuotationForm from '../pages/Quotation/Form.vue';

const props = defineProps<{ programId?: number }>();
const emit = defineEmits<{ close: [] }>();
const data = ref<InstanceType<typeof QuotationForm>['$props'] | null>(null);
const error = ref('');
const request = new AbortController();
onBeforeUnmount(() => request.abort());
async function load() {
    error.value = '';
    try {
        const response = await fetch(appUrl('/quotation/create' + (props.programId ? `?id_penjualan=${props.programId}` : '')), { headers: { Accept: 'application/json' }, signal: request.signal });
        if (!response.ok || !response.headers.get('Content-Type')?.includes('application/json')) throw new Error('Form quotation tidak dapat dimuat. Periksa sesi dan hak akses, lalu coba lagi.');
        const result = await response.json();
        if (!request.signal.aborted) data.value = result;
    } catch (e) {
        if (!request.signal.aborted) error.value = e instanceof Error ? e.message : 'Form quotation tidak dapat dimuat.';
    }
}
load();
</script>

<template>
    <QuotationForm v-if="data" v-bind="data" modal @close="emit('close')" />
    <ConfirmDialog v-else title="Buat Quotation" size="xwide" info @cancel="emit('close')">
        <template v-if="error"><p role="alert" class="error">{{ error }}</p><button type="button" @click="load">Coba lagi</button></template>
        <p v-else role="status">Memuat form quotation…</p>
    </ConfirmDialog>
</template>
