import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { parse, compileScript } from '@vue/compiler-sfc';
import ts from 'typescript';
import { createRenderer } from 'vue';
const file = new URL('../../resources/js/components/QuotationCreateModal.vue', import.meta.url);
let code = ts.transpileModule(compileScript(parse(readFileSync(file, 'utf8')).descriptor, { id: 'create-modal' }).content, { compilerOptions: { module: ts.ModuleKind.ES2022, target: ts.ScriptTarget.ES2022 } }).outputText;
code = code.replace(/import (\w+) from ['"][^'"]+\.vue['"];?/g, (_, symbol) => `const ${symbol}={};`);
code = code.replace(/from (['"])([^'"]+)\1/g, (_, quote, path) => `from '${path.startsWith('.') ? new URL(path + '.ts', file).href : import.meta.resolve(path)}'`);
const component = (await import(`data:text/javascript;base64,${Buffer.from(code).toString('base64')}`)).default;
component.render = () => null;
const renderer = createRenderer({ patchProp() {}, insert() {}, remove() {}, createElement: () => ({}), createText: () => ({}), createComment: () => ({}), setText() {}, setElementText() {}, parentNode: () => null, nextSibling: () => null });
const original = globalThis.fetch;
const requests = [];
globalThis.fetch = (url, options) => new Promise(resolve => requests.push({ url, options, resolve }));
const flush = () => new Promise(resolve => setImmediate(resolve));
try {
    const app = renderer.createApp(component, { programId: 7 });
    const state = app.mount({}).$.setupState;
    assert.equal(requests[0].url, '/quotation/create?id_penjualan=7');
    assert.equal(requests[0].options.headers.Accept, 'application/json');
    requests[0].resolve(new Response(JSON.stringify({ quotation: { id_penjualan: 7 } }), { headers: { 'Content-Type': 'application/json' } }));
    await flush();
    assert.equal(state.data.quotation.id_penjualan, 7);
    app.unmount();
    const other = renderer.createApp(component);
    const loading = other.mount({}).$.setupState;
    assert.equal(requests.at(-1).url, '/quotation/create');
    requests.at(-1).resolve(new Response('Forbidden', { status: 403 }));
    await flush();
    assert.match(loading.error, /hak akses/);
    const retry = loading.load();
    other.unmount();
    assert.equal(requests.at(-1).options.signal.aborted, true);
    requests.at(-1).resolve(new Response(JSON.stringify({ quotation: {} }), { headers: { 'Content-Type': 'application/json' } }));
    await retry;
    assert.equal(loading.data, null);
} finally { globalThis.fetch = original; }
for (const page of ['Index', 'Batch']) {
    const source = readFileSync(new URL(`../../resources/js/pages/Quotation/${page}.vue`, import.meta.url), 'utf8');
    assert.match(source, /<QuotationCreateModal/);
    assert.doesNotMatch(source, /:href="appUrl\(`?['"]?\/quotation\/create/);
}
console.log('Quotation create modal: program preset, loading, error, retry dan abort lulus.');
