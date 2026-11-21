import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { parse, compileScript } from '@vue/compiler-sfc';
import ts from 'typescript';
import { createRenderer, nextTick } from 'vue';
const file = new URL('../../resources/js/pages/Quotation/Form.vue', import.meta.url);
const source = readFileSync(file, 'utf8');
let code = ts.transpileModule(compileScript(parse(source).descriptor, { id: 'wizard' }).content, { compilerOptions: { module: ts.ModuleKind.ES2022, target: ts.ScriptTarget.ES2022 } }).outputText;
code = code.replace(/import (\w+) from ['"][^'"]+\.vue['"];?/g, (_, symbol) => `const ${symbol}={};`);
code = code.replace(/import \{ useConfirmedMutation \} from [^;]+;/, 'const useConfirmedMutation = () => ({pending: false});');
const pdfFile = new URL('../../resources/js/composables/pdf.ts', import.meta.url);
const pdfCode = ts.transpileModule(readFileSync(pdfFile, 'utf8'), { compilerOptions: { module: ts.ModuleKind.ES2022, target: ts.ScriptTarget.ES2022 } }).outputText.replace("'./appUrl'", `'${new URL('./appUrl.ts', pdfFile).href}'`);
const pdfModule = `data:text/javascript;base64,${Buffer.from(pdfCode).toString('base64')}`;
code = code.replace(/from (['"])([^'"]+)\1/g, (_, quote, path) => `from '${path.endsWith('/pdf') ? pdfModule : path.startsWith('.') ? new URL(path + '.ts', file).href : import.meta.resolve(path)}'`);
const component = (await import(`data:text/javascript;base64,${Buffer.from(code).toString('base64')}`)).default;
component.render = () => null;
const renderer = createRenderer({ patchProp() {}, insert() {}, remove() {}, createElement: () => ({}), createText: () => ({}), createComment: () => ({}), setText() {}, setElementText() {}, parentNode: () => null, nextSibling: () => null });
const originals = { fetch: globalThis.fetch, window: globalThis.window, document: globalThis.document };
const requests = [];
globalThis.window = { location: { search: '' } };
globalThis.document = { cookie: 'XSRF-TOKEN=test-token', querySelector: () => null };
globalThis.fetch = (url, options) => new Promise(resolve => requests.push({ url, options, resolve }));
const app = renderer.createApp(component, {
    quotation: { konfirmasi_pernyataan: '', id_template_konfirmasi: '', id_sales: 1, tanggal: '2026-11-20', berlaku_sampai: '2026-12-31', id: null, status: 'draft', id_penjualan: 7, harga_allin: 0, diskon: 0, biaya_lain: 0, konfirmasi_isian: ['nama'], kolom_peserta_b2b: [], keterangan: [], termin: [], kuota_peserta: 1 },
    programs: [{ id: 7, nomor: 'PJP-7', judul: '<Judul>', jenis: 'public', id_jenis_produk: 1, harga_jual_peserta: 100, tgl_mulai: '2026-11-25', tgl_selesai: '2026-11-25', waktu: '08:00', durasi_hari: 1, lokasi: 'Bekasi' }],
    templates: [{ id: 3, id_jenis_produk: 1, nama: 'Workshop', isian: ['nama', 'email'], pernyataan: '**{judul}** {lokasi}' }],
    companies: [], sales: [], fields: { nama: 'Nama', email: 'Email' }, keteranganOptions: [
        { id: 1, kategori: 'syarat', teks: 'Default public', is_default: 1, berlaku_untuk: 'public' },
        { id: 2, kategori: 'syarat', teks: 'Opsional', is_default: 0, berlaku_untuk: 'semua' },
        { id: 3, kategori: 'syarat', teks: 'Khusus B2B', is_default: 1, berlaku_untuk: 'b2b' },
        { id: 4, kategori: 'klausul', teks: 'Klausul sah', is_default: 1, berlaku_untuk: 'semua' },
    ], peserta: [], can: { edit: true, cancel: true }, revisions: [], documents: [], quotaOther: 0, salesOrder: null, companyConfirmation: null, link: null, linkClosed: null, calc: null,
});
try {
    const s = app.mount({}).$.setupState;
    assert.equal(s.ketChoices.length, 2);
    assert.equal(s.ketChoices[0].on, true);
    s.ketChoices[1].on = true;
    await nextTick();
    assert.ok(s.form.keterangan.some(d => d.teks === 'Opsional'));
    assert.ok(s.form.keterangan.some(d => d.kategori === 'klausul'));
    s.ketNew.syarat = 'Tambahan'; s.addKet('syarat');
    s.ketEditing = 0; s.ketText = 'Ubah lokal'; s.editKet();
    await nextTick();
    assert.equal(s.ketChoices[0].manual, true);
    s.resetKet(); await nextTick();
    assert.equal(s.ketChoices[0].teks, 'Default public');
    assert.deepEqual(s.form.keterangan.map(d => d.teks), ['Default public', 'Klausul sah']);
    s.extra = 'Tambahan'; s.addExtra(); s.extra = 'Tambahan'; s.addExtra();
    assert.equal(s.form.konfirmasi_isian.filter(x => x === 'Tambahan').length, 1);
    s.resetTemplate();
    assert.deepEqual([...s.form.konfirmasi_isian], ['nama', 'email']);
    assert.ok(s.statement.includes('**<Judul>**'));
    assert.ok(s.statement.join('').includes('Bekasi'));
    const response = document => new Response(JSON.stringify({ document }), { headers: { 'Content-Type': 'application/json' } });
    s.step = 2; await nextTick();
    assert.equal(requests[0].url, '/quotation/preview');
    assert.equal(JSON.parse(requests[0].options.body).preview_document, 'confirmation');
    assert.equal(requests[0].options.headers['X-XSRF-TOKEN'], 'test-token');
    requests[0].resolve(response('<!doctype html><p>Formulir</p>'));
    for (let i = 0; s.busy && i < 1000; i++) await new Promise(resolve => setTimeout(resolve, 1));
    assert.equal(s.previewDocument, '<!doctype html><p>Formulir</p>');
    s.form.konfirmasi_pernyataan = 'Pernyataan baru'; await nextTick();
    assert.equal(s.previewDocument, '');
    for (let i = 0; requests.length < 2 && i < 1000; i++) await new Promise(resolve => setTimeout(resolve, 1));
    assert.equal(JSON.parse(requests.at(-1).options.body).konfirmasi_pernyataan, 'Pernyataan baru');
    requests.at(-1).resolve(response('<p>Formulir terbaru</p>'));
    for (let i = 0; s.busy && i < 1000; i++) await new Promise(resolve => setTimeout(resolve, 1));
    assert.equal(s.previewDocument, '<p>Formulir terbaru</p>');
    s.step = 3; await nextTick();
    assert.equal(JSON.parse(requests.at(-1).options.body).preview_document, 'offer');
    requests.at(-1).resolve(response('<p>Penawaran</p>'));
    for (let i = 0; s.busy && i < 1000; i++) await new Promise(resolve => setTimeout(resolve, 1));
    assert.equal(s.previewDocument, '<p>Penawaran</p>');
    const failure = s.preview();
    requests.at(-1).resolve(new Response(JSON.stringify({ errors: { quotation: ['Lengkapi penawaran.'] } }), { status: 422, headers: { 'Content-Type': 'application/json' } }));
    await failure; assert.match(s.error, /Lengkapi penawaran/);
    const stale = s.preview(); const old = requests.at(-1);
    const fresh = s.preview();
    assert.equal(old.options.signal.aborted, true);
    requests.at(-1).resolve(response('<p>Terbaru</p>')); await fresh;
    old.resolve(new Response('expired', { status: 403 })); await stale;
    assert.equal(s.previewDocument, '<p>Terbaru</p>'); assert.equal(s.error, '');
    const pending = s.preview(); app.unmount();
    assert.equal(requests.at(-1).options.signal.aborted, true);
    requests.at(-1).resolve(response('<p>Lama</p>'));
    await pending; assert.equal(s.previewDocument, '');
} finally { Object.assign(globalThis, originals); }
assert.doesNotMatch(source, /v-html/);
assert.match(source, /q.id && \['draft', 'terkirim'\].includes\(q.status\) && can.cancel/);
assert.equal((source.match(/:srcdoc="previewDocument" sandbox=""/g) || []).length, 2);
assert.doesNotMatch(source, /fetchPdf|createObjectURL|window\.open|\.print\(/);
console.log('Quotation wizard: kategori/isian, preview dokumen tab3/tab4, perubahan terbaru, sandbox/error/race/abort lulus.');
