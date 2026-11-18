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
// Penanda pada skala (mockup .mk): BEP dan target profit di atas batang, rencana di bawah. Label tetap terbaca bila berdekatan.
const marks = computed(() => [
    ...(props.result.bep !== null ? [{ key: 'bep', label: `BEP ${props.result.bep}`, at: percent(props.result.bep), pos: 'top' }] : []),
    ...(goal.value ? [{ key: 'tp', label: `Target profit ${goal.value}`, at: percent(goal.value), pos: 'top' }] : []),
    { key: 'plan', label: `Rencana ${props.participants}`, at: percent(Number(props.participants)), pos: 'bot' },
]);
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
            <div class="simulation-wrap">
            <div class="simulation-scale" role="img" :aria-label="`Skala 0 sampai ${maximum} peserta. BEP ${result.bep ?? 'tidak tercapai'}, rencana ${participants}${goal ? ', target profit ' + goal : ''}.`">
                <span class="scale-loss" :style="{width:loss+'%'}"></span><span class="scale-target" :style="{width:(profit-loss)+'%'}"></span><span class="scale-profit" :style="{width:(100-profit)+'%'}"></span><i :style="{left:percent(Number(participants))+'%'}"></i>
            </div>
            <span v-for="m in marks" :key="m.key" class="sim-mark" :class="[`sim-mark-${m.key}`, `sim-mark-${m.pos}`]" :style="{left:m.at+'%'}" aria-hidden="true">{{ m.label }}</span>
            </div>
            <p class="simulation-axis"><span>0 peserta</span><span>{{ maximum }} peserta</span></p>
            <p class="muted">Merah: rugi · Kuning: untung, belum capai target profit · Hijau: target tercapai · Garis: rencana {{ participants }} peserta.</p>
            <details><summary>Rincian perhitungan</summary>
                <ol class="sim-steps">
                    <li><b>Biaya tetap acara</b> <span class="muted">— biaya yang keluar berapa pun jumlah pesertanya (trainer, ruangan, dll.).</span><div><b>{{ rupiah(result.fixed) }}</b></div></li>
                    <li><b>Biaya per peserta</b> <span class="muted">— biaya yang bertambah setiap ada 1 peserta (modul, sertifikat, makan, dll.).</span><div><b>{{ rupiah(result.varPer) }}</b> per peserta</div></li>
                    <li><b>Total biaya (HPP)</b> <span class="muted">— biaya tetap + biaya per peserta untuk {{ participants }} peserta.</span><div>{{ rupiah(result.fixed) }} + {{ rupiah(result.varPer) }} × {{ participants }} = <b>{{ rupiah(result.hpp) }}</b></div></li>
                    <li><b>Pendapatan</b> <span class="muted">— harga jual dikali jumlah peserta.</span><div>{{ rupiah(price) }} × {{ participants }} = <b>{{ rupiah(result.pendapatan) }}</b></div></li>
                    <li><b>{{ result.profit < 0 ? 'Rugi' : 'Untung' }}</b> <span class="muted">— pendapatan dikurangi total biaya.</span><div>{{ rupiah(result.pendapatan) }} − {{ rupiah(result.hpp) }} = <b>{{ rupiah(result.profit) }}</b></div></li>
                    <li><b>Titik impas</b> <span class="muted">— jumlah peserta paling sedikit supaya tidak rugi.</span><div v-if="result.bep === null">Harga jual belum lebih besar dari biaya per peserta, jadi berapa pun pesertanya tetap rugi.</div><div v-else>{{ rupiah(result.fixed) }} ÷ ({{ rupiah(price) }} − {{ rupiah(result.varPer) }}), dibulatkan ke atas = <b>{{ result.bep }} peserta</b></div></li>
                </ol>
                <div class="table-wrap"><table><caption class="sr-only">Simulasi perubahan jumlah peserta</caption><thead><tr><th>Peserta</th><th class="money">Pendapatan</th><th class="money">Biaya (HPP)</th><th class="money">Untung / rugi</th></tr></thead><tbody><tr v-for="s in scenarios" :key="s.n"><td>{{ s.n }}{{ s.n===Number(participants)?' (rencana)':'' }}{{ s.n===result.bep?' (titik impas)':'' }}</td><td class="money">{{ rupiah(s.revenue) }}</td><td class="money">{{ rupiah(s.cost) }}</td><td class="money">{{ rupiah(s.revenue-s.cost) }}</td></tr></tbody></table></div>
            </details>
        </template>
    </div>
</template>
