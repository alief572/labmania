import assert from 'node:assert/strict';
import { money } from '../../resources/js/types/salesOrder.ts';

assert.equal(money('999999999999999999'), 'Rp 999.999.999.999.999.999');
assert.equal(money('2119000'), 'Rp 2.119.000');
assert.equal(money('0'), 'Rp 0');
assert.equal(money(null), '—');
console.log('Format Rupiah fase 4: 4 pemeriksaan lulus.');
