# Dashboard: jadwal hanya Final; KPI total

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-28
- **Channel:** chat (masukan atas mockup draf 11h)
- **Contents:** pesan verbatim, tanpa file lampiran
- **Supersedes:** isi panel jadwal di `input/Master - Buat Program/2026-10-28-dashboard-depan/` (Draft/Final → Final saja)

## Verbatim

> untuk dashboard:
> 1. jadwal pelaksanaan terdekat yang sudah final aja
> 2. card-card penjualan program final, pendapatan rencana, profit rencana, masih draft. itu in total ya. lalu bedanya pendapatan rencana dan profit rencana tuh apa ya?

## Catatan penafsiran

- 1: Jadwal pelaksanaan terdekat hanya memuat Penjualan Program **Final**; kolom Status dihapus (semua Final).
- 2: keempat kartu adalah **total** seluruh data (tanpa filter periode); label diberi kata "Total". Pertanyaan beda pendapatan vs profit dijawab di chat: pendapatan = harga jual / peserta × target peserta; profit = pendapatan − HPP (fixed + variable cost). Keterangan singkat ditambahkan di kartu.

## Extracted

- FR-41, FR-42 -> docs/fr-nfr.md (draf 11f)
- Dashboard -> ui-mockup/produk/app.js (draf 11i)
