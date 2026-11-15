<script setup lang="ts">
import { useConfirmedMutation } from "../../composables/confirmedMutation";
import ServerTable from "../../components/ServerTable.vue";
import ActionMenu from "../../components/ActionMenu.vue";
import { computed, ref, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';

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
const apply = () => { appliedFilters.value = { ...filter.value }; table.value?.reload(true); };
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
const columns = computed(() => [{ title: 'No', orderable: false }, { title: 'Kode', orderable: true }, { title: 'Nama', orderable: true }, { title: 'Jenis Instansi', orderable: true }, { title: 'Lokasi', orderable: true }, { title: 'Status', orderable: true }, { title: 'Aksi', orderable: false }]);
</script>

<template>
    <Head title="Master Company" />
    <AppLayout title="Master Company" full-width>
        <div class="page-head"><h1>Master Company</h1><button v-if="can.add" type="button" class="primary" @click="open(null)" :disabled="confirming">+ Tambah Company</button></div>
        <p v-if="($page.props.errors as Record<string, string>).hapus" class="error" role="alert">{{ ($page.props.errors as Record<string, string>).hapus }}</p>
        <form class="filters" @submit.prevent="apply">
            <label>Jenis Instansi<select v-model="filter.jenis"><option value="">Semua</option><option v-for="j in jenis" :key="j" :value="j">{{ j }}</option></select></label>
            <button class="primary" :disabled="confirming">Terapkan</button>
        </form>
        <ServerTable ref="table" url="/master/company/data" :columns="columns" :filters="appliedFilters" :label="'Company'">
            <template #cell-0="{ number }">{{ number }}</template>
            <template #cell-1="{ row }">{{ row.id_customer }}</template>
            <template #cell-2="{ row }">{{ row.name_customer }}<small>PIC: {{ row.pic_name ?? '—' }}</small><small v-if="!row.npwp"><span class="badge warn">NPWP kosong</span> Lengkapi lewat Edit</small></template>
            <template #cell-3="{ row }">{{ row.jenis_instansi ?? '—' }}</template>
            <template #cell-4="{ row }">{{ row.kota ?? '—' }}<small>{{ row.provinsi }}</small></template>
            <template #cell-5="{ row }"><StatusBadge :active="row.aktif" /></template>
            <template #cell-6="{ row }"><ActionMenu ><template v-if="can.manage"><button type="button" @click="open(row)" :disabled="confirming">Edit</button><button type="button" @click="toggle(row)" :disabled="confirming">{{ row.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></template><button v-if="can.delete" type="button" class="danger" @click="remove(row)" :disabled="confirming">Hapus</button></ActionMenu></template>
        </ServerTable>

        <ConfirmDialog v-if="adding || editing" :title="editing ? `Edit Company ${editing.id_customer}` : 'Tambah Company'" :busy="form.processing || confirming" @confirm="save" @cancel="close">
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
    </AppLayout>
</template>
