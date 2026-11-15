<script setup lang="ts">
import CostingSimulation from "../../../components/CostingSimulation.vue";
import MoneyInput from "../../../components/MoneyInput.vue";
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../layouts/AppLayout.vue';
import FormField from '../../../components/FormField.vue';
import { calc, rupiah, costQuantity } from '../../../composables/costing';
import { useConfirmedMutation } from '../../../composables/confirmedMutation';

const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;

type Line = { id_rate: number | null; tipe: 'fixed' | 'variable'; item: string; satuan: string | null; id_satuan: number; harga: number | string; is_manual: boolean };
type Program = { id: number | null; nomor: string | null; status: 'draft' | 'final' | 'batal'; paket: number[]; durasi_hari: number | string; jam_pembelajaran: number | string | null; target_peserta: number | string; harga_jual_peserta: number | string; target_profit: number | string; biaya: Line[] };
type Product = { id: number; nomor: string; judul: string; aktif: number; produk: string | null; bidang: string | null; varian: string | null; sertifikat: string | null };
type Rate = { id: number; tipe: 'fixed' | 'variable'; item: string; id_satuan: number; default_cost: string | number; aktif: number; nama_satuan: string | null };
const props = defineProps<{ program: Program; products: Product[]; rates: Rate[]; satuan: { fixed: Record<string, number>; variable: Record<string, number> }; can: { edit: boolean; approve: boolean; cancel: boolean } }>();

const draft = props.program.status === 'draft';
const editable = draft && props.can.edit;
const form = useForm({
    paket: [...props.program.paket] as (number | '')[], durasi_hari: props.program.durasi_hari as number | string, jam_pembelajaran: (props.program.jam_pembelajaran ?? '') as number | string,
    target_peserta: props.program.target_peserta as number | string, harga_jual_peserta: props.program.harga_jual_peserta as number | string, target_profit: props.program.target_profit as number | string,
    biaya: props.program.biaya.map((b) => ({ ...b })) as Line[],
});
const err = (k: string) => (form.errors as Record<string, string>)[k];
const groups = [
    { tipe: 'fixed', satuan: 'Event', title: 'Fixed Cost / Event' }, { tipe: 'fixed', satuan: 'Day', title: 'Fixed Cost / Day' },
    { tipe: 'variable', satuan: 'Peserta/Event', title: 'Variable Cost / Peserta / Event' }, { tipe: 'variable', satuan: 'Peserta/Day', title: 'Variable Cost / Peserta / Day' },
] as const;
const inGroup = (g: (typeof groups)[number]) => form.biaya.map((b, index) => ({ b, index })).filter(({ b }) => b.tipe === g.tipe && b.satuan === g.satuan);
const result = computed(() => calc(form.durasi_hari, form.target_peserta, form.harga_jual_peserta, form.biaya));
const used = (rateId: number) => form.biaya.some((b) => b.id_rate === rateId);
const available = (g: (typeof groups)[number]) => props.rates.filter((r) => r.tipe === g.tipe && r.nama_satuan === g.satuan && r.aktif && !used(r.id));
const pick = ref<Record<string, string>>({});
function addRate(g: (typeof groups)[number]) {
    const r = props.rates.find((x) => x.id === Number(pick.value[g.title]));
    if (!r) return;
    form.biaya.push({ id_rate: r.id, tipe: r.tipe, item: r.item, satuan: r.nama_satuan, id_satuan: r.id_satuan, harga: Math.round(Number(r.default_cost)), is_manual: false });
    pick.value[g.title] = '';
}
const addManual = (g: (typeof groups)[number]) => form.biaya.push({ id_rate: null, tipe: g.tipe, item: '', satuan: g.satuan, id_satuan: props.satuan[g.tipe][g.satuan], harga: '', is_manual: true });
const productsLeft = computed(() => props.products.filter((p) => p.aktif || props.program.paket.includes(p.id)));
function save() {
    return confirmation.form(form, props.program.id ? 'put' : 'post', props.program.id ? `/program/${props.program.id}` : '/program',
        { title: 'Simpan Draft Master Program?', text: props.program.nomor || 'Nomor dibuat otomatis saat disimpan.' }, { preserveScroll: true });
}
const warnings = computed(() => {
    const w: string[] = [];
    if (form.biaya.some((b) => b.harga === '' || b.harga === null)) w.push('Ada nominal yang belum diisi.');
    if (Number(form.target_peserta) && result.value.pendapatan) {
        if (result.value.profit < 0) w.push('Rugi: pendapatan belum menutup HPP.');
        if (result.value.bep === null) w.push('Titik impas tidak tercapai: harga per peserta belum menutup biaya variable.');
        else if (result.value.bep > Number(form.target_peserta)) w.push(`Titik impas ${result.value.bep} peserta, di atas target ${form.target_peserta}.`);
        if (form.target_profit !== '' && result.value.profit < Number(form.target_profit)) w.push(`Profit ${rupiah(result.value.profit)} di bawah target profit ${rupiah(form.target_profit)}.`);
    }
    return w;
});
const statusLabel = { draft: 'Draft', final: 'Final', batal: 'Batal' } as const;
</script>

<template>
    <Head :title="program.nomor ? `Master Program ${program.nomor}` : 'Buat Master Program'" />
    <AppLayout :title="program.nomor ? `Master Program ${program.nomor}` : 'Buat Master Program'">
        <div class="page-head"><h1>{{ program.nomor ? `Master Program ${program.nomor}` : 'Buat Master Program' }}</h1><span class="badge" :class="`st-${program.status}`">{{ statusLabel[program.status] }}</span></div>
        <p v-if="!draft" class="notice">Master Program berstatus {{ statusLabel[program.status] }} terkunci dan tidak dapat diubah.</p>
        <p v-if="err('program')" class="error" role="alert">{{ err('program') }}</p>

        <form class="stack" @submit.prevent="save">
            <section class="panel" aria-labelledby="sec-header"><h2 id="sec-header">Header</h2><div class="form-grid">
                <label>No. master program<input :value="program.nomor || ''" placeholder="Otomatis saat disimpan" readonly /></label>
                <div><strong>Jenis</strong><p class="muted">Umum — Public/B2B dipilih di Penjualan Program</p></div>
                <div><strong>Status</strong><p><span class="badge" :class="`st-${program.status}`">{{ statusLabel[program.status] }}</span></p></div>
            </div></section>
            <section class="panel" aria-labelledby="sec-product"><h2 id="sec-product">1. Master Program</h2>
                <p v-if="err('paket')" class="error" role="alert">{{ err('paket') }}</p>
                <div v-for="(pid, i) in form.paket" :key="i" class="form-grid">
                    <label><span>Product {{ i + 1 }}{{ i === 0 ? ' (utama)' : '' }}</span>
                        <select v-model="form.paket[i]" :disabled="!editable" :aria-label="`Product ${i + 1}`"><option value="">Pilih…</option><option v-for="p in productsLeft" :key="p.id" :value="p.id" :disabled="form.paket.includes(p.id) && form.paket[i] !== p.id">{{ p.nomor }} · {{ p.judul }} · {{ p.sertifikat }}{{ p.aktif ? '' : ' (nonaktif)' }}</option></select></label>
                    <button v-if="editable && form.paket.length > 1" type="button" class="danger" @click="form.paket.splice(i, 1)">Hapus</button>
                </div>
                <button v-if="editable" type="button" @click="form.paket.push('')">+ Tambah Product aktif</button>
                <div class="form-grid">
                    <FormField id="durasi" label="Durasi (hari)" :error="form.errors.durasi_hari" v-slot="{ describedBy, invalid }"><input id="durasi" v-model="form.durasi_hari" inputmode="numeric" :disabled="!editable" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField id="jp" label="Jam Pembelajaran (JP)" hint="Wajib; nilai awal untuk Penjualan Program." :error="form.errors.jam_pembelajaran" v-slot="{ describedBy, invalid }"><input id="jp" v-model="form.jam_pembelajaran" inputmode="numeric" :disabled="!editable" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField id="target" label="Target peserta" :error="form.errors.target_peserta" v-slot="{ describedBy, invalid }"><input id="target" v-model="form.target_peserta" inputmode="numeric" :disabled="!editable" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField id="harga" label="Harga jual / peserta (Rp)" :error="form.errors.harga_jual_peserta" v-slot="{ describedBy, invalid }"><MoneyInput id="harga" v-model="form.harga_jual_peserta" :disabled="!editable" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField id="tp" label="Target profit (Rp)" :error="form.errors.target_profit" v-slot="{ describedBy, invalid }"><MoneyInput id="tp" v-model="form.target_profit" :disabled="!editable" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                </div>
            </section>

            <section v-for="(category, ci) in (['fixed','variable'] as const)" :key="category" class="panel cost-section"><h2><span class="section-number">{{ci+2}}</span> {{ category==='fixed'?'Fixed Cost':'Variable Cost' }}</h2><p class="muted">{{category==='fixed'?'Biaya per event / hari.':'Biaya per peserta; qty mengikuti target peserta dan durasi.'}}</p>
            <div v-for="g in groups.filter(g=>g.tipe===category)" :key="g.title" class="cost-group"><h3>{{ g.title }}</h3>
                <div class="table-wrap"><table>
                    <caption class="sr-only">{{ g.title }}</caption>
                    <thead><tr><th scope="col">Item</th><th scope="col" class="money">Nominal (Rp)</th><th scope="col" class="money">Qty</th><th scope="col" class="money">Total</th><th scope="col"><span class="sr-only">Aksi</span></th></tr></thead>
                    <tbody>
                        <tr v-for="{ b, index } in inGroup(g)" :key="index">
                            <td><input v-if="b.is_manual" v-model="b.item" maxlength="150" :disabled="!editable" :aria-label="`Nama biaya lain ${index + 1}`" placeholder="Nama biaya lain" /><template v-else>{{ b.item }}<small v-if="rates.find((r) => r.id === b.id_rate && !r.aktif)">(nonaktif)</small></template><p v-if="err(`biaya.${index}`)" class="error" role="alert">{{ err(`biaya.${index}`) }}</p></td>
                            <td><MoneyInput v-model="b.harga" :disabled="!editable" :aria-label="`Nominal ${b.item || 'biaya lain'}`" /></td>
                            <td class="money">{{costQuantity(b.tipe,b.satuan,form.durasi_hari,form.target_peserta)}}</td><td class="money">{{rupiah(Number(b.harga)*costQuantity(b.tipe,b.satuan,form.durasi_hari,form.target_peserta))}}</td><td><button v-if="editable" type="button" class="danger" :aria-label="`Hapus ${b.item || 'biaya lain'}`" @click="form.biaya.splice(index, 1)">×</button></td>
                        </tr>
                        <tr v-if="!inGroup(g).length"><td colspan="5" class="empty">Belum ada item.</td></tr>
                    </tbody>
                </table></div>
                <div v-if="editable" class="filters">
                    <label>Tambah dari Master Rate<select v-model="pick[g.title]" @change="addRate(g)"><option value="">Pilih item…</option><option v-for="r in available(g)" :key="r.id" :value="r.id">{{ r.item }} (default {{ rupiah(r.default_cost) }})</option></select></label>
                    <button type="button" @click="addManual(g)">+ Biaya lain</button>
                </div>
            </div><p class="cost-total">Total {{category==='fixed'?'Fixed Cost':'Variable Cost'}} <strong>{{rupiah(category==='fixed'?result.fixed:result.totalVar)}}</strong></p></section>

            <section class="panel" aria-labelledby="sec-sim"><h2 id="sec-sim"><span class="section-number">4</span> Simulasi Hasil</h2><div class="form-grid"><label>Durasi (hari)<input v-model="form.durasi_hari" type="number" min="1" :disabled="!editable" required/></label><label>Target peserta<input v-model="form.target_peserta" type="number" min="1" :disabled="!editable" required/></label><label>Harga jual / peserta (Rp)<MoneyInput v-model="form.harga_jual_peserta" :disabled="!editable" required/></label><label>Target profit (Rp)<MoneyInput v-model="form.target_profit" :disabled="!editable"/></label></div>
                <CostingSimulation :result="result" :participants="form.target_peserta" :price="form.harga_jual_peserta" :target-profit="form.target_profit" />
                <p v-for="w in warnings" :key="w" class="notice">{{ w }}</p>
                <p class="muted">Angka resmi dihitung ulang server saat simpan dan Finalkan.</p>
            </section>

            <div class="form-actions">
                <button v-if="editable" class="primary" :disabled="form.processing || confirming">{{ form.processing ? 'Menyimpan…' : 'Simpan Draft' }}</button>
                <Link href="/program">Kembali ke daftar</Link>
            </div>
        </form>
    </AppLayout>
</template>
