import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const src = readFileSync(new URL('../../resources/js/pages/Master/TemplateKonfirmasi.vue', import.meta.url), 'utf8');

// Kolom daftar = mockup listKfTpl (termasuk Pernyataan yang sebelumnya hilang).
for (const h of ['Kode', 'Nama Template', 'Default untuk Jenis Produk', 'Isian', 'Pernyataan', 'Status', 'Action']) assert.ok(src.includes(`title: '${h}'`), `kolom ${h}`);

// Isian standar tidak jadi chip; pernyataan dipotong 110 karakter; nilai kosong = "— (pilih manual)".
assert.match(src, /STANDARD = \['nama', 'nik', 'jabatan', 'instansi', 'alamat', 'email'\]/);
assert.match(src, /slice\(0, 110\)/);
assert.ok(src.includes('— (pilih manual)'));

// Urutan gear: Edit, Lihat contoh, toggle. Filter langsung berlaku (tanpa tombol Terapkan).
assert.ok(src.indexOf('open(row)') < src.indexOf('contoh(row)') && src.indexOf('contoh(row)') < src.indexOf('toggle(row)'));
assert.doesNotMatch(src, /Terapkan/);

// Modal dua kolom dengan hint penanda dan validasi minimal 1 isian.
assert.ok(src.includes('kf-grid') && src.includes('Pernyataan 1') && src.includes('Pilih minimal 1 isian'));
assert.ok(src.includes('{judul} {tanggal} {waktu} {durasi} {lokasi}'));
console.log('templateKonfirmasiView ok');
