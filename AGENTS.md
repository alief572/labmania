# Laravel Labmania

Laravel 13 / PHP 8.5, Inertia 3 / Vue 3 / TypeScript / Vite. Root CI3 tetap terpisah; jalankan dependency manager dari folder `laravel/`.

Sumber skema: `../specs/docs-to-be/fondasi-laravel.md` dan ERD to-be. Fase 1–3 tersedia; salinan development sudah diimpor ke `labmania_laravel`. Tidak ada migrasi default users atau seed akun contoh. Rehearsal data produksi dan cutover belum dijalankan.

Pemeriksaan dari folder `laravel/` dengan PHP 8.5 di PATH:

```powershell
php vendor/phpunit/phpunit/phpunit
php vendor/phpunit/phpunit/phpunit -c phpunit.mysql.xml --do-not-cache-result
node tests/frontend/phase3.mjs
node tests/frontend/phase4.mjs
npm.cmd run build
php artisan route:list --except-vendor
```

PHPUnit default memaksa APP_ENV=testing, DB_CONNECTION=sqlite, DB_DATABASE=:memory:, DB_URL kosong. Konfigurasi `phpunit.mysql.xml` memaksa `labmania_laravel_test`; guard menolak database tanpa akhiran `_test`. Suite MySQL membangun ulang tabel fixture: jangan jalankan dua proses pada DB test yang sama. Jangan memakai database aplikasi/CI3 untuk test, mengganti guard test agar lolos, atau menjalankan migrate/fresh/seed pada database lama.

`write_log()` memakai tujuh argumen dan `logs` existing. Update menyimpan old sebelum perubahan, new sesudahnya dan detail_changes untuk child. Audit internal wajib akun sah. Fase 3 membuat `logs.created_by` nullable hanya pada DB Laravel; `Audit::publicConfirmation()` menetapkan `actor_type=publik` di server, tanpa menyamar sebagai admin. Password/secret/token tidak masuk log/props umum.

Environment, session cookie, cache, storage dan APP_KEY terpisah. Jangan menghubungkan `.env` Laravel ke DB aktif CI3. Dokumen tidak diterbitkan lewat public/storage; endpoint unduh memeriksa hak dan batas path.

Fase 4: `SalesOrderController`/`InvoiceController`, support `SalesOrder`/`Invoice`, empat tabel Invoice terpisah. Invoice dan Surat Tugas memakai snapshot; jangan hitung ulang dokumen historis dari master terbaru. `coa_master` masih MyISAM: referensi COA memakai PK/validasi server, bukan FK fisik atau akun contoh. Konfigurasi akun WAPU dan identitas/rekening cetak wajib sah; nilai kosong harus menolak operasi terkait. Migration tidak memberikan hak menu otomatis. Prosedur operasional lengkap ada di README bagian fase 4.

Fase 5: `RescheduleController`, `ReconfirmationController`, `RefundController` dan support bernama sama. H-7 inklusif; jadwal baru minimal +7 hari. Konfirmasi ulang tutup H-1 00.00 WIB; GET tidak membatalkan peserta diam. Invoice baru ditahan sampai Deal terdampak tuntas; Invoice Batal dan lunas mempertahankan snapshot/jurnal. Refund memakai dana lunas teralokasi, permission `sales_order_refund` terpisah tanpa grant otomatis; transfer/pembalikan jurnal manual. Lock program mendahului quotation/Invoice/Refund. Prosedur dan batas fase 5 dimiliki README; operasional/Close dan cutover belum termasuk.

Fase 6: `ParticipantController`/`Participants`/`ParticipantPdf`, `ExpenseController`/`Expenses`, `SurveyController`/`Survey`, XLSX native `Xlsx`. Role Admin bisnis berbeda dari Superadmin role 1 dan Direktur; tanpa anggota/grant otomatis. Penerbitan sertifikat resmi ditolak, survei publik hard-gated off sampai BA sahkan. Pengajuan/OB baru memerlukan program terbuka, existing tetap dapat selesai setelah Closed. Paid memakai Manage, approval memakai Approve dan bukan pengaju. Close memeriksa pembayaran/WAPU/reconfirm/Refund serta menutup survei atomik; administrasi sertifikat/BNSP/catatan/cetak/export tetap boleh. Lock program sebelum baca ulang akses/budget/status. Prosedur/gate/font target ada di README fase 6. Tes fokus memakai `--filter Phase6Test` (SQLite) atau `-c phpunit.mysql.xml --filter Phase6MysqlTest --do-not-cache-result` (MySQL test, dua proses). Rehearsal/cutover tetap fase 7.
