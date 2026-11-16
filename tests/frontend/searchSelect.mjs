import assert from 'node:assert/strict';
import ts from 'typescript';
import { readFileSync } from 'node:fs';

const src = readFileSync(new URL('../../resources/js/composables/searchSelect.ts', import.meta.url), 'utf8');
const js = ts.transpileModule(src, { compilerOptions: { target: ts.ScriptTarget.ES2022, module: ts.ModuleKind.ES2022 } }).outputText;
const { filterOptions } = await import(`data:text/javascript;base64,${Buffer.from(js).toString('base64')}`);

const o = [{ label: 'Jawa Barat' }, { label: 'Jawa Tengah' }, { label: 'DKI Jakarta' }, { label: 'Yogyakarta' }];
assert.equal(filterOptions(o, '').length, 4);
assert.deepEqual(filterOptions(o, 'jaw').map(x => x.label), ['Jawa Barat', 'Jawa Tengah']);
assert.deepEqual(filterOptions(o, '  JAKARTA ').map(x => x.label), ['DKI Jakarta']);
assert.equal(filterOptions([{ label: 'Pelatihan Kesehatan' }], 'kesehatán').length, 1);
assert.equal(filterOptions([{ label: 'Café' }], 'cafe').length, 1);
assert.equal(filterOptions(o, 'zzz').length, 0);
console.log('searchSelect ok');
