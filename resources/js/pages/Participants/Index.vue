<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import { useConfirmedMutation } from '../../composables/confirmedMutation';
import PageHead from '../../components/PageHead.vue';

import ServerTable from "../../components/ServerTable.vue";

import ActionMenu from "../../components/ActionMenu.vue";

import {Head,Link,router} from '@inertiajs/vue3';

import {onBeforeUnmount,reactive,watch} from 'vue';

import AppLayout from '../../layouts/AppLayout.vue';

import type {PageRows} from '../../types/salesOrder';

import type {OpsProgram} from '../../types/operations';

const props=defineProps<{rows:PageRows<OpsProgram>;filters:Record<string,string>;canManage:boolean;counts:Record<string,number>}>();

const f=reactive({q:'',jenis:'',tab:'ongoing',...props.filters});

const confirmation = useConfirmedMutation();
const { pending: confirming } = confirmation;
const search = () => router.get(appUrl('/manage-peserta'), { ...f, tab: props.filters.tab || 'ongoing' }, { preserveState:true, preserveScroll:true, replace:true });
let timer: ReturnType<typeof setTimeout> | undefined;
watch(() => f.jenis, () => { clearTimeout(timer); search(); });
watch(() => f.q, () => { clearTimeout(timer); timer = setTimeout(search, 300); });
onBeforeUnmount(() => clearTimeout(timer));
function closeProgram(p: OpsProgram) { return confirmation.visit('post', `/manage-peserta/${p.id}/close`, {}, { title:`Close Program ${p.nomor}?`, text:['Program tidak dapat dibuka kembali. Quotation, Invoice, serta pengajuan biaya baru akan dikunci.', ...(p.warnings || []), ...(p.blockers || [])].join('\n'), danger:true, confirmLabel:'Ya, Close Program' }); }
</script>

<template><Head title="Manage Peserta"/><AppLayout title="Manage Peserta" full-width><PageHead title="Manage Peserta" description="Setiap Penjualan Program Final beserta peserta yang sudah Deal di quotation. Buka Lihat Peserta untuk daftar, cetak Daftar Hadir / Name Table (menu gear kolom Aksi), dan export Excel. Close Program menutup program setelah selesai." /><div class="batch-card"><nav class="tabs" aria-label="Status program"><Link :href="appUrl('/manage-peserta?tab=ongoing')" :aria-current="(filters.tab || 'ongoing')==='ongoing'?'page':undefined">On going<span class="nav-count">{{counts.ongoing}}</span></Link><Link :href="appUrl('/manage-peserta?tab=selesai')" :aria-current="filters.tab==='selesai'?'page':undefined">Selesai<span class="nav-count">{{counts.selesai}}</span></Link></nav><form class="filters" @submit.prevent="search"><label class="grow">Cari<input v-model="f.q" type="search" maxlength="100" placeholder="Cari nomor, judul program, lokasi, atau trainer…" /></label><label>Jenis<select v-model="f.jenis"><option value="">Public &amp; B2B</option><option value="public">Public</option><option value="b2b">B2B</option></select></label></form><ServerTable :keyword="filters.q || ''" :url="appUrl('/manage-peserta/data')" label="Manage Peserta" :columns='[{"title": "No", "orderable": false, "className": ""}, {"title": "No. Program", "orderable": true, "className": ""}, {"title": "Judul Program", "orderable": false, "className": ""}, {"title": "Jenis", "orderable": true, "className": ""}, {"title": "Tanggal & Lokasi", "orderable": true, "className": ""}, {"title": "Trainer", "orderable": false, "className": ""}, {"title": "Peserta (Deal / Target)", "orderable": true, "className": "dt-body-right"}, {"title": "Status", "orderable": false, "className": ""}, {"title": "Action", "orderable": false, "className": ""}]' :filters="{ ...filters }" refresh-on-visit ><template #cell-0="{ row: p, number }">{{ number }}</template><template #cell-1="{ row: p, number }">{{ p.nomor }}</template><template #cell-2="{ row: p, number }">{{ p.judul }}</template><template #cell-3="{ row: p, number }"><span class="tag" :class="p.jenis==='b2b'?'tag-b2b':'tag-public'">{{ p.jenis==='b2b'?'B2B':'Public' }}</span><small>{{ p.company }}</small></template><template #cell-4="{ row: p, number }">{{ p.tgl_mulai }} s.d. {{ p.tgl_selesai }}<small>{{ p.lokasi }}</small></template><template #cell-5="{ row: p, number }">{{ p.trainer || "—" }}</template><template #cell-6="{ row: p, number }">{{ p.peserta }} / {{ p.target_peserta }}</template><template #cell-7="{ row: p, number }"><span class="badge" :class="{off:!!p.closed_date,on:!p.closed_date}">{{ p.operational_status }}</span></template><template #cell-8="{ row: p, number }"><ActionMenu><Link :href="appUrl(`/manage-peserta/${p.id}`)">Lihat Peserta</Link><Link :href="appUrl(`/manage-peserta/${p.id}?tab=sertifikat`)">Kelola Sertifikat</Link><a :href="appUrl(`/manage-peserta/${p.id}/excel`)">Export Excel</a><button v-if="canManage && !p.closed_date" :disabled="confirming" type="button" class="danger" @click="closeProgram(p)">Close Program</button></ActionMenu></template></ServerTable></div></AppLayout></template>

