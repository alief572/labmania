import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import ts from 'typescript';
import { ref, watch, computed, effectScope } from 'vue';
import { navigationSections } from '../../resources/js/composables/navigation.ts';
import { appUrl } from '../../resources/js/composables/appUrl.ts';

// Execute the layout's actual search logic with a deterministic clock.
const source = readFileSync(new URL('../../resources/js/layouts/AppLayout.vue', import.meta.url), 'utf8');
const search = source.slice(source.indexOf("const search = ref('');"), source.indexOf('const currentSection'));
const enter = source.slice(source.indexOf('function searchKey('), source.indexOf('function resized('));
const js = ts.transpileModule(search + enter, { compilerOptions: { target: ts.ScriptTarget.ES2022 } }).outputText;
let now = 0, nextId = 0, cleanup;
const timers = new Map();
const setTimer = (fn, delay) => { const id = ++nextId; timers.set(id, { fn, at: now + delay }); return id; };
const clearTimer = id => timers.delete(id);
function tick(ms) {
    now += ms;
    for (const [id, timer] of [...timers]) if (timer.at <= now) { timers.delete(id); timer.fn(); }
}
const page = { url: '/', props: { navigation: [
    { label: 'Jenis Produk', url: '/master/jenis-produk', group: 'Master Data', sectionKey: '2', subgroupKey: '1' },
    { label: 'Master Trainer', url: '/master/trainer', group: 'Master Data', sectionKey: '2', subgroupKey: '3' },
] } };
const visits = [];
const scope = effectScope();
const state = scope.run(() => new Function('ref', 'watch', 'computed', 'onBeforeUnmount', 'setTimeout', 'clearTimeout', 'navigationSections', 'page', 'router', 'open', 'appUrl',
    js + '\nreturn { search, searchQuery, menus, searchKey };')(
    ref, watch, computed, fn => { cleanup = fn; }, setTimer, clearTimer, navigationSections, page,
    { visit: url => visits.push(url) }, ref(true), appUrl));
try {
    state.search.value = 'tr'; tick(200);
    assert.equal(state.searchQuery.value, '');
    state.search.value = 'trainer'; tick(249);
    assert.equal(state.searchQuery.value, '');
    assert.equal(timers.size, 1);
    tick(1);
    assert.equal(state.searchQuery.value, 'trainer');
    assert.equal(state.menus.value[0].groups[0].items[0].url, '/master/trainer');
    state.search.value = 'produk';
    state.searchKey({ key: 'Enter', preventDefault() {} });
    assert.deepEqual(visits, ['/master/jenis-produk']);
    assert.equal(timers.size, 0);
    state.search.value = 'trainer';
    state.search.value = ''; tick(250);
    assert.equal(state.searchQuery.value, '');
    assert.equal(timers.size, 0);
    state.search.value = 'trainer'; cleanup(); tick(250);
    assert.equal(state.searchQuery.value, '');
    assert.equal(timers.size, 0);
    assert.ok(!source.includes('${search.trim()}'));
    console.log('LULUS: debounce 250ms, reset timer, Enter hasil terbaru, clear langsung, cleanup unmount dan kelompok mengikuti query tertunda.');
} finally { cleanup(); scope.stop(); }

const controls = ts.transpileModule(
    source.slice(source.indexOf('async function showMenu('), source.indexOf('function trapFocus(')) +
    source.slice(source.indexOf('function resized('), source.indexOf('onMounted(() =>')),
    { compilerOptions: { target: ts.ScriptTarget.ES2022 } }).outputText;
const open = ref(false), collapsed = ref(true), mobile = ref(false);
let hiddenFocus = 0, closeFocus = 0, toggleFocus = 0;
const viewport = { innerWidth: 1280 };
const menu = new Function('open', 'collapsed', 'mobile', 'sidebar', 'closeButton', 'toggleButton', 'window', 'nextTick',
    controls + '\nreturn { showMenu, closeMenu, resized };')(
    open, collapsed, mobile, { value: { querySelector: () => ({ focus: () => hiddenFocus++ }) } },
    { value: { focus: () => closeFocus++ } }, { value: { focus: () => toggleFocus++ } }, viewport, async () => {});
menu.resized();
await menu.showMenu();
assert.equal(collapsed.value, false);
assert.equal(open.value, false, 'Membuka sidebar desktop tidak boleh meng-inert konten utama.');
assert.equal(hiddenFocus, 1);
viewport.innerWidth = 760; menu.resized(); await menu.showMenu();
assert.equal(mobile.value, true); assert.equal(open.value, true); assert.equal(closeFocus, 1);
await menu.closeMenu(); assert.equal(open.value, false); assert.equal(toggleFocus, 1);
await menu.showMenu(); viewport.innerWidth = 1280; menu.resized();
assert.equal(mobile.value, false); assert.equal(open.value, false);
console.log('LULUS: sidebar desktop/mobile, batas 760px, fokus kontrol dan konten utama tetap aktif di desktop.');
