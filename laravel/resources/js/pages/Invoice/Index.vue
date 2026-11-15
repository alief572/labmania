<script setup lang="ts">

import ServerTable from "../../components/ServerTable.vue";

import ActionMenu from "../../components/ActionMenu.vue";

import { computed, reactive, shallowRef } from 'vue';

import { Head, Link, router } from '@inertiajs/vue3';

import AppLayout from '../../layouts/AppLayout.vue';

import { money, type PageRows } from '../../types/salesOrder';

type Row = { id: number; status?: string; pending?: boolean; id_sales_order?: number; id_quotation: number; nomor: string; nomor_so: string; nomor_quotation: string; nomor_program: string; jenis: string; kepada_nama: string; judul: string; sales_nama: string; jumlah_peserta: number; total_tagihan: string; piutang?: string; perlakuan_ppn?: string; tanggal_bayar?: string | null; tanggal: string; closed_date?: string | null };

const props = defineProps<{ rows: PageRows<Row>; filters: Record<string, string>; tab: string; counts: { uninvoiced: number; invoiced: number }; programs: { id: number; nomor: string }[]; canAdd: boolean }>();

const tableCounts = shallowRef(props.counts);
const f = reactive({ q: '', jenis: '', tahun: '', program: '', perlakuan_ppn: '', pembayaran: '', status: '', ...props.filters, tab: props.tab });

const search = () => router.get('/invoicing', f);

const excel = computed(() => '/invoicing/excel?' + new URLSearchParams(Object.entries(f).filter(([, value]) => value !== '').map(([key, value]) => [key, String(value)])).toString());

</script>

<template>

    <Head title="Invoicing" /><AppLayout title="Invoicing" full-width><div class="page-head"><h1>Invoicing</h1><a :href="excel">Excel</a></div><nav class="tabs" aria-label="Status Invoicing"><Link href="/invoicing" :aria-current="tab === 'uninvoiced' ? 'page' : undefined">Uninvoiced ({{ tableCounts.uninvoiced }})</Link><Link href="/invoicing?tab=invoiced" :aria-current="tab === 'invoiced' ? 'page' : undefined">Invoiced ({{ tableCounts.invoiced }})</Link></nav>

        <p v-if="tab === 'uninvoiced'">SO Siap invoice, satu Invoice untuk seluruh peserta Deal. B2B All-in 1 company.</p>

        <p v-for="e in $page.props.errors" :key="String(e)" class="error" role="alert">{{ e }}</p>

        <form class="filters" @submit.prevent="search"><label>Jenis<select v-model="f.jenis"><option value="">Public &amp; B2B</option><option value="public">Public</option><option value="b2b">B2B</option></select></label><label>Program<select v-model="f.program"><option value="">Semua</option><option v-for="p in programs" :key="p.id" :value="p.id">{{ p.nomor }}</option></select></label><label>{{ tab === 'invoiced' ? 'Tahun Invoice' : 'Tahun Quotation' }}<input v-model="f.tahun" type="number" min="1900" max="2200" /></label><template v-if="tab === 'invoiced'"><label>Perlakuan PPN<select v-model="f.perlakuan_ppn"><option value="">Semua</option><option>PKP</option><option>WAPU</option><option value="KB">Kawasan Berikat</option></select></label><label>Status Invoice<select v-model="f.status"><option value="">Semua</option><option value="invoiced">Aktif</option><option value="batal">Batal</option></select></label><label>Pembayaran<select v-model="f.pembayaran"><option value="">Semua</option><option value="belum">Belum dibayar</option><option value="lunas">Lunas</option></select></label></template><button class="primary">Terapkan</button></form>

        <ServerTable @loaded="result=>{if(result.meta?.counts)tableCounts=result.meta.counts as typeof props.counts}" url="/invoicing/data" label="Uninvoiced" :columns='[{"title": "No", "orderable": false, "className": ""}, {"title": "No. Sales Order", "orderable": true, "className": ""}, {"title": "No. Quotation", "orderable": true, "className": ""}, {"title": "Customer", "orderable": true, "className": ""}, {"title": "Program", "orderable": true, "className": ""}, {"title": "Sales", "orderable": true, "className": ""}, {"title": "Cara tagih", "orderable": false, "className": ""}, {"title": "Nilai quotation", "orderable": false, "className": "dt-body-right"}, {"title": "Status", "orderable": true, "className": ""}, {"title": "Action", "orderable": false, "className": ""}]' :filters="{ ...filters, tab }" refresh-on-visit v-if="tab==='uninvoiced'"><template #cell-0="{ row: p, number }">{{ number }}</template><template #cell-1="{ row: p, number }">{{ p.nomor_so }}</template><template #cell-2="{ row: p, number }">{{ p.nomor_quotation }}</template><template #cell-3="{ row: p, number }">{{ p.kepada_nama }}</template><template #cell-4="{ row: p, number }">{{ p.nomor_program }}<small>{{ p.judul }}</small></template><template #cell-5="{ row: p, number }">{{ p.sales_nama }}</template><template #cell-6="{ row: p, number }">{{ p.skema_harga==='allin'?'All-in company':'Per quotation' }}</template><template #cell-7="{ row: p, number }">{{ money(p.total_tagihan) }}</template><template #cell-8="{ row: p, number }"><span class="badge">{{ p.pending?"Menunggu konfirmasi ulang":p.closed_date?"Closed":"Uninvoiced" }}</span></template><template #cell-9="{ row: p, number }"><Link v-if="canAdd && !p.closed_date && !p.pending" class="primary" :href="`/invoicing/create?id_sales_order=${p.id}`">Add Invoicing</Link></template></ServerTable><ServerTable @loaded="result=>{if(result.meta?.counts)tableCounts=result.meta.counts as typeof props.counts}" url="/invoicing/data" label="Invoiced" :columns='[{"title": "No", "orderable": false, "className": ""}, {"title": "No. Invoice", "orderable": true, "className": ""}, {"title": "Customer", "orderable": false, "className": ""}, {"title": "PPN", "orderable": true, "className": ""}, {"title": "Total", "orderable": true, "className": "dt-body-right"}, {"title": "Status", "orderable": true, "className": ""}, {"title": "Action", "orderable": false, "className": ""}]' :filters="{ ...filters, tab }" refresh-on-visit v-else><template #cell-0="{ row: p, number }">{{ number }}</template><template #cell-1="{ row: p, number }"><Link :href="`/invoicing/${p.id}`">{{ p.nomor }}</Link><small>{{ p.nomor_program }} · {{ p.nomor_so }} · {{ p.nomor_quotation }}</small><small>{{ p.tanggal }}</small></template><template #cell-2="{ row: p, number }">{{ p.kepada_nama }}<small>{{ p.sales_nama }}</small></template><template #cell-3="{ row: p, number }">{{ p.perlakuan_ppn }}</template><template #cell-4="{ row: p, number }">{{ money(p.total_tagihan) }}<small>Piutang {{ money(p.piutang) }}</small></template><template #cell-5="{ row: p, number }"><span class="badge" :class="`st-${p.status}`">{{ p.status==='batal'?'Batal':p.tanggal_bayar?'Lunas':'Belum dibayar' }}</span></template><template #cell-6="{ row: p, number }"><ActionMenu><Link :href="`/invoicing/${p.id}`">Lihat Invoice</Link><a :href="`/invoicing/${p.id}/pdf`" target="_blank" rel="noopener">Print Invoice</a></ActionMenu></template></ServerTable>

    </AppLayout>

</template>

