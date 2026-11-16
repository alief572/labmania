# Fondasi Laravel - kontrak identitas, akses, dan Activity Log

Tanggal: 13-11-2026; pembaruan kontrak audit publik 14-11-2026. Cakupan: fondasi fase 1 dan perluasan audit fase 3 migrasi Laravel; berlaku bersama FR Master Data/Role & Permission (`fr-nfr.md` FR-54 s.d. FR-59, FR-64 s.d. FR-65) dan `../docs/fr-nfr-activity-log.md`.

Dokumen ini merekonsiliasi nama persistence yang semula ringkas/berbeda pada ERD. Kontrak ini tidak membuat aturan keuangan, status transaksi, atau data awal baru. Nama field/tabel di bawah berasal dari pembacaan metadata schema-only database development MySQL 8.0.46 pada 13-11-2026; tidak ada pembacaan record pengguna atau DDL/DML pada pemeriksaan tersebut. Profil data dan verifikasi FK fisik belum dilakukan.

## Sumber dan kepemilikan skema

Keputusan pengguna 15-11-2026: sumber master COA adalah
`gl_labmania.coa_master`, melalui koneksi baca khusus. Kolom yang digunakan
tetap `id`, `no_perkiraan`, `nama`, `kdcab`; tidak membuat alias atau tabel baru.
Referensi COA pada Invoice/jurnal adalah referensi aplikasi lintas sumber,
bukan FK fisik ke tabel lama pada database aplikasi. Snapshot nomor/nama akun
pada transaksi historis tetap otoritatif; ID lama tidak dipetakan ulang otomatis.
Keputusan lanjutan pengguna 15-11-2026: hanya `coa_master.level = '5'` yang
boleh dipakai. Dropdown dan validasi akun baru/config/ID request mengikuti
filter ini. Simpan Rate baru maupun edit wajib memakai nomor GL level 5;
data existing tidak diubah massal, histori jurnal tetap snapshot. Dua akun
WAPU memerlukan pemetaan sah terpisah.
COA GL eksternal wajib masuk inventaris snapshot/dependensi cutover tersendiri.

Keputusan pengguna 15-11-2026: tujuh konfigurasi Finance (dua COA WAPU dan lima
isian rekening/penanda tangan Invoice) ditunda. Penundaan mengizinkan kesiapan
operasional dengan opsi eksplisit; bukan izin memakai akun contoh atau mencetak
Invoice resmi tanpa identitas lengkap. Blokir server pada transaksi WAPU/cetak
yang membutuhkan konfigurasi kosong tetap berlaku. Preview snapshot lokal boleh
menggantikan data UAT di localhost:8080, tetapi pengesahan sumber/upload/UAT
untuk production tetap terpisah. Database CI3/sumber dipertahankan.

- ERD yang memiliki fondasi ini: `diagrams/erd.drawio`, halaman **ERD Matrix Akses** dan **ERD Activity Log**, direkonsiliasi 13-11-2026. Gambar menampilkan field relevan; daftar lengkap metadata sepuluh objek yang diperiksa ada di bawah.
- Implementasi asal: `application/modules/users/libraries/Auth.php`, `application/modules/users/controllers/Users.php`, `application/helpers/akses_helper.php`, `application/helpers/log_helper.php`.
- Definisi perubahan historis: `database/migrations/20261031_master_user.sql`, `20261101_permission_menu.sql`, `20261107_master_user_role_override.sql`.
- Relasi pada ERD adalah relasi aplikasi. Label PK/UK berasal dari metadata indeks; tidak mengklaim FK fisik telah ditegakkan. PK gabungan tidak berarti tiap kolom unik secara terpisah.
- Di area fondasi, label lama `employees`, `menus`, `permissions`, `group_permissions`, dan `user_permissions` bukan alias untuk tabel aktual. Rujukan modul lain direkonsiliasi pada fase modul tersebut, tanpa rename diam-diam.

## Identitas dan autentikasi

`users.id_user` tetap identitas akun. Login memakai `username` dan hash `password`, dengan status `st_aktif` dan `deleted` yang sudah ada. Hash lama diverifikasi memakai kemampuan native PHP; jangan memakai cast yang me-rehash password lama saat membaca. Akun tidak dibuat ulang hanya untuk mengikuti kolom default Laravel `id/name/email_verified_at`.

Keputusan pengguna 14-11-2026 pada plan penyelesaian migrasi: login seluruh lingkungan, termasuk production, memakai username/password tanpa token atau TOTP. Akun aktif dan username tidak ambigu tetap wajib; pembatasan percobaan login, regenerasi ID session dan pembatalan session setelah password berubah tetap berlaku. Alur challenge/enrollment/reset 2FA tidak aktif lagi. Guard bootstrap data sintetis tetap terpisah dan tidak diperluas ke production/data asli.

`ga_secret` dipertahankan sebagai data historis, bukan faktor login aktif. Hash password dan secret sumber tidak diubah saat migrasi. Format `encrypted:` yang pernah ditulis fase 1 tetap dipertahankan tanpa konversi massal; CI3 tidak membaca format itu, sehingga koneksi database bersama dilarang. `APP_KEY` wajib dibackup bersama database Laravel; rotasi kunci perlu mempertahankan kunci sebelumnya. Secret tidak dimasukkan props umum, log, atau export.

Indeks `username` belum UNIQUE pada metadata aktual. Pencocokan username ambigu harus ditolak saat login, lalu dimasukkan daftar perbaikan data sebelum cutover. Ini bukan izin menggabungkan akun atau memilih pengguna pertama.

Master User aktual adalah `ms_user_karyawan`, relasi akun opsional unik lewat `id_user`, role lewat `id_group`; akun memakai relasi `user_groups`. `users.employee_id` tidak dijadikan relasi baru fondasi. Objek `employees` tidak ditemukan pada DB development yang diperiksa, meskipun SQL historis menyebutnya VIEW. Tidak ada tabel/view yang dibuat atau dihapus untuk menyamakan kondisi historis tersebut.

## Role, menu, dan permission

| Konsep | Persistence aktual |
|---|---|
| Master Role | `groups.id_group`, `groups.nm_group` |
| Role akun | `user_groups.id_user`, `user_groups.id_group` |
| Menu dan aksi tersedia | `ms_menu`, `ms_menu_aksi` |
| Hak role | `ms_group_akses` |
| Override tambahan user | `ms_user_akses` |

Hak efektif adalah gabungan hak role lewat `user_groups` dan override tambahan. Override tidak mengurangi hak role. Menu nonaktif tidak memberikan hak. Group id 1 adalah Superadmin developer sesuai perilaku existing dan FR-56; role Admin Labmania tetap role biasa yang dapat dibatasi. Tidak ada seed role/hak produksi baru pada rekonsiliasi ini.

Keputusan pengguna 14-11-2026: sidebar langsung mengikuti struktur `ms_menu`, meliputi parent, kelompok, urutan, label, aktif dan hak View. Link CI3 dipetakan eksplisit ke route internal Laravel; konfigurasi route tidak menentukan ulang struktur atau label. Perubahan melalui CRUD Menu tercermin pada request berikutnya. Menu tanpa route tersedia ditandai untuk perbaikan dan tidak diterbitkan sebagai tautan rusak. Pemeriksaan Gate server tetap berlaku meskipun menu ditampilkan.

Keputusan pengguna 15-11-2026: samakan penuh sidebar Master Data dengan mockup to-be/FR-27. Level visual adalah Modul / Submenu / Menu; `parent_id` tetap modul dan `kelompok` tetap nomor kelompok existing. Label submenu di `config/navigation-groups.php` berdasarkan kode modul `m_master_data`: 1 Product, 2 User, 3 Peserta & Trainer, 4 Dokumen. Keanggotaan, urutan, label modul/menu dan status tetap dibaca dari DB; modul lain dan kelompok tak dikenal tidak mendapat label submenu. Shared prop navigation menambah `subgroupLabel`; breadcrumb mengikuti cabang aktif, pencarian juga mencocokkan submenu, Enter membuka hasil pertama, Escape menghapus pencarian sebelum menutup sidebar mobile.

Master Role memakai entri/Gate `master_data.role` pada `/master/role`, terpisah dari `permission_group` pada `/permissions` (Role & Permission). `labmania:sidebar-to-be` menampilkan simulasi; `--terapkan --aktor=<id_superadmin>` menyesuaikan salinan lokal dalam satu transaksi dengan audit. Hak Master Role awal disalin dari katalog/grant role/override user permission_group hanya untuk lihat/tambah/ubah/aktif/hapus dan hanya saat entri pertama dibuat. Hak yang dicabut kemudian tidak disalin ulang. Penyesuaian susunan dilakukan sekali setelah impor DB; CRUD Menu selanjutnya tetap menentukan susunan. Tidak ada schema baru atau perubahan pada database CI3/production.

Activity Log juga memenuhi LOG-FR-09: selain Superadmin, akun wajib memiliki role Manajemen atau Admin yang ditetapkan Labmania **dan** izin View. `ACTIVITY_LOG_ROLE_IDS` memuat ID role tersebut dari database Laravel terpisah, ditetapkan saat setup (Q-35/Q-37); default kosong menolak akun selain Superadmin. Override View tanpa role yang memenuhi syarat tetap ditolak. Jangan menganggap semua pemilik override adalah Manajemen, atau menebak ID Direktur/Admin dari contoh mockup.

| Label target | Nilai aksi persistence |
|---|---|
| View | `lihat` |
| Add | `tambah` |
| Manage | `ubah` (dan kompatibilitas aksi status legacy `aktif` secara eksplisit) |
| Delete | `hapus` |
| Approve | `approve` |

Kolom `aksi` tetap VARCHAR(10). Hak Approve diperiksa terpisah dan **tidak** diperoleh dari `aktif` atau Manage. Nilai lama `cetak` tetap data historis yang tidak dihapus; pemetaan endpoint cetak diperiksa eksplisit saat modulnya dipindahkan. Tidak menganggap semua Manage berarti boleh melakukan seluruh transisi bisnis.

`ms_menu_aksi` memiliki PK `(id_menu, aksi)`. `ms_group_akses` memiliki PK `(id_group, id_menu, aksi)`; `ms_user_akses` memiliki PK `(id_user, id_menu, aksi)`. Hak hanya bermakna untuk aksi yang tersedia pada menu bersangkutan; validasi server tetap wajib walaupun UI menyembunyikan tombol.

## Activity Log

`logs` tetap tabel audit. Kontrak helper:

```php
write_log($module, $action, $description, $data = null,
          $ref_table = null, $ref_id = null, $ref_no = null);
```

`sub_module` adalah kolom existing nullable dan dapat diisi dari payload terstruktur; tidak menambah argumen posisi. Update mengambil snapshot old sebelum perubahan dan new setelah perubahan; child memakai detail_changes. Jangan log password, ga_secret, token, kredensial atau payload autentikasi mentah. Metadata dan nilai angka dinormalisasi mengikuti standar log existing.

Metadata awal CI3: `logs.created_by` adalah **INT NOT NULL**. Fase 3, sesuai keputusan pengguna pada rencana yang disetujui 13-11-2026, mengubah kolom menjadi **INT NULL hanya di database Laravel**. Audit internal tetap memerlukan aktor login sah dan gagal eksplisit bila tidak ada aktor, tanpa fallback user 1. Audit konfirmasi publik memakai `created_by=NULL` dan payload `actor_type=publik` yang ditetapkan server melalui `Audit::publicConfirmation()`, ditampilkan sebagai **Calon peserta / PIC (tanpa login)**. NULL tanpa penanda tersebut tidak dianggap publik. Login/logout tidak masuk Activity Log sesuai LOG-FR-04; aktivasi autentikator dicatat tanpa secret. Kegagalan autentikasi menggunakan security log aplikasi tanpa username/password/kode atau identitas admin fiktif. CI3 dan skema sumber tidak diubah.

Riwayat memakai `ref_table/ref_id/ref_no`, dan akses diperiksa server melalui permission Activity Log/riwayat. Nomor resmi dokumen dipertahankan. Rekonsiliasi ini tidak menjalankan purge atau mengubah kebijakan retensi FR Activity Log.

## Dokumen privat fase 1

Dua sumber diperiksa schema-only pada 13-11-2026 dan sesuai SQL `20261112_to_be_quotation_so.sql` serta ERD Quotation/Penjualan Program:

| Sumber endpoint | Tabel dan field aktual | Hak dan prefix path |
|---|---|---|
| `quotation` | `quotation_dokumen`: `id`, `id_quotation`, `nama_file`, `path_file`, `tipe_file`, `ukuran_byte`, `created_by`, `created_date` | View `quotation_program`; `uploads/quotation_dokumen/` |
| `reschedule` | `penjualan_program_reschedule_evidence`: `id`, `id_reschedule`, `nama_file`, `path_file`, `tipe_file`, `ukuran_byte` | View `penjualan_program`; `uploads/penjualan_program_reschedule/` |

Endpoint menerima sumber whitelist dan ID dokumen, bukan path dari request. Path dari DB dibatasi prefix sumber dan hasil realpath dalam root privat; path traversal, byte NUL dan file hilang ditolak. Root salinan dokumen legacy bersifat opsional, tidak mengubah lokasi file aktif. Sumber lain ditambahkan pada fase modul masing-masing. Metadata tidak membuktikan keberadaan/kelengkapan file atau FK fisik; rekonsiliasi dokumen masih fase 7.

## Metadata field identitas dan akses aktual

Daftar berikut merekam field fisik yang ada pada saat pemeriksaan; bukan instruksi membuat ulang tabel atau menjalankan migrasi. Field di luar kebutuhan fase 1 tetap dipertahankan.

### `users`

Jenis: BASE TABLE.

| Kolom | Tipe aktual | Nullable | Key metadata |
|---|---|---|---|
| `id_user` | `int AUTO_INCREMENT` | NO | PRI |
| `username` | `varchar(45)` | YES | - |
| `password` | `varchar(100)` | YES | - |
| `email` | `varchar(100)` | YES | - |
| `nm_lengkap` | `varchar(100)` | YES | - |
| `alamat` | `varchar(255)` | YES | - |
| `kota` | `varchar(20)` | YES | - |
| `hp` | `varchar(20)` | YES | - |
| `kdcab` | `varchar(10)` | YES | - |
| `work_location` | `varchar(100)` | YES | - |
| `group_id` | `varchar(100)` | YES | - |
| `ip` | `varchar(45)` | YES | - |
| `login_terakhir` | `datetime` | YES | - |
| `st_aktif` | `tinyint(1)` | YES | - |
| `photo` | `varchar(255)` | YES | - |
| `created_on` | `datetime` | YES | - |
| `deleted` | `tinyint(1)` | YES | - |
| `department_id` | `varchar(50)` | YES | - |
| `employee_id` | `varchar(255)` | YES | - |
| `ga_secret` | `text` | YES | - |

### `user_groups`

Jenis: BASE TABLE.

| Kolom | Tipe aktual | Nullable | Key metadata |
|---|---|---|---|
| `id_user` | `int` | NO | PRI |
| `id_group` | `int` | NO | PRI |

### `groups`

Jenis: BASE TABLE.

| Kolom | Tipe aktual | Nullable | Key metadata |
|---|---|---|---|
| `id_group` | `int AUTO_INCREMENT` | NO | PRI |
| `nm_group` | `varchar(50)` | NO | - |
| `ket` | `varchar(100)` | NO | - |
| `st_default` | `tinyint(1)` | YES | - |
| `created_by` | `int` | YES | - |
| `created_date` | `datetime` | YES | - |
| `updated_by` | `int` | YES | - |
| `updated_date` | `datetime` | YES | - |

### `employees`

Tidak ditemukan pada database development yang diperiksa. Tidak diasumsikan sebagai tabel/view yang tersedia.

### `ms_user_karyawan`

Jenis: BASE TABLE.

| Kolom | Tipe aktual | Nullable | Key metadata |
|---|---|---|---|
| `id` | `int AUTO_INCREMENT` | NO | PRI |
| `kode` | `varchar(20)` | NO | UNI |
| `nama` | `varchar(100)` | NO | - |
| `nik` | `varchar(16)` | YES | - |
| `tmp_lahir` | `varchar(50)` | YES | - |
| `tgl_lahir` | `date` | YES | - |
| `department` | `varchar(50)` | YES | - |
| `division` | `varchar(50)` | YES | - |
| `jabatan` | `varchar(30)` | YES | - |
| `id_group` | `int` | YES | - |
| `gender` | `varchar(10)` | YES | - |
| `agama` | `varchar(20)` | YES | - |
| `telp` | `varchar(20)` | YES | - |
| `email` | `varchar(100)` | YES | - |
| `alamat` | `varchar(255)` | YES | - |
| `npwp` | `varchar(20)` | YES | - |
| `tgl_masuk` | `date` | YES | - |
| `tipe` | `varchar(20)` | YES | - |
| `jobdesc` | `varchar(500)` | YES | - |
| `aktif` | `tinyint(1)` | NO | - |
| `id_user` | `int` | YES | UNI |
| `created_by` | `int` | YES | - |
| `created_date` | `datetime` | YES | - |
| `updated_by` | `int` | YES | - |
| `updated_date` | `datetime` | YES | - |

### `ms_menu`

Jenis: BASE TABLE.

| Kolom | Tipe aktual | Nullable | Key metadata |
|---|---|---|---|
| `id` | `int AUTO_INCREMENT` | NO | PRI |
| `parent_id` | `int` | NO | MUL |
| `kode` | `varchar(60)` | NO | UNI |
| `judul` | `varchar(100)` | NO | - |
| `link` | `varchar(150)` | NO | - |
| `ikon` | `varchar(30)` | YES | - |
| `kelompok` | `int` | NO | - |
| `urutan` | `int` | NO | - |
| `aktif` | `tinyint(1)` | NO | - |
| `sistem` | `tinyint(1)` | NO | - |
| `created_by` | `int` | YES | - |
| `created_date` | `datetime` | YES | - |
| `updated_by` | `int` | YES | - |
| `updated_date` | `datetime` | YES | - |

### `ms_menu_aksi`

Jenis: BASE TABLE.

| Kolom | Tipe aktual | Nullable | Key metadata |
|---|---|---|---|
| `id_menu` | `int` | NO | PRI |
| `aksi` | `varchar(10)` | NO | PRI |

### `ms_group_akses`

Jenis: BASE TABLE.

| Kolom | Tipe aktual | Nullable | Key metadata |
|---|---|---|---|
| `id_group` | `int` | NO | PRI |
| `id_menu` | `int` | NO | PRI |
| `aksi` | `varchar(10)` | NO | PRI |

### `ms_user_akses`

Jenis: BASE TABLE.

| Kolom | Tipe aktual | Nullable | Key metadata |
|---|---|---|---|
| `id_user` | `int` | NO | PRI |
| `id_menu` | `int` | NO | PRI |
| `aksi` | `varchar(10)` | NO | PRI |

### `logs`

Jenis: BASE TABLE.

Tabel berikut adalah metadata awal CI3 pada 13-11-2026. Pengecualian target Laravel fase 3: `created_by` nullable (YES), sesuai kontrak audit publik di atas.

| Kolom | Tipe aktual | Nullable | Key metadata |
|---|---|---|---|
| `id` | `bigint AUTO_INCREMENT` | NO | PRI |
| `module` | `varchar(50)` | NO | MUL |
| `sub_module` | `varchar(80)` | YES | - |
| `action` | `varchar(30)` | NO | - |
| `description` | `varchar(255)` | NO | - |
| `ref_table` | `varchar(50)` | YES | MUL |
| `ref_id` | `int` | YES | - |
| `ref_no` | `varchar(50)` | YES | - |
| `data` | `json` | YES | - |
| `created_by` | `int` | NO | - |
| `created_date` | `datetime` | NO | MUL |
| `ip_address` | `varchar(45)` | YES | - |

## Batas dan tindak lanjut

- Jangan mengubah database CI3 aktif untuk menyiapkan aplikasi Laravel. Gunakan database/lingkungan terpisah; test hanya data sintetis terisolasi.
- Sebelum cutover, profilkan username duplikat, akun aktif/deleted, keterkaitan karyawan/role, hak efektif dan secret TOTP tanpa mencetak data pribadi atau secret.
- Secret legacy tetap kompatibel tanpa encrypted cast. Konversi massal dan strategi rollback format baru `encrypted:` harus masuk rehearsal migrasi; jangan menyalin nilai baru ke CI3 tanpa konversi terkontrol.
- Foreign key fisik dan isi data belum diverifikasi; pemeriksaan metadata kolom/index tidak menjamin tidak ada data yatim.
- Nama/kolom Invoice, Company, Product, sertifikat/survey, dan transaksi lain tidak diubah melalui kontrak fondasi ini.
