import assert from 'node:assert/strict';
import { brief, changes } from '../../resources/js/composables/activityLog.ts';

const grants = [{ aksi: 'ubah', id_menu: 62, id_group: 13 }];
const log = { old: { grants }, new: { grants: [] }, detail_changes: { 'manage_peserta - ubah': { old: true, new: false }, 'kode_lama - cetak': { old: false, new: true } } };
assert.deepEqual(changes(log, { manage_peserta: 'Manage Peserta' }).map(c => [c.label, c.old, c.new]), [
    ['Manage Peserta · Manage', 'Ya', 'Tidak'],
    ['kode_lama · cetak', 'Tidak', 'Ya'],
]);
// Tanpa detail_changes, snapshot tetap ditampilkan agar informasinya tidak hilang.
assert.equal(changes({ old: { grants }, new: { grants: [] } })[0].field, 'grants');
// Log modul lain tidak diubah labelnya; field yang sama tidak ditampilkan.
const other = changes({ old: { nama: 'A', ket: 'x' }, new: { nama: 'B', ket: 'x' }, detail_changes: { 'Item - Qty': { old: 1, new: 2 } } });
assert.deepEqual(other.map(c => [c.label, c.old, c.new]), [['nama', 'A', 'B'], ['Item - Qty', 1, 2]]);
assert.equal(brief('a'.repeat(200)), `${'a'.repeat(120)}…`);
assert.equal(brief('pendek'), 'pendek');
assert.equal(brief(null), '—');
console.log('Activity Log: ringkasan hak akses, snapshot cadangan, log lain, dan pemotongan teks lulus.');
