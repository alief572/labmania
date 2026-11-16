_Bagian dari [indeks keputusan](../decisions.md)._

## Keputusan 1: Laravel memakai aplikasi dan database terpisah sampai cutover

Tanggal: 13-11-2026. Sumber: persetujuan roadmap migrasi dan permintaan pengguna melanjutkan fase 1 setelah checkpoint pada percakapan proyek.

### Konteks

CI3 masih dipakai dan memiliki perubahan kerja pengguna. Identitas, relasi, log dan dokumen historis wajib dipertahankan. Target UI/FR adalah produk-to-be, dengan keputusan BA terbuka yang diselesaikan per modul.

### Keputusan

Laravel 13/PHP 8.5/MySQL 8 dan Inertia 3/Vue 3/TypeScript/Vite dibangun di folder laravel, dengan database, session, APP_KEY dan storage terpisah. Tidak mengganti runtime root CI3 atau memakai database aktif bersama. Seluruh modul dimigrasikan bertahap, lalu satu kali cutover setelah rehearsal dan UAT.

### Konsekuensi

Fondasi memakai nama tabel/PK legacy, tanpa migration users default atau seed akun yang memutus ID. Setup TOTP baru terenkripsi hanya di DB Laravel; CI3 tidak membaca format tersebut. Pengujian fondasi memakai fixture sintetis SQLite in-memory. Provisioning DB, rekonsiliasi MySQL/data/file, pemetaan role Activity Log, dan rencana pemulihan masih wajib sebelum aplikasi dipakai. Petunjuk operasi dimiliki README aplikasi Laravel; kontrak field dimiliki dokumen fondasi to-be.

**Diperbarui 14-11-2026.** Salinan development `labmania_laravel` sudah diprovision, role Activity Log dipetakan, dan integrasi MySQL diuji terpisah pada `labmania_laravel_test`. Fase 3 tersedia pada salinan. Ini belum rehearsal dataset produksi, rekonsiliasi berkas nyata, UAT atau cutover; keputusan pemisahan CI3/Laravel tetap berlaku.
