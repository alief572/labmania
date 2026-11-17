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
const columns = computed(() => [{ title: 'No', orderable: false }, { title: `Kode ${props.title}`, orderable: true }, { title: 'Item', orderable: true }, { title: 'Satuan', orderable: true }, { title: 'Default Cost', orderable: true, className: 'dt-body-right' }, { title: 'Status', orderable: true }, { title: 'Action', orderable: false }]);
</script>

<template>
    <Head :title="`Master Rate · ${title}`" />
    <AppLayout title="Master Rate" full-width>
        <PageHead title="Master Rate" description="Item biaya beserta Default Cost yang dipakai costing Master Program dan Penjualan Program."><button v-if="can.add" type="button" class="primary" @click="open(null)" :disabled="confirming">+ Tambah {{ title }}</button></PageHead>
        <p class="notice">Satuan <strong>Fixed Cost</strong>: Event, Day (biaya per jam dicatat sebagai Event). Satuan <strong>Variable Cost</strong>: Peserta/Event, Peserta/Day. Satu item satu Default Cost, yang dapat disesuaikan di Master Program dan Penjualan Program tanpa mengubah master. Item tidak dapat dihapus — gunakan Nonaktifkan.</p>
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
            <template #cell-2="{ row }">{{ row.item }}<small v-if="row.keterangan">{{ row.keterangan }}</small></template>
            <template #cell-3="{ row }">{{ row.nama_satuan }}</template>
            <template #cell-4="{ row }">{{ rupiah(row.default_cost) }}</template>
            <template #cell-5="{ row }"><StatusBadge :active="row.aktif" /></template>
            <template #cell-6="{ row }"><ActionMenu v-if="can.manage" ><button type="button" @click="open(row)" :disabled="confirming">Edit</button><button type="button" @click="toggle(row)" :disabled="confirming">{{ row.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></ActionMenu></template>
        </ServerTable>

        <ConfirmDialog v-if="adding || editing" :title="editing ? `Edit ${title} ${editing.kode}` : `Tambah ${title}`" :busy="form.processing || confirming" @confirm="save" @cancel="close">
            <div class="grid2">
                <FormField id="rkode" label="Kode" hint="Diisi otomatis oleh sistem" v-slot="{ describedBy }"><input id="rkode" :value="editing?.kode ?? ''" placeholder="Dibuat otomatis" readonly aria-readonly="true" :aria-describedby="describedBy" /></FormField>
                <FormField id="rkat" label="Kategori" :hint="editing ? 'Kategori tidak dapat diubah setelah disimpan' : 'Ganti kategori lewat tab di atas'" v-slot="{ describedBy }"><select id="rkat" :value="tipe" disabled :aria-describedby="describedBy"><option v-for="[key, label] in tabs" :key="key" :value="key">{{ key === 'fixed' ? 'Fixed' : 'Variable' }}</option></select></FormField>
                <FormField id="satuan" label="Satuan" :error="form.errors.id_satuan" v-slot="{ describedBy, invalid }">
                    <select id="satuan" v-model="form.id_satuan" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">— Pilih Satuan —</option><option v-for="s in satuans" :key="s.id" :value="s.id">{{ s.nama }}</option></select>
                </FormField>
                <FormField class="span2" id="item" label="Item" :error="form.errors.item" v-slot="{ describedBy, invalid }"><input id="item" v-model="form.item" maxlength="150" placeholder="Nama item biaya" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
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
