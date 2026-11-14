<script setup lang="ts">
import ActionMenu from "../../components/ActionMenu.vue";
import {Head,Link,router} from '@inertiajs/vue3';
import {reactive} from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import Pagination from '../../components/Pagination.vue';
import {money,type PageRows} from '../../types/salesOrder';
import type {ExpenseRow} from '../../types/operations';
const props=defineProps<{rows:PageRows<ExpenseRow>;kind:string;filters:Record<string,string>}>();
const f=reactive({q:'',tab:props.kind,...props.filters});
</script>
<template><Head title="Approval Pengajuan"/><AppLayout title="Approval Pengajuan"><h1>Approval Pengajuan</h1><nav class="tabs" aria-label="Jenis approval"><Link href="/pengajuan-biaya/approval" :aria-current="kind==='pengajuan'?'page':undefined">Pengajuan</Link><Link href="/pengajuan-biaya/approval?tab=overbudget" :aria-current="kind==='overbudget'?'page':undefined">Overbudget</Link></nav><form class="filters" @submit.prevent="router.get('/pengajuan-biaya/approval',f)"><label>Cari<input v-model="f.q" type="search" maxlength="100"/></label><button>Terapkan</button></form><p>{{rows.total}} Menunggu Approval</p><div class="table-wrap"><table><thead><tr><th>Nomor / Program</th><th>Kategori</th><th>Total</th><th>Request By</th><th>Tanggal</th><th>Aksi</th></tr></thead><tbody><tr v-for="h in rows.data" :key="h.id"><td>{{h.nomor}}<small>{{h.program_nomor}}</small></td><td>{{h.tipe_biaya}}</td><td>{{money(h.total)}}</td><td>{{h.pengaju}}</td><td>{{h.tanggal}}</td><td><ActionMenu><Link :href="`/pengajuan-biaya/${kind}/${h.id}`">Lihat / Proses</Link></ActionMenu></td></tr><tr v-if="!rows.data.length"><td colspan="6" class="empty">Tidak ada pengajuan Menunggu Approval sesuai akses.</td></tr></tbody></table></div><Pagination :page="rows" label="Halaman Approval"/></AppLayout></template>
