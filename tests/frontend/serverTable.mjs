import assert from 'node:assert/strict';
import { tableQuery, tableError, lastTablePage } from '../../resources/js/composables/serverTable.ts';

const query = tableQuery({ draw: 7, start: 10, length: 10, search: { value: '0', regex: false },
    order: [{ column: 2, dir: 'desc' }], columns: [{ data: null, search: { value: '', regex: false } }] },
    { status: 'off', role: 0, jenis: 'Swasta & BUMN', q: 'pencarian lama', kosong: '' });
assert.equal(query.get('search[value]'), '0');
assert.equal(query.get('search[regex]'), 'false');
assert.equal(query.get('order[0][column]'), '2');
assert.equal(query.get('columns[0][search][value]'), '');
assert.equal(query.get('status'), 'off');
assert.equal(query.get('role'), '0');
assert.equal(new URLSearchParams(query.toString()).get('jenis'), 'Swasta & BUMN');
assert.equal(query.has('q'), false);
assert.equal(query.has('kosong'), false);
assert.equal(lastTablePage(0, 25), 0);
assert.equal(lastTablePage(25, 25), 0);
assert.equal(lastTablePage(26, 25), 1);
assert.equal(lastTablePage(50, 25), 1);
assert.match(tableError(401), /login/);
assert.match(tableError(419), /login/);
assert.match(tableError(403), /akses/);
assert.match(tableError(422), /tidak valid/);
assert.match(tableError(500), /gagal/);
console.log('ServerTable: encoding protokol/filter, pencarian 0, koreksi halaman, dan pesan error lulus.');
