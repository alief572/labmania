import { appUrl } from './appUrl';
import { ref } from 'vue';
import { rupiah } from './costing';
import { useConfirmedMutation } from './confirmedMutation';

type Summary = { id: number; nomor: string; status: string; judul: string; durasi: number; target: number; harga: number; jp: number | null; hpp: number; profit: number; bep_peserta: number | null; warnings: string[]; jumlah_biaya: number; n_penjualan: number };

/**
 * Finalkan / Batalkan Master Program: muat ringkasan (MP-FR-12) lalu minta konfirmasi SweetAlert2.
 * Dipakai daftar dan form agar teks dan urutannya sama; server tetap memeriksa hak dan status.
 */
export function useProgramTransition(confirmation: ReturnType<typeof useConfirmedMutation>) {
    const busy = ref(false);
    const error = ref('');
    async function ask(id: number, action: 'finalkan' | 'batalkan') {
        if (busy.value || confirmation.pending.value) return;
        busy.value = true;
        error.value = '';
        try {
            const res = await fetch(appUrl(`/program/${id}/ringkasan`), { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error('Ringkasan program gagal dimuat. Muat ulang data dan coba lagi.');
            const summary: Summary = await res.json();
            await confirmation.visit('post', `/program/${id}/${action}`, {}, {
                title: `${action === 'finalkan' ? 'Finalkan' : 'Batalkan'} ${summary.nomor}?`,
                text: `${summary.judul}\n${summary.durasi} hari / ${summary.jp ?? 'belum diisi'} JP; target ${summary.target} peserta. Harga ${rupiah(summary.harga)}, HPP ${rupiah(summary.hpp)}, profit ${rupiah(summary.profit)}, BEP ${summary.bep_peserta ?? 'tidak tercapai'}.\n${summary.warnings.join('\n')}\n${action === 'finalkan' ? 'Setelah Final, program terkunci dan tersedia di Penjualan Program.' : 'Program yang dibatalkan tidak dapat dibuka kembali.'}`.trim(),
                confirmLabel: action === 'finalkan' ? 'Ya, finalkan' : 'Ya, batalkan', danger: action === 'batalkan',
            }, { preserveScroll: true });
        } catch (failure) { error.value = failure instanceof Error ? failure.message : 'Ringkasan program gagal dimuat.'; }
        finally { busy.value = false; }
    }
    return { ask, busy, error };
}
