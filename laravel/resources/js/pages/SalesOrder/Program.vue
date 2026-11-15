<script setup lang="ts">
import ServerTable from "../../components/ServerTable.vue";
import ActionMenu from "../../components/ActionMenu.vue";
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import ReadyInvoice from '../../components/ReadyInvoice.vue';
import { money, type Order, type Program } from '../../types/salesOrder';
defineProps<{ program: Program & { judul: string; name_customer: string | null }; rows: Order[]; canManage: boolean }>();
</script>
<template>
    <Head :title="`Sales Order: ${program.nomor}`" /><AppLayout title="Sales Order" full-width><Link href="/sales-order">← Semua program</Link><h1>Sales Order: {{ program.nomor }}</h1><p>{{ program.jenis.toUpperCase() }} · {{ program.judul }}</p><p>{{ program.tgl_mulai }} s.d. {{ program.tgl_selesai }} · {{ program.lokasi }}</p><p v-if="program.closed_date" class="notice">Program Closed.</p>
        <ServerTable :url="`/sales-order/program/${program.id}/data`" label="Sales Order program" :columns='[{"title": "No", "orderable": false}, {"title": "Nomor SO", "orderable": true}, {"title": "No. Quotation", "orderable": true}, {"title": "Kepada", "orderable": true}, {"title": "Peserta Deal", "orderable": false}, {"title": "Nilai", "orderable": false, "className": "dt-body-right"}, {"title": "Status", "orderable": true}, {"title": "Action", "orderable": false}]' :filters="{}" refresh-on-visit ><template #cell-0="{ row: p, number }">{{ number }}</template><template #cell-1="{ row: p, number }"><Link :href="`/sales-order/${p.id}`">{{ p.nomor }}</Link><small>{{ p.tanggal }}</small></template><template #cell-2="{ row: p, number }">{{ p.nomor_quotation }}</template><template #cell-3="{ row: p, number }">{{ p.tujuan }} · {{ p.kepada_nama }}<small>u.p. {{ p.up_nama }}</small></template><template #cell-4="{ row: p, number }">{{ p.jumlah_peserta }}<small>{{ p.nama_peserta }}</small></template><template #cell-5="{ row: p, number }">{{ money(p.calc?.grand_total) }}</template><template #cell-6="{ row: p, number }">{{ p.status_tampil }}<small v-if="p.siap_date">{{ p.siap_nama }} · {{ p.siap_date }}</small></template><template #cell-7="{ row: p, number }"><ActionMenu><Link :href="`/sales-order/${p.id}`">View</Link><a :href="`/sales-order/${p.id}/pdf`" target="_blank" rel="noopener">Print Out SO</a><ReadyInvoice v-if="canManage && p.status==='open'" :so="p" :kepada="p.kepada_nama || ''" :count="p.jumlah_peserta || 0" :total="p.calc?.grand_total || '0'" /></ActionMenu></template></ServerTable>
    </AppLayout>
</template>
