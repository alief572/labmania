import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { parse, compileScript } from '@vue/compiler-sfc';
import ts from 'typescript';
import { createRenderer, h, ref, nextTick } from 'vue';

const source = readFileSync(new URL('../../resources/js/components/MoneyInput.vue', import.meta.url), 'utf8');
const { descriptor } = parse(source);
const script = compileScript(descriptor, { id: 'money-test', inlineTemplate: true }).content
    .replace(/from (['"])vue\1/g, `from '${import.meta.resolve('vue')}'`);
const compiled = ts.transpileModule(script, { compilerOptions: { target: ts.ScriptTarget.ES2022, module: ts.ModuleKind.ES2022 } }).outputText;
const MoneyInput = (await import(`data:text/javascript;base64,${Buffer.from(compiled).toString('base64')}`)).default;
const model = ref('');
const maxDigits = ref(13);
const renderer = createRenderer({
    createElement: () => ({ props: {}, value: '', selectionStart: 0, selectionEnd: 0,
        setSelectionRange(start, end) { this.selectionStart = start; this.selectionEnd = end; },
        setRangeText(text, start, end) {
            this.value = this.value.slice(0, start) + text + this.value.slice(end);
            this.setSelectionRange(start + text.length, start + text.length);
        },
    }),
    patchProp(el, key, _, value) { el.props[key] = value; if (key === 'value') el.value = value; },
    insert(el, parent) { parent.child = el; }, remove() {}, createText() {}, createComment() {},
    setText() {}, setElementText() {}, parentNode() {}, nextSibling() {},
});
const root = {};
renderer.createApp({ setup: () => () => h(MoneyInput, {
    maxDigits: maxDigits.value,
    modelValue: model.value, 'onUpdate:modelValue': value => { model.value = value; },
    id: 'harga', required: true, disabled: false, 'aria-label': 'Harga',
}) }).mount(root);
const el = root.child;
async function set(value) { model.value = value; await nextTick(); }
async function input(text, position = text.length) {
    el.value = text; el.setSelectionRange(position, position);
    el.props.onInput({ target: el }); await nextTick();
}
async function paste(text) {
    let prevented = false;
    el.props.onPaste({ target: el, clipboardData: { getData: () => text }, preventDefault() { prevented = true; } });
    await nextTick(); assert.equal(prevented, true);
}
assert.match(source, /text-align:\s*right/);
assert.equal(el.props.id, 'harga');
assert.equal(el.props.required, true);
assert.equal(el.props.disabled, false);
assert.equal(el.props['aria-label'], 'Harga');
assert.equal(el.props.inputmode, 'numeric');
await input('1500000');
assert.equal(el.value, '1.500.000'); assert.equal(model.value, '1500000');
assert.equal(Number(model.value) * 2, 3000000);
await input(''); assert.equal(model.value, ''); assert.equal(el.value, '');
await input('000'); assert.equal(model.value, '0'); assert.equal(el.value, '0');
await set('1500000.00'); assert.equal(el.value, '1.500.000');
await set(42000); assert.equal(el.value, '42.000');
await set(null); assert.equal(el.value, '');
await set(''); await paste('1.500.000'); assert.equal(model.value, '1500000');
for (const invalid of ['1.5', '1,5', '-100', '1e6', 'Rp 100']) {
    el.setSelectionRange(0, el.value.length); await paste(invalid);
    assert.equal(model.value, '1500000'); assert.equal(el.value, '1.500.000');
}
el.setSelectionRange(0, el.value.length); await paste('9876543210000');
assert.equal(model.value, '9876543210000'); assert.equal(el.value, '9.876.543.210.000');
await input('98.765.432.100.000'); assert.equal(model.value, '9876543210000');
assert.equal(el.value, '9.876.543.210.000');
await set('1234567');
await input('1.9234.567', 3); assert.equal(model.value, '19234567');
assert.equal(el.value, '19.234.567'); assert.equal(el.selectionStart, 2);
await set('1234567'); el.setSelectionRange(2, 2);
el.props.onKeydown({ target: el, key: 'Backspace', preventDefault() {} }); await nextTick();
assert.equal(model.value, '234567'); assert.equal(el.value, '234.567');
await set('1234567'); el.setSelectionRange(1, 1);
el.props.onKeydown({ target: el, key: 'Delete', preventDefault() {} }); await nextTick();
assert.equal(model.value, '134567'); assert.equal(el.value, '134.567');
await set('1234567'); el.setSelectionRange(2, 5); await paste('900');
assert.equal(model.value, '1900567'); assert.equal(el.value, '1.900.567');
await input('-1'); assert.equal(model.value, '1900567');
await input('1e6'); assert.equal(model.value, '1900567');
await set(''); await paste('123000'); assert.equal(model.value, '123000');
maxDigits.value = 18; await nextTick();
assert.equal(el.props.maxlength, 23);
await set(''); await paste('987654321012345678');
assert.equal(model.value, '987654321012345678');
assert.equal(el.value, '987.654.321.012.345.678');
await input('9.876.543.210.123.456.789');
assert.equal(model.value, '987654321012345678');
await set('987654321012345678.00');
assert.equal(el.value, '987.654.321.012.345.678');
for (const [page, count] of [['Master/Program/Form', 5], ['Sales/Form', 5], ['Quotation/Form', 4], ['Expenses/Program', 1], ['Master/Rate', 1]]) {
    const text = readFileSync(new URL(`../../resources/js/pages/${page}.vue`, import.meta.url), 'utf8');
    assert.equal((text.match(/<MoneyInput\b/g) ?? []).length, count, page);
}
const expense = readFileSync(new URL('../../resources/js/pages/Expenses/Program.vue', import.meta.url), 'utf8');
assert.match(expense, /<input v-model="i.qty"[^>]*step="0.01"/);
assert.match(expense, /<input v-model="form.no_rekening"/);
const refund = readFileSync(new URL('../../resources/js/components/RefundDetail.vue', import.meta.url), 'utf8');
assert.match(refund, /<MoneyInput v-model="proses.nominal_refund" :max-digits="18"/);
assert.match(refund, /<MoneyInput v-model="perbaiki.nominal" :max-digits="18"/);
assert.match(refund, /<input v-model="proses.no_rekening"/);
console.log('MoneyInput: format/model mentah, nol/kosong, DECIMAL, paste, batas 13/18 digit, caret, penghapusan, atribut, 16 binding dan qty/rekening lulus.');

const master = readFileSync(new URL('../../resources/js/pages/Master/Program/Form.vue', import.meta.url), 'utf8');
for (const field of ['durasi_hari', 'jam_pembelajaran', 'target_peserta']) {
    assert.ok(master.includes(`v-model="form.${field}" inputmode="numeric"`));
}
