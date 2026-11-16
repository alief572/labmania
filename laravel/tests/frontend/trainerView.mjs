import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const src = readFileSync(new URL('../../resources/js/pages/Master/Trainer.vue', import.meta.url), 'utf8');

// Lima tab form sesuai mockup (trainerBody): Identitas + empat daftar riwayat.
for (const t of ['Identitas Trainer', 'Pelatihan & Seminar', 'Pengalaman Kerja', 'Pengalaman Mengajar', 'Lampiran Dokumen']) assert.ok(src.includes(`'${t}'`), `tab ${t}`);

// Header kolom riwayat = TR_LISTS mockup.
for (const h of ['Judul Pelatihan/Seminar/Lainnya', 'Penyelenggara/Tempat', 'Institusi/Perusahaan', 'Topik Pembelajaran']) assert.ok(src.includes(h), `kolom ${h}`);

// Daftar: chip bidang maksimal 2 + "+n", urutan gear Edit / Lihat CV / toggle, header Nama Trainer.
assert.match(src, /lines\(row\.bidang_keahlian\)\.slice\(0, 2\)/);
assert.ok(src.indexOf('openEdit(row)') < src.indexOf('viewCv(row)') && src.indexOf('viewCv(row)') < src.indexOf('toggle(row)'));
assert.ok(src.includes("title: 'Nama Trainer'"));

// Tidak ada pengiriman data di luar wrapper SweetAlert2.
assert.doesNotMatch(src, /\b(?:router|form)\.(?:post|put|patch|delete)\(/);
console.log('trainerView ok');
