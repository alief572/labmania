<script setup lang="ts">
import ActionMenu from "../../components/ActionMenu.vue";
import { ref } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';
import Pagination from '../../components/Pagination.vue';
import StatusBadge from '../../components/StatusBadge.vue';

type Row = { id: number; kode: string; item: string; keterangan: string | null; id_satuan: number; nama_satuan: string | null; default_cost: string | number; coa: string; aktif: number };
const props = defineProps<{
    tipe: 'fixed' | 'variable'; title: string; filters: { q: string; status: string };
    rows: { data: Row[]; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null; from: number | null };
    satuans: { id: number; nama: string }[]; coa: { no_perkiraan: string; nama: string }[]; can: { add: boolean; manage: boolean };
}>();
const filter = ref({ ...props.filters });
const apply = () => router.get(`/master/rate/${props.tipe}`, filter.value, { preserveScroll: true });
const editing = ref<Row | null>(null);
const adding = ref(false);
const form = useForm({ item: '', id_satuan: '' as string | number, default_cost: '', keterangan: '', coa: '' });
function open(row: Row | null) {
    form.reset(); form.clearErrors(); editing.value = row; adding.value = !row;
    if (row) Object.assign(form, { item: row.item, id_satuan: row.id_satuan, default_cost: String(Math.round(Number(row.default_cost))), keterangan: row.keterangan ?? '', coa: row.coa });
}
const close = () => { editing.value = null; adding.value = false; };
function save() {
    const done = { preserveScroll: true, onSuccess: close };
    if (editing.value) form.put(`/master/rate/${props.tipe}/${editing.value.id}`, done);
    else form.post(`/master/rate/${props.tipe}`, done);
}
const toggle = (row: Row) => router.post(`/master/rate/${props.tipe}/${row.id}/toggle`, {}, { preserveScroll: true });
const rupiah = (v: string | number) => 'Rp ' + Math.round(Number(v)).toLocaleString('id-ID');
</script>

<template>
    <Head :title="`Master Rate · ${title}`" />
    <AppLayout title="Master Rate">
        <div class="page-head"><h1>Master Rate</h1><button v-if="can.add" type="button" class="primary" @click="open(null)">+ Tambah {{ title }}</button></div>
        <nav class="tabs" aria-label="Kategori rate">
            <Link href="/master/rate/fixed" :aria-current="tipe === 'fixed' ? 'page' : undefined">Fixed Cost</Link>
            <Link href="/master/rate/variable" :aria-current="tipe === 'variable' ? 'page' : undefined">Variable Cost</Link>
        </nav>
        <form class="filters" @submit.prevent="apply">
            <label>Cari<input v-model="filter.q" type="search" maxlength="100" placeholder="Kode atau item" /></label>
            <label>Status<select v-model="filter.status"><option value="all">Semua</option><option value="on">Aktif</option><option value="off">Nonaktif</option></select></label>
            <button class="primary">Terapkan</button>
        </form>
        <div class="table-wrap"><table>
            <caption class="sr-only">Daftar {{ title }}</caption>
            <thead><tr><th scope="col">No</th><th scope="col">Kode</th><th scope="col">Item</th><th scope="col">Satuan</th><th scope="col">Default Cost</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
            <tbody>
                <tr v-for="(row, i) in rows.data" :key="row.id">
                    <td>{{ (rows.from ?? 1) + i }}</td><td>{{ row.kode }}</td><td>{{ row.item }}<small v-if="row.keterangan">{{ row.keterangan }}</small></td>
                    <td>{{ row.nama_satuan }}</td><td>{{ rupiah(row.default_cost) }}</td><td><StatusBadge :active="row.aktif" /></td>
                    <td><ActionMenu v-if="can.manage" ><button type="button" @click="open(row)">Edit</button><button type="button" @click="toggle(row)">{{ row.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></ActionMenu></td>
                </tr>
                <tr v-if="!rows.data.length"><td colspan="7" class="empty">Belum ada {{ title }}.</td></tr>
            </tbody>
        </table></div>
        <Pagination :page="rows" :label="`Halaman ${title}`" />

        <ConfirmDialog v-if="adding || editing" :title="editing ? `Edit ${title} ${editing.kode}` : `Tambah ${title}`" :busy="form.processing" @confirm="save" @cancel="close">
            <div class="stack">
                <FormField id="item" label="Item" :error="form.errors.item" v-slot="{ describedBy, invalid }"><input id="item" v-model="form.item" maxlength="150" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="satuan" label="Satuan" :error="form.errors.id_satuan" v-slot="{ describedBy, invalid }">
                    <select id="satuan" v-model="form.id_satuan" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">Pilih…</option><option v-for="s in satuans" :key="s.id" :value="s.id">{{ s.nama }}</option></select>
                </FormField>
                <FormField id="cost" label="Default Cost (Rp)" hint="Angka bulat ≥ 0, tanpa titik." :error="form.errors.default_cost" v-slot="{ describedBy, invalid }">
                    <input id="cost" v-model="form.default_cost" inputmode="numeric" pattern="[0-9]*" required :aria-invalid="invalid" :aria-describedby="describedBy" />
                </FormField>
                <FormField id="coa" label="COA" :error="form.errors.coa" v-slot="{ describedBy, invalid }">
                    <select id="coa" v-model="form.coa" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">Pilih…</option><option v-for="c in coa" :key="c.no_perkiraan" :value="c.no_perkiraan">{{ c.no_perkiraan }} — {{ c.nama }}</option></select>
                </FormField>
                <FormField id="ket" label="Keterangan" :error="form.errors.keterangan" v-slot="{ describedBy, invalid }"><input id="ket" v-model="form.keterangan" maxlength="150" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
            </div>
        </ConfirmDialog>
    </AppLayout>
</template>
