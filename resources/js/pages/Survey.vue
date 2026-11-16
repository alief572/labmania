<script setup lang="ts">
import {Head,useForm,usePage} from '@inertiajs/vue3';
import type {SharedProps} from '../types';
import { useConfirmedMutation } from '../composables/confirmedMutation';
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;
const props=defineProps<{token:string;open:boolean;program:{judul:string;tgl_mulai:string;tgl_selesai:string;lokasi:string;trainer:string};participants:{id:number;nama:string;instansi:string;answered:boolean}[];questions:{id:number;bagian:string;teks:string;tipe:string;opsi:string[];wajib:boolean}[]}>();
const page=usePage<SharedProps>();
const form=useForm({id_quotation_peserta:'',jawaban:Object.fromEntries(props.questions.map(q=>[q.id,''])) as Record<string,string>});
function submit(){return confirmation.form(form,'post',`/survey/${props.token}`,{title:'Kirim jawaban survey?',text:'Periksa jawaban sebelum dikirim.',confirmLabel:'Ya, kirim',successText:'Jawaban survey berhasil dikirim.'});}
</script>
<template>
    <Head title="Survey Peserta"/><main class="confirmation-page"><section class="panel stack">
        <h1>Survey Peserta</h1><h2>{{program.judul}}</h2><p>{{program.tgl_mulai}} s.d. {{program.tgl_selesai}} · {{program.lokasi}} · Trainer {{program.trainer}}</p>
        <p v-if="page.props.flash.success" role="status">{{page.props.flash.success}}</p><p v-else-if="!open">Survey ini sudah ditutup.</p>
        <form v-else class="stack" @submit.prevent="submit">
            <label>Pilih nama Anda<select v-model="form.id_quotation_peserta" required><option value="">Pilih peserta</option><option v-for="x in participants" :key="x.id" :value="String(x.id)" :disabled="x.answered">{{x.nama}} · {{x.instansi}}{{x.answered?' (sudah mengisi)':''}}</option></select></label>
            <fieldset v-for="q in questions" :key="q.id"><legend>{{q.bagian}} · {{q.teks}}{{q.wajib?' *':''}}</legend>
                <template v-if="q.tipe==='skala'"><label v-for="n in 5" :key="n"><input v-model="form.jawaban[q.id]" type="radio" :name="`q${q.id}`" :value="String(n)" :required="q.wajib"/>{{n}}{{n===1?' Sangat kurang':n===5?' Sangat baik':''}}</label></template>
                <label v-else-if="q.tipe==='pilihan'">Jawaban<select v-model="form.jawaban[q.id]" :required="q.wajib"><option value="">Pilih jawaban</option><option v-for="o in q.opsi" :key="o">{{o}}</option></select></label>
                <label v-else>Jawaban<textarea v-model="form.jawaban[q.id]" maxlength="500" :required="q.wajib"/></label>
            </fieldset>
            <p v-for="e in form.errors" :key="e" class="error" role="alert">{{e}}</p><button class="primary" :disabled="form.processing || confirming">Kirim survey</button><p>Jawaban hanya dapat dikirim sekali dan tidak dapat diubah.</p>
        </form>
    </section></main>
</template>
