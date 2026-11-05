# Acuan konsep Invoicing (dari sistem SENDIGS SS, modul konsultasi)

- **Source:** tim Labmania / BA proyek, diserahkan lewat sesi Claude Code
- **Date:** 2026-10-30
- **Channel:** file di repo + chat
- **Contents:**
  - `input/Invoice & Pengajuan/Invoice Consulting.xlsx`: 5 sheet.
    - **Requirements:** sistem invoicing Progress, tanpa DP. Jenis PPN WAPU, PKP, Kawasan Berikat. 1 SPK punya beberapa TOP, 1 TOP = 1 invoice. Tidak ada deposit/proforma. Pembalikan uang muka PPN WAPU saat bukti setor diterima. Tidak memotong PPh 23 untuk Non PKP. Jurnal dibebankan ke company depan.
    - **Definisi:** WAPU; Penjualan = nilai kontrak; DPP = Penjualan × 11/12; PPN = 12% dari DPP; Piutang per perlakuan PPN (PKP = Penjualan + PPN, WAPU & Kawasan Berikat = Penjualan).
    - **Business Process:** gambar BPMN to-be (gambar, tidak terbaca sebagai teks); tabel jurnal Progress untuk WAPU, Terima FP WAPU, PKP, dan Kawasan Berikat; tiga report (Jurnal Invoice, Actual Tagih, Uninvoiced & Piutang).
    - **Konsep:** field form Create Invoice dan sumber tiap field; perhitungan; kolom jurnal (Tanggal, Company, COA, Nama COA, Deskripsi "Nama COA - No Invoice - No SPK", Debit, Credit, Balancing).
    - **Test Case:** simulasi angka untuk WAPU (291.000.000), PKP (19.581.600), dan Kawasan Berikat (60.000.000).
  - `input/Invoice & Pengajuan/Invoice Consulting.html`: mockup halaman Finance › Invoicing (daftar SPK Uninvoiced/Invoiced, form Add Invoicing satu termin, kartu perhitungan, tabel jurnal per perlakuan PPN, pilihan COA Penjualan, Save/Back).

## Verbatim

> aku kasih inputan html dan excel terkait invoice, nah dari quotation sebelumnya next stepnya kan masuk ke invoicing. aku mau kurang lebih dari segi perhitungan dan konsep seperti file excel dan html yang ada pada inputan tersebut. boleh tolong dikerjakan?

## Catatan penafsiran

- Kedua file berasal dari **modul konsultasi** (SPK, termin/TOP, plan tagih, company STM/VUCA), bukan dari Labmania. Yang diambil adalah **perhitungan dan konsepnya** ("kurang lebih dari segi perhitungan dan konsep"). Alur hulunya diganti dengan alur Labmania: **Quotation Deal → Invoice**.
- Kode aplikasi sudah punya modul `invoicing` (`application/modules/invoicing/`). Modul itu membaca tabel konsultasi `kons_tr_actual_plan_tagih`, `kons_tr_plan_tagih_header`, dan `kons_tr_spk_penawaran`. Aplikasi juga punya tabel `tr_invoice_*` dan `jurnal`. Belum diputuskan apakah Labmania memakai tabel tersebut (lihat Q-IVC di spec).
- Kode COA di file masih sementara: `1106-01-XX`, `2104-01-XX`, dan `0000-00-00` untuk COA Penjualan.

## Extracted

- IVC-FR-01 – IVC-FR-14, IVC-NFR-01 – IVC-NFR-06, Q-IVC-01 – Q-IVC-14 -> docs/fr-nfr-invoice.md (draf 1)
