import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { parse, compileScript } from '@vue/compiler-sfc';
import ts from 'typescript';
import { createRenderer } from 'vue';

const file = new URL('../../resources/js/pages/Quotation/Batch.vue', import.meta.url);
const { descriptor } = parse(readFileSync(file, 'utf8'));
let code = ts.transpileModule(compileScript(descriptor, { id: 'batch-pdf' }).content, {
    compilerOptions: { module: ts.ModuleKind.ES2022, target: ts.ScriptTarget.ES2022 },
}).outputText;
code = code.replace(/import (\w+) from ['"][^'"]+\.vue['"];?/g, (_, symbol) => `const ${symbol}={};`);
code = code.replace(/from (['"])([^'"]+)\1/g, (_, quote, path) => `from '${path.startsWith('.') ? new URL(path + '.ts', file).href : import.meta.resolve(path)}'`);
const component = (await import(`data:text/javascript;base64,${Buffer.from(code).toString('base64')}`)).default;
component.render = () => null;
const renderer = createRenderer({ patchProp() {}, insert() {}, remove() {}, createElement: () => ({}), createText: () => ({}), createComment: () => ({}), setText() {}, setElementText() {}, parentNode: () => null, nextSibling: () => null });
const originalFetch = globalThis.fetch;
const requests = [];
globalThis.fetch = (url, options) => new Promise(resolve => requests.push({ url, options, resolve }));
const app = renderer.createApp(component, { summary: {}, program: { id: 1 }, quotations: [], peserta: [], hasParticipants: true, dataTab: true, filters: {}, pesertaStatus: 'aktif', canAdd: false, canManage: false });
const state = app.mount({}).$.setupState;
try {
    const missing = state.openPdf();
    assert.equal(requests[0].url, '/quotation/batch/1/pdf');
    assert.equal(requests[0].options.headers.Accept, 'application/json');
    requests[0].resolve({ ok: false, json: async () => ({ errors: { pdf: ['Surat peserta #2 belum tersedia.'] } }) });
    await missing;
    assert.match(state.pdfError, /#2/);
    assert.equal(state.pdfOpen, true);
    assert.equal(state.pdfLoading, false);
    const valid = state.openPdf();
    requests.at(-1).resolve(new Response(new Blob(['%PDF-1.4'], { type: 'application/pdf' })));
    await valid;
    assert.match(state.pdfUrl, /^blob:/);
    const blobUrl = state.pdfUrl;
    state.closePdf();
    assert.equal(state.pdfUrl, '');
    await assert.rejects(originalFetch(blobUrl));
    const stale = state.openPdf();
    const oldRequest = requests.at(-1);
    const fresh = state.openPdf();
    assert.equal(oldRequest.options.signal.aborted, true);
    requests.at(-1).resolve(new Response(new Blob(['%PDF-1.4'], { type: 'application/pdf' })));
    await fresh;
    const activeUrl = state.pdfUrl;
    oldRequest.resolve({ ok: false, json: async () => ({ errors: { pdf: ['Lama'] } }) });
    await stale;
    assert.equal(state.pdfUrl, activeUrl);
    assert.equal(state.pdfError, '');
    const leaving = state.openPdf();
    app.unmount();
    assert.equal(requests.at(-1).options.signal.aborted, true);
    requests.at(-1).resolve(new Response(new Blob(['%PDF-1.4'], { type: 'application/pdf' })));
    await leaving;
    assert.equal(state.pdfUrl, '');
} finally {
    globalThis.fetch = originalFetch;
}
console.log('Quotation batch PDF: modal, validasi surat belum tersedia, preview Blob dan pembersihan URL lulus.');
