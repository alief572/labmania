# Revisi: Master Rate tanpa mode harga, skema harga B2B, hapus As Is vs Aktual, simulasi lebih mudah dibaca

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-23
- **Channel:** chat (review atas mockup draf 7)
- **Contents:** jawaban verbatim, tanpa file lampiran
- **Supersedes:**
  - `input/Master - Buat Program/2026-10-23-klarifikasi-program/` butir 3 — bagian "mode harga" (Default / Fix / Input manual) yang disetujui analis; *Catatan Sentral* di *Cost Master* tidak lagi dipakai sebagai mode
  - `input/Master - Buat Program/2026-10-23-arah-penjualan-program/` butir 3 sebagaimana ditafsirkan analis (As Is = Master Program vs Penjualan) — tafsiran itu salah dan fiturnya dihapus
  - `input/Master - Buat Program/2026-10-23-requirements-3-modul/` `[QT-7]` — harga paket all-in tidak wajib untuk B2B

## Verbatim

1. di master rate = gausah ada select fix, default, dan input. semua default aja tapi bisa edit
2. di penjualan program = gausah harus input harga jual all in, lebih ke peserta aja, harga jual all in itu opsi ajaaa jadi mau jual all in atau per peserta gitu.
3. as is vs aktual tuh bukan gitu konsepnya, hapus dulu aja gausah ada dulu.
4. simulasi dibuat lebih mudah dibaca dan dipahami ya

## Catatan penafsiran

- Butir 1: Master Rate tidak punya field Mode Harga. Setiap item punya Default Cost yang **selalu bisa diedit** saat costing (Master Program dan Penjualan Program). Item yang sebelumnya bermode "Input manual" (Seminar Kit, Sertifikasi LSP, Ongkir Dokumen) memakai Default Cost dari *Cost Master* (35.000 / 0 / 15.000).
- Butir 2: berlaku untuk B2B (Public memang sudah per peserta). B2B punya pilihan **skema harga**: **Per peserta** (default — harga jual / peserta × jumlah peserta) atau **All-in** (harga paket). Diskon dan jumlah peserta minimal 10 tetap berlaku untuk kedua skema.
- Butir 3: semua perbandingan As Is vs Aktual/Penjualan dihapus — dari form, dari laporan (UC-28), dan dari menu. Konsep yang benar belum dijelaskan; dicatat sebagai pertanyaan.
- Butir 4: bagian Simulasi ditata ulang (usulan analis): ringkasan "Pendapatan − HPP = Profit", kalimat sederhana untuk titik impas dan harga minimal, lalu tabel rincian perhitungan langkah demi langkah. Grafik dan batang yang membingungkan dihapus.

## Extracted

- FR-19, FR-20, FR-26, FR-33 (dihapus), NFR-12, UC-08, UC-09, lampiran -> docs/fr-nfr.md (draf 8)
- MP-FR-08, MP-FR-10, MP-FR-17 -> docs/fr-nfr-master-program.md (draf 4)
- docs/fr-nfr-penjualan-program.md (draf 4): skema harga B2B, As Is dihapus, UC-28 dihapus
- ERD: `ms_rate.mode_harga` dihapus, `penjualan_program.skema_harga` baru -> docs/diagrams/erd.drawio
