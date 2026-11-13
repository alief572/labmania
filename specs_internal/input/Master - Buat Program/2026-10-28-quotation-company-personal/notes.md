# Quotation untuk Public & B2B, tujuan Company atau Personal

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-28
- **Channel:** chat
- **Contents:** pesan verbatim; acuan tampilan `input/labmania.html` (panel Quotation)
- **Supersedes:** `input/Master - Buat Program/2026-10-25-jalur-public-b2b/` (B2B → Sales Order) — kini Public **dan** B2B masuk ke Quotation

## Verbatim

> untuk kedua jenis penjualan (B2B atau Public) akan masuk ke modul quotation ya. nah di quotation ini ada select juga quotationnya ke company atau ke personal. kalo ke company maka ada select peserta yang di satu company yg sama. kalo personal maka select peserta aja.
>
> kamu bisa liat gamnbarannya di labmania.html ya

## Catatan penafsiran

- Penjualan Program Final, Public maupun B2B, dibuatkan Quotation.
- Quotation memilih tujuan: **Company** atau **Personal**.
  - Company: pilih Company, lalu pilih peserta yang terdaftar di company itu. Karena itu Master Peserta diberi field **Company** (opsional). `[USULAN]`
  - Personal: pilih satu peserta. `[USULAN]` (satu peserta)
- Usulan analis, mengikuti `labmania.html` dan keputusan sebelumnya:
  - B2B selalu ke Company penjualan tersebut (terkunci).
  - Harga per peserta diambil dari Penjualan Program, tanpa diskon. PPN 11% dapat dicentang.
  - Status quotation: Draft → Terkirim → Selesai, atau Batal dari Draft. Deal/Batal dicatat **per peserta** (`[JPP-15]`).
  - Peserta yang sama tidak boleh ada di dua quotation aktif untuk Penjualan Program yang sama.
  - Nomor sementara `QT-YYMM-NNN`.
- Belum dijelaskan: nasib modul Sales Order (apakah dihapus atau menjadi tahap setelah Quotation Deal).

## Extracted

- mockup -> ui-mockup/produk/app.js (draf 11l): modul Quotation, field Company di Master Peserta
- spec (QTN-FR-01 – QTN-FR-08, revisi PJ-FR-16 / bagian 4.4) — **belum ditulis**
