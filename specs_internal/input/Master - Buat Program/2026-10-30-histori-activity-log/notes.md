# Jejak "dibuat oleh / tanggal & jam" di semua data + modul Activity Log

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-30
- **Channel:** chat
- **Contents:** pesan verbatim
- **Supersedes:** — (menambah; NFR audit `write_log()` di tiap modul tetap berlaku)

## Verbatim

> oh iya ada tambahan pada setiap membuat sesuatu, di master data, di master program, penjualan program, dan quotation. aku mau ada historicalnya, kayak "created by", "created date & hours" gitu-gitu. lebih baik lagi kalo ada 1 modul khusus untuk melihat pergerakan di sistem. kegiatan apapun, menghapus, mengedit, menambah, lengkap semuanya ada historicalnya ya.
>
> update ke mock up, FR & NFR, ERD, Use case, dan Flow. update juga ke folder final ya

## Catatan penafsiran

- **Jejak per data** di Master Data, Master Program, Penjualan Program, dan Quotation: **Dibuat oleh**, **tanggal & jam dibuat**; ditambah **Diubah terakhir oleh** dan tanggal & jam diubah `[USULAN]` (bagian dari "historical"). Ditampilkan di setiap daftar (kolom "Dibuat / Diubah") `[USULAN]`.
- **Modul khusus Activity Log**: satu menu untuk melihat **semua pergerakan** di sistem — tambah, edit, hapus, aktif/nonaktif, finalkan, batalkan, kirim & cetak, deal, cetak dokumen `[USULAN]` (cetak & aksi status termasuk "kegiatan apapun"). Tiap catatan: waktu (tanggal & jam), pengguna, modul, aksi, data (kode/nomor), dan field yang berubah **sebelum → sesudah**.
- Riwayat **satu data** dapat dibuka dari menu gear "Lihat Riwayat" `[USULAN]`.
- Catatan log tidak dapat diubah/dihapus `[USULAN]`.
- Kolom basis data mengikuti konvensi aplikasi: `created_by`, `created_date`, `updated_by`, `updated_date`, `deleted_by`, `deleted_date` `[USULAN]`.
- Diminta memperbarui mockup, FR & NFR, ERD, use case, flow, dan folder final.

## Extracted

- FR & NFR baru `docs/fr-nfr-activity-log.md` (LOG-FR-01 – LOG-FR-08, LOG-NFR-01 – LOG-NFR-05); rujukan di NFR audit tiap modul
- ERD halaman *ERD Activity Log* (tabel `logs` + pola kolom jejak); catatan halaman ERD lain
- UC-38, UC-39, modul Activity Log, entity -> docs/workbook.xlsx
- Flow: Level 0 kotak Activity Log + halaman Level 1 — Activity Log -> docs/diagrams/bpmn-to-be.drawio
- mockup draf 12 -> ui-mockup/produk/
- salinan -> specs/final/
