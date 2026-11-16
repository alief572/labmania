import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const read = (f) => readFileSync(new URL(`../../resources/js/pages/${f}`, import.meta.url), 'utf8');
const atribut = read('Master/Atribut.vue'), ket = read('Master/TemplateKeterangan.vue'), peserta = read('Master/Peserta.vue');
const company = read('Master/Company.vue'), role = read('Master/Role.vue'), idx = read('Master/Program/Index.vue'), form = read('Master/Program/Form.vue');

// Semua daftar: cari + filter langsung berlaku, tanpa tombol Terapkan.
for (const [n, src] of Object.entries({ atribut, ket, peserta, company, idx })) assert.doesNotMatch(src, /Terapkan/, `${n}: tombol Terapkan`);
for (const [n, src] of Object.entries({ atribut, ket, peserta, company })) assert.match(src, /:keyword="filter\.q"/, `${n}: kolom cari`);

// Placeholder pencarian = mockup.
assert.ok(atribut.includes('Cari kode atau nama…') && ket.includes('Cari kode atau teks…') && peserta.includes('Cari kode, nama, instansi, NIK, atau bidang…'));
assert.ok(company.includes('Cari kode, nama, kota, atau PIC…') && role.includes('Cari nama role…') && idx.includes('Cari nomor atau judul…'));

// Label kategori Template Keterangan penuh; Default "Ya"/"Tidak".
for (const t of ['Syarat keikutsertaan', 'Dokumen persyaratan', 'Klausul tanda tangan']) assert.ok(ket.includes(t), t);
assert.ok(ket.includes('Otomatis masuk quotation baru'));

// Peserta & Company: header, "Delete", modal bersesi (Company) dan hint kelengkapan (Peserta).
assert.ok(peserta.includes("title: 'Nama Peserta'") && company.includes("title: 'Nama Company'") && company.includes("title: 'Kode Company'"));
assert.ok(peserta.includes('>Delete</button>') && company.includes('>Delete</button>'));
for (const sec of ['1 · Identitas', '2 · Alamat', '3 · Kontak Kantor', '4 · PIC', '5 · Pajak']) assert.ok(company.includes(sec), sec);
assert.ok(peserta.includes('Semua field wajib diisi, kecuali Company, NPWP Instansi, dan NIK.'));

// Role: badge hak, role sistem "Terkunci", Hapus tersembunyi bila ada user.
assert.ok(role.includes('hak diberikan') && role.includes('Belum diatur') && role.includes('Semua hak aktif'));
assert.match(role, /can\.delete && !system\(row\) && !row\.n_user/);

// Master Program: sub-baris nomor · varian · sertifikat; form punya bar judul, daftar Product bernomor, aksi per status.
assert.match(idx, /nama_varian, row\.nama_sertifikasi/);
assert.ok(form.includes('pf-formbar') && form.includes('mp-prods') && form.includes('+ Tambah Product aktif…'));
for (const t of ['Batalkan Program', 'Finalkan program', 'Buat Penjualan Program', 'Simpan Draft']) assert.ok(form.includes(t), t);
assert.match(form, /\/penjualan-program\/create\?master=\$\{program\.id\}/);
assert.doesNotMatch(form, /\brouter\.(?:post|put)\(|\bform\.(?:post|put)\(/);
console.log('masterSmallView ok');
