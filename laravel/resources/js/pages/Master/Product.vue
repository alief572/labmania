<script setup lang="ts">
import ActionMenu from "../../components/ActionMenu.vue";
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';
import Pagination from '../../components/Pagination.vue';
import StatusBadge from '../../components/StatusBadge.vue';

type Opt = { id: number; nama: string };
type Row = { id: number; nomor: string; judul: string; aktif: number; id_jenis_produk: number; id_jenis_bidang: number; id_varian_pelaksanaan: number | null; id_jenis_sertifikasi: number; nama_produk: string | null; nama_bidang: string | null; nama_varian: string | null; nama_sertifikasi: string | null };
const props = defineProps<{
    filters: { q: string; status: string };
    rows: { data: Row[]; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null; from: number | null };
    options: { produk: Opt[]; bidang: Opt[]; varian: Opt[]; sertifikasi: Opt[] };
    can: { add: boolean; manage: boolean };
    lingkupMax: { max_items: number; max_length: number };
}>();
const fields = [['produk', 'Produk'], ['bidang', 'Bidang'], ['varian', 'Varian'], ['sertifikasi', 'Sertifikat']] as const;
const filter = ref({ ...props.filters });
const apply = () => router.get('/master/product', filter.value, { preserveScroll: true });
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
const done = { preserveScroll: true, onSuccess: close };
function saveAdd() { addForm.post('/master/product', done); }
function saveEdit() { editForm.put(`/master/product/${current.value!.id}`, done); }
function saveLingkup() {
    router.put(`/master/product/${current.value!.id}/lingkup`, { items: lingkupForm.text.split(/\r?\n/) }, { ...done, onError: (e) => { lingkupForm.setError('text', e.lingkup); } });
}
const toggle = (row: Row) => router.post(`/master/product/${row.id}/toggle`, {}, { preserveScroll: true });
const rowError = (i: number) => (addForm.errors as Record<string, string>)[`rows.${i}`] ?? (addForm.errors as Record<string, string>)[`rows.${i}.produk`];
</script>

<template>
    <Head title="Product" />
    <AppLayout title="Product">
        <div class="page-head"><h1>Product</h1><button v-if="can.add" type="button" class="primary" @click="openAdd">+ Tambah Product</button></div>
        <p v-if="($page.props.errors as Record<string, string>).status" class="error" role="alert">{{ ($page.props.errors as Record<string, string>).status }}</p>
        <form class="filters" @submit.prevent="apply">
            <label>Cari<input v-model="filter.q" type="search" maxlength="100" placeholder="Nomor atau judul" /></label>
            <label>Status<select v-model="filter.status"><option value="all">Semua</option><option value="on">Aktif</option><option value="off">Nonaktif</option></select></label>
            <button class="primary">Terapkan</button>
        </form>
        <div class="table-wrap"><table>
            <caption class="sr-only">Daftar Product</caption>
            <thead><tr><th scope="col">No</th><th scope="col">Product</th><th scope="col">Produk</th><th scope="col">Bidang</th><th scope="col">Varian</th><th scope="col">Sertifikat</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
            <tbody>
                <tr v-for="(row, i) in rows.data" :key="row.id">
                    <td>{{ (rows.from ?? 1) + i }}</td><td>{{ row.judul }}<small>{{ row.nomor }}</small></td><td>{{ row.nama_produk }}</td><td>{{ row.nama_bidang }}</td>
                    <td>{{ row.nama_varian ?? '—' }}<small v-if="!row.nama_varian">Belum diisi; lengkapi lewat Edit</small></td><td>{{ row.nama_sertifikasi }}</td><td><StatusBadge :active="row.aktif" /></td>
                    <td><ActionMenu v-if="can.manage" >
                        <button type="button" @click="openEdit(row)">Edit</button><button type="button" @click="openLingkup(row)">Lingkup</button>
                        <button type="button" @click="toggle(row)">{{ row.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button>
                    </ActionMenu></td>
                </tr>
                <tr v-if="!rows.data.length"><td colspan="8" class="empty">Belum ada Product.</td></tr>
            </tbody>
        </table></div>
        <Pagination :page="rows" label="Halaman Product" />

        <ConfirmDialog v-if="mode === 'add'" title="Tambah Product" :busy="addForm.processing" @confirm="saveAdd" @cancel="close">
            <FormField id="judul" label="Judul" :error="addForm.errors.judul" v-slot="{ describedBy, invalid }"><input id="judul" v-model="addForm.judul" maxlength="150" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
            <fieldset v-for="(row, i) in addForm.rows" :key="i" class="stack">
                <legend>Kombinasi {{ i + 1 }}</legend>
                <div class="form-grid">
                    <label v-for="[key, label] in fields" :key="key"><span>{{ label }}</span>
                        <select v-model="row[key]" :aria-label="`${label} kombinasi ${i + 1}`" required><option value="">Pilih…</option><option v-for="o in options[key]" :key="o.id" :value="o.id">{{ o.nama }}</option></select>
                    </label>
                </div>
                <p v-if="rowError(i)" class="error" role="alert">{{ rowError(i) }}</p>
                <button v-if="addForm.rows.length > 1" type="button" class="danger" @click="addForm.rows.splice(i, 1)">Hapus baris</button>
            </fieldset>
            <button type="button" @click="addForm.rows.push(blank())">+ Tambah Baris</button>
            <p class="muted">Setiap baris menjadi satu Product. Jika salah satu bentrok, tidak ada yang tersimpan.</p>
        </ConfirmDialog>

        <ConfirmDialog v-if="mode === 'edit' && current" :title="`Edit Product ${current.nomor}`" :busy="editForm.processing" @confirm="saveEdit" @cancel="close">
            <FormField id="ejudul" label="Judul" :error="editForm.errors.judul" v-slot="{ describedBy, invalid }"><input id="ejudul" v-model="editForm.judul" maxlength="150" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
            <div class="form-grid">
                <label v-for="[key, label] in fields" :key="key"><span>{{ label }}</span>
                    <select v-model="(editForm as any)[key]" required><option value="">Pilih…</option><option v-for="o in options[key]" :key="o.id" :value="o.id">{{ o.nama }}</option></select>
                    <span v-if="(editForm.errors as Record<string, string>)[key]" class="error" role="alert">{{ (editForm.errors as Record<string, string>)[key] }}</span>
                </label>
            </div>
            <p v-if="(editForm.errors as Record<string, string>).kombinasi" class="error" role="alert">{{ (editForm.errors as Record<string, string>).kombinasi }}</p>
        </ConfirmDialog>

        <ConfirmDialog v-if="mode === 'lingkup' && current" :title="`Lingkup ${current.nomor}`" :busy="lingkupForm.processing" @confirm="saveLingkup" @cancel="close">
            <FormField id="lingkup" label="Satu item per baris" :hint="`Maksimal ${lingkupMax.max_items} item, ${lingkupMax.max_length} karakter per item.`" :error="lingkupForm.errors.text" v-slot="{ describedBy, invalid }">
                <textarea id="lingkup" v-model="lingkupForm.text" rows="8" :aria-invalid="invalid" :aria-describedby="describedBy"></textarea>
            </FormField>
            <p v-if="detail?.dipakai" class="muted">Dipakai {{ detail.dipakai }} Master Program.</p>
        </ConfirmDialog>
    </AppLayout>
</template>
