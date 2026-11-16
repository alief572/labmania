# Docker Laravel lokal (UAT)

Target: http://localhost:8080. Hanya data sintetis; bukan konfigurasi production.
CI3 disimpan untuk rollback. Layanan DB CI3 dan `labmania_uji` bukan bagian setup UAT.

Jika snapshot asli sudah menggantikan UAT melalui `README.realdata-8080.md`,
skrip UAT ini menolak Setup/Switch/Rollback agar lingkungan data tidak terganti.
Gunakan Compose aktif dan runbook data asli untuk restart atau rollback.

## Setup pertama

Dari root repository, dengan Docker Desktop aktif:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/laravel-local.ps1 -Action Setup
```

Skrip memakai Docker Windows bila tersedia, atau Docker melalui WSL `Ubuntu-24.04`.
Skrip memerlukan container CI3 existing `labmania_web` dari project `labmania` untuk
menangkap backup. Ini bukan installer production atau installer CI3 dari clone kosong.
Build tidak memerlukan PHP/Node di Windows. Font berlisensi `times.ttf` dan
`timesbd.ttf` dibaca read-only dari folder Fonts Windows untuk Name Table.

Compose lama dan Apache efektif disalin ke `.artifacts/docker_laravel_local/backup_*`.
Image CI3 diberi tag rollback. Tidak ada volume lama dihapus.
Compose Laravel baru disalin ke `docker-compose.yml` hanya setelah build/bootstrap
dan pemeriksaan DB lulus. Jika pengalihan web gagal, skrip mencoba rollback CI3.
Web terhubung ke network bridge khusus untuk penerbitan port loopback dan network
UAT internal untuk DB. DB hanya terhubung ke network internal tersebut.

Database Laravel:

| Parameter | Nilai |
|---|---|
| Host dari container web | `uat-db` |
| Port internal | `3306` |
| Database | `labmania_laravel` |
| User aplikasi | `labmania_uat` |
| Volume DB | `labmania_uat_mysql` |
| Volume dokumen/session/cache | `labmania_uat_storage` |

DB UAT tidak membuka port ke Windows. APP_KEY dan password DB acak tersimpan
di `.env` root yang diabaikan Git; ini berbeda dari `.env` Laravel development.
Jangan menghapus `.env` root setelah inisialisasi volume. Backup file ini bersama
backup DB dan dokumen: APP_KEY diperlukan untuk membaca secret autentikator.
File `local/.state.json` menyimpan lokasi backup, bukan kredensial.

## Login dan akun uji

Bootstrap membuat enam akun: `admin` (Superadmin), `uat_sales`, `uat_manajemen`,
`uat_admin`, `uat_direktur`, dan `uat_trainer`. Password acak ditampilkan sekali
saat setup pertama; simpan secara privat.
Hak ini hanya skenario UAT, bukan keputusan role/grant production.

Login seluruh lingkungan langsung masuk setelah username/password benar, tanpa
token atau TOTP. Akun nonaktif/password salah ditolak, hak menu tetap berlaku,
dan perubahan password menutup sesi lama. Endpoint dan CLI enrollment sudah
dipensiunkan. Guard bootstrap tetap menolak DB asli dan production.

## Penggunaan berikutnya

```powershell
docker compose up -d
docker compose ps
docker compose exec --user www-data web php artisan labmania:uat --check
```

Perubahan kode memerlukan rebuild:

```powershell
docker compose build web
docker compose up -d web
```

Tidak ada bind mount source; dependency dan aset frontend ada dalam image.
Pemeriksaan HTTP tanpa browser tersedia dengan `python local/check-http.py`
dari root repository, memakai Python stdlib. Script tidak mengaktifkan 2FA akun.
Setup ulang mempertahankan password, autentikator, dan data UAT yang sudah diubah.
Jangan memakai `down -v` atau `--remove-orphans`: layanan CI3 existing tetap disimpan.
Peringatan orphan untuk DB/phpMyAdmin CI3 tidak meminta penghapusan.

Untuk backup DB sintetis, jalankan dump di container dengan password DB dari `.env`
root melalui prompt, lalu salin hasilnya ke artifact privat baru:

```powershell
docker compose exec uat-db mysqldump -u labmania_uat -p --single-transaction --skip-lock-tables --result-file=/tmp/uat-backup.sql labmania_laravel
docker compose cp uat-db:/tmp/uat-backup.sql .artifacts/docker_laravel_local/backup_uat_DD-MM-YYYY_HH-mm-ss.sql
```

Pakai nama file baru setiap backup. Simpan juga `.env` root dan dokumen pada
volume `labmania_uat_storage` secara privat. Dump di atas bukan backup production
atau bukti restore lengkap.

## Rollback dan kembali Laravel

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/laravel-local.ps1 -Action Rollback
```

CI3 kembali pada port 8080 dengan DB lamanya. Skrip memeriksa hash Compose backup,
memakai image CI3 tersimpan dan memasang Apache efektif dari backup. Volume UAT
tetap dipertahankan. Compose root hasil rollback memuat overlay tersebut agar
`docker compose up -d` berikutnya tetap memakai image dan Apache yang sama. Volume UAT
tetap ada; transaksi UAT tidak disalin ke CI3. Saat CI3 aktif, gunakan skrip berikut
untuk mengembalikan Laravel:

```powershell
powershell.exe -NoProfile -ExecutionPolicy Bypass -File scripts/laravel-local.ps1 -Action Switch
```

`Switch` memeriksa DB/bootstrap UAT tanpa seed ulang. Ini bukan rebuild.

## Batas hasil

Data awal mencakup master, rate, Company/Trainer, Master Program Draft/Final,
Penjualan Public Draft dan B2B Final. Skenario lanjut dijalankan lewat aplikasi.
Identitas/rekening cetak dan COA dalam UAT bersifat sintetis. Survei publik dan
penerbitan sertifikat resmi tetap ditahan oleh gate BA existing.
UAT lokal tidak menyelesaikan migrasi histori production atau audit paritas semua fitur.

Untuk paket L2, commit template `local/compose.yaml` dan skrip setup.
`docker-compose.yml` root tetap file lokal yang diabaikan Git karena bisa berganti
ke Compose CI3 lama yang memuat kredensial. Jangan ikutkan `.env`, state, backup,
dump, dokumen, vendor, node_modules atau build output dalam commit.
