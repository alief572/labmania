<script setup lang="ts">
import { useConfirmedMutation } from "../../composables/confirmedMutation";
import ServerTable from "../../components/ServerTable.vue";
import ActionMenu from "../../components/ActionMenu.vue";
import { computed, ref } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import FormField from '../../components/FormField.vue';
import PageHead from '../../components/PageHead.vue';

import StatusBadge from '../../components/StatusBadge.vue';

const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;

type Row = { id: number; kode: string; nama: string; no_telp: string | null; bidang_keahlian: string | null; aktif: number; id_user: number | null; n_pelatihan: number; n_kerja: number; n_mengajar: number; n_lampiran: number; n_penjualan: number };
type Detail = { trainer: Record<string, string | number | null>; pelatihan: Record<string, string>[]; pengalaman_kerja: Record<string, string>[]; pengalaman_mengajar: Record<string, string>[]; lampiran: { id: number; dokumen: string; tahun: string; ada_file: boolean }[] };
type Lampiran = { dokumen: string; tahun: string; file: File | null; file_id: number | null; ada_file: boolean };
const props = defineProps<{
    filters: { q: string; status: string };
    users: { id_user: number; nm_lengkap: string; username: string }[]; can: { add: boolean; manage: boolean };
}>();
const filter = ref({ ...props.filters });
const apply = () => { appliedFilters.value = { ...filter.value }; table.value?.reload(true); };
const tabs = [['identitas', 'Identitas'], ['pendidikan', 'Pendidikan & Keahlian'], ['riwayat', 'Riwayat'], ['lampiran', 'Lampiran']] as const;
const tab = ref<(typeof tabs)[number][0]>('identitas');
const mode = ref<'add' | 'edit' | null>(null);
const currentId = ref<number | null>(null);
const currentCode = ref('');
const blank = () => ({ nama: '', tempat_lahir: '', tgl_lahir: '', alamat: '', no_telp: '', id_user: '' as string | number, pend_smu: '', pend_diploma: '', pend_sarjana: '', pend_pascasarjana: '', bidang_keahlian: '', pengalaman_lain: '', cv_tempat: '', cv_tanggal: '', nama_ttd: '',
    pelatihan: [] as Record<string, string>[], pengalaman_kerja: [] as Record<string, string>[], pengalaman_mengajar: [] as Record<string, string>[], lampiran: [] as Lampiran[] });
const form = useForm(blank());
const cv = ref<Detail | null>(null);
const str = (v: unknown) => (v === null || v === undefined ? '' : String(v));
function openAdd() { form.reset(); form.clearErrors(); Object.assign(form, blank()); tab.value = 'identitas'; currentId.value = null; mode.value = 'add'; }
async function fetchDetail(id: number): Promise<Detail> { return (await fetch(`/master/trainer/${id}`, { headers: { Accept: 'application/json' } })).json(); }
async function openEdit(row: Row) {
    const d = await fetchDetail(row.id); form.clearErrors(); tab.value = 'identitas'; currentId.value = row.id; currentCode.value = row.kode;
    const t = d.trainer;
    Object.assign(form, { nama: str(t.nama), tempat_lahir: str(t.tempat_lahir), tgl_lahir: str(t.tgl_lahir).slice(0, 10), alamat: str(t.alamat), no_telp: str(t.no_telp), id_user: t.id_user ?? '', pend_smu: str(t.pend_smu), pend_diploma: str(t.pend_diploma), pend_sarjana: str(t.pend_sarjana), pend_pascasarjana: str(t.pend_pascasarjana), bidang_keahlian: str(t.bidang_keahlian), pengalaman_lain: str(t.pengalaman_lain), cv_tempat: str(t.cv_tempat), cv_tanggal: str(t.cv_tanggal).slice(0, 10), nama_ttd: str(t.nama_ttd),
        pelatihan: d.pelatihan, pengalaman_kerja: d.pengalaman_kerja, pengalaman_mengajar: d.pengalaman_mengajar,
        lampiran: d.lampiran.map((l) => ({ dokumen: l.dokumen, tahun: l.tahun ?? '', file: null, file_id: l.id, ada_file: l.ada_file })) });
    mode.value = 'edit';
}
const close = () => { mode.value = null; };
function save() {
    return confirmation.form(form, 'post', mode.value === 'edit' ? `/master/trainer/${currentId.value}` : '/master/trainer', { title: `${mode.value === 'edit' ? 'Simpan perubahan trainer' : 'Tambahkan trainer'} ${form.nama}${mode.value === 'edit' ? ` (${currentCode.value})` : ''}?` }, {
        preserveScroll: true, forceFormData: true, onSuccess: () => { close(); reload(); },
    });
}
const toggle = (row: Row) => confirmation.visit('post', `/master/trainer/${row.id}/toggle`, {}, {
    title: `${row.aktif ? 'Nonaktifkan' : 'Aktifkan'} ${row.nama} (${row.kode})?`,
    confirmLabel: row.aktif ? 'Ya, nonaktifkan' : 'Ya, aktifkan', danger: !!row.aktif,
}, { preserveScroll: true, onSuccess: reload });
const viewCv = async (row: Row) => { cv.value = await fetchDetail(row.id); };
const lists = [['pelatihan', 'Pelatihan & Seminar', ['judul', 'penyelenggara', 'waktu']], ['pengalaman_kerja', 'Pengalaman Kerja', ['jabatan', 'institusi', 'tahun']], ['pengalaman_mengajar', 'Pengalaman Mengajar', ['topik', 'tempat', 'tahun']]] as const;
const err = (k: string) => (form.errors as Record<string, string>)[k];
const lines = (v: unknown) => str(v).split(/\r?\n/).filter(Boolean);

const table = ref<InstanceType<typeof ServerTable> | null>(null);
const reload = () => table.value?.reload();
const appliedFilters = ref({ ...props.filters });
const columns = computed(() => [{ title: 'No', orderable: false }, { title: 'ID Trainer', orderable: true }, { title: 'Nama', orderable: true }, { title: 'Bidang Keahlian', orderable: true }, { title: 'Riwayat', orderable: false }, { title: 'Penjualan Program', orderable: true }, { title: 'Status', orderable: true }, { title: 'Action', orderable: false }]);
</script>

<template>
    <Head title="Master Trainer" />
    <AppLayout title="Master Trainer" full-width>
        <PageHead title="Master Trainer" description="Data trainer/fasilitator beserta riwayatnya (format CV trainer). Trainer aktif wajib dipilih di Penjualan Program (satu trainer per event) dan menerima Surat Kerja Trainer dari menu Sales Order."><button v-if="can.add" type="button" class="primary" @click="openAdd" :disabled="confirming">+ Tambah Trainer</button></PageHead>
        <p class="notice">Trainer <strong>tidak dapat dihapus</strong> — gunakan <strong>Nonaktifkan</strong>. Trainer nonaktif tidak muncul di pilihan Penjualan Program.</p>
        <form class="filters" @submit.prevent="apply">
            <label>Status<select v-model="filter.status"><option value="all">Semua</option><option value="on">Aktif</option><option value="off">Nonaktif</option></select></label>
            <button class="primary" :disabled="confirming">Terapkan</button>
        </form>
        <ServerTable ref="table" url="/master/trainer/data" :columns="columns" :filters="appliedFilters" :label="'Trainer'">
            <template #cell-0="{ number }">{{ number }}</template>
            <template #cell-1="{ row }">{{ row.kode }}</template>
            <template #cell-2="{ row }">{{ row.nama }}<small>{{ row.no_telp }}</small></template>
            <template #cell-3="{ row }"><span v-for="k in lines(row.bidang_keahlian)" :key="k" class="badge">{{ k }}</span></template>
            <template #cell-4="{ row }">{{ row.n_pelatihan }} · {{ row.n_kerja }} · {{ row.n_mengajar }} · {{ row.n_lampiran }}</template>
            <template #cell-5="{ row }">{{ row.n_penjualan }}</template>
            <template #cell-6="{ row }"><StatusBadge :active="row.aktif" /></template>
            <template #cell-7="{ row }"><ActionMenu ><button type="button" @click="viewCv(row)" :disabled="confirming">Lihat CV</button><template v-if="can.manage"><button type="button" @click="openEdit(row)" :disabled="confirming">Edit</button><button type="button" @click="toggle(row)" :disabled="confirming">{{ row.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></template></ActionMenu></template>
        </ServerTable>

        <ConfirmDialog v-if="mode" :title="mode === 'edit' ? 'Edit Trainer' : 'Tambah Trainer'" :busy="form.processing || confirming" @confirm="save" @cancel="close">
            <div class="tabs" role="tablist"><button v-for="[key, label] in tabs" :key="key" type="button" role="tab" :aria-selected="tab === key" :class="{ active: tab === key }" @click="tab = key" :disabled="confirming">{{ label }}</button></div>
            <div v-show="tab === 'identitas'" class="form-grid">
                <FormField id="tnama" label="Nama Lengkap" :error="form.errors.nama" v-slot="{ describedBy, invalid }"><input id="tnama" v-model="form.nama" maxlength="150" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="ttl" label="Tempat Lahir" :error="form.errors.tempat_lahir" v-slot="{ describedBy, invalid }"><input id="ttl" v-model="form.tempat_lahir" maxlength="100" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="ttg" label="Tanggal Lahir" :error="form.errors.tgl_lahir" v-slot="{ describedBy, invalid }"><input id="ttg" v-model="form.tgl_lahir" type="date" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="ttel" label="No. Telepon / WhatsApp" :error="form.errors.no_telp" v-slot="{ describedBy, invalid }"><input id="ttel" v-model="form.no_telp" maxlength="50" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="tusr" label="Akun login (Master User)" hint="Opsional; satu akun untuk satu trainer." :error="form.errors.id_user" v-slot="{ describedBy, invalid }"><select id="tusr" v-model="form.id_user" :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">Tanpa akun</option><option v-for="u in users" :key="u.id_user" :value="u.id_user">{{ u.nm_lengkap || u.username }}</option></select></FormField>
                <FormField id="talm" label="Alamat Rumah" :error="form.errors.alamat" v-slot="{ describedBy, invalid }"><textarea id="talm" v-model="form.alamat" :aria-invalid="invalid" :aria-describedby="describedBy"></textarea></FormField>
            </div>
            <div v-show="tab === 'pendidikan'" class="form-grid">
                <FormField v-for="[k, label] in [['pend_smu', 'Pendidikan SMU'], ['pend_diploma', 'Pendidikan Diploma'], ['pend_sarjana', 'Pendidikan Sarjana'], ['pend_pascasarjana', 'Pendidikan Pascasarjana']]" :id="`t${k}`" :key="k" :label="label" :error="err(k)" v-slot="{ describedBy, invalid }"><input :id="`t${k}`" v-model="(form as any)[k]" maxlength="255" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="tkeah" label="Bidang Keahlian" hint="Satu per baris." :error="form.errors.bidang_keahlian" v-slot="{ describedBy, invalid }"><textarea id="tkeah" v-model="form.bidang_keahlian" :aria-invalid="invalid" :aria-describedby="describedBy"></textarea></FormField>
                <FormField id="tlain" label="Pengalaman Lainnya" :error="form.errors.pengalaman_lain" v-slot="{ describedBy, invalid }"><textarea id="tlain" v-model="form.pengalaman_lain" :aria-invalid="invalid" :aria-describedby="describedBy"></textarea></FormField>
                <FormField id="tcvt" label="Tempat Pengesahan CV" :error="form.errors.cv_tempat" v-slot="{ describedBy, invalid }"><input id="tcvt" v-model="form.cv_tempat" maxlength="100" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="tcvg" label="Tanggal Pengesahan CV" :error="form.errors.cv_tanggal" v-slot="{ describedBy, invalid }"><input id="tcvg" v-model="form.cv_tanggal" type="date" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                <FormField id="tttd" label="Nama pada Tanda Tangan" :error="form.errors.nama_ttd" v-slot="{ describedBy, invalid }"><input id="tttd" v-model="form.nama_ttd" maxlength="150" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
            </div>
            <div v-show="tab === 'riwayat'" class="stack">
                <fieldset v-for="[key, label, cols] in lists" :key="key"><legend>{{ label }}</legend>
                    <div v-for="(row, i) in (form as any)[key]" :key="i" class="form-grid">
                        <label v-for="c in cols" :key="c"><span>{{ c }}</span><input v-model="row[c]" :aria-label="`${label} baris ${Number(i) + 1} ${c}`" /></label>
                        <button type="button" class="danger" @click="(form as any)[key].splice(i, 1)" :disabled="confirming">Hapus baris</button>
                        <p v-if="err(`${key}.${i}.${cols[0]}`)" class="error" role="alert">{{ err(`${key}.${i}.${cols[0]}`) }}</p>
                    </div>
                    <button type="button" :disabled="confirming" @click="(form as any)[key].push(Object.fromEntries(cols.map((c) => [c, ''])))">+ Tambah baris</button>
                </fieldset>
            </div>
            <div v-show="tab === 'lampiran'" class="stack">
                <div v-for="(row, i) in form.lampiran" :key="i" class="form-grid">
                    <label><span>Dokumen</span><input v-model="row.dokumen" maxlength="255" :aria-label="`Dokumen lampiran ${i + 1}`" /></label>
                    <label><span>Tahun</span><input v-model="row.tahun" maxlength="20" :aria-label="`Tahun lampiran ${i + 1}`" /></label>
                    <label><span>Berkas (PDF/JPG/PNG, maks 5 MB)</span><input type="file" accept=".pdf,.jpg,.jpeg,.png" :aria-label="`Berkas lampiran ${i + 1}`" @change="row.file = ($event.target as HTMLInputElement).files?.[0] ?? null" /></label>
                    <p v-if="row.ada_file && row.file_id && !row.file" class="muted"><a :href="`/documents/trainer/${row.file_id}`" target="_blank" rel="noopener">Berkas saat ini</a></p>
                    <button type="button" class="danger" @click="form.lampiran.splice(i, 1)" :disabled="confirming">Hapus baris</button>
                    <p v-if="err(`lampiran.${i}.file`)" class="error" role="alert">{{ err(`lampiran.${i}.file`) }}</p>
                </div>
                <button type="button" @click="form.lampiran.push({ dokumen: '', tahun: '', file: null, file_id: null, ada_file: false })" :disabled="confirming">+ Tambah lampiran</button>
            </div>
        </ConfirmDialog>

        <ConfirmDialog v-if="cv" :title="`CV ${cv.trainer.nama}`" confirm-label="Tutup" @confirm="cv = null" @cancel="cv = null">
            <p><a :href="`/master/trainer/${cv.trainer.id}/cv.pdf`" target="_blank" rel="noopener">Cetak CV (PDF)</a></p>
            <dl><template v-for="[label, k] in [['Tempat/Tanggal Lahir', 'tempat_lahir'], ['Telepon', 'no_telp'], ['Alamat', 'alamat'], ['SMU', 'pend_smu'], ['Diploma', 'pend_diploma'], ['Sarjana', 'pend_sarjana'], ['Pascasarjana', 'pend_pascasarjana'], ['Pengalaman Lainnya', 'pengalaman_lain']]" :key="k"><dt>{{ label }}</dt><dd>{{ str(cv.trainer[k]) || '—' }}</dd></template></dl>
            <h3>Bidang Keahlian</h3><ul><li v-for="k in lines(cv.trainer.bidang_keahlian)" :key="k">{{ k }}</li></ul>
            <template v-for="[key, label] in [['pelatihan', 'Pelatihan & Seminar'], ['pengalaman_kerja', 'Pengalaman Kerja'], ['pengalaman_mengajar', 'Pengalaman Mengajar']]" :key="key"><h3>{{ label }}</h3><ol><li v-for="(r, i) in (cv as any)[key]" :key="i">{{ Object.values(r).slice(3).filter(Boolean).join(' · ') }}</li></ol></template>
            <h3>Lampiran</h3><ol><li v-for="l in cv.lampiran" :key="l.id">{{ l.dokumen }} {{ l.tahun }} <a v-if="l.ada_file" :href="`/documents/trainer/${l.id}`" target="_blank" rel="noopener">Unduh</a></li></ol>
        </ConfirmDialog>
    </AppLayout>
</template>
