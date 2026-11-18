<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import PageHead from '../../components/PageHead.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import RefundDetail from '../../components/RefundDetail.vue';
import ServerTable from '../../components/ServerTable.vue';
import ActionMenu from '../../components/ActionMenu.vue';
import EmptyState from '../../components/EmptyState.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, reactive, ref, watch } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import type { PageRows as Page } from '../../types/salesOrder';
import { money } from '../../types/salesOrder';

// Satu halaman untuk dua menu: Daftar Refund (mode daftar) dan Approval Refund (mode approval). RF-FR-03 dan RF-FR-05.
type Row = { id: number; nomor: string; tanggal: string; sebab: string; siapa: string; invoice: string; so: string; alasan: string; status: string; nominal_tagihan: string; nominal_refund: string | null;
    keputusan: { jenis: string; oleh: string | null; waktu: string; catatan: string | null } | null; selesai_oleh: string | null; selesai_date: string | null };
const props = defineProps<{ rows: Page<Row>; filters: { q?: string; status?: string; tab?: string }; tab: string; mode: 'daftar' | 'approval';
    counts: { menunggu: number; disetujui: number; ditolak: number; selesai: number; riwayat: number };
    can: { proses: boolean; ajukan: boolean; approve: boolean; approval: boolean; daftar: boolean } }>();
const STATUS: Record<string, string> = { menunggu: 'Menunggu Approval', disetujui: 'Disetujui, menunggu transfer', ditolak: 'Ditolak, perlu diperbaiki', selesai: 'Selesai' };
const SEBAB: Record<string, string> = { reschedule: 'Tidak ikut setelah reschedule', batal: 'Penjualan Program dibatalkan' };
const approval = computed(() => props.mode === 'approval');
const base = computed(() => appUrl(approval.value ? '/refund/approval' : '/refund'));
const filter = reactive({ q: props.filters.q || '', status: props.filters.status || '' });
let timer: ReturnType<typeof setTimeout> | undefined;
const search = () => router.get(base.value, { ...Object.fromEntries(Object.entries(filter).filter(([, v]) => v !== '')), ...(approval.value ? { tab: props.tab } : {}) }, { preserveState: true, preserveScroll: true, replace: true });
watch(() => filter.status, () => { clearTimeout(timer); search(); });
watch(() => filter.q, () => { clearTimeout(timer); timer = setTimeout(search, 300); });

type Detail = InstanceType<typeof RefundDetail>['$props'];
const opened = ref(false), loading = ref(false), error = ref(''), detail = ref<Detail | null>(null), mode = ref<Detail['mode']>(null), busy = ref(false);
let controller: AbortController | undefined;
async function open(id: number, as: Detail['mode'] = null) {
    controller?.abort(); const c = new AbortController(); controller = c;
    opened.value = true; loading.value = true; error.value = ''; detail.value = null; mode.value = as;
    try {
        const response = await fetch(appUrl(`/refund/${id}?modal=1`), { signal: c.signal, headers: { Accept: 'application/json' } });
        if (!response.ok) throw new Error('Detail Refund tidak dapat dimuat.');
        const data = await response.json();
        if (!c.signal.aborted) detail.value = data;
    } catch (e) { if (!c.signal.aborted) error.value = e instanceof Error ? e.message : 'Detail Refund tidak dapat dimuat.'; }
    finally { if (!c.signal.aborted) loading.value = false; }
}
function close() { if (busy.value) return; controller?.abort(); opened.value = false; }
function saved() { busy.value = false; opened.value = false; router.reload(); }
onBeforeUnmount(() => { clearTimeout(timer); controller?.abort(); });
const title = computed(() => (mode.value === 'proses' ? 'Proses Refund' : mode.value === 'keputusan' ? 'Approval Refund' : mode.value === 'perbaiki' ? 'Perbaiki & ajukan ulang' : 'View Refund'));
const columns = [{ title: 'No', orderable: false }, { title: 'Nomor Refund', orderable: true }, { title: 'Peserta / Company', orderable: false }, { title: 'SO / Invoice', orderable: false },
    { title: 'Nominal', orderable: true, className: 'dt-body-right' }, { title: 'Status', orderable: true }, { title: 'Action', orderable: false }];
const empty = computed(() => props.rows.total === 0 && !props.filters.q && !props.filters.status);
</script>

<template>
    <Head :title="approval ? 'Approval Refund' : 'Daftar Refund'" />
    <AppLayout :title="approval ? 'Approval Refund' : 'Daftar Refund'" full-width>
        <PageHead :title="approval ? 'Approval Refund' : 'Daftar Refund'"
            :description="approval
                ? 'Persetujuan Finance sebelum dana ditransfer. Setujui: refund lanjut ke proses transfer. Tolak: alasan wajib; refund kembali ke Sales/Marketing untuk diperbaiki lalu diajukan ulang.'
                : 'Pengembalian dana peserta atau company yang sudah membayar lalu tidak ikut. Refund terbentuk otomatis, bukan diinput manual. Setelah disetujui di Approval Refund, Finance mencatat transfer di sini.'" />
        <div class="batch-card">
            <nav v-if="approval" class="tabs" aria-label="Status approval">
                <Link :href="appUrl('/refund/approval')" :aria-current="tab === 'menunggu' ? 'page' : undefined">Menunggu Approval<span class="nav-count">{{ counts.menunggu }}</span></Link>
                <Link :href="appUrl('/refund/approval?tab=riwayat')" :aria-current="tab === 'riwayat' ? 'page' : undefined">Sudah diputuskan<span class="nav-count">{{ counts.riwayat }}</span></Link>
            </nav>
            <form class="filters" @submit.prevent="search">
                <label class="grow">Cari<input v-model="filter.q" type="search" maxlength="100" placeholder="Cari nomor refund, peserta, SO, invoice…" /></label>
                <label v-if="!approval">Status<select v-model="filter.status"><option value="">Semua status</option><option v-for="(label, key) in STATUS" :key="key" :value="key">{{ label }}</option></select></label>
            </form>
            <ServerTable v-if="!empty" :keyword="filters.q || ''" :url="appUrl(approval ? '/refund/approval/data' : '/refund/data')" :label="approval ? 'Approval Refund' : 'Daftar Refund'" :columns="columns" :filters="{ ...filters, tab }" refresh-on-visit>
                <template #cell-0="{ number }">{{ number }}</template>
                <template #cell-1="{ row: p }"><button class="link code" type="button" @click="open(p.id)">{{ p.nomor }}</button><small>{{ p.tanggal }}</small></template>
                <template #cell-2="{ row: p }"><b>{{ p.siapa }}</b><small>{{ SEBAB[p.sebab] ?? '' }}: {{ p.alasan }}</small></template>
                <template #cell-3="{ row: p }"><span class="code">{{ p.so }}</span><small class="code">{{ p.invoice }} <span class="muted">dibayar</span></small></template>
                <template #cell-4="{ row: p }">{{ money(p.status === 'selesai' ? p.nominal_refund : p.nominal_tagihan) }}<small v-if="p.status === 'selesai' && p.nominal_refund !== p.nominal_tagihan">Tagihan {{ money(p.nominal_tagihan) }}</small></template>
                <template #cell-5="{ row: p }"><span class="badge" :class="`st-${p.status}`">{{ STATUS[p.status] }}</span>
                    <small v-if="p.keputusan">{{ p.keputusan.oleh ?? '—' }} · {{ p.keputusan.waktu }}</small>
                    <small v-if="p.status === 'selesai'">Transfer · {{ p.selesai_oleh ?? '—' }} · {{ p.selesai_date }}</small></template>
                <template #cell-6="{ row: p }"><ActionMenu>
                    <button type="button" @click="open(p.id)">View</button>
                    <button v-if="approval && can.approve && p.status === 'menunggu'" type="button" @click="open(p.id, 'keputusan')">Proses (Setujui / Tolak)</button>
                    <button v-if="!approval && can.proses && p.status === 'disetujui'" type="button" @click="open(p.id, 'proses')">Proses Refund</button>
                    <button v-if="!approval && can.ajukan && p.status === 'ditolak'" type="button" @click="open(p.id, 'perbaiki')">Perbaiki &amp; ajukan ulang</button>
                </ActionMenu></template>
            </ServerTable>
            <EmptyState v-else title="Belum ada Refund" description="Refund terbentuk otomatis saat peserta yang sudah membayar tidak ikut setelah reschedule, atau Penjualan Program dibatalkan." />
        </div>
        <ConfirmDialog v-if="opened" size="wide" :info="!mode" :busy="busy" :title="title" confirm-label="Tutup" @confirm="close" @cancel="close">
            <p v-if="loading" role="status">Memuat detail…</p><p v-else-if="error" class="error" role="alert">{{ error }}</p>
            <RefundDetail v-else-if="detail" :key="detail.refund.id" v-bind="detail" :mode="mode" :return-to-list="true" @busy="busy = $event" @saved="saved" />
        </ConfirmDialog>
    </AppLayout>
</template>
