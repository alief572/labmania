<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import PageHead from '../../components/PageHead.vue';

import ServerTable from "../../components/ServerTable.vue";

import ActionMenu from "../../components/ActionMenu.vue";

import {Head,Link,router} from '@inertiajs/vue3';

import {onBeforeUnmount,reactive,shallowRef,watch} from 'vue';

import AppLayout from '../../layouts/AppLayout.vue';

import {money,type PageRows} from '../../types/salesOrder';

import {progress,type Budget,type ExpenseRow,type OpsProgram} from '../../types/operations';

type Row=ExpenseRow & OpsProgram & {budget?:{fixed:Budget;variable:Budget};waiting:number;pengajuan_count:number};

const props=defineProps<{rows:PageRows<Row>;filters:Record<string,string>;counts:Record<string,number>;tab:string}>();

const tableCounts = shallowRef(props.counts);
const f=reactive({q:'',jenis:'',status:'',tab:props.tab,...props.filters});

const search = () => router.get(appUrl('/pengajuan-biaya'), {...f,tab:props.tab}, { preserveState: true, preserveScroll: true, replace: true });
let timer: ReturnType<typeof setTimeout> | undefined;
watch(() => [f.jenis, f.status], () => { clearTimeout(timer); search(); });
watch(() => f.q, () => { clearTimeout(timer); timer = setTimeout(search, 300); });
onBeforeUnmount(() => clearTimeout(timer));
const budgetSum = (row: Row, field: keyof Omit<Budget, 'items'>) => Number(row.budget?.fixed[field] ?? 0) + Number(row.budget?.variable[field] ?? 0);
</script>

<template><Head title="Pengajuan Biaya"/><AppLayout title="Pengajuan Biaya" full-width><PageHead title="Pengajuan Biaya" description="Tim program (Sales di quotation, Trainer, Manajemen) mengajukan biaya program (Kasbon / Direct Payment) ke Manajemen. Satu baris = satu Penjualan Program Final; budget = costing Penjualan Program Final. Hanya program yang terkait dengan Anda yang tampil; Admin melihat semua." /><div class="batch-card"><nav class="tabs" aria-label="Daftar biaya"><Link :href="appUrl('/pengajuan-biaya')" :aria-current="tab==='program'?'page':undefined">Ajukan Biaya<span class="nav-count">{{tableCounts.program}}</span></Link><Link :href="appUrl('/pengajuan-biaya?tab=pengajuan')" :aria-current="tab==='pengajuan'?'page':undefined">Sudah Diajukan<span class="nav-count">{{tableCounts.pengajuan}}</span></Link></nav><form class="filters" @submit.prevent="router.get(appUrl('/pengajuan-biaya'),f)"><label class="grow">Cari<input v-model="f.q" type="search" maxlength="100" :placeholder="tab==='program'?'Cari nomor program, judul, customer, sales, trainer…':'Cari nomor pengajuan, program, deskripsi, pengaju…'" /></label><label>Jenis<select v-model="f.jenis"><option value="">{{ tab==='program'?'Public & B2B':'Kasbon & Direct Payment' }}</option><template v-if="tab==='program'"><option value="public">Public</option><option value="b2b">B2B</option></template><template v-else><option value="kasbon">Kasbon</option><option value="direct_payment">Direct Payment</option></template></select></label><label v-if="tab==='pengajuan'">Progres<select v-model="f.status"><option value="">Semua</option><option v-for="(label,key) in progress" :key="key" :value="key">{{label}}</option></select></label></form><ServerTable @loaded="result=>{if(result.meta?.counts)tableCounts=result.meta.counts as typeof props.counts}" :keyword="filters.q || ''" :url="appUrl('/pengajuan-biaya/data')" label="Ajukan Biaya" :columns='[{"title": "No", "orderable": false, "className": ""}, {"title": "Program", "orderable": true, "className": ""}, {"title": "Tim", "orderable": false, "className": ""}, {"title": "Budget", "orderable": false, "className": "dt-body-right"}, {"title": "Status", "orderable": false, "className": ""}, {"title": "Action", "orderable": false, "className": ""}]' :filters="{ ...filters, tab }" refresh-on-visit v-if="tab==='program'"><template #cell-0="{ row: p, number }">{{ number }}</template><template #cell-1="{ row: p, number }">{{ p.nomor }}<small>{{ p.judul }} · {{ p.jenis.toUpperCase() }}</small><small>{{ p.tgl_mulai }} · {{ p.lokasi }}</small></template><template #cell-2="{ row: p, number }">{{ p.tim?.company }}<small>Sales {{ p.tim?.sales.join(", ") }}</small><small>Trainer {{ p.tim?.trainer }}</small></template><template #cell-3="{ row: p, number }"><b>{{ money(budgetSum(p, 'budget')) }}</b><small>Terpakai {{ money(budgetSum(p, 'aktual')) }}</small><small :class="{ neg: budgetSum(p, 'sisa') <= 0 }">Sisa {{ money(budgetSum(p, 'sisa')) }}</small></template><template #cell-4="{ row: p, number }">{{ p.closed_date?"Closed":p.waiting?`${p.waiting} Waiting Approval`:p.pengajuan_count?"Berjalan":"Belum ada pengajuan" }}</template><template #cell-5="{ row: p, number }"><ActionMenu><Link data-tour-next="pbProg" :href="appUrl(`/pengajuan-biaya/program/${p.id}`)">Lihat Pengajuan</Link></ActionMenu></template></ServerTable><ServerTable @loaded="result=>{if(result.meta?.counts)tableCounts=result.meta.counts as typeof props.counts}" :keyword="filters.q || ''" :url="appUrl('/pengajuan-biaya/data')" label="Sudah Diajukan" :columns='[{"title": "No", "orderable": false, "className": ""}, {"title": "No. Pengajuan", "orderable": true, "className": ""}, {"title": "Program", "orderable": true, "className": ""}, {"title": "Kategori / Deskripsi", "orderable": true, "className": ""}, {"title": "Total", "orderable": true, "className": "dt-body-right"}, {"title": "Request By", "orderable": true, "className": ""}, {"title": "Progres", "orderable": true, "className": ""}, {"title": "Action", "orderable": false, "className": ""}]' :filters="{ ...filters, tab }" refresh-on-visit v-else><template #cell-0="{ row: p, number }">{{ number }}</template><template #cell-1="{ row: p, number }">{{ p.nomor }}<small>{{ p.jenis }} · {{ p.tanggal }}</small></template><template #cell-2="{ row: p, number }">{{ p.program_nomor }}<small>{{ p.judul }}</small></template><template #cell-3="{ row: p, number }">{{ p.tipe_biaya==='fixed'?'Fixed Cost':'Variable Cost' }}<small>{{ p.deskripsi }}</small></template><template #cell-4="{ row: p, number }">{{ money(p.total) }}</template><template #cell-5="{ row: p, number }">{{ p.pengaju }}</template><template #cell-6="{ row: p, number }"><span class="badge" :class="`st-${p.status}`">{{ progress[p.status] }}</span></template><template #cell-7="{ row: p, number }"><ActionMenu><Link :href="appUrl(`/pengajuan-biaya/pengajuan/${p.id}`)">Lihat / Proses</Link></ActionMenu></template></ServerTable></div></AppLayout></template>

