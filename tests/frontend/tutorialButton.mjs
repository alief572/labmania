import assert from 'node:assert/strict';
import { readFileSync, readdirSync } from 'node:fs';
import { parse, compileScript } from '@vue/compiler-sfc';
import { createRenderer, reactive } from 'vue';
import ts from 'typescript';
import { tutorialKey } from '../../resources/js/composables/tutorials.ts';

const pages = new URL('../../resources/js/pages/', import.meta.url);
for (const path of readdirSync(pages, { recursive: true }).filter(name => name.endsWith('.vue'))) {
    const component = path.replaceAll('\\', '/').slice(0, -4);
    if (!tutorialKey(component, {})) continue;
    const { descriptor } = parse(readFileSync(new URL(path.replaceAll('\\', '/'), pages), 'utf8'));
    assert.match(descriptor.template.content, /<(PageHead|TutorialButton)\b/, `${component}: kepala halaman harus memasang tutorial`);
}

const page = reactive({ component: 'Master/Atribut', props: {} });
globalThis.__tutorialButtonTest = { page, tutorialKey, startTutorial() {}, stopTutorial() {} };
async function component(name) {
    const file = new URL(`../../resources/js/components/${name}.vue`, import.meta.url);
    const { descriptor } = parse(readFileSync(file, 'utf8'), { filename: file.pathname });
    let script = compileScript(descriptor, { id: name, inlineTemplate: true }).content;
    script = script.replace(/import \{ usePage \} from '@inertiajs\/vue3';/, 'const usePage = () => globalThis.__tutorialButtonTest.page;')
        .replace(/import \{ startTutorial, stopTutorial \} from '[^']+';/, 'const { startTutorial, stopTutorial } = globalThis.__tutorialButtonTest;')
        .replace(/import \{ tutorialKey, type TutorialKey \} from '[^']+';/, 'const { tutorialKey } = globalThis.__tutorialButtonTest;')
        .replace(/import TutorialButton from '[^']+';/, 'const TutorialButton = globalThis.__tutorialButtonTest.button;')
        .replace(/from ['"]vue['"]/g, `from '${import.meta.resolve('vue')}'`);
    script = ts.transpileModule(script, { compilerOptions: { target: ts.ScriptTarget.ES2022, module: ts.ModuleKind.ES2022 } }).outputText;
    return (await import(`data:text/javascript;base64,${Buffer.from(script).toString('base64')}`)).default;
}
globalThis.__tutorialButtonTest.button = await component('TutorialButton');
const PageHead = await component('PageHead');
const node = type => ({ type, props: {}, children: [] });
const renderer = createRenderer({
    createElement: node, createText: text => ({ ...node('text'), text }), createComment: () => node('comment'),
    patchProp: (element, key, _, value) => { element.props[key] = value; },
    insert: (element, parent) => { element.parent = parent; parent.children.push(element); },
    remove: element => { element.parent.children = element.parent.children.filter(child => child !== element); },
    setText: (element, text) => { element.text = text; }, setElementText: (element, text) => { element.text = text; },
    parentNode: element => element.parent, nextSibling: () => null,
});
const buttons = root => [root, ...root.children.flatMap(buttons)].filter(item => item.type === 'button' && item.props['data-tutorial-key']);
for (const [name, props, expected] of [
    ['Master/Atribut', { title: 'Jenis Produk' }, 'master'],
    ['Master/Katalog', { title: 'Katalog Produk' }, 'katalog'],
    ['Quotation/Index', { title: 'Daftar Quotation' }, 'qtn'],
    ['Home', { title: 'Dashboard' }, undefined],
    ['Expenses/Program', { title: 'Fixed Cost', tutorial: false }, undefined],
]) {
    page.component = name;
    const root = node('root');
    const app = renderer.createApp(PageHead, props);
    app.mount(root);
    assert.deepEqual(buttons(root).map(button => button.props['data-tutorial-key']), expected ? [expected] : [], `${name}: tombol tutorial aktual`);
    app.unmount();
}
page.component = 'Sales/Start';
const directRoot = node('root');
const direct = renderer.createApp(globalThis.__tutorialButtonTest.button);
direct.mount(directRoot);
assert.deepEqual(buttons(directRoot).map(button => button.props['data-tutorial-key']), ['qtStart'], 'Halaman tanpa PageHead tetap memasang tombol');
direct.unmount();
console.log('Tombol tutorial: cakupan halaman dan render komponen PageHead lulus.');
