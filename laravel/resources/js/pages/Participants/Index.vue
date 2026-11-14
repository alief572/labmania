<script setup lang="ts">
import ActionMenu from "../../components/ActionMenu.vue";
import {Head,Link,router} from '@inertiajs/vue3';
import {reactive} from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import Pagination from '../../components/Pagination.vue';
import type {PageRows} from '../../types/salesOrder';
import type {OpsProgram} from '../../types/operations';
const props=defineProps<{rows:PageRows<OpsProgram>;filters:Record<string,string>;canManage:boolean}>();
const f=reactive({q:'',jenis:'',tab:'ongoing',...props.filters});
</script>
<template><Head title="Manage Peserta"/><AppLayout title="Manage Peserta"><h1>Manage Peserta</h1><nav class="tabs" aria-label="Status program"><Link href="/manage-peserta?tab=ongoing" :aria-current="f.tab==='ongoing'?'page':undefined">On going</Link><Link href="/manage-peserta?tab=selesai" :aria-current="f.tab==='selesai'?'page':undefined">Selesai</Link></nav><form class="filters" @submit.prevent="router.get('/manage-peserta',f)"><label>Cari program<input v-model="f.q" type="search" maxlength="100" placeholder="Nomor, judul, lokasi, trainer, Company"/></label><label>Jenis<select v-model="f.jenis"><option value="">Semua</option><option value="public">Public</option><option value="b2b">B2B</option></select></label><button class="primary">Terapkan</button></form><p>{{rows.total}} program</p><div class="table-wrap"><table><thead><tr><th>Program</th><th>Waktu &amp; lokasi</th><th>Trainer</th><th>Deal / target</th><th>Status</th><th>Aksi</th></tr></thead><tbody><tr v-for="p in rows.data" :key="p.id"><td>{{p.nomor}}<small>{{p.judul}} · {{p.company}}</small></td><td>{{p.tgl_mulai}} s.d. {{p.tgl_selesai}}<small>{{p.lokasi}}</small></td><td>{{p.trainer}}</td><td>{{p.peserta}} / {{p.target_peserta}}</td><td>{{p.operational_status}}</td><td><ActionMenu><Link :href="`/manage-peserta/${p.id}`">Lihat Peserta<span v-if="canManage && !p.closed_date"> / Close Program</span></Link></ActionMenu></td></tr><tr v-if="!rows.data.length"><td colspan="6" class="empty">Tidak ada program sesuai filter.</td></tr></tbody></table></div><Pagination :page="rows" label="Halaman Manage Peserta"/></AppLayout></template>
