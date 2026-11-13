# Requirements 3 modul: Master Data, Master Program, Penjualan Program

- **Source:** tim Labmania / BA proyek
- **Date:** 2026-10-23
- **Channel:** 3 file Excel diletakkan di `input/` lalu dijelaskan lewat sesi Claude Code
- **Contents:** file dibiarkan di lokasi asal agar tidak mengubah `input/`:
  - `input/Master Data - Labmania.xlsx` — sheet *Requirements Master Data*, *Master Data* (mockup form), *Data Mentah Struktur Product*, *Data Mentah Master Rate*
  - `input/Master Program - Labmania.xlsx` — sheet *Requirements Program* (termasuk bagian 5 Open Points), *Cost Master*, *Simulasi Costing*
  - `input/Penjualan Program - Labmania.xlsx` — sheet *Requirements Quotation*, *Field Quotation*
- **Supersedes:**
  - `input/Master - Buat Program/2026-10-23-requirements-program/` — *Program - Labmania.xlsx* seluruhnya (Program Public/B2B dengan status Final/Deal/Selesai diganti Master Program generik + Quotation) dan *Product rev (1)* REQ-7, REQ-8
  - `input/Master - Buat Program/2026-10-23-layout-program-public/` — tetap berlaku sebagai acuan layout, sekarang untuk form Master Program dan Quotation

## Verbatim (penjelasan user)

> aku ada input excel baru, ada 3:
> 1. Master Data - Labmania = itu requirement untuk atribut pada product combined dan master program
> 2. Master Progam - Labmania = itu requirement untuk membuat program master yang bisa ditarik/select saat buat proposal/quotation (bingung namanya) intinya jalurnya terpisah antara b2b dan public saat setelah master program. ini kayak buat program aja, hanya udah ga ada select tanggal dan isi lokasi acara (itu ada disaat penjualan program)
> 3. Penjualan Program - Labmania = nah ini yang buat proposal/quotation. disini bisa tarik/select dari master program. disini bisa edit biaya, target peserta, harga jual, dsbnya semua bisa di edit ya
>
> coba deh pelajari dan benerin lagi

Penanda sumber di dokumen spec:

| Penanda | Arti |
|---|---|
| `[MD-n]` | *Master Data*, sheet *Requirements Master Data* bagian 1 No n |
| `[MD-FORM:<blok>]` | *Master Data*, sheet *Master Data* (mockup form) blok yang disebut |
| `[MD-RATE]` | *Master Data*, sheet *Data Mentah Master Rate* |
| `[MP-n]` | *Master Program*, sheet *Requirements Program* bagian 1 No n |
| `[MP-WISH-n]`, `[MP-OP-n]`, `[MP-SIPOC]` | *Master Program*, Wishlist No n / Open Points No n / SIPOC |
| `[MP-SIM]` | *Master Program*, sheet *Simulasi Costing* |
| `[QT-n]` | *Penjualan Program*, sheet *Requirements Quotation* bagian 1 No n |
| `[QT-FIELD]` | *Penjualan Program*, sheet *Field Quotation* |
| `[QT-SIPOC]` | *Penjualan Program*, SIPOC |
| `[USR-3M]` | penjelasan verbatim user di atas |

## Catatan penafsiran

- Struktur menjadi **tiga modul**: Master Data → Master Program → Penjualan Program (Quotation). Public/B2B tidak lagi dipilih di program, tetapi di Quotation `[MP-1]`, `[QT-2]`.
- Tanggal dan lokasi acara diisi di Penjualan Program, bukan di Master Program `[USR-3M]`. Field tanggal/lokasi tidak ada di sheet *Field Quotation*; nama dan formatnya diusulkan analis.
- Nama "Quotation" dipakai untuk dokumen penjualan (sheet *Requirements Quotation*); nama menu masih ditanyakan.
- Bertentangan dengan keputusan chat 23-10-2026 (`input/Master - Buat Program/2026-10-23-klarifikasi-program/`) dan **tidak** langsung diterapkan:
  - Satuan **Hours** (per jam) masih ada di `[MD-8]` dan `[MP-13]`, dan *Simulasi Costing* mengalikan Mandays Rate Charge & Honor Trainer dengan jumlah hari (`[MP-OP-1]`). Chat: "hours itu jatohnya sama kayak /event". Spec tetap memakai Event sampai dikonfirmasi.
  - `[MP-OP-4]` (rate B2B vs Public) sudah dijawab di chat: satu item satu Default Cost.
  - Master Rate di mockup Excel masih Edit/Delete; chat menyetujui nonaktif tanpa hapus.

## Extracted

- `[MD-*]` -> docs/fr-nfr.md (draf 5)
- `[MP-*]` -> docs/fr-nfr-master-program.md (draf 1)
- `[QT-*]` -> docs/fr-nfr-penjualan-program.md (draf 1); menggantikan docs/fr-nfr-program-penjualan.md (backup di input/backup/)
- UC-19 – UC-28 -> docs/workbook.xlsx
- ERD -> docs/diagrams/erd.drawio (halaman Master Data, Master Program, Penjualan Program)
