# Jawaban Labmania: harga mengikuti peserta aktual, quotation direvisi (to be)

- **Source:** Labmania (jawaban ke BA proyek), diteruskan BA lewat sesi Claude Code
- **Date:** 2026-11-11
- **Channel:** chat
- **Contents:** pertanyaan BA dan jawaban Labmania, verbatim, tanpa lampiran
- **Menjawab:** Q-TB-01 (`docs-to-be/fr-nfr-quotation.md`); menyentuh Q-TB-08 (peserta tambahan saat link tertutup).

## Verbatim

Pertanyaan BA:

> 1. Kuota quotation 5 peserta, tetapi yang konfirmasi/ikut hanya 3. Harga di Penawaran, Sales Order, dan Invoice dihitung dari 5 atau 3?
> 2. Kalau Penawaran untuk 5 orang sudah terkirim, perlu dicetak ulang?
> 3. Kuota quotation 5 peserta, tetapi aktualnya ada 7 peserta. Harga di Penawaran, Sales Order, dan Invoice dihitung dari 5 atau 3?

(Catatan: pertanyaan 3 menulis "5 atau 3"; dari konteks yang dimaksud "5 atau 7".)

Jawaban Labmania:

> 1. Di hitung 3, karena Quotation akan direvisi ulang
> 2. Betul, perlu di cetak ulang
> 3. Quotation akan disesuaikan dengan aktualnya. Sehingga jika ada perubahan peserta akan direvisi ulang mulai dari Penawaran, SO dan Invoice

## Catatan penafsiran

Belum diterapkan ke spec; masih didiskusikan dengan BA (sesi 11-11-2026). Yang sudah jelas: harga Penawaran, SO, dan Invoice mengikuti **jumlah peserta aktual**, dan Penawaran yang sudah terkirim **direvisi lalu dicetak ulang**. Yang belum jelas: mekanisme revisi (nomor, kapan, siapa), cara peserta tambahan masuk saat kuota penuh, dan perubahan peserta setelah Deal (SO dan Invoice sudah ada).
