<script setup lang="ts">
import { useConfirmedMutation } from "../../composables/confirmedMutation";
import ServerTable from "../../components/ServerTable.vue";
import ActionMenu from "../../components/ActionMenu.vue";
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';

import StatusBadge from '../../components/StatusBadge.vue';

const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;

type Opt = { id: number; nama: string };
type Row = { id: number; nomor: string; judul: string; aktif: number; id_jenis_produk: number; id_jenis_bidang: number; id_varian_pelaksanaan: number | null; id_jenis_sertifikasi: number; nama_produk: string | null; nama_bidang: string | null; nama_varian: string | null; nama_sertifikasi: string | null };
const props = defineProps<{
    filters: { q: string; status: string };
    options: { produk: Opt[]; bidang: Opt[]; varian: Opt[]; sertifikasi: Opt[] };
    can: { add: boolean; manage: boolean };
    lingkupMax: { max_items: number; max_length: number };
}>();
const fields = [['produk', 'Produk'], ['bidang', 'Bidang'], ['varian', 'Varian'], ['sertifikasi', 'Sertifikat']] as const;
const filter = ref({ ...props.filters });
const apply = () => { appliedFilters.value = { ...filter.value }; table.value?.reload(true); };
const blank = () => ({ produk: '', bidang: '', varian: '', sertifikasi: '' });

const mode = ref<'add' | 'edit' | 'lingkup' | null>(null);
const current = ref<Row | null>(null);
const addForm = useForm({ judul: '', rows: [blank()] as Record<string, string | number>[] });
const editForm = useForm({ judul: '', produk: '' as string | number, bidang: '' as string | number, varian: '' as string | number, sertifikasi: '' as string | number });
const lingkupForm = useForm({ text: '' });
const detail = ref<{ lingkup: string[]; dipakai: number } | null>(null);

function openAdd() { addForm.reset(); addForm.clearErrors(); addForm.rows = [blank()]; mode.value = 'add'; }
function openEdit(row: Row) {
    editForm.clearErrors(); current.value = row; mode.value = 'edit';
    Object.assign(editForm, { judul: row.judul, produk: row.id_jenis_produk, bidang: row.id_jenis_bidang, varian: row.id_varian_pelaksanaan ?? '', sertifikasi: row.id_jenis_sertifikasi });
}
async function openLingkup(row: Row) {
    current.value = row; lingkupForm.clearErrors(); mode.value = 'lingkup';
    const res = await fetch(`/master/product/${row.id}`, { headers: { Accept: 'application/json' } });
    detail.value = await res.json();
    lingkupForm.text = (detail.value?.lingkup ?? []).join('\n');
}
const close = () => { mode.value = null; current.value = null; };
const done = { preserveScroll: true, onSuccess: () => { close(); reload(); } };
function saveAdd() {
    return confirmation.form(addForm, 'post', '/master/product', {
        title: `Buat ${addForm.rows.length} Product berjudul ${addForm.judul}?`,
    }, done);
}
function saveEdit() {
    return confirmation.form(editForm, 'put', `/master/product/${current.value!.id}`, {
        title: `Simpan perubahan ${editForm.judul} (${current.value!.nomor})?`,
    }, done);
}
function saveLingkup() {
    return confirmation.visit('put', `/master/product/${current.value!.id}/lingkup`, { items: lingkupForm.text.split(/\r?\n/) }, {
        title: `Simpan lingkup ${current.value!.judul} (${current.value!.nomor})?`,
    }, { ...done, onError: (e) => { lingkupForm.setError('text', e.lingkup); } });
}
const toggle = (row: Row) => confirmation.visit('post', `/master/product/${row.id}/toggle`, {}, {
    title: `${row.aktif ? 'Nonaktifkan' : 'Aktifkan'} ${row.judul} (${row.nomor})?`,
    confirmLabel: row.aktif ? 'Ya, nonaktifkan' : 'Ya, aktifkan', danger: !!row.aktif,
}, { preserveScroll: true, onSuccess: reload });
const rowError = (i: number) => (addForm.errors as Record<string, string>)[`rows.${i}`] ?? (addForm.errors as Record<string, string>)[`rows.${i}.produk`];

const table = ref<InstanceType<typeof ServerTable> | null>(null);
const reload = () => table.value?.reload();
const appliedFilters = ref({ ...props.filters });
const columns = computed(() => [{ title: 'No', orderable: false }, { title: 'Product', orderable: true }, { title: 'Produk', orderable: true }, { title: 'Bidang', orderable: true }, { title: 'Varian', orderable: true }, { title: 'Sertifikat', orderable: true }, { title: 'Status', orderable: true }, { title: 'Aksi', orderable: false }]);
</script>

<template>
    <Head title="Product" />
    <AppLayout title="Product" full-width>
        <div class="page-head"><h1>Product</h1><button v-if="can.add" type="button" class="primary" @click="openAdd" :disabled="confirming">+ Tambah Product</button></div>
        <p v-if="($page.props.errors as Record<string, string>).status" class="error" role="alert">{{ ($page.props.errors as Record<string, string>).status }}</p>
        <form class="filters" @submit.prevent="apply">
            <label>Status<select v-model="filter.status"><option value="all">Semua</option><option value="on">Aktif</option><option value="off">Nonaktif</option></select></label>
            <button class="primary" :disabled="confirming">Terapkan</button>
        </form>
        <ServerTable ref="table" url="/master/product/data" :columns="columns" :filters="appliedFilters" label="Product">
            <template #cell-0="{ number }">{{ number }}</template>
            <template #cell-1="{ row }">{{ row.judul }}<small>{{ row.nomor }}</small></template>
            <template #cell-2="{ row }">{{ row.nama_produk }}</template>
            <template #cell-3="{ row }">{{ row.nama_bidang }}</template>
            <template #cell-4="{ row }">{{ row.nama_varian ?? '—' }}<small v-if="!row.nama_varian">Belum diisi; lengkapi lewat Edit</small></template>
            <template #cell-5="{ row }">{{ row.nama_sertifikasi }}</template>
            <template #cell-6="{ row }"><StatusBadge :active="row.aktif" /></template>
            <template #cell-7="{ row }"><ActionMenu v-if="can.manage" >
                        <button type="button" @click="openEdit(row)" :disabled="confirming">Edit</button><button type="button" @click="openLingkup(row)" :disabled="confirming">Lingkup</button>
                        <button type="button" @click="toggle(row)" :disabled="confirming">{{ row.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                    </ActionMenu></template>
        </ServerTable>

        <ConfirmDialog v-if="mode === 'add'" title="Tambah Product" :busy="addForm.processing || confirming" @confirm="saveAdd" @cancel="close">
            <FormField id="judul" label="Judul" :error="addForm.errors.judul" v-slot="{ describedBy, invalid }"><input id="judul" v-model="addForm.judul" maxlength="150" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
            <fieldset v-for="(row, i) in addForm.rows" :key="i" class="stack">
                <legend>Kombinasi {{ i + 1 }}</legend>
                <div class="form-grid">
                    <label v-for="[key, label] in fields" :key="key"><span>{{ label }}</span>
                        <select v-model="row[key]" :aria-label="`${label} kombinasi ${i + 1}`" required><option value="">Pilih…</option><option v-for="o in options[key]" :key="o.id" :value="o.id">{{ o.nama }}</option></select>
                    </label>
                </div>
                <p v-if="rowError(i)" class="error" role="alert">{{ rowError(i) }}</p>
                <button v-if="addForm.rows.length > 1" type="button" class="danger" :disabled="confirming" @click="addForm.rows.splice(i, 1)">Hapus baris</button>
            </fieldset>
            <button type="button" @click="addForm.rows.push(blank())" :disabled="confirming">+ Tambah Baris</button>
            <p class="muted">Setiap baris menjadi satu Product. Jika salah satu bentrok, tidak ada yang tersimpan.</p>
        </ConfirmDialog>

        <ConfirmDialog v-if="mode === 'edit' && current" :title="`Edit Product ${current.nomor}`" :busy="editForm.processing || confirming" @confirm="saveEdit" @cancel="close">
            <FormField id="ejudul" label="Judul" :error="editForm.errors.judul" v-slot="{ describedBy, invalid }"><input id="ejudul" v-model="editForm.judul" maxlength="150" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
            <div class="form-grid">
                <label v-for="[key, label] in fields" :key="key"><span>{{ label }}</span>
                    <select v-model="(editForm as any)[key]" required><option value="">Pilih…</option><option v-for="o in options[key]" :key="o.id" :value="o.id">{{ o.nama }}</option></select>
                    <span v-if="(editForm.errors as Record<string, string>)[key]" class="error" role="alert">{{ (editForm.errors as Record<string, string>)[key] }}</span>
                </label>
            </div>
            <p v-if="(editForm.errors as Record<string, string>).kombinasi" class="error" role="alert">{{ (editForm.errors as Record<string, string>).kombinasi }}</p>
        </ConfirmDialog>

        <ConfirmDialog v-if="mode === 'lingkup' && current" :title="`Lingkup ${current.nomor}`" :busy="lingkupForm.processing || confirming" @confirm="saveLingkup" @cancel="close">
            <FormField id="lingkup" label="Satu item per baris" :hint="`Maksimal ${lingkupMax.max_items} item, ${lingkupMax.max_length} karakter per item.`" :error="lingkupForm.errors.text" v-slot="{ describedBy, invalid }">
                <textarea id="lingkup" v-model="lingkupForm.text" rows="8" :aria-invalid="invalid" :aria-describedby="describedBy"></textarea>
            </FormField>
            <p v-if="detail?.dipakai" class="muted">Dipakai {{ detail.dipakai }} Master Program.</p>
        </ConfirmDialog>
    </AppLayout>
</template>
