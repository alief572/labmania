<script setup lang="ts">
import type { Budget } from '../types/operations';
import { rupiah } from '../composables/costing';
const props = defineProps<{ budget: Budget }>();
const qty = (value: string) => Number(value).toLocaleString('id-ID', { maximumFractionDigits: 2 });
const sum = (field: 'estimasi' | 'terpakai' | 'overbudget') => props.budget.items.reduce((total, item) => total + Number(item[field]), 0);
</script>
<template>
    <div class="table-wrap"><table class="pb-info"><caption class="sr-only">Rincian budget kategori</caption>
        <thead><tr><th rowspan="2">No.</th><th rowspan="2">Item</th><th colspan="3" class="group">Estimasi</th><th colspan="2" class="group">Terpakai</th><th colspan="2" class="group">Overbudget</th><th rowspan="2" class="money">Sisa</th></tr><tr><th class="money">Qty</th><th class="money">Price / Unit</th><th class="money">Total Budget</th><th class="money">Qty</th><th class="money">Total Terpakai</th><th class="money">Qty</th><th class="money">Total</th></tr></thead>
        <tbody><tr v-for="(item, index) in budget.items" :key="item.key"><td>{{index+1}}</td><td>{{ item.item }}<span v-if="!item.id_penjualan_biaya" class="tag">Item baru</span></td><td class="money">{{ item.id_penjualan_biaya ? qty(item.qty) : '—' }}</td><td class="money">{{ rupiah(item.harga) }}</td><td class="money">{{ item.id_penjualan_biaya ? rupiah(item.estimasi) : '—' }}</td><td class="money">{{ qty(item.qty_terpakai) }}</td><td class="money">{{ Number(item.terpakai) ? rupiah(item.terpakai) : '—' }}</td><td class="money">{{ qty(item.qty_overbudget) }}</td><td class="money">{{ Number(item.overbudget) ? rupiah(item.overbudget) : '—' }}</td><td class="money" :class="{ neg: Number(item.sisa) <= 0 }"><b>{{ rupiah(item.sisa) }}</b></td></tr><tr v-if="!budget.items.length"><td colspan="10">Tidak ada item biaya pada kategori ini.</td></tr></tbody>
        <tfoot><tr><th colspan="4" scope="row">Total</th><td class="money"><b>{{rupiah(sum('estimasi'))}}</b></td><td></td><td class="money"><b>{{rupiah(sum('terpakai'))}}</b></td><td></td><td class="money"><b>{{rupiah(sum('overbudget'))}}</b></td><td class="money"><b>{{rupiah(budget.sisa)}}</b></td></tr></tfoot>
    </table></div>
</template>

<style scoped>
.pb-info th, .pb-info td { font-size: 12.5px; }
.group { text-align: center; }
.money { white-space: nowrap; }
.tag { margin-left: 6px; }
tfoot th, tfoot td { border-top: 2px solid var(--line); background: var(--navy-50); }
</style>
