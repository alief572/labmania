import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';

const src = readFileSync(new URL('../../resources/js/pages/Master/Rate.vue', import.meta.url), 'utf8');

// Tab berbadge jumlah (mockup viewRate) dan filter langsung berlaku tanpa tombol Terapkan.
assert.match(src, /<span class="nav-count">\{\{ counts\[key\] \}\}<\/span>/);
assert.doesNotMatch(src, /Terapkan/);
assert.ok(src.includes('Cari kode atau item…'));

// Header kode mengikuti tab ("Kode Fixed Cost" / "Kode Variable Cost"); Default Cost rata kanan.
assert.match(src, /title: `Kode \$\{props\.title\}`/);
assert.match(src, /title: 'Default Cost', orderable: true, className: 'dt-body-right'/);

// Item mendahului pasangan Satuan/Default Cost agar grid tidak menyisakan sel kosong.
const order = ['id="rkode"', 'id="rkat"', 'id="item"', 'id="satuan"', 'id="cost"', 'id="ket"', 'id="coa"'].map((k) => src.indexOf(k));
assert.ok(order.every((v) => v > 0) && order.every((v, i) => i === 0 || v > order[i - 1]), `urutan field: ${order}`);

// Kategori tidak bisa diubah dari modal (server menolak ganti tipe); rupiah lewat helper tunggal.
assert.match(src, /id="rkat"[^>]*disabled/);
assert.doesNotMatch(src, /const rupiah = \(/);
console.log('rateView ok');
