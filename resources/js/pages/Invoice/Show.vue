<script setup lang="ts">
import { appUrl } from '../../composables/appUrl';
import { Head, Link } from '@inertiajs/vue3';
import AppLayout from '../../layouts/AppLayout.vue';
import PageHead from '../../components/PageHead.vue';
import InvoiceDetail from '../../components/InvoiceDetail.vue';
type Detail = InstanceType<typeof InvoiceDetail>['$props'];
defineProps<{invoice:Detail['invoice'];snapshot:Detail['snapshot'];journal:Detail['journal'];setor:Detail['setor'];canManage:boolean;today:string}>();
</script>
<template><Head :title="invoice.nomor"/><AppLayout title="Invoice"><PageHead :title="invoice.nomor" :description="`${invoice.tanggal} | ${invoice.status} | ${invoice.tanggal_bayar?'Lunas '+invoice.tanggal_bayar:'Belum dibayar'}`"><Link :href="appUrl('/invoicing?tab=invoiced')">Kembali ke Invoicing</Link></PageHead><InvoiceDetail :invoice="invoice" :snapshot="snapshot" :journal="journal" :setor="setor" :can-manage="canManage" :today="today" /></AppLayout></template>
