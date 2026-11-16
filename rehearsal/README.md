# Rehearsal fase 7 dan persiapan cutover

Target satu instance lokal. CI3 kini arsip di `ci3/`; runtime 8080 memakai preview snapshot lokal. Alat migrasi tersedia tidak
berarti histori operasional, UAT atau kesiapan produksi sudah terbukti.

## Input wajib

- Docker Engine/Compose v2 dan Python 3.11+.
- Snapshot database **beserta berkas privat** yang konsisten, mewakili seluruh
  histori Labmania. Pemilik data mengatur penghentian perubahan/metode capture.
  COA MyISAM membuat `--single-transaction` saja tidak cukup.
- Dump schema+data tanpa `CREATE/DROP DATABASE`, `USE`, `DEFINER`, routine/trigger.
  Ekspor ulang dump tidak kompatibel; jangan membersihkannya dengan regex.
- Berkas salinan memakai path asli, misalnya `files/uploads/trainers/...`.
  Jangan me-mount upload aktif atau menyalin seluruh repository sebagai dokumen.
- Font Times New Roman berlisensi: `times.ttf` dan `timesbd.ttf`.
- Folder konfigurasi/snapshot/backup privat; pastikan ACL pemilik pada Windows.

Untuk environment baru, letakkan snapshot, font dan backup di luar root
repository. Mount privat existing dalam `.artifacts/` tetap dipertahankan dan
dikecualikan oleh `.dockerignore`; jangan meregenerasi environment existing. `init-env` menolak input di dalam
build context agar data privat/font tidak ikut tersalin oleh Docker COPY.

Environment, network, database dan volume berbeda dari layanan existing. Web
hanya `127.0.0.1:8090`; database tidak dipublikasikan. HTTP/cookie non-secure
hanya pengecualian loopback lokal; produksi wajib HTTPS/secure cookie.

## 1. Snapshot dan environment

Dari `rehearsal`, siapkan folder berisi `snapshot.sql` dan `files/`:

```powershell
python snapshot.py manifest --root <folder-snapshot> --id <id-snapshot> --captured-at <waktu-ISO-8601-dengan-zona> --consistent
python snapshot.py init-env --snapshot <folder-snapshot> --fonts <folder-font> --backups <folder-backup-privat>
```

`--consistent` adalah pernyataan pemilik data, bukan pemeriksaan sumber aktif.
Manifest draft mengikat SHA-256 dump dan seluruh berkas. Script menolak overwrite.
`init-env` menghasilkan rahasia acak dalam `.env.rehearsal`; jangan mencetaknya
atau memasukkannya ke Git. Isi COA, rekening/identitas cetak dan role Activity Log
yang sah. Font, COA dan grant contoh tidak dibuat.

APP_KEY harus tetap untuk snapshot yang sama. TOTP lama CI3 disalin persis;
jika sumber sudah mengandung TOTP Laravel `encrypted:`, gunakan APP_KEY asal
yang benar. APP_KEY baru tidak dapat mendekripsi histori tersebut.

## 2. Bootstrap target baru dan restore sumber

```powershell
docker compose --env-file .env.rehearsal -f compose.yaml -p labmania-rehearsal build app
docker compose --env-file .env.rehearsal -f compose.yaml -p labmania-rehearsal up -d rehearsal-db
docker compose --env-file .env.rehearsal -f compose.yaml -p labmania-rehearsal run --rm --user root app php artisan down
docker compose --env-file .env.rehearsal -f compose.yaml -p labmania-rehearsal run --rm --user root app php artisan labmania:migrasi-operasi sumber --manifest=/snapshot/manifest-draft.json
docker compose --env-file .env.rehearsal -f compose.yaml -p labmania-rehearsal run --rm --user root app php artisan labmania:migrasi-profil --manifest=/snapshot/manifest-draft.json
```

Target otomatis mendapat **struktur saja** fase 1–6 dan catatan migration terkait,
tanpa akun/role/grant bisnis. Struktur mempertahankan nama dan constraint existing,
termasuk COA MyISAM. Setelah schema berubah pada pekerjaan berikutnya, perbarui
snapshot struktur dan tes bootstrap bersama migration/ERD yang terkait.

Jangan memakai volume `db_data`, menjalankan `migrate:fresh`, menghapus guard
migration atau menaruh histori mentah sebelum migration fase 3. Database sumber
`labmania_legacy_snapshot` dipulihkan oleh loader terbatas; aplikasi membacanya
dengan `snapshot_reader` yang hanya SELECT/SHOW VIEW. Sumber berisi tabel ditolak.
Kegagalan restore memerlukan project/volume baru, bukan overwrite sumber parsial.

## 3. Pemetaan yang disahkan

Profil draft dengan `tables: []` menghasilkan inventaris kolom, jumlah dan hash
tanpa menulis. Bentuk manifest baru berisi satu entri per tabel tujuan, berurutan
sesuai FK/relasi, menggunakan field berikut:

| Field | Isi |
|---|---|
| `source`, `target` | Nama tabel persis |
| `key` | Array kolom identitas target; disalin dari sumber |
| `columns` | Object kolom target ke kolom sumber |
| `values` | Opsional, nilai tetap yang memang disahkan |
| `omit_columns` | Kolom sumber yang ditinggalkan beserta alasan |
| `expected_rows`, `source_sha256` | Jumlah/checksum hasil profil lengkap |
| `overrides` | Opsional, `path` dan `sha256` JSONL koreksi per key |
| `extra_rows` | Opsional, `path` dan `sha256` JSONL baris tambahan yang disahkan |
| `projection_not_null`, `projection_reason` | Opsional, proyeksi child hanya untuk kolom sumber non-NULL; sumber lengkap wajib disalin oleh pemetaan lain |

Semua kolom target non-generated/non-auto-increment harus dipetakan eksplisit,
termasuk nullable agar metadata tidak terisi otomatis. Contoh satu baris koreksi:

```json
{"key":{"id_user":17},"values":{"username":"<nilai-disahkan>"},"reason":"<referensi-keputusan>"}
```

Key tidak dapat diganti. `extra_rows` berisi object baris target lengkap, misalnya
definisi menu baru. File rekonsiliasi wajib privat dan terikat hash. Tidak ada SQL/
PHP bebas. Normalisasi yang belum bisa dipetakan menjadi konflik, bukan fallback.

- Semua tabel sumber dipetakan atau dijelaskan dalam `excluded_source_tables`.
  Pengecualian hanya data teknis/non-Labmania, bukan transaksi bermasalah.
- Semua tabel target dipetakan atau dijelaskan dalam `empty_target_tables` dan
  terbukti kosong; `migrations` dimiliki bootstrap.
- Tambahkan relasi logis ERD dalam `relations`: `table`, `columns`, `references`,
  `reference_columns`. FK fisik diperiksa otomatis. Actor historis nullable tidak
  boleh diganti akun contoh.
- Pertahankan PK, relasi, nomor, password hash, TOTP, tanggal dan log. Username
  ambigu, status/nomor tidak sah, orphan dan berkas hilang harus direkonsiliasi.
- Pertahankan group 1 sebagai Superadmin. Reservasi ID role/menu sumber dahulu;
  definisi baru memakai ID yang disahkan tanpa overwrite. Admin bisnis/Direktur
  berbeda dari Superadmin. Finance SO/Invoice/Refund tidak mendapat grant otomatis.
- NPWP, JP, Perlakuan PPN, jadwal, nama, token atau snapshot dokumen yang hilang
  menjadi konflik per ID. Jangan menciptakan histori atau membangun ulang
  Invoice/SO/ST lama dari master terkini.

Jalankan profil lagi dengan pemetaan lengkap untuk mendapatkan checksum/jumlah
final. Isi `approval.by` dan `approval.at` berdasarkan pengesahan nyata pemilik
data/BA. Alat mencatat persetujuan, tidak menggantikan pengesahan bisnis.

## 4. Simulasi dan import

```powershell
docker compose --env-file .env.rehearsal -f compose.yaml -p labmania-rehearsal run --rm --user root app php artisan labmania:migrasi-impor --manifest=/snapshot/manifest-approved.json
docker compose --env-file .env.rehearsal -f compose.yaml -p labmania-rehearsal run --rm --user root app php artisan labmania:migrasi-impor --manifest=/snapshot/manifest-approved.json --terapkan
docker compose --env-file .env.rehearsal -f compose.yaml -p labmania-rehearsal run --rm --user root app php artisan labmania:migrasi-verifikasi --manifest=/snapshot/manifest-approved.json
docker compose --env-file .env.rehearsal -f compose.yaml -p labmania-rehearsal run --rm --user root app php artisan labmania:migrasi-operasi kesiapan
```

Simulasi tidak menulis database/status/berkas. Apply memerlukan maintenance,
lock lokal dan binding manifest+APP_KEY. Commit per tabel; MyISAM tidak atomik.
Retry hanya menerima baris identik atau menambahkan yang belum ada. Tidak ada
update/delete/upsert diam-diam. Manifest berubah sesudah import memerlukan target
baru. Kegagalan berarti target belum valid dan tidak boleh dibuka.

Verifikasi mencocokkan seluruh baris/key, nilai/metadata, jumlah, hash, desimal,
orphan, username dan referensi dokumen. Receipt teknis terpisah dari log bisnis;
tidak ada actor atau transaksi audit palsu. `verified` berarti cocok dengan
pemetaan yang diberikan; bukan tanda UAT atau produksi siap.

Lakukan dua rehearsal pada project berbeda, misalnya `labmania-rehearsal-a` dan
`labmania-rehearsal-b`, memakai snapshot, manifest dan APP_KEY sama. Ulangi tahap
2–4 dengan `-p` yang sesuai; jangan menghapus volume existing. Bandingkan laporan
tabel/hash, nominal dan berkas. Ukur query utama, PDF/XLSX, memori, durasi import,
ukuran backup serta waktu restore pada volume nyata. Jangan menetapkan SLA
produksi berdasarkan fixture/laptop.

## 5. Backup dan restore ke project baru

Target tetap maintenance dan verifikasi terakhir harus lulus:

```powershell
docker compose --env-file .env.rehearsal -f compose.yaml -p labmania-rehearsal run --rm --user root app php artisan labmania:migrasi-operasi backup --arsip=rehearsal-14-11-2026.zip
```

Arsip AES-256 menyimpan database, dokumen privat, receipt dan konfigurasi aplikasi
termasuk APP_KEY. Password backup tersedia melalui environment, bukan argumen/log.
Simpan password terpisah. Backup belum menjadi bukti pemulihan tanpa uji restore.

Buat environment **baru** `.env.rehearsal-restore` untuk project `labmania-restore`.
Isi `REHEARSAL_ENV_FILE=.env.rehearsal-restore`, gunakan kredensial DB baru, folder
backup yang sama dan password backup asal. Jangan menimpa environment asal.

```powershell
docker compose --env-file .env.rehearsal-restore -f compose.yaml -p labmania-restore up -d rehearsal-db
docker compose --env-file .env.rehearsal-restore -f compose.yaml -p labmania-restore run --rm --user root app php artisan down
docker compose --env-file .env.rehearsal-restore -f compose.yaml -p labmania-restore run --rm --user root app php artisan labmania:migrasi-operasi restore --arsip=rehearsal-14-11-2026.zip
```

Restore menolak target berisi data bisnis/dokumen/receipt. Hasilnya menyebut lokasi
privat `application-config.json`. Pulihkan APP_KEY dan pengaturan aplikasi ke
environment restore melalui kanal privat, tetapi pertahankan kredensial DB/path
infrastruktur project baru. Buat ulang app agar environment baru terbaca; pulihkan
snapshot sumber melalui operasi `sumber`, lalu jalankan `migrasi-verifikasi` dan
`kesiapan` pada project restore. Hash/jumlah/nominal/berkas harus sama dengan asal.
Konfigurasi rahasia tidak boleh ditempel ke output atau artifact terbuka.

Restore tidak memakai `migrate:rollback`. Kegagalan restore SQL/berkas memerlukan
project/volume baru. Pertahankan backup dan bukti diagnosis sebelum pembersihan.

## 6. HTTP CLI, UAT dan serah terima

Setelah verifikasi, smoke test lokal dapat dijalankan:

```powershell
docker compose --env-file .env.rehearsal -f compose.yaml -p labmania-rehearsal up -d --force-recreate app
docker compose --env-file .env.rehearsal -f compose.yaml -p labmania-rehearsal exec --user root app php artisan up
curl.exe --fail http://127.0.0.1:8090/up
```

Probe `.env`, `.git/config`, `.artifacts/`, `specs/`, `database/`, `vendor/` dan
`storage/`: isi internal tidak boleh terlayani. Untuk Apache CI3 existing,
jalankan syntax check, terapkan konfigurasi, lalu probe port existing sekaligus
rute CI3/aset penting. Runtime Docker/Apache yang belum tersedia berarti
pemeriksaan tersebut belum dilakukan, meskipun file konfigurasi telah diperkeras.

UAT pengguna mencakup login/2FA, hak role, Master/Program, Quotation/Deal, SO/ST
dan revisi, Invoice/lunas/WAPU, reschedule/reconfirm/refund, peserta, biaya/
approval/Paid/Close, PDF/XLSX serta dokumen privat tanpa hak. Tes otomatis tidak
dianggap pelaksanaan UAT. Survey publik dan sertifikat resmi tetap mengikuti gate BA.

Siap cutover lokal membutuhkan snapshot nyata lengkap, konflik nol, dua rehearsal
sama, restore terverifikasi, konfigurasi/grant/keputusan wajib sah, regresi lulus
dan laporan volume/performa. Checklist mencatat pemilik bukti, tanggal dan hasil.

Go-live terpisah: tentukan PIC/jendela layanan; selesaikan UAT dan kesiapan server,
HTTPS, backup, monitoring serta target pemulihan; hentikan seluruh penulis sumber;
ambil snapshot final konsisten; import/rekonsiliasi; buka layanan sekali jalan
sesudah keputusan go-live. Pantau health, autentikasi/akses, error, latensi, disk
dan keberhasilan backup. Fase ini tidak memindahkan layanan aktif.

Sebelum transaksi Laravel baru, rollback dapat mempertahankan CI3 aktif dan
menahan Laravel. Setelah ada transaksi baru, hentikan perubahan dan rekonsiliasi
transaksi/nomor/dokumen dahulu; jangan menyalin database balik atau mengalihkan
layanan langsung. TOTP `encrypted:` baru tidak kompatibel dengan CI3.

CLI dijalankan sebagai root **di container rehearsal** agar dapat membaca snapshot privat tanpa mengubah ACL sumber. Mount sumber tetap read-only. Saat web dibuat ulang, command service mengembalikan kepemilikan storage ke www-data sebelum Apache berjalan; request PHP memakai worker www-data. Jangan membuka layanan sesudah import/restore tanpa langkah create ulang web tersebut.
