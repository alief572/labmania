import assert from 'node:assert/strict';
import { calc, rupiahInput } from '../../resources/js/composables/costing.ts';

assert.equal(rupiahInput('1.000.000'), 1000000);
assert.equal(rupiahInput('1000000'), 1000000);
assert.equal(rupiahInput(1000000), 1000000);
assert.equal(rupiahInput(''), 0);
assert.equal(rupiahInput('salah'), 0);
const result = calc(2, 10, rupiahInput('1.000.000'), [
    { tipe: 'fixed', satuan: 'Day', harga: rupiahInput('500.000') },
    { tipe: 'variable', satuan: 'Peserta', harga: rupiahInput('100.000') },
]);
assert.equal(result.hpp, 2000000);
assert.equal(result.pendapatan, 10000000);
assert.equal(result.profit, 8000000);
console.log('Simulasi rupiah fase 3: 8 pemeriksaan lulus.');
