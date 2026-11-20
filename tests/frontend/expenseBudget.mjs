import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { parse } from '@vue/compiler-sfc';
import ts from 'typescript';

const source = readFileSync(new URL('../../resources/js/components/ExpenseBudget.vue', import.meta.url), 'utf8');
const { descriptor, errors } = parse(source);
assert.deepEqual(errors, []);
const script = ts.createSourceFile('Budget.ts', descriptor.scriptSetup.content, ts.ScriptTarget.Latest, true, ts.ScriptKind.TS);
const declaration = script.statements.find(node => ts.isVariableStatement(node) && node.declarationList.declarations.some(d => d.name.getText(script) === 'sum'));
const props = { budget: { items: [] } };
const compiled = ts.transpileModule(declaration.getText(script), {}).outputText;
const sum = new Function('props', `${compiled}; return sum;`)(props);
assert.equal(sum('estimasi'), 0);
props.budget.items = [
    { estimasi: '1000.25', terpakai: '500.50', overbudget: '0.00' },
    { estimasi: '0.00', terpakai: '100.25', overbudget: '250.50' },
];
assert.equal(sum('estimasi'), 1000.25);
assert.equal(sum('terpakai'), 600.75);
assert.equal(sum('overbudget'), 250.5);
props.budget.items[0].terpakai = '750.75';
assert.equal(sum('terpakai'), 851, 'Footer mengikuti data budget terbaru');
assert.equal(props.budget.items[0].estimasi, '1000.25', 'Agregasi tidak mengubah item budget');
console.log('ExpenseBudget: footer estimasi/terpakai/overbudget, item baru, data kosong dan pembaruan data lulus.');
