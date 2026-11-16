<script setup lang="ts">
import type { Budget } from '../types/operations';
import { rupiah } from '../composables/costing';
defineProps<{ budget: Budget }>();
const qty = (value: string) => Number(value).toLocaleString('id-ID', { maximumFractionDigits: 2 });
</script>
<template>
    <div class="kpis budget-summary"><div v-for="(label, field) in { budget: 'Budget', aktual: 'Terpakai', sisa: 'Sisa Budget' }" :key="field" class="kpi" :class="{ bad: field === 'sisa' && Number(budget.sisa) <= 0 }"><span class="kpi-l">{{ label }}</span><strong class="kpi-v">{{ rupiah(budget[field]) }}</strong></div></div>
    <div class="table-wrap"><table><caption class="sr-only">Rincian budget kategori</caption>
        <thead><tr><th rowspan="2">Item</th><th colspan="3">Estimasi</th><th colspan="2">Terpakai</th><th colspan="2">Overbudget</th><th rowspan="2" class="money">Sisa</th></tr><tr><th class="money">Qty</th><th class="money">Price / Unit</th><th class="money">Total Budget</th><th class="money">Qty</th><th class="money">Total</th><th class="money">Qty</th><th class="money">Total</th></tr></thead>
        <tbody><tr v-for="item in budget.items" :key="item.key"><td>{{ item.item }}<small v-if="!item.id_penjualan_biaya">Item baru</small></td><td class="money">{{ qty(item.qty) }}</td><td class="money">{{ rupiah(item.harga) }}</td><td class="money">{{ rupiah(item.estimasi) }}</td><td class="money">{{ qty(item.qty_terpakai) }}</td><td class="money">{{ rupiah(item.terpakai) }}</td><td class="money">{{ qty(item.qty_overbudget) }}</td><td class="money">{{ rupiah(item.overbudget) }}</td><td class="money" :class="{ neg: Number(item.sisa) <= 0 }">{{ rupiah(item.sisa) }}</td></tr><tr v-if="!budget.items.length"><td colspan="9">Tidak ada item biaya pada kategori ini.</td></tr></tbody>
    </table></div>
</template>
