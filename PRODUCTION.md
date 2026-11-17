# Docker production

`compose.production.yaml` masuk Git; `.env.production` dan hasil render Compose
tetap privat. File ini hanya menjalankan aplikasi, memakai Dockerfile existing
untuk PHP 8.5, Composer dan build Node.js 24. Tidak ada service database, impor,
migration, seed atau penggantian APP_KEY otomatis. Jangan memakai Compose root
lokal, setup UAT, atau `down -v` untuk deployment ini.

Topologi server: MySQL existing berjalan langsung di Ubuntu, aplikasi Laravel
di Docker. Compose membuat jaringan bridge project secara otomatis dan memakai
`host.docker.internal:host-gateway` untuk mencapai host Linux. Tidak perlu
container MySQL, nama jaringan database existing, atau memindahkan MySQL server.

## Dukungan subpath `/labmania/`

Target pengguna adalah `https://apps.sendigs.com/labmania/`. Prefix
dipertahankan end-to-end, tidak dikupas oleh proxy:

- Apache host meneruskan `/labmania/...` apa adanya ke `127.0.0.1:8081`.
- Apache di container (`rehearsal/apache.production.conf`, dipasang read-only
  oleh Compose) melayani `public/` di bawah `Alias /labmania` memakai
  `FallbackResource /labmania/index.php`. `.htaccess` sengaja tidak dipakai:
  rewrite-nya membuat `SCRIPT_NAME=/index.php` sehingga Laravel kehilangan
  base path. `DocumentRoot` tetap melayani `/up` untuk healthcheck.
- Aset di-build dengan `VITE_BASE=/labmania/build/` (build arg Compose).
  Pindah URL berarti image harus dibuild ulang.
- `TRUSTED_PROXIES=*` di Compose membuat Laravel memercayai `X-Forwarded-*`.
  Aman karena port hanya terikat ke `127.0.0.1`; jangan membuka port itu ke
  jaringan tanpa mengganti nilainya dengan IP proxy.
- `SESSION_PATH=/labmania` di Compose membatasi cookie ke subpath.
- Frontend memakai `appUrl()` ([resources/js/composables/appUrl.ts](resources/js/composables/appUrl.ts))
  yang membaca prefix dari `<meta name="app-base">`. URL baru di frontend yang
  diawali `/` wajib dibungkus `appUrl()`.

Contoh vhost Apache host (SSL, modul `proxy`, `proxy_http`, `headers`, `ssl`
harus aktif; sesuaikan sertifikat dan vhost existing):

```apache
ProxyPreserveHost On
RequestHeader set X-Forwarded-Proto "https"
RedirectMatch 301 ^/labmania$ /labmania/
ProxyPass        /labmania/ http://127.0.0.1:8081/labmania/
ProxyPassReverse /labmania/ http://127.0.0.1:8081/labmania/
```

Yang sudah diuji di mesin pengembangan (container sementara, tanpa database):
`/labmania/up`, `/labmania/login`, aset `/labmania/build/...`, redirect dan URL
aset berskema `https` serta berprefix di balik header proxy, cookie
`path=/labmania; secure`, dotfile ditolak (403).

**Belum terbukti dan wajib dicek saat rehearsal di server:** login sampai
selesai (butuh database; uji lokal berhenti di 500 karena DB fixture tidak
ada), navigasi sidebar, tabel server-side, PDF/unduhan, upload dokumen, link
konfirmasi publik `/labmania/konfirmasi/{token}`, dan perilaku di balik Apache
host sungguhan. Port lokal `/up` hanya menguji boot aplikasi, bukan akses
database, berkas historis, login, finance atau penerimaan production. Dokumen
ini tidak menggantikan rehearsal/cutover di `rehearsal/README.md`.

## Persiapan server Ubuntu

1. Sediakan Docker Engine >= 20.10 dan Compose plugin >= 2.24 mengikuti panduan
   resmi Ubuntu di https://docs.docker.com/engine/install/ubuntu/. Periksa
   versi Ubuntu dan paket existing sebelum instalasi; jangan menghapus paket
   runtime yang dipakai layanan lain. Jalankan seluruh perintah dari root
   repository. Apache dan MySQL Ubuntu tetap dipertahankan.

   ```bash
   docker --version
   docker compose version
   ```

2. Siapkan akses MySQL host dari bridge Docker. `DB_HOST` dipaksa menjadi
   `host.docker.internal` oleh Compose; `DB_PORT` memakai port MySQL host
   (default 3306). `127.0.0.1` di container berarti aplikasi itu sendiri.
   Tidak perlu `docker inspect labmania_db`: nama tersebut milik runtime lokal,
   bukan database production. Periksa listener host terlebih dahulu:

   ```bash
   sudo ss -lntp 'sport = :3306'
   docker network inspect bridge --format '{{(index .IPAM.Config 0).Gateway}}'
   ```

   Jika MySQL hanya mendengarkan `127.0.0.1`, container belum dapat mengaksesnya.
   Operator perlu menambahkan listener pada IP bridge host yang dipakai
   `host-gateway`, mempertahankan listener dan akses aplikasi existing. Periksa
   apakah daemon mengatur override `host-gateway-ip`; jangan mengasumsikan
   gateway project sama dengan gateway host. Sesuaikan `bind-address` dengan
   versi MySQL; jangan membuka 3306 ke internet atau langsung menggantinya
   menjadi `0.0.0.0`. Perubahan listener/restart MySQL perlu jendela operasional
   karena database dipakai layanan lain, bukan bagian startup Compose.

   Akun Laravel harus memperoleh hak hanya pada database Laravel baru dan
   berasal dari subnet project Docker yang benar, bukan akun `root` atau host
   `%`. Sesudah backend dibuat, lihat subnet sumber dengan:

   ```bash
   docker network inspect labmania-production_default --format '{{json .IPAM.Config}}'
   ```

   Batasi firewall ke subnet/interface Docker terkait; pertahankan aturan
   aplikasi existing. Periksa ulang grant/firewall jika jaringan dibuat ulang
   dan subnet berubah. Listener, grant dan firewall adalah tiga pemeriksaan
   terpisah; alias `host.docker.internal` saja tidak memberinya akses.
3. Pindahkan snapshot Laravel lokal mengikuti bagian pemindahan di bawah.
   Siapkan direktori storage khusus production di luar repository, misalnya
   `/srv/labmania/storage`. Pulihkan storage existing ke direktori ini,
   termasuk `app/private/documents` dengan path relatif yang sama. Jangan
   menganggap volume kosong sebagai pemindahan upload. Pastikan berkas/direktori
   yang harus ditulis aplikasi dimiliki UID/GID `www-data` image (33:33).
   Startup hanya menyesuaikan pemilik direktori runtime, bukan seluruh berkas
   hasil restore. Jangan menunjuk storage yang masih ditulis aplikasi lain.
4. Siapkan folder Times New Roman berlisensi di host, misalnya
   `/srv/labmania/fonts`, berisi `times.ttf` dan `timesbd.ttf`; dipasang read-only.
5. Buat environment baru hanya jika belum ada, tanpa menimpa `.env` lokal/server:

   ```bash
   test -f .env.production || (umask 077; cp .env.production.example .env.production)
   chmod 600 .env.production
   nano .env.production
   ```

   Sesuaikan baris existing dan tambahkan variabel production berikut. Nilai
   bertanda `<...>` wajib diganti; jangan salin secret dari contoh publik.

   ```dotenv
   APP_ENV=production
   APP_DEBUG=false
   APP_KEY='<APP_KEY instalasi Laravel asal>'
   APP_URL=https://apps.sendigs.com/labmania
   DB_CONNECTION=mysql
   DB_HOST=host.docker.internal
   DB_PORT=3306
   DB_DATABASE=<database Laravel terverifikasi>
   DB_USERNAME=<akun khusus Laravel, bukan root>
   DB_PASSWORD='<password database>'
   PRODUCTION_HTTP_PORT=8081
   PRODUCTION_STORAGE_DIR=/srv/labmania/storage
   PRODUCTION_FONT_DIR=/srv/labmania/fonts
   SESSION_PATH=/labmania
   SESSION_DOMAIN=null
   SESSION_SECURE_COOKIE=true
   ```

   Pertahankan APP_KEY asal jika membawa data Laravel existing. Instalasi baru
   saja boleh memakai kunci baru; Compose tidak membuatnya. Kutip secret dengan
   kutip tunggal sesuai format dotenv agar `$` tidak diinterpolasi; jangan
   kirim file environment ke chat atau Git. Konfigurasi COA memakai akun GL
   SELECT-only yang sah, host yang terjangkau dari jaringan aplikasi, dan
   field finance/cetak dari `.env.example`; nilai kosong tetap menahan operasi
   terkait. Jangan memalsukan akun, identitas, rekening atau grant menu.

   Jika GL/COA juga berada pada MySQL host Ubuntu yang sama, isi
   `COA_DB_HOST=host.docker.internal` dan `COA_DB_PORT` sesuai port host.
   Compose tidak menebak lokasi GL atau memberi grant reader otomatis.

## Pemindahan salinan lokal ke MySQL Ubuntu

Sumber yang dipilih adalah runtime Laravel lokal 8080: database
`labmania_laravel` pada service `rehearsal-db`, bukan `labmania_db` (GL),
database CI3, UAT sintetis, atau koneksi `.env` PHP lokal. Verifikasi ulang
runtime sumber sebelum capture. Snapshot lokal belum merupakan penerimaan
production; ikuti rekonsiliasi, rehearsal/restore dan keputusan cutover pada
`rehearsal/README.md` serta `local/README.realdata-8080.md`.

1. Sepakati jendela capture dan hentikan seluruh writer sumber Laravel saat
   capture DB/berkas; maintenance web saja tidak menghentikan proses penulis
   lain. Jangan mematikan database GL aktif. Backup schema/data Laravel dengan
   tool MySQL yang kompatibel, tanpa CREATE/DROP DATABASE atau USE yang bisa
   mengarahkan restore ke database lain. Sertakan semua objek aplikasi yang
   dibutuhkan dan catat jumlah tabel/baris, ukuran serta hash berkas. Jangan
   membuat dump memakai kredensial pada argumen CLI atau artifact Git.
2. Pada waktu capture yang sama, amankan dokumen privat beserta path relatif,
   APP_KEY dan konfigurasi aplikasi dari environment **container app aktif**.
   `.env` root lokal bukan acuan runtime 8080. Gunakan kanal transfer privat;
   jangan menempel secret ke chat atau menyalin seluruh environment lokal ke
   production. Akun, password dan hak bisnis ikut snapshot, tanpa seed akun
   atau grant menu otomatis.
3. Backup database server yang sudah ada. Siapkan **database Laravel terpisah
   yang kosong** dan akun aplikasi tersendiri pada MySQL Ubuntu. Periksa isi
   dump serta tujuan koneksi sebelum restore; jangan menimpa database aktif
   CI3, GL atau database yang sudah berisi transaksi. Restore salinan ke target
   kosong dengan klien MySQL operator. Kegagalan restore ditangani pada target
   kosong baru, bukan `migrate:fresh`/seed pada database existing.
4. Pulihkan dokumen ke `PRODUCTION_STORAGE_DIR`, APP_KEY asal ke
   `.env.production`, dan sesuaikan hanya koneksi/path/URL production yang
   diperlukan. Jangan membawa session, cache, compiled view atau file
   `storage/framework/down` dari lokal; pertahankan data bisnis dan dokumen.
   Jika memakai tool arsip existing, periksa cakupannya: arsip dokumen bukan
   otomatis seluruh storage. Uji restore serta cocokkan tabel/relasi, nominal,
   hak akses, hash dan keterbacaan dokumen sebelum pengalihan.
5. GL/COA adalah sumber terpisah dan **tidak ikut dump Laravel**. Tentukan sumber
   GL sah di server serta akun SELECT-only. Verifikasi PK akun dan konfigurasi
   invoice terhadap GL tujuan; jangan menganggap ID lokal berlaku di server.
   Capture GL MyISAM perlu mekanisme konsistensi tersendiri;
   `--single-transaction` saja tidak cukup. Finance/WAPU dan identitas/rekening
   cetak yang belum disahkan tetap kosong dan operasi terkait tetap ditolak.

Container/backend yang berhasil boot belum membuktikan hasil pemindahan data.
Jika sumber dibuka lagi dan ada transaksi baru setelah snapshot, lakukan
capture final/rekonsiliasi sebelum pengalihan; jangan menjalankan dua aplikasi
penulis tanpa rencana sinkronisasi yang sah.

## Preflight server

Sebelum `build`/`up`, jalankan dari root repository di server. Script hanya
membaca, tidak mengubah apa pun, dan tidak mencetak secret:

```bash
bash scripts/preflight-production.sh            # env, direktori, Docker, port, listener MySQL
bash scripts/preflight-production.sh --db       # + uji koneksi DB/COA dari container (image sudah dibuild)
bash scripts/preflight-production.sh --offline  # hanya env dan direktori
```

FAIL (exit 1) harus dibereskan; WARN dibaca operator (mis. finance/cetak kosong,
dokumen historis belum dipulihkan). Script tidak memeriksa grant akun MySQL dari
subnet Docker dan aturan firewall; itu tetap cek manual, kecuali `--db` yang
membuktikan koneksi sebenarnya. Tes logika env/direktori:
`python tests/preflight_production.py`.

## Validasi dan jalankan backend

Nama project selalu eksplisit supaya `COMPOSE_PROJECT_NAME` milik runtime lokal
tidak membuat aplikasi ini menggantikan service `app` project lain.

```bash
docker compose -p labmania-production --env-file .env.production -f compose.production.yaml config --quiet
docker compose -p labmania-production --env-file .env.production -f compose.production.yaml build app
docker compose -p labmania-production --env-file .env.production -f compose.production.yaml up -d --no-deps app
docker compose -p labmania-production --env-file .env.production -f compose.production.yaml ps
docker compose -p labmania-production --env-file .env.production -f compose.production.yaml logs --tail=100 app
curl --fail http://127.0.0.1:8081/up
docker compose -p labmania-production --env-file .env.production -f compose.production.yaml exec --user www-data app php artisan db:show --database=mysql
```

Sesuaikan port pada `curl` jika `PRODUCTION_HTTP_PORT` diubah. Direktori mount
harus sudah ada; Compose menolak membuat direktori kosong otomatis. Startup
menolak database `db_labmania` dan akun `root`, lalu menyiapkan direktori runtime
dan cache Laravel. Guard nama ini bukan bukti skema atau penerimaan data.
Build tidak memerlukan Composer/npm/PHP pada host. Jangan membagikan hasil
`config` lengkap, `docker inspect` penuh atau log berisi data privat; validasi
Compose memakai `--quiet` agar environment tidak tercetak. `db:show` memeriksa
koneksi baca ke MySQL host sesudah listener/grant/firewall disiapkan; bukan
verifikasi kelengkapan snapshot atau izin tulis bisnis.

## Pembaruan berikutnya

Sesudah dukungan `/labmania/` selesai, cutover disahkan dan writer diamankan,
backup data/berkas terlebih dahulu. Build image baru sebelum menghentikan
writer. Tahan trafik pada proxy selama penggantian aplikasi dan verifikasi;
setiap proses penulis lain juga harus dihentikan sementara. Jalankan:

```bash
git pull --ff-only
docker compose -p labmania-production --env-file .env.production -f compose.production.yaml build app
docker compose -p labmania-production --env-file .env.production -f compose.production.yaml up -d --no-deps app
```

Periksa health, koneksi database dan fungsi yang berubah sebelum membuka
trafik. Compose tidak menunggu health menjadi sehat dan tidak melakukan
rollback otomatis. Pertahankan image versi sebelumnya dan backup konsisten
untuk pemulihan; jangan menimpa database terbaru dengan backup lama jika sudah
ada transaksi baru. Migration, jika suatu rilis membutuhkannya, harus mengikuti
runbook rilis dan guard data, bukan ditambahkan ke command startup ini.

Pemeriksaan kontrak konfigurasi tanpa menjalankan container tersedia melalui
`python tests/production_compose.py`; memerlukan Docker CLI + Compose, tidak
memerlukan daemon atau database.
