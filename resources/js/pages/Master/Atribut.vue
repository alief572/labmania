<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import { useConfirmedMutation } from "../../composables/confirmedMutation";
import ServerTable from "../../components/ServerTable.vue";
import ActionMenu from "../../components/ActionMenu.vue";
import { computed, ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import PageHead from '../../components/PageHead.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';

import StatusBadge from '../../components/StatusBadge.vue';

const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;

type Row = { id: number; kode: string; nama: string; aktif: number };
const props = defineProps<{
    slug: string; label: string; filters: { q: string; status: string };
    can: { add: boolean; manage: boolean };
}>();
const filter = ref({ ...props.filters });
const presentation = computed(() => ({
    'jenis-produk': { code: 'Kode Jenis Produk', name: 'Jenis Produk' },
    'jenis-sertifikat': { code: 'Kode Sertifikat', name: 'Jenis Sertifikat' },
    'jenis-bidang': { code: 'Kode Bidang', name: 'Jenis Bidang' },
    'varian-pelaksanaan': { code: 'Kode Varian', name: 'Varian' },
} as Record<string, { code: string; name: string }>)[props.slug]);
watch(() => filter.value.status, () => { appliedFilters.value = { ...filter.value }; table.value?.reload(true); });
const editing = ref<Row | null>(null);
const adding = ref(false);
const form = useForm({ nama: '' });
function open(row: Row | null) { form.reset(); form.clearErrors(); editing.value = row; adding.value = !row; if (row) form.nama = row.nama; }
function close() { editing.value = null; adding.value = false; }
function save() {
    return confirmation.form(form, editing.value ? 'put' : 'post', editing.value ? `/master/${props.slug}/${editing.value.id}` : `/master/${props.slug}`, { title: `${editing.value ? 'Simpan perubahan' : 'Tambahkan'} ${form.nama || props.label}${editing.value ? ` (${editing.value.kode})` : ''}?` }, {
        preserveScroll: true, onSuccess: () => { close(); reload(); },
    });
}
const toggle = (row: Row) => confirmation.visit('post', `/master/${props.slug}/${row.id}/toggle`, {}, {
    title: `${row.aktif ? 'Nonaktifkan' : 'Aktifkan'} ${row.nama} (${row.kode})?`,
    confirmLabel: row.aktif ? 'Ya, nonaktifkan' : 'Ya, aktifkan', danger: !!row.aktif,
}, { preserveScroll: true, onSuccess: reload });

const table = ref<InstanceType<typeof ServerTable> | null>(null);
const reload = () => table.value?.reload();
const appliedFilters = ref({ ...props.filters });
const columns = computed(() => [{ title: 'No', orderable: false }, { title: presentation.value?.code || 'Kode', orderable: true }, { title: presentation.value?.name || 'Nama', orderable: true }, { title: 'Status', orderable: true }, { title: props.slug === 'jenis-produk' ? 'Aksi' : 'Action', orderable: false }]);
</script>

<template>
    <Head :title="label" />
    <AppLayout :title="label" full-width>
        <PageHead :title="label"><button v-if="can.add" type="button" class="primary" @click="open(null)" :disabled="confirming">+ Tambah {{ label }}</button></PageHead>
        <p class="notice" :class="{ 'jenis-produk-notice': slug === 'jenis-produk' }">Data master <strong>tidak dapat dihapus</strong>; gunakan <strong>Nonaktifkan</strong>. Data nonaktif tidak muncul sebagai pilihan di form Product dan Peserta.</p>
        <div :class="{ 'jenis-produk-list': slug === 'jenis-produk' }">
            <div class="filters">
                <label class="grow">Cari<input v-model="filter.q" type="search" maxlength="100" placeholder="Cari kode atau nama…" /></label>
                <label>Status<select v-model="filter.status"><option value="all">Semua status</option><option value="on">Aktif</option><option value="off">Nonaktif</option></select></label>
            </div>
            <ServerTable ref="table" :url="appUrl(`/master/${props.slug}/data`)" :columns="columns" :filters="appliedFilters" :keyword="filter.q" :label="label">
                <template #cell-0="{ number }">{{ number }}</template>
                <template #cell-1="{ row }"><span class="code">{{ row.kode }}</span></template>
                <template #cell-2="{ row }">{{ row.nama }}</template>
                <template #cell-3="{ row }"><StatusBadge :active="row.aktif" /></template>
                <template #cell-4="{ row }"><ActionMenu v-if="can.manage"><button type="button" @click="open(row)" :disabled="confirming">Edit</button><button type="button" @click="toggle(row)" :disabled="confirming">{{ row.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></ActionMenu></template>
            </ServerTable>
        </div>

        <ConfirmDialog v-if="adding || editing" :title="editing ? `Edit ${label} ${editing.kode}` : `Tambah ${label}`" :busy="form.processing || confirming" @confirm="save" @cancel="close">
            <div :class="{ 'jenis-produk-form': slug === 'jenis-produk' }">
                <FormField id="akode" :label="presentation?.code || 'Kode'" hint="Diisi otomatis oleh sistem saat disimpan" v-slot="{ describedBy }">
                    <input id="akode" :value="editing?.kode || ''" placeholder="Dibuat otomatis" readonly aria-readonly="true" :aria-describedby="describedBy" />
                </FormField>
                <FormField id="nama" :label="presentation?.name || 'Nama'" :error="form.errors.nama" v-slot="{ describedBy, invalid }">
                    <input id="nama" v-model="form.nama" maxlength="100" required :placeholder="`Ketik ${(presentation?.name || 'nama').toLowerCase()}`" :aria-invalid="invalid" :aria-describedby="describedBy" @keydown.enter.prevent="save" />
                </FormField>
            </div>
        </ConfirmDialog>
    </AppLayout>
</template>

<style scoped>
.jenis-produk-notice {
    margin: 0 0 20px;
    padding: 12px 16px;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    color: var(--ink-70);
    font-size: 13px;
    line-height: 1.6;
}
.jenis-produk-list {
    min-width: 0;
    border: 1px solid var(--line);
    border-radius: var(--radius);
    background: var(--surface);
    overflow: hidden;
}
.jenis-produk-list .filters {
    margin: 0;
    padding: 16px;
    gap: 16px;
    border: 0;
    border-bottom: 1px solid var(--line);
    border-radius: 0;
}
.jenis-produk-list .filters label { min-width: 0; max-width: none; flex: 1 1 100%; }
.jenis-produk-list :deep(input), .jenis-produk-list :deep(select) { border-color: #6F7F94; }
.jenis-produk-list :deep(.server-table) { margin: 0; padding: 16px; border: 0; border-radius: 0; box-shadow: none; }
.jenis-produk-list :deep(table.dataTable th), .jenis-produk-list :deep(table.dataTable td) { vertical-align: middle; }
.jenis-produk-list :deep(th:first-child), .jenis-produk-list :deep(td:first-child:not([colspan])) { width: 48px; }
.jenis-produk-list :deep(th:nth-child(2)), .jenis-produk-list :deep(td:nth-child(2)) { width: 180px; white-space: nowrap; }
.jenis-produk-list :deep(td:nth-child(3)) { min-width: 180px; font-weight: 600; }
.jenis-produk-list :deep(th:nth-child(4)), .jenis-produk-list :deep(td:nth-child(4)) { width: 110px; white-space: nowrap; }
.jenis-produk-list :deep(th:last-child), .jenis-produk-list :deep(td:last-child:not([colspan])) { width: 72px; text-align: center; }
.jenis-produk-form { display: grid; gap: 20px; }
.jenis-produk-form :deep(.field) { margin: 0; }
.jenis-produk-form :deep(input) { margin-top: 6px; border-color: #6F7F94; }
.jenis-produk-form :deep(input[aria-invalid=true]) { border-color: #9C2636; }
.jenis-produk-form :deep(input[readonly]) { background: var(--navy-50); color: var(--ink-70); font-family: var(--mono); }
.jenis-produk-form :deep(small) { color: var(--ink-70); }
@media (min-width: 600px) {
    .jenis-produk-list .filters .grow { flex: 1 1 280px; }
    .jenis-produk-list .filters label:not(.grow) { flex: 0 1 200px; }
}
</style>
