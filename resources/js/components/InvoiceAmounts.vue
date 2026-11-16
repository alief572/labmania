<script setup lang="ts">
import { money } from '../types/salesOrder';
defineProps<{ amounts: Record<string, string>; treatment: string; taxed: boolean }>();
</script>
<template>
    <dl><dt>Penjualan — nilai Deal setelah diskon</dt><dd>{{ money(amounts.penjualan) }}</dd><dt>DPP — {{ taxed ? 'Penjualan × 11/12, dibulatkan' : 'Quotation tanpa PPN' }}</dt><dd>{{ money(amounts.dpp) }}</dd><dt>PPN — {{ taxed ? 'DPP × 12%, dibulatkan' : 'Tanpa PPN' }}</dt><dd>{{ money(amounts.ppn) }}</dd><dt>Biaya Lain-lain — tanpa PPN</dt><dd>{{ money(amounts.biaya_lain) }}</dd><dt>Total Tagihan — Penjualan + PPN + Biaya Lain-lain</dt><dd>{{ money(amounts.total_tagihan) }}</dd><dt>Piutang — {{ treatment === 'PKP' ? 'Total Tagihan' : 'Penjualan + Biaya Lain-lain' }}</dt><dd><strong>{{ money(amounts.piutang) }}</strong></dd></dl>
</template>
