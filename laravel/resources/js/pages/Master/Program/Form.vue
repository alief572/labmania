<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../../layouts/AppLayout.vue';
import FormField from '../../../components/FormField.vue';
import { calc, rupiah } from '../../../composables/costing';

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
    const opts = { preserveScroll: true };
    if (props.program.id) form.put(`/program/${props.program.id}`, opts);
    else form.post('/program', opts);
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
        <div class="page-head"><h1>{{ program.nomor ? `Master Program ${program.nomor}` : 'Buat Master Program' }}</h1><span class="badge" :class="{ on: program.status === 'final', off: program.status === 'batal', warn: draft }">{{ statusLabel[program.status] }}</span></div>
        <p v-if="!draft" class="notice">Master Program berstatus {{ statusLabel[program.status] }} terkunci dan tidak dapat diubah.</p>
        <p v-if="err('program')" class="error" role="alert">{{ err('program') }}</p>

        <form class="stack" @submit.prevent="save">
            <section class="panel" aria-labelledby="sec-product"><h2 id="sec-product">1. Product dan jadwal</h2>
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
                    <FormField id="harga" label="Harga jual / peserta (Rp)" :error="form.errors.harga_jual_peserta" v-slot="{ describedBy, invalid }"><input id="harga" v-model="form.harga_jual_peserta" inputmode="numeric" :disabled="!editable" required :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                    <FormField id="tp" label="Target profit (Rp)" :error="form.errors.target_profit" v-slot="{ describedBy, invalid }"><input id="tp" v-model="form.target_profit" inputmode="numeric" :disabled="!editable" :aria-invalid="invalid" :aria-describedby="describedBy" /></FormField>
                </div>
            </section>

            <section v-for="g in groups" :key="g.title" class="panel" :aria-label="g.title"><h2>{{ g.title }}</h2>
                <div class="table-wrap"><table>
                    <caption class="sr-only">{{ g.title }}</caption>
                    <thead><tr><th scope="col">Item</th><th scope="col">Nominal (Rp)</th><th scope="col"><span class="sr-only">Aksi</span></th></tr></thead>
                    <tbody>
                        <tr v-for="{ b, index } in inGroup(g)" :key="index">
                            <td><input v-if="b.is_manual" v-model="b.item" maxlength="150" :disabled="!editable" :aria-label="`Nama biaya lain ${index + 1}`" placeholder="Nama biaya lain" /><template v-else>{{ b.item }}<small v-if="rates.find((r) => r.id === b.id_rate && !r.aktif)">(nonaktif)</small></template><p v-if="err(`biaya.${index}`)" class="error" role="alert">{{ err(`biaya.${index}`) }}</p></td>
                            <td><input v-model="b.harga" inputmode="numeric" :disabled="!editable" :aria-label="`Nominal ${b.item || 'biaya lain'}`" /></td>
                            <td><button v-if="editable" type="button" class="danger" :aria-label="`Hapus ${b.item || 'biaya lain'}`" @click="form.biaya.splice(index, 1)">×</button></td>
                        </tr>
                        <tr v-if="!inGroup(g).length"><td colspan="3" class="empty">Belum ada item.</td></tr>
                    </tbody>
                </table></div>
                <div v-if="editable" class="filters">
                    <label>Tambah dari Master Rate<select v-model="pick[g.title]" @change="addRate(g)"><option value="">Pilih item…</option><option v-for="r in available(g)" :key="r.id" :value="r.id">{{ r.item }} (default {{ rupiah(r.default_cost) }})</option></select></label>
                    <button type="button" @click="addManual(g)">+ Biaya lain</button>
                </div>
            </section>

            <section class="panel" aria-labelledby="sec-sim"><h2 id="sec-sim">Simulasi</h2>
                <dl>
                    <dt>Total Fixed Cost</dt><dd>{{ rupiah(result.fixed) }}</dd><dt>Variable / peserta</dt><dd>{{ rupiah(result.varPer) }}</dd><dt>HPP</dt><dd>{{ rupiah(result.hpp) }}</dd>
                    <dt>Pendapatan</dt><dd>{{ rupiah(result.pendapatan) }}</dd><dt>Profit</dt><dd>{{ rupiah(result.profit) }} ({{ result.margin }}%)</dd><dt>BEP (peserta)</dt><dd>{{ result.bep ?? 'Tidak tercapai' }}</dd>
                </dl>
                <p v-for="w in warnings" :key="w" class="notice">{{ w }}</p>
                <p class="muted">Angka resmi dihitung ulang server saat simpan dan Finalkan.</p>
            </section>

            <div class="form-actions">
                <button v-if="editable" class="primary" :disabled="form.processing">{{ form.processing ? 'Menyimpan…' : 'Simpan Draft' }}</button>
                <Link href="/program">Kembali ke daftar</Link>
            </div>
        </form>
    </AppLayout>
</template>
