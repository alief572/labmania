<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import { computed } from 'vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import FormField from '../../components/FormField.vue';
import { useConfirmedMutation } from '../../composables/confirmedMutation';
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;
type Program = { id:number; nomor:string; revisi:number; tgl_mulai:string; tgl_selesai:string; id_trainer:number; id_asisten:number; durasi_hari:number };
type History = { team: {trainer_lama:string|null;trainer_baru:string|null;asisten_lama:string|null;asisten_baru:string|null}|null; id:number; tgl_mulai_lama:string; tgl_selesai_lama:string; tgl_mulai_baru:string; tgl_selesai_baru:string; id_trainer_lama:number; id_trainer_baru:number; id_asisten_lama:number; id_asisten_baru:number; alasan:string; created_date:string; nm_lengkap:string; surat_lama:number|null; surat_baru:number|null; evidence:{id:number;nama_file:string}[]; revisions:{id:number;id_quotation:number;revisi:number}[] };
const props=defineProps<{program:Program;history:History[];version:string;requestId:string;reason:string|null;canManage:boolean;minimum:string;trainers:{id:number;nama:string}[];assistants:{id:number;nama:string}[];impact:{quotation:number;peserta:number;so:number;surat:number;lunas:number}}>();
const form=useForm({request_id:props.requestId,version:props.version,tgl_mulai:props.program.tgl_mulai,id_trainer:props.program.id_trainer,id_asisten:props.program.id_asisten,alasan:'',evidence:[] as File[]});
const history=props.history;
const teamChanged=computed(()=>form.id_trainer!==props.program.id_trainer||form.id_asisten!==props.program.id_asisten);
const dateChanged=computed(()=>form.tgl_mulai!==props.program.tgl_mulai);
const fmt=(d?:string|null)=>(d?new Date(`${d.slice(0,10)}T00:00:00`).toLocaleDateString('id-ID',{day:'numeric',month:'short',year:'numeric'}):'—');
const name=(list:{id:number;nama:string}[],id:number)=>list.find((x)=>x.id===id)?.nama||`ID ${id}`;
const sisa=computed(()=>history.length);
const endDate=computed(()=>{const d=new Date(form.tgl_mulai+'T00:00:00Z');if(Number.isNaN(d.getTime()))return '';d.setUTCDate(d.getUTCDate()+props.program.durasi_hari-1);return d.toISOString().slice(0,10);});
function save(){return confirmation.form(form,'post',`/penjualan-program/${props.program.id}/reschedule`,{title:`Reschedule ${props.program.nomor}?`,text:`Jadwal ${props.program.tgl_mulai} menjadi ${form.tgl_mulai} s.d. ${endDate.value}. Terdampak: ${props.impact.quotation} Quotation, ${props.impact.peserta} peserta dan ${props.impact.so} Sales Order. Perubahan mengikuti proses konfirmasi ulang.`,confirmLabel:'Ya, reschedule',danger:true},{forceFormData:true,preserveState:'errors'});}
</script>
<template>
<Head title="Reschedule dan Riwayat"/><AppLayout title="Reschedule"><div class="pf-formbar"><Link class="kt-btn" :href="appUrl(`/penjualan-program/${program.id}`)">← Kembali</Link><h1>Reschedule <span class="code">{{program.nomor}}<template v-if="program.revisi">-rev{{program.revisi}}</template></span></h1></div>
<p v-if="reason" class="notice" role="status">{{reason}}</p>
<form v-if="canManage && !reason" class="panel stack" @submit.prevent="save">
<p class="hint-line top">Yang dapat diganti hanya <b>tanggal</b>, <b>trainer</b>, dan <b>asisten trainer</b>. Biaya, waktu, dan lokasi tetap. Durasi tetap {{program.durasi_hari}} hari, jadi tanggal selesai mengikuti tanggal mulai.</p>
<dl class="summary"><div><dt>Jadwal sekarang</dt><dd>{{fmt(program.tgl_mulai)}} – {{fmt(program.tgl_selesai)}}<small>Trainer: {{name(trainers,program.id_trainer)}} · Asisten: {{name(assistants,program.id_asisten)}}</small></dd></div><div><dt>Sudah di-reschedule</dt><dd>{{sisa}} kali</dd></div></dl>
<div class="grid2">
<FormField id="rs-mulai" label="Tanggal mulai baru" :hint="`paling cepat ${fmt(minimum)}`" :error="form.errors.tgl_mulai" v-slot="{ describedBy, invalid }"><input id="rs-mulai" v-model="form.tgl_mulai" type="date" :min="minimum" required :aria-invalid="invalid" :aria-describedby="describedBy"/></FormField>
<div class="field"><span class="static-label">Tanggal selesai</span><output class="static" for="rs-mulai">{{fmt(endDate)}}</output><small>otomatis: mulai + {{program.durasi_hari-1}} hari</small></div>
<label>Trainer<select v-model="form.id_trainer" required><option v-for="x in trainers" :key="x.id" :value="x.id">{{x.nama}}</option></select></label>
<label>Asisten trainer<select v-model="form.id_asisten" required><option v-for="x in assistants" :key="x.id" :value="x.id">{{x.nama}}</option></select></label>
<FormField class="span2" id="rs-alasan" label="Alasan reschedule" :error="form.errors.alasan" v-slot="{ describedBy, invalid }"><textarea id="rs-alasan" v-model="form.alasan" rows="3" maxlength="500" required placeholder="mis. Kuota peserta belum terpenuhi" :aria-invalid="invalid" :aria-describedby="describedBy"/></FormField>
<div class="field span2"><span class="static-label">Evidence <span class="req" aria-hidden="true">*</span></span><label class="file-btn">Pilih file evidence<input type="file" multiple accept=".jpg,.jpeg,.png,.pdf,.doc,.docx" hidden required @change="form.evidence=Array.from(($event.target as HTMLInputElement).files || [])"/></label><small>JPG, PNG, DOC/DOCX, PDF · maks. 5 MB per file · boleh lebih dari satu</small><small aria-live="polite">{{form.evidence.length?form.evidence.map((f)=>f.name).join(', '):'Belum ada file'}}</small></div>
</div>
<section class="notice" aria-live="polite"><b>Yang ikut berubah bila disimpan:</b><ul class="rs-dampak"><template v-if="dateChanged"><li>Nomor program menjadi {{program.nomor}}-rev{{program.revisi+1}}</li><li>{{impact.quotation}} quotation naik revisi dan mendapat link konfirmasi baru</li><li>{{impact.peserta}} peserta harus konfirmasi ulang dengan surat bertanggal baru</li><li>{{impact.so}} Sales Order naik revisi</li><li v-if="impact.lunas">{{impact.lunas}} peserta Deal sudah membayar: boleh memilih Refund</li></template><li v-if="dateChanged||teamChanged">{{impact.surat}} Surat Tugas diganti surat baru dengan nomor baru</li><li v-if="!dateChanged&&!teamChanged" class="muted">Belum ada perubahan.</li></ul><span class="muted">Invoice tidak diubah karena biaya tetap.</span></section>
<p v-for="e in form.errors" :key="e" class="error" role="alert">{{e}}</p><progress v-if="form.progress" :value="form.progress.percentage" max="100" aria-label="Progres evidence"/>
<div class="form-actions page-actions"><Link :href="appUrl(`/penjualan-program/${program.id}`)">Batal</Link><button class="primary" :disabled="form.processing||confirming">Simpan jadwal baru</button></div></form>
<section class="panel"><h2>Riwayat Reschedule <span class="muted">{{history.length}} kali; jadwal lama, alasan, dan dampaknya</span></h2><p v-if="!history.length" class="muted">Belum ada Reschedule.</p>
<div v-else class="table-wrap"><table class="tr-table"><caption class="sr-only">Riwayat Reschedule</caption><thead><tr><th>Waktu</th><th>Oleh</th><th>Jadwal lama</th><th>Jadwal baru</th><th>Alasan</th><th>Evidence</th><th>Dampak</th></tr></thead><tbody>
<tr v-for="h in history" :key="h.id"><td>{{h.created_date}}</td><td>{{h.nm_lengkap}}</td>
<td>{{fmt(h.tgl_mulai_lama)}} – {{fmt(h.tgl_selesai_lama)}}<small>{{h.team?.trainer_lama||`Trainer ID ${h.id_trainer_lama}`}} · {{h.team?.asisten_lama||`Asisten ID ${h.id_asisten_lama}`}}</small></td>
<td>{{fmt(h.tgl_mulai_baru)}} – {{fmt(h.tgl_selesai_baru)}}<small>{{h.team?.trainer_baru||`Trainer ID ${h.id_trainer_baru}`}} · {{h.team?.asisten_baru||`Asisten ID ${h.id_asisten_baru}`}}</small></td>
<td>{{h.alasan}}</td>
<td><template v-if="h.evidence.length"><a v-for="f in h.evidence" :key="f.id" class="code" :href="appUrl(`/documents/reschedule/${f.id}`)">{{f.nama_file}}</a></template><span v-else class="muted">—</span></td>
<td><small v-for="q in h.revisions" :key="q.id">Quotation #{{q.id_quotation}} versi {{q.revisi}} tersimpan</small><small v-if="h.surat_baru">Surat Tugas #{{h.surat_lama}} diganti #{{h.surat_baru}}</small><a :href="appUrl(`/penjualan-program/${program.id}/reschedule/${h.id}/pdf`)" target="_blank" rel="noopener">PDF program sebelum perubahan</a></td></tr>
</tbody></table></div></section>
</AppLayout>
</template>
