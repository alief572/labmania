<script setup lang="ts">
import ActionMenu from "../../components/ActionMenu.vue";
import { computed, ref } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';
import Pagination from '../../components/Pagination.vue';
import StatusBadge from '../../components/StatusBadge.vue';

type Row = { id: number; kode: string; nama: string; aktif: number };
const props = defineProps<{
    slug: string; label: string; filters: { q: string; status: string };
    rows: { data: Row[]; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null; from: number | null };
    can: { add: boolean; manage: boolean };
}>();
const filter = ref({ ...props.filters });
const presentation = computed(() => ({
    'jenis-produk': { code: 'Kode Jenis Product', name: 'Jenis Product', description: 'Jenis produk yang dijual Labmania, mis. Pelatihan atau Sertifikasi.' },
    'jenis-sertifikat': { code: 'Kode Sertifikat', name: 'Jenis Sertifikat', description: 'Jenis sertifikat yang diterima peserta, mis. Sertifikat BNSP.' },
    'jenis-bidang': { code: 'Kode Bidang', name: 'Jenis Bidang', description: 'Bidang keilmuan/standar yang dibahas di pelatihan. Juga dipakai untuk mengelompokkan peserta.' },
    'varian-pelaksanaan': { code: 'Kode Varian', name: 'Varian', description: 'Cara melaksanakan suatu jenis bidang.' },
} as Record<string, { code: string; name: string; description: string }>)[props.slug]);
const apply = () => router.get(`/master/${props.slug}`, filter.value, { preserveScroll: true });
const editing = ref<Row | null>(null);
const adding = ref(false);
const form = useForm({ nama: '' });
function open(row: Row | null) { form.reset(); form.clearErrors(); editing.value = row; adding.value = !row; if (row) form.nama = row.nama; }
function close() { editing.value = null; adding.value = false; }
function save() {
    const done = { preserveScroll: true, onSuccess: close };
    if (editing.value) form.put(`/master/${props.slug}/${editing.value.id}`, done);
    else form.post(`/master/${props.slug}`, done);
}
const toggle = (row: Row) => router.post(`/master/${props.slug}/${row.id}/toggle`, {}, { preserveScroll: true });
</script>

<template>
    <Head :title="label" />
    <AppLayout :title="label">
        <div class="page-head"><div><h1>{{ label }}</h1><p v-if="presentation" class="muted">{{ presentation.description }}</p></div><button v-if="can.add" type="button" class="primary" @click="open(null)">+ Tambah {{ label }}</button></div>
        <p class="notice">Data master <strong>tidak dapat dihapus</strong> — gunakan <strong>Nonaktifkan</strong>. Data nonaktif tidak muncul sebagai pilihan baru.</p>
        <form class="filters" @submit.prevent="apply">
            <label>Cari<input v-model="filter.q" type="search" maxlength="100" placeholder="Kode atau nama" /></label>
            <label>Status<select v-model="filter.status"><option value="all">Semua</option><option value="on">Aktif</option><option value="off">Nonaktif</option></select></label>
            <button class="primary">Terapkan</button>
        </form>
        <div class="table-wrap"><table>
            <caption class="sr-only">Daftar {{ label }}</caption>
            <thead><tr><th scope="col">No</th><th scope="col">{{ presentation?.code || 'Kode' }}</th><th scope="col">{{ presentation?.name || 'Nama' }}</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
            <tbody>
                <tr v-for="(row, i) in rows.data" :key="row.id">
                    <td>{{ (rows.from ?? 1) + i }}</td><td>{{ row.kode }}</td><td>{{ row.nama }}</td><td><StatusBadge :active="row.aktif" /></td>
                    <td><ActionMenu v-if="can.manage" ><button type="button" @click="open(row)">Edit</button><button type="button" @click="toggle(row)">{{ row.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></ActionMenu></td>
                </tr>
                <tr v-if="!rows.data.length"><td colspan="5" class="empty">Belum ada data.</td></tr>
            </tbody>
        </table></div>
        <Pagination :page="rows" :label="`Halaman ${label}`" />

        <ConfirmDialog v-if="adding || editing" :title="editing ? `Edit ${label} ${editing.kode}` : `Tambah ${label}`" :busy="form.processing" @confirm="save" @cancel="close">
            <label>{{ presentation?.code || 'Kode' }}<input :value="editing?.kode || 'Dibuat otomatis saat disimpan'" readonly /></label>
            <FormField id="nama" :label="presentation?.name || 'Nama'" :error="form.errors.nama" v-slot="{ describedBy, invalid }">
                <input id="nama" v-model="form.nama" maxlength="100" required :aria-invalid="invalid" :aria-describedby="describedBy" @keydown.enter.prevent="save" />
            </FormField>
            <p v-if="adding" class="muted">Kode dibuat otomatis.</p>
        </ConfirmDialog>
    </AppLayout>
</template>
