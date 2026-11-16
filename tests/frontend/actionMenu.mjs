import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { parse, compileScript } from '@vue/compiler-sfc';
import ts from 'typescript';
import { createRenderer, h } from 'vue';

const { descriptor } = parse(readFileSync(new URL('../../resources/js/components/ActionMenu.vue', import.meta.url), 'utf8'));
const script = compileScript(descriptor, { id: 'action-menu-test', inlineTemplate: true }).content
    .replace(/from (['"])vue\1/g, `from '${import.meta.resolve('vue')}'`);
const compiled = ts.transpileModule(script, { compilerOptions: { target: ts.ScriptTarget.ES2022, module: ts.ModuleKind.ES2022 } }).outputText;
const ActionMenu = (await import(`data:text/javascript;base64,${Buffer.from(compiled).toString('base64')}`)).default;
const originalWindow = globalThis.window;
const listeners = new Set();
globalThis.window = { addEventListener: (_, fn) => listeners.add(fn), removeEventListener: (_, fn) => listeners.delete(fn) };
function node(tag = '') { return { tag, props: {}, children: [], parent: null }; }
const renderer = createRenderer({
    createElement: tag => node(tag), createText: () => node(), createComment: () => node(),
    patchProp: (el, key, _, value) => { el.props[key] = value; },
    setText() {}, setElementText() {},
    insert(el, parent, anchor) {
        if (el.parent) el.parent.children.splice(el.parent.children.indexOf(el), 1);
        const index = anchor ? parent.children.indexOf(anchor) : -1;
        parent.children.splice(index < 0 ? parent.children.length : index, 0, el);
        el.parent = parent;
    },
    remove(el) { if (el.parent) el.parent.children.splice(el.parent.children.indexOf(el), 1); el.parent = null; },
    parentNode: el => el.parent,
    nextSibling: el => el.parent?.children[el.parent.children.indexOf(el) + 1] ?? null,
});
const roots = [];
const changes = [];
const all = root => [root, ...root.children.flatMap(all)];
function mount(row) {
    const root = node('root');
    // The official DataTables wrapper mounts each cell slot using a separate render() root.
    renderer.render(h(ActionMenu, null, { default: () => h('button', { onClick: () => changes.push(row.id) }, 'Nonaktifkan') }), root);
    roots.push(root);
    return root;
}
function choose(root) {
    const gear = all(root).find(el => el.props.popovertarget);
    const menu = roots.flatMap(all).find(el => el.props.id === gear.props.popovertarget);
    assert.ok(menu, 'Gear harus memiliki target popover yang masih terpasang.');
    all(menu).find(el => el.tag === 'button').props.onClick();
}
function clear() { roots.splice(0).forEach(root => renderer.render(null, root)); }
try {
    mount({ id: 1, nama: 'Training' });
    const test = mount({ id: 99, nama: 'Test' });
    choose(test);
    assert.deepEqual(changes, [99], 'Gear Test harus menjalankan aksi ID Test, bukan ID baris pertama.');
    const seen = new Set(roots.flatMap(all).filter(el => el.props.id).map(el => el.props.id));
    assert.equal(seen.size, 2, 'ID popover harus unik lintas root Vue.');
    clear();
    for (const ids of [[99, 1, 42], [42, 99, 1], [99]]) {
        for (const id of ids) {
            const root = mount({ id });
            const menuId = all(root).find(el => el.props.id).props.id;
            assert.equal(seen.has(menuId), false, 'ID redraw tidak boleh bertabrakan dengan root lama.');
            seen.add(menuId);
        }
        roots.forEach((root, index) => { choose(root); assert.equal(changes.at(-1), ids[index]); });
        clear();
        assert.equal(listeners.size, 0, 'Listener menu lama harus dilepas saat redraw.');
    }
    console.log('ActionMenu: target/aksi ID baris benar lintas root, reorder, filter, redraw; listener dibersihkan.');
} finally {
    clear();
    globalThis.window = originalWindow;
}
