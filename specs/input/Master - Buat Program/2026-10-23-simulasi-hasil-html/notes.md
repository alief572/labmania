# Acuan tampilan Simulasi Hasil dari simulasi_hasil.html

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-23
- **Channel:** file HTML diletakkan di `input/` lalu diberitahukan lewat chat
- **Contents:** file dibiarkan di lokasi asal agar tidak mengubah `input/`:
  - `input/simulasi_hasil.html` (23-10-2026 15:05) — prototipe tampilan Simulasi Hasil dengan data contoh (`COST` statis)
- **Supersedes:** tata letak simulasi draf 8/9 (`input/Master - Buat Program/2026-10-23-revisi-rate-harga-simulasi/` butir 4, `input/Master - Buat Program/2026-10-23-simulasi-bep/`) — isinya tetap, tampilannya diganti

## Verbatim

> aku ada input simulasi hasil html, aku mau tampilannya seperti itu di mock up, tolong update ya

## Isi file (ringkas)

1. **Titik impas (BEP)** — dua kotak: *BEP jumlah peserta* = Fixed ÷ (harga − variable per peserta), dibulatkan ke atas; *BEP harga jual* = HPP ÷ rencana peserta. Teks: "Batas minimal supaya tidak rugi. Tidak tergantung rencana, hanya tergantung biaya."
2. **Hasil rencana simulasi** — tiga kotak: Pendapatan, Biaya (HPP), Profit (untung/rugi, margin % dari pendapatan); baris **target profit**: tercapai/kurang, dan **minimal peserta untuk capai target** = ⌈(Fixed + target profit) ÷ (harga − variable per peserta)⌉.
3. **Posisi di skala jumlah peserta** — batang berwarna: rugi (di bawah BEP), untung tetapi belum capai target, target tercapai; penanda BEP, Target profit, Rencana.
4. **Rincian perhitungan** (dapat dibuka/tutup) — tabel komponen / cara hitung / nilai (termasuk "sumbangan per peserta"), dan tabel profit menurut jumlah peserta di sekitar BEP dan rencana.

Warna file memakai hijau (untung), merah (rugi), kuning tua (BEP), biru (pendapatan).

## Catatan penafsiran

- Diikuti: susunan, teks, warna semantik, dan rumus tambahan dari file (margin %, minimal peserta untuk target profit). Input simulasi tetap di bagian 1 form (kotak kuning yang sudah ada).
- Data biaya diambil dari costing form (bagian 2 dan 3), bukan konstanta `COST` di file.
- B2B: skema per peserta memakai harga bersih per peserta setelah diskon. Skema all-in (tidak ada di file): kotak BEP menjadi *harga paket minimal* (= HPP) dan *maksimal peserta agar tidak rugi*; skala dibalik (untung di jumlah peserta kecil, rugi di atas batas) — usulan analis.

## Extracted

- MP-FR-10 -> docs/fr-nfr-master-program.md (draf 6)
- PJ-FR-10 -> docs/fr-nfr-penjualan-program.md (draf 6)
- mockup `simView` -> ui-mockup/produk/app.js, style.css
