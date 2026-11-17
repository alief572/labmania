<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import PageHead from '../../components/PageHead.vue';
import RefundDetail from '../../components/RefundDetail.vue';
defineProps<{refund:InstanceType<typeof RefundDetail>['$props']['refund'];snapshot:InstanceType<typeof RefundDetail>['$props']['snapshot'];canManage:boolean;closed:boolean}>();
</script>
<template><Head :title="refund.nomor"/><AppLayout title="Refund"><PageHead :title="refund.nomor"><Link :href="appUrl('/sales-order/refund')">← Daftar Refund</Link></PageHead><RefundDetail :refund="refund" :snapshot="snapshot" :can-manage="canManage" :closed="closed" /></AppLayout></template>
