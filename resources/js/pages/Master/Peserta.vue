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

import { useWilayah } from '../../composables/wilayah';

const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;

type Row = { id: number; kode: string; nama: string; gender: string | null; instansi: string | null; id_jenis_bidang: number | null; nama_bidang: string | null; id_prov: string | null; provinsi: string | null; id_kab: string | null; kabupaten: string | null; no_telp: string | null; email: string | null; nik: string | null; npwp_instansi: string | null; id_customer: string | null; asal_deal: string | null; kurang: string[] };
const props = defineProps<{
    filters: { q: string; kelengkapan: string };
    bidang: { id: number; nama: string }[]; companies: { id_customer: string; name_customer: string; npwp: string | null }[];
    can: { add: boolean; manage: boolean; delete: boolean };
}>();
const filter = ref({ ...props.filters });
watch(() => filter.value.kelengkapan, () => { appliedFilters.value = { ...filter.value }; table.value?.reload(true); });
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
    return confirmation.form(form, editing.value ? 'put' : 'post', editing.value ? `/master/peserta/${editing.value.id}` : '/master/peserta', { title: `${editing.value ? 'Simpan perubahan' : 'Tambahkan'} ${form.nama}${editing.value ? ` (${editing.value.kode})` : ''}?` }, {
        preserveScroll: true, onSuccess: () => { close(); reload(); },
    });
}
const remove = (row: Row) => confirmation.visit('delete', `/master/peserta/${row.id}`, {}, {
    title: `Hapus ${row.nama} (${row.kode})?`, text: 'Peserta dihapus dari daftar. Peserta yang sudah Deal tidak dapat dihapus.', confirmLabel: 'Ya, hapus', danger: true,
}, { preserveScroll: true, onSuccess: reload });
const err = (k: string) => (form.errors as Record<string, string>)[k];

const table = ref<InstanceType<typeof ServerTable> | null>(null);
const reload = () => table.value?.reload();
const appliedFilters = ref({ ...props.filters });
const columns = computed(() => [{ title: 'No', orderable: false }, { title: 'Kode Peserta', orderable: true }, { title: 'Nama Peserta', orderable: true }, { title: 'Bidang', orderable: true }, { title: 'Lokasi', orderable: true }, { title: 'Action', orderable: false }]);
</script>

<template>
    <Head title="Master Peserta" />
    <AppLayout title="Master Peserta" full-width>
        <PageHead title="Master Peserta"><button v-if="can.add" type="button" class="primary" @click="open(null)" :disabled="confirming">+ Tambah Peserta</button></PageHead>
        <p v-if="($page.props.errors as Record<string, string>).hapus" class="error" role="alert">{{ ($page.props.errors as Record<string, string>).hapus }}</p>
        <div class="filters">
            <label class="grow">Cari<input v-model="filter.q" type="search" maxlength="100" placeholder="Cari kode, nama, instansi, NIK, atau bidang…" /></label>
            <label>Kelengkapan<select v-model="filter.kelengkapan"><option value="all">Semua kelengkapan</option><option value="lengkap">Lengkap</option><option value="belum">Belum lengkap</option></select></label>
        </div>
        <ServerTable ref="table" :url="appUrl('/master/peserta/data')" :columns="columns" :filters="appliedFilters" :keyword="filter.q" :label="'Peserta'">
            <template #cell-0="{ number }">{{ number }}</template>
            <template #cell-1="{ row }"><span class="code">{{ row.kode }}</span></template>
            <template #cell-2="{ row }">{{ row.nama }}<span v-if="row.kurang.length" class="badge warn" :title="`Belum diisi: ${row.kurang.join(', ')}`">Belum lengkap</span><small>{{ row.instansi || '—' }}</small><small v-if="row.asal_deal">Dari Deal {{ row.asal_deal }}</small></template>
            <template #cell-3="{ row }"><template v-if="row.nama_bidang">{{ row.nama_bidang }}</template><span v-else class="muted">Belum diisi</span></template>
            <template #cell-4="{ row }"><template v-if="row.provinsi">{{ row.kabupaten || '—' }}<small>{{ row.provinsi }}</small></template><span v-else class="muted">Belum diisi</span></template>
            <template #cell-5="{ row }"><ActionMenu ><button v-if="can.manage" type="button" @click="open(row)" :disabled="confirming">Edit</button><button v-if="can.delete" type="button" class="danger" @click="remove(row)" :disabled="confirming">Delete</button></ActionMenu></template>
        </ServerTable>

        <ConfirmDialog v-if="adding || editing" :title="editing ? `Edit Peserta ${editing.kode}` : 'Tambah Master Peserta'" :busy="form.processing || confirming" @confirm="save" @cancel="close">
            <p class="hint-line top">Semua field wajib diisi, kecuali Company, NPWP Instansi, dan NIK.</p>
            <p v-if="editing?.kurang?.length" class="notice">Peserta ini masuk dari Deal {{ editing.asal_deal || 'quotation' }} dan <strong>belum lengkap</strong>. Lengkapi: {{ editing.kurang.join(', ') }}.</p>
            <div class="grid2">
                <FormField class="span2" id="pkode" label="Kode Peserta" v-slot="{ describedBy }"><input id="pkode" :value="editing?.kode ?? ''" placeholder="Dibuat otomatis" readonly aria-readonly="true" :aria-describedby="describedBy" /></FormField>
                <FormField id="pnama" label="Nama Peserta" :error="form.errors.nama" v-slot="{ describedBy, invalid }"><input id="pnama" v-model="form.nama" maxlength="100" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="pgender" label="Gender" :error="form.errors.gender" v-slot="{ describedBy, invalid }"><select id="pgender" v-model="form.gender" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">— Pilih —</option><option value="L">Laki-laki</option><option value="P">Perempuan</option></select></FormField>
                <FormField id="pco" label="Company" hint="Dari Master Company; dipakai Quotation ke Company" :error="form.errors.id_customer" v-slot="{ describedBy, invalid }"><select id="pco" v-model="form.id_customer" :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">— Tanpa company (personal) —</option><option v-for="c in companies" :key="c.id_customer" :value="c.id_customer">{{ c.name_customer }}</option></select></FormField>
                <FormField id="pins" label="Instansi/Perusahaan" hint="Terisi nama Company bila Company dipilih" :error="form.errors.instansi" v-slot="{ describedBy, invalid }"><input id="pins" v-model="form.instansi" maxlength="150" required :readonly="!!form.id_customer" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField class="span2" id="pnpwp" label="NPWP Instansi (opsional)" :error="form.errors.npwp_instansi" v-slot="{ describedBy, invalid }"><input id="pnpwp" v-model="form.npwp_instansi" maxlength="30" :readonly="!!form.id_customer" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="pbid" label="Bidang" :error="err('bidang')" v-slot="{ describedBy, invalid }"><select id="pbid" v-model="form.bidang" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">— Pilih Jenis Bidang —</option><option v-for="b in bidang" :key="b.id" :value="b.id">{{ b.nama }}</option></select></FormField>
                <FormField id="pprov" label="Lokasi (Provinsi)" :error="form.errors.id_prov" v-slot="{ describedBy, invalid }"><select id="pprov" v-model="form.id_prov" required :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">— Pilih Provinsi —</option><option v-for="w in wil.provinces.value" :key="w.code" :value="w.code">{{ w.name }}</option></select></FormField>
                <FormField id="pkab" label="Kota/Kabupaten" hint="Tersaring menurut provinsi" :error="form.errors.id_kab" v-slot="{ describedBy, invalid }"><select id="pkab" v-model="form.id_kab" required :disabled="!form.id_prov" :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">— Pilih Kota/Kabupaten —</option><option v-for="w in wil.regencies.value" :key="w.code" :value="w.code">{{ w.name }}</option></select></FormField>
                <FormField id="ptel" label="No Telp/WhatsApp" :error="form.errors.no_telp" v-slot="{ describedBy, invalid }"><input id="ptel" v-model="form.no_telp" inputmode="tel" placeholder="08xxxxxxxxxx" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="pmail" label="Email" :error="form.errors.email" v-slot="{ describedBy, invalid }"><input id="pmail" v-model="form.email" type="email" placeholder="nama@domain.com" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="pnik" label="NIK (opsional)" hint="Terisi dari isian link bila peserta mengisinya" :error="form.errors.nik" v-slot="{ describedBy, invalid }"><input id="pnik" v-model="form.nik" maxlength="20" inputmode="numeric" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
            </div>
        </ConfirmDialog>
    </AppLayout>
</template>
