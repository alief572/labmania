# Labmania Laravel

## UI indeks dan DataTables — 15-11-2026

Indeks Master Program, Penjualan Program, Quotation, Sales Order/Surat Kerja,
Invoicing, Activity Log, Menu Sidebar, Manage Peserta/Monitoring, Pengajuan Biaya/
Approval dan Refund memakai `ServerTable` dengan DataTables server-side. Daftar
turunan Quotation, SO per program, peserta per tab serta pengajuan/overbudget per
program memakai endpoint `/data` dengan otorisasi yang sama. Master Data yang
sudah memakai komponen ini tetap dipertahankan.

Paging, pencarian dan sorting berjalan di SQL sebelum enrichment halaman.
Ukuran halaman 10/25/50/100, default 25; pencarian ditunda 300 ms. Kolom turunan
yang belum memiliki urutan SQL ditandai tidak bisa diurutkan. Matriks hak akses,
baris input costing, rincian dokumen dan jurnal bukan indeks DataTables.

Acuan UI adalah berkas langsung `../specs/ui-mockup/produk-to-be/` yang dimuat
`Labmania.html`. Form costing memakai empat kelompok satuan, ringkasan biaya,
input simulasi yang terikat pada header, BEP, skala peserta dan rincian hitung.
Menu Sidebar mengikuti komponen bersama karena tidak ada mockup khususnya.
Simulasi target profit mengikuti batas tampilan 100.000 peserta pada mockup;
perhitungan resmi dan validasi transaksi tetap dilakukan server.

Regresi: PHPUnit SQLite, tes kontrak operasional di MySQL `_test`, seluruh
`tests/frontend/*.mjs`, serta `npm run build`. Tidak diperlukan migration DB
atau dependency baru. Penerapan lokal memperbarui container aplikasi saja;
build frontend harus dibuat ulang dari lockfile saat deploy lingkungan lain.

Sejak 15-11-2026, http://localhost:8080 memakai preview snapshot data asli lokal
melalui [local/README.realdata-8080.md](local/README.realdata-8080.md). Finance
ditunda; transaksi/cetak yang memerlukan konfigurasi kosong tetap ditolak.
Snapshot ini belum disahkan untuk production. DB CI3 dan volume UAT tetap disimpan.
Setup UAT sintetis pada [local/README.md](local/README.md) adalah jalur terpisah;
skrip menolak menimpa lingkungan snapshot yang sedang aktif.
Login seluruh lingkungan memakai username/password tanpa token/TOTP sesuai keputusan pengguna.
Guard bootstrap sintetis tetap terbatas pada DB dan lingkungan UAT lokal.

Perangkat fase 7 dan runbook tersedia di [rehearsal/README.md](rehearsal/README.md).
Kesiapan cutover memerlukan snapshot operasional lengkap, pemetaan sah, dua
rehearsal dan restore terverifikasi; tes sintetis bukan bukti migrasi histori nyata.

Migrasi fase 1–6, diperbarui 14-11-2026. Laravel 13 / PHP 8.5, MySQL 8, Inertia 3 / Vue 3 / TypeScript / Vite. Versi pasti dikunci di composer.lock dan package-lock.json.

Aplikasi CI3 tetap di root. Salinan development sudah diimpor ke `labmania_laravel`; perubahan skema fase 2–6 diterapkan hanya pada salinan tersebut. Tes MySQL memakai `labmania_laravel_test`. Tidak ada migration default users, seed akun, atau cutover pada fase ini. Salinan development hampir kosong, sehingga belum membuktikan kesiapan data produksi. Kontrak field: ../specs/docs-to-be/fondasi-laravel.md dan ERD to-be.

Fase 3 mencakup Penjualan Public/B2B dari Master Program Final, Quotation empat langkah, link konfirmasi peserta/PIC, PDF, Data Peserta dan Deal atomik. Deal mencocokkan Master Peserta (NIK, email, WA; konflik ditolak), membuat satu SO per quotation dan Surat Tugas pertama per program. Fase 4 melanjutkan SO/Surat Tugas, Invoice dan pencatatan lunas penuh. Reschedule, refund, jurnal kas dan cutover tetap pada fase berikutnya.

## Instalasi lokal

Jalankan dari PowerShell di root repository. Runtime portabel lokal tersedia di .artifacts/runtime_fase1/php85; folder artifact tidak ikut Git. Mesin lain perlu PHP 8.5 dengan curl, fileinfo, gd, mbstring, openssl, pdo_mysql, pdo_sqlite, sqlite3, zip, xmlwriter dan intl. Composer 2 dan Node.js yang mendukung Vite 8 diperlukan; instalasi ini memakai npm 11.19.0. Pemeriksaan frontend `.ts` langsung memerlukan Node.js 24 atau versi lain yang mendukung type stripping.

```powershell
$env:PATH = (Join-Path (Get-Location) '.artifacts/runtime_fase1/php85') + ';' + $env:PATH
Set-Location laravel
composer install --no-interaction
npm.cmd ci --ignore-scripts
if (-not (Test-Path .env)) { Copy-Item .env.example .env }
if (-not (Select-String -Path .env -Pattern '^APP_KEY=.+$' -Quiet)) { php artisan key:generate }
npm.cmd run build
php artisan serve --host=127.0.0.1 --port=8000
```

Jalankan key:generate hanya saat instalasi awal dengan APP_KEY kosong. Jangan mengganti kunci instalasi yang sudah memiliki session atau secret terenkripsi. Composer di mesin ini dapat dipanggil langsung dengan `php C:/ProgramData/ComposerSetup/bin/composer.phar` bila wrapper Composer tidak tersedia.

Edit .env menggunakan kredensial khusus database Laravel terpisah. Default nama database labmania_laravel tidak membuat databasenya otomatis. Jangan arahkan ke DB aktif CI3, menjalankan SQL historis, migrate:fresh, atau seed untuk mencoba login. Impor dan rekonsiliasi seluruh tabel/ID/relasi dilakukan pada salinan terisolasi melalui fase migrasi data.

Tidak ada registrasi, reset password publik, atau akun contoh di luar UAT terisolasi. Username ambigu, akun nonaktif dan deleted ditolak. Password benar langsung membuat session dengan regenerasi ID. Perubahan password membatalkan session sebelumnya. Hash password dan secret legacy tidak diubah; endpoint/kontrol 2FA sudah dipensiunkan. Backup APP_KEY dan database bersama-sama karena data arsip terenkripsi tetap memerlukan kunci. Rotasi kunci harus mempertahankan APP_PREVIOUS_KEYS.

## Izin dan audit

Group id 1 tetap Superadmin. Hak efektif menggabungkan role dan override tambahan, memakai tabel ms_menu/ms_menu_aksi/ms_group_akses/ms_user_akses existing. Approve berdiri sendiri; aktif legacy memberi Manage, bukan Approve. Cetak legacy dipertahankan sampai endpoint cetak modul terkait dipindahkan.

Saat setup, isi ACTIVITY_LOG_ROLE_IDS dengan ID role Manajemen dan Admin pilihan Labmania dari DB Laravel, dipisahkan koma. Default kosong memberi akses Activity Log hanya kepada Superadmin. Akun lain memerlukan role yang terdaftar sekaligus hak View; override tidak melewati batas ini. Pengisian daftar ID tidak otomatis memberikan hak View.

write_log() memakai tujuh argumen sesuai helper lama, aktor autentikasi, old/new/detail_changes, redaksi secret dan normalisasi desimal presisi. Konfirmasi publik memakai jalur khusus `Audit::publicConfirmation()`: `created_by=NULL`, penanda `actor_type=publik` dari server, label “Calon peserta / PIC (tanpa login)”. Audit internal tetap menolak aktor kosong. Login/logout tidak masuk Activity Log; kegagalan autentikasi ditulis ke log aplikasi tanpa kredensial.

Retensi 60 hari tersedia lewat logs:prune dan scheduler harian pukul 02:00 Asia/Jakarta. AUDIT_RETENTION_ENABLED=false menjadi kondisi awal. Aktifkan hanya setelah DB terpisah, backup dan kebijakan operasional diperiksa; kemudian pasang cron `php artisan schedule:run` setiap menit. Fase ini tidak menjalankan purge pada data asli. DB dan kolom audit data bisnis tidak ikut dibersihkan.

## Dokumen privat dan web server

Hanya public/ boleh menjadi document root web server. .htaccess menolak akses folder sumber lewat Apache 2.4 dan membuka public/; Nginx/IIS memerlukan konfigurasi setara. Jangan publikasikan root repository, .env, vendor, storage atau .artifacts. Untuk lokal, gunakan artisan serve.

Dokumen memakai storage/app/private/documents, dengan path relatif lama dipertahankan. Tidak ada storage symlink atau route unduh storage generik. Sumber whitelist `/documents/{source}/{id}`: `quotation`, `reschedule`, `refund`, `trainer`, `confirmation`, `company-confirmation`, dan `confirmation-history`. Endpoint memeriksa View pada menu sumber, mengambil path dari DB, membatasi realpath ke root privat dan mencatat unduhan. `?preview=1` hanya membuka PDF/JPEG/PNG inline; format lain tetap diunduh.

Unggahan surat konfirmasi: PDF/JPEG/PNG, maksimal 5 MB. Dokumen customer Deal juga menerima DOC/DOCX/XLS/XLSX; MIME, ekstensi dan struktur Office diperiksa. Gambar rusak atau di atas 16 megapiksel ditolak sebelum decode; perkecil foto atau gunakan PDF. Daftar B2B dibatasi teknis 1.000 baris per request/PDF. Data Peserta diekspor sebagai Excel XML 2003 (`.xml`); seluruh sel bertipe teks untuk menjaga NIK/telepon dan mencegah formula dari input peserta.

LEGACY_DOCUMENT_ROOT opsional untuk membaca salinan dokumen lama saat rehearsal. Nilainya root salinan repository/upload, bukan folder upload jenis tertentu. Tidak menyalin atau memindahkan berkas aktif pada fase ini. Jangan membuka folder uploads CI3 melalui public/storage Laravel.

Sidebar mengikuti `ms_menu`: label, induk, kelompok, urutan, aktif dan hak View. Registry `config/navigation.php` hanya memetakan link legacy ke route Laravel dan menjadi template bootstrap UAT. Menu tanpa route tersedia disembunyikan dari sidebar dan ditandai pada halaman Menu Sidebar; selesaikan sebelum cutover. Tautan konfirmasi CI3 `konfirmasi_kehadiran/{token}` dialihkan ke formulir Laravel dengan token yang sama.

Untuk dev lokal tanpa HTTPS, set SESSION_SECURE_COOKIE=false di `.env` (bukan di `.env.example`).

Sebelum deployment: HTTPS, APP_DEBUG=false, SESSION_SECURE_COOKIE=true, document root public/, dan permission tulis hanya storage/bootstrap/cache. Beberapa instance memerlukan session dan cache bersama agar pembatasan login serta lock konsisten. Penerapan server production dan penerimaan pengguna tetap terpisah dari bukti lokal.

## Pemeriksaan aman

Dari folder laravel/ dengan PHP 8.5 di PATH:

```powershell
php vendor/phpunit/phpunit/phpunit
php vendor/phpunit/phpunit/phpunit -c phpunit.mysql.xml --do-not-cache-result
node tests/frontend/phase3.mjs
node tests/frontend/phase4.mjs
npm.cmd run build
php artisan route:list --except-vendor
composer validate --strict
composer audit --locked
npm.cmd audit
```

PHPUnit default memaksa testing, SQLite :memory:, DB_URL kosong, session/cache array dan retensi nonaktif; fixture sintetis menolak koneksi lain sebelum CREATE. Suite `phpunit.mysql.xml` memakai hanya `labmania_laravel_test`, dengan guard akhiran `_test` sebelum membangun ulang schema fixture. Jangan menjalankan dua suite MySQL bersamaan pada DB test yang sama. Pengujian otomatis mencakup transaksi dan locking MySQL, tetapi tidak membuktikan profil data produksi, kelengkapan file, UAT atau cutover. Browser tidak diuji. Build tidak memerlukan lifecycle npm; jangan aktifkan script dependency secara menyeluruh untuk mengatasi error instalasi.

## Perintah operasi fase 2

- `php artisan labmania:profil-data` : profil kualitas data Master (read-only, hanya angka; menolak `db_labmania`). Jalankan ulang pada salinan berisi data sebelum cutover.
- `php artisan labmania:petakan-hak-menu` : simulasi pemetaan hak role untuk menu baru; tambahkan `--terapkan` untuk menulis ke salinan. Idempoten; menolak `db_labmania` dan `*_test`.
- `php vendor/phpunit/phpunit/phpunit -c phpunit.mysql.xml` : suite integrasi MySQL, hanya pada database berakhiran `_test`.
- `ACTIVITY_LOG_ROLE_IDS` di `.env` memetakan role yang boleh melihat Activity Log selain Superadmin (nilainya khusus lingkungan, jangan diisi di `.env.example`).

## Skema dan batas fase 3

Migration `2026_11_13_130000_penjualan_quotation_to_be.php` hanya menerima `labmania_laravel`, `labmania_laravel_test` atau SQLite `:memory:`. Migration menolak transaksi quotation historis yang belum direkonsiliasi; jangan menghapus guard untuk memaksa migrasi dataset produksi. Rollback destruktif sengaja ditolak. Pemulihan memakai backup salinan beserta berkas privat; uji restore pada rehearsal sebelum deployment.

Nilai sebelum Deal memakai kuota; setelah Deal memakai peserta terpilih. Paket B2B s.d. 25 peserta, tambahan Rp300.000/orang, diskon hanya paket, PIC mengirim satu kali mengikuti usulan tertulis BA yang disetujui sebagai aturan sementara untuk implementasi. Link tunduk pada H-14 inklusif, masa berlaku, status quotation dan Closed; kuota Public Company dapat ditambah saat Terkirim. Tidak ada input peserta manual oleh Sales.

## Operasi fase 4: SO, Surat Tugas dan Invoice

Migration `2026_11_14_090000_sales_order_invoice.php` menerima hanya salinan/test yang sama dengan fase 3. Empat tabel Invoice terpisah dari CI3. Relasi antar tabel transaksi dan SO memakai FK fisik; COA memakai PK `coa_master.id` yang diperiksa server, karena tabel COA existing masih MyISAM. Engine master tidak dikonversi. Nama/nomor akun disnapshot ke jurnal. Jangan mengedit master COA secara konkuren melalui aplikasi lain pada salinan ini.

Menu `sales_order`: View untuk daftar/detail/cetak/dokumen dan Manage untuk Siap invoice. Menu `invoicing`: View untuk daftar/detail/PDF/Excel, Add untuk membuat Invoice, Manage untuk pelunasan/bukti setor WAPU. Migration mendaftarkan menu/aksi secara idempoten tanpa memberikan hak baru kepada role. Superadmin dapat mengatur hak melalui Role & Permission.

Invoice hanya dari SO Siap invoice; Public dan B2B all-in masing-masing satu Invoice aktif per SO. Nomor bulanan `LMI/INV/MM/yy-zzz` dibuat saat Save. Penjualan, DPP dan PPN dihitung server dengan BigDecimal dan pembulatan Rupiah tiap tahap. Header, peserta, nilai, nomor referensi, jadwal dan jurnal merupakan snapshot historis. SO tetap menyimpan status open/siap; Invoiced diturunkan dari Invoice aktif.

Lengkapi Perlakuan PPN Company lama yang masih NULL sebelum Invoice; form Company baru default PKP. Konfigurasi `INVOICE_COA_*_ID` memakai PK akun yang benar bila nomor akun punya lebih dari satu baris. Empat nomor kandidat nyata tersedia sebagai lookup unik di config/labmania.php; sistem menolak pilihan ambigu. Akun WAPU sengaja kosong: isi `INVOICE_COA_UANG_MUKA_WAPU_ID` dan `INVOICE_COA_HUTANG_WAPU_ID` dari master sah sebelum membuat Invoice WAPU. Tidak ada akun contoh yang disisipkan.

PDF Invoice resmi menunggu `INVOICE_BANK`, `INVOICE_REKENING`, `INVOICE_ATAS_NAMA`, `INVOICE_PENANDA_TANGAN`, `INVOICE_JABATAN`; kota/kode dokumen mengikuti usulan tertulis yang dapat dikonfigurasi. Nilai/customer diambil dari snapshot, identitas penerbit dari konfigurasi cetak. Tidak ada tanda tangan/stempel digital fiktif.

Catat pembayaran sekali berarti lunas penuh senilai piutang, tanpa jurnal kas. Bukti setor WAPU sekali membentuk jurnal pembalikan dari akun historis Invoice. Replay identik tidak menggandakan data; kiriman berbeda ditolak. Semua mutasi ditolak pada program Closed. Tidak ada endpoint edit/batal Invoice, pembatalan SO, cicilan, refund atau reschedule pada fase 4.

Surat Tugas baru menyimpan snapshot saat Deal pertama. Surat lama tanpa snapshot tidak diisi otomatis dari master terbaru; tampilan/cetak meminta rekonsiliasi asal. SO dan tanda tangan Surat Tugas memakai tanggal cetak hari ini, sementara tanggal tersimpan tidak berubah. Surat Diganti mencetak snapshot dengan tanda DIGANTI. Dokumen Deal/konfirmasi pada `/sales-order/{id}/documents/{source}/{documentId}` memerlukan View SO dan relasi dokumen ke quotation/peserta Deal yang benar; tidak membutuhkan View Quotation dan tidak membuka dokumen quotation lain.

Excel Invoicing mengikuti filter aktif dan memakai XML Spreadsheet 2003 bertipe teks. Pemeriksaan fase 4 mencakup SQLite, MySQL terisolasi, build, format angka presisi, PDF/ERD, dan hash berkas sumber terlindungi. Pengujian browser, data nyata dan cutover belum dilakukan.

## Operasi fase 5: Reschedule, konfirmasi ulang dan Refund

Migration `2026_11_14_100000_reschedule_refund.php` hanya menerima salinan Laravel/test yang sama dengan fase sebelumnya. Lima tabel baru menyimpan Reschedule, evidence, revisi Quotation, target/jawaban konfirmasi ulang, serta Refund. Migration menambah metadata Keluar dan Invoice Batal; tidak memberikan hak role otomatis. Backup database dan dokumen privat harus dipulihkan bersama; rollback destruktif tetap ditolak.

Reschedule melalui Penjualan Program memerlukan View/Manage `penjualan_program`, program Final dan belum Closed. Batas H-7 jadwal lama inklusif; tanggal baru minimal hari ini +7, selesai mengikuti durasi tersimpan. Trainer/asisten aktif dan alasan/evidence wajib (JPG/PNG/PDF/DOC/DOCX, maksimal 5 MB per berkas). Biaya, durasi, waktu dan lokasi tetap. Perubahan trainer/asisten saja mengganti Surat Tugas tanpa revisi Quotation atau konfirmasi ulang.

Perubahan tanggal menaikkan revisi program, Quotation aktif dan SO; nomor dasar tetap, tampilan memakai `-revN`. PDF Quotation/SO/program sebelum perubahan dibekukan dalam snapshot; jangan membangunnya ulang dari master. Surat Tugas lama menjadi Diganti, surat baru memakai nomor baru dan snapshot. Request identik dapat diulang tanpa menggandakan transaksi; form usang ditolak. Snapshot PDF memperbesar backup database seiring jumlah revisi; ukur ukuran pada rehearsal data nyata sebelum cutover.

Sales mengirim ulang PDF dan link baru. Token lama ditutup; link baru mengarahkan peserta lama ke konfirmasi ulang. Batasnya H-1 pukul 00.00 WIB, terpisah dari batas pendaftaran baru H-14. Public menjawab per peserta; PIC B2B memilih minimal 10 nama existing, tanpa tambahan nama. Pindah jadwal memerlukan surat baru dan langsung efektif; surat lama tetap diarsipkan. Refund/Batal ikut menunggu proses Sales melalui Manage `quotation_program`. GET tidak mengubah peserta diam; setelah batas waktu Sales memprosesnya sebagai tidak ikut sesuai pembayaran.

Belum lunas: Pindah jadwal atau Batal ikut. Sudah lunas: Pindah jadwal atau Refund. Keputusan B2B berlaku per company; nama dicoret PIC diproses tanpa Refund individual. Peserta Deal yang tidak ikut menjadi Keluar, peserta Menunggu menjadi Batal; riwayat tetap tersimpan. Invoice belum dibayar yang mencakup peserta Batal dibatalkan seluruhnya, tetap dapat dilihat/dicetak dengan tanda BATAL. Snapshot/nilai/jurnal tidak ditulis ulang; pembalikan jurnal manual. Peserta lain kembali ditagih dengan Invoice baru setelah seluruh konfirmasi ulang Deal tuntas. Invoice lunas tetap utuh.

Refund memiliki menu/izin terpisah `sales_order_refund`: View untuk daftar/detail/bukti dan Manage untuk penyelesaian Finance tanpa approval. Manage SO tidak memberikan hak ini. Admin harus memberikan izin kepada role Finance yang sah. Maksimum Refund adalah dana lunas historis (`invoice.piutang`), bukan tagihan bruto. B2B per company; Public dibagi berdasarkan `invoice_peserta.nominal` dengan largest remainder, sisa seri ke ID peserta terkecil, bobot sama bila penjualan nol. PPN/biaya lain termasuk hanya sebesar dana yang dibayar; pajak WAPU yang dipungut pihak lain tidak masuk dana Refund.

Finance mentransfer di luar aplikasi, lalu mencatat nominal positif sampai batas dana, bank/rekening/penerima dan bukti PDF/JPG/PNG maksimal 5 MB. Potongan wajib keterangan. Penyelesaian sekali, replay identik aman, data berbeda ditolak; audit tidak memuat nomor rekening atau path bukti. Tidak ada jurnal Refund otomatis atau koneksi transfer bank. Semua mutasi memegang lock program dan ditolak setelah Closed.

Fase 6 tersedia sesuai bagian berikut; fase 7 mencakup rehearsal/cutover. Grant Finance, konfigurasi keuangan/cetak fase 4, rekonsiliasi data/dokumen nyata dan UAT tetap harus diselesaikan sebelum penggunaan produksi.

## Fase 6 — Peserta, sertifikat, survei, biaya dan Close

Jalankan migration `2026_11_14_110000_operasional_biaya.php` hanya pada salinan Laravel, setelah migration fase 3–5. Sepuluh tabel mengikuti ERD to-be. Role bisnis **Admin** dan **Direktur** dibuat tanpa anggota atau grant. Role **1** legacy bernama `admin` tetap Superadmin; Admin bisnis memiliki ID lain. Administrator menentukan anggota dan hak empat menu baru melalui Role & Permission. Penugasan/penamaan role yang memperluas cakupan data semua program hanya boleh dikelola Superadmin.

Manage Peserta membaca peserta Deal dari quotation Deal program Final, mengecualikan peserta Keluar. View mengizinkan lihat, PDF Name Table/Daftar Hadir, dan XLSX. Nama di sertifikat disimpan terpisah dari isian peserta dan Master Peserta; catatan sumber konfirmasi hanya dicatat dalam audit. Draft berwatermark tanpa nomor. **Penerbitan resmi ditolak server** sampai format, nomor, penandatangan dan aturan final disahkan BA. Administrasi BNSP, pengiriman sertifikat historis yang sudah terbit, serta catatan monitoring memakai Manage pada menu terkait. Berkas BNSP privat, PDF/JPG/PNG maksimal 5 MB. Server Name Table membutuhkan `times.ttf` dan `timesbd.ttf` (Times New Roman) pada `PARTICIPANT_FONT_DIR`, default Windows `C:/Windows/Fonts`; sediakan font berlisensi di server target. Font tidak dibundel.

Survei publik sengaja **nonaktif** di `config/labmania.php`, tanpa toggle UI/env. Pertanyaan contoh hanya fixture tes; tidak ada seed pertanyaan operasional. Setelah BA menyetujui pertanyaan dan aturan, masukkan pertanyaan sah dan ubah gate lewat perubahan kode yang direview. Setiap program memakai token acak, satu jawaban per peserta Deal, snapshot teks pertanyaan, rekap dan XLSX. Audit pengisian publik memakai aktor NULL dengan `actor_type=publik`. Sistem tidak mengirim WhatsApp/email otomatis.

Pengajuan Biaya memakai cakupan Sales pada quotation Deal atau akun Trainer program; Direktur, Admin bisnis dan Superadmin melihat semua program, tetap mengikuti izin menu (kecuali Superadmin). Asisten tidak otomatis menjadi tim biaya. Add membuat Kasbon/Direct Payment atau Overbudget, rekening wajib dan Qty desimal. Direct Payment wajib bukti PDF/JPG/PNG maksimal 5 MB/file, paling banyak 10 file; Kasbon opsional. Budget berasal dari costing Final dan target peserta, ditambah Overbudget Disetujui. Pengajuan Menunggu, Disetujui dan Paid tetap mengonsumsi budget; Ditolak/Batal melepaskannya. Batas diperiksa per kategori, sehingga selisih antaritem kategori yang sama diperbolehkan. Item baru Overbudget baru dapat dipakai setelah disetujui.

Approve pada **Approval Pengajuan** hanya untuk Direktur/Superadmin dan tidak boleh memproses pengajuan sendiri. Pembatalan hanya pengaju selama Menunggu; koreksi melalui batal lalu ajukan ulang. Paid memakai **Manage Pengajuan Biaya**, bukan Approve; tanggal bayar wajib antara tanggal pengajuan dan hari ini, keterangan/bukti opsional. Paid adalah pencatatan manual sementara: tidak membuat transfer bank, pencairan Finance, realisasi kasbon atau jurnal. Semua mutasi biaya mengunci program sebelum membaca ulang akses, status dan budget, lalu menyimpan audit dalam transaksi yang sama. Nomor rekening penuh dan path dokumen tidak masuk audit.

Close Program memakai Manage, hanya setelah tanggal selesai lewat (WIB), semua peserta Deal tertagih, seluruh invoice aktif lunas, bukti setor WAPU lengkap, dan konfirmasi ulang/Refund tuntas. Quotation Draft/Terkirim dan biaya Menunggu tetap menjadi peringatan. Close menulis `closed_by/closed_date` dengan status tetap Final dan menutup survei secara atomik; tidak ada Buka Kembali. Quotation/SO/Invoice/reschedule/Refund dan biaya/Overbudget **baru** terkunci. Pengajuan biaya/Overbudget existing masih dapat diproses, sedangkan administrasi sertifikat/BNSP/catatan/cetak/export tetap tersedia.

Tes fase 6: `php vendor/phpunit/phpunit/phpunit --filter Phase6Test --do-not-cache-result` dan `php vendor/phpunit/phpunit/phpunit -c phpunit.mysql.xml --filter Phase6MysqlTest --do-not-cache-result`. Suite MySQL memakai dua proses untuk memeriksa perebutan budget dan Close; hanya database `labmania_laravel_test`. Jangan menjalankan dua suite MySQL sekaligus. Tidak ada cutover atau perubahan aplikasi CI3 dalam fase ini.
