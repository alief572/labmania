<script setup lang="ts">
import DocumentUpload from '../../components/DocumentUpload.vue';
import TutorialButton from "../../components/TutorialButton.vue";
import { appUrl } from '../../composables/appUrl';
import MoneyInput from "../../components/MoneyInput.vue";
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import QuotationParticipants, { type Participant } from '../../components/QuotationParticipants.vue';
import { rupiah, rupiahInput } from '../../composables/costing';
import { postPdf } from '../../composables/pdf';
import { useConfirmedMutation } from '../../composables/confirmedMutation';
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;
type Program = { id: number; nomor: string; judul: string; jenis: string; id_customer: string | null; harga_jual_peserta: string; target_peserta: number; closed_date: string | null; id_jenis_produk?: number; bep_harga: number; tgl_mulai?: string; tgl_selesai?: string; waktu?: string; durasi_hari?: number; lokasi?: string };
type Detail = { kategori: string; teks: string };
type Quote = { revisi?: number; id: number | null; nomor: string; status: string; id_penjualan: number | ''; tanggal: string; berlaku_sampai: string; id_sales: number | ''; sumber_info: string; tujuan: string; id_customer: string | null; kepada_nama: string | null; up_nama: string | null; alamat: string | null; kuota_peserta: number; harga_allin: string | number; diskon: string | number; biaya_lain: string | number; cara_bayar: 'full' | 'top'; termin: { persen: number | string; keterangan: string }[]; is_ppn: number | boolean; id_template_konfirmasi: number | ''; konfirmasi_isian: string[]; konfirmasi_pernyataan: string; kolom_peserta_b2b: string[]; keterangan: Detail[]; paket_maks_peserta?: number; tambahan_per_peserta?: string; dokumen_keterangan?: string | null };
type Template = { id: number; nama: string; id_jenis_produk: number | null; isian: string[]; pernyataan: string };
const props = defineProps<{ modal?: boolean; quotation: Quote; revisions: {revisi:number;created_date:string}[]; salesOrder: { id: number; nomor: string; revisi:number; status?: string } | null; redeal?: { kode: string; teks: string; sub: string | null } | null; needsRedeal?: boolean; hasDocuments?: boolean; programs: Program[]; companies: { id_customer: string; name_customer: string; pic_name: string; address_office: string; kota: string; perlakuan_ppn?: string | null }[]; sales: { id: number; nama: string }[]; templates: Template[]; fields: Record<string, string>; keteranganOptions: (Detail & { id: number; berlaku_untuk: string; is_default: number })[]; peserta: Participant[]; documents: { id: number; nama_file: string; ukuran_byte: number }[]; companyConfirmation: { id: number; nama_perusahaan: string; pic_nama: string; pic_jabatan: string; pic_hp: string; pic_email: string; surat_konfirmasi_nama: string; tgl_kirim: string } | null; link: string | null; linkClosed: string | null; calc: { grand_total: string; dpp: string; ppn: string; n_peserta: number } | null; can: { edit: boolean; cancel: boolean }; quotaOther: number }>();
const emit = defineEmits<{ close: [] }>();
const q = props.quotation;
const form = useForm({ ...q, harga_allin: Math.round(Number(q.harga_allin)) as number | string, diskon: Math.round(Number(q.diskon)) as number | string, biaya_lain: Math.round(Number(q.biaya_lain)) as number | string, konfirmasi_isian: [...q.konfirmasi_isian], kolom_peserta_b2b: [...q.kolom_peserta_b2b], cara_bayar: q.cara_bayar ?? 'full', termin: (q.termin ?? []).map((t) => ({ ...t })), keterangan: q.keterangan.map((d) => ({ kategori: d.kategori, teks: d.teks })) });
const selected = computed(() => props.programs.find((p) => p.id === Number(form.id_penjualan)));
const b2b = computed(() => selected.value?.jenis === 'b2b');
const BULAN = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
const tglPanjang = (iso?: string | null) => { if (!iso) return '—'; const [y, m, d] = iso.slice(0, 10).split('-'); return `${+d} ${BULAN[+m - 1]} ${y}`; };
const rangePanjang = (a?: string | null, b?: string | null) => {
    if (!a) return '—';
    if (!b || a.slice(0, 10) === b.slice(0, 10)) return tglPanjang(a);
    const [y1, m1, d1] = a.slice(0, 10).split('-'), [y2, m2] = b.slice(0, 10).split('-');
    if (y1 === y2 && m1 === m2) return `${+d1}–${tglPanjang(b)}`;
    return y1 === y2 ? `${+d1} ${BULAN[+m1 - 1]} – ${tglPanjang(b)}` : `${tglPanjang(a)} – ${tglPanjang(b)}`;
};
const PERLAKUAN: Record<string, string> = { PKP: 'PKP', WAPU: 'WAPU', KB: 'Kawasan Berikat', NON: 'Tanpa PPN' };
const perlakuanPpn = computed(() => !q.is_ppn ? 'NON' : q.tujuan === 'company' ? (props.companies.find((c) => c.id_customer === q.id_customer)?.perlakuan_ppn || 'PKP') : 'PKP');
const bayarTxt = computed(() => q.cara_bayar === 'top' && q.termin.length > 1 ? `TOP ${q.termin.length} termin: ${q.termin.map((t, i) => `Termin ${i + 1} ${Math.round(Number(t.persen) * 100) / 100}% (${t.keterangan})`).join('; ')}` : 'Full payment');
const isianLink = computed(() => [...new Set(['nama', ...q.konfirmasi_isian])].map((k) => [k, (props.fields[k] ?? k).replace('*', '')] as const));
const copied = ref(false);
async function copyLink() { try { await navigator.clipboard.writeText(props.link ?? ''); copied.value = true; setTimeout(() => { copied.value = false; }, 2000); } catch { error.value = 'Link tidak dapat disalin. Salin manual dari kolom link.'; } }
const editing = ref(false);
const editable = computed(() => q.status === 'draft' && props.can.edit && (!q.id || editing.value));
const STATUS_LABEL: Record<string, string> = { draft: 'Draft', terkirim: 'Terkirim', deal: 'Deal', batal: 'Batal' };
const LANGKAH: Record<string, string> = {
    draft: 'Periksa isi quotation, lalu kirim ke customer. Link Konfirmasi Kehadiran sudah bisa dibagikan.',
    terkirim: props.needsRedeal ? 'Program di-reschedule: kirim ulang PDF dan link baru, tunggu konfirmasi ulang peserta, lalu Deal ulang paling lambat H-1.' : 'Tunggu calon peserta mengisi link. Setelah peserta pasti ikut, tandai Deal.',
    deal: 'Peserta sudah Deal. Lanjutkan di menu Sales Order: cetak SO, lalu tandai siap dibuatkan invoice. Confirmation Letter ada di tab Data Peserta.',
    batal: 'Quotation dibatalkan dan link Konfirmasi Kehadiran ditutup.',
};
const alur = ['draft', 'terkirim', 'deal'];
const alurPos = alur.indexOf(q.status);
const terisi = computed(() => props.peserta.filter((p) => p.status !== 'batal' && p.status !== 'keluar').length);
const kuotaTotal = computed(() => Number(q.kuota_peserta) || 0);
const kuotaPct = computed(() => (kuotaTotal.value ? Math.min(100, terisi.value / kuotaTotal.value * 100) : 0));
const steps = computed(() => ['Penawaran', 'Keterangan', b2b.value ? 'Konfirmasi Company (link)' : 'Formulir Konfirmasi', 'Preview']); const step = ref(0);
const dataTab = ref(!!q.id && new URLSearchParams(window.location.search).get('tab') === 'data');
const error = ref(''); const busy = ref(false); const action = ref('');
function company() { const c = props.companies.find((c) => c.id_customer === form.id_customer); if (c) { form.up_nama = c.pic_name; form.alamat = [c.address_office, c.kota].filter(Boolean).join(', '); } }
function template() { const t = props.templates.find((t) => t.id === Number(form.id_template_konfirmasi)); if (t) { form.konfirmasi_isian = [...new Set(['nama', ...t.isian])]; form.konfirmasi_pernyataan = t.pernyataan; } }
function program() {
    if (q.id) return;
    form.tujuan = b2b.value ? 'company' : 'personal'; form.id_customer = selected.value?.id_customer ?? '';
    form.kuota_peserta = b2b.value ? 10 : 1; form.harga_allin = Math.round(Number(selected.value?.harga_jual_peserta ?? 0)) * Math.min(form.kuota_peserta, 25); company();
    form.keterangan = props.keteranganOptions.filter((d) => d.is_default && ['semua', selected.value?.jenis].includes(d.berlaku_untuk)).map((d) => ({ kategori: d.kategori, teks: d.teks }));
    const t = props.templates.find((t) => t.id_jenis_produk === selected.value?.id_jenis_produk) ?? props.templates[0];
    if (t) { form.id_template_konfirmasi = t.id; template(); }
    if (b2b.value) { form.konfirmasi_isian = ['nama']; form.konfirmasi_pernyataan = 'Telah membaca dan menyetujui syarat dan ketentuan yang berlaku di LabMania, termasuk ketentuan biaya peserta di atas 25 orang sesuai quotation.'; }
}
if (!q.id && form.id_penjualan) program();
watch(() => form.tanggal, (value, old) => { if (form.berlaku_sampai === `${old.slice(0, 4)}-12-31`) form.berlaku_sampai = `${value.slice(0, 4)}-12-31`; });
function save() { return confirmation.form(form, q.id ? 'put' : 'post', q.id ? `/quotation/${q.id}` : '/quotation', { title: 'Simpan Draft Quotation?', text: q.nomor || 'Nomor dibuat otomatis saat disimpan.' }, { preserveState: 'errors' }); }
const previewDocument = ref('');
let previewRequest: AbortController | undefined;
let previewTimer: ReturnType<typeof setTimeout> | undefined;
function clearPreview() {
    clearTimeout(previewTimer);
    previewRequest?.abort(); previewRequest = undefined;
    previewDocument.value = ''; busy.value = false;
}
async function preview() {
    clearPreview(); busy.value = true; error.value = '';
    const request = new AbortController(); previewRequest = request;
    try {
        const csrf = document.cookie.split('; ').find(v => v.startsWith('XSRF-TOKEN='))?.slice(11);
        const response = await fetch(appUrl('/quotation/preview'), {
            method: 'POST', credentials: 'same-origin', signal: request.signal,
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-XSRF-TOKEN': decodeURIComponent(csrf ?? '') },
            body: JSON.stringify({ ...form.data(), preview_document: step.value === 2 ? 'confirmation' : 'offer' }),
        });
        if (!response.headers.get('Content-Type')?.includes('application/json')) throw new Error('Sesi berakhir. Muat ulang halaman dan masuk kembali.');
        const result = await response.json();
        if (!response.ok) throw new Error(result.errors ? Object.values(result.errors).flat().join('\n') : 'Preview tidak dapat dimuat. Coba lagi.');
        if (typeof result.document !== 'string') throw new Error('Preview tidak dapat dimuat. Coba lagi.');
        if (previewRequest === request) previewDocument.value = result.document;
    } catch (e) {
        if (previewRequest === request) error.value = e instanceof Error ? e.message : 'Preview tidak dapat dimuat.';
    } finally { if (previewRequest === request) busy.value = false; }
}
watch(step, value => { clearPreview(); if (value === 2 || value === 3) void preview(); });
watch(() => form.data(), () => {
    if (step.value !== 2 && step.value !== 3) return;
    clearPreview(); busy.value = true;
    previewTimer = setTimeout(preview, 300);
}, { deep: true });
onBeforeUnmount(clearPreview);
const transition = useForm({});
function process(kind: string) {
    if (kind === 'batalkan') return confirmation.form(transition, 'post', `/quotation/${q.id}/batalkan`, { title: `Batalkan ${q.nomor}?`, text: 'Quotation yang dibatalkan tidak dapat diproses kembali.', confirmLabel: 'Ya, batalkan', danger: true }, { preserveState: 'errors' });
    error.value = '';
    return confirmation.request({ id: q.id }, { title: `Kirim ${q.nomor}?`, text: 'Nomor dan isi dokumen mengikuti Quotation yang sudah disimpan. Status menjadi Terkirim dan dokumen PDF dibuka.', confirmLabel: 'Ya, kirim', successText: 'Quotation berhasil dikirim.' }, payload => postPdf(`/quotation/${payload.id}/kirim`), {
        onSuccess: () => router.get(window.location.pathname, {}, { preserveState: false, preserveScroll: true, replace: true }), onError: message => { error.value = message; },
    });
}
const quota = useForm({ kuota_peserta: q.kuota_peserta });
// Deal ulang (QTN-FR-42): yang dapat dicentang hanya peserta yang memilih Pindah jadwal pada revisi terakhir.
const again = !!props.needsRedeal;
const dealRows = props.peserta.filter((p) => again ? (p.status === 'menunggu' || p.status === 'deal') && p.konfirmasi_ulang?.keputusan === 'pindah_jadwal' : p.status === 'menunggu');
const deal = useForm({ peserta: dealRows.map((p) => p.id), dokumen: [] as File[], dokumen_keterangan: '' });
function saveDocuments() { return confirmation.form(deal, 'post', `/quotation/${q.id}/${q.status === 'deal' ? 'dokumen' : 'deal'}`, { title: q.status === 'deal' ? `Simpan dokumen ${q.nomor}?` : `${again ? 'Deal ulang' : 'Tandai'} ${q.nomor}${again ? '?' : ' Deal?'}`, text: q.status === 'deal' ? 'Dokumen ditambahkan ke riwayat customer.' : `${deal.peserta.length} peserta dipilih. Peserta lain menjadi Batal; Deal membentuk Master Peserta dan Sales Order.`, confirmLabel: q.status === 'deal' ? 'Ya, simpan' : 'Ya, Deal' }, { forceFormData: true, preserveState: 'errors', onSuccess: () => { action.value = ''; deal.reset('dokumen'); } }); }
function saveQuota() { return confirmation.form(quota, 'post', `/quotation/${q.id}/kuota`, { title: `Tambah kuota ${q.nomor}?`, text: `Kuota baru: ${quota.kuota_peserta} peserta.` }, { preserveScroll: true }); }
const sum = computed(() => {
    if (!editable.value && props.calc) return { count: props.calc.n_peserta, dpp: Number(props.calc.dpp), tax: Number(props.calc.ppn), total: Number(props.calc.grand_total) };
    const count = q.status === 'deal' ? (props.calc?.n_peserta ?? 0) : Number(form.kuota_peserta);
    const base = b2b.value ? rupiahInput(form.harga_allin) + Math.max(0, count - (q.paket_maks_peserta ?? 25)) * Number(q.tambahan_per_peserta ?? 300000) : count * Number(selected.value?.harga_jual_peserta ?? 0);
    const dpp = base - rupiahInput(form.diskon) * (b2b.value ? 1 : count); const tax = form.is_ppn ? Math.round(dpp * 11) / 100 : 0;
    return { count, dpp, tax, total: dpp + tax + rupiahInput(form.biaya_lain) };
});
// TOP (QTN-FR-44): termin diisi rupiah (persen otomatis) atau persen (rupiah otomatis) terhadap Grand total quotation.
const TOP_DEFAULT = () => [{ persen: 50, keterangan: 'Uang muka (DP) setelah quotation ditandatangani / PO' }, { persen: 50, keterangan: 'Pelunasan sebelum acara' }];
function setCara(value: 'full' | 'top') { form.cara_bayar = value; if (value === 'top' && form.termin.length < 2) form.termin = TOP_DEFAULT(); }
const pct2 = (n: number) => Math.round(n * 100) / 100;
const b2bExtra = computed(() => Math.max(0, sum.value.count - (q.paket_maks_peserta ?? 25)));
const belowBep = computed(() => !!selected.value && sum.value.count > 0 && sum.value.dpp / sum.value.count < selected.value.bep_harga && (b2b.value || rupiahInput(form.diskon) > 0));
const terminSum = computed(() => pct2(form.termin.reduce((s, t) => s + (Number(t.persen) || 0), 0)));
const terminNominal = (t: { persen: number | string }) => Math.round(sum.value.total * (Number(t.persen) || 0) / 100);
const terminOk = computed(() => form.cara_bayar !== 'top' || (form.termin.length >= 2 && Math.abs(terminSum.value - 100) <= 0.05));
function setNominal(i: number, value: string | number) { const n = Number(value) || 0; if (sum.value.total > 0) form.termin[i].persen = pct2(n / sum.value.total * 100); }
function addTermin() { if (form.termin.length < 12) form.termin.push({ persen: '', keterangan: '' }); }
const extra = ref('');
const ketCategories = [['syarat', 'Syarat keikutsertaan'], ['termasuk', 'Termasuk'], ['tidak', 'Tidak termasuk'], ['dokumen', 'Dokumen persyaratan']] as const;
const ketChoices = ref<(Detail & { on: boolean; manual: boolean })[]>([]);
const ketNew = reactive<Record<string, string>>({});
const ketEditing = ref<number | null>(null);
const ketText = ref('');
function loadKetChoices() {
    const master = props.keteranganOptions.filter(d => d.kategori !== 'klausul' && ['semua', selected.value?.jenis].includes(d.berlaku_untuk));
    ketChoices.value = master.map(d => ({ kategori: d.kategori, teks: d.teks, on: form.keterangan.some(x => x.kategori === d.kategori && x.teks === d.teks), manual: false }));
    for (const d of form.keterangan.filter(d => d.kategori !== 'klausul')) {
        if (!ketChoices.value.some(x => x.kategori === d.kategori && x.teks === d.teks)) ketChoices.value.push({ ...d, on: true, manual: true });
    }
    ketEditing.value = null;
}
loadKetChoices();
watch(() => form.id_penjualan, loadKetChoices);
watch(ketChoices, choices => {
    form.keterangan = [...choices.filter(d => d.on).map(({ kategori, teks }) => ({ kategori, teks })), ...form.keterangan.filter(d => d.kategori === 'klausul')];
}, { deep: true });
function resetKet() {
    form.keterangan = props.keteranganOptions.filter(d => d.is_default && ['semua', selected.value?.jenis].includes(d.berlaku_untuk)).map(({ kategori, teks }) => ({ kategori, teks }));
    loadKetChoices();
}
function addKet(kategori: string) {
    const teks = (ketNew[kategori] ?? '').trim();
    if (!teks) return;
    const existing = ketChoices.value.find(d => d.kategori === kategori && d.teks === teks);
    if (existing) existing.on = true;
    else ketChoices.value.push({ kategori, teks, on: true, manual: true });
    ketNew[kategori] = '';
}
function editKet() {
    if (ketEditing.value === null || !ketText.value.trim()) return;
    Object.assign(ketChoices.value[ketEditing.value], { teks: ketText.value.trim(), on: true, manual: true });
    ketEditing.value = null;
}
function resetTemplate() {
    const t = props.templates.find(t => t.id_jenis_produk === selected.value?.id_jenis_produk) ?? props.templates[0];
    if (t) { form.id_template_konfirmasi = t.id; template(); }
}
function addExtra() {
    const value = extra.value.trim();
    if (value && !form.konfirmasi_isian.includes(value) && form.konfirmasi_isian.length < 30) form.konfirmasi_isian.push(value);
    extra.value = '';
}
const statement = computed(() => {
    const p = selected.value;
    const vars: Record<string, string> = { judul: p?.judul ?? '-', tanggal: rangePanjang(p?.tgl_mulai, p?.tgl_selesai), waktu: p?.waktu || '..........', durasi: String(p?.durasi_hari ?? '-'), lokasi: p?.lokasi || '-' };
    return form.konfirmasi_pernyataan.replace(/\{(\w+)\}/g, (token, key) => vars[key] ?? token).split(/(\*\*[^*]+\*\*)/g);
});
</script>
<template>
    <Head v-if="!modal" :title="q.nomor || 'Buat Quotation'" /><component :is="modal ? ConfirmDialog : AppLayout" :title="modal ? 'Buat Quotation' : 'Quotation'" :size="modal ? 'xwide' : undefined" :busy="form.processing || confirming" full-width @cancel="emit('close')"><template v-if="modal" #footer><div class="form-actions dialog-foot"><button type="button" :disabled="form.processing || confirming" @click="emit('close')">Batal</button><button v-if="step > 0" type="button" :disabled="form.processing || confirming" @click="step--">Kembali</button><button v-if="step < 3" type="button" class="primary" :disabled="form.processing || confirming" @click="step++">Lanjut</button><button v-else type="button" class="primary" :disabled="form.processing || confirming" @click="save">{{ form.processing ? 'Menyimpan...' : 'Simpan Draft & Buat Link' }}</button></div></template><div class="quotation-form">
        <div v-if="!modal" class="pf-formbar"><Link class="kt-btn" :href="appUrl(form.id_penjualan ? `/quotation/batch/${form.id_penjualan}` : '/quotation')">← Kembali</Link><h1>Quotation <span v-if="q.nomor" class="code">{{ q.nomor }}</span><template v-else>baru</template></h1><TutorialButton /><span v-if="q.id" class="badge" :class="`st-${q.status}`">{{ STATUS_LABEL[q.status] ?? q.status }}</span><span v-if="q.id" class="ref">QTN-FR-22</span></div>
        <p v-if="selected && !modal" class="notice">No. Program <span class="code">{{ selected.nomor }}</span> <span class="tag" :class="selected.jenis === 'b2b' ? 'tag-b2b' : 'tag-public'">{{ selected.jenis === 'b2b' ? 'B2B' : 'Public' }}</span> · {{ selected.judul }}<template v-if="q.id && selected.tgl_mulai"> · {{ rangePanjang(selected.tgl_mulai, selected.tgl_selesai) }}</template><template v-if="q.id"> · {{ selected.lokasi || '—' }}</template>{{ selected.closed_date ? ' · Closed' : '' }}</p>
        <section v-if="q.id" class="panel qv-panel" data-tour="status" aria-label="Status quotation">
            <div class="qv-panel-top"><ol class="qv-alur" aria-label="Alur quotation"><li v-for="(st, i) in alur" :key="st" :class="{ done: q.status !== 'batal' && i < alurPos, on: q.status !== 'batal' && i === alurPos }" :aria-current="i === alurPos ? 'step' : undefined">{{ STATUS_LABEL[st] }}</li></ol><span v-if="q.status === 'batal'" class="badge st-batal">Batal</span><span class="badge" :class="linkClosed ? 'st-draft' : 'st-terkirim'">{{ linkClosed ? 'Link tertutup' : 'Link terbuka' }}</span></div>
            <p class="qv-next"><span class="muted">Langkah berikutnya:</span> {{ LANGKAH[q.status] }}</p>
            <div class="qv-panel-row"><div class="qv-btns"><a v-if="q.status !== 'draft'" class="btn btn-primary" :href="appUrl(`/quotation/${q.id}/pdf`)" target="_blank" rel="noopener">Cetak ulang PDF</a><button v-if="q.status === 'draft' && can.edit" type="button" class="btn btn-primary" :disabled="confirming || editing" @click="process('kirim')">Kirim dan Cetak Quotation</button><button v-if="q.status === 'draft' && can.edit && !editing" type="button" class="btn btn-outline" :disabled="confirming" @click="editing = true">Edit</button><button v-if="q.status === 'terkirim' && can.edit" type="button" class="btn btn-accent" :disabled="confirming" @click="action = 'deal'">{{ again ? 'Deal ulang' : 'Deal' }}</button><button v-if="q.id && ['draft', 'terkirim'].includes(q.status) && can.cancel" type="button" class="btn btn-ghost danger" :disabled="confirming" @click="process('batalkan')">Batalkan</button></div>
            <div v-if="q.status !== 'batal'" class="kq"><div class="kq-top"><span><b>{{ terisi }}</b> dari {{ kuotaTotal }} kuota terisi</span><span class="muted">{{ terisi > kuotaTotal ? `${terisi - kuotaTotal} melebihi kuota` : kuotaTotal - terisi ? `${kuotaTotal - terisi} kursi tersisa` : 'kuota penuh' }}</span></div><div class="kq-bar" role="progressbar" aria-label="Kuota terisi" aria-valuemin="0" :aria-valuemax="kuotaTotal" :aria-valuenow="terisi"><span :style="{ width: `${kuotaPct.toFixed(1)}%` }"></span></div></div></div>
        </section>
        <p v-if="error" class="error" role="alert">{{ error }}</p><ul v-if="Object.keys(form.errors).length" class="error" role="alert"><li v-for="e in form.errors" :key="e">{{ e }}</li></ul>
        <nav v-if="q.id" class="tabs qv-tabbar" aria-label="Detail quotation"><button type="button" :class="{ active: !dataTab }" :aria-pressed="!dataTab" @click="dataTab = false">Detail Quotation</button><button type="button" :class="{ active: dataTab }" :aria-pressed="dataTab" @click="dataTab = true">Data Peserta<span class="nav-count">{{ peserta.length }}</span></button></nav>
        <template v-if="dataTab">
            <section v-if="!peserta.length" class="panel dp-empty">
                <h3>{{ b2b ? 'Belum ada daftar peserta dari company' : 'Belum ada calon peserta yang mengisi' }}</h3>
                <p class="muted">{{ b2b ? 'Bagikan link Konfirmasi Kehadiran ke PIC company. PIC mengisi daftar peserta sekaligus.' : 'Bagikan link Konfirmasi Kehadiran ke calon peserta. Peserta hanya dapat masuk lewat link.' }}</p>
                <div v-if="link" class="dp-btns"><button type="button" class="btn btn-primary" @click="copyLink">{{ copied ? 'Tersalin' : 'Salin link' }}</button></div>
                <p v-if="copied" role="status" class="sr-only">Link berhasil disalin.</p>
            </section>
            <template v-else>
                <div class="dp-head"><p class="dp-sum"><b>{{ peserta.length }}</b> peserta, {{ b2b ? 'dikirim PIC company lewat link' : 'semua mengisi lewat link Konfirmasi Kehadiran' }}</p><div class="dp-btns"><a class="btn btn-outline" :href="appUrl(`/quotation/${q.id}/excel`)">Unduh Excel</a><a v-if="terisi" class="btn btn-outline" :href="appUrl(`/quotation/${q.id}/letters`)" target="_blank" rel="noopener">Confirmation Letter (semua)</a><button v-else type="button" class="btn btn-outline" disabled>Confirmation Letter (semua)</button></div></div>
                <section v-if="b2b" class="panel dp-co"><h3>Konfirmasi company</h3><template v-if="companyConfirmation"><p><b>{{ companyConfirmation.nama_perusahaan }}</b> &middot; PIC {{ companyConfirmation.pic_nama }}<template v-if="companyConfirmation.pic_jabatan"> ({{ companyConfirmation.pic_jabatan }})</template> &middot; {{ companyConfirmation.pic_hp || '-' }} &middot; {{ companyConfirmation.pic_email }}<br><span class="muted">{{ companyConfirmation.surat_konfirmasi_nama }} &middot; {{ companyConfirmation.tgl_kirim }}</span></p><div class="dp-btns"><a class="btn btn-outline sm" :href="appUrl(`/documents/company-confirmation/${companyConfirmation.id}?preview=1`)" target="_blank" rel="noopener">Lihat surat konfirmasi</a><a class="btn btn-ghost sm" :href="appUrl(`/documents/company-confirmation/${companyConfirmation.id}`)">Unduh</a></div></template><p v-else class="muted">PIC company belum mengirim daftar peserta. Bagikan link ke PIC company.</p></section>
                <div class="dp-card"><QuotationParticipants v-if="q.id" :rows="peserta" :data-url="appUrl(`/quotation/batch/${q.id_penjualan}/data`)" :filters="{ data: 1, id_quotation: q.id, peserta_status: 'all' }" :manage="can.edit" status-filter detail /></div>
            </template>
        </template>
        <template v-else>
            <div v-if="!editable" class="qv-detail">
                <div class="qtn-grid">
                    <section class="panel"><h2><span class="section-number">1</span> Data Quotation</h2><dl class="qv-kv"><dt>Nomor</dt><dd class="code">{{q.nomor}}</dd><dt>Tanggal</dt><dd>{{tglPanjang(q.tanggal)}}</dd><dt>Berlaku sampai</dt><dd>{{tglPanjang(q.berlaku_sampai)}}</dd><dt>Cara pembayaran</dt><dd>{{bayarTxt}}</dd><dt>Sales</dt><dd>{{sales.find(s=>s.id===Number(q.id_sales))?.nama || '—'}}</dd><dt>Sumber informasi</dt><dd>{{q.sumber_info || '—'}}</dd><dt>Kuota peserta</dt><dd>{{q.kuota_peserta}} peserta</dd><template v-if="b2b"><dt>Skema harga</dt><dd>All-in 1 company</dd></template></dl></section>
                    <section class="panel"><h2><span class="section-number">2</span> Ditujukan Kepada</h2><dl class="qv-kv"><dt>Tujuan</dt><dd><span class="tag" :class="q.tujuan==='company' ? 'tag-b2b' : 'tag-public'">{{q.tujuan==='company'?'Company':'Personal'}}</span></dd><dt>Kepada</dt><dd>{{q.tujuan==='company' ? companies.find(c=>c.id_customer===q.id_customer)?.name_customer || q.kepada_nama || '—' : q.kepada_nama || '—'}}</dd><dt>u.p.</dt><dd>{{q.tujuan==='company' ? q.up_nama || '—' : q.kepada_nama || '—'}}</dd><dt>Alamat</dt><dd class="qv-address">{{q.alamat || '—'}}</dd><template v-if="q.tujuan==='company'"><dt>Perlakuan PPN</dt><dd>{{PERLAKUAN[perlakuanPpn]}}</dd></template></dl></section>
                </div>
                <section class="panel"><h2><span class="section-number">3</span> Link Konfirmasi Kehadiran <button type="button" class="btn btn-ghost sm" @click="dataTab=true">Lihat Data Peserta ({{peserta.length}})</button></h2>
                    <template v-if="link"><p class="lk-lead">Calon peserta mengisi data dirinya sendiri lewat link ini. Link hanya berlaku untuk quotation {{ q.nomor }}.</p>
                    <div class="lk-copy"><label class="sr-only" for="link-url">Link Konfirmasi Kehadiran</label><input id="link-url" class="lk-url" :value="link" readonly /><button type="button" class="btn btn-primary" @click="copyLink">{{ copied ? 'Tersalin' : 'Salin link' }}</button><a data-tour="link" class="btn btn-outline" :href="link" target="_blank" rel="noopener noreferrer">Buka<span class="sr-only"> link di tab baru</span></a></div>
                    <p class="lk-state"><span class="badge" :class="linkClosed ? 'st-draft' : 'st-terkirim'">{{ linkClosed ? 'Link tertutup' : 'Link terbuka' }}</span> <span class="muted">{{ linkClosed || 'Link terbuka' }}</span></p>
                    <p class="lk-h">Isian yang diminta</p>
                    <div class="lk-chips"><span v-for="[key, label] in isianLink" :key="key" class="lk-chip" :class="{ on: key === 'nama' }">{{ label }}{{ key === 'nama' ? ' (wajib)' : '' }}</span></div>
                    <p class="lk-foot muted">Berlaku sampai {{ tglPanjang(q.berlaku_sampai) }} (mengikuti Berlaku sampai quotation)</p></template>
                    <p v-else class="muted">Link konfirmasi belum tersedia.</p></section>
                <section class="panel"><h2><span class="section-number">4</span> Harga <span class="muted">dihitung dari kuota{{ q.status === 'deal' ? '; Nilai Deal dari peserta Deal' : '' }}</span></h2><dl class="qv-totals"><template v-if="b2b"><dt>Harga all-in <span class="muted">paket s.d. {{ q.paket_maks_peserta ?? 25 }} peserta</span></dt><dd>{{rupiah(q.harga_allin)}}</dd><dt>Tambahan peserta <span class="muted">{{ b2bExtra > 0 ? `${b2bExtra} peserta di atas ${q.paket_maks_peserta ?? 25}` : 'tidak ada' }}</span></dt><dd>{{rupiah(b2bExtra * Number(q.tambahan_per_peserta ?? 300000))}}</dd><dt>Diskon <span class="muted">Rupiah total, dari harga paket</span></dt><dd>− {{rupiah(q.diskon)}}</dd></template><template v-else><dt>Subtotal <span class="muted">kuota {{ sum.count }} peserta × {{ rupiah(selected?.harga_jual_peserta ?? 0) }}</span></dt><dd>{{rupiah(sum.count * Number(selected?.harga_jual_peserta ?? 0))}}</dd><dt>Diskon <span class="muted">{{ sum.count }} × {{ rupiah(q.diskon) }}</span></dt><dd>− {{rupiah(sum.count * Number(q.diskon))}}</dd></template><dt>Setelah diskon</dt><dd>{{rupiah(sum.dpp)}}</dd><dt>PPN {{q.is_ppn?'11%':'(tidak dikenakan)'}}</dt><dd>{{rupiah(sum.tax)}}</dd><dt>Biaya lain-lain</dt><dd>{{rupiah(q.biaya_lain)}}</dd><dt class="grand">{{q.status==='deal'?'Grand total · Nilai Deal':'Grand total quotation'}}</dt><dd class="grand">{{rupiah(sum.total)}}</dd></dl></section>
            </div>
            <ol v-if="editable" class="stepper" aria-label="Langkah quotation"><li v-for="(s, i) in steps" :key="s" :class="{ on: step === i, done: step > i }"><button type="button" :aria-current="step === i ? 'step' : undefined" @click="step = i"><span>{{ i + 1 }}</span>{{ s }}</button></li></ol>
            <form v-if="editable" class="stack" novalidate @submit.prevent="step < 3 ? step++ : save()"><fieldset v-show="step === 0" :disabled="form.processing" class="panel"><legend class="sr-only">Penawaran</legend><div class="qtn-form">
                <p class="qtn-h">Data Quotation</p>
                <div class="grid4 qtn-top">
                    <label class="field span3">Penjualan Program <b class="req">*</b><select v-model="form.id_penjualan" required :disabled="!!q.id" @change="program"><option value="">— Pilih Penjualan Program Final —</option><option v-for="p in programs" :key="p.id" :value="p.id">{{ p.nomor }} · {{ p.judul }}</option></select><span class="hint">{{ selected ? `${b2b ? 'B2B' : 'Public'} · ${selected.lokasi || '—'} · harga ${rupiah(selected.harga_jual_peserta)} / peserta · target ${selected.target_peserta} peserta` : 'Hanya Penjualan Program berstatus Final.' }}</span></label>
                    <label class="field">Nomor Quotation<input :value="q.nomor || 'Dibuat otomatis'" readonly /><span class="hint">Otomatis oleh sistem saat Simpan</span></label>
                    <label class="field">Tanggal quotation <b class="req">*</b><input v-model="form.tanggal" type="date" required /></label>
                    <label class="field">Berlaku sampai<input v-model="form.berlaku_sampai" type="date" :min="form.tanggal" required /><span class="hint">Default 31 Desember tahun quotation</span></label>
                    <label class="field">Sales / marketing <b class="req">*</b><select v-model="form.id_sales" required><option value="">— Pilih —</option><option v-for="s in sales" :key="s.id" :value="s.id">{{ s.nama }}</option></select><span class="hint">Dari Master User aktif</span></label>
                    <label class="field">Sumber informasi<select v-model="form.sumber_info"><option value="">—</option><option v-for="s in ['Sales', 'Media sosial', 'Website', 'Referensi / repeat order', 'Lainnya']" :key="s">{{ s }}</option></select></label>
                </div>
                <div class="qtn-grid">
                    <div>
                        <p class="qtn-h">Ditujukan kepada</p>
                        <div class="field"><span class="lbl">Quotation ke <b class="req">*</b></span><div class="static seg"><label><input v-model="form.tujuan" type="radio" value="company" :disabled="b2b" /> Company</label><label :class="{ 'is-dis': b2b }"><input v-model="form.tujuan" type="radio" value="personal" :disabled="b2b" /> Personal</label></div><span class="hint">{{ b2b ? 'Penjualan B2B: quotation ke Company penjualan tersebut.' : 'Company = ditujukan ke perusahaan/instansi · Personal = ditujukan ke perorangan.' }}</span></div>
                        <div v-if="form.tujuan === 'company'" class="grid2">
                            <label class="field span2">Company <b class="req">*</b><select v-model="form.id_customer" :disabled="b2b" required @change="company"><option value="">— Pilih Company —</option><option v-for="c in companies" :key="c.id_customer" :value="c.id_customer">{{ c.name_customer }}</option></select><span class="hint">{{ b2b ? 'Mengikuti Company Penjualan Program B2B' : 'Dari Master Company aktif' }}</span></label>
                            <label class="field span2">u.p.<input v-model="form.up_nama" maxlength="100" placeholder="Nama yang dituju" /></label>
                        </div>
                        <label v-else class="field">Kepada <b class="req">*</b><input v-model="form.kepada_nama" maxlength="100" required placeholder="Nama orang yang dituju" /><span class="hint">Peserta belum ada saat quotation dibuat, jadi nama yang dituju diisi di sini.</span></label>
                        <label class="field">Alamat<textarea v-model="form.alamat" maxlength="3000" rows="2"></textarea><span class="hint">{{ form.tujuan === 'company' ? 'Terisi otomatis dari Company; dapat diubah untuk surat ini.' : 'Isi alamat yang dituju.' }}</span></label>
                    </div>
                    <div>
                        <p class="qtn-h">Kuota peserta</p>
                        <label class="field">Kuota peserta <b class="req">*</b><span class="kq-input"><input v-model="form.kuota_peserta" type="number" :min="b2b ? 10 : 1" step="1" inputmode="numeric" required /><span>peserta</span></span><span class="hint">{{ b2b ? `Penjualan B2B minimal 10 peserta. Harga paket mencakup s.d. ${q.paket_maks_peserta ?? 25} peserta; peserta berikutnya + ${rupiah(q.tambahan_per_peserta ?? 300000)}/orang.` : 'Jumlah kursi yang ditawarkan di quotation ini.' }}</span></label>
                        <dl v-if="selected" class="kq-stat"><div><dt>Target program</dt><dd>{{ selected.target_peserta }}</dd></div><div><dt>Di quotation lain</dt><dd>{{ quotaOther }}</dd></div><div><dt>Sisa target</dt><dd>{{ Math.max(0, selected.target_peserta - quotaOther) }}</dd></div></dl>
                        <p class="kq-info">Peserta tidak dipilih di sini. Setelah disimpan, sistem membuat <b>link Konfirmasi Kehadiran</b>. Calon peserta mengisi data dirinya lewat link; hasilnya masuk tab <b>Data Peserta</b>. Tidak ada tambah peserta manual.</p>
                    </div>
                </div>
                <p class="qtn-h">Harga</p>
                <div class="qtn-grid">
                    <div>
                        <div v-if="b2b" class="field"><span class="lbl">Skema harga</span><div class="static">All-in 1 company</div><span class="hint">Satu harga paket untuk s.d. {{ q.paket_maks_peserta ?? 25 }} peserta; diskon Rupiah total.</span></div>
                        <label v-if="b2b" class="field">Harga all-in (Rp) <b class="req">*</b><MoneyInput v-model="form.harga_allin" required /><span class="hint">Harga paket s.d. {{ q.paket_maks_peserta ?? 25 }} peserta. Ubah sesuai kesepakatan.</span></label>
                        <label class="field">{{ b2b ? 'Diskon (Rp total)' : 'Diskon (Rp / peserta)' }}<MoneyInput v-model="form.diskon" required /><span class="hint">{{ b2b ? 'Rupiah saja, tanpa %. Satu nilai untuk seluruh quotation, maksimal harga all-in.' : 'Rupiah saja, tanpa %. Maksimal harga / peserta.' }}</span></label>
                        <label class="field">Biaya lain-lain (Rp)<MoneyInput v-model="form.biaya_lain" required /><span class="hint">Baris "Biaya Lain-lain" di Penawaran</span></label>
                    </div>
                    <div class="totals">
                        <template v-if="b2b">
                            <div class="tr"><span>Harga all-in <span class="muted">paket s.d. {{ q.paket_maks_peserta ?? 25 }} peserta</span></span><span>{{ rupiah(rupiahInput(form.harga_allin)) }}</span></div>
                            <div class="tr"><span>Tambahan peserta <span class="muted">{{ b2bExtra > 0 ? `${b2bExtra} peserta di atas ${q.paket_maks_peserta ?? 25} × ${rupiah(q.tambahan_per_peserta ?? 300000)}` : 'tidak ada' }}</span></span><span>{{ rupiah(b2bExtra * Number(q.tambahan_per_peserta ?? 300000)) }}</span></div>
                            <div class="tr"><span>Diskon <span class="muted">Rupiah total, dari harga paket</span></span><span>− {{ rupiah(rupiahInput(form.diskon)) }}</span></div>
                        </template>
                        <template v-else>
                            <div class="tr"><span>Harga / peserta <span class="muted">dari Penjualan Program, tidak dapat diubah</span></span><span>{{ selected ? rupiah(selected.harga_jual_peserta) : '—' }}</span></div>
                            <div class="tr"><span>Subtotal <span class="muted">kuota {{ sum.count }} peserta × {{ rupiah(selected?.harga_jual_peserta ?? 0) }}</span></span><span>{{ rupiah(sum.count * Number(selected?.harga_jual_peserta ?? 0)) }}</span></div>
                            <div class="tr"><span>Diskon <span class="muted">{{ sum.count }} × {{ rupiah(rupiahInput(form.diskon)) }}</span></span><span>− {{ rupiah(sum.count * rupiahInput(form.diskon)) }}</span></div>
                        </template>
                        <div class="tr"><span>Setelah diskon</span><span>{{ rupiah(sum.dpp) }}</span></div>
                        <div class="tr"><label class="chk"><input v-model="form.is_ppn" type="checkbox" :true-value="1" :false-value="0" /> PPN 11%</label><span>{{ rupiah(sum.tax) }}</span></div>
                        <div class="tr"><span>Biaya lain-lain</span><span>{{ rupiah(rupiahInput(form.biaya_lain)) }}</span></div>
                        <div class="tr grand"><span><b>Grand total</b></span><span><b>{{ rupiah(sum.total) }}</b></span></div>
                        <div class="tr"><span class="muted">{{ b2b ? 'Setara harga / peserta' : 'Harga / peserta setelah diskon' }}</span><span :class="belowBep ? 'neg' : 'muted'">{{ sum.count > 0 ? rupiah(sum.dpp / sum.count) : '—' }}{{ belowBep ? ` (di bawah BEP harga ${rupiah(selected?.bep_harga)})` : '' }}</span></div>
                    </div>
                </div>
                <p class="qtn-h">Cara pembayaran</p>
                <fieldset class="konf-opsi"><legend class="sr-only">Cara pembayaran</legend>
                    <label class="chk"><input type="radio" name="cara_bayar" value="full" :checked="form.cara_bayar === 'full'" @change="setCara('full')" /> <span><b>Full payment</b> <span class="muted">ditagih satu kali (satu invoice) setelah Deal</span></span></label>
                    <label class="chk"><input type="radio" name="cara_bayar" value="top" :checked="form.cara_bayar === 'top'" @change="setCara('top')" /> <span><b>TOP (termin)</b> <span class="muted">dibagi beberapa termin; satu termin = satu invoice</span></span></label>
                </fieldset>
                <template v-if="form.cara_bayar === 'top'">
                    <p class="hint-line">{{ sum.total > 0 ? `Isi nominal (persen dihitung otomatis) atau persentase (nominal dihitung otomatis) dari Grand total ${rupiah(sum.total)}.` : 'Grand total masih Rp 0: isi kuota peserta dulu agar nominal dapat diisi, atau isi persentase saja.' }} Bila nilai Deal berbeda dari kuota, tiap termin ditagih menurut persentasenya.</p>
                    <div class="table-wrap"><table class="tr-table"><caption class="sr-only">Termin pembayaran TOP</caption>
                        <thead><tr><th class="num">Termin</th><th>Nominal (Rp)</th><th>Persentase</th><th>Keterangan</th><th></th></tr></thead>
                        <tbody><tr v-for="(t, i) in form.termin" :key="i">
                            <td class="num">{{ i + 1 }}</td>
                            <td><MoneyInput :model-value="sum.total > 0 ? terminNominal(t) : ''" :disabled="sum.total <= 0" :aria-label="`Nominal termin ${i + 1}`" @update:model-value="setNominal(i, $event)" /></td>
                            <td><input v-model.number="t.persen" type="number" min="0.01" max="99.99" step="0.01" inputmode="decimal" :aria-label="`Persentase termin ${i + 1}`" /></td>
                            <td><input v-model="t.keterangan" maxlength="150" placeholder="mis. DP setelah PO" :aria-label="`Keterangan termin ${i + 1}`" /></td>
                            <td><button v-if="form.termin.length > 2" type="button" class="btn btn-ghost sm" @click="form.termin.splice(i, 1)">Hapus</button></td>
                        </tr></tbody></table></div>
                    <p class="hint-line" aria-live="polite">Total {{ rupiah(form.termin.reduce((s, t) => s + terminNominal(t), 0)) }} ({{ terminSum }}%) dari Grand total {{ rupiah(sum.total) }}<template v-if="Math.abs(terminSum - 100) > 0.05"> · selisih {{ pct2(100 - terminSum) }}%</template></p>
                    <p v-if="!terminOk" class="error" role="alert">TOP memerlukan minimal 2 termin dengan total 100%.</p>
                    <p v-if="form.errors.termin" class="error" role="alert">{{ form.errors.termin }}</p>
                    <button v-if="editable" type="button" class="btn btn-outline sm" :disabled="form.termin.length >= 12" @click="addTermin">+ Tambah termin</button>
                </template></div></fieldset>
            <section v-show="step === 1" class="quotation-ket" aria-label="Keterangan">
                <template v-if="selected">
                    <div class="ket-tools"><span class="muted">Centang butir yang dicetak di Penawaran. Butir Default ({{ b2b ? 'B2B' : 'Public' }}) sudah tercentang; perubahan hanya untuk quotation ini.</span><button type="button" class="btn btn-ghost sm" @click="resetKet">Kembalikan ke default</button></div>
                    <details v-for="[key, label] in ketCategories" :key="key" class="ket-acc" open>
                        <summary><b>{{ label }}</b><span class="muted">{{ ketChoices.filter(d => d.kategori === key && d.on).length }} dari {{ ketChoices.filter(d => d.kategori === key).length }} dipilih</span></summary>
                        <div class="ket-list">
                            <template v-for="(d, i) in ketChoices" :key="i">
                                <div v-if="d.kategori === key" class="ket-item" :class="{ off: !d.on }">
                                    <template v-if="ketEditing === i"><input v-model="ketText" maxlength="2000" aria-label="Ubah teks" @keydown.enter.prevent="editKet" /><button type="button" @click="editKet">Simpan</button><button type="button" @click="ketEditing = null">Batal</button></template>
                                    <template v-else><label><input v-model="d.on" type="checkbox" /><span>{{ d.teks }} <small v-if="d.manual" class="tag">khusus quotation ini</small></span></label><button type="button" class="btn btn-ghost sm" aria-label="Ubah teks untuk quotation ini" @click="ketEditing = i; ketText = d.teks">Ubah</button></template>
                                </div>
                            </template>
                            <p v-if="!ketChoices.some(d => d.kategori === key)" class="muted">Belum ada butir di master untuk kategori ini.</p>
                            <div class="ket-new"><input v-model="ketNew[key]" maxlength="2000" placeholder="+ Tambah teks lain, lalu Enter" :aria-label="`Tambah teks ${label}`" @keydown.enter.prevent="addKet(key)" /><button type="button" class="btn btn-ghost sm" @click="addKet(key)">Tambah</button></div>
                        </div>
                    </details>
                    <p class="hint-line">Klausul tanda tangan ({{ form.keterangan.filter(d => d.kategori === 'klausul').length }} kalimat) dicetak otomatis dari Template Keterangan; diubah lewat Master Data.</p>
                </template><p v-else class="muted">Pilih Penjualan Program di langkah 1 dahulu; butir diisi sesuai jenis penjualan (Public / B2B).</p>
            </section>
            <section v-show="step === 2" class="quotation-confirmation" aria-label="Formulir Konfirmasi">
                <p v-if="!selected" class="muted">Pilih Penjualan Program di langkah 1 dahulu.</p>
                <template v-else-if="!b2b">
                    <p class="notice">Formulir ini <b>tidak dicetak kosong</b> lagi. Saat quotation disimpan, sistem membuat <b>link Konfirmasi Kehadiran</b> khusus quotation ini; tiap calon peserta mengisi data dirinya sendiri lewat link. Hasilnya masuk ke tab <b>Data Peserta</b> quotation dan menjadi Confirmation Letter.</p>
                    <div class="ket-tools"><span class="muted">Atur isian yang diminta di link. Template default dari Jenis Produk paket; perubahan hanya untuk quotation ini.</span><button type="button" class="btn btn-ghost sm" @click="resetTemplate">Kembalikan ke default</button></div>
                    <div class="kf-grid"><div>
                        <label>Template formulir<select v-model="form.id_template_konfirmasi" required @change="template"><option value="">Pilih template</option><option v-for="t in templates" :key="t.id" :value="t.id">{{ t.nama }}</option></select></label>
                        <h3>Isian <span class="muted">({{ form.konfirmasi_isian.length }})</span></h3>
                        <div class="kf-isian"><label v-for="(label, key) in fields" :key="key" class="kf-chip" :class="{ on: form.konfirmasi_isian.includes(String(key)) }"><input v-model="form.konfirmasi_isian" type="checkbox" :value="key" :disabled="key === 'nama'" />{{ label }}</label><span v-for="x in form.konfirmasi_isian.filter(x => !fields[x])" :key="x" class="kf-chip on extra">{{ x }}<button type="button" :aria-label="`Hapus isian ${x}`" @click="form.konfirmasi_isian = form.konfirmasi_isian.filter(f => f !== x)">&times;</button></span></div>
                        <div class="ket-new"><input v-model="extra" maxlength="80" placeholder="+ Tambah isian lain, lalu Enter" aria-label="Tambah isian" @keydown.enter.prevent="addExtra" /><button type="button" class="btn btn-ghost sm" @click="addExtra">Tambah</button></div>
                        <p class="hint-line">Semua isian diisi calon peserta sendiri di link; tidak diambil dari Master Peserta. Nama Lengkap selalu wajib.</p>
                    </div><div>
                        <label>Pernyataan 1<textarea v-model="form.konfirmasi_pernyataan" rows="4" maxlength="2000" required></textarea></label><p class="hint-line">Penanda: {judul} {tanggal} {waktu} {durasi} {lokasi} ? **teks** = tebal</p>
                        <div class="kf-prev"><span class="muted">Tampil di link dan Confirmation Letter: </span><template v-for="(part, i) in statement" :key="i"><strong v-if="part.startsWith('**') && part.endsWith('**')">{{ part.slice(2, -2) }}</strong><template v-else>{{ part }}</template></template></div>
                        <p class="hint-line">Pernyataan 2 (membaca &amp; menyetujui S&amp;K) dan catatan dicetak dari template; diubah lewat Master Data.</p>
                    </div></div>
                </template>
                <template v-else>
                    <p class="notice">Penjualan B2B dikonfirmasi <b>atas nama company</b>. Link diisi <b>satu kali oleh PIC company</b>: data company dan PIC, lalu daftar peserta. PIC mencetak surat konfirmasi company beserta lampiran daftar peserta, menandatangani dan memberi cap, lalu wajib mengunggahnya.</p>
                    <h3>Diisi PIC company</h3><p class="kf-prev">Nama Perusahaan &middot; Alamat Perusahaan &middot; Nama PIC &middot; Jabatan PIC &middot; Nomor HP / WhatsApp PIC &middot; Email PIC <span class="muted">(terisi awal dari Master Company, dapat diubah PIC)</span></p>
                    <h3>Kolom daftar peserta</h3><p class="hint-line">Company biasanya hanya mengirim nama. Centang kolom lain bila memang dibutuhkan.</p>
                    <div class="kf-isian"><span class="kf-chip on">Nama (wajib)</span><label v-for="k in ['jabatan', 'email', 'wa', 'nik']" :key="k" class="kf-chip" :class="{ on: form.kolom_peserta_b2b.includes(k) }"><input v-model="form.kolom_peserta_b2b" type="checkbox" :value="k" />{{ fields[k] }}</label></div>
                </template>
                <div v-if="selected" class="confirmation-document">
                    <h3>{{ b2b ? 'Surat konfirmasi company' : 'Formulir Konfirmasi' }} <span class="muted">(kosong, contoh)</span></h3>
                    <p v-if="busy" role="status">Menyiapkan preview...</p>
                    <iframe v-else-if="previewDocument && step === 2" class="quotation-document" :srcdoc="previewDocument" sandbox="" title="Contoh Formulir Konfirmasi"></iframe>
                    <button v-else type="button" class="btn btn-outline" @click="preview">Muat ulang preview</button>
                </div>
            </section>
            <section v-show="step === 3" class="quotation-preview" aria-label="Preview quotation">
                <div class="pdf-tools"><span class="badge st-draft">Preview: belum disimpan</span><span class="muted">Penawaran + Syarat &amp; Ketentuan. Formulir Konfirmasi tidak ikut dicetak; calon peserta mengisinya lewat link yang dibuat saat Simpan. Periksa, lalu Simpan atau Kembali.</span></div>
                <p v-if="busy" role="status">Menyiapkan preview...</p>
                <iframe v-else-if="previewDocument && step === 3" class="quotation-document" :srcdoc="previewDocument" sandbox="" title="Preview Penawaran dan Syarat & Ketentuan"></iframe>
                <button v-else type="button" class="btn btn-outline" @click="preview">Muat ulang preview</button>
            </section>
            <div v-if="!modal" class="form-actions"><button v-if="editable && step > 0" type="button" @click="step--">Sebelumnya</button><button v-if="editable && step < 3" type="button" @click="step++">Berikutnya</button><button v-if="editable && step === 3" class="primary" :disabled="form.processing || confirming">{{ form.processing ? 'Menyimpan…' : 'Simpan Draft' }}</button><button v-if="q.id && q.status === 'draft' && can.edit" type="button" :disabled="confirming" @click="process('kirim')">Kirim dan Cetak Quotation</button><button v-if="q.status === 'terkirim' && can.edit" type="button" :disabled="confirming" @click="action = 'deal'">{{ again ? 'Deal ulang' : 'Deal' }}</button><button v-if="q.id && ['draft', 'terkirim'].includes(q.status) && can.cancel" type="button" class="danger" :disabled="confirming" @click="process('batalkan')">Batalkan</button><button v-if="modal" type="button" :disabled="form.processing || confirming" @click="emit('close')">Batal</button><button v-else-if="q.id" type="button" :disabled="form.processing || confirming" @click="form.reset(); form.clearErrors(); step = 0; editing = false">Batal edit</button><Link v-else :href="appUrl(form.id_penjualan ? `/quotation/batch/${form.id_penjualan}` : '/quotation')">Kembali</Link></div></form>
            <form v-if="q.status === 'terkirim' && !b2b && q.tujuan === 'company' && can.edit" class="panel" @submit.prevent="saveQuota"><h2>Tambah kuota Company yang sama</h2><label>Kuota baru<input v-model="quota.kuota_peserta" type="number" :min="Number(q.kuota_peserta) + 1" required /></label><p v-for="e in quota.errors" :key="e" class="error" role="alert">{{ e }}</p><button :disabled="quota.processing || confirming">Tambah kuota</button></form>
            <section v-if="q.status === 'deal'" class="panel"><h2>Sales Order</h2><Link v-if="salesOrder && (($page.props.permissions as Record<string, string[]>).sales_order || []).includes('View')" :href="appUrl(`/sales-order/${salesOrder.id}`)">Lihat Sales Order {{ salesOrder.nomor + (salesOrder.revisi ? `-rev${salesOrder.revisi}` : '') }}</Link><p v-else-if="salesOrder">{{ salesOrder.nomor + (salesOrder.revisi ? `-rev${salesOrder.revisi}` : '') }}</p><h2>Dokumen Deal</h2><p>{{ q.dokumen_keterangan }}</p><p v-for="d in documents" :key="d.id"><a :href="appUrl(`/documents/quotation/${d.id}`)" target="_blank" rel="noopener">{{ d.nama_file }}</a> ({{ Math.ceil(d.ukuran_byte / 1024) }} KB)</p><button v-if="can.edit" type="button" @click="action = 'dokumen'">Tambah dokumen customer</button></section>
        </template>
        <section v-if="q.id && revisions.length" class="panel"><h2>Riwayat revisi Quotation</h2><p v-for="r in revisions" :key="r.revisi"><a :href="appUrl(`/quotation/${q.id}/revisi/${r.revisi}/quotation`)" target="_blank" rel="noopener">Versi {{ r.revisi || 'awal' }}</a> {{ r.created_date }}</p></section>
        <p v-for="e in transition.errors" :key="e" class="error" role="alert">{{ e }}</p>
        <ConfirmDialog v-if="['deal', 'dokumen'].includes(action)" :title="action === 'deal' ? `${again ? 'Deal ulang' : 'Deal'}: ${q.nomor}` : `Tambah dokumen: ${q.nomor}`" size="wide" :confirm-label="action === 'deal' ? (again ? 'Simpan Deal ulang' : 'Tandai Deal') : 'Simpan dokumen'" :busy="deal.processing || confirming" @cancel="action = ''" @confirm="saveDocuments">
            <div class="deal-content">
                <template v-if="action === 'deal'">
                    <p class="deal-intro"><b>{{ q.tujuan === 'company' ? companies.find(c => c.id_customer === q.id_customer)?.name_customer : q.kepada_nama }}</b> ? {{ selected?.judul }} ({{ selected?.nomor }}).</p>
                    <p v-if="again" class="deal-intro">Program di-reschedule ke <b>{{ rangePanjang(selected?.tgl_mulai, selected?.tgl_selesai) }}</b>. Centang peserta yang memilih <b>Pindah jadwal</b> lewat link konfirmasi baru. Peserta Deal lama yang tidak ikut diproses otomatis: <b>Refund</b> bila sudah bayar, invoice <b>dibatalkan</b> bila belum dibayar. Setelah Deal ulang terbit Sales Order <b>-rev{{ q.revisi }}</b>.</p>
                    <p v-else class="deal-intro">Peserta diambil dari <b>Data Peserta</b> quotation ini (isian link). Centang yang <b>deal</b>; yang tidak dicentang menjadi <b>Batal</b>. Setelah Deal, link Konfirmasi Kehadiran ditutup, <b>Sales Order</b> dibuat otomatis, dan peserta Deal disimpan ke <b>Master Peserta</b>.</p>
                    <p v-if="b2b" class="hint-line">Penjualan B2B: minimal 10 peserta deal.</p>
                    <div v-if="dealRows.length" class="ps-pick"><label v-for="p in dealRows" :key="p.id" class="ps-row"><input v-model="deal.peserta" type="checkbox" :value="p.id" :disabled="deal.processing || confirming || (!!p.konfirmasi_ulang && !p.konfirmasi_ulang.diproses_date)" /><span><b>{{ p.nama }}</b> <span class="tag" :class="p.sumber === 'company' ? 'tag-b2b' : 'tag-public'">{{ p.sumber === 'company' ? 'Daftar PIC' : 'Link' }}</span><small class="muted">{{ p.instansi || '-' }} &middot; {{ p.no_wa || p.no_hp || '-' }}</small></span></label></div>
                    <p v-else class="notice">{{ again ? 'Belum ada peserta yang memilih Pindah jadwal.' : 'Belum ada peserta di Data Peserta. Bagikan link Konfirmasi Kehadiran dahulu.' }}</p>
                </template>
                <section v-if="action === 'deal' && again && hasDocuments" class="deal-existing"><h3>Dokumen dari Deal sebelumnya</h3><ul><li v-for="d in documents" :key="d.id"><a :href="appUrl(`/documents/quotation/${d.id}`)" target="_blank" rel="noopener">{{ d.nama_file }}</a><small class="muted"> {{ Math.ceil(d.ukuran_byte / 1024) }} KB</small></li></ul><p class="hint-line">Tetap tersimpan; tidak perlu diunggah ulang.</p></section>
                <DocumentUpload v-model="deal.dokumen" :disabled="deal.processing || confirming" :optional="action === 'deal' && again && hasDocuments" />
                <label class="deal-note">Keterangan dokumen<input v-model="deal.dokumen_keterangan" maxlength="200" placeholder="mis. PO No. 118/PO/X/2026" :disabled="deal.processing || confirming" /></label>
                <p v-for="e in deal.errors" :key="e" class="error" role="alert">{{ e }}</p>
                <progress v-if="deal.progress" :value="deal.progress.percentage" max="100" aria-label="Progres unggahan"></progress>
            </div>
        </ConfirmDialog>
    </div></component>
</template>

<style scoped>
.deal-intro { margin: 0 0 12px; line-height: 1.6; }
.ps-pick { border: 1px solid var(--line); border-radius: 8px; margin: 16px 0 20px; max-height: 280px; overflow-y: auto; }
.ps-row { display: flex; align-items: flex-start; gap: 10px; padding: 12px 14px; border-bottom: 1px solid var(--line); cursor: pointer; }
.ps-row:last-child { border-bottom: 0; }
.ps-row input { width: 18px; height: 18px; min-height: 18px; flex: 0 0 18px; margin: 2px 0 0; padding: 0; }
.ps-row > span { min-width: 0; }
.ps-row small { display: block; margin-top: 4px; }
.deal-existing { margin-bottom: 16px; }
.deal-existing h3 { font-size: 14px; }
.deal-note { display: flex; flex-direction: column; gap: 6px; }

.dp-empty { text-align: center; padding: 32px 20px; }
.dp-empty h3 { margin: 0 0 6px; font-size: 16px; }
.dp-empty p { margin: 0 0 16px; }
.dp-empty .dp-btns { justify-content: center; }
.dp-head { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 16px; margin: 18px 0 14px; }
.dp-sum { margin: 0; font-size: 13.5px; }
.dp-btns { display: flex; flex-wrap: wrap; gap: 8px; }
.dp-card { border: 1px solid var(--line); border-radius: 10px; background: #fff; overflow: hidden; }
.dp-co { margin-bottom: 12px; }
.dp-co h3 { margin-top: 0; font-size: 14px; }

:global(.dialog.xwide:has(.quotation-form)) { max-width: 1080px; }
.stepper { display: flex; flex-wrap: wrap; gap: 6px; padding: 0; margin: 0 0 20px; list-style: none; border-bottom: 1px solid var(--line); }
.stepper button { display: flex; align-items: center; gap: 8px; padding: 10px 12px; border: 0; border-bottom: 3px solid transparent; border-radius: 0; background: transparent; color: var(--ink-50); }
.stepper button span { display: inline-grid; place-items: center; width: 22px; height: 22px; border-radius: 50%; background: #eef1f6; font-size: 12px; }
.stepper .on button { color: var(--navy); border-bottom-color: var(--orange); }
.stepper .on button span { background: var(--navy); color: #fff; }
.stepper .done button span { background: var(--navy-50); color: var(--navy); }
.ket-tools, .pdf-tools { display: flex; align-items: center; justify-content: space-between; gap: 12px; margin-bottom: 14px; }
.ket-tools > span { flex: 1; }
.ket-tools > button { flex-shrink: 0; }
.ket-acc { border: 1px solid var(--line); border-radius: 8px; margin-bottom: 10px; background: #fff; }
.ket-acc > summary { display: flex; justify-content: space-between; gap: 10px; padding: 10px 14px; cursor: pointer; list-style: none; color: var(--navy); }
.ket-acc > summary::-webkit-details-marker { display: none; }
.ket-acc > summary::before { content: '\25b8'; }
.ket-acc[open] > summary::before { content: '\25be'; }
.ket-acc > summary b { flex: 1; }
.ket-list { padding: 2px 14px 12px; border-top: 1px solid var(--line); }
.ket-item { display: flex; align-items: flex-start; gap: 8px; padding: 8px 0; border-bottom: 1px dashed var(--line); }
.ket-item label { flex: 1; display: flex; gap: 8px; align-items: flex-start; cursor: pointer; }
.ket-item > input { flex: 1; min-width: 0; }
.ket-item.off label { color: var(--ink-50); }
.ket-new { display: flex; gap: 6px; margin-top: 8px; }
.ket-new input { flex: 1; min-width: 0; }
.kf-grid { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 1fr); gap: 28px; }
.kf-grid h3, .quotation-confirmation h3 { font-size: 14px; margin: 16px 0 10px; }
.kf-isian { display: flex; flex-wrap: wrap; gap: 8px; margin-bottom: 8px; }
.kf-chip { display: inline-flex; align-items: center; gap: 6px; padding: 5px 10px; border: 1px solid var(--line); border-radius: 99px; font-size: 12.5px; cursor: pointer; background: #fff; color: var(--ink-50); }
.kf-chip.on { background: var(--navy-50); color: var(--navy); font-weight: 600; }
.kf-chip.extra { border-style: dashed; }
.kf-chip button { border: 0; background: transparent; padding: 0 2px; min-height: 24px; }
.kf-prev { background: #f6f8fb; border: 1px solid var(--line); border-radius: 8px; padding: 10px 12px; font-size: 13px; line-height: 1.5; white-space: pre-line; }
.pdf-tools { justify-content: flex-start; flex-wrap: wrap; }
.quotation-document { display: block; width: 100%; height: 66vh; border: 0; background: #e9ecf2; }
@media (max-width: 860px) { .kf-grid { grid-template-columns: minmax(0, 1fr); gap: 16px; } }
@media (max-width: 600px) { .ket-tools { flex-wrap: wrap; } .ket-item { flex-wrap: wrap; } .ket-item label { flex-basis: 100%; } }

.quotation-form { min-width: 0; }
.qv-panel-row, .qv-btns { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
.qv-panel-row { justify-content: space-between; }
.qv-panel-row .kq { flex: 0 1 360px; margin: 0; }
.qv-tabbar { margin: 16px 0; padding: 0; background: transparent; border-radius: 0; }
.qv-tabbar button { border-radius: 8px 8px 0 0; }
.qtn-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 20px; }
.qv-detail .panel { margin-top: 0; }
.qv-detail > .panel { margin-top: 16px; }
.qv-detail h2 { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-top: 0; }
.qv-detail .section-number { margin-right: 0; }
.qv-detail .qv-kv { grid-template-columns: max-content minmax(0, 1fr); gap: 6px 16px; margin: 0; }
.qv-kv dt, .qv-kv dd { padding: 0; border: 0; font-size: 13px; }
.qv-kv dd { font-weight: 600; }
.qv-address { white-space: pre-line; }
.link-box { padding: 10px 12px; border: 1px solid var(--line); border-radius: 8px; background: var(--navy-50); overflow-wrap: anywhere; }
.qv-detail .qv-totals { grid-template-columns: minmax(0, 1fr) auto; gap: 0; margin: 0; }
.qv-totals dd { text-align: right; font-variant-numeric: tabular-nums; }
.qv-totals .grand { background: var(--navy-50); font-weight: 700; color: var(--navy); border-top: 2px solid var(--line); }
.quotation-form :deep(input[type=radio]), .quotation-form :deep(input[type=checkbox]) { width: 18px; height: 18px; min-height: 18px; flex: 0 0 18px; margin: 0; padding: 0; }
.quotation-form .chk { display: flex; gap: 10px; align-items: flex-start; }
.quotation-form .chk > span { min-width: 0; }
.quotation-form .form-grid { align-items: start; }
@media (max-width: 860px) { .qtn-grid { grid-template-columns: 1fr; gap: 16px; } }
@media (max-width: 600px) { .qv-detail .qv-kv { grid-template-columns: 1fr; gap: 4px; } .qv-kv dd { margin-bottom: 8px; } .qv-panel-row .kq { flex-basis: 100%; } }
</style>


