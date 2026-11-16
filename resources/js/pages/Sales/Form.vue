<script setup lang="ts">
import CostingSimulation from "../../components/CostingSimulation.vue";
import MoneyInput from "../../components/MoneyInput.vue";
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import { calc, rupiah, rupiahInput, costQuantity } from '../../composables/costing';
import { useConfirmedMutation } from '../../composables/confirmedMutation';
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;
type Cost = { id_rate: number | null; tipe: 'fixed' | 'variable'; item: string; id_satuan: number; nama_satuan: string | null; harga: number | string; is_manual: number | boolean };
type Program = { id: number | null; nomor: string; status: string; closed_date: string | null; jenis: string; id_master_program: number | ''; id_customer: string; id_trainer: number | ''; id_asisten: number | ''; tgl_mulai: string; tgl_selesai: string; waktu: string; lokasi: string; jam_pembelajaran: number | string; durasi_hari: number; target_peserta: number; harga_jual_peserta: number | string; target_profit: number | string; biaya: Cost[] };
type Master = { id: number; nomor: string; judul: string; jam_pembelajaran: number; target_peserta: number; harga_jual_peserta: string; target_profit: string; biaya: Cost[] };
type Rate = { id: number; tipe: 'fixed' | 'variable'; item: string; id_satuan: number; nama_satuan: string; default_cost: string };
type Actual = { total_hpp: number; pendapatan: number; profit: number; margin: number };
const props = defineProps<{ program: Program; masters: Master[]; companies: { id_customer: string; name_customer: string }[]; trainers: { id: number; nama: string }[]; assistants: { id: number; nama: string; role: string | null }[]; rates: Rate[]; units: { id: number; nama: string }[]; can: { edit: boolean; approve: boolean; cancel: boolean }; actual: { peserta: number; rencana: Actual; aktual: Actual } | null }>();
const form = useForm({ ...props.program, harga_jual_peserta: Math.round(Number(props.program.harga_jual_peserta)) as number | string, target_profit: Math.round(Number(props.program.target_profit)) as number | string, biaya: props.program.biaya.map((b) => ({ ...b, harga: Math.round(Number(b.harga)) })) as Cost[] });
const editable = props.program.status === 'draft' && !props.program.closed_date && props.can.edit;
const errors = computed(() => Object.values(form.errors));
const choiceSearch = ref('');
const matches = (label: string) => label.toLocaleLowerCase('id-ID').includes(choiceSearch.value.toLocaleLowerCase('id-ID'));
function source() {
    const m = props.masters.find((m) => m.id === Number(form.id_master_program));
    if (!m || props.program.id) return;
    form.jam_pembelajaran = m.jam_pembelajaran; form.target_peserta = m.target_peserta;
    form.harga_jual_peserta = Math.round(Number(m.harga_jual_peserta)); form.target_profit = Math.round(Number(m.target_profit));
    form.biaya = m.biaya.map((b) => ({ ...b, harga: Math.round(Number(b.harga)) }));
}
// Dibuka dari Master Program Final (?master=): isi biaya dan target dari master itu, sama seperti memilih manual.
if (!props.program.id && props.program.id_master_program) source();
const days = computed(() => Math.max(1, (Date.parse(form.tgl_selesai) - Date.parse(form.tgl_mulai)) / 86400000 + 1 || 1));
const result = computed(() => calc(days.value, form.target_peserta, rupiahInput(form.harga_jual_peserta), form.biaya.map((b) => ({ ...b, harga: rupiahInput(b.harga), satuan: b.nama_satuan }))));
const pick = ref('');
function addRate() {
    const r = props.rates.find((r) => r.id === Number(pick.value)); if (!r) return;
    form.biaya.push({ id_rate: r.id, tipe: r.tipe, item: r.item, id_satuan: r.id_satuan, nama_satuan: r.nama_satuan, harga: Math.round(Number(r.default_cost)), is_manual: false }); pick.value = '';
}
function unit(b: Cost) { b.nama_satuan = props.units.find((u) => u.id === Number(b.id_satuan))?.nama ?? null; }
function save() { return confirmation.form(form, props.program.id ? 'put' : 'post', props.program.id ? `/penjualan-program/${props.program.id}` : '/penjualan-program', { title: 'Simpan Draft Penjualan Program?', text: props.program.nomor || 'Nomor dibuat otomatis saat disimpan.' }, { preserveState: 'errors' }); }
const STATUS_LABEL: Record<string, string> = { draft: 'Draft', final: 'Final', batal: 'Batal' };
const isNew = !props.program.id;
const b2bProgram = computed(() => form.jenis === 'b2b');
const lockText = computed(() => props.program.status === 'final'
    ? 'Penjualan Program Final — terkunci. Buat Quotation dari menu Quotation (' + (b2bProgram.value ? 'ke Company penjualan' : 'ke Company atau Personal') + ') — persiapan invoice dengan peserta yang sudah fix.' + (props.program.closed_date ? ' Program sudah Closed.' : ' Jadwal, trainer, dan asisten masih dapat diubah lewat Reschedule.')
    : props.program.status === 'batal' ? 'Penjualan Program dibatalkan dan tidak dapat diubah.' : '');
const transition = useForm({});
function status(kind: string) { return confirmation.form(transition, 'post', `/penjualan-program/${props.program.id}/${kind}`, { title: `${kind === 'finalkan' ? 'Finalkan' : 'Batalkan'} ${props.program.nomor}?`, text: kind === 'finalkan' ? 'Program terkunci setelah Final. Periksa jadwal, tim dan costing sebelum melanjutkan.' : 'Program yang dibatalkan tidak dapat dibuka kembali.', confirmLabel: kind === 'finalkan' ? 'Ya, finalkan' : 'Ya, batalkan', danger: kind === 'batalkan' }, { preserveState: 'errors' }); }
</script>
<template>
    <Head :title="program.nomor || 'Buat Penjualan Program'" /><AppLayout title="Penjualan Program">
        <div class="pf-formbar"><Link class="kt-btn" href="/penjualan-program">← Kembali</Link><h1>{{ program.nomor ? `Penjualan Program ${program.nomor}` : `Buat Penjualan Program ${b2bProgram ? 'B2B' : 'Public'}` }}</h1><span v-if="!isNew" class="badge" :class="`st-${program.status}`">{{ STATUS_LABEL[program.status] ?? program.status }}{{ program.closed_date ? ' · Closed' : '' }}</span></div>
        <p v-if="lockText" class="notice">{{ lockText }}</p>
        <ul v-if="errors.length" class="error" role="alert"><li v-for="e in errors" :key="e">{{ e }}</li></ul>
        <section class="panel" aria-labelledby="sales-header"><h2 id="sales-header">Header</h2><div class="form-grid">
            <label>No. penjualan<input :value="program.nomor || ''" placeholder="otomatis saat disimpan" readonly /></label>
            <div><strong>Jenis penjualan</strong><p>{{ form.jenis === 'b2b' ? 'B2B' : 'Public' }}</p></div>
            <div><strong>Status</strong><p><span class="badge" :class="`st-${program.status}`">{{ STATUS_LABEL[program.status] ?? program.status }}{{ program.closed_date ? ' · Closed' : '' }}</span><span v-if="program.status === 'final'" class="muted"> → Quotation</span></p></div>
        </div></section>
        <p v-if="program.id && program.status === 'final'"><Link :href="`/penjualan-program/${program.id}/reschedule`">Reschedule / Riwayat</Link></p><form class="stack" @submit.prevent="save"><fieldset :disabled="!editable || form.processing" class="panel"><legend><span class="section-number">1</span> Informasi Penjualan</legend><label v-if="editable">Cari pilihan program, Company, trainer, atau asisten<input v-model="choiceSearch" type="search" maxlength="100" /></label><div class="form-grid">
            <label>Jenis<select v-model="form.jenis" :disabled="!!program.id"><option value="public">Public</option><option value="b2b">B2B</option></select></label>
            <label>Master Program<select v-model="form.id_master_program" required :disabled="!!program.id" @change="source"><option value="">— Pilih Master Program Final —</option><option v-for="m in masters.filter((m) => m.id === Number(form.id_master_program) || matches(m.nomor + ' ' + m.judul))" :key="m.id" :value="m.id">{{ m.nomor }} · {{ m.judul }}</option></select></label>
            <label v-if="form.jenis === 'b2b'">Company<select v-model="form.id_customer" required><option value="">— Pilih Company aktif —</option><option v-for="c in companies.filter((c) => c.id_customer === form.id_customer || matches(c.name_customer))" :key="c.id_customer" :value="c.id_customer">{{ c.name_customer }}</option></select></label>
            <label>Trainer<select v-model="form.id_trainer" required><option value="">— Pilih trainer aktif —</option><option v-for="t in trainers.filter((t) => t.id === Number(form.id_trainer) || matches(t.nama))" :key="t.id" :value="t.id">{{ t.nama }}</option></select></label>
            <label>Asisten trainer<select v-model="form.id_asisten" required><option value="">— Pilih asisten aktif —</option><option v-for="a in assistants.filter((a) => a.id === Number(form.id_asisten) || matches(a.nama + ' ' + (a.role ?? '')))" :key="a.id" :value="a.id">{{ a.nama }}{{ a.role ? ' · ' + a.role : '' }}</option></select></label>
            <label>Tanggal mulai<input v-model="form.tgl_mulai" type="date" required /></label><label>Tanggal selesai<input v-model="form.tgl_selesai" type="date" :min="form.tgl_mulai" required /><small class="hint">durasi = selesai − mulai + 1 hari</small></label>
            <label>Waktu<input v-model="form.waktu" maxlength="50" placeholder="mis. 09.00–15.15 WIB" /><small class="hint">Jam pelaksanaan; tercetak di Penawaran &amp; Formulir Konfirmasi</small></label><label>Lokasi acara<input v-model="form.lokasi" maxlength="150" placeholder="mis. Offline Bekasi" /></label><label>Jam Pembelajaran<input v-model="form.jam_pembelajaran" type="number" min="1" placeholder="mis. 16" required /></label>
            <label>Target peserta<input v-model="form.target_peserta" type="number" :min="form.jenis === 'b2b' ? 10 : 1" required /></label><label>Harga jual / peserta (Rp)<MoneyInput v-model="form.harga_jual_peserta" required /></label><label>Target profit (Rp)<MoneyInput v-model="form.target_profit" /></label>
        </div><p>Durasi: {{ days }} hari.</p></fieldset>
        <fieldset v-for="(category,ci) in (['fixed','variable'] as const)" :key="category" :disabled="!editable || form.processing" class="panel cost-section"><legend><span class="section-number">{{ci+2}}</span> {{category==='fixed'?'Fixed Cost':'Variable Cost'}}</legend>
<div v-for="unitName in (category==='fixed'?['Event','Day']:['Peserta/Event','Peserta/Day'])" :key="unitName" class="cost-group"><h3>{{category==='fixed'?'Fixed Cost':'Variable Cost'}} / {{unitName}}</h3><div class="table-wrap"><table><thead><tr><th>Item</th><th class="money">Nominal (Rp)</th><th class="money">Qty</th><th class="money">Total</th><th>Aksi</th></tr></thead><tbody><tr v-for="{b,i} in form.biaya.map((b,i)=>({b,i})).filter(({b})=>b.tipe===category && (b.nama_satuan===unitName || (!b.nama_satuan && unitName===(category==='fixed'?'Event':'Peserta/Event'))))" :key="i"><td><input v-if="b.is_manual" v-model="b.item" :aria-label="`Item ${i+1}`" maxlength="150" required/><span v-else>{{b.item}}</span><label v-if="b.is_manual">Satuan<select v-model="b.id_satuan" required @change="unit(b)"><option :value="0">Pilih</option><option v-for="u in units.filter(u=>(category==='fixed'?['Event','Day']:['Peserta/Event','Peserta/Day']).includes(u.nama))" :key="u.id" :value="u.id">{{u.nama}}</option></select></label></td><td><MoneyInput v-model="b.harga" :aria-label="`Nominal ${b.item}`" required/></td><td class="money">{{costQuantity(b.tipe,b.nama_satuan,days,form.target_peserta)}}</td><td class="money">{{rupiah(rupiahInput(b.harga)*costQuantity(b.tipe,b.nama_satuan,days,form.target_peserta))}}</td><td><button v-if="editable" type="button" class="danger" :aria-label="`Hapus ${b.item}`" @click="form.biaya.splice(i,1)">Hapus</button></td></tr></tbody></table></div><div v-if="editable" class="filters"><label>Tambah dari Master Rate<select v-model="pick" @change="addRate"><option value="">Pilih item</option><option v-for="r in rates.filter(r=>r.tipe===category && r.nama_satuan===unitName && !form.biaya.some(b=>b.id_rate===r.id))" :key="r.id" :value="r.id">{{r.item}} · {{rupiah(r.default_cost)}}</option></select></label><button type="button" @click="form.biaya.push({id_rate:null,tipe:category,item:'',id_satuan:units.find(u=>u.nama===unitName)?.id || 0,nama_satuan:unitName,harga:'',is_manual:true})">+ Biaya lain</button></div></div>
<p class="cost-total">Total {{category==='fixed'?'Fixed Cost':'Variable Cost'}} <strong>{{rupiah(category==='fixed'?result.fixed:result.totalVar)}}</strong></p></fieldset>
        <section class="panel"><h2><span class="section-number">4</span> Simulasi Hasil</h2><div class="form-grid"><label>Target peserta<input v-model="form.target_peserta" type="number" :min="form.jenis==='b2b'?10:1" :disabled="!editable" required/></label><label>Harga jual / peserta (Rp)<MoneyInput v-model="form.harga_jual_peserta" :disabled="!editable" required/></label><label>Target profit (Rp)<MoneyInput v-model="form.target_profit" :disabled="!editable"/></label></div><CostingSimulation :result="result" :participants="form.target_peserta" :price="form.harga_jual_peserta" :target-profit="form.target_profit" /><p v-if="result.profit < 0 || (result.bep ?? Infinity) > Number(form.target_peserta)" class="notice">Target belum mencapai titik impas.</p></section>
        <p v-for="e in transition.errors" :key="e" class="error" role="alert">{{ e }}</p>
        <div class="form-actions page-actions">
            <Link href="/penjualan-program">{{ isNew ? 'Batal' : 'Kembali' }}</Link>
            <button v-if="program.id && program.status === 'draft' && can.cancel" type="button" :disabled="confirming" @click="status('batalkan')">Batalkan</button>
            <button v-if="editable" class="primary" :disabled="form.processing || confirming">{{ form.processing ? 'Menyimpan…' : 'Simpan Draft' }}</button>
            <button v-if="program.id && program.status === 'draft' && can.approve" type="button" class="primary" :disabled="confirming" @click="status('finalkan')">Finalkan → Quotation</button>
            <Link v-if="program.status === 'final' && !program.closed_date" :href="`/penjualan-program/${program.id}/reschedule`">Reschedule</Link>
            <Link v-if="program.status === 'final'" :href="`/quotation/batch/${program.id}`">Quotation</Link>
            <a v-if="program.status === 'final'" class="primary" :href="`/penjualan-program/${program.id}/pdf`" target="_blank" rel="noopener">Cetak Surat Penawaran</a>
        </div>
        </form>
        <section v-if="actual" class="panel"><h2>Rencana vs Aktual</h2><p>Aktual berdasarkan {{ actual.peserta }} peserta Deal dan harga Penjualan Program.</p><div class="table-wrap"><table><thead><tr><th>Perhitungan</th><th>Rencana</th><th>Aktual</th><th>Selisih</th></tr></thead><tbody><tr><th>Peserta</th><td class="money">{{ program.target_peserta }}</td><td class="money">{{ actual.peserta }}</td><td class="money">{{ actual.peserta - program.target_peserta }}</td></tr><tr v-for="[field, label] in ([['total_hpp', 'HPP'], ['pendapatan', 'Pendapatan'], ['profit', 'Profit']] as const)" :key="field"><th>{{ label }}</th><td class="money">{{ rupiah(actual.rencana[field]) }}</td><td class="money">{{ rupiah(actual.aktual[field]) }}</td><td class="money">{{ rupiah(actual.aktual[field] - actual.rencana[field]) }}</td></tr></tbody></table></div></section>
    </AppLayout>
</template>
