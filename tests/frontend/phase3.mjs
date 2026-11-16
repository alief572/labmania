import assert from 'node:assert/strict';
import { calc, rupiahInput, costQuantity, profitParticipants } from '../../resources/js/composables/costing.ts';

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


assert.equal(costQuantity('fixed', 'Event', 3, 10), 1);
assert.equal(costQuantity('fixed', 'Day', 3, 10), 3);
assert.equal(costQuantity('variable', 'Peserta/Event', 3, 10), 10);
assert.equal(costQuantity('variable', 'Peserta/Day', 3, 10), 30);

assert.equal(profitParticipants(1000, 100, 500, 1000), 5);
assert.equal(profitParticipants(1000, 500, 500, 1000), null);
assert.equal(profitParticipants(1000, 0, 1, 100000), null);
assert.equal(profitParticipants(0, 0.1, 0.3, 0.6), 4);
console.log('Simulasi rupiah, Qty empat satuan, target profit dan batas skala lulus.');
