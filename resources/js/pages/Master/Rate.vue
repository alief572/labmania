<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import { useConfirmedMutation } from "../../composables/confirmedMutation";
import MoneyInput from '../../components/MoneyInput.vue';
import ServerTable from "../../components/ServerTable.vue";
import ActionMenu from "../../components/ActionMenu.vue";
import { computed, ref, watch } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';
import PageHead from '../../components/PageHead.vue';
import { rupiah } from '../../composables/costing';

import StatusBadge from '../../components/StatusBadge.vue';

const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;

type Row = { id: number; kode: string; item: string; keterangan: string | null; id_satuan: number; nama_satuan: string | null; default_cost: string | number; coa: string; aktif: number };
const props = defineProps<{
    tipe: 'fixed' | 'variable'; title: string; filters: { q: string; status: string }; counts: { fixed: number; variable: number };
    satuans: { id: number; nama: string }[]; coa: { no_perkiraan: string; nama: string }[]; can: { add: boolean; manage: boolean };
}>();
const filter = ref({ ...props.filters });
watch(() => filter.value.status, () => { appliedFilters.value = { ...filter.value }; table.value?.reload(true); });
const tabs = [['fixed', 'Fixed Cost'], ['variable', 'Variable Cost']] as const;
const editing = ref<Row | null>(null);
const adding = ref(false);
const form = useForm({ item: '', id_satuan: '' as string | number, default_cost: '', keterangan: '', coa: '' });
function open(row: Row | null) {
    form.reset(); form.clearErrors(); editing.value = row; adding.value = !row;
    if (row) Object.assign(form, { item: row.item, id_satuan: row.id_satuan, default_cost: String(Math.round(Number(row.default_cost))), keterangan: row.keterangan ?? '', coa: row.coa });
}
const close = () => { editing.value = null; adding.value = false; };
function save() {
    return confirmation.form(form, editing.value ? 'put' : 'post', editing.value ? `/master/rate/${props.tipe}/${editing.value.id}` : `/master/rate/${props.tipe}`, { title: `${editing.value ? 'Simpan perubahan' : 'Tambahkan'} ${form.item}${editing.value ? ` (${editing.value.kode})` : ''}?` }, {
        preserveScroll: true, onSuccess: () => { close(); reload(); },
    });
}
const toggle = (row: Row) => confirmation.visit('post', `/master/rate/${props.tipe}/${row.id}/toggle`, {}, {
    title: `${row.aktif ? 'Nonaktifkan' : 'Aktifkan'} ${row.item} (${row.kode})?`,
    confirmLabel: row.aktif ? 'Ya, nonaktifkan' : 'Ya, aktifkan', danger: !!row.aktif,
}, { preserveScroll: true, onSuccess: reload });

const table = ref<InstanceType<typeof ServerTable> | null>(null);
const reload = () => table.value?.reload();
const appliedFilters = ref({ ...props.filters });
const columns = computed(() => [{ title: 'No', orderable: false }, { title: `Kode ${props.title}`, orderable: true }, { title: 'Item', orderable: true }, { title: 'Satuan', orderable: true }, { title: 'Default Cost', orderable: true, className: 'dt-body-right' }, { title: 'Status', orderable: true }, { title: 'Aksi', orderable: false }]);
</script>

<template>
    <Head :title="`Master Rate · ${title}`" />
    <AppLayout title="Master Rate" full-width>
        <PageHead title="Master Rate"><button v-if="can.add" type="button" class="primary" @click="open(null)" :disabled="confirming">+ Tambah {{ title }}</button></PageHead>
        <div class="rate-notice">
            <p><strong>{{ title }}</strong> memakai satuan <strong>{{ tipe === 'fixed' ? 'Event atau Day' : 'Peserta/Event atau Peserta/Day' }}</strong>.<template v-if="tipe === 'fixed'"> Biaya per jam dicatat sebagai Event.</template></p>
            <p>Satu item memiliki satu Default Cost. Nilainya dapat disesuaikan di Master Program dan Penjualan Program tanpa mengubah master. Item tidak dapat dihapus; gunakan <strong>Nonaktifkan</strong>.</p>
        </div>
        <div class="rate-list">
            <nav class="tabs" aria-label="Kategori rate">
                <Link v-for="[key, label] in tabs" :key="key" :href="appUrl(`/master/rate/${key}`)" :aria-current="tipe === key ? 'page' : undefined">{{ label }}<span class="nav-count">{{ counts[key] }}</span></Link>
            </nav>
            <div class="filters">
                <label class="grow">Cari<input v-model="filter.q" type="search" maxlength="100" placeholder="Cari kode atau item…" /></label>
                <label>Status<select v-model="filter.status"><option value="all">Semua status</option><option value="on">Aktif</option><option value="off">Nonaktif</option></select></label>
            </div>
            <ServerTable ref="table" :url="appUrl(`/master/rate/${props.tipe}/data`)" :columns="columns" :filters="appliedFilters" :keyword="filter.q" :label="title">
                <template #cell-0="{ number }">{{ number }}</template>
                <template #cell-1="{ row }"><span class="code">{{ row.kode }}</span></template>
                <template #cell-2="{ row }"><strong class="rate-item">{{ row.item }}</strong><small v-if="row.keterangan">{{ row.keterangan }}</small></template>
                <template #cell-3="{ row }">{{ row.nama_satuan }}</template>
                <template #cell-4="{ row }"><span class="rate-cost">{{ rupiah(row.default_cost) }}</span></template>
                <template #cell-5="{ row }"><StatusBadge :active="row.aktif" /></template>
                <template #cell-6="{ row }"><ActionMenu v-if="can.manage"><button type="button" @click="open(row)" :disabled="confirming">Edit</button><button type="button" @click="toggle(row)" :disabled="confirming">{{ row.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></ActionMenu></template>
            </ServerTable>
        </div>

        <ConfirmDialog v-if="adding || editing" :title="editing ? `Edit ${title} ${editing.kode}` : `Tambah ${title}`" :busy="form.processing || confirming" @confirm="save" @cancel="close">
            <div class="grid2 rate-form">
                <FormField id="rkode" label="Kode" hint="Diisi otomatis oleh sistem" v-slot="{ describedBy }"><input id="rkode" :value="editing?.kode ?? ''" placeholder="Dibuat otomatis" readonly aria-readonly="true" :aria-describedby="describedBy" /></FormField>
                <FormField id="rkat" label="Kategori" :hint="editing ? 'Kategori tidak dapat diubah setelah disimpan' : 'Ganti kategori lewat tab di atas'" v-slot="{ describedBy }"><select id="rkat" :value="tipe" disabled :aria-describedby="describedBy"><option v-for="[key, label] in tabs" :key="key" :value="key">{{ key === 'fixed' ? 'Fixed' : 'Variable' }}</option></select></FormField>
                <FormField class="span2" id="item" label="Item" :error="form.errors.item" v-slot="{ describedBy, invalid }"><input id="item" v-model="form.item" maxlength="150" placeholder="Nama item biaya" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="satuan" label="Satuan" :hint="tipe === 'fixed' ? 'Biaya per jam dicatat sebagai Event' : 'Pilih satuan biaya per peserta'" :error="form.errors.id_satuan" v-slot="{ describedBy, invalid }">
                    <select id="satuan" v-model="form.id_satuan" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">— Pilih Satuan —</option><option v-for="s in satuans" :key="s.id" :value="s.id">{{ s.nama }}</option></select>
                </FormField>
                <FormField id="cost" label="Default Cost (Rp)" hint="Nilai awal; dapat diedit di Master Program & Penjualan Program" :error="form.errors.default_cost" v-slot="{ describedBy, invalid }">
                    <MoneyInput id="cost" v-model="form.default_cost" required :aria-invalid="invalid" :aria-describedby="describedBy" />
                </FormField>
                <FormField class="span2" id="ket" label="Keterangan" :error="form.errors.keterangan" v-slot="{ describedBy, invalid }"><input id="ket" v-model="form.keterangan" maxlength="150" placeholder="Opsional" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField class="span2" id="coa" label="COA" hint="Daftar COA diambil dari master COA (level 5)" :error="form.errors.coa" v-slot="{ describedBy, invalid }">
                    <select id="coa" v-model="form.coa" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">— Pilih COA —</option><option v-if="editing && form.coa === editing.coa && !coa.some(c => c.no_perkiraan === editing?.coa)" :value="editing.coa" disabled>{{ editing.coa }} (COA lama; pilih level 5)</option><option v-for="c in coa" :key="c.no_perkiraan" :value="c.no_perkiraan">{{ c.no_perkiraan }} — {{ c.nama }}</option></select>
                </FormField>
            </div>
        </ConfirmDialog>
    </AppLayout>
</template>

<style scoped>
.rate-notice { margin-bottom: 20px; padding: 14px 16px; border: 1px solid var(--line); border-radius: var(--radius); background: var(--surface); color: var(--ink-70); font-size: 13px; line-height: 1.6; }
.rate-notice p { margin: 0; }
.rate-notice p + p { margin-top: 4px; }
.rate-list { min-width: 0; overflow: hidden; border: 1px solid var(--line); border-radius: var(--radius); background: var(--surface); }
.rate-list .tabs { margin: 0; padding: 0 16px; border-radius: 0; }
.rate-list .tabs a { display: inline-flex; align-items: center; min-height: 48px; }
.rate-list .filters { margin: 0; padding: 16px; gap: 16px; border: 0; border-bottom: 1px solid var(--line); border-radius: 0; }
.rate-list .filters label { min-width: 0; max-width: none; flex: 1 1 100%; }
.rate-list :deep(input), .rate-list :deep(select) { border-color: #6F7F94; }
.rate-list :deep(.server-table) { margin: 0; padding: 16px; border: 0; border-radius: 0; box-shadow: none; }
.rate-list :deep(table.dataTable th), .rate-list :deep(table.dataTable td) { vertical-align: middle; }
.rate-list :deep(th:first-child), .rate-list :deep(td:first-child:not([colspan])) { width: 48px; }
.rate-list :deep(th:nth-child(2)), .rate-list :deep(td:nth-child(2)) { width: 180px; white-space: nowrap; }
.rate-list :deep(td:nth-child(3)) { min-width: 220px; }
.rate-list :deep(td:nth-child(3) small) { margin-top: 4px; line-height: 1.5; }
.rate-list :deep(th:nth-child(4)), .rate-list :deep(td:nth-child(4)) { width: 140px; white-space: nowrap; }
.rate-list :deep(th:nth-child(5)), .rate-list :deep(td:nth-child(5)) { width: 160px; white-space: nowrap; }
.rate-list :deep(th:nth-child(6)), .rate-list :deep(td:nth-child(6)) { width: 110px; white-space: nowrap; }
.rate-list :deep(th:last-child), .rate-list :deep(td:last-child:not([colspan])) { width: 72px; text-align: center; }
.rate-item { font-weight: 600; }
.rate-cost { font-weight: 600; font-variant-numeric: tabular-nums; }
.rate-form { gap: 20px 24px; }
.rate-form :deep(input), .rate-form :deep(select) { margin-top: 6px; border-color: #6F7F94; }
.rate-form :deep([aria-invalid=true]) { border-color: #9C2636; }
.rate-form :deep(small) { color: var(--ink-70); }
@media (min-width: 600px) {
    .rate-list .filters .grow { flex: 1 1 280px; }
    .rate-list .filters label:not(.grow) { flex: 0 1 200px; }
}
</style>
