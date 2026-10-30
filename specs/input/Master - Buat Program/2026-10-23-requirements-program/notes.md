# Requirements Product rev (1) dan Requirements Program

- **Source:** tim Labmania / BA proyek
- **Date:** 2026-10-23
- **Channel:** file Excel diletakkan di `input/` (di luar folder intake) lalu diberitahukan lewat sesi Claude Code
- **Contents:** dua file, dibiarkan di lokasi asal agar tidak mengubah `input/`:
  - `input/Product - Labmania rev (1).xlsx` — revisi `input/Product - Labmania.xlsx`
  - `input/Program - Labmania.xlsx` — file baru
- **Supersedes:** `input/Product - Labmania.xlsx` sheet *Requirements Product* (sheet *Master Data* dan *Data Mentah Struktur Product* tidak berubah)

## Isi

### Product - Labmania rev (1).xlsx — sheet *Requirements Product*

- Kolom baru **"Berlaku Untuk: Public atau B2B"**. Butir 1–6 (struktur produk, atribut, combine) = Semua.
- **REQ-7 Master Peserta** (Public): data personel/peserta, menjadi customer pada program public dan dipilih saat membuat quotation. Form: Nama (wajib), Gender, Instansi/perusahaan (wajib), Bidang (wajib, dari Jenis Bidang), Provinsi (wajib), Kota/kabupaten, No. telp/WhatsApp, Email.
- **REQ-8 Master Company** (B2B): data perusahaan, menjadi customer pada program B2B dan dipilih sejak program dibuat. Form: identitas (nama wajib, jenis instansi: Swasta, BUMN/BUMD, Pemerintah, Pendidikan, Lainnya), alamat (alamat lengkap, provinsi, kota), kontak kantor (telepon, email), PIC (nama, jabatan, telp/WA, email), pajak (NPWP).
- Wishlist dan SIPOC tidak berubah.

### Program - Labmania.xlsx

- Sheet *Requirements Costing*: bagian 1 butir 1–29 (Program, Public vs B2B, status, konfirmasi, biaya, satuan, simulasi, realisasi), bagian 2 Pain Point (kosong), bagian 3 Wishlist (simulasi profit, simulasi BEP), bagian 4 SIPOC.
- Sheet *Cost Master*: daftar item biaya per kelompok (Fix Cost / Event, Fix Cost / Day & Hours, Variable Cost / Peserta / Event, Variable Cost / Peserta / Day) dengan kolom Kategori, Satuan, Default Cost, Keterangan, Aktif, dan *Catatan Sentral*.
- Sheet *Product Costing*: simulasi 6 skenario (Hari, Target Peserta, Harga Jual) dengan rumus Fixed, Variable, HPP, Pendapatan, Profit, Harga BEP/Peserta.

Penanda sumber yang dipakai di dokumen spec:

| Penanda | Arti |
|---|---|
| `[REQ-7]`, `[REQ-8]` | *Product rev (1)*, sheet *Requirements Product*, bagian 1 No 7 / 8 |
| `[PRG-n]` | *Program*, sheet *Requirements Costing*, bagian 1 No n |
| `[PRG-WISH-n]` | *Program*, sheet *Requirements Costing*, bagian 3 No n |
| `[PRG-SIPOC]` | *Program*, sheet *Requirements Costing*, bagian 4 |
| `[CM]` | *Program*, sheet *Cost Master* |
| `[PCS]` | *Program*, sheet *Product Costing* (simulasi) |

## Temuan saat membaca

- *Cost Master* D39 `=SUM(D5:D18)` ikut menjumlahkan subtotal D11 (biaya event terhitung dua kali).
- *Cost Master* baris 5 dan 6 sama-sama "Transport Trainer Luar Kota PP" (baris 6 tanpa Default Cost).
- *Cost Master* Mandays Rate Charge dan Honor Trainer bersatuan "Jam".
- Wishlist No 1 menulis Profit = Pendapatan − (Fixed − Variable); sheet *Product Costing* memakai Pendapatan − (Fixed + Variable).

Koreksinya diputuskan di `input/Master - Buat Program/2026-10-23-klarifikasi-program/`. File Excel di sini tidak diubah.

## Extracted

- REQ-7, REQ-8, `[CM]` -> docs/fr-nfr.md (draf 4, modul Master Data)
- PRG-1 – PRG-29, PRG-WISH-1/2, PRG-SIPOC, PCS -> docs/fr-nfr-program-penjualan.md (draf 1)
- UC-11 – UC-18 -> docs/workbook.xlsx
- ERD -> docs/diagrams/erd.drawio (halaman Master Data dan Program Penjualan)
