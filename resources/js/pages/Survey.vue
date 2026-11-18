<script setup lang="ts">
import { computed, ref } from 'vue';
import {Head,useForm,usePage} from '@inertiajs/vue3';
import type {SharedProps} from '../types';
import { useConfirmedMutation } from '../composables/confirmedMutation';
import PublicLayout from '../components/PublicLayout.vue';
import PublicHead from '../components/PublicHead.vue';
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;
const props=defineProps<{token:string;open:boolean;program:{judul:string;tgl_mulai:string;tgl_selesai:string;lokasi:string;trainer:string};participants:{id:number;nama:string;instansi:string;answered:boolean}[];questions:{id:number;bagian:string;teks:string;tipe:string;opsi:string[];wajib:boolean}[]}>();
const page=usePage<SharedProps>();
const form=useForm({id_quotation_peserta:'',jawaban:Object.fromEntries(props.questions.map(q=>[q.id,''])) as Record<string,string>});
const head=computed(()=>[`${props.program.tgl_mulai} s.d. ${props.program.tgl_selesai} · ${props.program.lokasi||'lokasi menyusul'}`,props.program.trainer?`Trainer ${props.program.trainer}`:null]);
const allDone=computed(()=>props.participants.length>0&&props.participants.every(x=>x.answered));
const sentName=ref('');
// Pertanyaan dikelompokkan per bagian (mockup .konf-sec) dengan nomor urut berlanjut antarbagian.
const sections=computed(()=>{let no=0;const out:{bagian:string;items:{q:typeof props.questions[number];no:number}[]}[]=[];for(const q of props.questions){let s=out.at(-1);if(!s||s.bagian!==q.bagian){s={bagian:q.bagian,items:[]};out.push(s);}s.items.push({q,no:++no});}return out;});
const SKALA:Record<number,string>={1:'Sangat kurang',5:'Sangat baik'};
function submit(){const who=props.participants.find(x=>String(x.id)===form.id_quotation_peserta)?.nama??'';return confirmation.form(form,'post',`/survey/${props.token}`,{title:'Kirim jawaban survey?',text:'Periksa jawaban sebelum dikirim.',confirmLabel:'Ya, kirim',successText:'Jawaban survey berhasil dikirim.'},{onSuccess:()=>{sentName.value=who;}});}
</script>
<template>
    <Head title="Survey Peserta"/>
    <PublicLayout subtitle="Survey Peserta">
        <template v-if="page.props.flash.success">
            <div class="konf-ok"><span class="konf-ok-mark" aria-hidden="true">✓</span><h1 tabindex="-1">Terima kasih{{sentName?`, ${sentName}`:''}}</h1></div>
            <PublicHead :judul="program.judul" :lines="head"/>
            <div class="pub-closed" role="status"><p><b>Jawaban survey Anda sudah kami terima.</b></p><p>Jawaban tidak dapat diubah setelah dikirim.</p></div>
        </template>
        <template v-else>
            <h1>Survey Peserta</h1>
            <PublicHead :judul="program.judul" :lines="head"/>
            <div v-if="!open" class="pub-closed" role="status"><p><b>Survey ini belum dibuka atau sudah ditutup.</b></p><p>Hubungi panitia LabMania bila Anda belum sempat mengisi.</p></div>
            <div v-else-if="allDone" class="pub-closed" role="status"><p><b>Semua peserta sudah mengisi survey ini.</b> Terima kasih.</p></div>
            <form v-else @submit.prevent="submit">
                <p class="konf-lead">Terima kasih sudah mengikuti program ini. Jawaban Anda membantu LabMania memperbaiki pelatihan berikutnya.</p>
                <section class="konf-sec"><h2>Nama Anda</h2><p class="konf-req"><span class="req" aria-hidden="true">*</span> wajib diisi</p>
                    <label>Pilih nama Anda <span class="req" aria-hidden="true">*</span><select v-model="form.id_quotation_peserta" required><option value="">Pilih nama</option><option v-for="x in participants" :key="x.id" :value="String(x.id)" :disabled="x.answered">{{x.nama}} · {{x.instansi}}{{x.answered?' (sudah mengisi)':''}}</option></select></label>
                    <p class="hint-line">Nama tidak ada di daftar? Hubungi panitia LabMania.</p>
                </section>
                <section v-for="s in sections" :key="s.bagian" class="konf-sec"><h2>{{s.bagian}}</h2>
                    <template v-for="{q,no} in s.items" :key="q.id">
                        <label v-if="q.tipe==='teks'" class="sv-q"><span class="sv-no">{{no}}.</span> {{q.teks}}<span v-if="q.wajib" class="req" aria-hidden="true"> *</span><span v-else class="muted"> (opsional)</span><textarea v-model="form.jawaban[q.id]" maxlength="500" rows="3" :required="q.wajib"/><small class="hint-line">Maksimal 500 karakter.</small></label>
                        <fieldset v-else class="sv-q"><legend><span class="sv-no">{{no}}.</span> {{q.teks}}<span v-if="q.wajib" class="req" aria-hidden="true"> *</span><span v-else class="muted"> (opsional)</span></legend>
                            <div v-if="q.tipe==='skala'" class="sv-skala"><label v-for="n in 5" :key="n" class="sv-opt"><input v-model="form.jawaban[q.id]" type="radio" :name="`q${q.id}`" :value="String(n)" :required="q.wajib"/><span class="sv-opt-l"><b>{{n}}</b><span>{{SKALA[n]||' '}}</span></span></label></div>
                            <div v-else class="sv-pilih"><label v-for="o in q.opsi" :key="o" class="sv-opt"><input v-model="form.jawaban[q.id]" type="radio" :name="`q${q.id}`" :value="o" :required="q.wajib"/><span class="sv-opt-l">{{o}}</span></label></div>
                        </fieldset>
                    </template>
                </section>
                <p v-for="e in form.errors" :key="e" class="error" role="alert">{{e}}</p>
                <button class="primary konf-kirim" :disabled="form.processing || confirming">Kirim survey</button>
                <p class="hint-line">Jawaban hanya dapat dikirim sekali dan tidak dapat diubah.</p>
            </form>
        </template>
    </PublicLayout>
</template>
