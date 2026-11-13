# Jawaban reschedule Penjualan Program (to be)

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-08
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes:** tidak ada. Menjawab pertanyaan reschedule di `input/Master - Buat Program/2026-11-08-lingkup-pdf-filter-reschedule/` (poin 3).

## Pertanyaan yang dijawab (diajukan analis di chat)

1. Siapa yang boleh mengajukan reschedule, dan siapa yang menyetujui?
2. Apakah ada batas berapa kali satu program boleh di-reschedule?
3. Peserta Deal yang tidak bisa ikut di jadwal baru: dibatalkan per peserta, dipindah, atau refund?
4. Kalau invoice sudah terbit, apakah perlu diterbitkan ulang?
5. Apakah customer perlu diberi tahu otomatis, atau cukup Sales mengabari manual?

## Verbatim

> 1. sales/marketing dari penjualan program itu
> 2. belum ada, tpi apabila reschedule perlu memberikan alasan reschedule
> 3. biasanya sebelum deal akan ada reschedulenya sih, soalnya surat konfirmasi dan detail acara harus wajib perlu ikut berubah mengikuti reschedule
> 4. sama kayak poin 3 ya, jadi sebelum jauh ke invoice pasti fix tanggal dulu
> 5. sama dengan poin 3 dan 4 ya

## Catatan penafsiran

1. Reschedule dilakukan oleh **Sales/Marketing pemilik Penjualan Program** itu. Jawaban tidak menyebut pihak penyetuju, jadi dibaca **tanpa approval** (sejalan dengan PJ-FR-14 "Tidak ada approval"); perlu dikonfirmasi (Q-PJ-23).
2. **Tidak ada batas** jumlah reschedule. **Alasan wajib** diisi setiap kali.
3. Reschedule terjadi **sebelum Deal**. Formulir/surat konfirmasi dan detail acara di quotation **wajib ikut** jadwal baru. Aturan sistem: Reschedule hanya untuk Penjualan Program **Final** yang **belum punya quotation Deal** `[USULAN]` (dari "biasanya sebelum deal"); kasus mendesak setelah Deal masih terbuka (Q-PJ-22).
4. Invoice tidak terdampak, karena tanggal sudah pasti sebelum invoice.
5. Tidak ada notifikasi otomatis terpisah: customer melihat jadwal baru di quotation dan link Konfirmasi Kehadiran yang ikut berubah.
6. Usulan analis (bukan dari jawaban): yang dapat diubah = tanggal mulai, waktu, lokasi; **durasi tetap** sehingga tanggal selesai mengikuti dan costing Final tidak berubah; tanggal mulai baru tidak boleh sebelum hari ini; minimal satu isian berubah. Trainer tidak ikut diubah lewat Reschedule (Q-PJ-24).

## Extracted

- PJ-FR-26 – PJ-FR-28, UC-65, PJ-NFR-10, bagian 6, Q-PJ-22 – Q-PJ-24 -> `docs-to-be/fr-nfr-penjualan-program.md`
- ERD `penjualan_program_reschedule` -> `docs-to-be/diagrams/erd.drawio` (halaman ERD Penjualan Program)
- mockup -> `ui-mockup/produk-to-be/mod-program.js`, `main.js`, `mod-activity-log.js`
