import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { parse, compileScript } from '@vue/compiler-sfc';
import ts from 'typescript';
import { createRenderer, nextTick, reactive } from 'vue';

// Exercise the actual component setup without a browser or a simulated DataTables implementation.
const source = readFileSync(process.argv[2] ?? new URL('../../resources/js/components/ServerTable.vue', import.meta.url), 'utf8');
const { descriptor } = parse(source);
let script = compileScript(descriptor, { id: 'server-table-test' }).content;
script = script.replace(/import '.*\.css';/g, '').replace(/from '([^']+)'/g, (_, name) => {
    const url = name.endsWith('/appUrl') ? new URL('../../resources/js/composables/appUrl.ts', import.meta.url).href : name.startsWith('.') ? new URL('../../resources/js/composables/serverTable.ts', import.meta.url).href : import.meta.resolve(name);
    return `from '${url}'`;
});
const compiled = ts.transpileModule(script, { compilerOptions: { target: ts.ScriptTarget.ES2022, module: ts.ModuleKind.ES2022 } }).outputText;
const Component = (await import(`data:text/javascript;base64,${Buffer.from(compiled).toString('base64')}`)).default;
Component.render = () => null;
const renderer = createRenderer({
    patchProp() {}, insert() {}, remove() {}, createElement: () => ({}), createText: () => ({}),
    createComment: () => ({}), setText() {}, setElementText() {}, parentNode: () => null, nextSibling: () => null,
});
const loaded = [];
const originalDocument = globalThis.document;
globalThis.document = new EventTarget();
globalThis.document.querySelector = () => null;
const app = renderer.createApp(Component, { url: '/master/role/data', label: 'Role', columns: [{ title: 'Nama', orderable: true }], filters: { tab: 'ongoing', status: '' }, refreshOnVisit: true, onLoaded: data => loaded.push(data) });
const vm = app.mount({});
const state = vm.$.setupState;
const originalFetch = globalThis.fetch;
const requests = [];
globalThis.fetch = (url, options) => new Promise(resolve => requests.push({ url, options, resolve }));
const result = (draw, recordsFiltered = 30) => ({ ok: true, json: async () => ({ draw, recordsFiltered, recordsTotal: 30, data: [{ nama: 'Role Uji' }] }) });
try {
    const received = [];
    const first = state.load({ draw: 1, start: 0, length: 10 }, data => received.push(data));
    const second = state.load({ draw: 2, start: 10, length: 10 }, data => received.push(data));
    assert.equal(requests[0].options.signal.aborted, true);
    requests[1].resolve(result(2));
    await second;
    requests[0].resolve(result(1));
    await first;
    assert.deepEqual(received.map(data => data.draw), [2]);
    assert.deepEqual(loaded.map(data => data.draw), [2]);
    assert.equal(loaded[0].recordsFiltered, 30);
    assert.equal(state.start, 10);
    const failed = state.load({ draw: 3 }, data => received.push(data));
    requests[2].resolve({ ok: false, status: 403 });
    await failed;
    assert.match(state.error, /akses/);
    assert.deepEqual(received.at(-1).data, []);

    const paging = [];
    state.instance = { dt: { page: page => paging.push(page), draw: mode => paging.push(mode), ajax: { reload: (_, reset) => paging.push(reset) } } };
    const emptied = state.load({ draw: 4, start: 20, length: 10 }, data => received.push(data));
    requests[3].resolve(result(4, 20));
    await emptied;
    assert.deepEqual(paging, [1, 'page']);
    assert.equal(state.error, '');
    await vm.reload();
    await vm.reload(true);
    assert.deepEqual(paging.slice(-2), [false, true]);

    const reloads = [];
    state.instance = { dt: { ajax: { reload: (_, reset) => reloads.push(reset) } } };
    for (let render = 0; render < 3; render++) {
        vm.$.props.filters = { tab: 'ongoing', status: '' };
        await nextTick();
        await nextTick();
    }
    assert.deepEqual(reloads, [], 'Render ulang statistik dengan isi filter sama tidak boleh memuat ulang tabel.');
    vm.$.props.filters = reactive({ tab: 'ongoing', status: 'deal' });
    await nextTick();
    await nextTick();
    assert.deepEqual(reloads, [true], 'Perubahan nilai filter tetap memuat ulang dari halaman pertama.');
    vm.$.props.filters.status = 'batal';
    await nextTick();
    await nextTick();
    assert.deepEqual(reloads, [true, true], 'Perubahan field pada objek filter tetap terpantau.');

    const leaving = state.load({ draw: 5 }, data => received.push(data));
    app.unmount();
    requests[4].resolve(result(5));
    await leaving;
    await nextTick();
    assert.equal(requests[4].options.signal.aborted, true);
    assert.equal(received.some(data => data.draw === 5), false);
    assert.equal(loaded.some(data => data.draw === 5), false);
    console.log('ServerTable lifecycle: request lama dibatalkan, error/retry, koreksi halaman, reload, filter tanpa loop, dan unmount lulus.');
} finally {
    globalThis.fetch = originalFetch;
    app.unmount();
    if (originalDocument === undefined) delete globalThis.document;
    else globalThis.document = originalDocument;
}
