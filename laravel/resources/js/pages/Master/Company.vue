<script setup lang="ts">
import ActionMenu from "../../components/ActionMenu.vue";
import { ref, watch } from 'vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';
import Pagination from '../../components/Pagination.vue';
import StatusBadge from '../../components/StatusBadge.vue';
import { useWilayah } from '../../composables/wilayah';

type Row = { id_customer: string; name_customer: string; jenis_instansi: string | null; address_office: string | null; id_prov: string | null; provinsi: string | null; id_city: string | null; kota: string | null; telephone: string | null; email: string | null; pic_name: string | null; pic_jabatan: string | null; pic_hp: string | null; pic_email: string | null; npwp: string | null; perlakuan_ppn: string | null; aktif: number };
const props = defineProps<{
    filters: { q: string; jenis: string }; jenis: string[];
    rows: { data: Row[]; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null };
    can: { add: boolean; manage: boolean; delete: boolean };
}>();
const filter = ref({ ...props.filters });
const apply = () => router.get('/master/company', Object.fromEntries(Object.entries(filter.value).filter(([, v]) => v !== '')), { preserveScroll: true });
const wil = useWilayah();
const editing = ref<Row | null>(null);
const adding = ref(false);
const form = useForm({ nama: '', jenis: '', alamat: '', id_prov: '', provinsi: '', id_kab: '', kabupaten: '', telp: '', email: '', picNama: '', picJabatan: '', picTelp: '', picEmail: '', npwp: '', perlakuan_ppn: 'PKP' });
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
        Object.assign(form, { nama: row.name_customer, jenis: row.jenis_instansi ?? '', alamat: row.address_office ?? '', id_prov: row.id_prov ?? '', provinsi: row.provinsi ?? '', telp: row.telephone ?? '', email: row.email ?? '', picNama: row.pic_name ?? '', picJabatan: row.pic_jabatan ?? '', picTelp: row.pic_hp ?? '', picEmail: row.pic_email ?? '', npwp: row.npwp ?? '', perlakuan_ppn: row.perlakuan_ppn ?? '' });
        await wil.loadRegencies(row.id_prov ?? '');
        Object.assign(form, { id_kab: row.id_city ?? '', kabupaten: row.kota ?? '' });
    }
}
const close = () => { editing.value = null; adding.value = false; };
function save() {
    const done = { preserveScroll: true, onSuccess: close };
    if (editing.value) form.put(`/master/company/${editing.value.id_customer}`, done);
    else form.post('/master/company', done);
}
const toggle = (row: Row) => router.post(`/master/company/${row.id_customer}/toggle`, {}, { preserveScroll: true });
const removing = ref<Row | null>(null);
const remove = () => router.delete(`/master/company/${removing.value!.id_customer}`, { preserveScroll: true, onFinish: () => { removing.value = null; } });
</script>

<template>
    <Head title="Master Company" />
    <AppLayout title="Master Company">
        <div class="page-head"><h1>Master Company</h1><button v-if="can.add" type="button" class="primary" @click="open(null)">+ Tambah Company</button></div>
        <p v-if="($page.props.errors as Record<string, string>).hapus" class="error" role="alert">{{ ($page.props.errors as Record<string, string>).hapus }}</p>
        <form class="filters" @submit.prevent="apply">
            <label>Cari<input v-model="filter.q" type="search" maxlength="100" placeholder="Kode, nama, kota, PIC" /></label>
            <label>Jenis Instansi<select v-model="filter.jenis"><option value="">Semua</option><option v-for="j in jenis" :key="j" :value="j">{{ j }}</option></select></label>
            <button class="primary">Terapkan</button>
        </form>
        <div class="table-wrap"><table>
            <caption class="sr-only">Daftar Company</caption>
            <thead><tr><th scope="col">No</th><th scope="col">Kode</th><th scope="col">Nama</th><th scope="col">Jenis Instansi</th><th scope="col">Lokasi</th><th scope="col">Status</th><th scope="col">Aksi</th></tr></thead>
            <tbody>
                <tr v-for="(row, i) in rows.data" :key="row.id_customer">
                    <td>{{ i + 1 }}</td><td>{{ row.id_customer }}</td>
                    <td>{{ row.name_customer }}<small>PIC: {{ row.pic_name ?? '—' }}</small><small v-if="!row.npwp"><span class="badge warn">NPWP kosong</span> Lengkapi lewat Edit</small></td>
                    <td>{{ row.jenis_instansi ?? '—' }}</td><td>{{ row.kota ?? '—' }}<small>{{ row.provinsi }}</small></td><td><StatusBadge :active="row.aktif" /></td>
                    <td><ActionMenu ><template v-if="can.manage"><button type="button" @click="open(row)">Edit</button><button type="button" @click="toggle(row)">{{ row.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></template><button v-if="can.delete" type="button" class="danger" @click="removing = row">Hapus</button></ActionMenu></td>
                </tr>
                <tr v-if="!rows.data.length"><td colspan="7" class="empty">Belum ada Company.</td></tr>
            </tbody>
        </table></div>
        <Pagination :page="rows" label="Halaman Company" />

        <ConfirmDialog v-if="adding || editing" :title="editing ? `Edit Company ${editing.id_customer}` : 'Tambah Company'" :busy="form.processing" @confirm="save" @cancel="close">
            <div class="form-grid">
                <FormField id="cnama" label="Nama Company" :error="form.errors.nama" v-slot="{ describedBy, invalid }"><input id="cnama" v-model="form.nama" maxlength="150" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="cjen" label="Jenis Instansi" :error="form.errors.jenis" v-slot="{ describedBy, invalid }"><select id="cjen" v-model="form.jenis" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">Pilih…</option><option v-for="j in jenis" :key="j" :value="j">{{ j }}</option></select></FormField>
                <FormField id="cppn" label="Perlakuan PPN" :error="form.errors.perlakuan_ppn" v-slot="{ describedBy, invalid }"><select id="cppn" v-model="form.perlakuan_ppn" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">Pilih perlakuan PPN</option><option value="PKP">PKP</option><option value="WAPU">WAPU</option><option value="KB">Kawasan Berikat</option></select></FormField>
                <FormField id="cnpwp" label="NPWP Instansi" hint="Wajib; 15 atau 16 digit." :error="form.errors.npwp" v-slot="{ describedBy, invalid }"><input id="cnpwp" v-model="form.npwp" maxlength="40" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="calm" label="Alamat Lengkap" :error="form.errors.alamat" v-slot="{ describedBy, invalid }"><input id="calm" v-model="form.alamat" maxlength="255" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="cprov" label="Provinsi" :error="form.errors.id_prov" v-slot="{ describedBy, invalid }"><select id="cprov" v-model="form.id_prov" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">Pilih…</option><option v-for="w in wil.provinces.value" :key="w.code" :value="w.code">{{ w.name }}</option></select></FormField>
                <FormField id="ckab" label="Kota/Kabupaten" :error="form.errors.id_kab" v-slot="{ describedBy, invalid }"><select id="ckab" v-model="form.id_kab" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">Pilih…</option><option v-for="w in wil.regencies.value" :key="w.code" :value="w.code">{{ w.name }}</option></select></FormField>
                <FormField id="ctel" label="Telepon Kantor" :error="form.errors.telp" v-slot="{ describedBy, invalid }"><input id="ctel" v-model="form.telp" inputmode="tel" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="cmail" label="Email Kantor" :error="form.errors.email" v-slot="{ describedBy, invalid }"><input id="cmail" v-model="form.email" type="email" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="cpn" label="Nama PIC" :error="form.errors.picNama" v-slot="{ describedBy, invalid }"><input id="cpn" v-model="form.picNama" maxlength="100" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="cpj" label="Jabatan PIC" :error="form.errors.picJabatan" v-slot="{ describedBy, invalid }"><input id="cpj" v-model="form.picJabatan" maxlength="100" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="cpt" label="Telp/WA PIC" :error="form.errors.picTelp" v-slot="{ describedBy, invalid }"><input id="cpt" v-model="form.picTelp" inputmode="tel" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="cpe" label="Email PIC" :error="form.errors.picEmail" v-slot="{ describedBy, invalid }"><input id="cpe" v-model="form.picEmail" type="email" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
            </div>
        </ConfirmDialog>

        <ConfirmDialog v-if="removing" :title="`Hapus ${removing.id_customer}?`" confirm-label="Hapus" danger @confirm="remove" @cancel="removing = null"><p>Company {{ removing.name_customer }} dihapus dari daftar. Company yang sudah dipakai tidak dapat dihapus.</p></ConfirmDialog>
    </AppLayout>
</template>
