# Data asli pada localhost:8080

## Sumber COA GL - 15-11-2026

Invoice (lookup dan pilihan akun) serta Rate (pilihan dan validasi simpan)
membaca `gl_labmania.coa_master` melalui koneksi `gl`, bukan COA lama pada DB
aplikasi. Pada Docker lokal, sumber adalah `labmania_db` di jaringan eksternal
`labmania_labmania_net`; app bergabung lewat `local/compose.realdata-8080.yaml`.
Sumber harus tersedia ketika app memakai fitur akun; tidak ada fallback diam-diam.

Hanya akun dengan `level = '5'` yang dapat dipilih dan dipakai untuk transaksi
baru. Filter berlaku pada server, termasuk ID akun Invoice yang dikirim langsung
dan akun dari konfigurasi. Akun level lain atau NULL ditolak.

Konfigurasi: `COA_DB_CONNECTION=gl`, `COA_DB_HOST`, `COA_DB_PORT`,
`COA_DB_DATABASE=gl_labmania`, `COA_DB_USERNAME`, `COA_DB_PASSWORD`, dan opsional
`COA_MYSQL_SSL_CA` untuk CA tepercaya. User aplikasi lokal `laravel_gl_reader`
hanya memiliki SELECT pada `gl_labmania.coa_master`. Rahasia berada pada env
privat; jangan menyalin Compose hasil render atau backup privat ke paket push.

Pemetaan lokal: Piutang ID 24 (`1102-01-01`), PPN DN ID 134 (`2104-01-07`),
Public ID 182 (`4101-01-03`), B2B ID 181 (`4101-01-02`). ID hanya berlaku bagi
GL yang diperiksa; verifikasi ulang pada target production. Dua COA WAPU tetap
kosong, tidak dipilih otomatis. Lima konfigurasi rekening/penanda tangan tetap
ditunda. Tidak ada perubahan saldo, nomor rate lama atau jurnal historis.

Invoice/jurnal historis menampilkan snapshot. Bukti setor WAPU memakai akun
dari jurnal Invoice asli, bukan menyelesaikan ulang ID lama terhadap GL baru.
Rate existing di luar daftar GL level 5 diberi label `COA lama; pilih level 5`;
data existing tidak diubah massal, tetapi saat disimpan wajib pilih nomor GL
level 5 walaupun sebelumnya tidak bermaksud mengganti field COA.

Tes SQLite dan MySQL memakai konfigurasi COA eksplisit pada DB test terisolasi.
Compose UAT sintetis memakai `COA_DB_CONNECTION=mysql`, sehingga seed UAT tidak
menulis ke GL. Jangan menjalankan seed UAT pada deployment data asli.

Backup/rehearsal aplikasi belum mencakup database GL eksternal. Sebelum cutover,
sertakan snapshot/version GL, hak reader dan konektivitasnya dalam penerimaan.
GL MyISAM memerlukan mekanisme capture konsisten tersendiri. Remote/production
belum dihubungkan pada pekerjaan ini. Rollback image **dan** Compose/environment
tersimpan privat di `.artifacts/integrasi_coa_gl/rollback-private/`; prosedur ada
di walkthrough fitur. Restart biasa tetap memakai konfigurasi GL yang baru.

Jalur ini terpisah dari `laravel-local.ps1`: tidak memanggil `labmania:uat`, seed,
atau migration. Jalankan Python 3.11+ dari WSL dengan Docker Compose >= 2.24.4.
Preview data asli harus sudah berjalan pada 8081. DB dan storage tetap memakai
volume proyek rehearsal yang sama; tidak ada salin balik ke CI3.

## Penundaan Finance dan preview lokal

Keputusan pengguna 15-11-2026: tujuh konfigurasi Finance boleh dilengkapi nanti.
Gunakan `--defer-finance` pada tool, atau `--tunda-keuangan` pada pemeriksaan
`labmania:migrasi-operasi kesiapan`. Semua nilai kosong tetap dilaporkan dengan
`finance_ready=false` dan `cutover_ready=false`. COA selain dua akun WAPU tetap
wajib. Invoice WAPU yang membutuhkan akun kosong dan cetak Invoice tanpa
identitas/rekening tetap ditolak server. Tanpa opsi, pemeriksaan tetap ketat.

Untuk mengganti UAT 8080 dengan **preview snapshot lokal** yang terverifikasi:

```bash
python3 laravel/local/promote.py preview-local \
  --project labmania-realdata-c \
  --env-file laravel/rehearsal/.env.rehearsal-realdata-c \
  --manifest manifest-rehearsal-mockup-extra-approved.json \
  --defer-finance
```

Aksi ini hanya menerima snapshot `REHEARSAL-`, project lokal, APP_ENV=rehearsal
dan port loopback 8080 dari overlay tetap. Verifikasi, maintenance, backup dan
penyimpanan Compose tetap wajib. Runtime diperiksa ulang sebelum maintenance
dibuka. Status hasil `local_preview=true`, `production_accepted=false`.
Ini tidak mengesahkan capture sumber, upload atau UAT dan tidak mengubah DB CI3.

Preview lokal memakai verifikasi baca-saja `--metadata-login-berubah`: hanya
`users.ip` dan `users.login_terakhir` boleh berbeda akibat login setelah impor.
Laporan menyebut kolom dan jumlah akun terdampak. Password, role, akun aktif,
data bisnis, jumlah baris, relasi dan berkas tetap diperiksa. Default verifikasi
tetap ketat; opsi ini tidak boleh dipakai untuk impor atau mengubah data.

Restart dari PowerShell pada mesin ini (Docker berada di WSL):

```powershell
wsl.exe -d Ubuntu-24.04 --cd /mnt/c/web_dev/labmania --exec docker compose up -d
```

Compose root hasil pengalihan menyertakan app tanpa profil tambahan, memakai
volume snapshot yang sama. Jangan menjalankan bootstrap/seed UAT untuk data ini.

Jalur `check`/`promote` untuk data yang diterima resmi tetap memerlukan dokumen
penerimaan. Jika Finance ditunda, dokumen harus memuat `finance_deferred=true`
dan perintah memakai `--defer-finance`; semua penerimaan lainnya tetap wajib.

## Penerimaan sebelum promosi

Salin `acceptance.example.json` ke folder privat `.artifacts/`, gunakan nama baru.
Pemilik data mengisi identitas, waktu berzona waktu, hash manifest, rujukan bukti
capture, dan pengesahan cakupan legacy, dokumen, pengecualian bisnis, keuangan,
hak akses, UAT serta backup/restore. Nilai `true` adalah pernyataan manusia,
bukan hasil yang boleh dibuat pengembang untuk meloloskan gerbang.

Backup MyISAM perlu bukti tidak ada writer/metode konsisten. Bila belum ada,
snapshot saat ini hanya untuk rehearsal; ambil snapshot konsisten baru untuk
impor authoritative. Persetujuan membiarkan JP/asisten/varian lama kosong harus
tetap menyertakan dampak data Final yang tidak dapat diedit biasa.

Contoh perintah dari root repo dalam WSL (ganti nama manifest dengan file yang
benar-benar disahkan, jangan memakai pengesahan rehearsal sebagai production):

```bash
python3 laravel/local/promote.py check \
  --project labmania-realdata-c \
  --env-file laravel/rehearsal/.env.rehearsal-realdata-c \
  --manifest manifest-approved.json \
  --acceptance .artifacts/penerimaan_data_asli/acceptance-approved.json
```

`check` memverifikasi isi DB terhadap manifest dan konfigurasi runtime. Pemeriksa
kesiapan melakukan probe tulis/baca/hapus berkas sementara di storage; tidak
mengubah data bisnis atau layanan. Kegagalan apa pun menahan pengalihan.
UAT yang sudah memakai data nyata wajib berangkat dari impor final yang
terverifikasi ulang; perubahan transaksi sejak snapshot tidak boleh diabaikan.

## Pengalihan lokal

Setelah `check` lulus, gunakan argumen yang sama dengan aksi `promote`.
Tool menyimpan Compose 8080 existing ke artifact privat baru, mengaktifkan
maintenance data asli, memverifikasi lagi, dan membuat backup terenkripsi.
Baru sesudah itu layanan `labmania_web` dihentikan dan app data asli diterbitkan
di loopback 8080 dengan cookie terpisah. Root `docker-compose.yml` diperbarui
agar `docker compose up -d` memakai lingkungan data asli pada restart berikutnya.

Tool juga menyimpan `.env` root sebelumnya secara privat dan menyesuaikan
`COMPOSE_PROJECT_NAME` ke project data asli. Variabel ini mengalahkan `name:`
pada Compose; keduanya harus selaras agar restart tidak membuat app duplikat.
Isi environment lain dipertahankan, termasuk format barisnya.

Compose hasil render bisa memuat environment privat. File root tersebut sudah
diabaikan Git; jangan menyalinnya ke paket push. Jangan menghapus environment,
backup atau volume UAT. Jangan menjalankan Setup/Switch UAT untuk memperbaiki
lingkungan data asli.

## Rollback lokal

Jika pengalihan gagal sebelum layanan UAT dihentikan, tool membuka maintenance
preview kembali. Jika gagal setelah pengalihan, tool menahan app dalam
maintenance atau menghentikannya. DB dan semua volume tetap dipertahankan;
operator perlu menilai kondisi sebelum membuka layanan.

Untuk kembali ke UAT, hentikan semua writer data asli, buat backup titik terbaru,
dan hentikan app data asli. Cocokkan SHA-256 cadangan Compose dengan `state.json`
di folder `promosi_*`, lalu pulihkan Compose **dan environment root** sebelumnya
(cocokkan `previous_env_sha256` bila tersedia) dan jalankan layanan
`web` project `labmania`. Data asli tetap berada di volume proyeknya, bukan
disalin ke DB UAT atau CI3. Periksa image UAT yang tersedia sebelum menjalankan
Compose lama: tag image dapat sudah menunjuk build lebih baru.

Untuk kembali ke UAT yang ada sebelum pekerjaan 15-11-2026, pasangan cadangan
awal berada di `.artifacts/penggantian_ci3_lokal/sebelum_pengalihan_15-11-2026/`
(`compose-awal.yaml`, `environment-awal.env`, dan hash environment). Hindari
memilih backup pengalihan lanjutan yang tujuan sebelumnya sudah data asli.
Snapshot tetap dibekukan/dibackup sebelum pergantian, dan tidak disalin ke UAT.

Jika sudah ada transaksi baru, jangan memulihkan backup lama ke DB yang sama
atau menyalin DB Laravel ke CI3. Bekukan writer, amankan backup terbaru, dan
rekonsiliasi transaksi bersama pemilik data. Pemulihan DB menggunakan proyek
dan volume baru, lalu verifikasi manifest/berkas sesuai runbook rehearsal.

Promosi lokal ini bukan deployment production. Konfigurasi HTTPS/server,
snapshot final, pengesahan pemilik dan verifikasi production tetap dilakukan
pada cutover sebenarnya oleh pengguna.

## Sidebar tiga level — 15-11-2026

Master Data mengikuti mockup: Product; User; Peserta & Trainer; Dokumen. Modul lain tidak menampilkan label kelompok.

Sesudah impor DB baru, simpan backup terenkripsi dan snapshot empat tabel `ms_menu`, `ms_menu_aksi`, `ms_group_akses`, `ms_user_akses`, lalu jalankan dari container app lokal:

```sh
php artisan labmania:sidebar-to-be
php artisan labmania:sidebar-to-be --terapkan --aktor=7
php artisan labmania:sidebar-to-be
```

Contoh memakai ID Superadmin lokal 7; ganti sesuai akun sah pada salinan yang dituju. Perintah pertama hanya simulasi; perintah terakhir harus menghasilkan changes kosong. Master Role mendapat kode/Gate `master_data.role` dengan hak awal dari permission_group. Setelah itu haknya terpisah; command tidak mengembalikan hak yang sudah dicabut. Command hanya menerima salinan lokal atau DB test terisolasi. Setelah mengubah source, build dan recreate service app saja; jangan reset volume atau menjalankan ulang impor.

Backup/rollback penerapan 15-11-2026 didokumentasikan di `.artifacts/perbaikan_sidebar_tiga_level/`. Penyesuaian menu/hak dan audit ini adalah perubahan sengaja pada target sesudah snapshot; database sumber tetap tidak berubah. Jangan menambahkan pengecualian verifier yang mengabaikan seluruh tabel menu/hak.

## Indeks Master Data dan DataTables — 15-11-2026

Seluruh indeks Master Data memakai lebar penuh. Empat belas daftar memakai DataTables server-side: empat atribut, Product, Fixed/Variable Cost, User, Role, Peserta, Company, Trainer dan dua template. Katalog tetap kartu; Role & Permission tetap matriks. Form/modal dan Gate aksi tetap memakai controller existing.

Pencarian, pengurutan dan pagination diproses endpoint GET `/master/.../data`; pilihan halaman 10/25/50/100, default 25. Filter khusus diterapkan lewat tombol Terapkan. Setelah simpan, tabel dimuat ulang dengan posisi halaman dipertahankan; halaman kosong setelah penghapusan disesuaikan. Index Inertia tidak lagi membawa props `rows`. Regex dan pencarian per kolom tidak didukung; kolom SQL dan field respons ditentukan server.

Dependency dikunci melalui `package-lock.json`. Rebuild/recreate app saja dengan `docker compose build app` lalu `docker compose up -d --no-deps --no-build app`; tidak perlu migration, seed, import atau reset volume. Pemeriksaan frontend tanpa browser: `node tests/frontend/serverTable.mjs` dan `node tests/frontend/serverTableLifecycle.mjs` dari folder `laravel/`, ditambah `npm run build`.

Bukti penerapan dan rollback image ada di `.artifacts/master_data_datatables/`. Tag lokal sebelum perubahan: `labmania-realdata:before-master-datatables-15-11-2026`. Pemulihan image tidak memerlukan pemulihan database.


## Konfirmasi perubahan Master Data - 15-11-2026

SweetAlert2 11.26.25 meminta persetujuan sebelum tambah, edit, ubah status, hapus,
reset password, simpan lingkup dan simpan permission/override. Berlaku pada semua
halaman Master Data; Master Program dan transaksi di luar cakupan ini.
Form modal tetap dipakai untuk mengisi data; konfirmasi terakhir muncul setelah
Simpan. Konfirmasi hapus lama diganti, sehingga tidak meminta persetujuan dua kali.

Dialog menyebut nama/kode sasaran. Batal atau Escape tidak mengirim request.
ID, URL dan payload dibekukan sebelum dialog; klik ganda ditahan sampai request
selesai. Password tidak ditampilkan dalam dialog. Validasi server, Gate dan audit
existing tetap berlaku. Pencarian, filter, lihat detail, CV dan cetak tidak meminta
konfirmasi karena bukan perubahan data master.

Pemeriksaan tanpa browser: `node tests/frontend/confirmedMutation.mjs` (48 alur),
tes regresi ActionMenu/DataTables/navigation, `npm run build` dan
`npm audit --omit=dev`. App Docker sudah direbuild/recreate: 14 endpoint berhasil,
15 hash source cocok, hash 62 tabel aplikasi tidak berubah.
Rollback image: `labmania-realdata:before-master-confirmation-15-11-2026`;
tag ulang menjadi `labmania-realdata:local`, lalu recreate service app saja.
Tidak perlu migration, seed, import atau reset volume.

## Indeks modul lebar penuh - 15-11-2026

Sebelas indeks memakai opsi AppLayout full-width: Program, Penjualan Program,
Quotation, Sales Order, Invoicing, Activity Log, Pengaturan Menu, Manage Peserta,
Monitoring Peserta, Pengajuan Biaya dan Approval Pengajuan. Seluruh tab indeks
ikut memakai lebar area kanan sidebar; padding dan scroll tabel tetap existing.
Form tambah/edit/detail mempertahankan batas lebar sebelumnya.

Perubahan hanya atribut layout pada 11 halaman Vue; CSS global, backend, DB,
COA level 5 dan konfigurasi environment tidak berubah. Build/typecheck lulus;
11 bundle target dan 77 hash source/aset cocok pada Docker localhost:8080.
Tidak melakukan tes browser manual atau mengulang tes PHP untuk atribut layout.
Rollback image: labmania-realdata:before-index-fullwidth-15-11-2026, tag ulang
sebagai labmania-realdata:local lalu recreate app saja. Tidak reset volume.
Bukti: .artifacts/index_full_width/walkthrough_index_full_width_15-11-2026.md.

## Input nominal - 15-11-2026

Program, Penjualan Program, Quotation dan Pengajuan/Overbudget memakai input
nominal rata kanan dengan separator ribuan titik otomatis. Model tetap digit
mentah, contoh 1500000 tampil 1.500.000. Sepuluh binding, satu MoneyInput Vue.
Qty/JP/persen/rekening tetap format existing. Tes komponen dan kalkulasi lulus;
vue-tsc/Vite lulus. Build aktif localhost:8080, 74 hash source/aset cocok,
Compose/environment dan lima source backend/layout terlindungi identik.
Tidak ada perubahan DB/production atau tes browser manual.
Rollback image: labmania-realdata:before-money-input-15-11-2026.
Bukti: .artifacts/input_nominal/walkthrough_input_nominal_15-11-2026.md.
