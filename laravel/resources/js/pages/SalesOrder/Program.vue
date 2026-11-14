<script setup lang="ts">
import ActionMenu from "../../components/ActionMenu.vue";
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ReadyInvoice from '../../components/ReadyInvoice.vue';
import { money, type Order, type Program } from '../../types/salesOrder';
defineProps<{ program: Program & { judul: string; name_customer: string | null }; rows: Order[]; canManage: boolean }>();
</script>
<template>
    <Head :title="`Sales Order: ${program.nomor}`" /><AppLayout title="Sales Order"><Link href="/sales-order">← Semua program</Link><h1>Sales Order: {{ program.nomor }}</h1><p>{{ program.jenis.toUpperCase() }} · {{ program.judul }}</p><p>{{ program.tgl_mulai }} s.d. {{ program.tgl_selesai }} · {{ program.lokasi }}</p><p v-if="program.closed_date" class="notice">Program Closed.</p>
        <div class="table-wrap"><table><caption class="sr-only">Sales Order program</caption><thead><tr><th>Nomor SO</th><th>No. Quotation</th><th>Kepada</th><th>Peserta Deal</th><th>Nilai</th><th>Status</th><th>Aksi</th></tr></thead><tbody><tr v-for="s in rows" :key="s.id"><td><Link :href="`/sales-order/${s.id}`">{{ s.nomor }}</Link><small>{{ s.tanggal }}</small></td><td>{{ s.nomor_quotation }}</td><td>{{ s.tujuan }} · {{ s.kepada_nama }}<small>u.p. {{ s.up_nama }}</small></td><td>{{ s.jumlah_peserta }}<small>{{ s.nama_peserta }}</small></td><td>{{ money(s.calc?.grand_total) }}</td><td>{{ s.status_tampil }}<small v-if="s.siap_date">{{ s.siap_nama }} · {{ s.siap_date }}</small></td><td><ActionMenu ><Link :href="`/sales-order/${s.id}`">View</Link><a :href="`/sales-order/${s.id}/pdf`" target="_blank" rel="noopener">Print Out SO</a><ReadyInvoice v-if="canManage && s.status === 'open'" :so="s" :kepada="s.kepada_nama || ''" :count="s.jumlah_peserta || 0" :total="s.calc?.grand_total || '0'" /></ActionMenu></td></tr><tr v-if="!rows.length"><td colspan="7" class="empty">Belum ada SO.</td></tr></tbody></table></div>
    </AppLayout>
</template>
