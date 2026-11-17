<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import PageHead from '../../components/PageHead.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import RefundDetail from '../../components/RefundDetail.vue';
import ServerTable from "../../components/ServerTable.vue";
import ActionMenu from "../../components/ActionMenu.vue";
import { Head, Link, router } from '@inertiajs/vue3';
import { onBeforeUnmount, reactive, ref, watch } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import type { PageRows as Page } from '../../types/salesOrder';
import { money } from '../../types/salesOrder';
const props=defineProps<{rows:Page<{id:number;nomor:string;tanggal:string;siapa:string;invoice:string;so:string;alasan:string;status:string;nominal_tagihan:string;nominal_refund:string|null}>;filters:{q?:string;status?:string};pending:number;canSalesOrder:boolean;canManage:boolean;counts:{so?:number;surat?:number}}>();
const filter=reactive({q:props.filters.q||'',status:props.filters.status||''});
const canSalesOrder = () => props.canSalesOrder;
let timer: ReturnType<typeof setTimeout> | undefined;
const search = () => router.get(appUrl('/sales-order/refund'), filter, {preserveState:true,preserveScroll:true,replace:true});
watch(()=>filter.status,()=>{clearTimeout(timer);search();});
watch(()=>filter.q,()=>{clearTimeout(timer);timer=setTimeout(search,300);});
type Detail = Pick<InstanceType<typeof RefundDetail>['$props'], 'refund'|'snapshot'|'canManage'|'closed'>;
const opened=ref(false), loading=ref(false), error=ref(''), detail=ref<Detail|null>(null), processing=ref(false);
let requestController:AbortController|undefined;
async function open(id:number, process=false) {
    requestController?.abort(); const controller=new AbortController(); requestController=controller;
    opened.value=true;loading.value=true;error.value='';detail.value=null;processing.value=process;
    try { const response=await fetch(appUrl(`/sales-order/refund/${id}?modal=1`),{signal:controller.signal,headers:{Accept:'application/json'}});if(!response.ok)throw new Error('Detail Refund tidak dapat dimuat.');const data=await response.json();if(!controller.signal.aborted)detail.value=data; }
    catch(e){if(!controller.signal.aborted)error.value=e instanceof Error?e.message:'Detail Refund tidak dapat dimuat.';}
    finally{if(!controller.signal.aborted)loading.value=false;}
}
const busy=ref(false);
function close(){if(busy.value)return;requestController?.abort();opened.value=false;}
onBeforeUnmount(()=>{clearTimeout(timer);requestController?.abort();});
</script>
<template><Head title="Refund"/><AppLayout title="Refund"><PageHead title="Sales Order" description="Pengembalian dana peserta atau company dengan Invoice yang sudah dibayar. Transfer dilakukan Finance di luar aplikasi; penyelesaian dicatat bersama bukti transfer." /><div class="batch-card"><nav class="tabs" aria-label="Jenis dokumen"><Link v-if="canSalesOrder()" :href="appUrl('/sales-order')">Sales Order<span class="nav-count">{{counts.so}}</span></Link><Link v-if="canSalesOrder()" :href="appUrl('/sales-order?tab=surat')">Surat Kerja Trainer<span class="nav-count">{{counts.surat}}</span></Link><Link :href="appUrl('/sales-order/refund')" aria-current="page">Refund<span class="nav-count">{{pending}}</span></Link></nav><p>Refund terbentuk dari peserta/company dengan Invoice lunas yang diproses tidak ikut setelah Reschedule.</p><form class="filters" @submit.prevent="router.get(appUrl('/sales-order/refund'),filter)"><label class="grow">Cari<input v-model="filter.q" type="search" maxlength="100" placeholder="Cari nomor refund, peserta, SO, invoice…" /></label><label>Status<select v-model="filter.status"><option value="">Semua status</option><option value="menunggu">Menunggu Finance</option><option value="selesai">Selesai</option></select></label></form><ServerTable :keyword="filters.q || ''" :url="appUrl('/sales-order/refund/data')" label="Refund" :columns='[{"title": "No", "orderable": false, "className": ""}, {"title": "Nomor Refund", "orderable": true, "className": ""}, {"title": "Peserta / Company", "orderable": false, "className": ""}, {"title": "Sales Order", "orderable": false, "className": ""}, {"title": "Invoice", "orderable": false, "className": ""}, {"title": "Nominal", "orderable": true, "className": "dt-body-right"}, {"title": "Status", "orderable": true, "className": ""}, {"title": "Action", "orderable": false, "className": ""}]' :filters="{ ...filters }" refresh-on-visit ><template #cell-0="{ row: p, number }">{{ number }}</template><template #cell-1="{ row: p, number }"><button class="link code" type="button" @click="open(p.id)">{{ p.nomor }}</button><small>{{ p.tanggal }}</small></template><template #cell-2="{ row: p, number }">{{ p.siapa }}<small>{{ p.alasan }}</small></template><template #cell-3="{ row: p, number }">{{ p.so }}</template><template #cell-4="{ row: p, number }">{{ p.invoice }}<small>Sudah dibayar</small></template><template #cell-5="{ row: p, number }">{{ money(p.status==='selesai'?p.nominal_refund:p.nominal_tagihan) }}<small v-if="p.status==='selesai' && p.nominal_refund!==p.nominal_tagihan">Tagihan {{ money(p.nominal_tagihan) }}</small></template><template #cell-6="{ row: p, number }"><span class="badge" :class="`st-${p.status}`">{{ p.status==='menunggu'?'Menunggu Finance':'Selesai' }}</span><small v-if="p.status==='selesai'">Transfer · {{p.selesai_oleh || '—'}} · {{p.selesai_date}}</small></template><template #cell-7="{ row: p, number }"><ActionMenu><button type="button" @click="open(p.id)">View</button><button v-if="canManage && p.status==='menunggu'" type="button" @click="open(p.id,true)">Proses Refund</button><Link :href="appUrl(`/sales-order/refund/${p.id}`)">Buka halaman detail</Link></ActionMenu></template></ServerTable><section v-if="rows.total===0 && !filters.q && !filters.status" class="empty-state"><h2>Belum ada Refund</h2><p>Refund terbentuk dari peserta atau company dengan Invoice lunas yang diproses tidak ikut setelah Reschedule.</p></section></div><ConfirmDialog v-if="opened" :busy="busy" :title="processing?'Proses Refund':'View Refund'" confirm-label="Tutup" @confirm="close" @cancel="close"><p v-if="loading" role="status">Memuat detail…</p><p v-else-if="error" class="error" role="alert">{{error}}</p><RefundDetail v-else-if="detail" :key="detail.refund.id" v-bind="detail" :process="processing" :return-to-list="true" @busy="busy=$event" @saved="busy=false;close()" /></ConfirmDialog></AppLayout></template>
