import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = (f) => readFileSync(new URL(`../../resources/js/${f}`, import.meta.url), 'utf8');
const qIdx = read('pages/Quotation/Index.vue'), qBatch = read('pages/Quotation/Batch.vue'), qForm = read('pages/Quotation/Form.vue');
const soIdx = read('pages/SalesOrder/Index.vue'), soProg = read('pages/SalesOrder/Program.vue'), soShow = read('pages/SalesOrder/Show.vue');
const sIdx = read('pages/Sales/Index.vue'), sForm = read('pages/Sales/Form.vue'), rs = read('pages/Sales/Reschedule.vue'), ps = read('components/QuotationParticipants.vue');

// Filter langsung berlaku di semua daftar; tidak ada tombol "Terapkan".
for (const [n, src] of Object.entries({ qIdx, qBatch, soIdx, sIdx })) assert.doesNotMatch(src, /<button[^>]*>\s*Terapkan\s*<\/button>/, `${n}: tombol Terapkan`);

// Quotation: tab berbadge, pipeline status, tanpa legenda mockup, tombol Buat Quotation, label "Action".
assert.match(qIdx, /<span class="nav-count">\{\{ tabCounts\[key\] \}\}<\/span>/);
for (const t of ['Draft', 'Terkirim', 'Deal', 'Batal']) assert.ok(qIdx.includes(`<span class="kpi-l">${t}</span>`), `pipeline ${t}`);
assert.doesNotMatch(qIdx, /Yang berubah di Quotation|tobe-box|class="tobe"/);
assert.ok(qIdx.includes('+ Buat Quotation') && qIdx.includes('"title":"Action"'));
assert.ok(qIdx.includes('Cari program, nomor quotation, kepada, atau peserta…'));
assert.ok(qBatch.includes('Quotation —') && qBatch.includes('Data Peserta —') && qBatch.includes('← Semua Penjualan Program') && qBatch.includes('Export PDF'));
assert.ok(qForm.includes('Langkah berikutnya:') && qForm.includes('Alur quotation') && qForm.includes('kuota terisi'));

// Sales Order: tanpa pengantar per tab, tab berbadge, status berlabel, bagian bernomor 5-7, label "Confirmation Letter".
assert.doesNotMatch(soIdx, /Surat Tugas untuk trainer dan asisten trainer program\.|Setiap quotation yang Deal otomatis menjadi satu Sales Order/);
assert.match(soIdx, /Sales Order<span class="nav-count">/);
assert.ok(soProg.includes('Print Out SO') && soProg.includes('Peserta deal'));
for (const n of [5, 6, 7]) assert.ok(soShow.includes(`<span class="section-number">${n}</span>`), `seksi ${n}`);
assert.ok(soShow.includes('Confirmation Letter') && soShow.includes('Langkah berikutnya:') && soShow.includes('Buka quotation'));

// Penjualan Program: tag jenis, status berlabel, bar judul dan tombol per status; Reschedule berbentuk mockup.
assert.ok(sIdx.includes('Cari nomor, program, atau lokasi…') && sIdx.includes('> Quotation') === false);
assert.ok(sForm.includes('Informasi Penjualan') && sForm.includes('Finalkan → Quotation') && sForm.includes('Cetak Surat Penawaran'));
for (const t of ['Jadwal sekarang', 'Tanggal mulai baru', 'Alasan reschedule', 'Yang ikut berubah bila disimpan', 'Simpan jadwal baru', 'Riwayat Reschedule']) assert.ok(rs.includes(t), t);
assert.ok(ps.includes('PS_LABEL') && ps.includes('Tidak ada peserta yang cocok.'));

// Mutasi tetap lewat SweetAlert2.
for (const [n, src] of Object.entries({ qForm, sForm, rs })) assert.doesNotMatch(src, /\brouter\.(?:post|put|patch|delete)\(/, `${n}: mutasi di luar wrapper`);
console.log('salesFlowView ok');
