<script setup lang="ts">
import PageHead from '../../components/PageHead.vue';

import ServerTable from "../../components/ServerTable.vue";

import ActionMenu from "../../components/ActionMenu.vue";

import {Head,Link,router} from '@inertiajs/vue3';

import {reactive} from 'vue';

import AppLayout from '../../layouts/AppLayout.vue';

import type {PageRows} from '../../types/salesOrder';

import type {OpsProgram} from '../../types/operations';

const props=defineProps<{rows:PageRows<OpsProgram>;filters:Record<string,string>;canManage:boolean}>();

const f=reactive({q:'',jenis:'',tab:'ongoing',...props.filters});

</script>

<template><Head title="Manage Peserta"/><AppLayout title="Manage Peserta" full-width><PageHead title="Manage Peserta" description="Setiap Penjualan Program Final beserta peserta yang sudah Deal di quotation. Buka Lihat Peserta untuk daftar, cetak Daftar Hadir / Name Table (menu gear kolom Aksi), dan export Excel. Close Program menutup program setelah selesai." /><nav class="tabs" aria-label="Status program"><Link href="/manage-peserta?tab=ongoing" :aria-current="f.tab==='ongoing'?'page':undefined">On going</Link><Link href="/manage-peserta?tab=selesai" :aria-current="f.tab==='selesai'?'page':undefined">Selesai</Link></nav><form class="filters" @submit.prevent="router.get('/manage-peserta',f)"><label>Jenis<select v-model="f.jenis"><option value="">Semua</option><option value="public">Public</option><option value="b2b">B2B</option></select></label><button class="primary">Terapkan</button></form><ServerTable url="/manage-peserta/data" label="Manage Peserta" :columns='[{"title": "No", "orderable": false, "className": ""}, {"title": "No. Program", "orderable": true, "className": ""}, {"title": "Judul Program", "orderable": false, "className": ""}, {"title": "Jenis", "orderable": true, "className": ""}, {"title": "Tanggal & Lokasi", "orderable": true, "className": ""}, {"title": "Trainer", "orderable": false, "className": ""}, {"title": "Peserta (Deal / Target)", "orderable": true, "className": "dt-body-right"}, {"title": "Status", "orderable": false, "className": ""}, {"title": "Action", "orderable": false, "className": ""}]' :filters="{ ...filters }" refresh-on-visit ><template #cell-0="{ row: p, number }">{{ number }}</template><template #cell-1="{ row: p, number }">{{ p.nomor }}</template><template #cell-2="{ row: p, number }">{{ p.judul }}</template><template #cell-3="{ row: p, number }">{{ p.jenis.toUpperCase() }}<small>{{ p.company }}</small></template><template #cell-4="{ row: p, number }">{{ p.tgl_mulai }} s.d. {{ p.tgl_selesai }}<small>{{ p.lokasi }}</small></template><template #cell-5="{ row: p, number }">{{ p.trainer || "—" }}</template><template #cell-6="{ row: p, number }">{{ p.peserta }} / {{ p.target_peserta }}</template><template #cell-7="{ row: p, number }"><span class="badge" :class="{off:!!p.closed_date,on:!p.closed_date}">{{ p.operational_status }}</span></template><template #cell-8="{ row: p, number }"><ActionMenu><Link :href="`/manage-peserta/${p.id}`">Lihat Peserta</Link><Link :href="`/manage-peserta/${p.id}?tab=sertifikat`">Kelola Sertifikat</Link><a :href="`/manage-peserta/${p.id}/excel`">Export Excel</a></ActionMenu></template></ServerTable></AppLayout></template>

