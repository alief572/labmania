import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import { parse, compileScript } from '@vue/compiler-sfc';
import ts from 'typescript';
import { createRenderer, effectScope, reactive, nextTick } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const dialogs = [];
let closed = 0;
globalThis.__confirmationTestSwal = {
    fire: options => new Promise(resolve => dialogs.push({ options, resolve })),
    close: () => { closed++; },
};
function moduleUrl(source) {
    const output = ts.transpileModule(source, { compilerOptions: { target: ts.ScriptTarget.ES2022, module: ts.ModuleKind.ES2022 } }).outputText;
    return `data:text/javascript;base64,${Buffer.from(output).toString('base64')}`;
}
function externalImports(source) {
    return source.replace(/from (['"])([^'"]+)\1/g, (_, quote, name) => `from '${import.meta.resolve(name)}'`);
}
let source = readFileSync(new URL('../../resources/js/composables/confirmedMutation.ts', import.meta.url), 'utf8');
source = source.replace(/import Swal from '[^']+';/, 'const Swal = globalThis.__confirmationTestSwal;').replace(/import '[^']+\.css';/g, '');
// Transpile first, so type-only imports need no runtime resolution.
const helperUrl = moduleUrl(externalImports(ts.transpileModule(source, { compilerOptions: { module: ts.ModuleKind.ES2022 } }).outputText));
const { useConfirmedMutation } = await import(helperUrl);
const originalVisit = router.visit;
const requests = [];
router.visit = (url, options) => {
    const visit = { url, cancelled: false, completed: false, interrupted: false };
    if (options.onBefore?.(visit) === false) return;
    options.onStart?.(visit);
    requests.push({ url, options, visit });
};
function finish(request = requests.at(-1)) { request.options.onFinish?.(request.visit); }
function helper() {
    const scope = effectScope();
    return { scope, action: scope.run(useConfirmedMutation) };
}
const renderer = createRenderer({ patchProp() {}, insert() {}, remove() {}, createElement: () => ({}), createText: () => ({}),
    createComment: () => ({}), setText() {}, setElementText() {}, parentNode: () => null, nextSibling: () => null });
const apps = [];
const pdfRequests = [];
globalThis.__confirmationTestPdf = async url => { pdfRequests.push(url); };
const pdfUrl = moduleUrl('export const postPdf = (...args) => globalThis.__confirmationTestPdf(...args);');
const programTransitionUrl = moduleUrl(ts.transpileModule(readFileSync(new URL('../../resources/js/composables/programTransition.ts', import.meta.url), 'utf8'), { compilerOptions: { module: ts.ModuleKind.ES2022 } }).outputText
    .replace(/from 'vue'/g, `from '${import.meta.resolve('vue')}'`).replace(/from '\.\/confirmedMutation'/g, `from '${helperUrl}'`).replace(/from '\.\/costing'/g, `from '${new URL('../../resources/js/composables/costing.ts', import.meta.url).href}'`));
globalThis.window = { location: { search: '' } };
globalThis.fetch = async () => ({ ok: true, json: async () => ({
    id: 77, nomor: 'MP-077', judul: 'Test', durasi: 2, jp: 16, target: 10,
    harga: 100000, hpp: 50000, profit: 50000, bep_peserta: 5, warnings: [],
}) });
async function page(name, props = {}) {
    const file = new URL(`../../resources/js/${name.startsWith('components/') ? name : 'pages/' + name}.vue`, import.meta.url);
    const original = readFileSync(file, 'utf8');
    const { descriptor } = parse(original);
    let script = compileScript(descriptor, { id: `test-${name}` }).content;
    script = ts.transpileModule(script, { compilerOptions: { module: ts.ModuleKind.ES2022 } }).outputText;
    script = script.replace(/import (\w+) from ['"][^'"]+\.vue['"];?/g, (_, symbol) => `const ${symbol} = {};`);
    script = script.replace(/from (['"])([^'"]+)\1/g, (_, quote, dependency) => {
        if (dependency.endsWith('/confirmedMutation')) return `from '${helperUrl}'`;
        if (dependency.endsWith('/pdf')) return `from '${pdfUrl}'`;
        if (dependency.endsWith('/programTransition')) return `from '${programTransitionUrl}'`;
        const url = dependency.startsWith('.') ? new URL(`${dependency}.ts`, file).href : import.meta.resolve(dependency);
        return `from '${url}'`;
    });
    const component = (await import(moduleUrl(script))).default;
    component.render = () => null;
    component.inheritAttrs = false;
    const app = renderer.createApp(component, {
        filters: { q: '', status: 'all', role: '', jenis: '', kelengkapan: 'all' }, slug: 'jenis-produk', label: 'Jenis Produk',
        can: { add: true, manage: true, delete: true }, tipe: 'fixed', title: 'Fixed Cost',
        companies: [], selected: [], inherited: [], roles: [{ id_group: 2, nm_group: 'Staf' }], users: [], menus: [],
        jenis: [], bidang: [], admin: 1, superadmin: 1, me: 7, satuans: [], coa: [], kategori: [], untuk: [], fields: {}, produk: [], groups: [],
        lingkupMax: { max_items: 20, max_length: 200 }, kind: 'role', editable: true, targetId: 2,
        history: [], reason: null, minimum: '', canManage: true, canAdd: true, canApprove: true, canCancel: true, canPay: true,
        salesOrder: null, sales: [], companyConfirmation: null, link: null, linkClosed: null, calc: null,
        tanggal: '2026-11-15', today: '2026-11-15', configurationErrors: {}, journal: [], snapshot: {}, setor: null,
        category: 'fixed', headers: {}, budgets: {}, budget: { items: [] }, notTeam: false, lastAccount: null, requestBy: 'Test',
        details: [], files: [], rows: [], counts: {}, canCertificates: true, canQuotation: true, survey: {},
        icons: [], aksi: {}, unmapped: [], closed: false, fieldLabels: {}, company: null, program: {},
        nomor: 'PJP-077', judul: 'Test', oldDate: null, newDate: '', endDate: '', deadline: '', canRegister: false,
        open: true, participants: [], years: [],
        options: { gender: [], agama: [], tipe: [], produk: [], bidang: [], varian: [], sertifikasi: [] }, ...props,
    });
    apps.push(app);
    return { app, state: app.mount({}).$.setupState, original };
}
try {
    const { scope, action } = helper();
    const data = reactive({ items: ['Lingkup awal'] });
    const before = requests.length;
    let call = action.visit('put', '/master/product/77/lingkup', data, { title: 'Simpan <img src=x>?' });
    assert.equal(action.pending.value, true);
    assert.equal(requests.length, before);
    assert.equal(dialogs.at(-1).options.titleText, 'Simpan <img src=x>?');
    assert.equal(dialogs.at(-1).options.html, undefined);
    assert.equal(dialogs.at(-1).options.title, undefined);
    assert.equal(dialogs.at(-1).options.focusCancel, true);
    assert.equal(dialogs.at(-1).options.returnFocus, true);
    assert.equal(dialogs.at(-1).options.allowOutsideClick, false);
    const dialogCount = dialogs.length;
    assert.equal(await action.visit('post', '/duplicate', {}, { title: 'Ganda?' }), false);
    assert.equal(dialogs.length, dialogCount);
    dialogs.at(-1).resolve({ isConfirmed: false, dismiss: 'esc' });
    assert.equal(await call, false);
    assert.equal(requests.length, before);
    assert.deepEqual(data.items, ['Lingkup awal']);
    assert.equal(action.pending.value, false);

    call = action.visit('put', '/master/product/77/lingkup', data, { title: 'Simpan?' });
    data.items[0] = 'Diubah ketika dialog terbuka';
    dialogs.at(-1).resolve({ isConfirmed: true });
    assert.equal(await call, true);
    assert.deepEqual(requests.at(-1).options.data.items, ['Lingkup awal']);
    assert.equal(action.pending.value, true);
    const sentCount = requests.length;
    assert.equal(await action.visit('post', '/duplicate', {}, { title: 'Ganda?' }), false);
    assert.equal(requests.length, sentCount);
    finish();
    assert.equal(action.pending.value, false);

    const file = new File(['PDF asli'], 'bukti.pdf', { type: 'application/pdf' });
    const form = scope.run(() => useForm({ nama: 'Trainer awal', lampiran: [{ file: null }] }));
    form.lampiran[0].file = file;
    call = action.form(form, 'post', '/master/trainer/77', { title: 'Simpan Trainer?' }, { forceFormData: true });
    form.nama = 'Trainer berubah';
    form.lampiran.push({ file: null });
    dialogs.at(-1).resolve({ isConfirmed: true });
    await call;
    const request = requests.at(-1);
    assert.equal(request.options.data.nama, 'Trainer awal');
    assert.equal(request.options.data.lampiran.length, 1);
    assert.equal(request.options.data.lampiran[0].file, file);
    assert.equal(request.options.forceFormData, true);
    request.options.onError?.({ nama: 'Nama tidak valid.' });
    finish(request);
    assert.equal(form.errors.nama, 'Nama tidak valid.');
    assert.equal(form.nama, 'Trainer berubah');
    assert.equal(form.processing, false);
    assert.equal(action.pending.value, false);
    // Snapshot transforms must not leak into subsequent ordinary form submissions.
    form.post('/ordinary');
    assert.equal(requests.at(-1).options.data.nama, 'Trainer berubah');
    finish();

    call = action.visit('delete', '/master/role/77', {}, { title: 'Hapus?' }, { onFinish: () => { throw new Error('callback gagal'); } });
    dialogs.at(-1).resolve({ isConfirmed: true });
    await call;
    assert.throws(() => finish(), /callback gagal/);
    assert.equal(action.pending.value, false);

    const sending = router.visit;
    router.visit = () => { throw new Error('gagal memulai request'); };
    call = action.form(form, 'post', '/failed', { title: 'Simpan?' });
    dialogs.at(-1).resolve({ isConfirmed: true });
    assert.equal(await call, false);
    assert.equal(dialogs.at(-1).options.icon, 'error');
    assert.equal(dialogs.at(-1).options.text, 'gagal memulai request');
    assert.equal(action.pending.value, false);
    router.visit = sending;

    call = action.visit('post', '/feedback', {}, { title: 'Simpan?' });
    dialogs.at(-1).resolve({ isConfirmed: true });
    await call;
    const feedbackRequest = requests.at(-1);
    feedbackRequest.options.onSuccess({ props: { flash: { success: 'Data disimpan <aman>.' } } });
    assert.equal(dialogs.at(-1).options.icon, 'success');
    assert.equal(dialogs.at(-1).options.text, 'Data disimpan <aman>.');
    assert.equal(dialogs.at(-1).options.html, undefined);
    feedbackRequest.options.onError({ field: 'Isian tidak valid.' });
    assert.equal(dialogs.at(-1).options.icon, 'error');
    assert.equal(dialogs.at(-1).options.text, 'Isian tidak valid.');
    assert.equal(feedbackRequest.options.onHttpException({ status: 419 }), false);
    assert.match(dialogs.at(-1).options.text, /Sesi berakhir/);
    assert.equal(feedbackRequest.options.onHttpException({ status: 403 }), false);
    assert.match(dialogs.at(-1).options.text, /akses/);
    assert.equal(feedbackRequest.options.onNetworkError(new Error('offline')), false);
    assert.match(dialogs.at(-1).options.text, /Koneksi terputus/);
    finish(feedbackRequest);

    let sentPayload;
    const asyncData = { id: 8, file };
    call = action.request(asyncData, { title: 'Kirim PDF?', successText: 'Quotation dikirim.' }, async payload => { sentPayload = payload; });
    asyncData.id = 99;
    dialogs.at(-1).resolve({ isConfirmed: true });
    await call;
    assert.equal(sentPayload.id, 8);
    assert.equal(sentPayload.file, file);
    assert.equal(dialogs.at(-1).options.text, 'Quotation dikirim.');
    assert.equal(action.pending.value, false);
    call = action.request({}, { title: 'Kirim?' }, async () => { throw new Error('PDF gagal'); });
    dialogs.at(-1).resolve({ isConfirmed: true });
    await call;
    assert.equal(dialogs.at(-1).options.icon, 'error');
    assert.equal(dialogs.at(-1).options.text, 'PDF gagal');
    assert.equal(action.pending.value, false);
    call = action.request({}, { title: 'Kirim?' }, async () => {}, { onSuccess: () => { throw new Error('navigasi gagal'); } });
    dialogs.at(-1).resolve({ isConfirmed: true }); await call;
    assert.match(dialogs.at(-1).options.text, /Perubahan tersimpan.*pembaruan halaman gagal/);
    assert.equal(action.pending.value, false);

    const late = helper();
    let release, navigated = false;
    const response = new Promise(resolve => { release = resolve; });
    const lateRequest = late.action.request({}, { title: 'Kirim PDF?' }, () => response, { onSuccess: () => { navigated = true; } });
    dialogs.at(-1).resolve({ isConfirmed: true });
    await Promise.resolve();
    const beforeLeaving = dialogs.length;
    late.scope.stop(); release(); await lateRequest;
    assert.equal(dialogs.length, beforeLeaving, 'Hasil request lama tidak membuka popup di halaman baru.');
    assert.equal(navigated, false, 'Hasil request lama tidak memindahkan halaman baru.');

    const leavingCount = requests.length;
    call = action.visit('post', '/must-not-send', {}, { title: 'Keluar?' });
    scope.stop();
    assert.equal(closed, 1);
    dialogs.at(-1).resolve({ isConfirmed: true });
    assert.equal(await call, false);
    assert.equal(requests.length, leavingCount);

    const row = { id: 77, id_group: 2, id_customer: 'CO-077', kode: 'X-077', nomor: 'PC-077',
        nama: 'Test', name_customer: 'Company Test', judul: 'Product Test', aktif: 1, username: 'test.user' };
    const scenarios = [];
    const standard = ['Atribut', 'Company', 'Peserta', 'Role', 'User', 'Rate', 'TemplateKeterangan', 'TemplateKonfirmasi', 'Trainer'];
    const paths = { Atribut: 'jenis-produk', Company: 'company', Peserta: 'peserta', Role: 'role', User: 'user', Rate: 'rate/fixed',
        TemplateKeterangan: 'template-keterangan', TemplateKonfirmasi: 'template-konfirmasi', Trainer: 'trainer' };
    for (const name of standard) {
        const base = `/master/${paths[name]}`;
        const id = name === 'Company' ? 'CO-077' : name === 'Role' ? 2 : 77;
        scenarios.push([name, 'add', 'save', 'post', base], [name, 'edit', 'save', name === 'Trainer' ? 'post' : 'put', `${base}/${id}`]);
        if (!['Peserta', 'Role'].includes(name)) scenarios.push([name, 'row', 'toggle', 'post', `${base}/${id}/toggle`]);
        if (['Peserta', 'Company', 'Role'].includes(name)) scenarios.push([name, 'row', 'remove', 'delete', `${base}/${id}`]);
    }
    scenarios.push(['User', 'reset', 'doReset', 'post', '/master/user/77/reset-password']);
    scenarios.push(['ProductForm', 'add', 'save', 'post', '/master/product'], ['Product', 'edit', 'saveEdit', 'put', '/master/product/77'],
        ['Product', 'row', 'toggle', 'post', '/master/product/77/toggle'],
        ['Katalog', 'edit', 'save', 'put', '/master/product/77/lingkup'], ['Permissions', 'role', 'save', 'put', '/permissions/roles/2'],
        ['PermissionOverride', 'user', 'save', 'put', '/permissions/users/77']);
    for (const slug of ['jenis-sertifikat', 'jenis-bidang', 'varian-pelaksanaan']) {
        for (const [mode, fn, method, suffix] of [['add', 'save', 'post', ''], ['edit', 'save', 'put', '/77'], ['row', 'toggle', 'post', '/77/toggle']]) {
            scenarios.push(['Atribut', mode, fn, method, `/master/${slug}${suffix}`, { slug }]);
        }
    }
    for (const [mode, fn, method, suffix] of [['add', 'save', 'post', ''], ['edit', 'save', 'put', '/77'], ['row', 'toggle', 'post', '/77/toggle']]) {
        scenarios.push(['Rate', mode, fn, method, `/master/rate/variable${suffix}`, { tipe: 'variable' }]);
    }
    for (const [name, mode, fn, method, url, props = {}] of scenarios) {
        const target = ['Permissions', 'PermissionOverride'].includes(name) ? name : `Master/${name}`;
        const { app, state, original } = await page(target, ['Permissions', 'PermissionOverride'].includes(name) ? { kind: mode, targetId: mode === 'user' ? 77 : 2, targetName: 'Test', kpi: { roles: 2, users: 1, menus: 3, hak: 9 } } : props);
        assert.doesNotMatch(original, /\b(?:router|form|\w+Form)\.(?:post|put|patch|delete|submit)\(/, target);
        if (state.form) Object.assign(state.form, Object.fromEntries(Object.entries({ nama: 'Test', item: 'Item Test', teks: 'Keterangan Test' }).filter(([key]) => key in state.form)));
        if (mode === 'edit') {
            if ('editing' in state) state.editing = name === 'Katalog' ? { item: row, bidang: 'Bidang Test' } : row;
            if ('mode' in state) state.mode = 'edit';
            if ('current' in state) state.current = row;
            if ('currentId' in state) { state.currentId = 77; state.currentCode = row.kode; }
        }
        if (name === 'Permissions') { state.editing = { id_group: 2, nm_group: 'Staf', sistem: false }; state.detail = { menus: [], selected: [], editable: true, n_user: 0 }; }
        if (mode === 'reset') { state.resetting = row; state.resetForm.password = 'JanganTampil'; state.resetForm.password_confirmation = 'JanganTampil'; }
        if (name === 'Product') { state.current = row; state.editForm.judul = row.judul; }
        if (name === 'ProductForm') { state.form.judul = row.judul; }
        const count = requests.length;
        let pending = state[fn](row);
        const title = dialogs.at(-1).options;
        assert.ok(title.titleText, `${target}:${fn} wajib menampilkan konfirmasi.`);
        assert.equal(JSON.stringify(title).includes('JanganTampil'), false);
        dialogs.at(-1).resolve({ isConfirmed: false });
        await pending;
        assert.equal(requests.length, count, `${target}:${fn} batal tidak boleh mengirim.`);
        const selectedRow = { ...row };
        pending = state[fn](selectedRow);
        // A redraw or stale reference must not switch the target after the dialog opened.
        selectedRow.id = 999;
        selectedRow.id_group = 999;
        selectedRow.id_customer = 'CO-999';
        dialogs.at(-1).resolve({ isConfirmed: true });
        await pending;
        assert.equal(requests.length, count + 1, `${target}:${fn} harus tepat satu request.`);
        assert.equal(requests.at(-1).url, url, `${target}:${fn}`);
        assert.equal(requests.at(-1).options.method, method, `${target}:${fn}`);
        finish();
        app.unmount();
    }
    const program = { id: 77, nomor: 'PJP-077', status: 'draft', jenis: 'public', paket: [], biaya: [], tgl_mulai: '2026-12-01', tgl_selesai: '2026-12-02', durasi_hari: 2 };
    const quotation = { id: 77, nomor: 'QTN-077', status: 'draft', id_penjualan: 77, kuota_peserta: 1, konfirmasi_isian: [], kolom_peserta_b2b: [], keterangan: [] };
    const base = { program, masters: [], rates: [], products: [], trainers: [], assistants: [], units: [], companies: [], can: { edit: true }, actual: null };
    const quoteProps = { quotation, programs: [], peserta: [], templates: [], keteranganOptions: [], documents: [], quotaOther: 0, can: { edit: true }, revisions: [] };
    const operations = [
        ...['finalkan', 'batalkan'].map(kind => ['Master/Program/Index', {}, 'ask', [row, kind], `/program/77/${kind}`]),
        ...['finalkan', 'batalkan'].map(kind => ['Sales/Index', {}, 'process', [{ ...row, costing: { pendapatan: 100000, total_hpp: 50000, profit: 50000, bep_peserta: 5 }, warnings: [] }, kind], `/penjualan-program/77/${kind}`]),
        ['Master/Program/Form', { ...base, satuan: { fixed: {}, variable: {} } }, 'save', [], '/program/77', 'put'],
        ['Sales/Form', base, 'save', [], '/penjualan-program/77', 'put'],
        ['Sales/Form', base, 'status', ['finalkan'], '/penjualan-program/77/finalkan'],
        ['Sales/Form', base, 'status', ['batalkan'], '/penjualan-program/77/batalkan'],
        ['Sales/Reschedule', { ...base, impact: { quotation: 1, peserta: 1, so: 1 }, requestId: 'request-77', version: 'v1' }, 'save', [], '/penjualan-program/77/reschedule'],
        ['Quotation/Form', quoteProps, 'save', [], '/quotation/77', 'put'],
        ['Quotation/Form', quoteProps, 'process', ['batalkan'], '/quotation/77/batalkan'],
        ['Quotation/Form', quoteProps, 'saveQuota', [], '/quotation/77/kuota'],
        ['Quotation/Form', quoteProps, 'saveDocuments', [], '/quotation/77/deal'],
        ['components/ReadyInvoice', { so: row, kepada: 'Test', count: 1, total: '100000' }, 'save', [], '/sales-order/77/siap-invoice'],
        ['Invoice/Form', { source: { so: row, amounts: { total_tagihan: '100000' } }, accounts: [], coa: 7 }, 'save', [], '/invoicing'],
        ['components/InvoiceDetail', { invoice: { id: 77, nomor: 'INV-077', piutang: '100000', ppn: '11000' } }, 'save', [], '/invoicing/77/pembayaran', 'post', s => { s.action = 'pembayaran'; }],
        ['components/InvoiceDetail', { invoice: { id: 77, nomor: 'INV-077', piutang: '100000', ppn: '11000' } }, 'save', [], '/invoicing/77/bukti-setor', 'post', s => { s.action = 'bukti-setor'; }],
        ['components/RefundDetail', { refund: { id: 77, nomor: 'RF-077', nominal_tagihan: '987654321012345678.00' } }, 'save', [], '/sales-order/refund/77'],
        ...['pengajuan', 'overbudget'].map(creating => ['Expenses/Program', { ...base, creating, budget: { items: [] } }, 'submit', [], `/pengajuan-biaya/program/77/${creating}`]),
        ...['setujui', 'tolak', 'batal', 'paid'].map(kind => ['Expenses/Show', { header: { id: 77, nomor: 'PB-077', total: '100000' }, kind: 'pengajuan' }, 'send', [kind], `/pengajuan-biaya/pengajuan/77/${kind}`]),
        ['Participants/Show', { program, filters: {}, warnings: [], blockers: [] }, 'save', [], '/manage-peserta/peserta/77/nama', 'post', s => { s.editing = row; }],
        ['Participants/Show', { program, filters: {}, warnings: [], blockers: [] }, 'closeProgram', [], '/manage-peserta/77/close'],
        ['Participants/Index', {}, 'closeProgram', [{...program,warnings:['Sertifikat belum dikirim'],blockers:[]}], '/manage-peserta/77/close'],
        ...['buka', 'tutup'].map(kind => ['Participants/Show', { program, filters: {}, warnings: [], blockers: [] }, 'surveyStatus', [kind], `/manage-peserta/77/survey/${kind}`]),
        ['Participants/Monitoring', {}, 'save', [], '/manage-peserta/peserta/77/catatan', 'post', s => { s.editing = row; }],
        ['components/QuotationParticipants', { rows: [], manage: true }, 'save', [], '/quotation/peserta/77/surat', 'post', s => { s.replacing = row; }],
        ['components/QuotationParticipants', { rows: [], manage: true }, 'saveAnswer', [], '/quotation/peserta/77/konfirmasi-ulang', 'post', s => { s.processing = row; }],
        ['Menu/Index', { moduls: [], groupLabels: {} }, 'save', [], '/pengaturan/menu'],
        ['Menu/Index', { moduls: [], groupLabels: {} }, 'toggle', [row], '/pengaturan/menu/77/toggle'],
        ['Menu/Index', { moduls: [], groupLabels: {} }, 'move', [row, 'up'], '/pengaturan/menu/77/pindah/up'],
        ['Confirmation', { info: { kuota: 1, jenis: 'public' }, token: 'public-token', closed: null }, 'submit', [], '/konfirmasi/public-token'],
        ['Reconfirmation', { people: [{ id: 77 }], token: 'public-token', revisi: 2, jenis: 'public' }, 'submit', [], '/konfirmasi/public-token/ulang'],
        ['Survey', { questions: [{ id: 1 }], token: 'public-token' }, 'submit', [], '/survey/public-token'],
    ];
    for (const [name, props, fn, args, url, method = 'post', prepare] of operations) {
        const { app, state } = await page(name, props);
        prepare?.(state);
        const count = requests.length;
        let pending = state[fn](...args);
        await Promise.resolve(); await Promise.resolve();
        assert.ok(dialogs.at(-1).options.showCancelButton, `${name}:${fn} confirmation`);
        dialogs.at(-1).resolve({ isConfirmed: false });
        await pending;
        assert.equal(requests.length, count, `${name}:${fn} cancel`);
        const form = state.form || state.deal;
        if (form && 'bukti' in form) form.bukti = Array.isArray(form.bukti) ? [file] : file;
        if (form && 'surat' in form) form.surat = file;
        const chosenArgs = args.map(value => value && typeof value === 'object' ? { ...value } : value);
        pending = state[fn](...chosenArgs);
        if (chosenArgs[0] && typeof chosenArgs[0] === 'object') chosenArgs[0].id = 999;
        await Promise.resolve(); await Promise.resolve();
        const dialogCount = dialogs.length;
        await state[fn](...args);
        assert.equal(dialogs.length, dialogCount, `${name}:${fn} duplicate`);
        dialogs.at(-1).resolve({ isConfirmed: true });
        await pending;
        const request = requests.at(-1);
        assert.equal(requests.length, count + 1, `${name}:${fn} single request`);
        assert.equal(request.url, url, `${name}:${fn} URL`);
        assert.equal(request.options.method, method, `${name}:${fn} method`);
        if (request.options.data.bukti) assert.equal(Array.isArray(request.options.data.bukti) ? request.options.data.bukti[0] : request.options.data.bukti, file);
        if (request.options.data.surat) assert.equal(request.options.data.surat, file);
        request.options.onError({ field: 'Validasi gagal.' });
        finish(request);
        if (form && 'surat' in form) assert.equal(form.surat, file, 'Error mempertahankan lampiran.');
        if (name === 'components/RefundDetail') assert.equal(request.options.data.nominal_refund, '987654321012345678');
        app.unmount();
    }
    const pdfPage = await page('Quotation/Form', quoteProps);
    let send = pdfPage.state.process('kirim');
    dialogs.at(-1).resolve({ isConfirmed: false }); await send;
    assert.equal(pdfRequests.length, 0);
    send = pdfPage.state.process('kirim');
    const duplicateDialogs = dialogs.length;
    await pdfPage.state.process('kirim'); assert.equal(dialogs.length, duplicateDialogs);
    dialogs.at(-1).resolve({ isConfirmed: true }); await send;
    assert.deepEqual(pdfRequests, ['/quotation/77/kirim']);
    assert.equal(dialogs.at(-1).options.icon, 'success');
    assert.equal(requests.at(-1).options.method, 'get', 'Sesudah kirim PDF, hanya refresh halaman.');
    finish();
    globalThis.__confirmationTestPdf = async () => { throw new Error('PDF ditolak server.'); };
    send = pdfPage.state.process('kirim'); dialogs.at(-1).resolve({ isConfirmed: true }); await send;
    assert.equal(pdfPage.state.error, 'PDF ditolak server.');
    assert.equal(dialogs.at(-1).options.icon, 'error');
    pdfPage.app.unmount();
    const participantPage = await page('components/QuotationParticipants', { rows: [], manage: true, dataUrl: '/quotation/batch/77/data', filters: { data: 1, id_quotation: 77 }, statusFilter: true });
    assert.equal(participantPage.state.serverFilters.peserta_status, 'all');
    participantPage.state.status = 'batal';
    assert.deepEqual(participantPage.state.serverFilters, { data: 1, id_quotation: 77, peserta_status: 'batal' });
    participantPage.app.unmount();
    await nextTick();
    console.log(`Konfirmasi: ${scenarios.length} alur Master Data dan ${operations.length} alur transaksi/publik lulus; cancel/confirm, URL/metode, snapshot/File, pending/ganda, error/finish, fokus dan unmount.`);
} finally {
    apps.forEach(app => app.unmount());
    router.visit = originalVisit;
    delete globalThis.__confirmationTestSwal;
    delete globalThis.window;
    delete globalThis.__confirmationTestPdf;
    delete globalThis.fetch;
}
