# Nomor Penjualan Program ikut rev; opsi konfirmasi ulang = ikut jadwal baru / batal ikut / refund (to be)

- **Source:** BA proyek, lewat sesi Claude Code (setelah mencoba mockup)
- **Date:** 2026-11-12
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes (khusus to be, hari yang sama):** usulan analis "tidak bisa ikut → pindah ke program lain / refund" dan Refund jenis Dialihkan / Potong tagihan (`input/Master - Buat Program/2026-11-12-jawaban-reschedule-refund-asisten/` catatan penafsiran 6); tombol Proses di kolom Konfirmasi ulang (`input/Master - Buat Program/2026-11-12-aksi-gear-cetak-peserta/`).

## Verbatim

> nah aku ada tambahan ya:
> 1. yang reschedule janghan hanya no quotation yang ada rev, tapi nomor penjualan program juga biar ga membingungkan
> 2. lalu action "proses" pada data peserta masukin ke kolom "Aksi" aja ya
> 3. apabila pada proses milih pindah jadwal maka gausah isi alasan dan pindah jadwalnya pasti ikutin ke jadwal dari penjualan program rev itu, ga geser ke program lain
> 4. di konfirmasi ulang rev 1 juga masih belum sesuai. opsi ada 3 berarti, kalai sudah bayar invoice maka ada opsi refund, kalau belum opsi ada 2 "batal ikut" dan "pindah jadwal ke reschedule (bahasanya benerin dari kamu)
>
> coba benerin dulu ya

## Catatan penafsiran

1. Nomor Penjualan Program tampil `PJP-2611-002-rev1`, naik bersama quotation dan SO, yaitu setiap reschedule yang **mengubah tanggal** `[USULAN]` (ganti trainer/asisten saja tidak menaikkan rev, sama dengan aturan quotation/SO).
2. "Pindah jadwal" = **ikut jadwal baru program yang sama** (hasil reschedule). Label di sistem: **"Ikut jadwal baru"**. Pindah ke program lain tidak ada lagi.
3. Opsi konfirmasi ulang: **Ikut jadwal baru**, **Batal ikut**, dan **Refund** hanya bila invoice peserta itu **sudah dibayar**. Belum dibayar (termasuk belum ada invoice): dua opsi saja.
4. Sistem belum mencatat pembayaran invoice (Q-MON-02). Agar opsi Refund dapat ditentukan, invoice diberi aksi **Catat Pembayaran** (tanggal bayar) `[USULAN]`, menyentuh paket Invoice - Pengajuan.
5. Proses oleh Sales memakai tiga opsi yang sama. "Ikut jadwal baru" tanpa alasan; Batal ikut / Refund tetap dengan alasan `[USULAN]`.
6. Refund hanya untuk invoice yang sudah dibayar, jadi penyelesaian Finance cukup **Transfer** (Potong tagihan dan Dialihkan dihapus).
7. Belum terjawab: peserta yang **sudah bayar tetapi memilih Batal ikut** (dana hangus menurut S&K?), dan peserta yang **batal ikut padahal invoice sudah terbit tetapi belum dibayar** (siapa menyesuaikan tagihan).

## Extracted

- `docs-to-be/fr-nfr-penjualan-program.md` PJ-FR-07, PJ-FR-27, PJ-FR-31 · `fr-nfr-quotation.md` QTN-FR-35, QTN-FR-36, UC-71, UC-72, Q-QTN-24 · `fr-nfr-sales-order.md` SO-FR-17 – SO-FR-19, UC-73, Q-SO-07 · `README.md`
- ERD to be: `penjualan_program.revisi`; `quotation_peserta_konfirmasi_ulang.keputusan` (ikut/batal/refund, tanpa `id_penjualan_tujuan`); `sales_order_refund` disederhanakan
- Mockup: `mod-program.js`, `mod-quotation.js`, `mod-sales-order.js`, `mod-invoice.js`, `konfirmasi.html`, `main.js`
