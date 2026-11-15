<script setup lang="ts">
import ActionMenu from "../../components/ActionMenu.vue";
import ServerTable from "../../components/ServerTable.vue";
import {Head,Link,router,useForm} from '@inertiajs/vue3';
import {reactive,ref,shallowRef} from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import type {PageRows} from '../../types/salesOrder';
import type {OpsParticipant} from '../../types/operations';
import { useConfirmedMutation } from '../../composables/confirmedMutation';
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;
const props=defineProps<{rows:PageRows<OpsParticipant>;filters:Record<string,string>;counts:Record<string,number>;canManage:boolean;canCertificates:boolean;canQuotation:boolean}>();
const tableCounts = shallowRef(props.counts);
const f=reactive({q:'',tipe:'',tahun:'',bulan:'',tindak:'',...props.filters});
const followups:Record<string,string>={uninvoiced:'Belum dibuatkan invoice',pelatihan:'Sertifikat pelatihan belum dikirim',bnsp:'BNSP belum diterima',expired:'Sertifikat habis ≤90 hari'};
const editing=ref<OpsParticipant|null>(null),form=useForm({catatan_monitoring:''});
function save(){if(editing.value)return confirmation.form(form,'post',`/manage-peserta/peserta/${editing.value.id}/catatan`,{title:`Simpan catatan ${editing.value.nama}?`},{onSuccess:()=>editing.value=null});}
const search=()=>router.get('/manage-peserta/monitoring',f);
const exportUrl=()=>'/manage-peserta/monitoring?'+new URLSearchParams({...f,export:'1'}).toString();
</script>
<template>
    <Head title="Monitoring Peserta"/><AppLayout title="Monitoring Peserta" full-width>
        <div class="page-head"><h1>Monitoring Peserta</h1><a :href="exportUrl()">Export Excel</a></div>
        <div class="status-summary"><button v-for="(label,key) in followups" :key="key" :aria-pressed="f.tindak===key" @click="f.tindak=key;search()">{{label}} ({{tableCounts[key]}})</button></div>
        <form class="filters" @submit.prevent="search"><label>Tipe<select v-model="f.tipe"><option value="">Semua</option><option>Pelatihan</option><option>Sertifikasi</option><option>Training &amp; Sertifikasi</option></select></label><label>Tahun<input v-model="f.tahun" type="number" min="1900" max="2200"/></label><label>Bulan<select v-model="f.bulan"><option value="">Semua</option><option v-for="n in 12" :key="n" :value="String(n)">{{n}}</option></select></label><label>Tindak lanjut<select v-model="f.tindak"><option value="">Semua</option><option v-for="(label,key) in followups" :key="key" :value="key">{{label}}</option></select></label><button>Terapkan</button></form>
        <p>Pelatihan {{tableCounts.Pelatihan}} · Sertifikasi {{tableCounts.Sertifikasi}} · Training &amp; Sertifikasi {{tableCounts['Training & Sertifikasi']}}</p>
        <ServerTable @loaded="result=>{if(result.meta?.counts)tableCounts=result.meta.counts as typeof props.counts}" url="/manage-peserta/monitoring/data" label="Monitoring Peserta" :columns='[{"title":"No","orderable":false},{"title":"Peserta","orderable":true},{"title":"Program","orderable":true},{"title":"Invoice","orderable":false},{"title":"Sertifikat Pelatihan","orderable":true},{"title":"Sertifikasi LSP / BNSP","orderable":true},{"title":"Berlaku sampai","orderable":true},{"title":"Catatan","orderable":true},{"title":"Action","orderable":false}]' :filters="{ ...filters }" refresh-on-visit>
            <template #cell-0="{ number }">{{ number }}</template>
            <template #cell-1="{ row: p }">{{ p.nama }}<small>{{ p.instansi }} · {{ p.jabatan }}</small><small>{{ p.no_wa }} · {{ p.email }}</small></template>
            <template #cell-2="{ row: p }">{{ p.nomor_program }}<small>{{ p.judul }} · {{ p.tipe }}</small><small>{{ p.tgl_mulai }} · {{ p.lokasi }} · PIC {{ p.sales }}</small></template>
            <template #cell-3="{ row: p }">{{ p.invoice || 'Uninvoiced' }}<small>{{ p.pembayaran }}</small></template>
            <template #cell-4="{ row: p }">{{ p.no_sertifikat || 'Belum terbit' }}<small>{{ p.status_kirim || 'Belum dikirim' }}</small></template>
            <template #cell-5="{ row: p }">{{ p.no_sertifikat_bnsp || '—' }}<small>{{ p.sertifikat_lsp || '—' }} · {{ p.hasil || 'Belum diuji' }}</small></template>
            <template #cell-6="{ row: p }">{{ p.sertifikat_berlaku || '—' }}</template>
            <template #cell-7="{ row: p }">{{ p.catatan_monitoring || '—' }}</template>
            <template #cell-8="{ row: p }"><ActionMenu><Link v-if="canQuotation" :href="`/quotation/${p.id_quotation}`">Lihat Quotation</Link><Link v-if="canCertificates" :href="`/manage-peserta/${p.id_penjualan}?tab=sertifikat`">Kelola Sertifikat</Link><button v-if="canManage" type="button" :disabled="confirming" @click="editing=p;form.catatan_monitoring=p.catatan_monitoring || '';form.clearErrors()">Catatan</button></ActionMenu></template>
        </ServerTable>
        <ConfirmDialog v-if="editing" title="Catatan monitoring" :busy="form.processing || confirming" @cancel="editing=null" @confirm="save"><label>Catatan {{editing.nama}}<textarea v-model="form.catatan_monitoring" maxlength="500"/></label><p v-for="e in form.errors" :key="e" class="error" role="alert">{{e}}</p></ConfirmDialog>
    </AppLayout>
</template>
