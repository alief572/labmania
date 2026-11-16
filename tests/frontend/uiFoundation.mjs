import assert from 'node:assert/strict';
import { readFileSync, readdirSync, statSync } from 'node:fs';
import { join } from 'node:path';

const root = new URL('../../resources/js/', import.meta.url).pathname.replace(/^\/([A-Za-z]:)/, '$1');
const walk = (d) => readdirSync(d).flatMap((f) => (statSync(join(d, f)).isDirectory() ? walk(join(d, f)) : [join(d, f)]));
const vue = walk(join(root, 'pages')).concat(walk(join(root, 'components'))).filter((f) => f.endsWith('.vue'));
const src = (f) => readFileSync(f, 'utf8');

// Header kolom aksi mengikuti mockup ("Action"), bukan "Aksi".
for (const f of vue) assert.doesNotMatch(src(f), /title: 'Aksi'|"title": "Aksi"/, `${f}: header kolom harus "Action"`);

// Tidak ada helper rupiah lokal yang menduplikasi composables/costing.
for (const f of vue) assert.doesNotMatch(src(f), /const rupiah = \(/, `${f}: pakai rupiah dari composables/costing`);

// Sel nominal di tabel memakai class money (rata kanan), baik rupiah() maupun money().
for (const f of vue) assert.doesNotMatch(src(f), /<td>\{\{ *(rupiah|money)\(/, `${f}: <td> nominal wajib class="money"`);

// Kolom ServerTable bernama Nilai/Default Cost rata kanan.
for (const f of vue) for (const m of src(f).matchAll(/\{ ?"?title"?: ['"](Nilai|Default Cost)['"][^}]*\}/g)) assert.match(m[0], /dt-body-right/, `${f}: kolom ${m[1]}`);

// Halaman daftar Master memakai PageHead dengan deskripsi dari mockup.
for (const n of ['Product', 'Peserta', 'Company', 'Role', 'User', 'Trainer', 'TemplateKeterangan', 'TemplateKonfirmasi', 'Rate']) assert.match(src(join(root, 'pages/Master', `${n}.vue`)), /<PageHead [^>]*description="/, n);

// FormField menampilkan * hanya lewat atribut required pada kontrol anak.
const ff = src(join(root, 'components/FormField.vue'));
assert.match(ff, /querySelector\('\[required\]'\)/);
assert.match(ff, /aria-hidden="true"/);

console.log('uiFoundation ok');
