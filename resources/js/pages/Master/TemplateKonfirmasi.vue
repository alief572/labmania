<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import { useConfirmedMutation } from "../../composables/confirmedMutation";
import ServerTable from "../../components/ServerTable.vue";
import ActionMenu from "../../components/ActionMenu.vue";
import { computed, ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';
import PageHead from '../../components/PageHead.vue';

import StatusBadge from '../../components/StatusBadge.vue';

const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;

type Row = { id: number; kode: string; nama: string; label_kegiatan: string; id_jenis_produk: number | null; nama_produk: string | null; isian: string[]; pernyataan: string; catatan: string | null; aktif: number };
const props = defineProps<{
    fields: Record<string, string>; produk: { id: number; nama: string }[]; can: { add: boolean; manage: boolean };
}>();
// Isian standar tidak ditampilkan sebagai chip di daftar (mockup listKfTpl).
const STANDARD = ['nama', 'nik', 'jabatan', 'instansi', 'alamat', 'email'];
const filter = ref({ q: '', status: 'all' });
const appliedFilters = ref({ q: '', status: 'all' });
watch(() => filter.value.status, () => { appliedFilters.value = { ...filter.value }; table.value?.reload(true); });
const short = (v: string) => (v.length > 110 ? `${v.slice(0, 110)}…` : v);
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
    return confirmation.form(form, editing.value ? 'put' : 'post', editing.value ? `/master/template-konfirmasi/${editing.value.id}` : '/master/template-konfirmasi', { title: `${editing.value ? 'Simpan perubahan' : 'Tambahkan'} ${form.nama}${editing.value ? ` (${editing.value.kode})` : ''}?` }, {
        preserveScroll: true, onSuccess: () => { close(); reload(); },
    });
}
const toggle = (row: Row) => confirmation.visit('post', `/master/template-konfirmasi/${row.id}/toggle`, {}, {
    title: `${row.aktif ? 'Nonaktifkan' : 'Aktifkan'} ${row.nama} (${row.kode})?`,
    confirmLabel: row.aktif ? 'Ya, nonaktifkan' : 'Ya, aktifkan', danger: !!row.aktif,
}, { preserveScroll: true, onSuccess: reload });
async function contoh(row: Row) { sample.value = await (await fetch(appUrl(`/master/template-konfirmasi/${row.id}/contoh`), { headers: { Accept: 'application/json' } })).json(); }

const table = ref<InstanceType<typeof ServerTable> | null>(null);
const reload = () => table.value?.reload();
const columns = computed(() => [{ title: 'No', orderable: false }, { title: 'Kode', orderable: true }, { title: 'Nama Template', orderable: true }, { title: 'Default untuk Jenis Produk', orderable: true }, { title: 'Isian', orderable: false }, { title: 'Pernyataan', orderable: false }, { title: 'Status', orderable: true }, { title: 'Action', orderable: false }]);
</script>

<template>
    <Head title="Template Formulir Konfirmasi" />
    <AppLayout title="Template Formulir Konfirmasi" full-width>
        <PageHead title="Template Formulir Konfirmasi"><button v-if="can.add" type="button" class="primary" @click="open(null)" :disabled="confirming">+ Tambah Template</button></PageHead>
        <p class="notice">Perubahan di sini hanya berlaku untuk quotation <strong>baru</strong>. Tidak dapat dihapus — gunakan Nonaktifkan.</p>
        <div class="filters">
            <label class="grow">Cari<input v-model="filter.q" type="search" maxlength="100" placeholder="Cari kode atau nama template…" /></label>
            <label>Status<select v-model="filter.status"><option value="all">Semua status</option><option value="on">Aktif</option><option value="off">Nonaktif</option></select></label>
        </div>
        <ServerTable ref="table" :url="appUrl('/master/template-konfirmasi/data')" :columns="columns" :filters="appliedFilters" :keyword="filter.q" label="Template Formulir Konfirmasi">
            <template #cell-0="{ number }">{{ number }}</template>
            <template #cell-1="{ row }"><span class="code">{{ row.kode }}</span></template>
            <template #cell-2="{ row }"><b>{{ row.nama }}</b><small>subject: kegiatan {{ row.label_kegiatan }}</small></template>
            <template #cell-3="{ row }"><template v-if="row.nama_produk">{{ row.nama_produk }}</template><span v-else class="muted">— (pilih manual)</span></template>
            <template #cell-4="{ row }"><b>{{ row.isian.length }}</b> <span class="muted">isian</span><br><template v-for="k in row.isian.filter((x: string) => !STANDARD.includes(x))" :key="k"><span class="chip">{{ fields[k] }}</span> </template></template>
            <template #cell-5="{ row }"><span class="muted">{{ short(row.pernyataan) }}</span></template>
            <template #cell-6="{ row }"><StatusBadge :active="row.aktif" /></template>
            <template #cell-7="{ row }"><ActionMenu><button v-if="can.manage" type="button" @click="open(row)" :disabled="confirming">Edit</button><button type="button" @click="contoh(row)" :disabled="confirming">Lihat contoh</button><button v-if="can.manage" type="button" @click="toggle(row)" :disabled="confirming">{{ row.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></ActionMenu></template>
        </ServerTable>

        <ConfirmDialog v-if="adding || editing" :title="editing ? `Edit Template ${editing.kode}` : 'Tambah Template Formulir Konfirmasi'" :busy="form.processing || confirming" @confirm="save" @cancel="close">
            <div class="kf-grid">
                <div>
                    <div class="grid2">
                        <FormField id="kfkode" label="Kode" v-slot="{ describedBy }"><input id="kfkode" :value="editing?.kode ?? ''" placeholder="Dibuat otomatis" readonly aria-readonly="true" :aria-describedby="describedBy" /></FormField>
                        <FormField id="nama" label="Nama template" :error="form.errors.nama" v-slot="{ describedBy, invalid }"><input id="nama" v-model="form.nama" maxlength="80" placeholder="mis. Workshop" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                        <FormField id="lk" label="Label kegiatan" hint="Dipakai di subject email: &quot;kegiatan … dengan judul …&quot;" :error="form.errors.label_kegiatan" v-slot="{ describedBy, invalid }"><input id="lk" v-model="form.label_kegiatan" maxlength="80" placeholder="mis. Training dan Sertifikasi" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                        <FormField id="jp" label="Default untuk Jenis Produk" :error="form.errors.id_jenis_produk" v-slot="{ describedBy, invalid }">
                            <select id="jp" v-model="form.id_jenis_produk" :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">— Tidak ada (pilih manual) —</option><option v-for="p in produk" :key="p.id" :value="p.id">{{ p.nama }}</option></select>
                        </FormField>
                    </div>
                    <p class="qtn-h">Isian formulir</p>
                    <div class="kf-isian"><label v-for="(text, key) in fields" :key="key" class="chk"><input v-model="form.isian" type="checkbox" :value="key" /> {{ text }}</label></div>
                    <p v-if="form.errors.isian" class="error" role="alert">Pilih minimal 1 isian</p>
                </div>
                <div>
                    <p class="qtn-h">Pernyataan 1</p>
                    <FormField id="pn" label="Kalimat pernyataan" hint="Penanda diisi otomatis: {judul} {tanggal} {waktu} {durasi} {lokasi}. **teks** = tebal. Pernyataan 2 (membaca &amp; menyetujui S&amp;K) selalu tercetak." :error="form.errors.pernyataan" v-slot="{ describedBy, invalid }"><textarea id="pn" v-model="form.pernyataan" rows="4" required :aria-invalid="invalid" :aria-describedby="describedBy"></textarea></FormField>
                    <p class="qtn-h">Catatan</p>
                    <FormField id="ct" label="Butir catatan" hint="Satu butir per baris. Penanda: {email} {telp} (sales quotation), {kegiatan}, {judul}." :error="form.errors.catatan" v-slot="{ describedBy, invalid }"><textarea id="ct" v-model="form.catatan" rows="7" :aria-invalid="invalid" :aria-describedby="describedBy"></textarea></FormField>
                </div>
            </div>
        </ConfirmDialog>

        <ConfirmDialog v-if="sample" :title="`Contoh — ${sample.template.nama}`" confirm-label="Tutup" @confirm="sample = null" @cancel="sample = null">
            <p class="muted">Contoh dengan program: {{ sample.program ?? 'belum ada Penjualan Program Final' }}</p>
            <div class="letter">
                <p><b>FORMULIR KONFIRMASI KEIKUTSERTAAN</b><br><span class="muted">{{ sample.template.label_kegiatan }}</span></p>
                <dl class="summary"><div v-for="k in sample.template.isian" :key="k"><dt>{{ sample.fields[k] }}</dt><dd>&nbsp;</dd></div></dl>
                <p class="qtn-h">Pernyataan</p>
                <p>{{ sample.template.pernyataan }}</p>
                <template v-if="sample.template.catatan"><p class="qtn-h">Catatan</p><ol><li v-for="(c, i) in sample.template.catatan.split(/\r?\n/).filter(Boolean)" :key="i">{{ c }}</li></ol></template>
            </div>
        </ConfirmDialog>
    </AppLayout>
</template>
