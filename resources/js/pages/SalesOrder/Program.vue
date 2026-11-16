<script setup lang="ts">
import PageHead from '../../components/PageHead.vue';
import ServerTable from "../../components/ServerTable.vue";
import ActionMenu from "../../components/ActionMenu.vue";
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ReadyInvoice from '../../components/ReadyInvoice.vue';
import { money, type Order, type Program } from '../../types/salesOrder';
defineProps<{ program: Program & { judul: string; name_customer: string | null }; rows: Order[]; canManage: boolean }>();
const fmt = (d?: string) => (d ? new Date(`${d.slice(0, 10)}T00:00:00`).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' }) : '—');
const range = (a?: string, b?: string) => (!b || a === b ? fmt(a) : `${fmt(a)} – ${fmt(b)}`);
const stamp = (d?: string | null) => (d ? new Date(d.replace(' ', 'T')).toLocaleString('id-ID', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '');
</script>
<template>
    <Head :title="`Sales Order: ${program.nomor}`" />
    <AppLayout title="Sales Order" full-width>
        <PageHead :title="`Sales Order: ${program.nomor}`">
            <Link href="/sales-order">← Semua program</Link>
        </PageHead>
        <p class="batch-info"><span class="tag" :class="program.jenis === 'b2b' ? 'tag-b2b' : 'tag-public'">{{ program.jenis === 'b2b' ? 'B2B' : 'Public' }}</span> <b>{{ program.judul }}</b> · {{ range(program.tgl_mulai, program.tgl_selesai) }} · {{ program.lokasi || '—' }}<template v-if="program.jenis === 'b2b' && program.name_customer"> · {{ program.name_customer }}</template></p>
        <p v-if="program.closed_date" class="notice">Program Closed.</p>
        <div class="batch-card">
            <ServerTable :url="`/sales-order/program/${program.id}/data`" label="Sales Order program" :columns='[{"title":"No","orderable":false},{"title":"Nomor SO","orderable":true},{"title":"No. Quotation","orderable":true},{"title":"Kepada","orderable":true},{"title":"Peserta deal","orderable":false},{"title":"Nilai","orderable":false,"className":"dt-body-right"},{"title":"Status","orderable":true},{"title":"Action","orderable":false}]' :filters="{}" refresh-on-visit>
                <template #cell-0="{ number }">{{ number }}</template>
                <template #cell-1="{ row: p }"><Link class="code" :href="`/sales-order/${p.id}`">{{ p.nomor }}</Link><small>{{ fmt(p.tanggal) }}</small></template>
                <template #cell-2="{ row: p }"><span class="code">{{ p.nomor_quotation }}</span></template>
                <template #cell-3="{ row: p }"><span class="tag" :class="p.tujuan === 'company' ? 'tag-b2b' : 'tag-public'">{{ p.tujuan === 'company' ? 'Company' : 'Personal' }}</span> {{ p.kepada_nama }}<small v-if="p.up_nama">u.p. {{ p.up_nama }}</small></template>
                <template #cell-4="{ row: p }"><b>{{ p.jumlah_peserta }}</b> peserta<small>{{ p.nama_peserta }}</small></template>
                <template #cell-5="{ row: p }">{{ money(p.calc?.grand_total) }}</template>
                <template #cell-6="{ row: p }"><span class="badge" :class="p.status_tampil === 'Invoiced' ? 'st-deal' : p.status === 'siap' ? 'st-terkirim' : 'st-draft'">{{ p.status_tampil }}</span><small v-if="p.siap_date">{{ p.siap_nama }} · {{ stamp(p.siap_date) }}</small></template>
                <template #cell-7="{ row: p }"><ActionMenu><Link :href="`/sales-order/${p.id}`">View</Link><a :href="`/sales-order/${p.id}/pdf`" target="_blank" rel="noopener">Print Out SO</a><ReadyInvoice v-if="canManage && p.status === 'open'" :so="p" :kepada="p.kepada_nama || ''" :count="p.jumlah_peserta || 0" :total="p.calc?.grand_total || '0'" /></ActionMenu></template>
            </ServerTable>
        </div>
    </AppLayout>
</template>
