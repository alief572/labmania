<script setup lang="ts">
import ActionMenu from "../../components/ActionMenu.vue";
import { ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';
import Pagination from '../../components/Pagination.vue';
import StatusBadge from '../../components/StatusBadge.vue';

type Row = { id: number; kode: string; kategori: string; teks: string; berlaku_untuk: string; is_default: number; aktif: number };
const props = defineProps<{
    filters: { kategori: string; status: string }; kategori: string[]; untuk: string[];
    rows: { data: Row[]; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null; from: number | null };
    can: { add: boolean; manage: boolean };
}>();
const label: Record<string, string> = { syarat: 'Syarat', termasuk: 'Termasuk', tidak: 'Tidak termasuk', dokumen: 'Dokumen', klausul: 'Klausul', semua: 'Semua', public: 'Public', b2b: 'B2B' };
const filter = ref({ ...props.filters });
const apply = () => router.get('/master/template-keterangan', Object.fromEntries(Object.entries(filter.value).filter(([, v]) => v !== '')), { preserveScroll: true });
const editing = ref<Row | null>(null);
const adding = ref(false);
const form = useForm({ kategori: '', teks: '', berlaku_untuk: 'semua', is_default: false });
function open(row: Row | null) {
    form.reset(); form.clearErrors(); editing.value = row; adding.value = !row;
    if (row) Object.assign(form, { kategori: row.kategori, teks: row.teks, berlaku_untuk: row.berlaku_untuk, is_default: !!row.is_default });
}
const close = () => { editing.value = null; adding.value = false; };
function save() {
    const done = { preserveScroll: true, onSuccess: close };
    if (editing.value) form.put(`/master/template-keterangan/${editing.value.id}`, done);
    else form.post('/master/template-keterangan', done);
}
const toggle = (row: Row) => router.post(`/master/template-keterangan/${row.id}/toggle`, {}, { preserveScroll: true });
</script>

<template>
    <Head title="Template Keterangan Quotation" />
    <AppLayout title="Template Keterangan Quotation">
        <div class="page-head"><h1>Template Keterangan Quotation</h1><button v-if="can.add" type="button" class="primary" @click="open(null)">+ Tambah Butir</button></div>
        <form class="filters" @submit.prevent="apply">
            <label>Kategori<select v-model="filter.kategori"><option value="">Semua</option><option v-for="k in kategori" :key="k" :value="k">{{ label[k] }}</option></select></label>
            <label>Status<select v-model="filter.status"><option value="all">Semua</option><option value="on">Aktif</option><option value="off">Nonaktif</option></select></label>
            <button class="primary">Terapkan</button>
        </form>
        <div class="table-wrap"><table>
            <caption class="sr-only">Daftar butir keterangan</caption>
            <thead><tr><th scope="col">No</th><th scope="col">Kode</th><th scope="col">Kategori</th><th scope="col">Teks</th><th scope="col">Berlaku untuk</th><th scope="col">Default</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
            <tbody>
                <tr v-for="(row, i) in rows.data" :key="row.id">
                    <td>{{ (rows.from ?? 1) + i }}</td><td>{{ row.kode }}</td><td>{{ label[row.kategori] ?? row.kategori }}</td><td>{{ row.teks }}</td><td>{{ label[row.berlaku_untuk] }}</td>
                    <td>{{ row.is_default ? 'Ya' : '—' }}</td><td><StatusBadge :active="row.aktif" /></td>
                    <td><ActionMenu v-if="can.manage" ><button type="button" @click="open(row)">Edit</button><button type="button" @click="toggle(row)">{{ row.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></ActionMenu></td>
                </tr>
                <tr v-if="!rows.data.length"><td colspan="8" class="empty">Belum ada butir.</td></tr>
            </tbody>
        </table></div>
        <Pagination :page="rows" label="Halaman template keterangan" />

        <ConfirmDialog v-if="adding || editing" :title="editing ? `Edit butir ${editing.kode}` : 'Tambah butir'" :busy="form.processing" @confirm="save" @cancel="close">
            <div class="stack">
                <FormField id="kat" label="Kategori" :error="form.errors.kategori" v-slot="{ describedBy, invalid }">
                    <select id="kat" v-model="form.kategori" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">Pilih…</option><option v-for="k in kategori" :key="k" :value="k">{{ label[k] }}</option></select>
                </FormField>
                <FormField id="teks" label="Teks" hint="Maksimal 500 karakter." :error="form.errors.teks" v-slot="{ describedBy, invalid }"><textarea id="teks" v-model="form.teks" maxlength="500" required :aria-invalid="invalid" :aria-describedby="describedBy"></textarea></FormField>
                <FormField id="untuk" label="Berlaku untuk" :error="form.errors.berlaku_untuk" v-slot="{ describedBy, invalid }">
                    <select id="untuk" v-model="form.berlaku_untuk" :aria-invalid="invalid" :aria-describedby="describedBy"><option v-for="u in untuk" :key="u" :value="u">{{ label[u] }}</option></select>
                </FormField>
                <label><input v-model="form.is_default" type="checkbox" /> Terpilih otomatis (default)</label>
            </div>
        </ConfirmDialog>
    </AppLayout>
</template>
