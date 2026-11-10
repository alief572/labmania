# Jawaban lanjutan reschedule: Q-PJ-22 s.d. Q-PJ-24 (to be)

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-08
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes:** tidak ada. Menjawab Q-PJ-22 – Q-PJ-24 dari `input/Master - Buat Program/2026-11-08-jawaban-reschedule/`.

## Verbatim

> PJ-22 = ini tuh ada kemungkinannya, tapi keputusannya nanti ya
> PJ-23 = iya akan diatur melalui role & permission
> PJ-24 = iya boleh
>
> nah cukup sampai situ dulu ya reschedule nya, ada lagi request ya mau aku diskusiin

## Catatan penafsiran

1. **Q-PJ-22:** reschedule setelah Deal mungkin terjadi, tetapi aturannya diputuskan nanti. Tetap terbuka (ditunda); sistem to be tetap menolak reschedule setelah Deal sampai ada keputusan.
2. **Q-PJ-23:** terjawab. Reschedule tanpa approval; hak Reschedule diatur lewat **Role & Permission** (aksi tersendiri di modul Penjualan Program).
3. **Q-PJ-24:** terjawab. **Trainer boleh diganti** saat reschedule (opsional, dari Master Trainer aktif). Trainer lama dan baru ikut tercatat di riwayat.
4. Pembahasan reschedule dihentikan sampai di sini atas permintaan user.

## Extracted

- PJ-FR-26, PJ-FR-28, PJ-NFR-07, Q-PJ-22 – Q-PJ-24 -> `docs-to-be/fr-nfr-penjualan-program.md`
- ERD `penjualan_program_reschedule.id_trainer_lama`, `id_trainer_baru` -> `docs-to-be/diagrams/erd.drawio`
- mockup -> `ui-mockup/produk-to-be/mod-program.js`
