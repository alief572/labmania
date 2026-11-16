<script setup lang="ts">
import { computed } from 'vue';
import { money, type Journal } from '../types/salesOrder';
const props = defineProps<{ rows: Journal[]; program: string }>();
const debit = computed(() => props.rows.reduce((sum, r) => sum + BigInt(r.debit), 0n).toString());
const credit = computed(() => props.rows.reduce((sum, r) => sum + BigInt(r.credit), 0n).toString());
</script>
<template>
    <div class="table-wrap"><table><caption class="sr-only">Jurnal Invoice {{ program }}</caption><thead><tr><th>Tanggal Jurnal</th><th>Company</th><th>No. Program</th><th>COA</th><th>Nama COA</th><th>Deskripsi</th><th class="money">Debit</th><th class="money">Credit</th></tr></thead><tbody><tr v-for="(j, index) in rows" :key="j.id || index"><td>{{ j.tanggal }}</td><td>{{ j.company }}<small v-if="j.jenis">{{ j.jenis }}</small></td><td>{{ program }}</td><td>{{ j.no_coa }}</td><td>{{ j.nama_coa }}</td><td>{{ j.deskripsi }}</td><td class="money">{{ money(j.debit) }}</td><td class="money">{{ money(j.credit) }}</td></tr><tr v-if="!rows.length"><td colspan="8" class="empty">Jurnal belum tersedia. Lengkapi konfigurasi COA.</td></tr></tbody><tfoot><tr><th colspan="6">Balancing: {{ debit === credit ? 'seimbang' : 'tidak seimbang' }}</th><td class="money">{{ money(debit) }}</td><td class="money">{{ money(credit) }}</td></tr></tfoot></table></div>
</template>
