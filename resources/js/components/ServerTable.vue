<script setup lang="ts">
import { appUrl } from '../composables/appUrl';
import { computed, nextTick, onBeforeUnmount, ref, shallowRef, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import DataTable from 'datatables.net-vue3';
import DataTablesCore from 'datatables.net-dt';
import type { AjaxFunction, Api, Options } from 'datatables.net';
import 'datatables.net-dt/css/dataTables.dataTables.css';
import { lastTablePage, tableError, tableQuery } from '../composables/serverTable';

DataTable.use(DataTablesCore);
const props = defineProps<{ url: string; label: string; columns: { title: string; orderable: boolean; className?: string }[]; filters?: Record<string, string | number>; refreshOnVisit?: boolean; keyword?: string }>();
const emit = defineEmits<{ loaded: [result: { recordsTotal: number; recordsFiltered: number; data?: unknown[]; meta?: Record<string, unknown> }] }>();
const instance = shallowRef<{ dt?: Api } | null>(null);
const error = ref('');
const start = ref(0);
let pending: AbortController | undefined;
let disposed = false;
const columns = computed(() => props.columns.map((column, index) => ({ ...column, data: null, defaultContent: '', render: `#cell-${index}` })));
const load: AjaxFunction = async (data, callback) => {
    const offset = data.start ?? 0;
    const length = data.length ?? 25;
    pending?.abort();
    const controller = new AbortController();
    pending = controller;
    error.value = '';
    try {
        const response = await fetch(`${appUrl(props.url)}?${tableQuery(data, props.filters)}`, {
            signal: controller.signal, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (!response.ok) throw new Error(tableError(response.status));
        const result = await response.json();
        if (!Array.isArray(result.data) || !Number.isInteger(result.recordsFiltered) || !Number.isInteger(result.recordsTotal) || result.draw !== data.draw) throw new Error(tableError(500));
        if (disposed || controller.signal.aborted) return;
        start.value = offset;
        callback(result);
        emit('loaded', result);
        if (offset > 0 && offset >= result.recordsFiltered) {
            await nextTick();
            if (!disposed && !controller.signal.aborted) {
                const api = instance.value?.dt;
                api?.page(lastTablePage(result.recordsFiltered, length));
                api?.draw('page');
            }
        }
    } catch (failure) {
        if (disposed || controller.signal.aborted) return;
        error.value = failure instanceof Error ? failure.message : tableError(500);
        callback({ draw: data.draw, recordsTotal: 0, recordsFiltered: 0, data: [] });
    }
};
const options: Options = {
    serverSide: true, processing: true, pageLength: 25, lengthMenu: [10, 25, 50, 100], searchDelay: 300,
    layout: { topStart: null, topEnd: props.keyword !== undefined ? null : 'search', bottomStart: ['pageLength', 'info'], bottomEnd: 'paging' },
    order: [], autoWidth: false, scrollX: true, stateSave: false, search: { search: String(props.keyword ?? props.filters?.q ?? props.filters?.keyword ?? '') },
    language: {
        search: 'Cari:', searchPlaceholder: 'Cari data…', lengthMenu: 'Tampilkan _MENU_ baris',
        info: 'Menampilkan _START_–_END_ dari _TOTAL_ data', infoEmpty: 'Tidak ada data', infoFiltered: '(disaring dari _MAX_ data)',
        emptyTable: 'Tidak ada data yang cocok.', zeroRecords: 'Tidak ada data yang cocok.', processing: 'Memuat data…', loadingRecords: 'Memuat data…',
        paginate: { first: 'Pertama', last: 'Terakhir', next: 'Berikutnya', previous: 'Sebelumnya' },
    },
};
async function reload(reset = false) { await nextTick(); instance.value?.dt?.ajax.reload(undefined, reset); }
watch(() => JSON.stringify(props.filters), () => { if (props.refreshOnVisit) reload(true); });
// Kotak cari milik halaman (toolbar mockup) memakai search[value] bawaan DataTables; kotak "Cari:" bawaan disembunyikan lewat CSS.
let keywordTimer: ReturnType<typeof setTimeout> | undefined;
watch(() => props.keyword, (value) => {
    clearTimeout(keywordTimer);
    keywordTimer = setTimeout(() => { const api = instance.value?.dt; if (api && api.search() !== (value ?? '')) api.search(value ?? '').draw(); }, 300);
});
const removeVisit = props.refreshOnVisit ? router.on('success', () => reload()) : undefined;
onBeforeUnmount(() => { disposed = true; clearTimeout(keywordTimer); pending?.abort(); removeVisit?.(); });
defineExpose({ reload });
</script>

<template>
    <section data-tour="table" class="server-table" :aria-label="label" :data-own-search="keyword !== undefined ? '' : undefined">
        <p v-if="error" class="error table-error" role="alert">{{ error }} <button type="button" @click="reload()">Coba lagi</button></p>
        <DataTable ref="instance" :columns="columns" :ajax="load" :options="options" class="display" style="width: 100%">
            <caption class="sr-only">{{ label }}</caption>
            <template v-for="(_, index) in props.columns" :key="index" #[`cell-${index}`]="cell">
                <slot :name="`cell-${index}`" :row="cell.rowData" :number="start + cell.rowIndex + 1" />
            </template>
        </DataTable>
    </section>
</template>

<style scoped>
.server-table :deep(.dt-length label) {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    margin: 0;
    color: var(--ink-70);
    font-size: 13px;
    font-weight: 500;
    white-space: nowrap;
}
.server-table :deep(.dt-length select) {
    width: 76px;
    min-height: 44px;
    margin: 0;
    padding: 8px 10px;
    border: 1px solid #6F7F94;
    border-radius: 8px;
    background: var(--surface);
    color: var(--navy);
    font-weight: 600;
    font-variant-numeric: tabular-nums;
}
.server-table :deep(.dt-length select:hover) { border-color: var(--navy); }
</style>
