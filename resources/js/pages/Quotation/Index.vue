<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import PageHead from '../../components/PageHead.vue';
import ServerTable from '../../components/ServerTable.vue';
import ActionMenu from "../../components/ActionMenu.vue";
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, reactive, shallowRef, watch } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { rupiah } from '../../composables/costing';
type Row = { id: number; nomor: string; jenis: string; judul: string; tgl_mulai: string; tgl_selesai: string; lokasi: string; deal: number; menunggu: number; target_peserta: number; bep: number | null; jumlah_quotation: number; quotation_status: Record<string, number>; can_add: boolean };
type Stat = { status: string; jumlah: number; peserta: number; nilai: string };
const props = defineProps<{ rows: { data: Row[]; total: number; current_page: number; last_page: number; prev_page_url: string | null; next_page_url: string | null }; filters: Record<string, string>; tab: string; tabCounts: { ongoing: number; selesai: number; data: number }; stats: Stat[]; years: number[] }>();
const tableStats = shallowRef<Stat[]>(props.stats);
const f = reactive({ q: '', jenis: '', tahun: '', bulan: '', status: '', tab: props.tab, ...props.filters });

// Filter langsung berlaku (mockup tanpa tombol Terapkan); kata kunci ditunda 300 ms.
const search = () => router.get(appUrl('/quotation'), Object.fromEntries(Object.entries(f).filter(([k, v]) => v !== '' && v !== null && !(k === 'tab' && v === 'ongoing'))), { preserveState: true, preserveScroll: true, replace: true });
let timer: ReturnType<typeof setTimeout> | undefined;
watch(() => [f.jenis, f.tahun, f.bulan], () => { clearTimeout(timer); search(); });
watch(() => f.q, () => { clearTimeout(timer); timer = setTimeout(search, 300); });
onBeforeUnmount(() => clearTimeout(timer));
const status = (s: string) => { f.status = f.status === s ? '' : s; clearTimeout(timer); search(); };

const STATUS_LABEL: Record<string, string> = { draft: 'Draft', terkirim: 'Terkirim', deal: 'Deal', batal: 'Batal' };
const stat = (s: string) => tableStats.value.find((x) => x.status === s) ?? { status: s, jumlah: 0, peserta: 0, nilai: '0' };
const dealRate = computed(() => { const deal = stat('deal').jumlah, batal = stat('batal').jumlah; return deal + batal ? Math.round(deal / (deal + batal) * 100) : null; });
const bulan = (n: number) => new Date(2026, n - 1).toLocaleString('id-ID', { month: 'long' });
const tabs = [['ongoing', 'On going', appUrl('/quotation')], ['selesai', 'Selesai', appUrl('/quotation?tab=selesai')], ['data', 'Data Peserta', appUrl('/quotation?tab=data')]] as const;
const statusText = (p: Row) => ['draft', 'terkirim', 'deal', 'batal'].filter((s) => p.quotation_status[s]).map((s) => `${p.quotation_status[s]} ${STATUS_LABEL[s].toLowerCase()}`).join(', ') || 'belum ada';
const fmt = (d: string) => (d ? new Date(`${d.slice(0, 10)}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—');
const range = (a: string, b: string) => (!b || a === b ? fmt(a) : `${fmt(a)} – ${fmt(b)}`);
</script>

<template>
    <Head title="Quotation" />
    <AppLayout title="Quotation" full-width>
        <PageHead title="Daftar Quotation">
            <Link data-tour-next="qtnModal" class="primary" :href="appUrl('/quotation/create')">+ Buat Quotation</Link>
        </PageHead>

        <div v-if="tab !== 'data'" class="qtn-flow" data-tour="stats">
            <button type="button" class="kpi" :class="{ on: f.status === 'draft' }" :aria-pressed="f.status === 'draft'" title="Saring menurut status" @click="status('draft')"><span class="kpi-l">Draft</span><span class="kpi-v">{{ stat('draft').jumlah }} <small>quotation</small></span><span class="kpi-s">kuota {{ stat('draft').peserta }} peserta, belum dikirim</span></button><span class="qf-arrow" aria-hidden="true">›</span>
            <button type="button" class="kpi kwarn" :class="{ on: f.status === 'terkirim' }" :aria-pressed="f.status === 'terkirim'" title="Saring menurut status" @click="status('terkirim')"><span class="kpi-l">Terkirim</span><span class="kpi-v">{{ stat('terkirim').jumlah }} <small>quotation</small></span><span class="kpi-s">kuota {{ stat('terkirim').peserta }} peserta, menunggu deal</span></button><span class="qf-arrow" aria-hidden="true">›</span>
            <button type="button" class="kpi good" :class="{ on: f.status === 'deal' }" :aria-pressed="f.status === 'deal'" title="Saring menurut status" @click="status('deal')"><span class="kpi-l">Deal</span><span class="kpi-v">{{ stat('deal').jumlah }} <small>quotation</small></span><span class="kpi-s">{{ stat('deal').peserta }} peserta deal · {{ rupiah(stat('deal').nilai) }}<template v-if="dealRate !== null"><br>tingkat deal {{ dealRate }}%</template></span></button><span class="qf-gap"></span>
            <button type="button" class="kpi lost" :class="{ on: f.status === 'batal' }" :aria-pressed="f.status === 'batal'" title="Saring menurut status" @click="status('batal')"><span class="kpi-l">Batal</span><span class="kpi-v">{{ stat('batal').jumlah }} <small>quotation</small></span><span class="kpi-s">kuota {{ stat('batal').peserta }} peserta</span></button>
        </div>

        <div class="batch-card">
        <nav class="tabs" aria-label="Kelompok program"><Link v-for="[key, label, href] in tabs" :key="key" :href="href" :aria-current="tab === key ? 'page' : undefined">{{ label }}<span class="nav-count">{{ tabCounts[key] }}</span></Link></nav>
        <div class="filters">
            <label class="grow">Cari<input v-model="f.q" type="search" maxlength="100" :placeholder="tab === 'data' ? 'Cari program, nomor, atau nama peserta…' : 'Cari program, nomor quotation, kepada, atau peserta…'" /></label>
            <label>Jenis<select v-model="f.jenis"><option value="">Semua jenis</option><option value="public">Public</option><option value="b2b">B2B</option></select></label>
            <label>Tahun<select v-model="f.tahun"><option value="">Semua tahun</option><option v-for="y in years" :key="y" :value="y">{{ y }}</option></select></label>
            <label>Bulan<select v-model="f.bulan"><option value="">Semua bulan</option><option v-for="n in 12" :key="n" :value="n">{{ bulan(n) }}</option></select></label>
        </div>

        <div class="table-wrap"><ServerTable @loaded="(result) => { if (result.meta?.stats) tableStats = result.meta.stats as Stat[]; }" :url="appUrl('/quotation/data')" label="Daftar Quotation" :columns='[{"title":"No","orderable":false},{"title":"Penjualan Program","orderable":true},{"title":"Peserta deal","orderable":true,"className":"dt-body-right"},{"title":"Progres","orderable":false},{"title":"Quotation","orderable":false},{"title":"Action","orderable":false}]' :filters="{ ...filters }" refresh-on-visit>
            <template #cell-0="{ number }">{{ number }}</template>
            <template #cell-1="{ row: p }"><span class="code">{{ p.nomor }}</span> <span class="tag" :class="p.jenis === 'b2b' ? 'tag-b2b' : 'tag-public'">{{ p.jenis === 'b2b' ? 'B2B' : 'Public' }}</span><br>{{ p.judul }}<small>{{ range(p.tgl_mulai, p.tgl_selesai) }} · {{ p.lokasi || '—' }}</small></template>
            <template #cell-2="{ row: p }"><b>{{ p.deal }}</b> / {{ p.target_peserta }}<small v-if="p.menunggu">kuota {{ p.menunggu }} menunggu</small></template>
            <template #cell-3="{ row: p }"><small>Deal</small><progress :value="p.deal" :max="Math.max(1, p.target_peserta)" :aria-label="`Peserta Deal ${p.nomor}`"></progress><small>Deal + menunggu</small><progress :value="p.deal + p.menunggu" :max="Math.max(1, p.target_peserta)" :aria-label="`Peserta Deal dan kuota menunggu ${p.nomor}`"></progress><small>{{ p.bep === null ? 'BEP tidak tercapai' : `BEP ${p.bep} peserta · ` + (p.deal >= p.bep ? 'Lewat BEP' : `Kurang ${p.bep - p.deal} peserta menuju BEP`) }}</small></template>
            <template #cell-4="{ row: p }"><b>{{ p.jumlah_quotation }}</b> quotation<small>{{ statusText(p) }}</small></template>
            <template #cell-5="{ row: p }"><ActionMenu><Link :href="appUrl(`/quotation/batch/${p.id}${tab === 'data' ? '?data=1' : ''}`)">{{ tab === 'data' ? 'Data Peserta' : 'Lihat detail' }}</Link><Link v-if="p.can_add" :href="appUrl(`/quotation/create?id_penjualan=${p.id}`)">+ Buat Quotation</Link></ActionMenu></template>
        </ServerTable></div>
        </div>
    </AppLayout>
</template>
