# Laravel Labmania

Laravel 13 / PHP 8.5, Inertia 3 / Vue 3 / TypeScript / Vite berada di root repository. Jalankan dependency manager dan Artisan dari root. CI3 di `ci3/` adalah arsip; jangan mengaktifkan kembali runtime atau memakai dependency CI3 untuk Laravel.

Sumber skema: `specs/docs-to-be/fondasi-laravel.md` dan ERD to-be. Tidak ada migrasi default users atau seed akun contoh. Runtime localhost:8080 adalah preview snapshot lokal, bukan pengesahan production; lihat `local/README.realdata-8080.md`.

Pemeriksaan dari root repository dengan PHP 8.5 di PATH:

```powershell
php vendor/phpunit/phpunit/phpunit
php vendor/phpunit/phpunit/phpunit -c phpunit.mysql.xml --do-not-cache-result
node tests/frontend/phase3.mjs
node tests/frontend/phase4.mjs
npm.cmd run build
php artisan route:list --except-vendor
```

Komunikasi dan dokumentasi memakai Bahasa Indonesia. `specs/` tetap sumber acuan BA: requirement, field, rumus, status dan skema tidak boleh dikarang. Cocokkan perubahan database dengan ERD dan requirement; nama fisik harus sama. UI mengacu `specs/ui-mockup/produk-to-be/`. `specs/input/` bersifat append-only/read-only.

Artifact berada di `.artifacts/<fitur>/`, diabaikan Git. Buat file baru `plan_<fitur>_<DD-MM-YYYY>.md`, `task_<fitur>_<DD-MM-YYYY>.md`, dan `walkthrough_<fitur>_<DD-MM-YYYY>.md`; jangan menimpa atau mengganti nama artifact lama. Laporkan perubahan file, alasan, hasil pemeriksaan, dan sisa risiko. Pengujian browser tidak wajib.

Konfigurasi Docker hasil render dan environment privat tidak masuk Git. Database runtime 8080 berbeda dari koneksi PHP lokal dalam `.env`. Pertahankan volume dan APP_KEY. `docker/mysql/` masih menjadi bind mount database GL aktif; jangan dipindahkan, dihentikan, atau dihapus bersama arsip CI3. Recreate aplikasi lokal memakai `--no-deps`; jangan menjalankan `compose down -v` atau bootstrap UAT pada data asli.

PHPUnit default memaksa APP_ENV=testing, DB_CONNECTION=sqlite, DB_DATABASE=:memory:, DB_URL kosong. Konfigurasi `phpunit.mysql.xml` memaksa `labmania_laravel_test`; guard menolak database tanpa akhiran `_test`. Suite MySQL membangun ulang tabel fixture: jangan jalankan dua proses pada DB test yang sama. Jangan memakai database aplikasi/CI3 untuk test, mengganti guard test agar lolos, atau menjalankan migrate/fresh/seed pada database lama.

`write_log()` memakai tujuh argumen dan `logs` existing. Update menyimpan old sebelum perubahan, new sesudahnya dan detail_changes untuk child. Audit internal wajib akun sah. Fase 3 membuat `logs.created_by` nullable hanya pada DB Laravel; `Audit::publicConfirmation()` menetapkan `actor_type=publik` di server, tanpa menyamar sebagai admin. Password/secret/token tidak masuk log/props umum.

Environment, session cookie, cache, storage dan APP_KEY terpisah. Jangan menghubungkan `.env` Laravel ke DB aktif CI3. Dokumen tidak diterbitkan lewat public/storage; endpoint unduh memeriksa hak dan batas path.

Fase 4: `SalesOrderController`/`InvoiceController`, support `SalesOrder`/`Invoice`, empat tabel Invoice terpisah. Invoice dan Surat Tugas memakai snapshot; jangan hitung ulang dokumen historis dari master terbaru. `coa_master` masih MyISAM: referensi COA memakai PK/validasi server, bukan FK fisik atau akun contoh. Konfigurasi akun WAPU dan identitas/rekening cetak wajib sah; nilai kosong harus menolak operasi terkait. Migration tidak memberikan hak menu otomatis. Prosedur operasional lengkap ada di README bagian fase 4.

Fase 5: `RescheduleController`, `ReconfirmationController`, `RefundController` dan support bernama sama. H-7 inklusif; jadwal baru minimal +7 hari. Konfirmasi ulang tutup H-1 00.00 WIB; GET tidak membatalkan peserta diam. Invoice baru ditahan sampai Deal terdampak tuntas; Invoice Batal dan lunas mempertahankan snapshot/jurnal. Refund memakai dana lunas teralokasi, permission `sales_order_refund` terpisah tanpa grant otomatis; transfer/pembalikan jurnal manual. Lock program mendahului quotation/Invoice/Refund. Prosedur dan batas fase 5 dimiliki README; operasional/Close dan cutover belum termasuk.

Fase 6: `ParticipantController`/`Participants`/`ParticipantPdf`, `ExpenseController`/`Expenses`, `SurveyController`/`Survey`, XLSX native `Xlsx`. Role Admin bisnis berbeda dari Superadmin role 1 dan Direktur; tanpa anggota/grant otomatis. Penerbitan sertifikat resmi ditolak, survei publik hard-gated off sampai BA sahkan. Pengajuan/OB baru memerlukan program terbuka, existing tetap dapat selesai setelah Closed. Paid memakai Manage, approval memakai Approve dan bukan pengaju. Close memeriksa pembayaran/WAPU/reconfirm/Refund serta menutup survei atomik; administrasi sertifikat/BNSP/catatan/cetak/export tetap boleh. Lock program sebelum baca ulang akses/budget/status. Prosedur/gate/font target ada di README fase 6. Tes fokus memakai `--filter Phase6Test` (SQLite) atau `-c phpunit.mysql.xml --filter Phase6MysqlTest --do-not-cache-result` (MySQL test, dua proses). Rehearsal/cutover tetap fase 7.
