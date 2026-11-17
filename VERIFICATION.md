# Checklist verifikasi production

Dijalankan operator setelah deploy atau pembaruan, sebelum trafik dibuka (cutover)
dan setelahnya. Melengkapi [PRODUCTION.md](PRODUCTION.md); tidak menggantikan
rehearsal/cutover di `rehearsal/README.md`. Setiap butir punya **perintah atau
langkah**, **hasil yang benar**, dan **bila gagal**. Centang hanya bila hasilnya
teramati sendiri, bukan karena `/up` hijau: `/up` hanya membuktikan aplikasi boot.

Variabel dipakai di bawah (sesuaikan):

```bash
P="docker compose -p labmania-production --env-file .env.production -f compose.production.yaml"
URL=https://apps.sendigs.com/labmania
```

Jangan menempel hasil `config` penuh, `docker inspect` penuh, isi `.env.production`,
cookie, atau token konfirmasi ke chat/tiket. Gunakan akun uji, bukan akun pimpinan.

## A. Sebelum trafik dibuka

### A1. Backup dan jalur mundur
- [ ] Backup terbaru ada dan lolos verifikasi:
  `bash scripts/backup-production.sh --verify /srv/labmania/backups/labmania-<stamp>`
  - Benar: `PASS  backup utuh`.
- [ ] `APP_KEY` asal tersimpan terpisah dan sama dengan `.env.production`. Salah
  kunci = data terenkripsi dan sesi tidak terbaca.
- [ ] Image versi sebelumnya masih ada (`docker image ls`) bila ini pembaruan.
- [ ] Restore pernah diuji ke database kosong dan jumlah baris cocok dengan manifest
  (lihat A3). Backup yang belum diuji restore belum jalur mundur.

### A2. Server dan container
- [ ] `bash scripts/preflight-production.sh --db` selesai tanpa FAIL.
  - Gagal: perbaiki sesuai pesan; `WARN` dibaca dan dicatat.
- [ ] `$P ps` menunjukkan `app` berstatus `healthy`, bukan hanya `running`.
  Compose tidak menunggu health: tunggu sampai `start_period` 60 detik lewat.
- [ ] `$P logs --tail=100 app` tanpa `Exception`, `SQLSTATE`, atau `Permission denied`.
- [ ] `curl --fail http://127.0.0.1:8081/up` jawab 200 (sesuaikan port).
- [ ] `curl -s -o /dev/null -w '%{http_code}\n' http://127.0.0.1:8081/labmania/login` = `200`.
  - 404 = vhost container tidak terpasang. Periksa mount
    `rehearsal/apache.production.conf` pada `$P config --quiet`/`$P exec app ls /etc/apache2/sites-available`.
- [ ] Port hanya di loopback: `ss -lnt 'sport = :8081'` menunjukkan `127.0.0.1:8081`,
  bukan `0.0.0.0` atau `*`. `TRUSTED_PROXIES=*` aman hanya karena ini.

### A3. Database dan data
- [ ] `$P exec --user www-data app php artisan db:show --database=mysql` terhubung.
- [ ] Akun aplikasi bukan `root` dan hanya punya `SELECT, INSERT, UPDATE, DELETE`:
  `sudo mysql -e "SHOW GRANTS FOR '<akun>'@'<host>'"`.
  - Host berupa `jaringan/netmask` subnet Docker, bukan `%`. Bila network dibuat ulang
    dan subnet berubah, jalankan ulang `scripts/provision-db.sh` dan cabut akun lama.
- [ ] Jumlah baris tiap tabel sama dengan manifest backup/snapshot sumber. Untuk database
  hasil restore dibanding manifest backup (jalankan sebagai admin MySQL):

  ```bash
  DB=<database laravel>
  awk -F'\t' '/^  [^ \t]+\t[0-9]+$/ {sub(/^  /,""); print}' <folder-backup>/manifest.txt > /tmp/expected.tsv
  sudo mysql --batch --skip-column-names -e "select table_name from information_schema.tables where table_schema='$DB' and table_type='BASE TABLE' order by 1" \
    | while read -r t; do printf '%s\t%s\n' "$t" "$(sudo mysql --batch --skip-column-names -e "select count(*) from \`$DB\`.\`$t\`")"; done > /tmp/actual.tsv
  diff /tmp/expected.tsv /tmp/actual.tsv && echo SAMA
  ```

  - Benar: `SAMA`. Beda = restore tidak lengkap atau ada transaksi baru setelah capture:
    lakukan capture final dan rekonsiliasi; jangan menjalankan dua aplikasi penulis.
- [ ] Nominal, relasi, dan hak akses dicocokkan dengan sumber pada beberapa baris
  nyata yang dipilih BA/finance (bukan hanya hitungan baris).
- [ ] Tidak ada seed akun contoh: akun yang bisa login hanya akun dari snapshot.

### A4. Dokumen dan font
- [ ] `ls /srv/labmania/storage/app/private/documents` berisi folder historis
  (`uploads/...`) dan pemiliknya `33:33`.
- [ ] Hash beberapa berkas sampel sama dengan sumber (`sha256sum`).
- [ ] Folder font berisi `times.ttf` dan `timesbd.ttf`; cetak PDF (butir C3) tidak jatuh ke font lain.

### A5. COA/GL (finance)
- [ ] Akun GL hanya `SELECT` pada `coa_master`; koneksi `COA_DB_*` berhasil
  (`preflight --db` menampilkan `OK COA_DB`).
- [ ] PK akun pada `INVOICE_COA_*` diverifikasi terhadap GL **tujuan**; ID lokal
  tidak berlaku di server. Baris `coa_master` untuk tiap nomor harus tunggal.
- [ ] Identitas dan rekening cetak (`INVOICE_BANK`, `INVOICE_REKENING`,
  `INVOICE_ATAS_NAMA`, `INVOICE_PENANDA_TANGAN`, `INVOICE_JABATAN`) hanya diisi nilai
  yang disahkan. Bila kosong, pembuatan/cetak invoice **memang ditolak**; itu benar,
  bukan bug. Catat siapa yang mengesahkan.

## B. Lewat reverse proxy (HTTPS, URL publik)

Dari luar server (laptop operator), bukan dari server.

- [ ] `curl -sI $URL/` -> `302` ke `$URL/login` (**https**, host dan prefix benar).
  - `http://` atau tanpa `/labmania` = header proxy belum benar
    (`RequestHeader set X-Forwarded-Proto "https"`, `ProxyPass .../labmania/`).
- [ ] `curl -sI http://apps.sendigs.com/labmania` (tanpa slash) dialihkan ke
  `https://.../labmania/`. Tanpanya link tanpa slash bisa jatuh ke vhost lain.
- [ ] Buka `$URL/login` di browser: halaman tampil dengan CSS/JS. Di DevTools
  Network semua aset `/labmania/build/assets/...` berstatus 200 (tidak ada 404,
  tidak ada `http://` mixed content).
- [ ] Cookie session: DevTools > Application > Cookies. Nama `labmania_production_session`,
  `Path=/labmania`, `Secure`, `HttpOnly`. `XSRF-TOKEN` `Path=/labmania`, `Secure`.
  - `Path=/` = `SESSION_PATH` tidak terpakai; cookie bocor ke aplikasi lain di host yang sama.
- [ ] Dotfile ditolak: `curl -s -o /dev/null -w '%{http_code}\n' $URL/.env` = `403`/`404`, bukan 200.
- [ ] Aplikasi lain di `apps.sendigs.com` (di luar `/labmania`) tetap normal setelah
  perubahan vhost.
- [ ] `/labmania/up` dari luar sesuai kebijakan (dibuka atau diblok); tidak membocorkan
  detail selain status.

## C. Fungsional (akun uji, di browser)

Cek berurutan; berhenti dan catat bila ada yang gagal.

### C1. Autentikasi
- [ ] Login akun uji sah berhasil; masuk ke Dashboard di `$URL/`.
- [ ] Kredensial salah ditolak dengan pesan wajar, bukan 500.
- [ ] Logout kembali ke `$URL/login` dan sesi tidak bisa dipakai ulang (tombol Back
  tidak membuka halaman terautentikasi).
- [ ] Kunci throttle: 6 kali login salah berturut-turut dari satu IP dengan akun yang
  sama -> kena `429`. Throttle memakai IP klien; bila **semua** pengguna terkunci
  bersamaan, IP yang terlihat adalah gateway Docker (header `X-Forwarded-For` tidak sampai).

### C2. Navigasi dan tabel
- [ ] Sidebar tampil sesuai hak akun uji (menu yang tidak berhak tidak muncul).
- [ ] Klik tiap modul di sidebar: URL di address bar selalu `…/labmania/…` dan halaman
  terbuka. Menu aktif ter-highlight benar (pencocokan memakai path tanpa prefix).
- [ ] Tabel server-side (mis. Quotation, Sales Order, Invoice, Master Trainer) memuat data,
  sort, cari, dan ganti halaman. DevTools: permintaan data ke `/labmania/.../data`
  berstatus 200.
- [ ] Muat ulang (F5) di halaman dalam (mis. detail Sales Order): tetap terbuka,
  tidak 404 dan tidak kembali ke root domain.
- [ ] Wilayah (Provinsi/Kota) di Master Company/Peserta terisi.

### C3. PDF, Excel, dan unduhan
Buka masing-masing lewat tombolnya (bukan menempel URL), pastikan berkas terunduh/terbuka
dan isinya benar (font Times New Roman, tidak kosong):
- [ ] Quotation PDF dan Excel; Quotation batch PDF.
- [ ] Sales Order PDF; Surat Kerja Trainer PDF.
- [ ] Invoice PDF dan Excel (bila konfigurasi finance terisi; bila kosong, harus ada pesan penolakan).
- [ ] Peserta: PDF hadir/name-table/draft dan Excel.
- [ ] Master Trainer CV PDF; Katalog PDF.
- [ ] Preview PDF yang dibuka di tab baru (mis. preview Quotation) tidak diblokir popup.
- Gagal: `$P logs --tail=100 app` untuk `mpdf`/`font`; periksa mount `/fonts` dan
  pemilik `storage/app/mpdf`.

### C4. Dokumen privat dan unggah
- [ ] Buka satu dokumen historis lewat tautan "Lihat/Unduh" (Quotation, konfirmasi,
  refund, lampiran pengajuan biaya, lampiran trainer). Berkas terbuka, bukan 404.
  - 404 untuk berkas yang ada di DB = path relatif di storage tidak sama
    (`storage/app/private/documents/uploads/...`) atau `LEGACY_DOCUMENT_ROOT` perlu diisi.
- [ ] Unggah berkas uji pada fitur yang diizinkan, lalu buka kembali. Berkas
  tersimpan di `$PRODUCTION_STORAGE_DIR/app/private/documents/...` milik `33:33`.
  Hapus data uji sesuai kebijakan; jangan meninggalkan berkas uji di data bisnis.
- [ ] Dokumen **tidak** tersedia tanpa login: buka URL dokumen di jendela privat
  -> diarahkan ke login.
- [ ] `ls public/storage` di dalam container tidak ada (dokumen tidak lewat public/storage):
  `$P exec app sh -c 'test ! -e public/storage && echo OK'`.

### C5. Menulis data dan transaksi
Dengan data uji yang jelas dan disepakati, di lingkungan yang boleh ditulisi:
- [ ] Simpan perubahan pada satu master kecil (konfirmasi SweetAlert muncul, flash
  "berhasil", data berubah, tidak 419/500).
- [ ] Alur bisnis utama sampai selesai di dekat batas finance: Quotation -> Sales Order ->
  Invoice (hanya bila konfigurasi finance disahkan). Jurnal seimbang; periksa baris
  jurnal di GL sesuai kebijakan.
- [ ] Activity log mencatat perubahan di atas dengan akun yang benar.
- [ ] Hapus/ubah data uji sesuai prosedur BA; catat apa yang tersisa.

### C6. Halaman publik (tanpa login, token)
Gunakan token uji yang disiapkan BA, bukan token pelanggan sungguhan.
- [ ] `$URL/konfirmasi/<token>` terbuka tanpa login, form tampil. Respons berisi header
  `Cache-Control: no-store, private`, `Referrer-Policy: no-referrer`,
  `X-Robots-Tag: noindex, nofollow` (`curl -sI`).
- [ ] Link lama `$URL/konfirmasi_kehadiran/<token>` dialihkan ke `$URL/konfirmasi/<token>`
  (https + prefix). Tautan lama dari CI3 sudah beredar.
- [ ] Kirim konfirmasi uji dan preview PDF berfungsi; throttle 12 kali per jam per IP aktif.
- [ ] Token palsu/32 hex acak -> 404, bukan 500 atau data orang lain.
- [ ] Link yang dibuat aplikasi (mis. "Link Konfirmasi Kehadiran" di Quotation) bernilai
  `https://apps.sendigs.com/labmania/konfirmasi/...`, bukan `http://` atau tanpa prefix.
- [ ] Survei publik (`/survey/<token>`) sengaja nonaktif sampai BA mengesahkan
  (`survey_public_enabled=false`); jangan dianggap rusak.

## D. Keamanan dan operasi

- [ ] `APP_DEBUG=false`: paksa error (mis. `$URL/halaman-tidak-ada`) menampilkan halaman
  generik tanpa stack trace/path/SQL.
- [ ] `.env.production` izin `600`, di luar Git (`git status` tidak menampilkannya).
- [ ] Log tidak berisi password/token/APP_KEY: `$P logs --tail=300 app | grep -iE 'password|app_key|token'`
  tidak mengembalikan nilai sungguhan.
- [ ] Rotasi log container aktif (`json-file`, 10 MB x 3 dari Compose).
- [ ] Container hidup kembali setelah reboot server: `docker` aktif saat boot
  (`systemctl is-enabled docker`) dan kebijakan `restart: unless-stopped`. Uji pada
  jendela yang disepakati, bukan di jam sibuk.
- [ ] Jadwal backup rutin berjalan (cron/systemd timer) dan hasilnya diperiksa
  (`--verify`); ruang disk cukup (`df -h`).
- [ ] Firewall: hanya 80/443 (dan SSH) terbuka ke publik; 3306 dan 8081 tidak.

## E. Penutupan

- [ ] Catat tanggal/jam, versi commit (`git rev-parse --short HEAD`), operator, dan hasil
  tiap bagian. Butir yang **tidak** dijalankan ditulis jelas beserta alasannya.
- [ ] Butir gagal punya pemilik dan tenggat; cutover tidak dilanjutkan bila gagal
  pada A, B, C1-C4, C6 atau D (keamanan).
- [ ] Jendela pemantauan: periksa `$P logs` dan `$P ps` pada +15 menit, +1 jam, dan
  hari berikutnya; lalu simpan backup pasca-cutover.

## Rollback singkat

Bila A-D gagal dan tidak bisa diperbaiki di jendela yang disepakati:

1. Tahan trafik di proxy (kembalikan vhost `/labmania` ke keadaan sebelum cutover).
2. `$P stop app` (jangan `down -v`).
3. Pembaruan: build ulang dari commit sebelumnya (`git checkout <commit lama>`;
   `$P build app`; `$P up -d --no-deps app`).
4. Data: pulihkan **hanya bila** belum ada transaksi baru. Bila sudah ada,
   jangan menimpa database terbaru dengan backup lama; rekonsiliasi dulu.
5. Ulangi A2-A3 sebelum membuka trafik lagi dan catat penyebab kegagalan.
