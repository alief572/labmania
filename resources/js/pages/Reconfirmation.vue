<script setup lang="ts">
import { appUrl } from '../composables/appUrl';
import { computed, ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { postPdf } from '../composables/pdf';
import type { SharedProps } from '../types';
import { useConfirmedMutation } from '../composables/confirmedMutation';
import PublicLayout from '../components/PublicLayout.vue';
import PublicHead from '../components/PublicHead.vue';
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;
const props=defineProps<{token:string;revisi:number;nomor:string;judul:string;jenis:string;oldDate:string|null;newDate:string;endDate:string;deadline:string;people:{id:number;nama:string;keputusan:string|null;answered:boolean;paid:boolean}[];closed:boolean;canRegister:boolean}>();
const page=usePage<SharedProps>();
const form=useForm({revisi:props.revisi,id_peserta:'' as number|'',peserta:props.people.map(x=>x.id),keputusan:'pindah_jadwal',catatan:'',surat:null as File|null,setuju:false});
const person=computed(()=>props.people.find(x=>x.id===Number(form.id_peserta)));
const b2b=props.jenis==='b2b';const paid=computed(()=>b2b?props.people.some(x=>x.paid):!!person.value?.paid);
const answered=computed(()=>b2b?props.people.some(x=>x.answered):!!person.value?.answered);
const busy=ref(false);const error=ref('');
const head=computed(()=>[`Program ${props.nomor}`,`Jadwal baru ${props.newDate} s.d. ${props.endDate}`]);
// Pilihan jawaban menurut status bayar (mockup opsiHtml): sudah bayar = Pindah jadwal atau Refund; belum = Pindah jadwal atau Batal ikut.
const opsi=computed(()=>[{v:'pindah_jadwal',t:'Pindah jadwal',d:'ikut program ini di jadwal baru'},paid.value?{v:'refund',t:'Refund',d:'tidak ikut; pembayaran Anda dikembalikan sesuai syarat & ketentuan'}:{v:'batal',t:'Batal ikut',d:'tidak ikut program ini; tagihan dibatalkan'}]);
const done=ref(false);
async function preview(){busy.value=true;error.value='';try{await postPdf(`/konfirmasi/${props.token}/ulang/preview`,form.data());}catch(e){error.value=(e as Error).message;}finally{busy.value=false;}}
function submit(){return confirmation.form(form,'post',`/konfirmasi/${props.token}/ulang`,{title:'Kirim keputusan konfirmasi ulang?',text:`Program ${props.nomor}. Periksa pilihan keikutsertaan dan daftar peserta sebelum mengirim.`,confirmLabel:'Ya, kirim',danger:form.keputusan!=='pindah_jadwal',successText:'Keputusan konfirmasi ulang berhasil dikirim.'},{forceFormData:true,preserveState:'errors',onSuccess:()=>{done.value=true;}});}
</script>
<template><Head title="Konfirmasi Ulang Jadwal"/>
<PublicLayout subtitle="Konfirmasi Kehadiran Peserta">
    <template v-if="done&&page.props.flash.success">
        <div class="konf-ok"><span class="konf-ok-mark" aria-hidden="true">✓</span><h1 tabindex="-1">Terima kasih{{person?`, ${person.nama}`:''}}</h1></div>
        <PublicHead :judul="judul" :lines="head"/>
        <div class="pub-closed" role="status"><p><b>{{page.props.flash.success}}</b></p></div>
        <p v-if="!b2b&&people.some(x=>!x.answered&&x.id!==person?.id)" class="konf-lagi"><button type="button" class="btn btn-outline" @click="done=false;form.id_peserta=''">Konfirmasi untuk peserta lain</button></p>
    </template>
    <template v-else>
        <h1>Konfirmasi ulang jadwal baru</h1>
        <PublicHead :judul="judul" :lines="head"/>
        <section class="konf-ubah"><p><b>Jadwal berubah.</b> Jadwal lama {{oldDate}}. Jadwal baru {{newDate}} s.d. {{endDate}}.</p><p>Konfirmasi ulang ditutup {{deadline}}. Belum menjawab akan diproses Sales sebagai tidak ikut sesuai status pembayaran.</p></section>
        <p v-if="page.props.flash.success" class="notice" role="status">{{page.props.flash.success}}</p>
        <div v-if="closed" class="pub-closed" role="status"><p><b>Konfirmasi ulang sudah ditutup.</b></p><p>Hubungi Sales LabMania.</p></div>
        <form v-else @submit.prevent="submit">
            <section v-if="!b2b" class="konf-sec"><h2>1. Nama Anda</h2><label>Pilih nama Anda <span class="req" aria-hidden="true">*</span><select v-model="form.id_peserta" required @change="form.keputusan='pindah_jadwal'"><option value="">Pilih nama</option><option v-for="p in people" :key="p.id" :value="p.id">{{p.nama}}{{p.answered?' — sudah menjawab':''}}</option></select></label></section>
            <p v-if="answered" class="pub-closed">Jawaban sudah diterima. Perubahan hanya melalui Sales.</p>
            <template v-else>
                <section class="konf-sec"><h2>{{b2b?'1':'2'}}. Jawaban Anda</h2>
                    <fieldset class="konf-opsi"><legend class="sr-only">Keputusan</legend><label v-for="o in opsi" :key="o.v" class="chk"><input v-model="form.keputusan" type="radio" name="keputusan" :value="o.v"/> <span><b>{{o.t}}</b> <span class="muted">{{o.d}}</span></span></label></fieldset>
                    <template v-if="form.keputusan==='pindah_jadwal'">
                        <fieldset v-if="b2b"><legend>Peserta yang tetap ikut, minimal 10 nama terdaftar</legend><label v-for="p in people" :key="p.id" class="chk"><input v-model="form.peserta" type="checkbox" :value="p.id"/> {{p.nama}}</label></fieldset>
                        <ol class="konf-langkah"><li>Cetak surat konfirmasi bertanggal baru. Data dari pendaftaran awal sudah tercetak.<div><button type="button" class="btn btn-outline konf-cetak-btn" :disabled="busy || (!b2b && !person)" @click="preview">{{busy?'Menyiapkan PDF…':'Unduh surat konfirmasi jadwal baru'}}</button></div></li><li>Tanda tangani dengan pena, foto atau pindai, lalu unggah.</li></ol>
                        <label>Surat ditandatangani <span class="req" aria-hidden="true">*</span><input class="konf-file" type="file" accept=".pdf,.jpg,.jpeg,.png" required @change="form.surat=($event.target as HTMLInputElement).files?.[0] || null"/></label><p class="hint-line">PDF, JPG, atau PNG, maksimal 5 MB.</p>
                    </template>
                    <label>Catatan untuk Sales <span class="muted">(opsional)</span><textarea v-model="form.catatan" maxlength="500" rows="2"/></label>
                    <p v-if="form.keputusan!=='pindah_jadwal'" class="hint-line">Jawaban tidak ikut akan diproses Sales. Refund diselesaikan Finance sesuai S&amp;K.</p>
                </section>
                <section class="konf-sec"><h2>{{b2b?'2':'3'}}. Pernyataan</h2><label class="chk konf-setuju"><input v-model="form.setuju" type="checkbox" required/> Saya menyetujui jawaban untuk revisi jadwal ini.</label></section>
                <p v-for="e in form.errors" :key="e" class="error" role="alert">{{e}}</p><p v-if="error" class="error" role="alert">{{error}}</p>
                <button class="primary konf-kirim" :disabled="form.processing || confirming || (!b2b && !person)">Kirim jawaban</button>
            </template>
        </form>
        <p v-if="canRegister" class="konf-lagi"><a :href="appUrl(`/konfirmasi/${token}?daftar_baru=1`)">Pendaftaran peserta baru</a> mengikuti batas H-14, masa berlaku, dan kuota.</p>
    </template>
</PublicLayout></template>
