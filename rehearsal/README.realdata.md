# Rehearsal data asli di localhost:8081

Overlay `compose.realdata.yaml` memakai importer existing dan volume per proyek. DB hanya pada jaringan internal; app juga terhubung ke bridge `preview-web` agar port loopback dapat diakses pada Docker Desktop. Memerlukan Compose >= 2.24.4 (`!override`). UAT `labmania_web` di 8080 tidak diganti. Hanya satu proyek membuka preview; B dan restore dijalankan lewat CLI.

## 1. Input wajib dan capture

Backup `db_labmania_Last.sql` sudah diterima dan rehearsal data asli telah dijalankan; bukti privat tersedia di artifact `migrasi_data_asli_lokal`. Arsip upload dan bukti konsistensi capture/zona waktu tetap perlu disahkan. Rehearsal bukan pengesahan sumber atau cutover production. Prosedur lokal tidak menulis DB remote.

Pengelola sumber perlu menyediakan:

- Nama DB CI3 sebenarnya, versi server, waktu snapshot dengan zona waktu, dan identitas pengambil backup.
- Dump satu DB, seluruh berkas upload terkait dengan path relatif aslinya, serta konfirmasi konsistensi DB/berkas pada waktu capture yang sama.
- Untuk tabel MyISAM, jeda **semua** writer selama capture DB/berkas, atau backup infrastruktur yang menjamin konsistensi. `--single-transaction` sendiri tidak cukup. Pengambilan backup tidak mengizinkan agent mematikan aplikasi production.
- Informasi role/izin dan konfigurasi keuangan sah untuk rekonsiliasi. Jika TOTP sumber sudah terenkripsi, sumber kunci enkripsinya perlu ditangani privat; jangan menyalin ciphertext lalu menganggap kuncinya cocok.

Nama DB remote tidak diasumsikan sama dengan default development. Kredensial bukan bukti bahwa capture sudah konsisten. Akun DB juga tidak menyediakan akses upload; berkas harus disediakan oleh pemilik/pengelola.

Dump yang diterima loader tidak mengandung CREATE/DROP DATABASE, USE, DEFINER atau LOCK/UNLOCK TABLES. Pengelola mengekspor dengan opsi yang didukung versi kliennya: tanpa routine/event/trigger, tanpa `--databases`, `--skip-add-locks`, `--skip-lock-tables`, `--hex-blob`, charset utf8mb4; `--set-gtid-purged=OFF` hanya untuk klien MySQL yang mendukungnya. Jangan menghapus bagian SQL dengan regex. Dump tidak sesuai harus diekspor ulang.

## 2. Folder privat dan environment

Jalankan dari WSL Ubuntu-24.04 agar bind mount memakai `/mnt/c/...`, bukan `C:/...` yang tidak dikenali Compose Linux. Dari PowerShell:

```powershell
wsl.exe -d Ubuntu-24.04 --cd /mnt/c/web_dev/labmania/rehearsal
```

Contoh environment baru di shell WSL, dari direktori `rehearsal`. Folder privat baru harus di luar root repository. Lewati perintah `init-env` bila file tersebut sudah disiapkan; file existing sengaja ditolak. Mount existing dalam `.artifacts/` tetap dipertahankan dan tidak perlu dipindahkan:

```bash
set -euo pipefail
root=/mnt/c/web_dev/labmania_private/migrasi_data_asli_lokal
mkdir -p "$root/snapshot/files" "$root/backups" "$root/reports"

# Hanya bila environment belum ada. Semua output memakai create-new; jangan hapus key existing.
python3 snapshot.py init-env --snapshot "$root/snapshot" --fonts /mnt/c/Windows/Fonts --backups "$root/backups" --output .env.rehearsal-realdata-a
python3 snapshot.py init-env --snapshot "$root/snapshot" --fonts /mnt/c/Windows/Fonts --backups "$root/backups" --output .env.rehearsal-realdata-b --key-from .env.rehearsal-realdata-a
python3 snapshot.py init-env --snapshot "$root/snapshot" --fonts /mnt/c/Windows/Fonts --backups "$root/backups" --output .env.rehearsal-realdata-restore --key-from .env.rehearsal-realdata-a
```

Environment A/B/restore berbagi APP_KEY dan password arsip; password DB/reader/loader/root berbeda. Tidak ada key atau password dicetak. File `.env.rehearsal-*` diabaikan Git dan Docker build. Pertahankan akses folder hanya untuk pemilik/operator; Git ignore bukan kontrol akses. Font menggunakan berkas Windows berlisensi, bukan font contoh.

Letakkan salinan dump sebagai `snapshot/snapshot.sql`, berkas pada `snapshot/files/uploads/...`; simpan arsip sumber asli tanpa perubahan dan catat SHA-256 di laporan privat. Tolak path traversal, symlink/junction, atau arsip yang menulis di luar folder tujuan saat mengekstrak; periksa daftar entri lebih dahulu. Jangan ekstrak langsung ke storage aplikasi.

Setelah **bukti konsistensi tersedia**, buat draft:

```bash
python3 snapshot.py manifest --root "$root/snapshot" --id '<ID-snapshot-unik>' --captured-at '<ISO8601-dengan-zona-waktu>' --consistent
```

Ganti placeholder memakai metadata nyata. Jangan memakai `--consistent` hanya karena dump berhasil diunduh. Environment dapat disiapkan sebelum snapshot tersedia, tetapi manifest dan impor tidak dapat dilanjutkan.

## 3. Restore sumber, profil dan pemetaan

Helper berikut membatasi nama proyek; semua perintah tetap menggunakan overlay:

```bash
rd() {
  local target="$1"
  shift
  case "$target" in realdata-a|realdata-b|realdata-restore) ;; *) return 1 ;; esac
  docker compose --env-file ".env.rehearsal-$target" -f compose.yaml -f compose.realdata.yaml -p "labmania-$target" "$@"
}

rd realdata-a build app
rd realdata-a up -d rehearsal-db
rd realdata-a run --rm --no-deps --user root app php artisan down
rd realdata-a run --rm --no-deps --user root app php artisan labmania:migrasi-operasi sumber --manifest=/snapshot/manifest-draft.json
rd realdata-a run --rm --no-deps --user root app php artisan labmania:migrasi-profil --manifest=/snapshot/manifest-draft.json
```

Laporan disimpan privat dengan nama baru. Jangan mencetak raw SQL, password, atau baris PII ke log umum. Jika restore parsial gagal, simpan diagnosis dan gunakan proyek/volume baru; jangan mengulang restore ke sumber berisi tabel atau menghapus volume lama.

Susun manifest lengkap sesuai kontrak README rehearsal: semua tabel/kolom sumber dan target terhitung, PK/relasi/nomor/nominal/status/tanggal/hash password/TOTP/log dipertahankan, tabel kosong/pengecualian beralasan. Cocokkan dengan ERD/spec BA. Kelompok 1 tetap Superadmin; role dan grant baru tidak diberikan otomatis.

Data hilang (NPWP, JP, varian, Perlakuan PPN, snapshot dokumen historis), username ambigu, relasi putus dan berkas hilang menjadi konflik per ID. Simpan koreksi pada file override terikat hash, dengan keputusan pemilik data yang nyata. Tidak ada default bisnis, actor palsu, histori rekonstruksi dari master terkini, atau perubahan ID untuk membuat test lolos.

Profil ulang pemetaan lengkap. Buat `manifest-approved.json` sebagai file baru hanya setelah pengesahan pemilik data/BA. Isi konfigurasi COA, rekening, identitas cetak dan role log dengan nilai sah pada environment privat; jangan mengambil nilai sintetis dari UAT.

## 4. Dua impor dan verifikasi

Siapkan sumber B dari snapshot yang sama, lalu impor A dan B berurutan:

```bash
rd realdata-b up -d rehearsal-db
rd realdata-b run --rm --no-deps --user root app php artisan down
rd realdata-b run --rm --no-deps --user root app php artisan labmania:migrasi-operasi sumber --manifest=/snapshot/manifest-approved.json
for target in realdata-a realdata-b; do
  rd "$target" run --rm --no-deps --user root app php artisan labmania:migrasi-impor --manifest=/snapshot/manifest-approved.json
  rd "$target" run --rm --no-deps --user root app php artisan labmania:migrasi-impor --manifest=/snapshot/manifest-approved.json --terapkan
  rd "$target" run --rm --no-deps --user root app php artisan labmania:migrasi-verifikasi --manifest=/snapshot/manifest-approved.json
  rd "$target" run --rm --no-deps --user root app php artisan labmania:migrasi-operasi kesiapan
done
```

Hentikan pada exit nonzero. Bandingkan laporan A/B: jumlah/key/hash per tabel, total DECIMAL keuangan, seluruh referensi berkas dan metadata. Sumber mentah tetap tidak berubah. Hash password harus identik sebelum login, bukan di-reset ke password UAT. Nilai historis tidak dibulatkan menjadi float.

`kesiapan` dapat menolak konfigurasi keuangan/role yang belum lengkap: perbaiki dari sumber sah dan catat blocker. Jangan mengisi nilai contoh. Impor `verified` sendiri tidak berarti siap production. Jangan menjalankan `labmania:uat`, `migrate:fresh`, atau seed ke data asli.

## 5. Backup, restore dan pembukaan preview

Sebelum menjalankan preview, A tetap maintenance dan terverifikasi:

```bash
rd realdata-a run --rm --no-deps --user root app php artisan labmania:migrasi-operasi backup --arsip=realdata-14-11-2026.zip
rd realdata-restore up -d rehearsal-db
rd realdata-restore run --rm --no-deps --user root app php artisan down
rd realdata-restore run --rm --no-deps --user root app php artisan labmania:migrasi-operasi restore --arsip=realdata-14-11-2026.zip
```

Restore mengembalikan lokasi privat `application-config.json`. Pulihkan APP_KEY dan pengaturan bisnisnya ke environment restore secara privat, pertahankan kredensial DB/folder infrastrukturnya, lalu buat ulang container CLI. Pulihkan juga sumber snapshot pada proyek restore, jalankan `migrasi-verifikasi` dan `kesiapan` kembali. Cocokkan laporan dengan A. Backup belum dianggap dapat dipulihkan sebelum langkah ini berhasil. Nama arsip pada pengulangan harus baru.

Hanya sesudah verifikasi A/B, restore, dan konfigurasi sah:

```bash
rd realdata-a --profile preview up -d --no-build app
rd realdata-a exec --user root app php artisan up
```

Preview: **http://localhost:8081**. Login seluruh lingkungan memakai username/password tanpa token sesuai keputusan pengguna. Password asli tidak direset; hash yang tidak didukung tetap menjadi konflik. Sesi, pemeriksaan akun aktif, rate limit dan permission berlaku; sesi batal jika password berubah. Bootstrap UAT tetap ditolak pada DB asli dan production. Hanya satu proyek membuka port; jangan menjalankan web B/restore bersamaan di port 8081.

Mail diarahkan ke log privat, tidak ada scheduler/worker yang diluncurkan. DB tetap internal; bridge app digunakan untuk port preview. Pengujian otomatis lewat CLI; penerimaan tampilan dan alur kerja oleh pengguna tetap terpisah.

Untuk menghentikan preview: `rd realdata-a stop app`. Ini mempertahankan seluruh volume. Jangan gunakan `down -v`, menghapus CI3, atau mengganti root Compose/port 8080. Restore tidak menggunakan rollback migration.

## Status selesai

Selesai hanya dengan bukti sumber lengkap/konsisten, pemetaan disahkan, konflik nol dalam cakupan, impor A/B identik, backup/restore terverifikasi, serta test dan konfigurasi sah. Rehearsal ini bukan cutover production. Tanpa input nyata, status tetap **persiapan selesai; migrasi data asli tertahan**.
