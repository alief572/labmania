# Modul Manage Peserta — permintaan + jawaban usulan; perbaikan pemilih user mockup

- **Source:** user / BA proyek Labmania, disampaikan lewat sesi Claude Code (chat)
- **Date:** 2026-11-04
- **Channel:** chat (dua pesan: permintaan awal, lalu jawaban atas usulan analis Fase 1 / Fase 2)
- **Contents:** pernyataan verbatim, tanpa lampiran
- **Supersedes:** `docs/fr-nfr-pengajuan-biaya.md` PB-FR-20 "status Closed belum ada, menyusul `[JPB-12]`" — Close Program kini ada di modul ini.

## Verbatim — permintaan

> nah aku juga mau ada penambahan modul lagi, dimana nama modulnya adalah "Manage Peserta". disini gunanaya dari setiap program akan terlist dan bisa print daftar perserta, dsbnya. aku masih kurang tau sih butuh apa aja, tapi coba deh menurut kamu di manage peserta ini gimana

## Usulan analis yang dijawab (ringkas)

- Peserta = peserta yang sudah **Deal** di Quotation; modul tidak menambah peserta.
- Halaman 1 Daftar Program: Penjualan Program Final, tab On going / Selesai; kolom No. Program, Judul, Jenis, Tanggal & Lokasi, Trainer, Peserta (Deal / Target); Action Lihat Peserta.
- Halaman 2 Peserta per Program: info program; tabel No, Kode, Nama, Instansi/Company, No. WA, Email, No. Quotation; Cetak Daftar Peserta, Cetak Daftar Hadir (per hari), Export Excel; data diperbaiki lewat Master Peserta.
- Fase 2: input kehadiran, hasil & sertifikat, rekap rencana vs aktual.
- Pertanyaan: cakupan, siapa yang pakai, format cetak, kolom status bayar.

## Verbatim — jawaban

> benar, ini setelah deal quotation.
> 1. Halaman 1 = Setuju, di action tambah juga close program
> 2. Halaman 2 = Setuju semua
>
> Fase 2: Nanti dulu ya
> Yang bisa pakai nanti aku setting sendiri ya.
> Belum ada formatnya ya. buatin dulu aja.
>
> btw ini dibagian pojok kiri bawah akun, di mock up putih warnanya jadu aku kesulitan untuk ganti-ganti user. tolong diperbaiki ya

## Catatan penafsiran

1. Cakupan = Fase 1 (Halaman 1 + Halaman 2 seperti usulan) + aksi **Close Program** di Halaman 1. Fase 2 ditunda.
2. Hak pakai diatur user sendiri lewat **Role & Permission** — menu Manage Peserta dengan aksi View (lihat, cetak, export) dan Manage (Close Program).
3. Format Daftar Peserta dan Daftar Hadir **belum ada** — dibuatkan template usulan.
4. Kolom status pembayaran (pertanyaan 4) tidak dijawab → tidak dimasukkan; "Halaman 2 = Setuju semua" merujuk ke isi Halaman 2 yang diusulkan.
5. **Close Program** mengisi status Closed yang sebelumnya "menyusul" di Pengajuan Biaya `[JPB-12]` ("selama program belum Closed"): program Closed tidak dapat diajukan biaya lagi.
6. Pemilih "Login sebagai" di mockup: teks putih di kotak putih — diperbaiki (khusus mockup, bukan kebutuhan aplikasi).

## Extracted

- `docs/fr-nfr-manage-peserta.md` draf 1 (MP-FR-01 – MP-FR-10)
- `docs/fr-nfr-pengajuan-biaya.md` draf 3c (PB-FR-20)
- ERD halaman *ERD Penjualan Program* (catatan `closed_*`)
- workbook UC-50 – UC-52, Modules
- mockup `ui-mockup/produk/` draf 14e
