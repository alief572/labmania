<script setup lang="ts">
import ActionMenu from "../../components/ActionMenu.vue";
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';
import Pagination from '../../components/Pagination.vue';
import { useWilayah } from '../../composables/wilayah';

type Row = { id: number; kode: string; nama: string; gender: string | null; instansi: string | null; id_jenis_bidang: number | null; nama_bidang: string | null; id_prov: string | null; provinsi: string | null; id_kab: string | null; kabupaten: string | null; no_telp: string | null; email: string | null; nik: string | null; npwp_instansi: string | null; id_customer: string | null; asal_deal: string | null; kurang: string[] };
const props = defineProps<{
    filters: { q: string; kelengkapan: string };
    rows: { data: Row[]; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null; from: number | null };
    bidang: { id: number; nama: string }[]; companies: { id_customer: string; name_customer: string; npwp: string | null }[];
    can: { add: boolean; manage: boolean; delete: boolean };
}>();
const filter = ref({ ...props.filters });
const apply = () => router.get('/master/peserta', Object.fromEntries(Object.entries(filter.value).filter(([, v]) => v !== '')), { preserveScroll: true });
const wil = useWilayah();
const editing = ref<Row | null>(null);
const adding = ref(false);
const form = useForm({ nama: '', gender: '', instansi: '', bidang: '' as string | number, id_prov: '', provinsi: '', id_kab: '', kabupaten: '', no_telp: '', email: '', nik: '', npwp_instansi: '', id_customer: '' });
watch(() => form.id_customer, () => {
    const c = props.companies.find((item) => item.id_customer === form.id_customer);
    if (c) { form.instansi = c.name_customer; form.npwp_instansi = c.npwp ?? ''; }
});
watch(() => form.id_prov, async (code, old) => {
    form.provinsi = wil.nameOf(wil.provinces.value, code);
    if (old !== undefined && old !== code) { form.id_kab = ''; form.kabupaten = ''; }
    await wil.loadRegencies(code);
});
watch(() => form.id_kab, (code) => { form.kabupaten = wil.nameOf(wil.regencies.value, code); });
async function open(row: Row | null) {
    form.reset(); form.clearErrors(); editing.value = row; adding.value = !row;
    await wil.loadProvinces();
    if (row) {
        Object.assign(form, { nama: row.nama, gender: row.gender ?? '', instansi: row.instansi ?? '', bidang: row.id_jenis_bidang ?? '', id_prov: row.id_prov ?? '', provinsi: row.provinsi ?? '', no_telp: row.no_telp ?? '', email: row.email ?? '', nik: row.nik ?? '', npwp_instansi: row.npwp_instansi ?? '', id_customer: row.id_customer ?? '' });
        await wil.loadRegencies(row.id_prov ?? '');
        Object.assign(form, { id_kab: row.id_kab ?? '', kabupaten: row.kabupaten ?? '' });
    }
}
const close = () => { editing.value = null; adding.value = false; };
function save() {
    const done = { preserveScroll: true, onSuccess: close };
    if (editing.value) form.put(`/master/peserta/${editing.value.id}`, done);
    else form.post('/master/peserta', done);
}
const removing = ref<Row | null>(null);
const remove = () => router.delete(`/master/peserta/${removing.value!.id}`, { preserveScroll: true, onFinish: () => { removing.value = null; } });
const err = (k: string) => (form.errors as Record<string, string>)[k];
</script>

<template>
    <Head title="Master Peserta" />
    <AppLayout title="Master Peserta">
        <div class="page-head"><h1>Master Peserta</h1><button v-if="can.add" type="button" class="primary" @click="open(null)">+ Tambah Peserta</button></div>
        <p v-if="($page.props.errors as Record<string, string>).hapus" class="error" role="alert">{{ ($page.props.errors as Record<string, string>).hapus }}</p>
        <form class="filters" @submit.prevent="apply">
            <label>Cari<input v-model="filter.q" type="search" maxlength="100" placeholder="Kode, nama, instansi, NIK" /></label>
            <label>Kelengkapan<select v-model="filter.kelengkapan"><option value="all">Semua</option><option value="lengkap">Lengkap</option><option value="belum">Belum lengkap</option></select></label>
            <button class="primary">Terapkan</button>
        </form>
        <div class="table-wrap"><table>
            <caption class="sr-only">Daftar peserta</caption>
            <thead><tr><th scope="col">No</th><th scope="col">Kode</th><th scope="col">Nama</th><th scope="col">Bidang</th><th scope="col">Lokasi</th><th scope="col">Aksi</th></tr></thead>
            <tbody>
                <tr v-for="(row, i) in rows.data" :key="row.id">
                    <td>{{ (rows.from ?? 1) + i }}</td><td>{{ row.kode }}</td>
                    <td>{{ row.nama }}<small v-if="row.kurang.length"><span class="badge warn">Belum lengkap</span> {{ row.kurang.join(', ') }}</small><small v-if="row.asal_deal">Deal: {{ row.asal_deal }}</small></td>
                    <td>{{ row.nama_bidang ?? '—' }}</td><td>{{ row.kabupaten ?? '—' }}<small>{{ row.provinsi }}</small></td>
                    <td><ActionMenu ><button v-if="can.manage" type="button" @click="open(row)">Edit</button><button v-if="can.delete" type="button" class="danger" @click="removing = row">Hapus</button></ActionMenu></td>
                </tr>
                <tr v-if="!rows.data.length"><td colspan="6" class="empty">Belum ada peserta.</td></tr>
            </tbody>
        </table></div>
        <Pagination :page="rows" label="Halaman peserta" />

        <ConfirmDialog v-if="adding || editing" :title="editing ? `Edit Peserta ${editing.kode}` : 'Tambah Peserta'" :busy="form.processing" @confirm="save" @cancel="close">
            <div class="form-grid">
                <FormField id="pnama" label="Nama Peserta" :error="form.errors.nama" v-slot="{ describedBy, invalid }"><input id="pnama" v-model="form.nama" maxlength="100" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="pgender" label="Gender" :error="form.errors.gender" v-slot="{ describedBy, invalid }"><select id="pgender" v-model="form.gender" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">Pilih…</option><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></FormField>
                <FormField id="pco" label="Company (opsional)" :error="form.errors.id_customer" v-slot="{ describedBy, invalid }"><select id="pco" v-model="form.id_customer" :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">Tanpa Company</option><option v-for="c in companies" :key="c.id_customer" :value="c.id_customer">{{ c.name_customer }}</option></select></FormField>
                <FormField id="pins" label="Instansi/Perusahaan" :error="form.errors.instansi" v-slot="{ describedBy, invalid }"><input id="pins" v-model="form.instansi" maxlength="150" :readonly="!!form.id_customer" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="pbid" label="Bidang" :error="err('bidang')" v-slot="{ describedBy, invalid }"><select id="pbid" v-model="form.bidang" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">Pilih…</option><option v-for="b in bidang" :key="b.id" :value="b.id">{{ b.nama }}</option></select></FormField>
                <FormField id="pprov" label="Provinsi" :error="form.errors.id_prov" v-slot="{ describedBy, invalid }"><select id="pprov" v-model="form.id_prov" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">Pilih…</option><option v-for="w in wil.provinces.value" :key="w.code" :value="w.code">{{ w.name }}</option></select></FormField>
                <FormField id="pkab" label="Kota/Kabupaten" :error="form.errors.id_kab" v-slot="{ describedBy, invalid }"><select id="pkab" v-model="form.id_kab" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">Pilih…</option><option v-for="w in wil.regencies.value" :key="w.code" :value="w.code">{{ w.name }}</option></select></FormField>
                <FormField id="ptel" label="No Telp/WhatsApp" hint="9–15 digit." :error="form.errors.no_telp" v-slot="{ describedBy, invalid }"><input id="ptel" v-model="form.no_telp" inputmode="tel" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="pmail" label="Email" :error="form.errors.email" v-slot="{ describedBy, invalid }"><input id="pmail" v-model="form.email" type="email" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="pnik" label="NIK (opsional)" :error="form.errors.nik" v-slot="{ describedBy, invalid }"><input id="pnik" v-model="form.nik" maxlength="20" inputmode="numeric" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="pnpwp" label="NPWP Instansi (opsional)" :error="form.errors.npwp_instansi" v-slot="{ describedBy, invalid }"><input id="pnpwp" v-model="form.npwp_instansi" maxlength="30" :readonly="!!form.id_customer" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
            </div>
        </ConfirmDialog>

        <ConfirmDialog v-if="removing" :title="`Hapus ${removing.kode}?`" confirm-label="Hapus" danger @confirm="remove" @cancel="removing = null"><p>Peserta {{ removing.nama }} dihapus dari daftar. Peserta yang sudah Deal tidak dapat dihapus.</p></ConfirmDialog>
    </AppLayout>
</template>
