import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const list = readFileSync(new URL('../../resources/js/pages/Master/Product.vue', import.meta.url), 'utf8');
const form = readFileSync(new URL('../../resources/js/pages/Master/ProductForm.vue', import.meta.url), 'utf8');

// "Tambah Product" = halaman penuh (mockup viewCombine), bukan modal di daftar.
assert.match(list, /<Link[^>]*v-if="can\.add"[^>]*:href="appUrl\('\/master\/product\/create'\)"/);
assert.doesNotMatch(list, /addForm|saveAdd|openLingkup|saveLingkup/);

// Daftar: nomor di atas judul, atribut nonaktif ditandai, filter langsung berlaku.
assert.match(list, /<span class="code">\{\{ row\.nomor \}\}<\/span><br>\{\{ row\.judul \}\}/);
assert.ok(list.includes('(nonaktif)') && list.includes('attr-off'));
assert.doesNotMatch(list, /Terapkan/);

// Form tambah: judul wajib, tabel kombinasi, hapus dinonaktifkan bila satu baris, tombol "Simpan N Paket".
assert.match(form, /Simpan \{\{ n \}\} Paket/);
assert.match(form, /:disabled="n === 1 \|\| confirming"/);
assert.match(form, /akan menjadi \{\{ n \}\} paket produk/);
for (const h of ['Produk', 'Bidang', 'Varian', 'Sertifikat']) assert.ok(form.includes(`'${h}'`), h);
assert.doesNotMatch(form, /\brouter\.(?:post|put)\(|\bform\.(?:post|put)\(/);
console.log('productView ok');
