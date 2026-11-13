# Penjualan Program tanpa diskon (sementara)

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-23
- **Channel:** chat (review mockup)
- **Contents:** jawaban verbatim, tanpa file lampiran
- **Supersedes:** `input/Master - Buat Program/2026-10-23-arah-penjualan-program/` butir 2 ("B2B juga ada diskon") — untuk sementara

## Verbatim

> di modul daftar penjualan program, B2B atau Public gausah ada diskon dulu

## Catatan penafsiran

- Penjualan Program (Public dan B2B) **tanpa diskon** untuk saat ini ("dulu" = ditunda, bukan dibatalkan). Public memang sudah tanpa diskon di modul ini; yang dihapus adalah diskon B2B (field, perhitungan, kolom ERD).
- Diskon per peserta untuk Public tetap tercatat sebagai bagian modul Quotation yang ditunda (`input/Master - Buat Program/2026-10-23-arah-penjualan-program/` butir 5).
- Pendapatan B2B = harga jual / peserta × jumlah peserta (per peserta) atau harga paket (all-in), tanpa pengurang.

## Extracted

- PJ-FR-12, PJ-FR-13, PJ-FR-15, PJ-NFR-06, UC-23, UC-29, Q-PJ-13 -> docs/fr-nfr-penjualan-program.md (draf 7)
- ERD: `penjualan_program.diskon_tipe`, `diskon` dihapus -> docs/diagrams/erd.drawio
- mockup -> ui-mockup/produk/app.js
