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
        <p v-if="Number(participants) <= 0 || Number(price) <= 0" class="sim-empty">Isi target peserta dan harga jual / peserta untuk melihat simulasi.</p>
        <template v-else>
            <section class="sx-panel">
            <h3>Titik impas (BEP)</h3>
            <p class="rowdesc">Batas minimal supaya tidak rugi. Tidak tergantung rencana, hanya tergantung biaya.</p>
            <div class="simulation-cards duo">
                <div class="bep"><span>BEP jumlah peserta</span><strong>{{ result.bep === null ? 'Tidak tercapai' : result.bep }} <small v-if="result.bep !== null">peserta</small></strong><p v-if="result.bep === null">Harga jual tidak melebihi biaya variable per peserta.</p><p v-else>Dengan harga {{ rupiah(price) }}. Di bawah {{ result.bep }} peserta rugi. Rencana {{ participants }} peserta, {{ Number(participants) >= result.bep ? `aman (lebih ${Number(participants) - result.bep})` : `kurang ${result.bep - Number(participants)}` }}.</p></div>
                <div class="bep"><span>BEP harga jual</span><strong>{{ rupiah(result.hppPerPax) }} <small>/ peserta</small></strong><p>Dengan {{ participants }} peserta. Di bawah harga ini rugi. Harga rencana {{ rupiah(price) }}, {{ Number(price) >= result.hppPerPax ? 'lebih' : 'kurang' }} {{ rupiah(Math.abs(Number(price) - result.hppPerPax)) }}.</p></div>
            </div>
            <h3 class="gap">Hasil rencana simulasi</h3>
            <p class="rowdesc">Dihitung dari rencana: {{ participants }} peserta, harga {{ rupiah(price) }} / peserta.</p>
            <div class="simulation-cards trio">
                <div class="harga"><span>Pendapatan</span><strong>{{ rupiah(result.pendapatan) }}</strong><p>{{ participants }} peserta × {{ rupiah(price) }}</p></div>
                <div class="biaya"><span>Biaya (HPP)</span><strong>{{ rupiah(result.hpp) }}</strong><p>Fixed {{ rupiah(result.fixed) }} + variable {{ rupiah(result.totalVar) }}</p></div>
                <div :class="result.profit < 0 ? 'simulation-loss' : 'simulation-profit'"><span>Profit ({{ result.profit < 0 ? 'rugi' : 'untung' }})</span><strong>{{ rupiah(result.profit) }}</strong><p>Margin {{ result.margin.toLocaleString('id-ID', { minimumFractionDigits: 1 }) }}% dari pendapatan (pendapatan − biaya).</p></div>
            </div>
            <p v-if="target" class="sx-target" :class="result.profit >= target ? 'ok' : 'bad'">Target profit {{ rupiah(target) }} {{ result.profit >= target ? 'tercapai, lebih' : 'belum tercapai, kurang' }} {{ rupiah(Math.abs(result.profit - target)) }}.<template v-if="goal"> {{ result.profit >= target ? 'Minimal' : 'Perlu minimal' }} {{ goal }} peserta {{ result.profit >= target ? 'untuk capai target' : 'pada harga ini' }}.</template></p>
            <p v-else class="sx-target">Target profit tidak diisi.</p>
            </section>
            <section class="sx-panel">
            <h3>Posisi di skala jumlah peserta</h3>
            <div class="simulation-wrap">
            <div class="simulation-scale" role="img" :aria-label="`Skala 0 sampai ${maximum} peserta. BEP ${result.bep ?? 'tidak tercapai'}, rencana ${participants}${goal ? ', target profit ' + goal : ''}.`">
                <span class="scale-loss" :style="{width:loss+'%'}"></span><span class="scale-target" :style="{width:(profit-loss)+'%'}"></span><span class="scale-profit" :style="{width:(100-profit)+'%'}"></span>
            </div>
            <span v-for="m in marks" :key="m.key" class="sim-mark" :class="[`sim-mark-${m.key}`, `sim-mark-${m.pos}`]" :style="{left:m.at+'%'}" aria-hidden="true">{{ m.label }}</span>
            </div>
            <p class="simulation-axis"><span>0 peserta</span><span>{{ maximum }} peserta</span></p>
            <div class="simulation-legend"><span class="loss">Rugi (di bawah BEP)</span><span class="target">Untung, belum capai target profit</span><span class="profit">Target profit tercapai</span></div>
            </section>
            <section class="sx-panel">
            <details><summary>Rincian perhitungan</summary>
                <ol class="sim-steps">
                    <li><b>Biaya tetap acara</b> <span class="muted">— biaya yang keluar berapa pun jumlah pesertanya (trainer, ruangan, dll.).</span><div><b>{{ rupiah(result.fixed) }}</b></div></li>
                    <li><b>Biaya per peserta</b> <span class="muted">— biaya yang bertambah setiap ada 1 peserta (modul, sertifikat, makan, dll.).</span><div><b>{{ rupiah(result.varPer) }}</b> per peserta</div></li>
                    <li><b>Total biaya (HPP)</b> <span class="muted">— biaya tetap + biaya per peserta untuk {{ participants }} peserta.</span><div>{{ rupiah(result.fixed) }} + {{ rupiah(result.varPer) }} × {{ participants }} = <b>{{ rupiah(result.hpp) }}</b></div></li>
                    <li><b>Pendapatan</b> <span class="muted">— harga jual dikali jumlah peserta.</span><div>{{ rupiah(price) }} × {{ participants }} = <b>{{ rupiah(result.pendapatan) }}</b></div></li>
                    <li><b>{{ result.profit < 0 ? 'Rugi' : 'Untung' }}</b> <span class="muted">— pendapatan dikurangi total biaya.</span><div>{{ rupiah(result.pendapatan) }} − {{ rupiah(result.hpp) }} = <b>{{ rupiah(result.profit) }}</b></div></li>
                    <li><b>Titik impas</b> <span class="muted">— jumlah peserta paling sedikit supaya tidak rugi.</span><div v-if="result.bep === null">Harga jual belum lebih besar dari biaya per peserta, jadi berapa pun pesertanya tetap rugi.</div><div v-else>{{ rupiah(result.fixed) }} ÷ ({{ rupiah(price) }} − {{ rupiah(result.varPer) }}), dibulatkan ke atas = <b>{{ result.bep }} peserta</b></div></li>
                </ol>
                <p class="sx-sub">Kalau jumlah pesertanya berubah:</p>
                <div class="table-wrap"><table><caption class="sr-only">Simulasi perubahan jumlah peserta</caption><thead><tr><th>Peserta</th><th class="money">Pendapatan</th><th class="money">Biaya (HPP)</th><th class="money">Untung / rugi</th></tr></thead><tbody><tr v-for="s in scenarios" :key="s.n" :class="{ sum: s.n === Number(participants) }"><td>{{ s.n }}{{ s.n===Number(participants)?' (rencana)':'' }}{{ s.n===result.bep?' (titik impas)':'' }}{{ s.n===goal?' (target profit)':'' }}</td><td class="money">{{ rupiah(s.revenue) }}</td><td class="money">{{ rupiah(s.cost) }}</td><td class="money" :class="s.revenue >= s.cost ? 'pos' : 'neg'">{{ rupiah(s.revenue-s.cost) }}</td></tr></tbody></table></div>
            </details>
            </section>
        </template>
    </div>
</template>

<style scoped>
.costing-simulation { margin-top: 16px; color: #16243a; font-size: 15px; line-height: 1.5; }
.sim-empty { padding: 26px; text-align: center; color: #5d6b7e; border: 1px dashed var(--navy-100); border-radius: 8px; }
.sx-panel { background: #fff; border: 1px solid #dfe5ee; border-radius: 10px; padding: 16px; margin-bottom: 16px; min-width: 0; }
.sx-panel:last-child { margin-bottom: 0; }
.costing-simulation h3 { font-size: 16px; margin: 0 0 12px; color: #16243a; }
.costing-simulation h3.gap { margin-top: 22px; }
.rowdesc { margin: -6px 0 12px; font-size: 13px; color: #5d6b7e; }
.duo { grid-template-columns: repeat(2, minmax(0, 1fr)); }
.trio { grid-template-columns: repeat(3, minmax(0, 1fr)); }
.simulation-cards > div { border: 1px solid #dfe5ee; border-top-width: 4px; border-radius: 10px; padding: 14px 16px; min-width: 0; }
.simulation-cards span { font-size: 13px; font-weight: 700; }
.simulation-cards strong { font-size: 28px; line-height: 1.2; margin: 6px 0 4px; color: #16243a; overflow-wrap: anywhere; }
.simulation-cards strong small { font-size: 14px; font-weight: 600; color: #5d6b7e; }
.simulation-cards p { margin: 0; color: #5d6b7e; }
.simulation-cards .bep { border-top-color: #b7791f; background: #fdf3df; }
.bep > span { color: #85530f; }
.simulation-cards .harga { border-top-color: #2f5d9e; background: #eaf1fb; }
.harga > span { color: #2f5d9e; }
.simulation-cards .biaya { border-top-color: #6b7788; background: #f1f3f6; }
.biaya > span { color: #4a5565; }
.simulation-cards .simulation-profit { border-top-color: #1b7a4b; background: #e6f4ec; }
.simulation-profit > span, .simulation-profit > strong { color: #1b7a4b; }
.simulation-cards .simulation-loss { border-top-color: #b42318; background: #fdecea; }
.simulation-loss > span, .simulation-loss > strong { color: #b42318; }
.sx-target { margin: 12px 0 0; padding: 10px 14px; border: 1px solid #dfe5ee; border-radius: 8px; font-size: 14px; }
.sx-target.ok { border-color: #b7dcc6; background: #e6f4ec; }
.sx-target.bad { border-color: #f2b8b2; background: #fdecea; }
.simulation-wrap { padding: 0; margin: 44px 6px 40px; }
.simulation-scale { height: 22px; border: 1px solid #dfe5ee; border-radius: 6px; overflow: hidden; }
.scale-loss { background: #f4b6b0; }.scale-target { background: #f6dc9c; }.scale-profit { background: #9fd2b3; }
.sim-mark { border: 0; border-radius: 0; background: transparent; padding: 0; text-align: center; font-size: 12px; font-weight: 700; }
.sim-mark-top { top: auto; bottom: 30px; }.sim-mark-bot { bottom: auto; top: 30px; }
.sim-mark::after { content: ''; position: absolute; left: 50%; width: 2px; background: #16243a; transform: translateX(-50%); height: 10px; }
.sim-mark-top::after { top: 100%; }.sim-mark-bot::after { bottom: 100%; }
.sim-mark-bep { color: #85530f; }.sim-mark-tp { color: #6b4e9b; }.sim-mark-plan { color: #2f5d9e; }
.simulation-axis { margin: 0 6px 12px; color: #5d6b7e; }
.simulation-legend { display: flex; flex-wrap: wrap; gap: 14px; font-size: 13px; color: #5d6b7e; }
.simulation-legend span::before { content: ''; display: inline-block; width: 12px; height: 12px; border-radius: 3px; margin-right: 6px; vertical-align: -1px; }
.simulation-legend .loss::before { background: #f4b6b0; }.simulation-legend .target::before { background: #f6dc9c; }.simulation-legend .profit::before { background: #9fd2b3; }
.sim-steps { margin: 12px 0 4px; padding-left: 22px; font-size: 14px; }
.sx-sub { margin: 16px 0 0; font-weight: 600; font-size: 14px; }
.costing-simulation summary { display: list-item; padding: 10px 0; }
table { margin-top: 10px; font-size: 14px; }th, td { padding: 8px 10px; }
th { background: #eef2f8; text-transform: none; letter-spacing: 0; font-weight: 600; }.sum td { font-weight: 700; background: #f3f6fb; }
.pos { color: #1b7a4b; }.neg { color: #b42318; }
@media (max-width: 760px) { .duo { grid-template-columns: minmax(0, 1fr); }.trio { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
@media (max-width: 480px) { .trio { grid-template-columns: minmax(0, 1fr); } }
</style>
