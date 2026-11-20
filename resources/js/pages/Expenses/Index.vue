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

<template><Head title="Pengajuan Biaya"/><AppLayout title="Pengajuan Biaya" full-width><PageHead title="Pengajuan Biaya" /><div class="batch-card pb-list"><nav class="tabs" aria-label="Daftar biaya"><Link :href="appUrl('/pengajuan-biaya')" :aria-current="tab==='program'?'page':undefined">Ajukan Biaya<span class="nav-count">{{tableCounts.program}}</span></Link><Link :href="appUrl('/pengajuan-biaya?tab=pengajuan')" :aria-current="tab==='pengajuan'?'page':undefined">Sudah Diajukan<span class="nav-count">{{tableCounts.pengajuan}}</span></Link></nav><form class="toolbar pb-toolbar" @submit.prevent="router.get(appUrl('/pengajuan-biaya'),f)"><label class="search"><span class="sr-only">Cari pengajuan biaya</span><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path d="m16 16 4 4"/></svg><input v-model="f.q" type="search" maxlength="100" :placeholder="tab==='program'?'Cari nomor program, judul, customer, sales, trainer…':'Cari nomor pengajuan, program, deskripsi, pengaju…'" /></label><label class="pb-filter"><span class="sr-only">Jenis</span><select v-model="f.jenis"><option value="">{{ tab==='program'?'Public & B2B':'Kasbon & Direct Payment' }}</option><template v-if="tab==='program'"><option value="public">Program Public</option><option value="b2b">Program B2B</option></template><template v-else><option value="kasbon">Kasbon</option><option value="direct_payment">Direct Payment</option></template></select></label><label v-if="tab==='pengajuan'" class="pb-filter"><span class="sr-only">Progres</span><select v-model="f.status"><option value="">Semua progres</option><option v-for="(label,key) in progress" :key="key" :value="key">{{label}}</option></select></label></form><ServerTable @loaded="result=>{if(result.meta?.counts)tableCounts=result.meta.counts as typeof props.counts}" :keyword="filters.q || ''" :url="appUrl('/pengajuan-biaya/data')" label="Ajukan Biaya" :columns='[{"title": "No", "orderable": false, "className": ""}, {"title": "Program", "orderable": true, "className": "pb-program"}, {"title": "Tim", "orderable": false, "className": "pb-team"}, {"title": "Budget", "orderable": false, "className": "dt-body-right pb-budget"}, {"title": "Status", "orderable": false, "className": ""}, {"title": "Action", "orderable": false, "className": ""}]' :filters="{ ...filters, tab }" refresh-on-visit v-if="tab==='program'"><template #cell-0="{ row: p, number }">{{ number }}</template><template #cell-1="{ row: p, number }"><div class="pb-program-head"><span class="tag" :class="p.jenis==='b2b'?'tag-b2b':'tag-public'">{{p.jenis==='b2b'?'B2B':'Public'}}</span><Link class="btn btn-ghost sm code" data-tour-next="pbProg" :href="appUrl(`/pengajuan-biaya/program/${p.id}`)">{{p.nomor}}</Link></div><div class="pb-title">{{p.judul}}</div><small>{{p.tgl_mulai}} s.d. {{p.tgl_selesai}} · {{p.jenis==='b2b' ? p.tim?.company || '—' : 'Public'}}</small></template><template #cell-2="{ row: p, number }"><b>Sales:</b> {{p.tim?.sales.join(', ') || '—'}}<small>Trainer: {{p.tim?.trainer || '—'}}</small></template><template #cell-3="{ row: p, number }">{{money(budgetSum(p, 'budget'))}}<small>terpakai {{money(budgetSum(p, 'aktual'))}}</small><b class="pb-remaining" :class="{neg:budgetSum(p, 'sisa')<=0}">sisa {{money(budgetSum(p, 'sisa'))}}</b></template><template #cell-4="{ row: p, number }"><span class="badge" :class="p.closed_date?'st-draft':p.waiting?'st-menunggu':p.pengajuan_count?'st-disetujui':'st-draft'">{{p.closed_date?'Closed':p.waiting?`${p.waiting} Waiting Approval`:p.pengajuan_count?'Berjalan':'Belum ada pengajuan'}}</span></template><template #cell-5="{ row: p, number }"><ActionMenu><Link data-tour-next="pbProg" :href="appUrl(`/pengajuan-biaya/program/${p.id}`)">Lihat Pengajuan</Link></ActionMenu></template></ServerTable><ServerTable @loaded="result=>{if(result.meta?.counts)tableCounts=result.meta.counts as typeof props.counts}" :keyword="filters.q || ''" :url="appUrl('/pengajuan-biaya/data')" label="Sudah Diajukan" :columns='[{"title": "No", "orderable": false, "className": ""}, {"title": "No. Pengajuan", "orderable": true, "className": ""}, {"title": "Program", "orderable": true, "className": ""}, {"title": "Kategori / Deskripsi", "orderable": true, "className": ""}, {"title": "Total", "orderable": true, "className": "dt-body-right"}, {"title": "Request By", "orderable": true, "className": ""}, {"title": "Progres", "orderable": true, "className": ""}, {"title": "Action", "orderable": false, "className": ""}]' :filters="{ ...filters, tab }" refresh-on-visit v-else><template #cell-0="{ row: p, number }">{{ number }}</template><template #cell-1="{ row: p, number }"><span class="code">{{p.nomor}}</span><div class="pb-request-meta"><span class="tag" :class="p.jenis==='kasbon'?'tag-b2b':'tag-public'">{{p.jenis==='kasbon'?'Kasbon':'Direct Payment'}}</span><span class="muted">{{p.tanggal}}</span></div></template><template #cell-2="{ row: p, number }"><Link class="btn btn-ghost sm code" :href="appUrl(`/pengajuan-biaya/program/${p.id_penjualan}`)">{{p.program_nomor}}</Link><small>{{p.judul}}</small></template><template #cell-3="{ row: p, number }"><b>{{p.tipe_biaya==='fixed'?'Fixed Cost':'Variable Cost'}}</b><div>{{p.deskripsi}}</div></template><template #cell-4="{ row: p, number }">{{ money(p.total) }}</template><template #cell-5="{ row: p, number }">{{ p.pengaju }}</template><template #cell-6="{ row: p, number }"><ol class="pb-prog" :data-status="p.status" :aria-label="`Progres: ${progress[p.status]}`"><li v-for="(state,i) in ['menunggu','disetujui','paid']" :key="state" :aria-current="state===p.status?'step':undefined"><i aria-hidden="true"></i><span>{{i===0 && ['ditolak','batal'].includes(p.status)?progress[p.status]:progress[state]}}</span></li></ol></template><template #cell-7="{ row: p, number }"><ActionMenu><Link :href="appUrl(`/pengajuan-biaya/pengajuan/${p.id}`)">Lihat / Proses</Link></ActionMenu></template></ServerTable></div></AppLayout></template>

<style scoped>
.pb-toolbar { align-items: center; }
.pb-toolbar .search { flex: 1 1 320px; max-width: 460px; }
.pb-filter { flex: 0 1 210px; min-width: 180px; }
.pb-filter select { margin: 0; }
.pb-program-head, .pb-request-meta { display: flex; align-items: center; gap: 8px; flex-wrap: wrap; }
.pb-title { margin: 4px 0; }
.pb-list :deep(td.pb-program) { min-width: 280px; max-width: 440px; white-space: normal; }
.pb-list :deep(td.pb-team) { min-width: 180px; }
.pb-list :deep(td.pb-budget) { min-width: 180px; white-space: nowrap; }
.pb-remaining { display: block; margin-top: 4px; }
.pb-prog { display: flex; list-style: none; margin: 0; padding: 0; min-width: 300px; }
.pb-prog li { flex: 1; position: relative; text-align: center; font-size: 11px; color: var(--ink-70); padding-top: 22px; line-height: 1.25; }
.pb-prog i { position: absolute; top: 0; left: 50%; transform: translateX(-50%); width: 18px; height: 18px; border-radius: 50%; background: white; border: 2px solid var(--line); z-index: 1; }
.pb-prog li::before { content: ''; position: absolute; top: 8px; left: -50%; width: 100%; height: 2px; background: var(--line); }
.pb-prog li:first-child::before { display: none; }
.pb-prog[data-status=disetujui] li:first-child i, .pb-prog[data-status=paid] i { background: #2E8B57; border-color: #2E8B57; }
.pb-prog[data-status=disetujui] li:first-child i::after, .pb-prog[data-status=paid] i::after { content: '✓'; color: white; font: 700 11px/14px sans-serif; display: block; }
.pb-prog[data-status=disetujui] li:nth-child(2)::before, .pb-prog[data-status=paid] li::before { background: #2E8B57; }
.pb-prog[data-status=disetujui] li:first-child span, .pb-prog[data-status=paid] span { color: #1D6B3A; font-weight: 600; }
.pb-prog li[aria-current=step] i { background: var(--orange); border-color: var(--orange); box-shadow: 0 0 0 4px var(--orange-50); }
.pb-prog[data-status=paid] li[aria-current=step] i { background: #2E8B57; border-color: #2E8B57; box-shadow: none; }
.pb-prog li[aria-current=step] span { color: var(--ink); font-weight: 700; }
.pb-prog[data-status=ditolak] li:first-child i, .pb-prog[data-status=batal] li:first-child i { background: var(--orange-600); border-color: var(--orange-600); }
.pb-prog[data-status=ditolak] li:first-child i::after, .pb-prog[data-status=batal] li:first-child i::after { content: '×'; color: white; font: 700 13px/14px sans-serif; display: block; }
.pb-prog[data-status=ditolak] li:first-child span, .pb-prog[data-status=batal] li:first-child span { color: var(--orange-600); font-weight: 700; }
@media (max-width: 600px) { .pb-toolbar .search { flex-basis: 100%; max-width: none; } .pb-filter { flex: 1 1 180px; } }
</style>
