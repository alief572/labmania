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
import { useWilayah } from '../../composables/wilayah';

const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;

type Row = { id_customer: string; name_customer: string; jenis_instansi: string | null; address_office: string | null; id_prov: string | null; provinsi: string | null; id_city: string | null; kota: string | null; telephone: string | null; email: string | null; pic_name: string | null; pic_jabatan: string | null; pic_hp: string | null; pic_email: string | null; npwp: string | null; perlakuan_ppn: string | null; aktif: number };
const props = defineProps<{
    filters: { q: string; jenis: string }; jenis: string[];
    can: { add: boolean; manage: boolean; delete: boolean };
}>();
const filter = ref({ ...props.filters });
watch(() => filter.value.jenis, () => { appliedFilters.value = { ...filter.value }; table.value?.reload(true); });
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
    return confirmation.form(form, editing.value ? 'put' : 'post', editing.value ? `/master/company/${editing.value.id_customer}` : '/master/company', { title: `${editing.value ? 'Simpan perubahan' : 'Tambahkan'} ${form.nama}${editing.value ? ` (${editing.value.id_customer})` : ''}?` }, {
        preserveScroll: true, onSuccess: () => { close(); reload(); },
    });
}
const toggle = (row: Row) => confirmation.visit('post', `/master/company/${row.id_customer}/toggle`, {}, {
    title: `${row.aktif ? 'Nonaktifkan' : 'Aktifkan'} ${row.name_customer} (${row.id_customer})?`,
    confirmLabel: row.aktif ? 'Ya, nonaktifkan' : 'Ya, aktifkan', danger: !!row.aktif,
}, { preserveScroll: true, onSuccess: reload });
const remove = (row: Row) => confirmation.visit('delete', `/master/company/${row.id_customer}`, {}, {
    title: `Hapus ${row.name_customer} (${row.id_customer})?`, text: 'Company dihapus dari daftar. Company yang sudah dipakai tidak dapat dihapus.', confirmLabel: 'Ya, hapus', danger: true,
}, { preserveScroll: true, onSuccess: reload });

const table = ref<InstanceType<typeof ServerTable> | null>(null);
const reload = () => table.value?.reload();
const appliedFilters = ref({ ...props.filters });
const columns = computed(() => [{ title: 'No', orderable: false }, { title: 'Kode Company', orderable: true }, { title: 'Nama Company', orderable: true }, { title: 'Jenis Instansi', orderable: true }, { title: 'Lokasi', orderable: true }, { title: 'Status', orderable: true }, { title: 'Action', orderable: false }]);
</script>

<template>
    <Head title="Master Company" />
    <AppLayout title="Master Company" full-width>
        <PageHead title="Master Company" description="Data perusahaan/instansi yang menjadi customer penjualan B2B. Company aktif dipilih di Penjualan Program B2B (wajib) dan sebagai tujuan Quotation ke Company."><button v-if="can.add" type="button" class="primary" @click="open(null)" :disabled="confirming">+ Tambah Company</button></PageHead>
        <p v-if="($page.props.errors as Record<string, string>).hapus" class="error" role="alert">{{ ($page.props.errors as Record<string, string>).hapus }}</p>
        <div class="filters">
            <label class="grow">Cari<input v-model="filter.q" type="search" maxlength="100" placeholder="Cari kode, nama, kota, atau PIC…" /></label>
            <label>Jenis Instansi<select v-model="filter.jenis"><option value="">Semua jenis instansi</option><option v-for="j in jenis" :key="j" :value="j">{{ j }}</option></select></label>
        </div>
        <ServerTable ref="table" :url="appUrl('/master/company/data')" :columns="columns" :filters="appliedFilters" :keyword="filter.q" :label="'Company'">
            <template #cell-0="{ number }">{{ number }}</template>
            <template #cell-1="{ row }"><span class="code">{{ row.id_customer }}</span></template>
            <template #cell-2="{ row }">{{ row.name_customer }}<small>PIC: {{ row.pic_name ?? '—' }}</small><small v-if="!row.npwp"><span class="badge warn">NPWP kosong</span> Lengkapi lewat Edit</small></template>
            <template #cell-3="{ row }">{{ row.jenis_instansi ?? '—' }}<small>PPN: {{ row.perlakuan_ppn === 'KB' ? 'Kawasan Berikat' : (row.perlakuan_ppn || 'PKP') }}</small></template>
            <template #cell-4="{ row }">{{ row.kota ?? '—' }}<small>{{ row.provinsi }}</small></template>
            <template #cell-5="{ row }"><StatusBadge :active="row.aktif" /></template>
            <template #cell-6="{ row }"><ActionMenu ><template v-if="can.manage"><button type="button" @click="open(row)" :disabled="confirming">Edit</button><button type="button" @click="toggle(row)" :disabled="confirming">{{ row.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></template><button v-if="can.delete" type="button" class="danger" @click="remove(row)" :disabled="confirming">Delete</button></ActionMenu></template>
        </ServerTable>

        <ConfirmDialog v-if="adding || editing" :title="editing ? `Edit Company ${editing.id_customer}` : 'Tambah Master Company'" :busy="form.processing || confirming" @confirm="save" @cancel="close">
            <div class="grid2">
                <FormField class="span2" id="ckode" label="Kode Company" v-slot="{ describedBy }"><input id="ckode" :value="editing?.id_customer ?? ''" placeholder="Dibuat otomatis" readonly aria-readonly="true" :aria-describedby="describedBy" /></FormField>
                <div class="form-sec span2">1 · Identitas</div>
                <FormField id="cnama" label="Nama Company" :error="form.errors.nama" v-slot="{ describedBy, invalid }"><input id="cnama" v-model="form.nama" maxlength="150" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="cjen" label="Jenis Instansi" :error="form.errors.jenis" v-slot="{ describedBy, invalid }"><select id="cjen" v-model="form.jenis" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">— Pilih —</option><option v-for="j in jenis" :key="j" :value="j">{{ j }}</option></select></FormField>
                <div class="form-sec span2">2 · Alamat</div>
                <FormField class="span2" id="calm" label="Alamat Lengkap" :error="form.errors.alamat" v-slot="{ describedBy, invalid }"><textarea id="calm" v-model="form.alamat" rows="2" maxlength="255" required :aria-invalid="invalid" :aria-describedby="describedBy"></textarea></FormField>
                <FormField id="cprov" label="Provinsi" :error="form.errors.id_prov" v-slot="{ describedBy, invalid }"><select id="cprov" v-model="form.id_prov" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">— Pilih Provinsi —</option><option v-for="w in wil.provinces.value" :key="w.code" :value="w.code">{{ w.name }}</option></select></FormField>
                <FormField id="ckab" label="Kota/Kabupaten" hint="Tersaring menurut provinsi" :error="form.errors.id_kab" v-slot="{ describedBy, invalid }"><select id="ckab" v-model="form.id_kab" required :disabled="!form.id_prov" :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">— Pilih Kota/Kabupaten —</option><option v-for="w in wil.regencies.value" :key="w.code" :value="w.code">{{ w.name }}</option></select></FormField>
                <div class="form-sec span2">3 · Kontak Kantor</div>
                <FormField id="ctel" label="Telepon Kantor" :error="form.errors.telp" v-slot="{ describedBy, invalid }"><input id="ctel" v-model="form.telp" inputmode="tel" placeholder="021xxxxxxx" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="cmail" label="Email Kantor" :error="form.errors.email" v-slot="{ describedBy, invalid }"><input id="cmail" v-model="form.email" type="email" placeholder="info@perusahaan.co.id" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <div class="form-sec span2">4 · PIC</div>
                <FormField id="cpn" label="Nama PIC" :error="form.errors.picNama" v-slot="{ describedBy, invalid }"><input id="cpn" v-model="form.picNama" maxlength="100" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="cpj" label="Jabatan PIC" :error="form.errors.picJabatan" v-slot="{ describedBy, invalid }"><input id="cpj" v-model="form.picJabatan" maxlength="100" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="cpt" label="Telp/WA PIC" :error="form.errors.picTelp" v-slot="{ describedBy, invalid }"><input id="cpt" v-model="form.picTelp" inputmode="tel" placeholder="08xxxxxxxxxx" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="cpe" label="Email PIC" :error="form.errors.picEmail" v-slot="{ describedBy, invalid }"><input id="cpe" v-model="form.picEmail" type="email" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <div class="form-sec span2">5 · Pajak</div>
                <FormField id="cnpwp" label="NPWP Company" :error="form.errors.npwp" v-slot="{ describedBy, invalid }"><input id="cnpwp" v-model="form.npwp" maxlength="40" inputmode="numeric" placeholder="15 atau 16 digit" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="cppn" label="Perlakuan PPN" hint="Dipakai invoice untuk perhitungan dan jurnal. WAPU biasanya BUMN; Kawasan Berikat tidak bayar PPN." :error="form.errors.perlakuan_ppn" v-slot="{ describedBy, invalid }"><select id="cppn" v-model="form.perlakuan_ppn" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">— Pilih perlakuan PPN —</option><option value="PKP">PKP</option><option value="WAPU">WAPU (PPN disetor customer)</option><option value="KB">Kawasan Berikat</option></select></FormField>
            </div>
        </ConfirmDialog>
    </AppLayout>
</template>
