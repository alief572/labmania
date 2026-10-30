# Activity Log: tanpa login/logout, simpan 60 hari, akses Manajemen & Admin Developer, tampil di daftar + modul khusus

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-30
- **Channel:** chat — jawaban atas Q-LOG-01 – Q-LOG-05 (`docs/fr-nfr-activity-log.md` draf 1)
- **Contents:** pesan verbatim
- **Supersedes:** usulan analis draf 1 "log disimpan permanen" (Q-LOG-03) — kini 60 hari.

## Verbatim

> 1. efeknya apa kalo buat baru? dan kalo ambil dari proyek lama tuh gmn maksudnya?
> 2. login logut gausah dulu
> 3. log disimpan selama 60 hari
> 4. iya manajemen dan akun admin developer
> 5. tampil di daftar dan di modul khusus historycal

## Catatan penafsiran

- 1: pertanyaan balik, bukan keputusan — dijelaskan di sesi (helper `write_log()` / `log_helper.php` dan tabel log yang disebut `AGENTS.md` tidak ada di kode Labmania; dapat dibuat baru atau disalin dari aplikasi Sentral lain yang sudah memakainya). Q-LOG-01 tetap terbuka.
- 2: **login / logout tidak dicatat** (untuk saat ini).
- 3: log **disimpan 60 hari**; yang lebih lama dihapus otomatis oleh sistem `[USULAN]` (cara: job harian). Ekspor tidak disebut — tetap usulan tanpa ekspor.
- 4: Activity Log dan "Lihat Riwayat" hanya untuk group **Manajemen** dan **Admin Developer** (group akses baru di Master User). Kolom "Dibuat / Diubah" di daftar tetap terlihat semua pengguna `[USULAN]`.
- 5: jejak tampil **di daftar** (kolom Dibuat / Diubah) dan **di modul khusus** Activity Log — sesuai draf 1.

## Extracted

- LOG-FR-04, LOG-FR-08, LOG-FR-09 (baru), LOG-NFR-03, LOG-NFR-06 (baru), UC-38, UC-39, bagian 7 -> docs/fr-nfr-activity-log.md (draf 2)
- Group Akses "Admin Developer" -> docs/fr-nfr.md FR-36 / Q-25
- UC-38, UC-39 -> docs/workbook.xlsx; catatan ERD Activity Log & Flow Level 1 — Activity Log
- mockup draf 12a; salinan final
