# Paket final

Satu folder per paket. Setiap paket berisi FR & NFR, ERD, Flow Process, Use Case, dan Mock Up miliknya
sendiri, disalin dari file kerja (`docs/`, `docs/diagrams/`, `docs/workbook.xlsx`, `ui-mockup/produk/`).

| Paket | Modul | Menu umum ikut tampil |
|---|---|---|
| `Master - Quotation/` | Master Data, Master Program, Penjualan Program, Quotation | Master User, Master Role, Role & Permission, Activity Log |
| `Invoice - Pengajuan/` | Invoice, Manage Peserta, Pengajuan Biaya | Master User, Master Role, Role & Permission, Activity Log |

## Membangun ulang

Hanya paket yang disebut yang ditulis; paket lain tidak berubah.

```
py -3 final/build_final.py "Master - Quotation"
py -3 final/build_final.py "Invoice - Pengajuan"
```

Isi tiap paket (dokumen FR, halaman ERD dan flow, modul use case, menu mockup) diatur di `PAKET` dalam
`build_final.py`. Menu mockup per paket diatur di `PAKET_MENU` dalam `ui-mockup/produk/core.js`.

## Catatan

- Mockup paket memuat semua file modul karena antarmodul saling memakai data (Invoice membaca Quotation
  Deal, Pengajuan Biaya membaca Penjualan Program). Sidebar dan Role & Permission hanya menampilkan menu
  paket itu dan menu umum. Dashboard tetap sama untuk semua paket.
- Invoice belum punya halaman ERD (menunggu Q-IVC-01) dan use case-nya belum masuk workbook.
- Paket sebelum dipisah: `input/backup/final_sebelum-pisah-paket_05-11-2026/`.
