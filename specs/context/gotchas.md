# Gotchas — The Learning Buffer

Raw, uncompiled observations from real work. This file is the **input layer** of
the self-learning loop:

```
context/gotchas.md  →  distill pass  →  context/ (compiled truth)
```

It is the knowledge equivalent of `input/`: cheap to append to while working,
never authoritative, and periodically digested into the files that are. The
procedure that digests it is `context/distill.md`.

## Why this exists at all, given the repo once decided against it

An earlier version of this framework dropped the buffer, on the grounds that
every entry ended up duplicated into a conventions file anyway. That reasoning
holds only when someone files the lesson **at the moment they learn it**, into
the right file, in that file's voice. In practice the moment a lesson appears is
the moment attention is somewhere else — mid-task, with the real work
unfinished — and the choice on offer is not "file it properly or append it
here", it is "append it here or lose it". Losing it is what actually happened:
learnings defaulted into the issue log, which nothing reads at session start,
and the conventions files never received them.

So the buffer is back, with the constraint that makes it safe: **it is not read
as guidance.** It is intake, and the distill pass is what turns intake into
truth.

## Three rules

**1. This file is never deleted.** The distill pass **empties** it and leaves it
here with this header intact. Deleting it breaks the loop, and it breaks quietly
— the compile output stays behind and looks healthy while nothing new
accumulates. In the project this framework came from, the buffer was twice swept
away as migration debris, and both times nobody noticed for weeks. If you are
reorganising `context/` and this file looks like leftovers: it is not.

**2. It is not read at session start.** `context/RULES.md` deliberately omits
it. Entries here are unverified and unfiled; treating them as guidance defeats
the point of compiling them. Read it when you are appending to it, or when you
are running the distill pass.

**3. Nothing here is durable truth yet.** A rule that already has an owner in
`context/` belongs in that owner, not here. This file is for the observation
that does not yet have a home.

## What belongs here

Anything a future session would have wanted to know and could not have derived
from the artifacts themselves:

- a tool or library that does not behave the way its documentation implies
- a trap that cost real time, or that can destroy work
- a convention that emerged from a decision made in passing
- a piece of ownership that moved
- what the user said the business **means**, why a rule exists, or which way to
  lean — a `type: shape` entry, drafted by the harvest skill
  (`.claude/skills/harvest/`) and approved by the user before it lands here

Not: issue status, narrative history, or anything already stated in `context/`.
Those are `pm/tracker.yaml`, `pm/log.md`, and their existing owner respectively.

## Entry format

Append to the bottom. One `##` section per observation; delete the `_Empty. Append below._`
line when you add the first.

```markdown
## Short name for the fact

- **type:** convention | hazard | trap | ownership | decision | tooling | workflow | shape
- **area:** orders (shape entries only — the `context/shape/` file it feeds)
- **date:** YYYY-MM-DD
- **issue:** UC-07 (optional)

What is true, in one or two sentences, with a file path, a command, or a
rendered artifact where it helps.

**Why:** the mechanism — what causes this, so a reader can tell when it applies.
**How to apply:** what to do differently next time.
```

The **Why** line is what separates an entry that can be promoted from one that
cannot. "Gateway styles need `gwType=`" is a note; "the installed shape library
diverges from the spec's attribute names, so a plausible-looking style renders
blank" is a rule that generalises to the next attribute.

A `shape` entry replaces the **Why** / **How to apply** pair with the user's
own words, because the user is its only evidence:

```markdown
**Quote:** "the user's words, trimmed, typos left alone" (YYYY-MM-DD)
**Replaces:** the earlier claim it reverses, if any.
```

No quote and no pointer to code or a decision means it is an agent's inference,
and it does not go in. `context/shape/README.md` owns the rules.

`type` is a routing hint for the compile, not a taxonomy:

| type | roughly |
|---|---|
| `convention` | a project rule |
| `hazard` | a trap, especially one that can destroy work |
| `ownership` | which artifact or code owns which behaviour |
| `decision` | an architectural choice and its consequence |
| `tooling` | repository-level tooling, exports, build, `.gitignore` |
| `shape` | what the business means, why, which way to lean — **quote the user** and date it; the code cannot verify it |

**This is a hint about the kind of fact, not a destination.** Routing is decided
by `context/distill.md`, which owns the destination table — do not restate that
table here, or the two will drift.

## When to run a pass

Nothing surfaces staleness automatically, so noticing is a reader's job: a
glance at the `## Entries` headings gives the count, and the oldest `date:`
gives the age. Past roughly a dozen entries, or a month, it is worth a pass.

---

## Entries

## Workbook memakai kolom Sub Modul di luar konvensi

- **type:** convention
- **date:** 2026-10-23

Sejak draf 3, sheet *UC BPMN* dan *UC Non-BPMN* di `docs/workbook.xlsx` punya kolom `Sub Modul` setelah `Modul`. Sheet *Modules* dan *Entities* juga punya kolom `Sub Modul`, dan *Entities* punya kolom `Tabel ERD`. Semua UC ber-`Modul` = Master Data (`input/Master - Buat Program/2026-10-23-review-mockup-master-data/`).

**Why:** user meminta satu modul "Master Data" dengan sub modul. Tanpa kolom Sub Modul, pengelompokan ERD dan menu per sub modul hilang dari workbook. `workbook-conventions.md` belum menyebut kolom ini.
**How to apply:** saat distill, putuskan apakah `Sub Modul` menjadi kolom resmi di `workbook-conventions.md`. Script yang membaca workbook menurut posisi kolom (bukan nama header) perlu disesuaikan.

## draw.io CLI tidak terpasang di mesin ini

- **type:** tooling
- **date:** 2026-10-23

`C:\Program Files\draw.io\draw.io.exe` (yang disebut `drawio-general-guide.md`) tidak ada, dan `where draw.io.exe` tidak menemukan apa-apa. `python` di PATH hanya stub Microsoft Store, sedangkan `py` (Python 3.14) bisa dipakai. `openpyxl` dipasang dengan `py -m pip install --user openpyxl`.

**Why:** langkah 3 workflow wajib draw.io (export PNG lalu lihat hasilnya) tidak bisa dijalankan. ERD `docs/diagrams/erd.drawio` dibuat dari script generator dengan pengecekan bentrok jalur garis, tetapi belum dilihat hasil render-nya.
**How to apply:** pasang draw.io Desktop atau buka file secara manual, lalu render dan periksa ERD sebelum dianggap final.

## Mockup HTML bisa diuji dengan Chrome headless

- **type:** tooling
- **date:** 2026-10-23

`C:\Program Files\Google\Chrome\Application\chrome.exe` (dan Edge) tersedia. Mockup `ui-mockup/produk/` diuji dengan harness di scratchpad: salinan `index.html` yang memuat `app.js` lewat `file:///` plus `test.js` yang mengklik tombol `[data-act]`, lalu `--headless=new --allow-file-access-from-files --dump-dom` (hasil test ditulis ke `<pre>`), dan `--screenshot` + `--window-size=1440,1100` dengan hash URL untuk memilih layar. `py -I` tidak melihat paket `--user` (openpyxl) — pakai `py -E -P`. Heredoc berisi tanda kutip di tool Bash sering gagal ("unexpected EOF"); tulis file lewat tool Write.

**Why:** mockup bergantung pada perilaku JS (simulasi costing, transisi status) yang tidak terlihat dari membaca kode; draw.io CLI tidak ada, tetapi browser ada.
**How to apply:** setelah mengubah `app.js`, jalankan `node --check` lalu harness Chrome headless sebelum melapor selesai.

## Master Company diusulkan memakai tabel existing `customer`

- **type:** decision
- **date:** 2026-10-23
- **issue:** UC-11

ERD draf 4 menggambar Company sebagai tabel existing `customer` (+ kolom baru `jenis_instansi`), bukan tabel baru. Kolomnya sudah ada di `application/modules/customer/controllers/Customer.php` (nm_customer, alamat, provinsi, kota, telepon, email, npwp, pic_*, aktif). Belum dikonfirmasi — Q-20 di `docs/fr-nfr.md`.

**Why:** menghindari dua tabel customer; Sales Order setelah Deal B2B kemungkinan juga memakai `customer`.
**How to apply:** jika Q-20 dijawab "tabel baru", ganti `customer` di ERD kedua halaman, workbook Entities, dan catatan ERD.

## Render .drawio tanpa draw.io CLI: viewer-static di Chrome headless; lane wajib punya y eksplisit

- **type:** tooling
- **date:** 2026-10-27

File `.drawio` bisa dirender dengan HTML berisi `<div class="mxgraph" data-mxgraph='{"xml":..., "page":n}'>` + `https://viewer.diagrams.net/js/viewer-static.min.js`, lalu `chrome.exe --headless=new --virtual-time-budget=15000 --screenshot`. Viewer **tidak** menjalankan `childLayout=stackLayout` pada Pool, sehingga Lane tanpa `y` menumpuk di y=0 (lane ke-2 menimpa lane ke-1).

**Why:** render pertama Level 1 Master Program/Penjualan Program terlihat kacau padahal XML benar; halaman *Product Combine* lama juga tidak memberi `y` pada lane.
**How to apply:** beri setiap Lane `y` eksplisit (20, 20+tinggi lane 1, ...) walau `bpmn-conventions.md` bilang cukup `height`; verifikasi dengan viewer di atas.

## BPMN: pool = aktor yang menjalankan proses; tidak ada lane "Sistem"

- **type:** decision
- **date:** 2026-10-27

Arahan user 27-10-2026: setiap pool adalah milik user (aktor) yang menjalankan proses. Langkah yang dikerjakan aktor **memakai sistem** digambar sebagai **User task** (ikon orang); langkah **manual tanpa sistem** sebagai **Manual task** (ikon tangan). Pekerjaan otomatis sistem (hitung simulasi, validasi, penomoran, write_log) tidak digambar sebagai lane/Service task terpisah, melainkan ditulis di catatan halaman sebagai bagian dari User task-nya. `docs/diagrams/bpmn-to-be.drawio` (Level 1 Master Program, Level 1 Penjualan Program, Product Combine) sudah digambar ulang begitu.

**Why:** lane "Sistem (ERP)" dengan Service/Business Rule task dianggap salah oleh user; `bpmn-conventions.md` masih menawarkan Service task sebagai pilihan umum.
**How to apply:** untuk diagram proses di proyek ini, jangan buat pool/lane "Sistem"; pilih `taskMarker=user` atau `taskMarker=manual` saja. Lanskap Level 0 tetap memakai lane kategori proses (Manajerial/Utama/Pendukung). Saat distill, pertimbangkan menurunkan aturan ini ke `bpmn-conventions.md`.

## draw.io Desktop tidak terpasang; viewer CDN bisa ditolak izin

- **type:** gotcha
- **date:** 2026-11-13

Di mesin BA (13-11-2026) `C:\Program Files\draw.io\draw.io.exe` tidak ada (drawio-general-guide.md menyebut ada), dan menjalankan `viewer-static.min.js` dari viewer.diagrams.net lewat puppeteer ditolak oleh pemeriksa izin agen. Untuk `bpmn-to-be.drawio` dipakai cek geometris dari koordinat (bentuk/label bertumpuk, garis menembus bentuk atau label termasuk label milik bentuk asal, segmen miring, implicit merge) plus pratinjau SVG sederhana; skripnya ada di scratchpad sesi, bukan di repo.

**Why:** langkah 3 "export ke PNG dan lihat" di drawio-general-guide.md tidak bisa dijalankan; tanpa pengganti, tumpang tindih label hanya ketahuan setelah user membuka file.
**How to apply:** cek dulu `ls "/c/Program Files/draw.io"`; bila tidak ada, minta user memasang draw.io Desktop atau mengizinkan viewer, dan sampai itu ada, beri setiap edge port + waypoint eksplisit supaya jalurnya bisa diperiksa secara geometris. Laporkan ke user bahwa cek visual akhir di draw.io masih perlu.

## Laravel: akun tanpa secret 2FA tidak boleh enroll sendiri

- **type:** decision
- **date:** 2026-11-13

CI3 (`users/setup_2fa`) membiarkan user yang sudah login password mendaftarkan autentikator sendiri. Di Laravel hal itu membuat 2FA tidak melindungi akun yang belum enroll atau habis reset: penyerang yang tahu password mendaftarkan autentikatornya sendiri. Keputusan pengguna 13-11-2026: admin dengan hak Manage Master User membuat token enroll sekali pakai (15 menit); tanpa token, login berhenti di halaman "hubungi admin".

**Why:** hasil code review fase 1; plan di `.artifacts/perbaikan_fase1_review/`.
**How to apply:** jangan port `setup_2fa` CI3 apa adanya ke Laravel; reset 2FA mandiri juga harus lewat jalur terkontrol. Delegasi hak: aktor non-Superadmin hanya boleh menambah grant yang ia punya dan tidak boleh mengubah override/role miliknya sendiri.

## Laravel: tes SQLite saja tidak cukup untuk SQL yang menyentuh MySQL

- **type:** trap
- **date:** 2026-11-13

Saat suite MySQL pertama dijalankan pada DB `*_test`, dua kesalahan yang lolos di 100 tes SQLite langsung muncul: data uji melanggar `VARCHAR(20)` (SQLite tidak menegakkan panjang), dan `groups` adalah kata cadangan MySQL 8 sehingga SQL mentah tanpa backtick gagal (query builder membungkusnya otomatis). SQLite juga tidak membuktikan unique index pada generated column, kolasi, ENUM, dan lock `FOR UPDATE`.

**Why:** hasil fase 2; suite `phpunit.mysql.xml` dan `tests/Mysql/`.
**How to apply:** untuk SQL mentah (`DB::select`/`selectRaw`) selalu bungkus nama tabel/kolom yang mungkin kata cadangan dengan backtick, dan jalankan `phpunit -c phpunit.mysql.xml` sebelum menutup fitur yang menyentuh constraint atau SQL mentah. Suite itu hanya boleh jalan di database berakhiran `_test` (guard di `MysqlTestCase`); jangan pernah mengarahkannya ke `labmania_laravel` atau `db_labmania`.

## DB CI3 aktual tertinggal dari SQL migrasi di repo

- **type:** trap
- **date:** 2026-11-13

Repo memuat SQL `database/migrations/20261112_*.sql`, tetapi `db_labmania` di container belum menjalankannya: `ms_peserta.nik`, `ms_varian_pelaksanaan`, `paket_produk_lingkup`, `master_program_produk`, dan `master_program.jam_pembelajaran` tidak ada, sedangkan kode CI3 dan ERD memakainya. Keberadaan file SQL bukan bukti SQL itu pernah dijalankan.

**Why:** ditemukan saat menyalin DB dan menulis tes Peserta; konsisten dengan peringatan di rencana induk.
**How to apply:** sebelum menulis migrasi atau fixture, baca metadata aktual (`information_schema`) dari salinan, bukan dari file SQL. Skema to-be dibuat lewat migrasi Laravel yang berguard dan hanya jalan di `labmania_laravel`; jangan replay SQL CI3 (tidak idempotent).

## Salinan DB untuk Laravel: skrip PHP, akun terbatas, MySQL di port 3307

- **type:** decision
- **date:** 2026-11-13

Tidak ada `mysqldump` di mesin dev, dan MySQL CI3 berjalan di container `labmania_db` dengan port host **3307** (bukan 3306). Keputusan pengguna: salinan dibuat dengan skrip PDO (`.artifacts/fase2_master_data_program/copy_db_13-11-2026.php`) yang membaca sumber `READ ONLY` dengan snapshot konsisten dan menolak jalan bila target tidak kosong. Laravel memakai akun `labmania_laravel` yang hanya berhak atas `labmania_laravel` dan `labmania_laravel_test`; root tidak pernah masuk `.env` Laravel.

**Why:** isolasi data CI3 (aturan proyek) sekaligus memungkinkan tes MySQL nyata.
**How to apply:** `DB_PORT=3307` untuk container ini. Salinan berisi data pribadi: hanya di MySQL lokal, jangan masuk git atau artifact (`laravel/.env` sudah diabaikan git). Bila container dibuat ulang, jalankan ulang `provision_db_*.php` lalu `copy_db_*.php` dan `php artisan migrate`.


## Laravel 13 getTables tanpa schema mencakup database lain

- **type:** hazard
- **date:** 2026-11-14
- **issue:** 8

Pada akun yang bisa membaca salinan dan DB test, `getTables()` menghasilkan 124 entri untuk 62 nama tabel. Fase 7 membatasi inventaris dengan `getTables(getCurrentSchemaName())`; schema-only bootstrap dan tes MySQL memverifikasi 62 tabel tanpa duplikasi.

**Why:** argumen schema null menginventaris semua schema yang dapat diakses, bukan hanya database default.
**How to apply:** selalu nyatakan schema koneksi pada inventaris/export/DDL scoped; jangan melakukan DDL dari daftar tanpa batas schema.

## Docker: .dockerignore ".env*" hanya cocok di root context, rahasia di subfolder ikut ke image

- **type:** trap
- **date:** 2026-11-14

Pola `.env*` di `.dockerignore` hanya mengecualikan berkas di root context. `laravel/rehearsal/.env.rehearsal` (berisi APP_KEY dan password) ikut tersalin lewat `COPY . .` dan terbaca di dalam image. Ditemukan saat rehearsal pertama di container; tidak terlihat dari tes PHP atau Python mana pun.

**Why:** hasil fase 7 lanjutan; tes `test_dockerignore_excludes_nested_env_and_private_inputs` di `laravel/rehearsal/test_snapshot.py`.
**How to apply:** untuk berkas rahasia di subfolder pakai `**/.env*` dengan negasi `!**/.env*.example`, lalu buktikan dengan `docker build` image uji berisi `ls` folder tersebut. Jangan percaya `.gitignore` sebagai pengganti `.dockerignore`: keduanya terpisah.

## Git Bash di Windows mengubah argumen berawalan "/" menjadi path Windows saat memanggil docker

- **type:** tooling
- **date:** 2026-11-14

`docker run ... --manifest=/snapshot/x.json` dari Git Bash diterima container sebagai `--manifest=C:/Program Files/Git/snapshot/x.json`, sehingga alat melaporkan "manifest tidak ditemukan" padahal berkasnya ada. `docker cp ... /tmp/x` juga gagal diam-diam ke `C:	mp`. Gejalanya menyesatkan: pesan error dari aplikasi, bukan dari docker.

**Why:** konversi path MSYS; ditemukan saat menjalankan `labmania:migrasi-operasi`.
**How to apply:** set `MSYS_NO_PATHCONV=1` sebelum perintah docker yang memuat path container. Setelah `docker cp` atau pipe ke berkas, selalu pastikan berkas hasil tidak kosong sebelum membandingkan; perbandingan dua berkas kosong tampak "identik".

## Rehearsal: klien mariadb 11 menolak sertifikat self-signed MySQL, dan loader tanpa LOCK TABLES

- **type:** trap
- **date:** 2026-11-14

Klien `mariadb` di image PHP menolak MySQL 8 bersertifikat self-signed ("self-signed certificate in certificate chain"). Selain itu `snapshot_loader` sengaja tanpa grant `LOCK TABLES`, sedangkan `mysqldump` default menulis `LOCK TABLES`; restore gagal di tengah dan menyisakan tabel parsial di sumber.

**Why:** hasil fase 7 lanjutan; `MigrationOperations::clientTlsOptions()` dan tes `MigrationOperationsTest`.
**How to apply:** ekspor dump dengan `--skip-add-locks` (selain `--skip-lock-tables`); alat menolak dump ber-`LOCK TABLES` sebelum menulis. Jangan menambah grant demi lolos. Kegagalan restore menuntut project dan volume baru, bukan overwrite.

## Docker UAT: health internal tidak membuktikan port host bisa diakses

- **type:** tooling
- **date:** 2026-11-14

Pada Docker Desktop lokal, web yang hanya terhubung ke network `internal: true` healthy lewat `/up` dari dalam container, tetapi `docker compose ps` hanya menunjukkan `80/tcp` dan HTTP Windows ditolak. Menambahkan network bridge khusus web membuat `127.0.0.1:8080->80/tcp` muncul dan pemeriksaan HTTP Windows lulus. DB tetap hanya pada network internal. Penggantian binding CI3 juga sempat gagal; menghentikan web lama lalu memberi jeda 3 detik sebelum recreate berhasil pada runtime ini.

**Why:** pemeriksaan HTTP Windows pada Docker UAT 14-11-2026 gagal meski container healthy; network bridge tambahan membuat probe yang sama lulus.
**How to apply:** probe HTTP dari host setelah health container. Jangan menganggap health internal sebagai bukti akses localhost, atau menghapus DB/network lama untuk memperbaikinya.

## Laravel Schema getTableListing mengembalikan nama lengkap schema secara default

- **type:** tooling
- **date:** 2026-11-14

Bootstrap UAT MySQL awal menolak schema-only karena membandingkan `labmania_laravel.migrations` dengan `migrations`. Laravel 13 `getTableListing($schema = null, $schemaQualified = true)` memakai nama lengkap secara default. `getTableListing(schemaQualified: false)` membuat pengecualian ledger tepat; tabel bisnis tetap harus kosong. Bootstrap nyata kemudian lulus, tes LocalUatTest memuat ledger dummy agar pengecualian tetap diuji.

**Why:** penolakan nyata sebelum seed direproduksi terhadap schema-only; definisi API framework diperiksa dan bootstrap MySQL lulus setelah opsi nama schema dimatikan.
**How to apply:** pilih `schemaQualified` secara eksplisit jika nama hasil dipakai untuk pembandingan atau whitelist tabel.

## Backup rehearsal: mysqldump memuat nilai kolom generated dan restore ditolak MySQL 3105

- **type:** tooling
- **date:** 2026-11-14

Backup `labmania:migrasi-operasi backup` berhasil dibuat dan terenkripsi, tetapi restore ke target baru gagal dengan `ERROR 3105: The value specified for generated column 'kombinasi_aktif' in table 'paket_produk' is not allowed`. Dump biasa (juga dengan `--complete-insert`) menyertakan nilai kolom generated (`paket_produk.kombinasi_aktif`, `invoice.so_aktif`, `surat_kerja_trainer.program_berlaku`). Rehearsal data uji tidak menemukannya karena tabel itu kosong; data asli punya 5 baris `paket_produk`. Pesan alat hanya "Restore SQL gagal" (sengaja tanpa pesan SQL), jadi penyebab baru terlihat setelah dump direproduksi dan dimuat ke database scratch. Perbaikan: `MigrationOperations::generatedTables()` dan `generatedTableInserts()` mengecualikan data tabel itu dari dump (`--ignore-table-data`) lalu menulis ulang INSERT tanpa kolom generated; kolom biner pada tabel itu ditolak.

**Why:** gagal nyata pada rehearsal data asli 14-11-2026; sesudah perbaikan, restore ke target baru dan `CHECKSUM TABLE` 43 tabel identik dengan sumber, termasuk kolom generated yang dihitung ulang.
**How to apply:** setiap perubahan skema yang menambah kolom generated harus lolos uji restore pada tabel berisi, bukan hanya tabel kosong. Saat restore gagal dengan pesan generik, reproduksi dump ke database scratch di container restore (bukan target) untuk melihat pesan SQL sebenarnya.
