import assert from 'node:assert/strict';
import { readFileSync, readdirSync } from 'node:fs';
import ts from 'typescript';
import { tutorials, tutorialKey } from '../../resources/js/composables/tutorials.ts';

const mockup = new URL('../../specs/ui-mockup/produk-to-be/', import.meta.url);
const keys = new Set(readdirSync(mockup).filter(name => /^mod-.*\.js$/.test(name)).flatMap(name => [...readFileSync(new URL(name, mockup), 'utf8').matchAll(/TOUR\.(\w+)\s*=/g)].map(match => match[1])));
assert.equal(keys.size, 38);
assert.deepEqual(Object.keys(tutorials).sort(), [...keys].sort());
assert.equal(tutorialKey('Dashboard', {}), undefined);
assert.equal(tutorialKey('Public/Confirmation', {}), undefined);
assert.equal(tutorialKey('Menu', {}), undefined);
assert.equal(tutorialKey('Sales/Start', {}), 'qtStart');
assert.equal(tutorialKey('Sales/Form', { program: {} }), 'qtForm');
assert.equal(tutorialKey('Sales/Form', { program: { id: 1 } }), 'qtForm');
assert.equal(tutorialKey('Quotation/Form', { quotation: { id: 1, status: 'deal' } }), 'qtnDetail');
assert.equal(tutorialKey('SalesOrder/Index', { tab: 'surat' }), 'skt');
assert.equal(tutorialKey('Refund/Index', { mode: 'approval' }), 'rfAppr');
assert.equal(tutorialKey('Expenses/Program', { creating: true }), 'pbForm');
for (const steps of Object.values(tutorials)) {
    assert.ok(steps.length > 1);
    for (const step of steps) if (step.next) assert.ok(tutorials[step.next]);
}

// Run actual controller with a minimal DOM and Driver boundary; no network or table mock.
const source = readFileSync(new URL('../../resources/js/composables/tutorial.ts', import.meta.url), 'utf8');
let options, navigation, failed = false, delayed;
let destroyed = 0, driven = 0, focused = 0, observed = 0;
globalThis.__tutorialTest = {
    tutorials,
    router: { on: (_, callback) => { navigation = callback; return () => { navigation = undefined; }; }, visit: () => assert.fail('No destination rendered: navigation forbidden') },
    load: async () => { if (delayed) await delayed; if (failed) throw Error('offline'); return { driver: config => {
        options = config;
        return { drive: () => driven++, destroy: () => { destroyed++; config.onDestroyed(); }, getActiveElement: () => undefined };
    } }; },
};
let script = source.replace(/import \{ nextTick \} from 'vue';/, 'const nextTick = async () => {};')
    .replace(/import \{ router \} from '@inertiajs\/vue3';/, 'const { router } = globalThis.__tutorialTest;')
    .replace(/import \{ tutorials, type TutorialKey \} from '.\/tutorials';/, 'const { tutorials } = globalThis.__tutorialTest;')
    .replace(/await import\('driver.js'\)/g, 'await globalThis.__tutorialTest.load()');
script = ts.transpileModule(script, { compilerOptions: { target: ts.ScriptTarget.ES2022, module: ts.ModuleKind.ES2022 } }).outputText;
const { startTutorial, stopTutorial, tutorialTarget } = await import(`data:text/javascript;base64,${Buffer.from(script).toString('base64')}`);
globalThis.CSS = { escape: value => value };
globalThis.getComputedStyle = () => ({ visibility: 'visible' });
globalThis.MutationObserver = class { observe() { observed++; } disconnect() { observed--; } };
globalThis.document = { body: {} };
const element = (shown, textContent = '') => ({ isConnected: true, getClientRects: () => shown ? [1] : [], closest: () => null, textContent });
const hidden = element(false), visible = element(true, 'Nomor SO');
assert.equal(tutorialTarget({ querySelectorAll: () => [hidden, visible] }, 'column:Nomor SO'), visible);
assert.equal(tutorialTarget({ querySelectorAll: () => [hidden] }, 'table'), undefined);
const tutorialOnly = { ...element(true), closest: () => ({}) };
const actionContainer = { ...element(true), querySelectorAll: () => [tutorialOnly] };
assert.equal(tutorialTarget({ querySelectorAll: () => [actionContainer] }, 'actions'), undefined, 'Tutorial button alone is not an Add permission');
const addAction = { ...element(true), matches: () => false };
actionContainer.querySelectorAll = () => [tutorialOnly, addAction];
assert.equal(tutorialTarget({ querySelectorAll: () => [actionContainer] }, 'actions'), actionContainer);
const root = { querySelector: () => null, querySelectorAll: () => [] };
const trigger = { ...element(true), matches: () => false, focus: () => focused++ };
await startTutorial('qtn', trigger, root);
assert.equal(driven, 1);
assert.equal(options.steps.length, 1, 'Missing targets and destinations skipped, including empty tables');
assert.equal(options.disableActiveInteraction, true);
assert.equal(options.progressText, '{{current}} dari {{total}}');
await startTutorial('so', trigger, root);
assert.equal(destroyed, 1, 'Only one active tour');
navigation();
assert.equal(destroyed, 2);
assert.equal(observed, 0);
assert.equal(navigation, undefined);
assert.equal(focused, 2);
failed = true;
await assert.rejects(startTutorial('qtn', trigger, root), /offline/);
assert.equal(navigation, undefined, 'Failed load cleans navigation listener');
failed = false;
await startTutorial('qtn', trigger, root);
const destroyedBeforeClose = destroyed;
options.onDestroyed();
assert.equal(destroyed, destroyedBeforeClose, 'Natural close does not destroy same Driver twice');
assert.equal(observed, 0);
assert.equal(navigation, undefined);
let release;
delayed = new Promise(resolve => { release = resolve; });
const pending = startTutorial('qtn', trigger, root);
stopTutorial();
const before = driven;
release();
await pending;
assert.equal(driven, before, 'Unmount/navigation cancels pending lazy load');
delayed = undefined;
const nextLink = Object.assign(element(true), { matches: () => false, href: 'http://localhost/penjualan-program/create?master=1&jenis=public' });
const startRoot = { querySelector: selector => selector === '[data-tour-next="qtForm"]' ? nextLink : null, querySelectorAll: () => [] };
await startTutorial('qtStart', trigger, startRoot);
const continuation = options.steps.at(-1).popover;
assert.equal(continuation.doneBtnText, 'Lanjut →', 'Last continuation must not be labelled Selesai');
stopTutorial();
console.log('Tutorial: 38 definisi mockup, resolver, target, lifecycle, pembatalan dan retry lulus.');
