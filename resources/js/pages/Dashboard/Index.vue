<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import { ref } from 'vue';
import PageHead from '../../components/PageHead.vue';
import ServerTable from '../../components/ServerTable.vue';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import { rupiah } from '../../composables/costing';


type Pj = { id: number; nomor: string; judul: string; jenis: string; lokasi: string | null; company: string | null; tgl_mulai: string | null; tgl_selesai: string | null; target: number; status: string };
type Todo = { type: 'mp' | 'pj'; id: number; nomor: string; judul: string; msg: string; warn: boolean };
const props = defineProps<{
    kpi: { final: number; public: number; b2b: number; pendapatan: number; peserta: number; profit: number; margin: number; draft: number };
    jadwal: Pj[]; todo: Todo[]; todoTotal: number; can: { master: boolean; sales: boolean };
}>();

const key = ref<'final' | 'pend' | 'prof' | 'draft' | null>(null);
const totals = ref<Record<string, number>>({});
const titles = { final: 'Total Penjualan Program Final', pend: 'Total pendapatan rencana', prof: 'Total profit rencana', draft: 'Total masih Draft' } as const;
const FINAL_SRC = 'Daftar Penjualan Program berstatus Final. Angka rencana memakai costing saat program difinalkan.';
function open(k: 'final' | 'pend' | 'prof' | 'draft') { totals.value = {}; key.value = k; }
const columns = () => [
    { title: 'No', orderable: false }, { title: 'Penjualan Program', orderable: true },
    ...(key.value !== 'final' ? [{ title: 'Harga / peserta', orderable: false, className: 'dt-body-right' }] : []),
    { title: 'Target', orderable: false, className: 'dt-body-right' },
    ...(key.value === 'pend' ? [{ title: 'Pendapatan', orderable: false, className: 'dt-body-right' }] : []),
    ...(key.value === 'prof' ? ['Pendapatan', 'Fixed', 'Variable', 'Profit', 'Margin'].map(title => ({ title, orderable: false, className: 'dt-body-right' })) : []),
];
const loaded = (result: { meta?: Record<string, unknown> }) => { totals.value = (result.meta?.totals ?? {}) as Record<string, number>; };
const range = (a: string | null, b: string | null) => (a && b && a !== b ? `${a} s/d ${b}` : a || b || '—');
const where = (p: Pj) => (p.jenis === 'b2b' && p.company ? p.company : p.lokasi || '—');

const link = (t: Todo) => appUrl(t.type === 'mp' ? `/program/${t.id}` : `/sales/${t.id}`);
const pct = (v: number) => String(v).replace('.', ',') + '%';
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout title="Dashboard" full-width>
        <PageHead title="Dashboard" description="Ringkasan Master Program dan Penjualan Program. Angka adalah total seluruh Penjualan Program Final (rencana)." />
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
                        <td><Link v-if="can.sales" :href="appUrl(`/sales/${p.id}`)">{{ p.judul }}</Link><template v-else>{{ p.judul }}</template><small>{{ p.nomor }} · {{ where(p) }}</small></td>
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
            <template v-if="key === 'draft'">
                <section v-for="source in ['master', 'penjualan']" :key="source">
                    <h3>{{ source === 'master' ? 'Master Program Draft' : 'Penjualan Program Draft' }}</h3>
                    <ServerTable :url="appUrl('/dashboard/kpi/draft/data')" :label="source + ' Draft'" :filters="{ source }" :columns="[{ title: 'No', orderable: false }, { title: 'Program', orderable: true }]">
                        <template #cell-0="{ number }">{{ number }}</template>
                        <template #cell-1="{ row }"><Link v-if="source === 'master' ? can.master : can.sales" :href="appUrl(source === 'master' ? `/program/${row.id}` : `/sales/${row.id}`)">{{ row.nomor }}</Link><span v-else>{{ row.nomor }}</span><small>{{ row.judul }}</small></template>
                    </ServerTable>
                </section>
            </template>
            <template v-else>
                <p class="muted"><b>Sumber data:</b> {{ FINAL_SRC }}</p>
                <p v-if="key === 'pend'">Pendapatan = harga jual / peserta × target peserta.</p>
                <p v-if="key === 'prof'">Profit = pendapatan − Fixed Cost − Variable Cost.</p>
                <ServerTable :key="key" :url="appUrl(`/dashboard/kpi/${key}/data`)" :label="titles[key]" :columns="columns()" @loaded="loaded">
                    <template #cell-0="{ number }">{{ number }}</template>
                    <template #cell-1="{ row }"><Link v-if="can.sales" :href="appUrl(`/sales/${row.id}`)">{{ row.nomor }}</Link><span v-else>{{ row.nomor }}</span><small>{{ row.judul }} · {{ where(row) }}</small></template>
                    <template #cell-2="{ row }">{{ key === 'final' ? row.target : rupiah(row.harga) }}</template>
                    <template #cell-3="{ row }">{{ row.target }}</template>
                    <template #cell-4="{ row }">{{ rupiah(row.pendapatan) }}</template>
                    <template #cell-5="{ row }">{{ rupiah(row.fixed) }}</template>
                    <template #cell-6="{ row }">{{ rupiah(row.variable) }}</template>
                    <template #cell-7="{ row }"><span :class="{ error: row.profit < 0 }">{{ rupiah(row.profit) }}</span></template>
                    <template #cell-8="{ row }">{{ row.pendapatan ? pct(row.margin) : '—' }}</template>
                </ServerTable>
                <div class="status-summary" role="status"><b>Total {{ totals.count ?? 0 }} penjualan</b><span>Target {{ totals.target ?? 0 }} peserta</span><span v-if="key !== 'final'">Pendapatan {{ rupiah(totals.pendapatan ?? 0) }}</span><template v-if="key === 'prof'"><span>Fixed {{ rupiah(totals.fixed ?? 0) }}</span><span>Variable {{ rupiah(totals.variable ?? 0) }}</span><span>Profit {{ rupiah(totals.profit ?? 0) }}</span></template></div>
            </template>
        </ConfirmDialog>
    </AppLayout>
</template>
