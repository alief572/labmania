/**
 * Rumus costing Master Program untuk tampilan simulasi. Server (app/Support/Costing.php) menghitung ulang saat
 * simpan dan Finalkan; tes PHP membandingkannya dengan CI3. Port dari lm-costing.js (CI3).
 */
export type Biaya = { tipe: 'fixed' | 'variable'; satuan: string | null; harga: number | string };

export const rupiahInput = (v: number | string) => Number(typeof v === 'string' ? v.replace(/\./g, '') : v) || 0;

const num = (v: unknown) => Number(v) || 0;
const isDay = (satuan: string | null) => satuan === 'Day' || satuan === 'Peserta/Day';

export const costQuantity = (tipe: Biaya['tipe'], satuan: string | null, durasi: unknown, peserta: unknown) =>
    (isDay(satuan) ? num(durasi) || 1 : 1) * (tipe === 'variable' ? num(peserta) : 1);

// Same display ceiling and first profitable participant search as mod-program.js::simView.
export function profitParticipants(fixed: number, variable: number, price: number, target: number): number | null {
    if (target <= 0 || price <= variable) return null;
    for (let n = Math.max(1, Math.ceil((fixed + target) / (price - variable)) - 2); n <= 100000; n++) {
        if (price * n - (fixed + variable * n) >= target) return n;
    }
    return null;
}

export function calc(durasi: unknown, targetPeserta: unknown, hargaJual: unknown, biaya: Biaya[]) {
    const d = num(durasi) || 1;
    const n = num(targetPeserta);
    const harga = num(hargaJual);
    let fixed = 0;
    let varPer = 0;
    for (const b of biaya) {
        const sub = num(b.harga) * (isDay(b.satuan) ? d : 1);
        if (b.tipe === 'fixed') fixed += sub;
        else varPer += sub;
    }
    const totalVar = varPer * n;
    const hpp = fixed + totalVar;
    const pendapatan = harga * n;
    const profit = pendapatan - hpp;
    const kontribusi = harga - varPer;
    const bep = kontribusi > 0 ? Math.max(1, Math.ceil(Math.round((fixed / kontribusi) * 1e6) / 1e6)) : null;
    return { fixed, varPer, totalVar, hpp, pendapatan, profit, bep, hppPerPax: n > 0 ? hpp / n : 0, margin: pendapatan > 0 ? Math.round((profit / pendapatan) * 1000) / 10 : 0 };
}

export const rupiah = (v: number | string | null | undefined) =>
    v === null || v === undefined || v === '' || Number.isNaN(Number(v)) ? '—' : (Number(v) < 0 ? '−' : '') + 'Rp ' + Math.abs(Math.round(Number(v))).toLocaleString('id-ID');
