import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { parse } from '@vue/compiler-sfc';
import ts from 'typescript';

const source = readFileSync(new URL('../../resources/js/pages/Sales/Form.vue', import.meta.url), 'utf8');
const { descriptor, errors } = parse(source);
assert.deepEqual(errors, []);
const script = ts.createSourceFile('Form.ts', descriptor.scriptSetup.content, ts.ScriptTarget.Latest, true, ts.ScriptKind.TS);
const names = ['addRate', 'costRows', 'revealInvalid'];
const functions = script.statements.filter(node => ts.isFunctionDeclaration(node) && names.includes(node.name?.text)).map(node => node.getText(script)).join('\n');
const props = { rates: [{ id: 1, tipe: 'fixed', item: 'Ruangan', id_satuan: 1, nama_satuan: 'Event', default_cost: '125000' }, { id: 2, tipe: 'fixed', item: 'Trainer', id_satuan: 2, nama_satuan: 'Day', default_cost: '250000' }] };
const form = { biaya: [] };
const pick = { value: { Event: 1, Day: 2 } };
const attributes = [];
class Field { closest(selector) { assert.equal(selector, 'details'); return { setAttribute: (...args) => attributes.push(args) }; } }
const compiled = ts.transpileModule(functions, { compilerOptions: { target: ts.ScriptTarget.ES2022 } }).outputText;
const actions = new Function('props', 'form', 'pick', 'HTMLElement', `${compiled}; return { ${names.join(', ')} };`)(props, form, pick, Field);

actions.addRate('Event');
assert.equal(form.biaya[0].harga, 125000);
assert.equal(pick.value.Event, '');
assert.equal(pick.value.Day, 2, 'Pemilihan satuan lain tidak ikut direset');
actions.addRate('Day');
pick.value.Event = 1;
actions.addRate('Event');
assert.equal(form.biaya.length, 2, 'Rate tidak boleh ditambah dua kali');
pick.value.Day = 1;
actions.addRate('Day');
assert.equal(form.biaya.length, 2, 'Rate harus sesuai satuan kelompok');
form.biaya.push({ tipe: 'fixed', nama_satuan: null }, { tipe: 'variable', nama_satuan: null }, { tipe: 'variable', nama_satuan: 'Peserta/Day' });
assert.deepEqual(actions.costRows('fixed', 'Event').map(row => row.i), [0, 2]);
assert.deepEqual(actions.costRows('fixed', 'Day').map(row => row.i), [1]);
assert.deepEqual(actions.costRows('variable', 'Peserta/Event').map(row => row.i), [3]);
assert.deepEqual(actions.costRows('variable', 'Peserta/Day').map(row => row.i), [4]);
actions.revealInvalid({ target: new Field() });
assert.deepEqual(attributes, [['open', '']], 'Bagian tertutup dibuka sebelum fokus validasi');
actions.revealInvalid({ target: null });

const template = descriptor.template.content;
assert.doesNotMatch(source, /choiceSearch|choice-search|matches\(/, 'Pencarian pilihan tambahan tidak ada pada mockup');
assert.doesNotMatch(template, /v-model="form\.jenis"|v-model="b\.id_satuan"/, 'Jenis tidak diedit di form; satuan biaya mengikuti kelompok seperti mockup');
assert.ok(template.indexOf('id="sales-masters"') < template.indexOf('id="sales-information"'), 'Master Program berada pada panel sebelum bagian 1');
assert.ok(template.indexOf('class="pickrow"', template.indexOf('class="cost-group"')) < template.indexOf('class="items"'), 'Pemilih biaya berada di atas tabel');
assert.match(template, /@invalid\.capture="revealInvalid"/);
assert.match(template, /<tfoot><tr><td colspan="3"/);
assert.match(template, /v-if="actual"/);
console.log('salesFormMockup: pemilihan rate per satuan, kelompok biaya, panel, dan validasi bagian tertutup lulus.');
