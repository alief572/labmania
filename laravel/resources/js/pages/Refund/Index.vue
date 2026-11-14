<script setup lang="ts">
import ActionMenu from "../../components/ActionMenu.vue";
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import Pagination from '../../components/Pagination.vue';
import type { PageRows as Page } from '../../types/salesOrder';
import { money } from '../../types/salesOrder';
const props=defineProps<{rows:Page<{id:number;nomor:string;tanggal:string;siapa:string;invoice:string;so:string;alasan:string;status:string;nominal_tagihan:string;nominal_refund:string|null}>;filters:{q?:string;status?:string};pending:number}>();
const filter=reactive({q:props.filters.q||'',status:props.filters.status||''});
</script>
<template><Head title="Refund"/><AppLayout title="Refund"><header class="page-header"><h1>Refund ({{pending}} Menunggu Finance)</h1><Link href="/sales-order">Sales Order</Link></header><p>Refund terbentuk dari peserta/company dengan Invoice lunas yang diproses tidak ikut setelah Reschedule.</p><form class="filters" @submit.prevent="router.get('/sales-order/refund',filter)"><label>Pencarian<input v-model="filter.q" placeholder="Nomor, peserta, company, SO, Invoice"/></label><label>Status<select v-model="filter.status"><option value="">Semua</option><option value="menunggu">Menunggu Finance</option><option value="selesai">Selesai</option></select></label><button>Cari</button></form><div class="table-wrap"><table><caption class="sr-only">Daftar Refund</caption><thead><tr><th>Nomor Refund</th><th>Peserta / Company</th><th>SO / Invoice</th><th>Dana teralokasi</th><th>Status</th><th>Aksi</th></tr></thead><tbody><tr v-for="r in rows.data" :key="r.id"><td><Link :href="`/sales-order/refund/${r.id}`">{{r.nomor}}</Link><small>{{r.tanggal}}</small></td><td>{{r.siapa}}<small>{{r.alasan}}</small></td><td>{{r.so}}<small>{{r.invoice}}</small></td><td>{{money(r.nominal_tagihan)}}</td><td>{{r.status==='menunggu'?'Menunggu Finance':'Selesai'}}<small v-if="r.nominal_refund">{{money(r.nominal_refund)}}</small></td><td><ActionMenu><Link :href="`/sales-order/refund/${r.id}`">{{r.status==='menunggu'?'View / Proses Refund':'View'}}</Link></ActionMenu></td></tr><tr v-if="!rows.data.length"><td colspan="6">Belum ada Refund sesuai filter.</td></tr></tbody></table></div><Pagination :page="rows" label="Halaman Refund"/></AppLayout></template>
