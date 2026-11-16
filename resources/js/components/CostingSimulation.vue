<script setup lang="ts">
import { computed } from 'vue';
import { calc, profitParticipants, rupiah } from '../composables/costing';

const props = defineProps<{ result: ReturnType<typeof calc>; participants: number | string; price: number | string; targetProfit: number | string }>();
const target = computed(() => Number(props.targetProfit) || 0);
const goal = computed(() => profitParticipants(props.result.fixed, props.result.varPer, Number(props.price), target.value));
const maximum = computed(() => Math.max(10, Math.ceil(Math.max(Number(props.participants), props.result.bep ?? 0, goal.value ?? 0) * 1.2)));
const percent = (n: number) => Math.max(0, Math.min(100, n / maximum.value * 100));
const loss = computed(() => props.result.bep === null ? 100 : percent(props.result.bep));
const profit = computed(() => goal.value ? Math.max(loss.value, percent(goal.value)) : loss.value);
const scenarios = computed(() => [...new Set([Number(props.participants), ...(props.result.bep ? [props.result.bep - 1, props.result.bep] : []), ...(goal.value ? [goal.value] : [])])].filter(n => n >= 1).sort((a, b) => a - b).slice(0, 5).map(n => ({ n, revenue: Number(props.price) * n, cost: props.result.fixed + props.result.varPer * n })));
</script>

<template>
    <div class="costing-simulation">
        <p v-if="Number(participants) <= 0 || Number(price) <= 0" class="notice">Isi target peserta dan harga jual / peserta untuk melihat simulasi.</p>
        <template v-else>
            <h3>Titik impas (BEP)</h3>
            <div class="simulation-cards">
                <div><span>BEP jumlah peserta</span><strong>{{ result.bep === null ? 'Tidak tercapai' : `${result.bep} peserta` }}</strong><p>Dengan harga {{ rupiah(price) }} / peserta.</p></div>
                <div><span>BEP harga jual</span><strong>{{ rupiah(result.hppPerPax) }} / peserta</strong><p>Dengan {{ participants }} peserta.</p></div>
            </div>
            <h3>Hasil rencana simulasi</h3>
            <div class="simulation-cards">
                <div><span>Pendapatan</span><strong>{{ rupiah(result.pendapatan) }}</strong><p>{{ participants }} peserta × {{ rupiah(price) }}</p></div>
                <div><span>Biaya (HPP)</span><strong>{{ rupiah(result.hpp) }}</strong><p>Fixed {{ rupiah(result.fixed) }} + variable {{ rupiah(result.totalVar) }}</p></div>
                <div :class="result.profit < 0 ? 'simulation-loss' : 'simulation-profit'"><span>Profit ({{ result.profit < 0 ? 'rugi' : 'untung' }})</span><strong>{{ rupiah(result.profit) }}</strong><p>Margin {{ result.margin }}% dari pendapatan.</p></div>
            </div>
            <p v-if="target" class="notice">Target profit {{ rupiah(target) }} {{ result.profit >= target ? 'tercapai' : 'belum tercapai' }}.<template v-if="goal"> Minimal {{ goal }} peserta pada harga ini.</template></p>
            <p v-else class="muted">Target profit tidak diisi.</p>
            <h3>Posisi di skala jumlah peserta</h3>
            <div class="simulation-scale" role="img" :aria-label="`Skala 0 sampai ${maximum} peserta. BEP ${result.bep ?? 'tidak tercapai'}, rencana ${participants}${goal ? ', target profit ' + goal : ''}.`">
                <span class="scale-loss" :style="{width:loss+'%'}"></span><span class="scale-target" :style="{width:(profit-loss)+'%'}"></span><span class="scale-profit" :style="{width:(100-profit)+'%'}"></span><i :style="{left:percent(Number(participants))+'%'}"></i>
            </div>
            <p class="simulation-axis"><span>0 peserta</span><span>{{ maximum }} peserta</span></p>
            <p class="muted">Merah: rugi · Kuning: untung, belum capai target profit · Hijau: target tercapai · Garis: rencana {{ participants }} peserta.</p>
            <details><summary>Rincian perhitungan</summary><dl><dt>Total Fixed Cost</dt><dd>{{ rupiah(result.fixed) }}</dd><dt>Variable / peserta</dt><dd>{{ rupiah(result.varPer) }}</dd><dt>HPP</dt><dd>{{ rupiah(result.fixed) }} + {{ rupiah(result.varPer) }} × {{ participants }} = {{ rupiah(result.hpp) }}</dd><dt>Profit</dt><dd>{{ rupiah(result.pendapatan) }} − {{ rupiah(result.hpp) }} = {{ rupiah(result.profit) }}</dd></dl>
                <div class="table-wrap"><table><caption class="sr-only">Simulasi perubahan jumlah peserta</caption><thead><tr><th>Peserta</th><th class="money">Pendapatan</th><th class="money">Biaya (HPP)</th><th class="money">Untung / rugi</th></tr></thead><tbody><tr v-for="s in scenarios" :key="s.n"><td>{{ s.n }}{{ s.n===Number(participants)?' (rencana)':'' }}{{ s.n===result.bep?' (titik impas)':'' }}</td><td class="money">{{ rupiah(s.revenue) }}</td><td class="money">{{ rupiah(s.cost) }}</td><td class="money">{{ rupiah(s.revenue-s.cost) }}</td></tr></tbody></table></div>
            </details>
        </template>
    </div>
</template>
