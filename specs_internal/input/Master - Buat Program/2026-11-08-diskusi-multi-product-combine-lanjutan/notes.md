# Diskusi lanjutan: kombinasi Product Combine per acara bersifat acak (BELUM DIPUTUSKAN)

- **Source:** BA proyek (menyampaikan kondisi Labmania), lewat sesi Claude Code
- **Date:** 2026-11-08
- **Channel:** chat
- **Contents:** pesan verbatim + ringkasan tanggapan analis, tanpa lampiran
- **Supersedes:** melanjutkan `input/Master - Buat Program/2026-11-08-diskusi-multi-product-combine/`. Opsi B di catatan itu **gugur**.
- **Status:** **diskusi terbuka**. Belum ada perubahan dokumen, ERD, atau mockup.

## Verbatim

> soalnya kalo usulan kamu gitu, artinya setiap ada acara yang punya beberapa product combine harus dibuat masternya dulu, sedangkan, kombinasi product combine pada satu acara itu bisa random dan belum tentu bisa dibuatkan master nya..

## Catatan penafsiran

1. Kombinasi Product Combine dalam satu acara **acak / ad hoc**, tidak bisa dibakukan sebagai Master Program. Opsi B (Master Program berisi >1 Product Combine) **gugur**.
2. Penggabungan harus terjadi **di Penjualan Program** (sejalan dengan ide awal user).

## Opsi yang tersisa (usulan analis)

| Opsi | Isi | Cocok bila |
|---|---|---|
| **A versi ringan** | Satu Penjualan Program memilih **beberapa Master Program Final**; costing semua master disalin ke **satu tabel costing** dikelompokkan per produk; tetap satu simulasi, satu harga jual / peserta, satu jadwal & lokasi. Risiko: biaya acara (venue, konsumsi, biaya per hari) **terhitung ganda**; pengguna menyesuaikan karena semua nominal Penjualan Program memang dapat diedit (PJ-FR-05); sistem dapat menandai item sama dari dua master. Hilir (Quotation, SO, reschedule, invoice) hampir tidak berubah | peserta ikut semua produk, satu harga paket, satu trainer |
| **C** | Satu **Acara induk** (dibuat bebas, tanpa master) berisi beberapa Penjualan Program, masing-masing costing sendiri. Kekurangan: biaya bersama dibagi manual; satu quotation peserta bisa mencakup beberapa Penjualan Program | peserta boleh memilih sebagian, harga per produk, atau trainer berbeda per produk |

## Pertanyaan penentu (belum dijawab)

1. Peserta selalu ikut **semua produk** dalam acara itu, atau boleh **memilih sebagian**?
2. Harga **satu harga paket / peserta**, atau dirinci **per produk**?
3. Trainer **satu untuk seluruh acara**, atau **berbeda per produk**? (memengaruhi Surat Kerja Trainer, kini 1 per program)

Pertanyaan lama yang masih relevan: contoh nyata program multi produk; perlu untung-rugi per produk atau cukup per acara.

## Extracted

- Belum ada.
