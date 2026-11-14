<script setup lang="ts">
import ActionMenu from "../../components/ActionMenu.vue";
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';
import Pagination from '../../components/Pagination.vue';
import StatusBadge from '../../components/StatusBadge.vue';

type Row = { id: number; kode: string; nama: string; label_kegiatan: string; id_jenis_produk: number | null; nama_produk: string | null; isian: string[]; pernyataan: string; catatan: string | null; aktif: number };
const props = defineProps<{
    rows: { data: Row[]; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null; from: number | null };
    fields: Record<string, string>; produk: { id: number; nama: string }[]; can: { add: boolean; manage: boolean };
}>();
const editing = ref<Row | null>(null);
const adding = ref(false);
const form = useForm({ nama: '', label_kegiatan: '', id_jenis_produk: '' as string | number, isian: [] as string[], pernyataan: '', catatan: '' });
const sample = ref<{ template: { nama: string; label_kegiatan: string; isian: string[]; pernyataan: string; catatan: string | null }; fields: Record<string, string>; program: string | null } | null>(null);
function open(row: Row | null) {
    form.reset(); form.clearErrors(); editing.value = row; adding.value = !row;
    if (row) Object.assign(form, { nama: row.nama, label_kegiatan: row.label_kegiatan, id_jenis_produk: row.id_jenis_produk ?? '', isian: [...row.isian], pernyataan: row.pernyataan, catatan: row.catatan ?? '' });
}
const close = () => { editing.value = null; adding.value = false; };
function save() {
    const done = { preserveScroll: true, onSuccess: close };
    if (editing.value) form.put(`/master/template-konfirmasi/${editing.value.id}`, done);
    else form.post('/master/template-konfirmasi', done);
}
const toggle = (row: Row) => router.post(`/master/template-konfirmasi/${row.id}/toggle`, {}, { preserveScroll: true });
async function contoh(row: Row) { sample.value = await (await fetch(`/master/template-konfirmasi/${row.id}/contoh`, { headers: { Accept: 'application/json' } })).json(); }
</script>

<template>
    <Head title="Template Formulir Konfirmasi" />
    <AppLayout title="Template Formulir Konfirmasi">
        <div class="page-head"><h1>Template Formulir Konfirmasi</h1><button v-if="can.add" type="button" class="primary" @click="open(null)">+ Tambah Template</button></div>
        <div class="table-wrap"><table>
            <caption class="sr-only">Daftar template formulir konfirmasi</caption>
            <thead><tr><th scope="col">No</th><th scope="col">Kode</th><th scope="col">Nama</th><th scope="col">Default untuk</th><th scope="col">Isian</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
            <tbody>
                <tr v-for="(row, i) in rows.data" :key="row.id">
                    <td>{{ (rows.from ?? 1) + i }}</td><td>{{ row.kode }}</td><td>{{ row.nama }}<small>{{ row.label_kegiatan }}</small></td><td>{{ row.nama_produk ?? 'Semua' }}</td><td>{{ row.isian.length }} isian</td><td><StatusBadge :active="row.aktif" /></td>
                    <td><ActionMenu ><button type="button" @click="contoh(row)">Lihat contoh</button><template v-if="can.manage"><button type="button" @click="open(row)">Edit</button><button type="button" @click="toggle(row)">{{ row.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></template></ActionMenu></td>
                </tr>
                <tr v-if="!rows.data.length"><td colspan="7" class="empty">Belum ada template.</td></tr>
            </tbody>
        </table></div>
        <Pagination :page="rows" label="Halaman template konfirmasi" />

        <ConfirmDialog v-if="adding || editing" :title="editing ? `Edit template ${editing.kode}` : 'Tambah template'" :busy="form.processing" @confirm="save" @cancel="close">
            <div class="stack">
                <FormField id="nama" label="Nama template" :error="form.errors.nama" v-slot="{ describedBy, invalid }"><input id="nama" v-model="form.nama" maxlength="80" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="lk" label="Label kegiatan" :error="form.errors.label_kegiatan" v-slot="{ describedBy, invalid }"><input id="lk" v-model="form.label_kegiatan" maxlength="80" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="jp" label="Default untuk Jenis Produk" :error="form.errors.id_jenis_produk" v-slot="{ describedBy, invalid }">
                    <select id="jp" v-model="form.id_jenis_produk" :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">Semua</option><option v-for="p in produk" :key="p.id" :value="p.id">{{ p.nama }}</option></select>
                </FormField>
                <fieldset><legend>Isian formulir</legend>
                    <label v-for="(text, key) in fields" :key="key"><input v-model="form.isian" type="checkbox" :value="key" /> {{ text }}</label>
                    <p v-if="form.errors.isian" class="error" role="alert">{{ form.errors.isian }}</p>
                </fieldset>
                <FormField id="pn" label="Kalimat pernyataan" :error="form.errors.pernyataan" v-slot="{ describedBy, invalid }"><textarea id="pn" v-model="form.pernyataan" required :aria-invalid="invalid" :aria-describedby="describedBy"></textarea></FormField>
                <FormField id="ct" label="Catatan (satu butir per baris)" :error="form.errors.catatan" v-slot="{ describedBy, invalid }"><textarea id="ct" v-model="form.catatan" :aria-invalid="invalid" :aria-describedby="describedBy"></textarea></FormField>
            </div>
        </ConfirmDialog>

        <ConfirmDialog v-if="sample" :title="`Contoh: ${sample.template.nama}`" confirm-label="Tutup" @confirm="sample = null" @cancel="sample = null">
            <p class="muted">Program contoh: {{ sample.program ?? 'belum ada Penjualan Program Final' }}</p>
            <h3>{{ sample.template.label_kegiatan }}</h3>
            <ul><li v-for="k in sample.template.isian" :key="k">{{ sample.fields[k] }}</li></ul>
            <p>{{ sample.template.pernyataan }}</p>
            <p v-if="sample.template.catatan" style="white-space: pre-line">{{ sample.template.catatan }}</p>
        </ConfirmDialog>
    </AppLayout>
</template>
