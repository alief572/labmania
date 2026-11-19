<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import PageHead from '../../components/PageHead.vue';
import ServerTable from '../../components/ServerTable.vue';
import ActionMenu from "../../components/ActionMenu.vue";
import { onBeforeUnmount, reactive, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import { money, type PageRows, type Program } from '../../types/salesOrder';
type Row = Program & { judul: string; jumlah_so: number; peserta_deal: number; nilai_so: string; statuses: Record<string, number>; program_nomor?: string; status?: string; tanggal?: string; facts?: { judul: string; program: Program; trainer: { nama: string; bidang_keahlian: string }; asisten: { nama: string } } | null };
const props = defineProps<{ rows: PageRows<Row>; filters: Record<string, string>; tab: string; counts: { so: number; batal: number; surat: number }; years?: number[] }>();
const f = reactive({ q: '', jenis: '', tahun: '', bulan: '', ...props.filters, tab: props.tab });
// Filter langsung berlaku (mockup tanpa tombol Terapkan); kata kunci ditunda 300 ms.
const search = () => router.get(appUrl('/sales-order'), Object.fromEntries(Object.entries(f).filter(([k, v]) => v !== '' && v !== null && !(k === 'tab' && v === 'so'))), { preserveState: true, preserveScroll: true, replace: true });
let timer: ReturnType<typeof setTimeout> | undefined;
watch(() => [f.jenis, f.tahun, f.bulan], () => { clearTimeout(timer); search(); });
watch(() => f.q, () => { clearTimeout(timer); timer = setTimeout(search, 300); });
onBeforeUnmount(() => clearTimeout(timer));
const surat = () => props.tab === 'surat';
const batal = () => props.tab === 'batal';
const bulan = (n: number) => new Date(2026, n - 1).toLocaleString('id-ID', { month: 'long' });
const fmt = (d?: string) => (d ? new Date(`${d.slice(0, 10)}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—');
const range = (a?: string, b?: string) => (!b || a === b ? fmt(a) : `${fmt(a)} – ${fmt(b)}`);
const statusText = (p: Row) => Object.entries(p.statuses ?? {}).map(([s, n]) => `${n} ${s.toLowerCase()}`).join(', ');
</script>
<template>
    <Head title="Sales Order" />
    <AppLayout title="Sales Order" full-width>
        <PageHead title="Sales Order" />
        <p v-for="e in $page.props.errors" :key="String(e)" class="error" role="alert">{{ e }}</p>
        <div class="batch-card">
            <nav class="tabs" aria-label="Jenis dokumen">
                <Link :href="appUrl('/sales-order')" :aria-current="tab === 'so' ? 'page' : undefined">Sales Order<span class="nav-count">{{ counts.so }}</span></Link>
                <Link :href="appUrl('/sales-order?tab=batal')" :aria-current="tab === 'batal' ? 'page' : undefined">SO Batal<span class="nav-count">{{ counts.batal }}</span></Link>
                <Link :href="appUrl('/sales-order?tab=surat')" :aria-current="tab === 'surat' ? 'page' : undefined">Surat Kerja Trainer<span class="nav-count">{{ counts.surat }}</span></Link>
            </nav>
            <div class="filters">
                <label class="grow">Cari<input v-model="f.q" type="search" maxlength="100" :placeholder="surat() ? 'Cari nomor surat, program, judul, trainer…' : 'Cari nomor program, judul, nomor SO, quotation, customer…'" /></label>
                <label>Jenis<select v-model="f.jenis"><option value="">Public &amp; B2B</option><option value="public">Program Public</option><option value="b2b">Program B2B</option></select></label>
                <label>Tahun<select v-model="f.tahun"><option value="">Semua tahun</option><option v-for="y in years ?? []" :key="y" :value="y">{{ y }}</option></select></label>
                <label>Bulan<select v-model="f.bulan"><option value="">Semua bulan</option><option v-for="n in 12" :key="n" :value="n">{{ bulan(n) }}</option></select></label>
            </div>
            <ServerTable v-if="tab === 'so'" :url="appUrl('/sales-order/data')" label="Sales Order" :columns='[{"title":"No","orderable":false},{"title":"Penjualan Program","orderable":true},{"title":"Sales Order","orderable":false},{"title":"Peserta deal","orderable":false,"className":"dt-body-right"},{"title":"Nilai SO","orderable":false,"className":"dt-body-right"},{"title":"Action","orderable":false}]' :filters="{ ...filters, tab }" refresh-on-visit>
                <template #cell-0="{ number }">{{ number }}</template>
                <template #cell-1="{ row: p }"><span class="code">{{ p.nomor }}</span> <span class="tag" :class="p.jenis === 'b2b' ? 'tag-b2b' : 'tag-public'">{{ p.jenis === 'b2b' ? 'B2B' : 'Public' }}</span><br>{{ p.judul }}<small>{{ range(p.tgl_mulai, p.tgl_selesai) }} · {{ p.lokasi || '—' }}</small></template>
                <template #cell-2="{ row: p }"><b>{{ p.jumlah_so }}</b> SO<small>{{ statusText(p) }}</small></template>
                <template #cell-3="{ row: p }">{{ p.peserta_deal }}</template>
                <template #cell-4="{ row: p }">{{ money(p.nilai_so) }}</template>
                <template #cell-5="{ row: p }"><ActionMenu><Link data-tour-next="soProg" :href="appUrl(`/sales-order/program/${p.id}`)">Lihat SO</Link></ActionMenu></template>
            </ServerTable>
            <ServerTable v-else-if="tab === 'batal'" :url="appUrl('/sales-order/data')" label="SO Batal" :columns='[{"title":"No","orderable":false},{"title":"Nomor SO","orderable":true},{"title":"Quotation","orderable":true},{"title":"Penjualan Program","orderable":true},{"title":"Dibatalkan","orderable":true},{"title":"Saat batal","orderable":false,"className":"dt-body-right"},{"title":"Diganti oleh","orderable":false},{"title":"Action","orderable":false}]'>
                <template #cell-0="{ number }">{{ number }}</template>
                <template #cell-1="{ row: s }"><span class="code">{{ s.nomor }}</span><small>{{ fmt(s.tanggal) }}</small></template>
                <template #cell-2="{ row: s }"><span class="code">{{ s.quotation_nomor }}</span><small>{{ s.kepada_nama }}</small></template>
                <template #cell-3="{ row: s }"><span class="code">{{ s.program_nomor }}</span> <span class="tag" :class="s.jenis === 'b2b' ? 'tag-b2b' : 'tag-public'">{{ s.jenis === 'b2b' ? 'B2B' : 'Public' }}</span><small>{{ range(s.tgl_mulai, s.tgl_selesai) }}</small></template>
                <template #cell-4="{ row: s }"><span class="badge st-batal">{{ s.sebab }}</span><small>{{ fmt(s.batal_date) }}</small></template>
                <template #cell-5="{ row: s }">{{ s.batal_peserta ?? 0 }} peserta<small>{{ money(s.batal_nilai ?? 0) }}</small></template>
                <template #cell-6="{ row: s }"><template v-if="s.diganti"><Link :href="appUrl(`/sales-order/${s.diganti.id}`)" class="code">{{ s.diganti.nomor }}</Link><small>Deal ulang {{ fmt(s.diganti.tanggal) }}</small></template><span v-else-if="s.menunggu_deal_ulang" class="muted">Menunggu Deal ulang</span><span v-else class="muted">—</span></template>
                <template #cell-7="{ row: s }"><ActionMenu><Link :href="appUrl(`/sales-order/program/${s.id_penjualan}`)">View</Link></ActionMenu></template>
            </ServerTable>
            <ServerTable v-else :url="appUrl('/sales-order/data')" label="Surat Kerja Trainer" :columns='[{"title":"No","orderable":false},{"title":"Nomor Surat","orderable":true},{"title":"Status","orderable":true},{"title":"Penjualan Program","orderable":true},{"title":"Trainer & Asisten","orderable":false},{"title":"Peserta deal","orderable":false,"className":"dt-body-right"},{"title":"Action","orderable":false}]' :filters="{ ...filters, tab }" refresh-on-visit>
                <template #cell-0="{ number }">{{ number }}</template>
                <template #cell-1="{ row: s }"><Link data-tour-next="sktDetail" class="code" :href="appUrl(`/surat-kerja-trainer/${s.id}`)">{{ s.nomor }}</Link><small>{{ fmt(s.tanggal) }}</small></template>
                <template #cell-2="{ row: s }"><span v-if="s.status === 'diganti'" class="badge st-draft">Diganti</span><span v-else-if="s.status === 'batal'" class="badge st-batal">Batal</span><span v-else class="badge st-terkirim">Berlaku</span></template>
                <template #cell-3="{ row: s }"><span class="code">{{ s.facts?.program.nomor || s.program_nomor }}</span><br>{{ s.facts?.judul || 'Snapshot belum tersedia — perlu rekonsiliasi' }}<small>{{ range(s.facts?.program.tgl_mulai, s.facts?.program.tgl_selesai) }} · {{ s.facts?.program.lokasi }}</small></template>
                <template #cell-4="{ row: s }"><b v-if="s.facts?.trainer.nama">{{ s.facts?.trainer.nama }}</b><span v-else class="muted">Trainer belum dipilih</span><small>{{ s.facts?.trainer.bidang_keahlian }}</small><small>Asisten: {{ s.facts?.asisten.nama || '—' }}</small></template>
                <template #cell-5="{ row: s }">{{ s.peserta_deal }}</template>
                <template #cell-6="{ row: s }"><ActionMenu><Link data-tour-next="sktDetail" :href="appUrl(`/surat-kerja-trainer/${s.id}`)">View</Link><a :href="appUrl(`/surat-kerja-trainer/${s.id}/pdf`)" target="_blank" rel="noopener">Print Out Surat Kerja</a></ActionMenu></template>
            </ServerTable>
        </div>
    </AppLayout>
</template>
