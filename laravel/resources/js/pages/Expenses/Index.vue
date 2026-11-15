<script setup lang="ts">

import ServerTable from "../../components/ServerTable.vue";

import ActionMenu from "../../components/ActionMenu.vue";

import {Head,Link,router} from '@inertiajs/vue3';

import {reactive,shallowRef} from 'vue';

import AppLayout from '../../layouts/AppLayout.vue';

import {money,type PageRows} from '../../types/salesOrder';

import {progress,type Budget,type ExpenseRow,type OpsProgram} from '../../types/operations';

type Row=ExpenseRow & OpsProgram & {budget?:{fixed:Budget;variable:Budget};waiting:number;pengajuan_count:number};

const props=defineProps<{rows:PageRows<Row>;filters:Record<string,string>;counts:Record<string,number>;tab:string}>();

const tableCounts = shallowRef(props.counts);
const f=reactive({q:'',jenis:'',status:'',tab:props.tab,...props.filters});

</script>

<template><Head title="Pengajuan Biaya"/><AppLayout title="Pengajuan Biaya" full-width><h1>Pengajuan Biaya</h1><nav class="tabs" aria-label="Daftar biaya"><Link href="/pengajuan-biaya" :aria-current="tab==='program'?'page':undefined">Ajukan Biaya ({{tableCounts.program}})</Link><Link href="/pengajuan-biaya?tab=pengajuan" :aria-current="tab==='pengajuan'?'page':undefined">Sudah Diajukan ({{tableCounts.pengajuan}})</Link></nav><form class="filters" @submit.prevent="router.get('/pengajuan-biaya',f)"><label>Jenis<select v-model="f.jenis"><option value="">Semua</option><template v-if="tab==='program'"><option value="public">Public</option><option value="b2b">B2B</option></template><template v-else><option value="kasbon">Kasbon</option><option value="direct_payment">Direct Payment</option></template></select></label><label v-if="tab==='pengajuan'">Progres<select v-model="f.status"><option value="">Semua</option><option v-for="(label,key) in progress" :key="key" :value="key">{{label}}</option></select></label><button>Terapkan</button></form><ServerTable @loaded="result=>{if(result.meta?.counts)tableCounts=result.meta.counts as typeof props.counts}" url="/pengajuan-biaya/data" label="Ajukan Biaya" :columns='[{"title": "No", "orderable": false, "className": ""}, {"title": "Program", "orderable": true, "className": ""}, {"title": "Tim", "orderable": false, "className": ""}, {"title": "Budget", "orderable": false, "className": "dt-body-right"}, {"title": "Status", "orderable": false, "className": ""}, {"title": "Action", "orderable": false, "className": ""}]' :filters="{ ...filters, tab }" refresh-on-visit v-if="tab==='program'"><template #cell-0="{ row: p, number }">{{ number }}</template><template #cell-1="{ row: p, number }">{{ p.nomor }}<small>{{ p.judul }} · {{ p.jenis.toUpperCase() }}</small><small>{{ p.tgl_mulai }} · {{ p.lokasi }}</small></template><template #cell-2="{ row: p, number }">{{ p.tim?.company }}<small>Sales {{ p.tim?.sales.join(", ") }}</small><small>Trainer {{ p.tim?.trainer }}</small></template><template #cell-3="{ row: p, number }"><div v-for="(b,key) in p.budget" :key="key" class="budget-cell"><b>{{ key==='fixed'?'Fixed Cost':'Variable Cost' }}</b><small>Budget {{ money(b.budget) }}</small><small>Aktual {{ money(b.aktual) }}</small><small>Sisa {{ money(b.sisa) }}</small></div></template><template #cell-4="{ row: p, number }">{{ p.closed_date?"Closed":p.waiting?`${p.waiting} Waiting Approval`:p.pengajuan_count?"Berjalan":"Belum ada pengajuan" }}</template><template #cell-5="{ row: p, number }"><ActionMenu><Link :href="`/pengajuan-biaya/program/${p.id}`">Lihat Pengajuan</Link></ActionMenu></template></ServerTable><ServerTable @loaded="result=>{if(result.meta?.counts)tableCounts=result.meta.counts as typeof props.counts}" url="/pengajuan-biaya/data" label="Sudah Diajukan" :columns='[{"title": "No", "orderable": false, "className": ""}, {"title": "No. Pengajuan", "orderable": true, "className": ""}, {"title": "Program", "orderable": true, "className": ""}, {"title": "Kategori / Deskripsi", "orderable": true, "className": ""}, {"title": "Total", "orderable": true, "className": "dt-body-right"}, {"title": "Request By", "orderable": true, "className": ""}, {"title": "Progres", "orderable": true, "className": ""}, {"title": "Action", "orderable": false, "className": ""}]' :filters="{ ...filters, tab }" refresh-on-visit v-else><template #cell-0="{ row: p, number }">{{ number }}</template><template #cell-1="{ row: p, number }">{{ p.nomor }}<small>{{ p.jenis }} · {{ p.tanggal }}</small></template><template #cell-2="{ row: p, number }">{{ p.program_nomor }}<small>{{ p.judul }}</small></template><template #cell-3="{ row: p, number }">{{ p.tipe_biaya==='fixed'?'Fixed Cost':'Variable Cost' }}<small>{{ p.deskripsi }}</small></template><template #cell-4="{ row: p, number }">{{ money(p.total) }}</template><template #cell-5="{ row: p, number }">{{ p.pengaju }}</template><template #cell-6="{ row: p, number }"><span class="badge" :class="`st-${p.status}`">{{ progress[p.status] }}</span></template><template #cell-7="{ row: p, number }"><ActionMenu><Link :href="`/pengajuan-biaya/pengajuan/${p.id}`">Lihat / Proses</Link></ActionMenu></template></ServerTable></AppLayout></template>

