<script setup lang="ts">
import { appUrl } from '../composables/appUrl';
import { watch } from 'vue';
import { useForm } from '@inertiajs/vue3';
import { money } from '../types/salesOrder';
import MoneyInput from './MoneyInput.vue';
import { useConfirmedMutation } from '../composables/confirmedMutation';
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;
const emit=defineEmits<{saved:[];busy:[value:boolean]}>();
const props=defineProps<{refund:{id:number;nomor:string;alasan:string;status:string;nominal_tagihan:string;nominal_refund:string|null;bank:string|null;no_rekening:string|null;atas_nama:string|null;catatan:string|null;bukti_transfer_nama:string|null;selesai_date:string|null};snapshot:{peserta:string|null;company:string|null;invoice:string;nomor_so:string;nomor_quotation:string};canManage:boolean;closed:boolean;process?:boolean;returnToList?:boolean}>();
const form=useForm({nominal_refund:props.refund.nominal_tagihan.replace(/\.0+$/, ''),bank:'',no_rekening:'',atas_nama:'',catatan:'',bukti:null as File|null});
watch(()=>form.processing || confirming.value,value=>emit('busy',value),{immediate:true});
const save=()=>confirmation.form(form,'post',`/sales-order/refund/${props.refund.id}${props.returnToList?'?modal=1':''}`,{title:`Selesaikan Refund ${props.refund.nomor}?`,text:`Nominal Rp ${form.nominal_refund.replace(/\B(?=(\d{3})+(?!\d))/g, '.')}. Transfer dilakukan di luar aplikasi; penyelesaian tidak dapat diedit.`,confirmLabel:'Ya, simpan penyelesaian',danger:true},{forceFormData:true,preserveState:'errors',onSuccess:()=>emit('saved')});
</script>
<template><div class="refund-detail">
        <section class="panel"><h2>{{snapshot.peserta || snapshot.company}}</h2><p>{{snapshot.nomor_so}} · {{snapshot.nomor_quotation}} · Invoice {{snapshot.invoice}}</p><p>{{refund.alasan}}</p><p>Batas dana lunas teralokasi: {{money(refund.nominal_tagihan)}}.</p><p>Status {{refund.status==='menunggu'?'Menunggu Finance':'Selesai'}} {{refund.selesai_date}}</p>
            <template v-if="refund.status==='selesai'"><p>Transfer {{money(refund.nominal_refund || '0')}} melalui {{refund.bank}}, {{refund.no_rekening}}, a.n. {{refund.atas_nama}}.</p><p>{{refund.catatan}}</p><a :href="appUrl(`/documents/refund/${refund.id}`)">Unduh {{refund.bukti_transfer_nama}}</a></template>
        </section>
        <p v-if="closed" class="notice">Program Closed; perubahan terkunci.</p>
        <form v-if="process !== false && refund.status==='menunggu' && canManage && !closed" class="panel stack" @submit.prevent="save">
            <h2>Catat transfer Refund</h2><p>Transfer dilakukan Finance di luar aplikasi. Simpan setelah transfer; penyelesaian tidak dapat diedit.</p>
            <label>Nominal refund<MoneyInput v-model="form.nominal_refund" :max-digits="18" required/></label>
            <div class="form-grid"><label>Bank<input v-model="form.bank" maxlength="50" required/></label><label>Nomor rekening<input v-model="form.no_rekening" maxlength="30" required/></label><label>Atas nama<input v-model="form.atas_nama" maxlength="100" required/></label></div>
            <label>Keterangan<textarea v-model="form.catatan" maxlength="500"/><small>Wajib diisi bila nominal refund kurang dari batas dana.</small></label><label>Bukti transfer PDF/JPG/PNG maksimal 5 MB<input type="file" accept=".pdf,.jpg,.jpeg,.png" required @change="form.bukti=($event.target as HTMLInputElement).files?.[0] || null"/></label>
            <p v-for="e in form.errors" :key="e" class="error" role="alert">{{e}}</p><button class="primary" :disabled="form.processing || confirming">Simpan penyelesaian</button>
        </form>
</div></template>
