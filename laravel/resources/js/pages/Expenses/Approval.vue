<script setup lang="ts">

import ServerTable from "../../components/ServerTable.vue";

import ActionMenu from "../../components/ActionMenu.vue";

import {Head,Link,router} from '@inertiajs/vue3';

import {reactive} from 'vue';

import AppLayout from '../../layouts/AppLayout.vue';

import {money,type PageRows} from '../../types/salesOrder';

import type {ExpenseRow} from '../../types/operations';

const props=defineProps<{rows:PageRows<ExpenseRow>;kind:string;filters:Record<string,string>}>();

const f=reactive({q:'',tab:props.kind,...props.filters});

</script>

<template><Head title="Approval Pengajuan"/><AppLayout title="Approval Pengajuan" full-width><h1>Approval Pengajuan</h1><nav class="tabs" aria-label="Jenis approval"><Link href="/pengajuan-biaya/approval" :aria-current="kind==='pengajuan'?'page':undefined">Pengajuan</Link><Link href="/pengajuan-biaya/approval?tab=overbudget" :aria-current="kind==='overbudget'?'page':undefined">Overbudget</Link></nav><ServerTable url="/pengajuan-biaya/approval/data" label="Approval Pengajuan" :columns='[{"title": "No", "orderable": false, "className": ""}, {"title": "No. Program", "orderable": true, "className": ""}, {"title": "Kategori / Jenis", "orderable": true, "className": ""}, {"title": "Total", "orderable": true, "className": "dt-body-right"}, {"title": "Request By", "orderable": true, "className": ""}, {"title": "Action", "orderable": false, "className": ""}]' :filters="{ ...filters, tab: kind }" refresh-on-visit ><template #cell-0="{ row: p, number }">{{ number }}</template><template #cell-1="{ row: p, number }">{{ p.program_nomor }}<small>{{ p.nomor }} · {{ p.tanggal }}</small></template><template #cell-2="{ row: p, number }">{{ p.tipe_biaya==='fixed'?'Fixed Cost':'Variable Cost' }}<small>{{ kind==='overbudget'?'Overbudget':'Pengajuan' }}</small></template><template #cell-3="{ row: p, number }">{{ money(p.total) }}</template><template #cell-4="{ row: p, number }">{{ p.pengaju }}</template><template #cell-5="{ row: p, number }"><ActionMenu><Link :href="`/pengajuan-biaya/${kind}/${p.id}`">Lihat / Proses</Link></ActionMenu></template></ServerTable></AppLayout></template>

