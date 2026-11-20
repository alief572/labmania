<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import ExpenseBudget from '../../components/ExpenseBudget.vue';
import ServerTable from "../../components/ServerTable.vue";
import MoneyInput from "../../components/MoneyInput.vue";
import ActionMenu from "../../components/ActionMenu.vue";
import {Head,Link,router,useForm} from '@inertiajs/vue3';
import {computed} from 'vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
import TutorialButton from '../../components/TutorialButton.vue';
import AppLayout from '../../layouts/AppLayout.vue';
import {rupiah as money} from '../../composables/costing';
import {progress,type OpsProgram,type Budget,type ExpenseRow} from '../../types/operations';
import { useConfirmedMutation } from '../../composables/confirmedMutation';
const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;
const props=defineProps<{program:OpsProgram;category:string;budget:Budget;budgets:Record<string,Budget>;headers:Record<string,ExpenseRow[]>;canAdd:boolean;notTeam:boolean;lastAccount:{bank:string;no_rekening:string;nama_rekening:string}|null;creating:string|null;requestBy:string;today:string}>();
const over=props.creating==='overbudget';
const form=useForm({tipe_biaya:props.category,jenis:'kasbon',deskripsi:'',alasan:'',bank:props.lastAccount?.bank || '',no_rekening:props.lastAccount?.no_rekening || '',nama_rekening:props.lastAccount?.nama_rekening || '',bukti:[] as File[],items:props.budget.items.filter(i=>!over || i.id_penjualan_biaya).map(i=>({key:i.key,item:i.item,qty:'0',harga:i.harga}))});
const total=computed(()=>form.items.reduce((n,i)=>n+Math.round(Number(i.qty)*Number(i.harga)*100)/100,0));
const submit=()=>confirmation.form(form,'post',`/pengajuan-biaya/program/${props.program.id}/${props.creating}`,{title:over?'Ajukan Overbudget?':'Ajukan biaya program?',text:`${props.program.nomor}: ${money(total.value)}. Pengajuan dikirim untuk approval.`,confirmLabel:'Ya, ajukan'},{forceFormData:true});
const exceeded=(i:{key:string;qty:string;harga:string})=>Number(i.qty)*Number(i.harga)>Number(props.budget.items.find(b=>b.key===i.key)?.sisa || 0);
</script>
<template>
    <Head :title="program.nomor" /><AppLayout title="Pengajuan Biaya" full-width>
        <div v-if="creating" class="pf-formbar"><Link class="btn btn-ghost sm" :href="appUrl(`/pengajuan-biaya/program/${program.id}`)">← Kembali</Link><h1>{{over?'Add Overbudget':'Add Pengajuan'}} — {{category==='fixed'?'Fixed Cost':'Variable Cost'}}</h1><TutorialButton /></div>
        <div v-else class="pf-formbar"><Link :href="appUrl('/pengajuan-biaya')">← Kembali</Link><h1>Pengajuan {{ program.nomor }}</h1><TutorialButton /></div>
        <section class="panel pb-head">
            <dl class="pb-kvs">
                <div><dt>No. Program</dt><dd><span class="code">{{ program.nomor }}</span> <span class="tag" :class="program.jenis === 'b2b' ? 'tag-b2b' : 'tag-public'">{{ program.jenis === 'b2b' ? 'B2B' : 'Public' }}</span></dd></div>
                <div><dt>Sales (quotation)</dt><dd>{{ program.tim?.sales.join(', ') || 'Belum ada quotation' }}</dd></div>
                <div><dt>Customer</dt><dd>{{ program.jenis === 'b2b' ? program.tim?.company || '—' : 'Public' }}</dd></div>
                <div><dt>Trainer</dt><dd>{{ program.tim?.trainer || '—' }}</dd></div>
                <div><dt>Program</dt><dd>{{ program.judul }}</dd></div>
                <div><dt>Waktu</dt><dd>{{ program.tgl_mulai }} s.d. {{ program.tgl_selesai }}<template v-if="program.waktu"> · {{ program.waktu }}</template></dd></div>
                <div><dt>Lokasi</dt><dd>{{ program.lokasi || '—' }}</dd></div>
                <div><dt>Durasi · Target peserta</dt><dd>{{ program.durasi_hari }} hari · {{ program.target_peserta }} peserta</dd></div>
            </dl>
        </section>
        <p v-if="program.closed_date" class="notice">Program Closed; pengajuan baru terkunci. Pengajuan existing tetap dapat diselesaikan.</p><p v-if="notTeam && (!creating || over)" class="notice">Anda bukan tim program ini. Akses mengikuti role dan izin yang diberikan.</p>
        <template v-if="!creating">
            <section v-for="(categoryBudget, cat) in budgets" :key="cat" class="panel">
                <div class="pb-blok-h">
                    <h2>Pengajuan {{ cat === 'fixed' ? 'Fixed Cost' : 'Variable Cost' }}</h2>
                    <div class="kpis pb-kpis"><div v-for="(label, field) in {budget:'Budget',aktual:'Aktual',sisa:'Sisa Budget'}" :key="field" class="kpi" :class="{bad:field==='sisa' && Number(categoryBudget.sisa)<=0,good:field==='sisa' && Number(categoryBudget.sisa)>0}"><span class="kpi-l">{{label}}</span><strong class="kpi-v">{{money(categoryBudget[field])}}</strong></div></div>
                </div>
                <div v-if="canAdd" class="pb-btns"><Link class="btn btn-accent sm" data-tour-next="pbForm" :href="appUrl(`/pengajuan-biaya/program/${program.id}?kategori=${cat}&buat=pengajuan`)">+ Add Pengajuan</Link></div>
                <ServerTable :url="appUrl(`/pengajuan-biaya/program/${program.id}/data`)" :label="`${cat} pengajuan`" :filters="{kategori:cat,kind:'pengajuan'}" keyword="" :columns="[{title:'No',orderable:false},{title:'No. Pengajuan',orderable:true},{title:'Deskripsi',orderable:true},{title:'Total',orderable:true,className:'dt-body-right'},{title:'Jenis',orderable:true},{title:'Request By',orderable:true},{title:'Status',orderable:true},{title:'Tanggal',orderable:true},{title:'Option',orderable:false}]" refresh-on-visit>
                    <template #cell-0="{number}">{{number}}</template><template #cell-1="{row:h}"><span class="code" :class="{'is-off':['batal','ditolak'].includes(h.status)}">{{h.nomor}}</span></template><template #cell-2="{row:h}">{{h.deskripsi}}</template><template #cell-3="{row:h}">{{money(h.total)}}</template><template #cell-4="{row:h}"><span class="tag">{{h.jenis==='direct_payment'?'Direct Payment':'Kasbon'}}</span></template><template #cell-5="{row:h}">{{h.pengaju}}</template><template #cell-6="{row:h}"><span class="badge" :class="`st-${h.status}`">{{progress[h.status]}}</span></template><template #cell-7="{row:h}">{{h.tanggal}}</template><template #cell-8="{row:h}"><ActionMenu><Link :href="appUrl(`/pengajuan-biaya/pengajuan/${h.id}`)">Lihat</Link></ActionMenu></template>
                </ServerTable>
                <section class="pb-ob">
                    <div class="pb-ob-h"><h3>Overbudget {{ cat === 'fixed' ? 'Fixed Cost' : 'Variable Cost' }}</h3><Link v-if="canAdd" class="btn btn-outline sm" :href="appUrl(`/pengajuan-biaya/program/${program.id}?kategori=${cat}&buat=overbudget`)">+ Add Overbudget</Link></div>
                    <ServerTable :url="appUrl(`/pengajuan-biaya/program/${program.id}/data`)" :label="`${cat} overbudget`" :filters="{kategori:cat,kind:'overbudget'}" keyword="" :columns="[{title:'No',orderable:false},{title:'No. Overbudget',orderable:true},{title:'Item',orderable:false},{title:'Amount',orderable:true,className:'dt-body-right'},{title:'Status',orderable:true},{title:'Pembuat Pengajuan',orderable:true},{title:'Option',orderable:false}]" refresh-on-visit>
                        <template #cell-0="{number}">{{number}}</template><template #cell-1="{row:h}"><span class="code" :class="{'is-off':['batal','ditolak'].includes(h.status)}">{{h.nomor}}</span></template><template #cell-2="{row:h}">{{h.items.join(', ')}}</template><template #cell-3="{row:h}">{{money(h.total)}}</template><template #cell-4="{row:h}"><span class="badge" :class="`st-${h.status}`">{{h.status==='menunggu'?'Menunggu Approval':h.status==='disetujui'?'Disetujui':progress[h.status]}}</span></template><template #cell-5="{row:h}">{{h.pengaju}}</template><template #cell-6="{row:h}"><ActionMenu><Link :href="appUrl(`/pengajuan-biaya/overbudget/${h.id}`)">Lihat</Link></ActionMenu></template>
                    </ServerTable>
                </section>
            </section>
        </template>
        <component v-else :is="over ? ConfirmDialog : 'section'" title="Add Overbudget" :tutorial="over ? 'pbForm' : undefined" :busy="form.processing || confirming" @confirm="submit" @cancel="router.get(appUrl(`/pengajuan-biaya/program/${program.id}`))">
            <form class="stack pb-form" @submit.prevent="submit">
                <section class="panel"><h2><span class="section-number">1</span> Data Pengajuan</h2>
                    <template v-if="over"><p>Request By {{requestBy}} · Tanggal {{today}}</p><label>Alasan wajib<textarea v-model="form.alasan" maxlength="2000" required /></label></template>
                    <div v-else class="pb-data-grid">
                        <fieldset class="pb-jenis"><legend>Jenis Pengajuan *</legend><div class="pb-radios">
                            <label class="pb-radio" :class="{on:form.jenis==='kasbon'}"><input v-model="form.jenis" type="radio" name="jenis" value="kasbon" required /><span><b>Kasbon</b><small>uang muka ke rekening pengaju</small></span></label>
                            <label class="pb-radio" :class="{on:form.jenis==='direct_payment'}"><input v-model="form.jenis" type="radio" name="jenis" value="direct_payment" required @change="form.bank='';form.no_rekening='';form.nama_rekening=''" /><span><b>Direct Payment</b><small>bayar langsung ke rekening vendor / penerima</small></span></label>
                        </div></fieldset>
                        <div><span class="field-label">Kategori</span><div class="static">{{category==='fixed'?'Fixed Cost':'Variable Cost'}}</div></div>
                        <div><span class="field-label">Request By</span><div class="static">{{requestBy}}</div><small v-if="notTeam" class="pb-team-warning">Anda bukan tim program ini. Akses mengikuti role dan izin yang diberikan.</small></div>
                        <label>Tanggal<input :value="today" readonly /></label><div aria-hidden="true"></div>
                        <label class="pb-description">Deskripsi / Keterangan *<textarea v-model="form.deskripsi" maxlength="2000" rows="2" placeholder="Deskripsi / Keterangan" required /></label>
                    </div>
                </section>
                <section class="panel"><h2><span class="section-number">2</span> Informasi Pengajuan</h2><ExpenseBudget :budget="budget" /></section>
                <section class="panel"><h2><span class="section-number">3</span> Pengajuan</h2><div class="table-wrap"><table><thead><tr><th>No.</th><th>Item</th><th class="money">Sisa</th><th class="money">Qty</th><th class="money">Price / Unit</th><th class="money">Total Pengajuan</th><th v-if="over">Action</th></tr></thead><tbody>
                    <tr v-for="(i,n) in form.items" :key="n"><td>{{n+1}}</td><td><input v-if="over && !i.key" v-model="i.item" :aria-label="`Nama item baru ${n+1}`" maxlength="150" required /><template v-else>{{i.item}}</template><small v-if="!over && exceeded(i)">Melebihi sisa item; diperbolehkan selama kategori cukup.</small></td><td class="money">{{money(budget.items.find(b=>b.key===i.key)?.sisa || 0)}}</td><td><input v-model="i.qty" class="money" :aria-label="`Qty ${i.item || n+1}`" type="number" min="0" max="99999999.99" step="0.01" :disabled="!over && Number(budget.sisa)<=0" required /></td><td><MoneyInput v-model="i.harga" :aria-label="`Harga ${i.item || n+1}`" :disabled="!over && Number(budget.sisa)<=0" required /></td><td class="money">{{money(Number(i.qty)*Number(i.harga))}}</td><td v-if="over"><button type="button" @click="form.items.splice(n,1)">Hapus</button></td></tr>
                </tbody><tfoot><tr><th colspan="5">Grand Total</th><td class="money">{{money(Math.round(total))}}</td><td v-if="over" /></tr><tr v-if="!over"><th colspan="5">Sisa kategori setelah pengajuan</th><td class="money" :class="{neg:Number(budget.sisa)-total<0}">{{money(Math.round(Number(budget.sisa)-total))}}</td></tr></tfoot></table></div><button v-if="over" type="button" @click="form.items.push({key:'',item:'',qty:'1',harga:'0'})">+ Item baru</button><p class="hint">Budget dicek per kategori. Jika sisa kategori tidak cukup, ajukan Overbudget dahulu.</p></section>
                <section v-if="!over" class="panel"><h2><span class="section-number">4</span> {{form.jenis==='kasbon'?'Rekening Tujuan & Bukti':'Rekening Penerima & Bukti Tagihan'}}</h2><div class="pb-account-grid">
                    <div class="pb-account"><label>Bank *<input v-model="form.bank" maxlength="50" required /></label><label>No. Rekening *<input v-model="form.no_rekening" maxlength="30" required /></label><label>Nama Rekening *<input v-model="form.nama_rekening" maxlength="100" required /></label></div>
                    <div><span class="field-label">{{form.jenis==='direct_payment'?'Bukti Tagihan *':'Bukti Penggunaan (opsional untuk Kasbon)'}}</span><label class="pb-drop"><input type="file" multiple accept=".pdf,.jpg,.jpeg,.png" :required="form.jenis==='direct_payment'" :aria-label="form.jenis==='direct_payment'?'Unggah bukti tagihan':'Unggah bukti penggunaan'" aria-describedby="pb-file-help" @change="form.bukti=Array.from(($event.target as HTMLInputElement).files || [])" /><span><b>Tarik &amp; letakkan file di sini</b> atau klik untuk memilih file<small id="pb-file-help">PDF, JPG, PNG · maks. 5 MB per file</small></span></label><p class="hint" aria-live="polite">{{form.bukti.length?form.bukti.map(f=>f.name).join(', '):'Belum ada file'}}</p></div>
                </div></section>
                <p v-for="e in form.errors" :key="e" class="error" role="alert">{{e}}</p><div v-if="!over" class="form-actions"><Link class="btn btn-outline" :href="appUrl(`/pengajuan-biaya/program/${program.id}`)">Back</Link><button class="primary" :disabled="form.processing || confirming || Number(budget.sisa)<=0">Save</button></div>
            </form>
        </component>
    </AppLayout>
</template>

<style scoped>
.pb-head { margin-bottom: 14px; }
.pb-head > .pb-kvs { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 6px 28px; margin: 0; }
.pb-kvs > div { display: grid; grid-template-columns: 170px minmax(0, 1fr); gap: 10px; font-size: 13px; align-items: baseline; }
.pb-kvs dt, .pb-kvs dd { padding: 0; border: 0; }
.pb-kvs dt { color: var(--ink-70); font-weight: 600; }
.pb-kvs dd { margin: 0; font-weight: 600; overflow-wrap: anywhere; }
.pb-blok-h { display: grid; grid-template-columns: minmax(180px, 1fr) minmax(0, 2.2fr); gap: 16px; align-items: center; margin-bottom: 12px; }
.pb-blok-h h2, .pb-ob-h h3 { margin: 0; font-size: 15px; color: var(--navy); }
.pb-kpis { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.pb-kpis .kpi { min-width: 0; cursor: default; }
.pb-kpis .kpi:hover { transform: none; box-shadow: none; }
.pb-kpis .kpi-v { overflow-wrap: anywhere; }
.pb-btns, .pb-ob-h { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; margin-bottom: 10px; }
.pb-ob { margin-top: 18px; padding-top: 14px; border-top: 1px dashed var(--line); }
.panel > .server-table, .pb-ob > .server-table { margin-top: 0; padding: 0; border: 0; border-radius: 0; box-shadow: none; }
.pb-form { gap: 14px; margin-top: 14px; }
.pb-form > .panel { margin-top: 0; }
.pb-form > .form-actions { display: flex; align-items: center; flex-wrap: wrap; gap: 12px; }
.pb-form > .panel > h2 { display: flex; align-items: center; margin: 0 0 14px; padding: 0; border: 0; font-size: 15px; }
.pb-data-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 16px; align-items: start; }
.pb-jenis { grid-column: span 2; margin: 0; padding: 0; border: 0; }
.pb-jenis legend, .pb-form label, .field-label { font-size: 13px; font-weight: 600; }
.pb-jenis legend, .field-label { display: block; margin-bottom: 6px; }
.pb-radios { display: flex; gap: 10px; flex-wrap: wrap; }
.pb-radio { display: flex; align-items: center; gap: 8px; padding: 8px 12px; border: 1px solid var(--line); border-radius: 8px; cursor: pointer; }
.pb-radio.on { border-color: var(--navy); background: var(--navy-50); }
.pb-radio input { width: 18px; height: 18px; min-height: 18px; flex: 0 0 18px; margin: 0; padding: 0; }
.pb-radio small { margin-left: 6px; color: var(--ink-70); font-weight: 400; }
.pb-description { grid-column: 1 / -1; }
.pb-description textarea { min-height: 64px; }
.pb-team-warning { display: block; margin-top: 6px; color: var(--orange-600); }
.pb-account-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 28px; align-items: start; }
.pb-account { display: grid; gap: 12px; }
.pb-drop { position: relative; display: grid; place-items: center; min-height: 150px; padding: 16px; border: 1.5px dashed var(--navy-100); border-radius: 8px; text-align: center; cursor: pointer; }
.pb-drop:hover { border-color: var(--navy); }
.pb-drop:focus-within { outline: 3px solid #84610A; outline-offset: 3px; }
.pb-drop input { position: absolute; inset: 0; width: 100%; height: 100%; margin: 0; opacity: 0; cursor: pointer; }
.pb-drop small { display: block; margin-top: 8px; font-weight: 400; color: var(--ink-70); }
.pb-form :deep(.table-wrap) { margin-top: 0; }
.pb-form :deep(tfoot th), .pb-form :deep(tfoot td) { border-top: 2px solid var(--line); background: var(--navy-50); }
.pb-form :deep(td > input.money), .pb-form :deep(td > .money-input) { width: 120px; margin-left: auto; }
@media (max-width: 860px) { .pb-data-grid, .pb-account-grid { grid-template-columns: 1fr; } .pb-jenis { grid-column: auto; } .pb-data-grid > [aria-hidden] { display: none; } }
@media (max-width: 1100px) { .pb-blok-h, .pb-head > .pb-kvs { grid-template-columns: 1fr; } }
@media (max-width: 600px) { .pb-kvs > div { grid-template-columns: 1fr; gap: 2px; } .pb-kpis { grid-template-columns: 1fr; } }
</style>
