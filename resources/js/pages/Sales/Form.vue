<script setup lang="ts">
import TutorialButton from "../../components/TutorialButton.vue";
import { appUrl } from '../../composables/appUrl';
import CostingSimulation from "../../components/CostingSimulation.vue";
import MoneyInput from "../../components/MoneyInput.vue";
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import CancelProgramDialog from '../../components/CancelProgramDialog.vue';
import { calc, rupiah, rupiahInput, costQuantity } from '../../composables/costing';
import { useConfirmedMutation } from '../../composables/confirmedMutation';
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;
type Cost = { id_rate: number | null; tipe: 'fixed' | 'variable'; item: string; id_satuan: number; nama_satuan: string | null; harga: number | string; is_manual: number | boolean };
type Program = { id: number | null; nomor: string; status: string; closed_date: string | null; jenis: string; id_master_program: number | ''; id_customer: string; id_trainer: number | ''; id_asisten: number | ''; tgl_mulai: string; tgl_selesai: string; waktu: string; lokasi: string; jam_pembelajaran: number | string; durasi_hari: number; target_peserta: number; harga_jual_peserta: number | string; target_profit: number | string; biaya: Cost[] };
type Master = { id: number; nomor: string; judul: string; durasi_hari?: number; jam_pembelajaran: number; target_peserta: number; harga_jual_peserta: string; target_profit: string; biaya: Cost[] };
// Satu Master Program dalam penjualan (PJ-FR-32): jadwal, tim, harga, dan biayanya sendiri.
type Part = { id_master_program: number | ''; judul?: string; tgl_mulai: string; tgl_selesai: string; jam_pembelajaran: number | string; id_trainer: number | ''; id_asisten: number | ''; harga_jual_peserta: number | string; target_profit: number | string; biaya: Cost[] };
type Rate = { id: number; tipe: 'fixed' | 'variable'; item: string; id_satuan: number; nama_satuan: string; default_cost: string };
type Actual = { total_hpp: number; pendapatan: number; profit: number; margin: number };
const props = defineProps<{ program: Program; masters: Master[]; companies: { id_customer: string; name_customer: string }[]; trainers: { id: number; nama: string }[]; assistants: { id: number; nama: string; role: string | null }[]; rates: Rate[]; units: { id: number; nama: string }[]; can: { edit: boolean; approve: boolean; cancel: boolean }; jenisLocked: boolean; actual: { peserta: number; rencana: Actual; aktual: Actual } | null }>();
const money = (v: unknown) => Math.round(Number(v) || 0);
const cleanCosts = (list: Cost[]) => list.map((b) => ({ ...b, harga: money(b.harga) })) as Cost[];
const savedParts: Part[] = ((props.program as unknown as { masters?: Part[] }).masters ?? []).map((m) => ({ ...m, harga_jual_peserta: money(m.harga_jual_peserta), target_profit: money(m.target_profit), biaya: cleanCosts(m.biaya ?? []) }));
const form = useForm({ ...props.program, harga_jual_peserta: money(props.program.harga_jual_peserta) as number | string, target_profit: money(props.program.target_profit) as number | string, biaya: cleanCosts(props.program.biaya) });
// parts = semua Master Program; field datar form.* (jadwal, tim, harga, biaya) memegang yang sedang tampil, seperti mockup pfStore/pfLoad.
const parts = ref<Part[]>(savedParts.length ? savedParts : []);
const current = ref(0);
const ACTIVE = ['id_master_program', 'tgl_mulai', 'tgl_selesai', 'jam_pembelajaran', 'id_trainer', 'id_asisten', 'harga_jual_peserta', 'target_profit', 'biaya'] as const;
function store() { if (!parts.value.length) return; const f = form as unknown as Record<string, unknown>; const row = parts.value[current.value] as unknown as Record<string, unknown>; ACTIVE.forEach((k) => { row[k] = f[k]; }); }
function load(i: number) { store(); current.value = i; const f = form as unknown as Record<string, unknown>; const row = parts.value[i] as unknown as Record<string, unknown>; ACTIVE.forEach((k) => { f[k] = row[k]; }); }
if (parts.value.length) { const f = form as unknown as Record<string, unknown>; const row = parts.value[0] as unknown as Record<string, unknown>; ACTIVE.forEach((k) => { f[k] = row[k]; }); }
const editable = props.program.status === 'draft' && !props.program.closed_date && props.can.edit;
const errors = computed(() => Object.values(form.errors));
const masterById = (id: number | '') => props.masters.find((m) => m.id === Number(id));
// Master Program pertama (penjualan baru): jadwal dan tim kosong, JP, harga, dan biaya disalin dari Master Program.
function source() {
    const m = masterById(form.id_master_program);
    if (!m || props.program.id) return;
    form.jam_pembelajaran = m.jam_pembelajaran; form.target_peserta = m.target_peserta;
    form.harga_jual_peserta = money(m.harga_jual_peserta); form.target_profit = money(m.target_profit);
    form.biaya = cleanCosts(m.biaya);
    parts.value = [{ id_master_program: m.id, judul: m.judul, tgl_mulai: '', tgl_selesai: '', jam_pembelajaran: m.jam_pembelajaran, id_trainer: '', id_asisten: '', harga_jual_peserta: form.harga_jual_peserta, target_profit: form.target_profit, biaya: form.biaya }];
    current.value = 0;
}
// Dibuka dari Master Program Final (?master=): isi dari master itu, sama seperti memilih manual.
if (!props.program.id && props.program.id_master_program) source();
// Master Program tambahan (PJ-FR-32, [JUM-2]): tanggal, trainer, dan asisten kosong dan dipilih sendiri; JP dan costing disalin.
const addPick = ref<number | ''>('');
function addMaster() {
    const m = masterById(addPick.value); addPick.value = '';
    if (!m || !parts.value.length || parts.value.some((x) => Number(x.id_master_program) === m.id)) return;
    store();
    parts.value.push({ id_master_program: m.id, judul: m.judul, tgl_mulai: '', tgl_selesai: '', jam_pembelajaran: m.jam_pembelajaran, id_trainer: '', id_asisten: '', harga_jual_peserta: money(m.harga_jual_peserta), target_profit: money(m.target_profit), biaya: cleanCosts(m.biaya) });
    load(parts.value.length - 1);
}
function removeMaster(i: number) {
    if (parts.value.length < 2) return;
    store(); parts.value.splice(i, 1);
    load(Math.min(i === current.value ? 0 : current.value > i ? current.value - 1 : current.value, parts.value.length - 1));
}
const partDays = (p: Part) => (p.tgl_mulai && p.tgl_selesai ? Math.max(1, (Date.parse(p.tgl_selesai) - Date.parse(p.tgl_mulai)) / 86400000 + 1 || 1) : 1);
const usedIds = computed(() => parts.value.map((p) => Number(p.id_master_program)));
const addOptions = computed(() => props.masters.filter((m) => !usedIds.value.includes(m.id)));
const multi = computed(() => parts.value.length > 1);
// Hitungan per Master Program: biaya per hari memakai durasi Master Program itu (PJ-FR-32). Baris aktif dibaca dari form agar ikut terisi saat diketik.
const liveParts = computed<Part[]>(() => parts.value.map((p, i) => (i === current.value ? { ...p, tgl_mulai: form.tgl_mulai, tgl_selesai: form.tgl_selesai, harga_jual_peserta: form.harga_jual_peserta, target_profit: form.target_profit, biaya: form.biaya } : p)));
const partCalc = (p: Part) => calc(partDays(p), form.target_peserta, rupiahInput(p.harga_jual_peserta), p.biaya.map((b) => ({ ...b, harga: rupiahInput(b.harga), satuan: b.nama_satuan })));
const programResult = computed(() => {
    const cs = liveParts.value.map((p) => partCalc(p));
    const sum = (k: 'fixed' | 'varPer') => cs.reduce((t, c) => t + c[k], 0);
    const fixed = sum('fixed'), varPer = sum('varPer'), n = Number(form.target_peserta) || 0;
    const price = liveParts.value.reduce((t, p) => t + rupiahInput(p.harga_jual_peserta), 0);
    const totalVar = varPer * n, hpp = fixed + totalVar, pendapatan = price * n, profit = pendapatan - hpp, kontribusi = price - varPer;
    return { fixed, varPer, totalVar, hpp, pendapatan, profit, bep: kontribusi > 0 ? Math.max(1, Math.ceil(Math.round((fixed / kontribusi) * 1e6) / 1e6)) : null, hppPerPax: n > 0 ? hpp / n : 0, margin: pendapatan > 0 ? Math.round((profit / pendapatan) * 1000) / 10 : 0, price };
});
const programTarget = computed(() => liveParts.value.reduce((t, p) => t + rupiahInput(p.target_profit), 0));
const days = computed(() => Math.max(1, (Date.parse(form.tgl_selesai) - Date.parse(form.tgl_mulai)) / 86400000 + 1 || 1));
const result = computed(() => calc(days.value, form.target_peserta, rupiahInput(form.harga_jual_peserta), form.biaya.map((b) => ({ ...b, harga: rupiahInput(b.harga), satuan: b.nama_satuan }))));
const activeLabel = computed(() => (multi.value ? (masterById(form.id_master_program)?.nomor ?? '') : ''));
const pick = ref<Record<string, number | ''>>({ Event: '', Day: '', 'Peserta/Event': '', 'Peserta/Day': '' });
function addRate(unitName: string) {
    const r = props.rates.find((r) => r.id === Number(pick.value[unitName]) && r.nama_satuan === unitName); if (!r || form.biaya.some((b) => b.id_rate === r.id)) return;
    form.biaya.push({ id_rate: r.id, tipe: r.tipe, item: r.item, id_satuan: r.id_satuan, nama_satuan: r.nama_satuan, harga: Math.round(Number(r.default_cost)), is_manual: false }); pick.value[unitName] = '';
}
function costRows(category: 'fixed' | 'variable', unitName: string) {
    return form.biaya.map((b, i) => ({ b, i })).filter(({ b }) => b.tipe === category && (b.nama_satuan === unitName || (!b.nama_satuan && unitName === (category === 'fixed' ? 'Event' : 'Peserta/Event'))));
}
function revealInvalid(event: Event) {
    if (event.target instanceof HTMLElement) event.target.closest('details')?.setAttribute('open', '');
}
function save() {
    store();
    const payload = parts.value.map((p) => ({ id_master_program: p.id_master_program, tgl_mulai: p.tgl_mulai, tgl_selesai: p.tgl_selesai, jam_pembelajaran: p.jam_pembelajaran, id_trainer: p.id_trainer, id_asisten: p.id_asisten, harga_jual_peserta: p.harga_jual_peserta, target_profit: p.target_profit, biaya: p.biaya }));
    (form as unknown as { masters: unknown }).masters = payload;
    return confirmation.form(form, props.program.id ? 'put' : 'post', props.program.id ? `/penjualan-program/${props.program.id}` : '/penjualan-program', { title: 'Simpan Draft Penjualan Program?', text: props.program.nomor || 'Nomor dibuat otomatis saat disimpan.' }, { preserveState: 'errors' });
}
const STATUS_LABEL: Record<string, string> = { draft: 'Draft', final: 'Final', batal: 'Batal' };
const isNew = !props.program.id;
const b2bProgram = computed(() => form.jenis === 'b2b');
const lockText = computed(() => props.program.status === 'final'
    ? 'Penjualan Program Final — terkunci. Buat Quotation dari menu Quotation (' + (b2bProgram.value ? 'ke Company penjualan' : 'ke Company atau Personal') + ') — persiapan invoice dengan peserta yang sudah fix.' + (props.program.closed_date ? ' Program sudah Closed.' : ' Jadwal, trainer, dan asisten masih dapat diubah lewat Reschedule.')
    : props.program.status === 'batal' ? 'Penjualan Program dibatalkan dan tidak dapat diubah.' : '');
const transition = useForm({});
const cancelling = ref(false);
function status(kind: 'finalkan') { return confirmation.form(transition, 'post', `/penjualan-program/${props.program.id}/${kind}`, { title: `Finalkan ${props.program.nomor}?`, text: 'Program terkunci setelah Final. Periksa jadwal, tim dan costing sebelum melanjutkan.', confirmLabel: 'Ya, finalkan' }, { preserveState: 'errors' }); }
</script>
<template>
    <Head :title="program.nomor || 'Buat Penjualan Program'" />
    <AppLayout title="Penjualan Program" full-width>
        <div class="pf-formbar">
            <Link class="back-link" :href="appUrl('/penjualan-program')">← Kembali</Link>
            <h1>{{ program.nomor ? 'Penjualan Program ' + program.nomor : 'Buat Penjualan Program ' + (b2bProgram ? 'B2B' : 'Public') }}</h1>
            <TutorialButton />
        </div>
        <p v-if="lockText" class="notice">{{ lockText }}</p>
        <ul v-if="errors.length" class="error" role="alert"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
        <form class="sales-form" @submit.prevent="save" @invalid.capture="revealInvalid">
            <section class="csec" aria-labelledby="sales-header">
                <h2 id="sales-header" class="csec-h">Header</h2>
                <div class="grid3">
                    <label>No. penjualan<input :value="program.nomor || ''" placeholder="otomatis saat disimpan" readonly /></label>
                    <div><span class="field-label">Jenis penjualan</span><div class="static"><span class="tag">{{ form.jenis === 'b2b' ? 'B2B' : 'Public' }}</span></div></div>
                    <div><span class="field-label">Status</span><div class="static"><span class="badge" :class="'st-' + program.status">{{ STATUS_LABEL[program.status] ?? program.status }}{{ program.closed_date ? ' · Closed' : '' }}</span></div></div>
                </div>
            </section>

            <section v-if="parts.length" class="csec pj-mps-block" aria-labelledby="sales-masters" data-tour="table">
                <h2 id="sales-masters" class="csec-h">Master Program</h2>
                <div class="table-wrap">
                    <table class="pj-mps">
                        <caption class="sr-only">Master Program dalam penjualan</caption>
                        <thead><tr><th>Master Program</th><th>Jadwal &amp; tim</th><th class="money">Harga jual / peserta</th><th class="money">HPP</th><th class="money">Profit</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                        <tbody>
                            <tr v-for="(p, i) in liveParts" :key="String(p.id_master_program)" :class="{ on: i === current }">
                                <td><button type="button" class="pj-mp-btn" :aria-current="i === current ? 'true' : undefined" @click="load(i)"><span class="code">{{ masterById(p.id_master_program)?.nomor }}</span> {{ p.judul ?? masterById(p.id_master_program)?.judul }}</button><span v-if="i === current" class="tag">tampil di bawah</span></td>
                                <td><template v-if="p.tgl_mulai">{{ p.tgl_mulai }} – {{ p.tgl_selesai || '…' }} · {{ partDays(p) }} hari · {{ p.jam_pembelajaran }} JP</template><span v-else class="muted">Tanggal belum diisi</span><small>{{ trainers.find(t => t.id === Number(p.id_trainer))?.nama ?? 'Trainer —' }} · {{ assistants.find(a => a.id === Number(p.id_asisten))?.nama ?? 'Asisten —' }}</small></td>
                                <td class="money">{{ rupiah(rupiahInput(p.harga_jual_peserta)) }}</td><td class="money">{{ rupiah(partCalc(p).hpp) }}</td><td class="money" :class="{ neg: partCalc(p).profit < 0 }">{{ rupiah(partCalc(p).profit) }}</td>
                                <td><button v-if="editable && multi" type="button" class="row-remove" :aria-label="'Hapus ' + masterById(p.id_master_program)?.nomor + ' dari penjualan ini'" @click="removeMaster(i)">Hapus</button></td>
                            </tr>
                        </tbody>
                        <tfoot v-if="multi"><tr><th>Total program<small>{{ parts.length }} Master Program</small></th><td>{{ liveParts.reduce((t, p) => t + (Number(p.jam_pembelajaran) || 0), 0) }} JP</td><td class="money"><b>{{ rupiah(programResult.price) }}</b></td><td class="money"><b>{{ rupiah(programResult.hpp) }}</b></td><td class="money" :class="{ neg: programResult.profit < 0 }"><b>{{ rupiah(programResult.profit) }}</b></td><td /></tr></tfoot>
                    </table>
                </div>
                <div v-if="editable" class="pickrow"><select v-model="addPick" aria-label="Tambah Master Program" @change="addMaster"><option value="">+ Tambah Master Program Final…</option><option v-for="m in addOptions" :key="m.id" :value="m.id">{{ m.nomor }} — {{ m.judul }} · {{ rupiah(m.harga_jual_peserta) }}</option></select></div>
            </section>

            <details class="csec step" open>
                <summary id="sales-information" class="csec-h">1. Informasi Penjualan<span class="step-sum">{{ form.target_peserta }} peserta · {{ rupiah(form.harga_jual_peserta) }} / peserta</span></summary>
                <fieldset class="step-body" :disabled="!editable || form.processing" aria-labelledby="sales-information">
                    <div class="grid3">
                        <div v-if="parts.length"><span class="field-label">{{ multi ? 'Master Program (costing yang tampil)' : 'Master Program' }}</span><div class="static"><span class="code">{{ masterById(form.id_master_program)?.nomor }}</span> {{ masterById(form.id_master_program)?.judul }}</div></div>
                        <label v-else>Master Program<select v-model="form.id_master_program" required :disabled="!!program.id" @change="source"><option value="">— Pilih Master Program Final —</option><option v-for="m in masters" :key="m.id" :value="m.id">{{ m.nomor }} · {{ m.judul }}</option></select></label>
                        <label v-if="form.jenis === 'b2b'">Company<select v-model="form.id_customer" required><option value="">— Pilih Company aktif —</option><option v-for="c in companies" :key="c.id_customer" :value="c.id_customer">{{ c.name_customer }}</option></select></label>
                        <p v-if="multi" class="hint span3 mp-aktif-note">Tanggal, Jam Pembelajaran, trainer, dan asisten di bawah milik <b>{{ activeLabel }}</b>. Pilih Master Program lain di tabel untuk mengisi jadwalnya.</p>
                        <label>Tanggal mulai<input v-model="form.tgl_mulai" type="date" required /></label>
                        <label>Tanggal selesai<input v-model="form.tgl_selesai" type="date" :min="form.tgl_mulai" required /><small class="hint">durasi = selesai − mulai + 1 hari</small></label>
                        <label>Waktu<input v-model="form.waktu" maxlength="50" placeholder="mis. 09.00–15.15 WIB" /><small class="hint">Jam pelaksanaan; tercetak di Penawaran &amp; Formulir Konfirmasi</small></label>
                        <label>Jam Pembelajaran<div class="input-unit"><input v-model="form.jam_pembelajaran" type="number" min="1" required aria-label="Jam Pembelajaran" placeholder="mis. 16" /><span class="unit">JP</span></div></label>
                        <label>Lokasi acara<input v-model="form.lokasi" maxlength="150" placeholder="mis. Offline Bekasi" /></label>
                        <label>Trainer<select v-model="form.id_trainer" required><option value="">— Pilih trainer aktif —</option><option v-for="t in trainers" :key="t.id" :value="t.id">{{ t.nama }}</option></select></label>
                        <label>Asisten trainer<select v-model="form.id_asisten" required><option value="">— Pilih asisten aktif —</option><option v-for="a in assistants" :key="a.id" :value="a.id">{{ a.nama }}{{ a.role ? ' · ' + a.role : '' }}</option></select></label>
                    </div>
                    <div class="sim-in">
                        <h3>Input simulasi</h3>
                        <div class="grid4">
                            <label>Durasi (hari)<input :value="days" type="number" readonly /><small class="hint">otomatis dari tanggal</small></label>
                            <label>Target peserta<input v-model="form.target_peserta" type="number" :min="form.jenis === 'b2b' ? 10 : 1" required /></label>
                            <label>Harga jual / peserta (Rp)<MoneyInput v-model="form.harga_jual_peserta" required /></label>
                            <label>Target profit (Rp)<MoneyInput v-model="form.target_profit" /></label>
                        </div>
                    </div>
                </fieldset>
            </details>

            <p v-if="program.id && program.status === 'final'"><Link :href="appUrl('/penjualan-program/' + program.id + '/reschedule')">Reschedule / Riwayat</Link></p>
            <details v-for="(category, ci) in (['fixed', 'variable'] as const)" :key="category" class="csec step cost-section" open>
                <summary :id="'sales-' + category" class="csec-h">{{ ci + 2 }}. {{ category === 'fixed' ? 'Fixed Cost' : 'Variable Cost' }}<span v-if="multi" class="code">{{ activeLabel }}</span><span class="step-sum">Total {{ rupiah(category === 'fixed' ? result.fixed : result.totalVar) }}</span></summary>
                <fieldset class="step-body" :disabled="!editable || form.processing" :aria-labelledby="'sales-' + category">
                    <div v-for="unitName in (category === 'fixed' ? ['Event', 'Day'] : ['Peserta/Event', 'Peserta/Day'])" :key="unitName" class="cost-group">
                        <h3 class="csec-sub">{{ category === 'fixed' ? 'Fixed Cost' : 'Variable Cost' }} / {{ unitName }}<span v-if="!costRows(category, unitName).length" class="muted"> — belum ada item</span></h3>
                        <div v-if="editable" class="pickrow">
                            <select v-model="pick[unitName]" :aria-label="'Tambah dari Master Rate ' + unitName"><option value="">— Pilih item dari Master Rate —</option><option v-for="r in rates.filter(r => r.tipe === category && r.nama_satuan === unitName && !form.biaya.some(b => b.id_rate === r.id))" :key="r.id" :value="r.id">{{ r.item }} · {{ rupiah(r.default_cost) }}</option></select>
                            <button type="button" :disabled="!pick[unitName]" @click="addRate(unitName)">+ Tambah</button>
                            <button type="button" class="ghost" @click="form.biaya.push({ id_rate: null, tipe: category, item: '', id_satuan: units.find(u => u.nama === unitName)?.id || 0, nama_satuan: unitName, harga: '', is_manual: true })">+ Biaya lain</button>
                        </div>
                        <div v-if="costRows(category, unitName).length" class="table-wrap">
                            <table class="items">
                                <caption class="sr-only">{{ category === 'fixed' ? 'Fixed Cost' : 'Variable Cost' }} / {{ unitName }}</caption>
                                <thead><tr><th>Item</th><th class="money">{{ unitName.endsWith('Day') ? 'Nominal / hari' : 'Nominal / event' }}{{ category === 'variable' ? ' / peserta' : '' }}</th><th class="money">Qty</th><th class="money">Total</th><th><span class="sr-only">Aksi</span></th></tr></thead>
                                <tbody>
                                    <tr v-for="{ b, i } in costRows(category, unitName)" :key="i">
                                        <td><input v-if="b.is_manual" v-model="b.item" :aria-label="'Item ' + (i + 1)" placeholder="Nama biaya lain" maxlength="150" required /><span v-else>{{ b.item }}</span></td>
                                        <td class="money"><MoneyInput v-model="b.harga" :aria-label="'Nominal ' + b.item" required /></td>
                                        <td class="money">{{ costQuantity(b.tipe, b.nama_satuan, days, form.target_peserta) }}</td>
                                        <td class="money">{{ rupiah(rupiahInput(b.harga) * costQuantity(b.tipe, b.nama_satuan, days, form.target_peserta)) }}</td>
                                        <td><button v-if="editable" type="button" class="row-remove" :aria-label="'Hapus ' + b.item" @click="form.biaya.splice(i, 1)">×</button></td>
                                    </tr>
                                </tbody>
                                <tfoot><tr><td colspan="3" class="money"><b>Total</b></td><td class="money"><b>{{ rupiah(costRows(category, unitName).reduce((total, { b }) => total + rupiahInput(b.harga) * costQuantity(b.tipe, b.nama_satuan, days, form.target_peserta), 0)) }}</b></td><td /></tr></tfoot>
                            </table>
                        </div>
                    </div>
                    <div class="totals"><div class="total-row"><strong>Total {{ category === 'fixed' ? 'Fixed Cost' : 'Variable Cost' }}</strong><strong>{{ rupiah(category === 'fixed' ? result.fixed : result.totalVar) }}</strong></div></div>
                </fieldset>
            </details>

            <details class="csec step" open>
                <summary id="sales-simulation" class="csec-h">4. Simulasi Hasil<span v-if="multi" class="code">{{ activeLabel }}</span><span class="step-sum">Profit {{ rupiah((multi ? programResult : result).profit) }}</span></summary>
                <div class="step-body">
                    <div class="sim-in"><h3>Input simulasi</h3><div class="grid4">
                        <label>Durasi (hari)<input :value="days" type="number" readonly /></label>
                        <label>Target peserta<input v-model="form.target_peserta" type="number" :min="form.jenis === 'b2b' ? 10 : 1" :disabled="!editable || form.processing" required /></label>
                        <label>Harga jual / peserta (Rp)<MoneyInput v-model="form.harga_jual_peserta" :disabled="!editable || form.processing" required /></label>
                        <label>Target profit (Rp)<MoneyInput v-model="form.target_profit" :disabled="!editable || form.processing" /></label>
                    </div></div>
                    <p v-if="multi" class="hint">Ringkasan seluruh program ({{ parts.length }} Master Program): harga jual, HPP, pendapatan, dan profit dijumlah; biaya per hari memakai durasi tiap Master Program.</p>
                    <CostingSimulation :result="multi ? programResult : result" :participants="form.target_peserta" :price="multi ? programResult.price : form.harga_jual_peserta" :target-profit="multi ? programTarget : form.target_profit" />
                    <p v-if="(multi ? programResult : result).profit < 0 || ((multi ? programResult : result).bep ?? Infinity) > Number(form.target_peserta)" class="notice">Target belum mencapai titik impas.</p>
                </div>
            </details>
            <p v-for="e in transition.errors" :key="e" class="error" role="alert">{{ e }}</p>
            <div class="form-actions page-actions">
                <Link :href="appUrl('/penjualan-program')">{{ isNew ? 'Batal' : 'Kembali' }}</Link>
                <button v-if="program.id && (program.status === 'draft' || program.status === 'final') && !program.closed_date && can.cancel" type="button" class="danger" :disabled="confirming" @click="cancelling = true">Batalkan</button>
                <button v-if="editable" class="save-draft" :disabled="form.processing || confirming">{{ form.processing ? 'Menyimpan…' : 'Simpan Draft' }}</button>
                <button v-if="program.id && program.status === 'draft' && can.approve" type="button" class="primary" :disabled="confirming" @click="status('finalkan')">Finalkan → Quotation</button>
                <Link v-if="program.status === 'final' && !program.closed_date" :href="appUrl('/penjualan-program/' + program.id + '/reschedule')">Reschedule</Link>
                <Link v-if="program.status === 'final'" :href="appUrl('/quotation/batch/' + program.id)">Quotation</Link>
                <a v-if="program.status === 'final'" class="primary" :href="appUrl('/penjualan-program/' + program.id + '/pdf')" target="_blank" rel="noopener">Cetak Surat Penawaran</a>
            </div>
        </form>
        <section v-if="actual" class="panel"><h2>Rencana vs Aktual</h2><p>Aktual berdasarkan {{ actual.peserta }} peserta Deal dan harga Penjualan Program.</p><div class="table-wrap"><table><thead><tr><th>Perhitungan</th><th>Rencana</th><th>Aktual</th><th>Selisih</th></tr></thead><tbody><tr><th>Peserta</th><td class="money">{{ program.target_peserta }}</td><td class="money">{{ actual.peserta }}</td><td class="money">{{ actual.peserta - program.target_peserta }}</td></tr><tr v-for="[field, label] in ([['total_hpp', 'HPP'], ['pendapatan', 'Pendapatan'], ['profit', 'Profit']] as const)" :key="field"><th>{{ label }}</th><td class="money">{{ rupiah(actual.rencana[field]) }}</td><td class="money">{{ rupiah(actual.aktual[field]) }}</td><td class="money">{{ rupiah(actual.aktual[field] - actual.rencana[field]) }}</td></tr></tbody></table></div></section>
        <CancelProgramDialog v-if="cancelling && program.id" :id="program.id" :nomor="program.nomor" @close="cancelling = false" />
    </AppLayout>
</template>

<style scoped>
.pf-formbar { display: flex; flex-wrap: wrap; align-items: center; gap: 14px; margin-bottom: 16px; }
.pf-formbar h1 { margin: 0; font-size: 20px; color: var(--navy); }
.pf-formbar :deep(.tour-btn) { margin-left: auto; }
.back-link { display: inline-flex; align-items: center; min-height: 44px; text-decoration: none; font-weight: 600; }
.csec { border: 1px solid var(--line); border-radius: var(--radius); padding: 16px 18px; margin-bottom: 14px; background: var(--surface); box-shadow: var(--shadow); }
.csec-h { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; margin: 0 0 14px; padding-bottom: 10px; border-bottom: 1px solid var(--line); color: var(--navy); font-size: 15px; font-weight: 700; }
summary.csec-h { cursor: pointer; min-height: 44px; list-style: none; }
summary.csec-h::-webkit-details-marker { display: none; }
summary.csec-h::before { content: '▾'; width: 16px; color: var(--ink-50); }
details:not([open]) > summary { margin: 0; padding-bottom: 0; border: 0; }
details:not([open]) > summary::before { content: '▸'; }
.step-sum { display: none; margin-left: auto; color: var(--ink-70); font-size: 12.5px; font-weight: 500; }
details:not([open]) > summary .step-sum { display: inline; }
.step-body { border: 0; padding: 0; margin: 0; min-width: 0; }
.grid3 { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px 18px; }
.grid4 { display: grid; grid-template-columns: repeat(4, minmax(0, 1fr)); gap: 16px 14px; }
.grid3 > *, .grid3 label { min-width: 0; }
.span3 { grid-column: 1 / -1; }
.field-label, .sales-form label { font-size: 13px; font-weight: 600; }
.static { min-height: 44px; display: flex; flex-wrap: wrap; align-items: center; gap: 8px; font-size: 13px; }
.input-unit { display: flex; align-items: center; gap: 8px; }
.input-unit input { flex: 1; min-width: 0; }
.unit { color: var(--ink-70); font-weight: 500; }
.sim-in { margin-top: 16px; padding: 12px 14px; border: 1px solid rgb(213 163 32 / .45); border-radius: 8px; background: var(--orange-50); }
.sim-in h3 { margin: 0 0 10px; color: var(--orange-600); font-size: 13px; }
.sim-in :deep(input) { border-color: rgb(184 138 18 / .55); font-weight: 600; }
.pj-mps { min-width: 1000px; table-layout: fixed; }
.pj-mps th:nth-child(2) { width: 220px; }
.pj-mps th.money { width: 150px; }
.pj-mps th:last-child { width: 96px; }
.pj-mps td { vertical-align: top; }
.pj-mps .money { white-space: nowrap; }
.pj-mps tr.on td { background: var(--navy-50); }
.pj-mp-btn { display: block; width: 100%; border: 0; padding: 4px 0; background: transparent; text-align: left; white-space: normal; overflow-wrap: anywhere; line-height: 1.6; }
.pj-mps .tag { margin-top: 6px; }
.pj-mps-block > .pickrow { margin-top: 12px; margin-bottom: 0; }
.table-wrap { box-shadow: none; }
.cost-group + .cost-group { margin-top: 20px; padding-top: 0; border-top: 0; }
.csec-sub { margin: 16px 0 8px; color: var(--navy); font-size: 13px; font-weight: 700; }
.csec-sub .muted { font-weight: 400; }
.pickrow { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-bottom: 8px; }
.pickrow select { width: auto; min-width: min(280px, 100%); max-width: 100%; flex: 0 1 460px; }
.ghost { border-color: transparent; background: transparent; }
.items { min-width: 760px; table-layout: fixed; }
.items th { padding: 8px 10px; font-size: 12.5px; text-transform: none; letter-spacing: 0; }
.items th:nth-child(2) { width: 190px; }
.items th:nth-child(3) { width: 72px; }
.items th:nth-child(4) { width: 170px; }
.items th:last-child { width: 64px; }
.items td { padding: 7px 10px; }
.items td:first-child { overflow-wrap: anywhere; }
.items td.money { white-space: nowrap; }
.items td.money :deep(input) { width: 140px; max-width: 100%; display: inline-block; }
.row-remove { min-width: 44px; border: 0; background: transparent; color: #9C2636; padding: 4px 8px; }
.items .row-remove { font-size: 22px; line-height: 1; }
.total-row { display: flex; justify-content: space-between; gap: 12px; padding: 8px 0; }
.totals { margin-top: 10px; font-size: 13.5px; }
.page-actions { margin-top: 16px; }
@media (max-width: 760px) {
    .grid3, .grid4 { grid-template-columns: minmax(0, 1fr); }
    .step-sum { width: 100%; margin-left: 26px; }
    .pickrow select { flex-basis: 100%; }
}
</style>
