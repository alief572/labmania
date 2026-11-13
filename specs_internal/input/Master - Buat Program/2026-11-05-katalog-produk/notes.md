# Katalog Produk — filter Bidang & Sertifikat, cetak PDF

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-05
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa file lampiran
- **Supersedes:** status FR-14 "ditunda sampai format tersedia" (Q-09) di `docs/fr-nfr.md`

## Verbatim

> kan ada menu katalog produk ya, tolong buatin deh, yang bisa di filter berdasarkan bidang dan sertifikat, lalu saat di print out bentuknya PDF yang udah bagus seperti katalog produk pada umumnya gitu

## Catatan penafsiran

1. Menu **Katalog Produk** (Master Data › Produk) tidak lagi ditunda; dibuat sekarang.
2. Filter yang diminta: **Bidang** dan **Sertifikat** (dua-duanya dapat dipakai bersamaan).
3. Hasil cetak berupa **PDF** dengan tampilan katalog produk pada umumnya. Format detail tidak diberikan klien; tata letak berikut usulan analis `[USULAN]`:
   - Sampul depan (logo, judul "Katalog Produk", tahun, jumlah paket/bidang/jenis sertifikat, filter yang dipakai, tanggal cetak).
   - Daftar Isi per Bidang dengan nomor halaman + daftar jenis sertifikat.
   - Isi: dikelompokkan per **Bidang**, tiap paket berupa kartu 2 kolom (Jenis Produk, Nomor Product Combine, Judul, Bidang, Sertifikat). Bidang yang terpotong halaman diberi judul "(lanjutan)".
   - Sampul belakang berisi kontak (Customer Care, website, alamat — sama dengan kop PDF Quotation).
4. Isi katalog = **Paket Produk berstatus Aktif** (FR-14: katalog dari Paket Produk; FR-24: hanya paket aktif yang siap dijual) `[USULAN]` (hanya aktif).
5. Yang dicetak = hasil filter yang sedang tampil (Bidang, Sertifikat, dan kata kunci cari) `[USULAN]`.
6. Pilihan filter hanya menampilkan Bidang/Sertifikat yang dipakai paket aktif, beserta jumlah paketnya, agar tidak ada pilihan yang hasilnya kosong `[USULAN]`.
7. Katalog **tidak disimpan** sebagai data/tabel baru; disusun langsung dari `paket_produk` saat dibuka/dicetak. ERD tidak berubah.
8. Aksi cetak tercatat di Activity Log (modul Master Data, sub Katalog Produk, aksi Cetak) `[USULAN]`.
9. Belum ditanyakan / masih terbuka:
   - Q-06 tetap: siapa aktor yang membuat katalog.
   - Q-36 baru: perlu tambahan isi per paket di katalog (deskripsi singkat, durasi, harga, foto)? Saat ini Paket Produk hanya punya Judul + 3 atribut, jadi hanya itu yang tampil.

## Extracted

- FR-14 (diperbarui), FR-60 – FR-63, UC-06, Q-09 (terjawab), Q-36 -> docs/fr-nfr.md (draf 11v)
- UC-06, Entities *Katalog Produk* -> docs/workbook.xlsx
- mockup -> ui-mockup/produk/app.js + style.css (menu Katalog Produk, PDF katalog), draf 14f
