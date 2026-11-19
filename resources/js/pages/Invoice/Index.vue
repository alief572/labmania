<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import PageHead from '../../components/PageHead.vue';
import ServerTable from '../../components/ServerTable.vue';
import ActionMenu from '../../components/ActionMenu.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import InvoiceDetail from '../../components/InvoiceDetail.vue';
import { computed, onBeforeUnmount, reactive, ref, shallowRef, watch } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import { money } from '../../types/salesOrder';
const props = defineProps<{ filters:Record<string,string>;tab:string;counts:{uninvoiced:number;invoiced:number};programs:{id:number;nomor:string}[];years:number[];canAdd:boolean }>();
const tableCounts=shallowRef(props.counts);
const f=reactive({q:'',jenis:'',tahun:'',program:'',perlakuan_ppn:'',pembayaran:'',status:'',...props.filters});
const values=()=>({...f,tab:props.tab});
const search=()=>router.get(appUrl('/invoicing'),values(),{preserveState:true,preserveScroll:true,replace:true});
let timer:ReturnType<typeof setTimeout>|undefined;
watch(()=>[f.jenis,f.tahun,f.program,f.perlakuan_ppn,f.pembayaran,f.status],()=>{clearTimeout(timer);search();});
watch(()=>f.q,()=>{clearTimeout(timer);timer=setTimeout(search,300);});
const excel=computed(()=>appUrl('/invoicing/excel?'+new URLSearchParams(Object.entries(values()).filter(([,value])=>value!=='')).toString()));
type Detail=Pick<InstanceType<typeof InvoiceDetail>['$props'],'invoice'|'snapshot'|'journal'|'setor'|'canManage'|'today'>;
const opened=ref(false),loading=ref(false),error=ref(''),detail=ref<Detail|null>(null),initialAction=ref('');
let requestController:AbortController|undefined;
async function open(id:number,action=''){
    requestController?.abort();const controller=new AbortController();requestController=controller;
    opened.value=true;loading.value=true;error.value='';detail.value=null;initialAction.value=action;
    try{const response=await fetch(appUrl(`/invoicing/${id}?modal=1`),{signal:controller.signal,headers:{Accept:'application/json'}});if(!response.ok)throw new Error('Detail Invoice tidak dapat dimuat.');const data=await response.json();if(!controller.signal.aborted)detail.value=data;}
    catch(e){if(!controller.signal.aborted)error.value=e instanceof Error?e.message:'Detail Invoice tidak dapat dimuat.';}
    finally{if(!controller.signal.aborted)loading.value=false;}
}
const busy=ref(false);
function close(){if(busy.value)return;requestController?.abort();opened.value=false;}
onBeforeUnmount(()=>{clearTimeout(timer);requestController?.abort();});
const invoicedColumns=[{title:'No',orderable:false},{title:'No. Program',orderable:true},{title:'No. Invoice',orderable:true},{title:'Customer',orderable:false},{title:'PPN',orderable:true},{title:'Total',orderable:true,className:'dt-body-right'},{title:'Status bayar',orderable:true},{title:'Action',orderable:false}];
const uninvoicedColumns=[{title:'No',orderable:false},{title:'No. Program',orderable:true},{title:'No. Sales Order',orderable:true},{title:'No. Quotation',orderable:true},{title:'Customer',orderable:true},{title:'Program',orderable:false},{title:'Sales',orderable:true},{title:'Pembayaran',orderable:false},{title:'Nilai quotation',orderable:false,className:'dt-body-right'},{title:'Status',orderable:true},{title:'Action',orderable:false}];
</script>
<template>
    <Head title="Invoicing"/><AppLayout title="Invoicing" full-width>
        <PageHead title="Invoicing"><a :href="excel">Excel</a></PageHead>
        <p v-for="e in $page.props.errors" :key="String(e)" class="error" role="alert">{{e}}</p>
        <div class="batch-card">
            <nav class="tabs" aria-label="Status Invoicing"><Link :href="appUrl('/invoicing')" :aria-current="tab==='uninvoiced'?'page':undefined">Uninvoiced<span class="nav-count">{{tableCounts.uninvoiced}}</span></Link><Link :href="appUrl('/invoicing?tab=invoiced')" :aria-current="tab==='invoiced'?'page':undefined">Invoiced<span class="nav-count">{{tableCounts.invoiced}}</span></Link></nav>
            <form class="filters" @submit.prevent="search"><label class="grow">Cari<input v-model="f.q" type="search" maxlength="100" placeholder="Cari nomor program, invoice, quotation, customer, sales…"/></label><label>Jenis<select v-model="f.jenis" @change="f.program=''"><option value="">Public &amp; B2B</option><option value="public">Public</option><option value="b2b">B2B</option></select></label><label>Program<select v-model="f.program"><option value="">Semua program</option><option v-for="p in programs" :key="p.id" :value="p.id">{{p.nomor}}</option></select></label><label>{{tab==='invoiced'?'Tahun Invoice':'Tahun Quotation'}}<select v-model="f.tahun"><option value="">Semua tahun</option><option v-for="year in years" :key="year" :value="year">{{year}}</option></select></label><template v-if="tab==='invoiced'"><label>Perlakuan PPN<select v-model="f.perlakuan_ppn"><option value="">Semua</option><option>PKP</option><option>WAPU</option><option value="KB">Kawasan Berikat</option></select></label><label>Status Invoice<select v-model="f.status"><option value="">Semua</option><option value="invoiced">Aktif</option><option value="batal">Batal</option></select></label><label>Pembayaran<select v-model="f.pembayaran"><option value="">Semua</option><option value="belum">Belum dibayar</option><option value="lunas">Lunas</option></select></label></template></form>
            <ServerTable v-if="tab==='uninvoiced'" :url="appUrl('/invoicing/data')" label="Uninvoiced" :keyword="filters.q || ''" :columns="uninvoicedColumns" :filters="{...filters,tab}" refresh-on-visit @loaded="result=>{if(result.meta?.counts)tableCounts=result.meta.counts as typeof props.counts}">
                <template #cell-0="{number}">{{number}}</template>
                <template #cell-1="{row:p}"><span class="tag" :class="p.jenis==='b2b'?'tag-b2b':'tag-public'">{{p.jenis==='b2b'?'B2B':'Public'}}</span> <span class="code">{{p.nomor_program}}</span><small>{{p.tgl_mulai}} s.d. {{p.tgl_selesai}}</small></template>
                <template #cell-2="{row:p}"><span class="code">{{p.nomor_so}}</span></template>
                <template #cell-3="{row:p}"><span class="code">{{p.nomor_quotation}}</span></template>
                <template #cell-4="{row:p}"><span class="tag">{{p.tujuan==='company'?'Company':'Personal'}}</span> {{p.kepada_nama}}</template>
                <template #cell-5="{row:p}">{{p.judul}}</template><template #cell-6="{row:p}">{{p.sales_nama}}</template>
                <template #cell-7="{row:p}"><template v-if="p.pembayaran?.cara==='top'">TOP {{p.pembayaran.jumlah}} termin<small>Berikutnya: Termin {{p.pembayaran.berikutnya}} · {{p.pembayaran.persen}}%</small><small>{{p.pembayaran.keterangan}}</small></template><template v-else>Full payment<small>{{p.skema_harga==='allin'?'All-in company':'Per quotation'}}</small></template></template><template #cell-8="{row:p}">{{money(p.total_tagihan)}}</template>
                <template #cell-9="{row:p}"><span class="badge">{{p.pending?'Menunggu konfirmasi ulang':p.closed_date?'Closed':'Uninvoiced'}}</span></template>
                <template #cell-10="{row:p}"><ActionMenu v-if="canAdd && !p.closed_date && !p.pending"><Link :href="appUrl(`/invoicing/create?id_sales_order=${p.id}`)">Add Invoicing</Link></ActionMenu></template>
            </ServerTable>
            <ServerTable v-else :url="appUrl('/invoicing/data')" label="Invoiced" :keyword="filters.q || ''" :columns="invoicedColumns" :filters="{...filters,tab}" refresh-on-visit @loaded="result=>{if(result.meta?.counts)tableCounts=result.meta.counts as typeof props.counts}">
                <template #cell-0="{number}">{{number}}</template>
                <template #cell-1="{row:p}"><span class="tag" :class="p.jenis==='b2b'?'tag-b2b':'tag-public'">{{p.jenis==='b2b'?'B2B':'Public'}}</span> <span class="code">{{p.nomor_program}}</span><small>{{p.tgl_mulai}} s.d. {{p.tgl_selesai}}</small></template>
                <template #cell-2="{row:p}"><button type="button" class="link code" @click="open(p.id)">{{p.nomor}}</button><small>{{p.tanggal}} · {{p.nomor_so}}</small><small v-if="p.termin_ke">Termin {{p.termin_ke}}<template v-if="p.termin"> dari {{p.termin.dari}}</template></small></template>
                <template #cell-3="{row:p}"><span class="tag">{{p.tujuan==='company'?'Company':'Personal'}}</span> {{p.kepada_nama}}<small>{{p.jumlah_peserta}} peserta · {{p.sales_nama}}</small></template>
                <template #cell-4="{row:p}">{{p.perlakuan_ppn}}<small>{{p.no_faktur?'Faktur '+p.no_faktur:'Tanpa faktur'}}</small><small v-if="p.perlakuan_ppn==='WAPU'">{{p.setor_tanggal?'Setor '+p.setor_tanggal:'Bukti setor belum'}}</small></template>
                <template #cell-5="{row:p}">{{money(p.total_tagihan)}}<small v-if="p.piutang!==p.total_tagihan">Piutang {{money(p.piutang)}}</small></template>
                <template #cell-6="{row:p}"><span class="badge" :class="`st-${p.status}`">{{p.status==='batal'?'Batal':p.tanggal_bayar?'Dibayar':'Belum dibayar'}}</span><small v-if="p.tanggal_bayar">{{p.tanggal_bayar}}</small><small v-if="p.resched" class="redeal"><b>Reschedule rev{{p.resched.revisi}}</b> · {{p.resched.menunggu ? 'menunggu konfirmasi ulang' : 'ikut jadwal baru'}}</small></template>
                <template #cell-7="{row:p}"><ActionMenu><button type="button" @click="open(p.id)">Lihat Invoice</button><a :href="appUrl(`/invoicing/${p.id}/pdf`)" target="_blank" rel="noopener">Print Invoice</a><button v-if="p.canManage && !p.tanggal_bayar" type="button" @click="open(p.id,'pembayaran')">Catat Pembayaran</button><button v-if="p.canManage && p.perlakuan_ppn==='WAPU' && BigInt(p.ppn)>0n && !p.setor_id" type="button" @click="open(p.id,'bukti-setor')">Terima Bukti Setor WAPU</button><Link :href="appUrl(`/invoicing/${p.id}`)">Buka halaman detail</Link></ActionMenu></template>
            </ServerTable>
        </div>
        <ConfirmDialog v-if="opened" :busy="busy" :title="detail?.invoice.nomor || 'Invoice'" confirm-label="Tutup" @confirm="close" @cancel="close"><p v-if="loading" role="status">Memuat detail…</p><p v-else-if="error" class="error" role="alert">{{error}}</p><InvoiceDetail v-else-if="detail" :key="detail.invoice.id" v-bind="detail" :initial-action="initialAction" @busy="busy=$event" @saved="busy=false;close()" /></ConfirmDialog>
    </AppLayout>
</template>
