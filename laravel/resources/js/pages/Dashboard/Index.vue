<script setup lang="ts">
import { ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import { rupiah } from '../../composables/costing';
import type { SharedProps } from '../../types';

type Pj = { id: number; nomor: string; judul: string; jenis: string; lokasi: string | null; company: string | null; tgl_mulai: string | null; tgl_selesai: string | null; target: number; status: string };
type Finals = Pj & { harga: number; pendapatan: number; fixed: number; variable: number; hpp: number; profit: number; margin: number };
type Todo = { type: 'mp' | 'pj'; id: number; nomor: string; judul: string; msg: string; warn: boolean };
const props = defineProps<{
    kpi: { final: number; public: number; b2b: number; pendapatan: number; peserta: number; profit: number; margin: number; draft: number };
    jadwal: Pj[]; todo: Todo[]; todoTotal: number; can: { master: boolean; sales: boolean };
}>();
const page = usePage<SharedProps>();
const key = ref<'final' | 'pend' | 'prof' | 'draft' | null>(null);
const detail = ref<{ finals?: Finals[]; master?: { id: number; nomor: string; judul: string }[]; penjualan?: Pj[] } | null>(null);
const loading = ref(false);
const titles = { final: 'Total Penjualan Program Final', pend: 'Total pendapatan rencana', prof: 'Total profit rencana', draft: 'Total masih Draft' } as const;
const FINAL_SRC = 'menu Daftar Penjualan Program, hanya yang berstatus Final (Draft dan Batal tidak dihitung). Angka rencana = isian Penjualan Program saat difinalkan.';
async function open(k: 'final' | 'pend' | 'prof' | 'draft') {
    key.value = k; detail.value = null; loading.value = true;
    try { detail.value = await (await fetch(`/dashboard/kpi/${k}`, { headers: { Accept: 'application/json' } })).json(); } finally { loading.value = false; }
}
const range = (a: string | null, b: string | null) => (a && b && a !== b ? `${a} s/d ${b}` : a || b || '—');
const where = (p: Pj) => (p.jenis === 'b2b' && p.company ? p.company : p.lokasi || '—');
const sum = (list: Finals[], f: (x: Finals) => number) => list.reduce((s, x) => s + f(x), 0);
const link = (t: Todo) => (t.type === 'mp' ? `/program/${t.id}` : `/sales/${t.id}`);
const pct = (v: number) => String(v).replace('.', ',') + '%';
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout title="Dashboard">
        <h1>Dashboard</h1>
        <p class="muted">Ringkasan Master Program dan Penjualan Program. Angka adalah total seluruh Penjualan Program Final (rencana). Pengguna: {{ page.props.auth?.nm_lengkap || page.props.auth?.username }}.</p>
        <div class="kpis" role="list">
            <button type="button" class="kpi" role="listitem" @click="open('final')"><span class="kpi-l">Total Penjualan Program Final <small>rincian ›</small></span><strong class="kpi-v">{{ kpi.final }}</strong><span class="kpi-s">Public {{ kpi.public }} · B2B {{ kpi.b2b }}</span></button>
            <button type="button" class="kpi" role="listitem" @click="open('pend')"><span class="kpi-l">Total pendapatan rencana <small>rincian ›</small></span><strong class="kpi-v">{{ rupiah(kpi.pendapatan) }}</strong><span class="kpi-s">{{ kpi.peserta }} peserta = total target peserta dari {{ kpi.final }} Penjualan Final</span></button>
            <button type="button" class="kpi" :class="kpi.profit < 0 ? 'bad' : 'good'" role="listitem" @click="open('prof')"><span class="kpi-l">Total profit rencana <small>rincian ›</small></span><strong class="kpi-v">{{ rupiah(kpi.profit) }}</strong><span class="kpi-s">Pendapatan − biaya (HPP) · margin {{ pct(kpi.margin) }}</span></button>
            <button type="button" class="kpi" :class="{ warn: kpi.draft }" role="listitem" @click="open('draft')"><span class="kpi-l">Total masih Draft <small>rincian ›</small></span><strong class="kpi-v">{{ kpi.draft }}</strong><span class="kpi-s">Master Program + Penjualan Program belum Final</span></button>
        </div>

        <div class="dash-grid">
            <section class="panel" aria-labelledby="jadwal"><h2 id="jadwal">Jadwal pelaksanaan terdekat <small class="muted">Penjualan Program Final</small></h2>
                <div v-if="jadwal.length" class="table-wrap"><table>
                    <caption class="sr-only">Lima jadwal pelaksanaan terdekat</caption>
                    <thead><tr><th scope="col">Tanggal</th><th scope="col">Program</th><th scope="col">Jenis</th><th scope="col">Peserta</th></tr></thead>
                    <tbody><tr v-for="p in jadwal" :key="p.id">
                        <td>{{ range(p.tgl_mulai, p.tgl_selesai) }}</td>
                        <td><Link v-if="can.sales" :href="`/sales/${p.id}`">{{ p.judul }}</Link><template v-else>{{ p.judul }}</template><small>{{ p.nomor }} · {{ where(p) }}</small></td>
                        <td>{{ p.jenis === 'b2b' ? 'B2B' : 'Public' }}</td><td>{{ p.target }}</td>
                    </tr></tbody>
                </table></div>
                <p v-else class="empty">Belum ada Penjualan Program Final yang akan dilaksanakan.</p>
            </section>
            <section class="panel" aria-labelledby="tindakan"><h2 id="tindakan">Perlu tindakan</h2>
                <ul v-if="todo.length" class="dash-list">
                    <li v-for="t in todo" :key="`${t.type}-${t.id}-${t.msg}`"><Link :href="link(t)"><span class="code">{{ t.nomor }}</span> {{ t.judul }}</Link><small :class="t.warn ? 'error' : 'muted'">{{ t.msg }}</small></li>
                </ul>
                <p v-else class="empty">Tidak ada yang perlu ditindaklanjuti.</p>
                <p v-if="todoTotal > todo.length" class="muted">+{{ todoTotal - todo.length }} lainnya</p>
            </section>
        </div>

        <ConfirmDialog v-if="key" :title="titles[key]" confirm-label="Tutup" @confirm="key = null" @cancel="key = null">
            <p v-if="loading" role="status">Memuat rincian…</p>
            <template v-else-if="detail">
                <template v-if="key === 'draft'">
                    <h3>Master Program Draft</h3>
                    <ul v-if="detail.master?.length"><li v-for="m in detail.master" :key="m.id"><Link :href="`/program/${m.id}`">{{ m.nomor }}</Link> {{ m.judul }}</li></ul><p v-else class="empty">Belum ada data.</p>
                    <h3>Penjualan Program Draft</h3>
                    <ul v-if="detail.penjualan?.length"><li v-for="p in detail.penjualan" :key="p.id"><Link :href="`/sales/${p.id}`">{{ p.nomor }}</Link> {{ p.judul }}</li></ul><p v-else class="empty">Belum ada data.</p>
                </template>
                <template v-else>
                    <p class="muted"><b>Sumber data:</b> {{ FINAL_SRC }}</p>
                    <p v-if="key === 'pend'" class="muted"><b>Rumus:</b> pendapatan = harga jual / peserta × target peserta, lalu dijumlah semua penjualan.</p>
                    <p v-if="key === 'prof'" class="muted"><b>Rumus:</b> profit = pendapatan − HPP. HPP = Fixed Cost + Variable Cost. Margin = profit ÷ pendapatan.</p>
                    <div v-if="detail.finals?.length" class="table-wrap"><table>
                        <caption class="sr-only">{{ titles[key] }}</caption>
                        <thead><tr><th scope="col">Penjualan Program</th><th v-if="key !== 'final'" scope="col">Harga / peserta</th><th scope="col">Target</th><th v-if="key === 'pend'" scope="col">Pendapatan</th><template v-if="key === 'prof'"><th scope="col">Pendapatan</th><th scope="col">Fixed</th><th scope="col">Variable</th><th scope="col">Profit</th><th scope="col">Margin</th></template></tr></thead>
                        <tbody><tr v-for="f in detail.finals" :key="f.id">
                            <td>{{ f.nomor }} {{ f.judul }}<small>{{ f.jenis === 'b2b' ? 'B2B' : 'Public' }} · {{ where(f) }}</small></td>
                            <td v-if="key !== 'final'" class="money">{{ rupiah(f.harga) }}</td><td class="money">{{ f.target }}</td>
                            <td v-if="key === 'pend'" class="money">{{ rupiah(f.pendapatan) }}</td>
                            <template v-if="key === 'prof'"><td class="money">{{ rupiah(f.pendapatan) }}</td><td class="money">− {{ rupiah(f.fixed) }}</td><td class="money">− {{ rupiah(f.variable) }}</td><td :class="['money', f.profit < 0 ? 'error' : '']">{{ rupiah(f.profit) }}</td><td>{{ f.pendapatan ? pct(f.margin) : '—' }}</td></template>
                        </tr></tbody>
                        <tfoot><tr><th scope="row">Total {{ detail.finals.length }} penjualan</th><td v-if="key !== 'final'"></td><td class="money">{{ sum(detail.finals, (x) => x.target) }}</td><td v-if="key === 'pend'">{{ rupiah(sum(detail.finals, (x) => x.pendapatan)) }}</td><template v-if="key === 'prof'"><td class="money">{{ rupiah(sum(detail.finals, (x) => x.pendapatan)) }}</td><td class="money">− {{ rupiah(sum(detail.finals, (x) => x.fixed)) }}</td><td class="money">− {{ rupiah(sum(detail.finals, (x) => x.variable)) }}</td><td class="money">{{ rupiah(sum(detail.finals, (x) => x.profit)) }}</td><td></td></template></tr></tfoot>
                    </table></div>
                    <p v-else class="empty">Belum ada data.</p>
                </template>
            </template>
        </ConfirmDialog>
    </AppLayout>
</template>
