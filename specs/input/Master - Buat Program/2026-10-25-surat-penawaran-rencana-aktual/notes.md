# Surat penawaran dari Penjualan Program Final; laporan Rencana vs Aktual

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-25
- **Channel:** chat (jawaban atas Q-PJ-16 dan Q-PJ-17, `docs/fr-nfr-penjualan-program.md` draf 8a)
- **Contents:** jawaban verbatim, tanpa file lampiran
- **Supersedes:** Q-PJ-14 / Q-MP-07 (konsep As Is vs Aktual belum jelas, `input/Master - Buat Program/2026-10-23-revisi-rate-harga-simulasi/` butir 3) — kini laporan **Rencana vs Aktual**

## Butir yang ditanyakan (ringkas, oleh analis)

1. Di mana penawaran ke customer terjadi — Penjualan Program Final dicetak sebagai surat penawaran, atau di luar sistem? (Q-PJ-16)
2. Bila jumlah peserta fix di Quotation / SO berbeda dari rencana Final: boleh berbeda + laporan, buka ulang Penjualan Program, atau tidak boleh berbeda? (Q-PJ-17)

## Verbatim

> 1. iya saat penjualan program sudah final, maka bisa di cetak sebagai surat penawaran, di sini sistem sebagai tanda verifikasi dan trigger ke SO
> 2. kalo b2b itu karena nginduk ke 1 company maka ga akan masalah antara perbedaan peserta karena company yang mendanai. kalo public harga jual tidak akan berubah, akan berpengaruh ke profit aja dan itu gapapa. betul harus ada report/laporan perbandingan antara rencana vs aktual

## Catatan penafsiran

- Butir 1: Penjualan Program **Final** dapat **dicetak sebagai surat penawaran**. Final = tanda verifikasi di sistem dan **pemicu** pembuatan SO. Jawaban menyebut SO; untuk Public (Quotation) belum disebut apakah surat penawaran juga dicetak.
- Butir 1 berbenturan dengan `input/Master - Buat Program/2026-10-25-jalur-public-b2b/`: Company B2B dipindah ke SO, padahal surat penawaran B2B dicetak sebelum SO dan wajar ditujukan ke company. Ditanyakan ulang (Q-PJ-18), belum diubah.
- Butir 2: jumlah peserta fix **boleh berbeda** dari rencana. B2B: tidak masalah karena company yang mendanai. Public: harga jual per peserta **tidak berubah**; selisih peserta hanya memengaruhi profit, dan itu diterima.
- Butir 2: **wajib ada laporan Rencana vs Aktual**. Rencana = angka Penjualan Program Final; Aktual = peserta fix dari Quotation / SO. Komponen yang dibandingkan (peserta, pendapatan, HPP, profit) dan cara menghitung HPP aktual adalah usulan analis.

## Extracted

- PJ-FR-16, PJ-FR-20, PJ-FR-21, bagian 4.3, Q-PJ-14, Q-PJ-16 – Q-PJ-18 -> docs/fr-nfr-penjualan-program.md (draf 8b)
- MP-FR-17, Q-MP-07 -> docs/fr-nfr-master-program.md (draf 9a)
- tombol Cetak Surat Penawaran -> ui-mockup/produk/app.js
