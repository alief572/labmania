<script setup lang="ts">
import PageHead from '../../components/PageHead.vue';
import ExpenseBudget from '../../components/ExpenseBudget.vue';
import ServerTable from "../../components/ServerTable.vue";
import MoneyInput from "../../components/MoneyInput.vue";
import ActionMenu from "../../components/ActionMenu.vue";
import {Head,Link,router,useForm} from '@inertiajs/vue3';
import {computed} from 'vue';
import ConfirmDialog from '../../components/ConfirmDialog.vue';
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
        <PageHead :title="creating ? `${over ? 'Add Overbudget' : 'Add Pengajuan'} — ${category === 'fixed' ? 'Fixed Cost' : 'Variable Cost'}` : `Pengajuan Biaya — ${program.nomor}`" :description="program.judul"><Link :href="creating ? `/pengajuan-biaya/program/${program.id}` : '/pengajuan-biaya'">← Kembali</Link></PageHead>
        <section class="panel"><span class="tag" :class="program.jenis === 'b2b' ? 'tag-b2b' : 'tag-public'">{{ program.jenis === 'b2b' ? 'B2B' : 'Public' }}</span> <span class="code">{{ program.nomor }}</span><p>{{ program.tim?.company }} · {{ program.tgl_mulai }} s.d. {{ program.tgl_selesai }} · {{ program.durasi_hari }} hari · {{ program.lokasi }}</p><p>Sales {{ program.tim?.sales.join(', ') }} · Trainer {{ program.tim?.trainer }} · Target {{ program.target_peserta }} peserta.</p><p v-if="program.closed_date" class="notice">Program Closed; pengajuan baru terkunci. Pengajuan existing tetap dapat diselesaikan.</p><p v-if="notTeam" class="notice">Anda bukan tim program ini. Akses mengikuti role dan izin yang diberikan.</p></section>
        <template v-if="!creating">
            <section v-for="(categoryBudget, cat) in budgets" :key="cat" class="panel">
                <PageHead :title="cat === 'fixed' ? 'Fixed Cost' : 'Variable Cost'"><Link v-if="canAdd" :href="`/pengajuan-biaya/program/${program.id}?kategori=${cat}&buat=pengajuan`">+ Add Pengajuan</Link></PageHead>
                <ExpenseBudget :budget="categoryBudget" />
                <section v-for="kind in ['pengajuan', 'overbudget']" :key="kind">
                    <div class="page-head"><h3>{{ kind === 'pengajuan' ? 'Pengajuan' : 'Overbudget' }}</h3><Link v-if="canAdd && kind === 'overbudget'" :href="`/pengajuan-biaya/program/${program.id}?kategori=${cat}&buat=overbudget`">+ Add Overbudget</Link></div>
                    <ServerTable :url="`/pengajuan-biaya/program/${program.id}/data`" :label="`${cat} ${kind}`" :filters="{kategori:cat,kind}" :columns="[{title:'No',orderable:false},{title:kind==='pengajuan'?'No. Pengajuan':'No. Overbudget',orderable:true},{title:'Jenis',orderable:kind==='pengajuan'},{title:'Tanggal',orderable:true},{title:'Deskripsi / Item',orderable:true},{title:'Total',orderable:true,className:'dt-body-right'},{title:'Request By',orderable:true},{title:'Progres',orderable:true},{title:'Action',orderable:false}]" refresh-on-visit>
                        <template #cell-0="{number}">{{number}}</template><template #cell-1="{row:h}"><span class="code" :class="{'is-off':['batal','ditolak'].includes(h.status)}">{{h.nomor}}</span></template><template #cell-2="{row:h}">{{h.jenis==='direct_payment'?'Direct Payment':h.jenis==='kasbon'?'Kasbon':'Overbudget'}}</template><template #cell-3="{row:h}">{{h.tanggal}}</template><template #cell-4="{row:h}">{{h.deskripsi || h.alasan}}</template><template #cell-5="{row:h}">{{money(h.total)}}</template><template #cell-6="{row:h}">{{h.pengaju}}</template><template #cell-7="{row:h}"><span class="badge" :class="`st-${h.status}`">{{progress[h.status]}}</span></template><template #cell-8="{row:h}"><ActionMenu><Link :href="`/pengajuan-biaya/${kind}/${h.id}`">Lihat</Link></ActionMenu></template>
                    </ServerTable>
                </section>
            </section>
        </template>
        <component v-else :is="over ? ConfirmDialog : 'section'" title="Add Overbudget" :busy="form.processing || confirming" @confirm="submit" @cancel="router.get(`/pengajuan-biaya/program/${program.id}`)">
            <form class="stack" @submit.prevent="submit">
                <section class="panel"><h2><span class="section-number">1</span> Data Pengajuan</h2><p>Request By {{requestBy}} · Tanggal {{today}}</p><label v-if="over">Alasan wajib<textarea v-model="form.alasan" maxlength="2000" required /></label><template v-else><label>Jenis *<select v-model="form.jenis" required @change="form.jenis==='direct_payment' && (form.bank='',form.no_rekening='',form.nama_rekening='')"><option value="kasbon">Kasbon</option><option value="direct_payment">Direct Payment</option></select></label><label>Deskripsi / keterangan *<textarea v-model="form.deskripsi" maxlength="2000" required /></label></template></section>
                <section class="panel"><h2><span class="section-number">2</span> Informasi Pengajuan</h2><ExpenseBudget :budget="budget" /></section>
                <section class="panel"><h2><span class="section-number">3</span> Pengajuan</h2><div class="table-wrap"><table><thead><tr><th>No.</th><th>Item</th><th class="money">Sisa</th><th class="money">Qty</th><th class="money">Price / Unit</th><th class="money">Total Pengajuan</th><th v-if="over">Action</th></tr></thead><tbody>
                    <tr v-for="(i,n) in form.items" :key="n"><td>{{n+1}}</td><td><input v-if="over && !i.key" v-model="i.item" :aria-label="`Nama item baru ${n+1}`" maxlength="150" required /><template v-else>{{i.item}}</template><small v-if="!over && exceeded(i)">Melebihi sisa item; diperbolehkan selama kategori cukup.</small></td><td class="money">{{money(budget.items.find(b=>b.key===i.key)?.sisa || 0)}}</td><td><input v-model="i.qty" class="money" :aria-label="`Qty ${i.item || n+1}`" type="number" min="0" max="99999999.99" step="0.01" :disabled="!over && Number(budget.sisa)<=0" required /></td><td><MoneyInput v-model="i.harga" :aria-label="`Harga ${i.item || n+1}`" :disabled="!over && Number(budget.sisa)<=0" required /></td><td class="money">{{money(Number(i.qty)*Number(i.harga))}}</td><td v-if="over"><button type="button" @click="form.items.splice(n,1)">Hapus</button></td></tr>
                </tbody><tfoot><tr><th colspan="5">Grand Total</th><td class="money">{{money(Math.round(total))}}</td><td v-if="over" /></tr><tr v-if="!over"><th colspan="5">Sisa kategori setelah pengajuan</th><td class="money" :class="{neg:Number(budget.sisa)-total<0}">{{money(Math.round(Number(budget.sisa)-total))}}</td></tr></tfoot></table></div><button v-if="over" type="button" @click="form.items.push({key:'',item:'',qty:'1',harga:'0'})">+ Item baru</button><p class="hint">Budget dicek per kategori. Jika sisa kategori tidak cukup, ajukan Overbudget dahulu.</p></section>
                <section v-if="!over" class="panel"><h2><span class="section-number">4</span> {{form.jenis==='kasbon'?'Rekening Tujuan & Bukti':'Rekening Penerima & Bukti Tagihan'}}</h2><div class="form-grid"><label>Bank *<input v-model="form.bank" maxlength="50" required /></label><label>No. Rekening *<input v-model="form.no_rekening" maxlength="30" required /></label><label>Nama Rekening *<input v-model="form.nama_rekening" maxlength="100" required /></label></div><label>Bukti PDF/JPG/PNG maksimal 5 MB per file {{form.jenis==='direct_payment'?'(wajib)':'(opsional untuk Kasbon)'}}<input type="file" multiple accept=".pdf,.jpg,.jpeg,.png" :required="form.jenis==='direct_payment'" @change="form.bukti=Array.from(($event.target as HTMLInputElement).files || [])" /></label></section>
                <p v-for="e in form.errors" :key="e" class="error" role="alert">{{e}}</p><div v-if="!over" class="form-actions"><Link :href="`/pengajuan-biaya/program/${program.id}`">Back</Link><button class="primary" :disabled="form.processing || confirming || Number(budget.sisa)<=0">Save</button></div>
            </form>
        </component>
    </AppLayout>
</template>
