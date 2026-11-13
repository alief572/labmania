# Tab Data Peserta: daftar batch seperti On going, peserta dan Export Excel di detail (to be)

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-09
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes:** `input/Master - Buat Program/2026-11-09-tab-data-peserta-batch/` butir penafsiran 2 dan 3 (peserta dikelompokkan langsung di tab, Export Excel di kepala tiap kelompok).

## Verbatim

> yang data peserta maksud aku perbatch itu kayak di on going, batch nya per program, lalu pas detail baru deh data pesertanya. nah export to excelnya di dalam detail

## Catatan penafsiran

1. Tab **Data Peserta** menampilkan **daftar batch**: satu baris per Penjualan Program, seperti tab On going. Kolom: Penjualan Program, Peserta (jumlah + deal/menunggu/batal), Quotation (jumlah), Batch (On going / Selesai), Action **Lihat detail**.
2. **Lihat detail** membuka halaman **Data Peserta batch itu**: peserta dari semua quotation Penjualan Program itu, dengan filter status dan pencarian.
3. **Export Excel** ada di halaman detail itu, tidak di daftar.

## Extracted

- QTN-FR-29 -> `docs-to-be/fr-nfr-quotation.md`
- Mockup -> `ui-mockup/produk-to-be/mod-quotation.js`, `main.js`, `style.css`
