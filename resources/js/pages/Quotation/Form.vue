<script setup lang="ts">
import TutorialButton from "../../components/TutorialButton.vue";
import { appUrl } from '../../composables/appUrl';
import MoneyInput from "../../components/MoneyInput.vue";
import { computed, ref, watch } from 'vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import QuotationParticipants, { type Participant } from '../../components/QuotationParticipants.vue';
import { rupiah, rupiahInput } from '../../composables/costing';
import { postPdf } from '../../composables/pdf';
import { useConfirmedMutation } from '../../composables/confirmedMutation';
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;
type Program = { id: number; nomor: string; judul: string; jenis: string; id_customer: string | null; harga_jual_peserta: string; target_peserta: number; closed_date: string | null; id_jenis_produk?: number; bep_harga: number };
type Detail = { kategori: string; teks: string };
type Quote = { revisi?: number; id: number | null; nomor: string; status: string; id_penjualan: number | ''; tanggal: string; berlaku_sampai: string; id_sales: number | ''; sumber_info: string; tujuan: string; id_customer: string | null; kepada_nama: string | null; up_nama: string | null; alamat: string | null; kuota_peserta: number; harga_allin: string | number; diskon: string | number; biaya_lain: string | number; cara_bayar: 'full' | 'top'; termin: { persen: number | string; keterangan: string }[]; is_ppn: number | boolean; id_template_konfirmasi: number | ''; konfirmasi_isian: string[]; konfirmasi_pernyataan: string; kolom_peserta_b2b: string[]; keterangan: Detail[]; paket_maks_peserta?: number; tambahan_per_peserta?: string; dokumen_keterangan?: string | null };
type Template = { id: number; nama: string; id_jenis_produk: number | null; isian: string[]; pernyataan: string };
const props = defineProps<{ quotation: Quote; revisions: {revisi:number;created_date:string}[]; salesOrder: { id: number; nomor: string; revisi:number; status?: string } | null; redeal?: { kode: string; teks: string; sub: string | null } | null; needsRedeal?: boolean; hasDocuments?: boolean; programs: Program[]; companies: { id_customer: string; name_customer: string; pic_name: string; address_office: string; kota: string }[]; sales: { id: number; nama: string }[]; templates: Template[]; fields: Record<string, string>; keteranganOptions: (Detail & { id: number; berlaku_untuk: string; is_default: number })[]; peserta: Participant[]; documents: { id: number; nama_file: string; ukuran_byte: number }[]; companyConfirmation: { id: number; nama_perusahaan: string; pic_nama: string; pic_jabatan: string; pic_hp: string; pic_email: string; surat_konfirmasi_nama: string; tgl_kirim: string } | null; link: string | null; linkClosed: string | null; calc: { grand_total: string; dpp: string; ppn: string; n_peserta: number } | null; can: { edit: boolean; cancel: boolean }; quotaOther: number }>();
const q = props.quotation;
const form = useForm({ ...q, harga_allin: Math.round(Number(q.harga_allin)) as number | string, diskon: Math.round(Number(q.diskon)) as number | string, biaya_lain: Math.round(Number(q.biaya_lain)) as number | string, konfirmasi_isian: [...q.konfirmasi_isian], kolom_peserta_b2b: [...q.kolom_peserta_b2b], cara_bayar: q.cara_bayar ?? 'full', termin: (q.termin ?? []).map((t) => ({ ...t })), keterangan: q.keterangan.map((d) => ({ kategori: d.kategori, teks: d.teks })) });
const selected = computed(() => props.programs.find((p) => p.id === Number(form.id_penjualan)));
const b2b = computed(() => selected.value?.jenis === 'b2b');
const editable = q.status === 'draft' && props.can.edit;
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
const steps = ['Penawaran', 'Keterangan', 'Formulir Konfirmasi', 'Preview']; const step = ref(0);
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
async function preview() { busy.value = true; error.value = ''; try { await postPdf('/quotation/preview', form.data()); } catch (e) { error.value = (e as Error).message; } finally { busy.value = false; } }
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
function files(e: Event) { deal.dokumen.push(...Array.from((e.target as HTMLInputElement).files ?? [])); (e.target as HTMLInputElement).value = ''; }
function saveDocuments() { return confirmation.form(deal, 'post', `/quotation/${q.id}/${q.status === 'deal' ? 'dokumen' : 'deal'}`, { title: q.status === 'deal' ? `Simpan dokumen ${q.nomor}?` : `${again ? 'Deal ulang' : 'Tandai'} ${q.nomor}${again ? '?' : ' Deal?'}`, text: q.status === 'deal' ? 'Dokumen ditambahkan ke riwayat customer.' : `${deal.peserta.length} peserta dipilih. Peserta lain menjadi Batal; Deal membentuk Master Peserta dan Sales Order.`, confirmLabel: q.status === 'deal' ? 'Ya, simpan' : 'Ya, Deal' }, { forceFormData: true, preserveState: 'errors', onSuccess: () => { action.value = ''; deal.reset('dokumen'); } }); }
function saveQuota() { return confirmation.form(quota, 'post', `/quotation/${q.id}/kuota`, { title: `Tambah kuota ${q.nomor}?`, text: `Kuota baru: ${quota.kuota_peserta} peserta.` }, { preserveScroll: true }); }
const sum = computed(() => {
    if (!editable && props.calc) return { count: props.calc.n_peserta, dpp: Number(props.calc.dpp), tax: Number(props.calc.ppn), total: Number(props.calc.grand_total) };
    const count = q.status === 'deal' ? (props.calc?.n_peserta ?? 0) : Number(form.kuota_peserta);
    const base = b2b.value ? rupiahInput(form.harga_allin) + Math.max(0, count - (q.paket_maks_peserta ?? 25)) * Number(q.tambahan_per_peserta ?? 300000) : count * Number(selected.value?.harga_jual_peserta ?? 0);
    const dpp = base - rupiahInput(form.diskon) * (b2b.value ? 1 : count); const tax = form.is_ppn ? Math.round(dpp * 11) / 100 : 0;
    return { count, dpp, tax, total: dpp + tax + rupiahInput(form.biaya_lain) };
});
// TOP (QTN-FR-44): termin diisi rupiah (persen otomatis) atau persen (rupiah otomatis) terhadap Grand total quotation.
const TOP_DEFAULT = () => [{ persen: 50, keterangan: 'Uang muka (DP) setelah quotation ditandatangani / PO' }, { persen: 50, keterangan: 'Pelunasan sebelum acara' }];
function setCara(value: 'full' | 'top') { form.cara_bayar = value; if (value === 'top' && form.termin.length < 2) form.termin = TOP_DEFAULT(); }
const pct2 = (n: number) => Math.round(n * 100) / 100;
const terminSum = computed(() => pct2(form.termin.reduce((s, t) => s + (Number(t.persen) || 0), 0)));
const terminNominal = (t: { persen: number | string }) => Math.round(sum.value.total * (Number(t.persen) || 0) / 100);
const terminOk = computed(() => form.cara_bayar !== 'top' || (form.termin.length >= 2 && Math.abs(terminSum.value - 100) <= 0.05));
function setNominal(i: number, value: string | number) { const n = Number(value) || 0; if (sum.value.total > 0) form.termin[i].persen = pct2(n / sum.value.total * 100); }
function addTermin() { if (form.termin.length < 12) form.termin.push({ persen: '', keterangan: '' }); }
function addKeterangan(d: Detail) { form.keterangan.push({ kategori: d.kategori, teks: d.teks }); }
const extra = ref('');
</script>
<template>
    <Head :title="q.nomor || 'Buat Quotation'" /><AppLayout title="Quotation" full-width><div class="quotation-form">
        <div class="pf-formbar"><Link class="kt-btn" :href="appUrl(form.id_penjualan ? `/quotation/batch/${form.id_penjualan}` : '/quotation')">← Kembali</Link><h1>Quotation <span v-if="q.nomor" class="code">{{ q.nomor }}</span><template v-else>baru</template></h1><TutorialButton /><span v-if="q.id" class="badge" :class="`st-${q.status}`">{{ STATUS_LABEL[q.status] ?? q.status }}</span></div>
        <p v-if="selected" class="notice">No. Program <span class="code">{{ selected.nomor }}</span> <span class="tag" :class="selected.jenis === 'b2b' ? 'tag-b2b' : 'tag-public'">{{ selected.jenis === 'b2b' ? 'B2B' : 'Public' }}</span> · {{ selected.judul }}{{ selected.closed_date ? ' · Closed' : '' }}</p>
        <section v-if="q.id" class="panel qv-panel" data-tour="status" aria-label="Status quotation">
            <div class="qv-panel-top"><ol class="qv-alur" aria-label="Alur quotation"><li v-for="(st, i) in alur" :key="st" :class="{ done: q.status !== 'batal' && i < alurPos, on: q.status !== 'batal' && i === alurPos }" :aria-current="i === alurPos ? 'step' : undefined">{{ STATUS_LABEL[st] }}</li></ol><span v-if="q.status === 'batal'" class="badge st-batal">Batal</span><span class="badge" :class="linkClosed ? 'st-draft' : 'st-terkirim'">{{ linkClosed ? 'Link tertutup' : 'Link terbuka' }}</span></div>
            <p class="qv-next"><span class="muted">Langkah berikutnya:</span> {{ LANGKAH[q.status] }}</p>
            <div class="qv-panel-row"><div class="qv-btns"><a class="btn btn-primary" :href="appUrl(`/quotation/${q.id}/pdf`)" target="_blank" rel="noopener">Cetak ulang PDF</a><button v-if="q.status === 'draft' && can.edit" type="button" class="btn btn-outline" :disabled="confirming" @click="process('kirim')">Kirim dan Cetak Quotation</button><button v-if="q.status === 'terkirim' && can.edit" type="button" class="btn btn-accent" :disabled="confirming" @click="action = 'deal'">{{ again ? 'Deal ulang' : 'Deal' }}</button><button v-if="['draft', 'terkirim'].includes(q.status) && can.cancel" type="button" class="btn btn-ghost danger" :disabled="confirming" @click="process('batalkan')">Batalkan</button></div>
            <div v-if="q.status !== 'batal'" class="kq"><div class="kq-top"><span><b>{{ terisi }}</b> dari {{ kuotaTotal }} kuota terisi</span><span class="muted">{{ terisi > kuotaTotal ? `${terisi - kuotaTotal} melebihi kuota` : kuotaTotal - terisi ? `${kuotaTotal - terisi} kursi tersisa` : 'kuota penuh' }}</span></div><div class="kq-bar" role="progressbar" aria-label="Kuota terisi" aria-valuemin="0" :aria-valuemax="kuotaTotal" :aria-valuenow="terisi"><span :style="{ width: `${kuotaPct.toFixed(1)}%` }"></span></div></div></div>
        </section>
        <p v-if="error" class="error" role="alert">{{ error }}</p><ul v-if="Object.keys(form.errors).length" class="error" role="alert"><li v-for="e in form.errors" :key="e">{{ e }}</li></ul>
        <nav v-if="q.id" class="tabs qv-tabbar" aria-label="Detail quotation"><button type="button" :class="{ active: !dataTab }" :aria-pressed="!dataTab" @click="dataTab = false">Detail Quotation</button><button type="button" :class="{ active: dataTab }" :aria-pressed="dataTab" @click="dataTab = true">Data Peserta<span class="nav-count">{{ peserta.length }}</span></button></nav>
        <template v-if="dataTab">
            <section v-if="link" class="panel"><h2>Link Konfirmasi Kehadiran</h2><p>{{ linkClosed || 'Link terbuka' }}</p><a data-tour="link" :href="link" target="_blank" rel="noopener noreferrer">{{ link }}</a><p class="muted">Bagikan link kepada calon peserta / PIC Company. Peserta hanya masuk melalui link ini.</p></section>
            <div class="form-actions"><a :href="appUrl(`/quotation/${q.id}/excel`)">Unduh Excel</a><a v-if="peserta.some((p) => p.status !== 'batal')" :href="appUrl(`/quotation/${q.id}/letters`)" target="_blank" rel="noopener">Confirmation Letter (semua aktif)</a></div>
            <section v-if="b2b" class="panel"><h2>Konfirmasi Company</h2><template v-if="companyConfirmation"><p>{{ companyConfirmation.nama_perusahaan }} · {{ companyConfirmation.pic_nama }} · {{ companyConfirmation.pic_jabatan }}</p><p>{{ companyConfirmation.pic_hp }} · {{ companyConfirmation.pic_email }} · {{ companyConfirmation.tgl_kirim }}</p><a :href="appUrl(`/documents/company-confirmation/${companyConfirmation.id}?preview=1`)" target="_blank" rel="noopener">Lihat {{ companyConfirmation.surat_konfirmasi_nama }}</a></template><p v-else>PIC Company belum mengirim daftar peserta.</p></section>
            <QuotationParticipants v-if="q.id" :rows="peserta" :data-url="appUrl(`/quotation/batch/${q.id_penjualan}/data`)" :filters="{ data: 1, id_quotation: q.id, peserta_status: 'all' }" :manage="can.edit" status-filter />
        </template>
        <template v-else>
            <div v-if="!editable" class="qv-detail">
                <div class="qtn-grid">
                    <section class="panel"><h2><span class="section-number">1</span> Data Quotation</h2><dl class="qv-kv"><dt>Nomor</dt><dd class="code">{{q.nomor}}</dd><dt>Tanggal</dt><dd>{{q.tanggal}}</dd><dt>Berlaku sampai</dt><dd>{{q.berlaku_sampai}}</dd><dt>Cara pembayaran</dt><dd>{{q.cara_bayar==='top' ? `TOP ${q.termin.length} termin` : 'Full payment'}}<ol v-if="q.cara_bayar==='top'"><li v-for="(t,i) in q.termin" :key="i">{{t.persen}}% · {{t.keterangan}}</li></ol></dd><dt>Sales</dt><dd>{{sales.find(s=>s.id===Number(q.id_sales))?.nama || '—'}}</dd><dt>Sumber informasi</dt><dd>{{q.sumber_info || '—'}}</dd><dt>Kuota peserta</dt><dd>{{q.kuota_peserta}} peserta</dd><template v-if="b2b"><dt>Skema harga</dt><dd>All-in 1 company</dd></template></dl></section>
                    <section class="panel"><h2><span class="section-number">2</span> Ditujukan Kepada</h2><dl class="qv-kv"><dt>Tujuan</dt><dd>{{q.tujuan==='company'?'Company':'Personal'}}</dd><dt>Kepada</dt><dd>{{q.tujuan==='company' ? companies.find(c=>c.id_customer===q.id_customer)?.name_customer || q.kepada_nama || '—' : q.kepada_nama || '—'}}</dd><dt>u.p.</dt><dd>{{q.tujuan==='company' ? q.up_nama || '—' : q.kepada_nama || '—'}}</dd><dt>Alamat</dt><dd class="qv-address">{{q.alamat || '—'}}</dd></dl></section>
                </div>
                <section class="panel"><h2><span class="section-number">3</span> Link Konfirmasi Kehadiran <button type="button" class="btn btn-ghost sm" @click="dataTab=true">Lihat Data Peserta ({{peserta.length}})</button></h2><p>{{linkClosed || 'Link terbuka'}}</p><div v-if="link" class="link-box"><a data-tour="link" :href="link" target="_blank" rel="noopener noreferrer">{{link}}</a></div><p v-else class="muted">Link konfirmasi belum tersedia.</p></section>
                <section class="panel"><h2><span class="section-number">4</span> Harga</h2><dl class="qv-totals"><dt>{{b2b?'Diskon paket':'Diskon per peserta'}}</dt><dd>{{rupiah(q.diskon)}}</dd><dt>Setelah diskon</dt><dd>{{rupiah(sum.dpp)}}</dd><dt>PPN {{q.is_ppn?'11%':'(tidak dikenakan)'}}</dt><dd>{{rupiah(sum.tax)}}</dd><dt>Biaya lain-lain</dt><dd>{{rupiah(q.biaya_lain)}}</dd><dt class="grand">{{q.status==='deal'?'Grand total · Nilai Deal':'Grand total quotation'}}</dt><dd class="grand">{{rupiah(sum.total)}}</dd></dl></section>
            </div>
            <nav v-if="editable" class="tabs quotation-steps" aria-label="Langkah quotation"><button v-for="(s, i) in steps" :key="s" type="button" :aria-current="step === i ? 'step' : undefined" @click="step = i">{{ i + 1 }}. {{ s }}</button></nav>
            <form v-if="editable" class="stack" novalidate @submit.prevent="save"><fieldset v-show="step === 0" :disabled="form.processing" class="panel"><legend><span class="section-number">1</span> Penawaran</legend><div class="form-grid">
                <label>Penjualan Program<select v-model="form.id_penjualan" required :disabled="!!q.id" @change="program"><option value="">— Pilih Penjualan Program Final —</option><option v-for="p in programs" :key="p.id" :value="p.id">{{ p.nomor }} · {{ p.judul }}</option></select></label>
                <label>Tanggal quotation<input v-model="form.tanggal" type="date" required /></label><label>Berlaku sampai<input v-model="form.berlaku_sampai" type="date" :min="form.tanggal" required /></label>
                <label>Sales / marketing<select v-model="form.id_sales" required><option value="">— Pilih —</option><option v-for="s in sales" :key="s.id" :value="s.id">{{ s.nama }}</option></select></label>
                <label>Sumber informasi<select v-model="form.sumber_info"><option v-for="s in ['Sales', 'Media sosial', 'Website', 'Referensi / repeat order', 'Lainnya']" :key="s">{{ s }}</option></select></label>
                <label>Quotation ke<select v-model="form.tujuan" :disabled="b2b"><option value="company">Company</option><option value="personal">Personal</option></select></label>
                <label v-if="form.tujuan === 'company'">Company<select v-model="form.id_customer" :disabled="b2b" required @change="company"><option value="">— Pilih Company —</option><option v-for="c in companies" :key="c.id_customer" :value="c.id_customer">{{ c.name_customer }}</option></select></label>
                <label v-else>Kepada<input v-model="form.kepada_nama" maxlength="100" required /></label><label v-if="form.tujuan === 'company'">u.p.<input v-model="form.up_nama" maxlength="100" /></label><label>Alamat<textarea v-model="form.alamat" maxlength="3000" rows="3"></textarea></label>
                <label>Kuota peserta<input v-model="form.kuota_peserta" type="number" :min="b2b ? 10 : 1" required /></label><label v-if="b2b">Harga paket s.d. {{ q.paket_maks_peserta ?? 25 }} peserta (Rp)<MoneyInput v-model="form.harga_allin" required /></label><p v-else>Harga / peserta: {{ rupiah(selected?.harga_jual_peserta) }}</p><label>{{ b2b ? 'Diskon paket' : 'Diskon per peserta' }} (Rp)<MoneyInput v-model="form.diskon" required /></label><label>Biaya lain-lain (Rp)<MoneyInput v-model="form.biaya_lain" required /></label><label><input v-model="form.is_ppn" type="checkbox" :true-value="1" :false-value="0" /> PPN 11%</label>
            </div><p v-if="selected">Target {{ selected.target_peserta }} · Kuota quotation lain {{ quotaOther }} · Sisa target {{ Math.max(0, selected.target_peserta - quotaOther - Number(form.kuota_peserta)) }}</p><p v-if="selected && sum.count > 0 && sum.dpp / sum.count < selected.bep_harga" class="notice">Harga setelah diskon {{ rupiah(sum.dpp / sum.count) }} / peserta di bawah BEP harga {{ rupiah(selected.bep_harga) }}. Quotation tetap dapat disimpan.</p><p v-if="b2b" class="notice">Paket tetap untuk peserta hingga {{ q.paket_maks_peserta ?? 25 }}. Peserta tambahan {{ rupiah(q.tambahan_per_peserta ?? 300000) }} / orang. B2B minimal 10 peserta.</p>
                <fieldset class="konf-opsi"><legend>Cara pembayaran</legend>
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
                </template></fieldset>
            <fieldset v-show="!editable || step === 1" :disabled="!editable || form.processing" class="panel"><legend><span class="section-number">2</span> Keterangan</legend><details v-if="editable"><summary>Tambah dari Template Keterangan</summary><button v-for="d in keteranganOptions.filter((d) => ['semua', selected?.jenis].includes(d.berlaku_untuk))" :key="d.id" type="button" @click="addKeterangan(d)">{{ d.kategori }}: {{ d.teks }}</button></details><div v-for="(d, i) in form.keterangan" :key="i" class="form-grid"><label>Kategori<select v-model="d.kategori"><option v-for="k in ['syarat', 'termasuk', 'tidak', 'dokumen', 'klausul']" :key="k">{{ k }}</option></select></label><label>Teks<textarea v-model="d.teks" :readonly="d.kategori === 'klausul'" maxlength="2000" rows="3" required></textarea></label><button v-if="editable" type="button" class="danger" @click="form.keterangan.splice(i, 1)">Hapus</button></div><button v-if="editable" type="button" @click="addKeterangan({ kategori: 'syarat', teks: '' })">Tambah keterangan</button><p v-if="!form.keterangan.length" class="muted">Belum ada keterangan.</p></fieldset>
            <fieldset v-show="!editable || step === 2" :disabled="!editable || form.processing" class="panel"><legend><span class="section-number">3</span> Formulir Konfirmasi</legend><template v-if="!b2b"><label>Template aktif<select v-model="form.id_template_konfirmasi" required @change="template"><option value="">Pilih template</option><option v-for="t in templates" :key="t.id" :value="t.id">{{ t.nama }}</option></select></label><div class="form-grid"><label v-for="(label, key) in fields" :key="key"><input v-model="form.konfirmasi_isian" type="checkbox" :value="key" :disabled="key === 'nama'" /> {{ label }}</label></div><label>Isian tambahan<input v-model="extra" maxlength="80" /></label><button v-if="editable" type="button" @click="extra.trim() && form.konfirmasi_isian.push(extra.trim()); extra = ''">Tambah isian</button><p v-for="x in form.konfirmasi_isian.filter((x) => !fields[x])" :key="x">{{ x }} <button v-if="editable" type="button" @click="form.konfirmasi_isian = form.konfirmasi_isian.filter((f) => f !== x)">Hapus</button></p></template><template v-else><p>Konfirmasi satu kali oleh PIC Company. Nama peserta wajib.</p><label v-for="k in ['jabatan', 'email', 'wa', 'nik']" :key="k"><input v-model="form.kolom_peserta_b2b" type="checkbox" :value="k" /> {{ fields[k] }}</label></template><label>Pernyataan persetujuan<textarea v-model="form.konfirmasi_pernyataan" rows="4" maxlength="2000" required></textarea></label></fieldset>
            <section v-show="!editable || step === 3" class="panel"><h2><span class="section-number">4</span> Preview</h2><p>{{ sum.count }} peserta · DPP {{ rupiah(sum.dpp) }} · PPN {{ rupiah(sum.tax) }} · Total {{ rupiah(sum.total) }}</p><p class="muted">Nilai sebelum Deal berdasarkan kuota. Nilai Deal berdasarkan peserta terpilih. Server menghitung ulang nilai resmi.</p><button v-if="editable" type="button" :disabled="busy" @click="preview">{{ busy ? 'Menyiapkan PDF…' : 'Preview PDF sebelum simpan' }}</button><a v-else :href="appUrl(`/quotation/${q.id}/pdf`)" target="_blank" rel="noopener">Cetak ulang PDF</a></section>
            <div class="form-actions"><button v-if="editable && step > 0" type="button" @click="step--">Sebelumnya</button><button v-if="editable && step < 3" type="button" @click="step++">Berikutnya</button><button v-if="editable && step === 3" class="primary" :disabled="form.processing || confirming">{{ form.processing ? 'Menyimpan…' : 'Simpan Draft' }}</button><button v-if="q.id && q.status === 'draft' && can.edit" type="button" :disabled="confirming" @click="process('kirim')">Kirim dan Cetak Quotation</button><button v-if="q.status === 'terkirim' && can.edit" type="button" :disabled="confirming" @click="action = 'deal'">{{ again ? 'Deal ulang' : 'Deal' }}</button><button v-if="['draft', 'terkirim'].includes(q.status) && can.cancel" type="button" class="danger" :disabled="confirming" @click="process('batalkan')">Batalkan</button><Link :href="appUrl(form.id_penjualan ? `/quotation/batch/${form.id_penjualan}` : '/quotation')">Kembali</Link></div></form>
            <form v-if="q.status === 'terkirim' && !b2b && q.tujuan === 'company' && can.edit" class="panel" @submit.prevent="saveQuota"><h2>Tambah kuota Company yang sama</h2><label>Kuota baru<input v-model="quota.kuota_peserta" type="number" :min="Number(q.kuota_peserta) + 1" required /></label><p v-for="e in quota.errors" :key="e" class="error" role="alert">{{ e }}</p><button :disabled="quota.processing || confirming">Tambah kuota</button></form>
            <section v-if="q.status === 'deal'" class="panel"><h2>Sales Order</h2><Link v-if="salesOrder && (($page.props.permissions as Record<string, string[]>).sales_order || []).includes('View')" :href="appUrl(`/sales-order/${salesOrder.id}`)">Lihat Sales Order {{ salesOrder.nomor + (salesOrder.revisi ? `-rev${salesOrder.revisi}` : '') }}</Link><p v-else-if="salesOrder">{{ salesOrder.nomor + (salesOrder.revisi ? `-rev${salesOrder.revisi}` : '') }}</p><h2>Dokumen Deal</h2><p>{{ q.dokumen_keterangan }}</p><p v-for="d in documents" :key="d.id"><a :href="appUrl(`/documents/quotation/${d.id}`)" target="_blank" rel="noopener">{{ d.nama_file }}</a> ({{ Math.ceil(d.ukuran_byte / 1024) }} KB)</p><button v-if="can.edit" type="button" @click="action = 'dokumen'">Tambah dokumen customer</button></section>
        </template>
        <section v-if="q.id && revisions.length" class="panel"><h2>Riwayat revisi Quotation</h2><p v-for="r in revisions" :key="r.revisi"><a :href="appUrl(`/quotation/${q.id}/revisi/${r.revisi}/quotation`)" target="_blank" rel="noopener">Versi {{ r.revisi || 'awal' }}</a> {{ r.created_date }}</p></section>
        <p v-for="e in transition.errors" :key="e" class="error" role="alert">{{ e }}</p>
        <ConfirmDialog v-if="['deal', 'dokumen'].includes(action)" :title="action === 'deal' ? (again ? 'Deal ulang' : 'Tandai Deal') : 'Tambah dokumen customer'" :confirm-label="action === 'deal' ? (again ? 'Simpan Deal ulang' : 'Simpan Deal') : 'Simpan dokumen'" :busy="deal.processing || confirming" @cancel="action = ''" @confirm="saveDocuments"><template v-if="action === 'deal'"><p v-if="again">Program di-reschedule. Centang peserta yang memilih <b>Pindah jadwal</b>. Peserta Deal lama yang tidak ikut diproses otomatis: <b>Refund</b> bila invoice sudah dibayar, invoice <b>dibatalkan</b> bila belum. Terbit Sales Order <b>-rev{{ q.revisi }}</b>; dokumen customer lama terbawa.</p><p v-else>Peserta tidak dicentang menjadi Batal. Deal membuat Master Peserta, satu SO, dan Surat Tugas pertama program.</p><label v-for="p in dealRows" :key="p.id"><input v-model="deal.peserta" type="checkbox" :value="p.id" :disabled="!!p.konfirmasi_ulang && !p.konfirmasi_ulang.diproses_date" /> {{ p.nama }} · {{ p.instansi }}</label><p v-if="!dealRows.length" class="notice">{{ again ? 'Belum ada peserta yang memilih Pindah jadwal.' : 'Belum ada peserta Menunggu. Bagikan link terlebih dahulu.' }}</p></template><label>Keterangan dokumen<input v-model="deal.dokumen_keterangan" maxlength="200" /></label><label>{{ again && hasDocuments ? 'Tambah dokumen customer (opsional, dokumen lama terbawa; maksimal 5 MB / berkas)' : 'Dokumen customer (wajib, maksimal 5 MB / berkas)' }}<input type="file" multiple accept=".pdf,.jpg,.jpeg,.png,.doc,.docx,.xls,.xlsx" @change="files" /></label><ul><li v-for="(f, i) in deal.dokumen" :key="i">{{ f.name }} ({{ Math.ceil(f.size / 1024) }} KB) <button type="button" @click="deal.dokumen.splice(i, 1)">Hapus</button></li></ul><p v-for="e in deal.errors" :key="e" class="error" role="alert">{{ e }}</p><progress v-if="deal.progress" :value="deal.progress.percentage" max="100" aria-label="Progres unggahan"></progress></ConfirmDialog>
    </div></AppLayout>
</template>

<style scoped>
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


