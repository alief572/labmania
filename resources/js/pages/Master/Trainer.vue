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

type Row = { id: number; kode: string; nama: string; no_telp: string | null; bidang_keahlian: string | null; aktif: number; id_user: number | null; n_pelatihan: number; n_kerja: number; n_mengajar: number; n_lampiran: number; n_penjualan: number };
type Detail = { trainer: Record<string, string | number | null>; pelatihan: Record<string, string>[]; pengalaman_kerja: Record<string, string>[]; pengalaman_mengajar: Record<string, string>[]; lampiran: { id: number; dokumen: string; tahun: string; ada_file: boolean }[] };
type Lampiran = { dokumen: string; tahun: string; file: File | null; file_id: number | null; ada_file: boolean };
const props = defineProps<{
    filters: { q: string; status: string };
    users: { id_user: number; nm_lengkap: string; username: string }[]; can: { add: boolean; manage: boolean };
}>();
const filter = ref({ ...props.filters });
watch(() => filter.value.status, () => { appliedFilters.value = { ...filter.value }; table.value?.reload(true); });
const tabs = [['identitas', 'Identitas Trainer'], ['pelatihan', 'Pelatihan & Seminar'], ['pengalaman_kerja', 'Pengalaman Kerja'], ['pengalaman_mengajar', 'Pengalaman Mengajar'], ['lampiran', 'Lampiran Dokumen']] as const;
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
async function fetchDetail(id: number): Promise<Detail> { return (await fetch(appUrl(`/master/trainer/${id}`), { headers: { Accept: 'application/json' } })).json(); }
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
// Kolom riwayat = header mockup (TR_LISTS); kunci = nama field di server.
const lists = {
    pelatihan: [['judul', 'Judul Pelatihan/Seminar/Lainnya', 'wide'], ['penyelenggara', 'Penyelenggara/Tempat', ''], ['waktu', 'Waktu', 'yr']],
    pengalaman_kerja: [['jabatan', 'Jabatan', 'wide'], ['institusi', 'Institusi/Perusahaan', ''], ['tahun', 'Tahun', 'yr']],
    pengalaman_mengajar: [['topik', 'Topik Pembelajaran', 'wide'], ['tempat', 'Tempat', ''], ['tahun', 'Tahun', 'yr']],
} as const;
type ListKey = keyof typeof lists;
const listKeys = Object.keys(lists) as ListKey[];
const count = (k: string) => k === 'lampiran' ? form.lampiran.length : k in lists ? (form as unknown as Record<string, unknown[]>)[k].length : null;
const fmtTgl = (v: unknown) => { const d = str(v).slice(0, 10); if (!d) return '—'; const [y, m, day] = d.split('-'); return `${day}-${m}-${y}`; };
const err = (k: string) => (form.errors as Record<string, string>)[k];
const lines = (v: unknown) => str(v).split(/\r?\n/).filter(Boolean);

const table = ref<InstanceType<typeof ServerTable> | null>(null);
const reload = () => table.value?.reload();
const appliedFilters = ref({ ...props.filters });
const columns = computed(() => [{ title: 'No', orderable: false }, { title: 'ID Trainer', orderable: true }, { title: 'Nama Trainer', orderable: true }, { title: 'Bidang Keahlian', orderable: true }, { title: 'Riwayat', orderable: false }, { title: 'Penjualan Program', orderable: true, className: 'dt-body-right' }, { title: 'Status', orderable: true }, { title: 'Action', orderable: false }]);
</script>

<template>
    <Head title="Master Trainer" />
    <AppLayout title="Master Trainer" full-width>
        <PageHead title="Master Trainer"><button v-if="can.add" type="button" class="primary" @click="openAdd" :disabled="confirming">+ Tambah Trainer</button></PageHead>
        <p class="notice">Trainer <strong>tidak dapat dihapus</strong> — gunakan <strong>Nonaktifkan</strong>. Trainer nonaktif tidak muncul di pilihan Penjualan Program.</p>
        <div class="filters">
            <label class="grow">Cari<input v-model="filter.q" type="search" maxlength="100" placeholder="Cari ID, nama, atau bidang keahlian…" /></label>
            <label>Status<select v-model="filter.status"><option value="all">Semua status</option><option value="on">Aktif</option><option value="off">Nonaktif</option></select></label>
        </div>
        <ServerTable ref="table" :url="appUrl('/master/trainer/data')" :columns="columns" :filters="appliedFilters" :keyword="filter.q" :label="'Trainer'">
            <template #cell-0="{ number }">{{ number }}</template>
            <template #cell-1="{ row }"><span class="code">{{ row.kode }}</span></template>
            <template #cell-2="{ row }">{{ row.nama }}<small>{{ row.no_telp || '—' }}</small></template>
            <template #cell-3="{ row }"><template v-for="k in lines(row.bidang_keahlian).slice(0, 2)" :key="k"><span class="chip">{{ k }}</span> </template><span v-if="lines(row.bidang_keahlian).length > 2" class="muted">+{{ lines(row.bidang_keahlian).length - 2 }}</span></template>
            <template #cell-4="{ row }"><span class="muted">{{ row.n_pelatihan }} pelatihan &amp; seminar<br>{{ row.n_kerja }} pengalaman kerja<br>{{ row.n_mengajar }} pengalaman mengajar<br>{{ row.n_lampiran }} lampiran dokumen</span></template>
            <template #cell-5="{ row }">{{ row.n_penjualan }}</template>
            <template #cell-6="{ row }"><StatusBadge :active="row.aktif" /></template>
            <template #cell-7="{ row }"><ActionMenu><button v-if="can.manage" type="button" @click="openEdit(row)" :disabled="confirming">Edit</button><button type="button" @click="viewCv(row)" :disabled="confirming">Lihat CV</button><button v-if="can.manage" type="button" @click="toggle(row)" :disabled="confirming">{{ row.aktif ? 'Nonaktifkan' : 'Aktifkan' }}</button></ActionMenu></template>
        </ServerTable>

        <ConfirmDialog v-if="mode" size="xwide" :title="mode === 'edit' ? `Edit Trainer ${currentCode}` : 'Tambah Trainer'" :busy="form.processing || confirming" @confirm="save" @cancel="close">
            <div class="tabs tr-tabs" role="tablist"><button v-for="[key, label] in tabs" :key="key" type="button" role="tab" :aria-selected="tab === key" :class="{ active: tab === key }" @click="tab = key" :disabled="confirming">{{ label }}<span v-if="count(key) !== null" class="nav-count">{{ count(key) }}</span></button></div>

            <div v-show="tab === 'identitas'">
                <div class="grid2">
                    <FormField id="tkode" label="ID Trainer" hint="Diisi otomatis oleh sistem saat disimpan" v-slot="{ describedBy }"><input id="tkode" :value="currentCode" placeholder="Dibuat otomatis" readonly aria-readonly="true" :aria-describedby="describedBy" /></FormField>
                    <FormField id="tnama" label="Nama lengkap" :error="form.errors.nama" v-slot="{ describedBy, invalid }"><input id="tnama" v-model="form.nama" maxlength="150" placeholder="Nama dan gelar" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField id="ttl" label="Tempat lahir" :error="form.errors.tempat_lahir" v-slot="{ describedBy, invalid }"><input id="ttl" v-model="form.tempat_lahir" maxlength="100" placeholder="mis. Jakarta" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField id="ttg" label="Tanggal lahir" :error="form.errors.tgl_lahir" v-slot="{ describedBy, invalid }"><input id="ttg" v-model="form.tgl_lahir" type="date" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField class="span2" id="talm" label="Alamat rumah" :error="form.errors.alamat" v-slot="{ describedBy, invalid }"><textarea id="talm" v-model="form.alamat" rows="2" :aria-invalid="invalid" :aria-describedby="describedBy"></textarea></FormField>
                    <FormField class="span2" id="ttel" label="No. telepon rumah/HP" :error="form.errors.no_telp" v-slot="{ describedBy, invalid }"><input id="ttel" v-model="form.no_telp" maxlength="50" placeholder="08xxxxxxxxxx / 08xxxxxxxxxx" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField class="span2" id="tusr" label="Akun login (Master User)" hint="Opsional; satu akun untuk satu trainer." :error="form.errors.id_user" v-slot="{ describedBy, invalid }"><select id="tusr" v-model="form.id_user" :aria-invalid="invalid" :aria-describedby="describedBy"><option value="">— Tanpa akun —</option><option v-for="u in users" :key="u.id_user" :value="u.id_user">{{ u.nm_lengkap }} ({{ u.username }})</option></select></FormField>
                </div>
                <p class="qtn-h">Pendidikan formal</p>
                <div class="grid2">
                    <FormField v-for="[k, label] in [['pend_smu', 'SMU/sederajat'], ['pend_diploma', 'Diploma'], ['pend_sarjana', 'Sarjana'], ['pend_pascasarjana', 'Pascasarjana']]" :id="`t${k}`" :key="k" :label="label" :error="err(k)" v-slot="{ describedBy, invalid }"><input :id="`t${k}`" v-model="(form as any)[k]" maxlength="255" placeholder="Jurusan, institusi, tahun lulus" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                </div>
                <p class="qtn-h">Keahlian &amp; pengesahan CV</p>
                <div class="grid2">
                    <FormField id="tkeah" label="Bidang keahlian" hint="Satu bidang per baris" :error="form.errors.bidang_keahlian" v-slot="{ describedBy, invalid }"><textarea id="tkeah" v-model="form.bidang_keahlian" rows="4" placeholder="Satu bidang per baris" :aria-invalid="invalid" :aria-describedby="describedBy"></textarea></FormField>
                    <FormField id="tlain" label="Pengalaman lainnya" :error="form.errors.pengalaman_lain" v-slot="{ describedBy, invalid }"><textarea id="tlain" v-model="form.pengalaman_lain" rows="4" placeholder="mis. Asesor BNSP" :aria-invalid="invalid" :aria-describedby="describedBy"></textarea></FormField>
                    <FormField id="tcvt" label="Tempat pengesahan CV" :error="form.errors.cv_tempat" v-slot="{ describedBy, invalid }"><input id="tcvt" v-model="form.cv_tempat" maxlength="100" placeholder="mis. Bekasi" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField id="tcvg" label="Tanggal pengesahan CV" :error="form.errors.cv_tanggal" v-slot="{ describedBy, invalid }"><input id="tcvg" v-model="form.cv_tanggal" type="date" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField class="span2" id="tttd" label="Nama pada tanda tangan" :error="form.errors.nama_ttd" v-slot="{ describedBy, invalid }"><input id="tttd" v-model="form.nama_ttd" maxlength="150" placeholder="Nama tanpa gelar" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                </div>
            </div>

            <div v-for="key in listKeys" v-show="tab === key" :key="key" class="trainer-history">
                <div class="table-wrap"><table class="tr-table">
                    <caption class="sr-only">{{ tabs.find((t) => t[0] === key)?.[1] }}</caption>
                    <thead><tr><th class="num">No.</th><th v-for="[c, label, cls] in lists[key]" :key="c" :class="cls">{{ label }}</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="(row, i) in (form as any)[key]" :key="i" :class="{ 'row-err': err(`${key}.${i}.${lists[key][0][0]}`) }">
                            <td class="num">{{ Number(i) + 1 }}</td>
                            <td v-for="[c, label, cls] in lists[key]" :key="c" :class="cls"><input v-model="row[c]" :aria-label="`${label} baris ${Number(i) + 1}`" :placeholder="cls === 'yr' ? 'mis. 2025' : ''" /></td>
                            <td><button type="button" class="btn btn-ghost sm" :aria-label="`Hapus baris ${Number(i) + 1}`" @click="(form as any)[key].splice(i, 1)" :disabled="confirming">Hapus</button></td>
                        </tr>
                        <tr v-if="!(form as any)[key].length"><td :colspan="lists[key].length + 2" class="empty">Belum ada data. Klik + Tambah Baris.</td></tr>
                    </tbody>
                </table></div>
                <button type="button" class="btn btn-outline sm" :disabled="confirming" @click="(form as any)[key].push(Object.fromEntries(lists[key].map(([c]) => [c, ''])))">+ Tambah Baris</button>
                <template v-for="(row, i) in (form as any)[key]" :key="`e${i}`"><p v-if="err(`${key}.${i}.${lists[key][0][0]}`)" class="error" role="alert">Baris {{ Number(i) + 1 }}: {{ err(`${key}.${i}.${lists[key][0][0]}`) }}</p></template>
            </div>

            <div v-show="tab === 'lampiran'" class="trainer-history">
                <div class="table-wrap"><table class="tr-table">
                    <caption class="sr-only">Lampiran Dokumen</caption>
                    <thead><tr><th class="num">No.</th><th class="wide">Dokumen / Sertifikat Lampiran</th><th class="yr">Tahun</th><th class="file">File</th><th></th></tr></thead>
                    <tbody>
                        <tr v-for="(row, i) in form.lampiran" :key="i">
                            <td class="num">{{ Number(i) + 1 }}</td>
                            <td class="wide"><input v-model="row.dokumen" maxlength="255" :aria-label="`Dokumen lampiran ${Number(i) + 1}`" /></td>
                            <td class="yr"><input v-model="row.tahun" maxlength="20" placeholder="mis. 2025" :aria-label="`Tahun lampiran ${Number(i) + 1}`" /></td>
                            <td class="file"><a v-if="row.ada_file && row.file_id && !row.file" :href="appUrl(`/documents/trainer/${row.file_id}`)" target="_blank" rel="noopener">Berkas saat ini</a> <span v-if="row.file" class="code">{{ row.file.name }}</span>
                                <label class="file-btn">{{ row.ada_file || row.file ? 'Ganti' : 'Unggah' }}<input type="file" accept=".pdf,.jpg,.jpeg,.png" hidden :aria-label="`Berkas lampiran ${Number(i) + 1}`" @change="row.file = ($event.target as HTMLInputElement).files?.[0] ?? null" /></label>
                                <small v-if="err(`lampiran.${i}.file`)" class="error" role="alert">{{ err(`lampiran.${i}.file`) }}</small></td>
                            <td><button type="button" class="btn btn-ghost sm" :aria-label="`Hapus lampiran ${Number(i) + 1}`" @click="form.lampiran.splice(i, 1)" :disabled="confirming">Hapus</button></td>
                        </tr>
                        <tr v-if="!form.lampiran.length"><td colspan="5" class="empty">Belum ada data. Klik + Tambah Baris.</td></tr>
                    </tbody>
                </table></div>
                <button type="button" class="btn btn-outline sm" :disabled="confirming" @click="form.lampiran.push({ dokumen: '', tahun: '', file: null, file_id: null, ada_file: false })">+ Tambah Baris</button>
                <p class="hint-line">File lampiran PDF/JPG/PNG, maks 5 MB.</p>
            </div>
        </ConfirmDialog>

        <ConfirmDialog v-if="cv" size="xwide" info :title="`CV Trainer — ${cv.trainer.nama}`" @confirm="cv = null" @cancel="cv = null">
            <div class="letter">
                <p><b>MASTER TRAINER — IDENTITAS FASILITATOR</b><br><span class="muted code">{{ cv.trainer.kode }}</span></p>
                <dl class="summary">
                    <div><dt>Nama lengkap</dt><dd>{{ cv.trainer.nama }}</dd></div>
                    <div><dt>Tempat, tanggal lahir</dt><dd>{{ cv.trainer.tempat_lahir || '—' }}, {{ fmtTgl(cv.trainer.tgl_lahir) }}</dd></div>
                    <div><dt>Alamat rumah</dt><dd>{{ cv.trainer.alamat || '—' }}</dd></div>
                    <div><dt>No. telepon rumah/HP</dt><dd>{{ cv.trainer.no_telp || '—' }}</dd></div>
                    <div v-for="[k, l] in [['pend_smu', 'SMU/sederajat'], ['pend_diploma', 'Diploma'], ['pend_sarjana', 'Sarjana'], ['pend_pascasarjana', 'Pascasarjana']]" :key="k"><dt>Pendidikan — {{ l }}</dt><dd>{{ cv.trainer[k] || '—' }}</dd></div>
                    <div><dt>Bidang keahlian</dt><dd>{{ lines(cv.trainer.bidang_keahlian).join('; ') || '—' }}</dd></div>
                    <div><dt>Pengalaman lainnya</dt><dd>{{ cv.trainer.pengalaman_lain || '—' }}</dd></div>
                    <div><dt>Pengesahan CV</dt><dd>{{ cv.trainer.cv_tempat || '—' }}, {{ fmtTgl(cv.trainer.cv_tanggal) }}</dd></div>
                    <div><dt>Nama pada tanda tangan</dt><dd>{{ cv.trainer.nama_ttd || '—' }}</dd></div>
                </dl>
                <template v-for="key in listKeys" :key="key">
                    <p class="qtn-h">{{ tabs.find((t) => t[0] === key)?.[1] }}</p>
                    <div v-if="(cv as any)[key].length" class="table-wrap"><table class="tr-table">
                        <thead><tr><th class="num">No.</th><th v-for="[c, label] in lists[key]" :key="c">{{ label }}</th></tr></thead>
                        <tbody><tr v-for="(r, i) in (cv as any)[key]" :key="i"><td class="num">{{ Number(i) + 1 }}</td><td v-for="[c] in lists[key]" :key="c">{{ r[c] || '—' }}</td></tr></tbody>
                    </table></div>
                    <p v-else class="muted">Belum ada data.</p>
                </template>
                <p class="qtn-h">Lampiran Dokumen</p>
                <div v-if="cv.lampiran.length" class="table-wrap"><table class="tr-table">
                    <thead><tr><th class="num">No.</th><th>Dokumen / Sertifikat Lampiran</th><th class="yr">Tahun</th><th>File</th></tr></thead>
                    <tbody><tr v-for="(l, i) in cv.lampiran" :key="l.id"><td class="num">{{ Number(i) + 1 }}</td><td>{{ l.dokumen }}</td><td class="yr">{{ l.tahun || '—' }}</td><td><a v-if="l.ada_file" :href="appUrl(`/documents/trainer/${l.id}`)" target="_blank" rel="noopener">Unduh</a><span v-else>—</span></td></tr></tbody>
                </table></div>
                <p v-else class="muted">Belum ada data.</p>
                <p class="cv-pdf"><a class="primary" :href="appUrl(`/master/trainer/${cv.trainer.id}/cv.pdf`)" target="_blank" rel="noopener">Cetak CV (PDF)</a></p>
            </div>
        </ConfirmDialog>
    </AppLayout>
</template>

<style scoped>
.tr-tabs { padding: 0; margin: -4px 0 14px; flex-wrap: wrap; }
.tr-tabs button { flex-shrink: 0; }
.trainer-history .table-wrap { margin-bottom: 12px; box-shadow: none; }
.trainer-history .tr-table { min-width: 800px; }
.trainer-history th { padding: 10px 14px; font-size: 11.5px; }
.trainer-history td { padding: 11px 14px; }
.trainer-history .wide { min-width: 280px; }
.trainer-history .yr { width: 130px; }
.trainer-history td:last-child { text-align: right; white-space: nowrap; }
.trainer-history td.empty { padding: 32px; text-align: center; white-space: normal; }
</style>
