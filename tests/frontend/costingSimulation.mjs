import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { parse, compileScript } from '@vue/compiler-sfc';
import { createSSRApp } from 'vue';
import { renderToString } from '@vue/server-renderer';
import ts from 'typescript';
import { calc } from '../../resources/js/composables/costing.ts';

const file = new URL('../../resources/js/components/CostingSimulation.vue', import.meta.url);
const { descriptor } = parse(readFileSync(file, 'utf8'));
let code = ts.transpileModule(compileScript(descriptor, { id: 'simulation', inlineTemplate: true }).content, {
    compilerOptions: { module: ts.ModuleKind.ES2022, target: ts.ScriptTarget.ES2022 },
}).outputText;
code = code.replace(/from (['"])([^'"]+)\1/g, (_, quote, path) => `from '${path.startsWith('.') ? new URL(path + '.ts', file).href : import.meta.resolve(path)}'`);
const component = (await import(`data:text/javascript;base64,${Buffer.from(code).toString('base64')}`)).default;
async function render(participants, price, targetProfit, fixed = 3590000, variable = 185000) {
    const result = calc(1, participants, price, [
        { tipe: 'fixed', satuan: 'Event', harga: fixed },
        { tipe: 'variable', satuan: 'Peserta/Event', harga: variable },
    ]);
    return renderToString(createSSRApp(component, { result, participants, price, targetProfit }));
}
const html = await render(15, 800000, 1500000);
assert.equal((html.match(/<section class="sx-panel"/g) || []).length, 3);
for (const text of ['simulation-cards duo', 'simulation-cards trio', 'simulation-legend', 'aman (lebih 9)', 'tercapai, lebih', '47,0%', '(target profit)', 'Rincian perhitungan']) assert.ok(html.includes(text), text);
assert.ok(html.includes('Rp 5.635.000'));
assert.match(await render(3, 800000, 1500000), /belum tercapai, kurang/);
assert.match(await render(15, 100000, 1500000), /Tidak tercapai/);
assert.match(await render(15, 800000, 0), /Target profit tidak diisi/);
const empty = await render(0, 0, 0);
assert.match(empty, /Isi target peserta/);
assert.doesNotMatch(empty, /class="sx-panel"/);
console.log('CostingSimulation: panel mockup, hasil, target tercapai/kurang/kosong, BEP tidak tercapai dan empty state lulus.');
