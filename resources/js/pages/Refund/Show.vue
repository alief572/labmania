<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import PageHead from '../../components/PageHead.vue';
import RefundDetail from '../../components/RefundDetail.vue';
type Detail = InstanceType<typeof RefundDetail>['$props'];
defineProps<{ refund: Detail['refund']; snapshot: Detail['snapshot']; riwayat: Detail['riwayat']; maks: string; canManage: boolean; canAjukan: boolean; canApprove: boolean; closed: boolean }>();
</script>

<template>
    <Head :title="refund.nomor" />
    <AppLayout title="Refund">
        <PageHead :title="refund.nomor"><Link :href="appUrl('/refund')">← Daftar Refund</Link></PageHead>
        <RefundDetail :refund="refund" :snapshot="snapshot" :riwayat="riwayat" :maks="maks" :can-manage="canManage" :can-ajukan="canAjukan" :can-approve="canApprove" :closed="closed" :mode="refund.status === 'disetujui' ? 'proses' : refund.status === 'ditolak' ? 'perbaiki' : refund.status === 'menunggu' ? 'keputusan' : null" />
    </AppLayout>
</template>
