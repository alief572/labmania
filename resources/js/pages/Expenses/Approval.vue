<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import PageHead from '../../components/PageHead.vue';
import ServerTable from '../../components/ServerTable.vue';
import ActionMenu from '../../components/ActionMenu.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { onBeforeUnmount, reactive, watch } from 'vue';
import AppLayout from '../../layouts/AppLayout.vue';
import { money } from '../../types/salesOrder';
const props = defineProps<{ kind: string; filters: Record<string, string>; counts: Record<string, number>; canApprove: boolean }>();
const f = reactive({ q: '', tab: props.kind, ...props.filters });
let timer: ReturnType<typeof setTimeout> | undefined;
watch(() => f.q, () => { clearTimeout(timer); timer = setTimeout(() => router.get(appUrl('/pengajuan-biaya/approval'), f, { preserveState: true, preserveScroll: true, replace: true }), 300); });
onBeforeUnmount(() => clearTimeout(timer));
</script>
<template>
    <Head title="Approval Pengajuan" /><AppLayout title="Approval Pengajuan" full-width>
        <PageHead title="Approval Pengajuan" />
        <p v-if="!canApprove" class="notice">Anda dapat melihat daftar, tetapi tidak memiliki hak Approve atau peran yang diperlukan untuk menyetujui pengajuan.</p>
        <div class="batch-card">
            <nav class="tabs" aria-label="Jenis approval"><Link :href="appUrl('/pengajuan-biaya/approval')" :aria-current="kind === 'pengajuan' ? 'page' : undefined">Pengajuan<span class="nav-count">{{ counts.pengajuan }}</span></Link><Link :href="appUrl('/pengajuan-biaya/approval?tab=overbudget')" :aria-current="kind === 'overbudget' ? 'page' : undefined">Overbudget<span class="nav-count">{{ counts.overbudget }}</span></Link></nav>
            <div class="filters"><label class="grow">Cari<input v-model="f.q" type="search" maxlength="100" placeholder="Cari nomor pengajuan, program, pengaju…" /></label></div>
            <ServerTable :url="appUrl('/pengajuan-biaya/approval/data')" label="Approval Pengajuan" :keyword="filters.q || ''" :filters="{ ...filters, tab: kind }" :columns="[{title:'No',orderable:false},{title:kind==='overbudget'?'No. Overbudget':'No. Pengajuan',orderable:true},{title:'No. Program',orderable:true},{title:'Kategori / Jenis',orderable:true},{title:'Deskripsi / Item',orderable:true},{title:'Total',orderable:true,className:'dt-body-right'},{title:'Request By',orderable:true},{title:'Action',orderable:false}]" refresh-on-visit>
                <template #cell-0="{ number }">{{ number }}</template>
                <template #cell-1="{ row }"><span class="code">{{ row.nomor }}</span><small>{{ row.tanggal }}</small></template>
                <template #cell-2="{ row }"><span class="tag" :class="row.jenis_program === 'b2b' ? 'tag-b2b' : 'tag-public'">{{ row.jenis_program === 'b2b' ? 'B2B' : 'Public' }}</span> <span class="code">{{ row.program_nomor }}</span><small>{{ row.judul }}</small></template>
                <template #cell-3="{ row }">{{ row.tipe_biaya === 'fixed' ? 'Fixed Cost' : 'Variable Cost' }}<small>{{ row.jenis === 'direct_payment' ? 'Direct Payment' : row.jenis === 'kasbon' ? 'Kasbon' : 'Overbudget' }}</small></template>
                <template #cell-4="{ row }">{{ row.deskripsi || row.alasan }}</template>
                <template #cell-5="{ row }">{{ money(row.total) }}</template>
                <template #cell-6="{ row }">{{ row.pengaju }}</template>
                <template #cell-7="{ row }"><ActionMenu><Link :href="appUrl(`/pengajuan-biaya/${kind}/${row.id}`)">Lihat</Link><Link v-if="row.canApprove" :href="appUrl(`/pengajuan-biaya/${kind}/${row.id}?aksi=approval`)">Proses (Setujui / Tolak)</Link></ActionMenu></template>
            </ServerTable>
        </div>
    </AppLayout>
</template>
