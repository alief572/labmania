# Workflow Log

Narrative record of what happened and why. **Newest entry at the top.**
Append-only: never edit or delete a past entry — a log that gets corrected in
place is a log nobody can trust as evidence. A superseded entry is corrected by
a new entry that says so.

## What goes here, and what does not

This file records **events**. `context/` records **truth**. The test: if the
sentence would still be worth reading when the issue is long closed, it belongs
in `context/`, not here.

| | Goes to |
|---|---|
| "FIX-12 closed, verified against the failing case" | here |
| "The gateway style attribute is `gwType=`, not `symbol=`" | `context/` — via `context/gotchas.md` |
| "We chose X over Y, and here is what it costs" | `context/index/decisions/` + a row in `decisions.md` |
| "What is open, what is next" | `pm/tracker.yaml` |

A durable fact that lands only here is lost: nothing reads this file at session
start, by design.

## Entry format

One `-` bullet per event, newest first:

```markdown
- **YYYY-MM-DD** `[TAG][ID]` **Headline in bold.** What changed, what it was
  verified against, and anything a future reader would otherwise have to
  reconstruct. Inline `[TAG]`s are fine mid-entry when one event carried
  several kinds of outcome.
```

`ID` is the issue id, or omitted for work that never became an issue.

| Tag | For |
|---|---|
| `[STATUS]` | An issue opened, moved, closed, or was dropped |
| `[DECISION]` | A choice was made — record the alternative that was rejected |
| `[DISCOVERY]` | Something turned out not to be what it looked like |
| `[TODO]` | Something was deliberately deferred, and what would settle it |

---

- **2026-10-30** `[DECISION]` **Activity Log: tanpa login/logout, simpan 60 hari, akses Manajemen & Admin Developer.**
  `input/Master - Buat Program/2026-10-30-activity-log-akses-retensi/`. Spec activity log draf 2 (LOG-FR-09, LOG-NFR-06 baru);
  group akses baru **Admin Developer** (`fr-nfr.md` draf 11q, FR-36). Kolom Dibuat/Diubah tetap terlihat semua pengguna (usulan).
  Q-LOG-01 belum diputuskan — user bertanya efek "buat baru" vs "ambil dari proyek lama"; dijelaskan di sesi: kode Labmania punya
  modul PO yang sama dengan proyek acuan `AGENTS.md`, tetapi tanpa `log_helper.php`/`write_log()`. Mockup draf 12a (menu & gear
  disembunyikan untuk group lain, purge > 60 hari), diuji jsdom; workbook, catatan ERD & Flow, salinan final diperbarui.
- **2026-10-30** `[DECISION]` **Jejak data (Dibuat/Diubah oleh + tanggal & jam) di semua modul dan modul Activity Log.**
  `input/Master - Buat Program/2026-10-30-histori-activity-log/`. Spec baru `docs/fr-nfr-activity-log.md` draf 1 (LOG-FR-01 –
  -08, LOG-NFR-01 – -05, UC-38, UC-39, Q-LOG-01 – -05); NFR audit tiap modul merujuknya (Master Data 11p, MP 11b, PJ 12b,
  QTN 9c). Kolom ikut konvensi aplikasi (`created_by`/`created_date`/`updated_*`/`deleted_*`, terbanyak dipakai di
  `application/`); tabel `logs` mengikuti standar `write_log()` AGENTS.md — helper/tabel itu **belum ada** di kode Labmania
  (Q-LOG-01). ERD: halaman *ERD Activity Log* (tabel `logs` + kotak pola kolom jejak; tidak digambar per tabel agar ERD lain
  tetap terbaca — ditolak: menambah 6 baris ke setiap tabel). Workbook: modul, UC-38, UC-39, entity. Flow: kotak Activity Log
  di Level 0, halaman Level 1 — Activity Log, catatan halaman lain. Mockup draf 12: jejak dideteksi otomatis dari perubahan
  data setiap aksi (bukan hook per form), kolom "Dibuat / Diubah" di semua daftar, gear "Lihat Riwayat", menu Activity Log
  dengan filter & detail sebelum/sesudah, pemilih "Login sebagai"; diuji jsdom. Salinan final dibuat ulang (FR & NFR 5 bagian).
  `[TODO]` Render ERD & Flow belum dicek visual.
- **2026-10-30** `[DECISION]` **Flow process (BPMN) diperbarui: Sales Order dihapus, halaman Level 1 — Quotation baru.**
  Menutup `[TODO]` entri salinan final. Level 0: kotak Quotation aktif (biru, tertaut), Sales Order dihapus. Level 1 Penjualan
  Program: gateway "Jenis?" dan end "Ke Sales Order" dihapus — Final langsung "Ke Quotation (Public & B2B)". Halaman baru
  Level 1 — Quotation (pool Manajemen, User task; cabang Public/B2B hanya untuk Formulir Konfirmasi dan Confirmation Letter;
  aturan B2B lain ditulis di label & catatan agar diagram tidak bercabang berlebihan). Dibuat lewat generator; XML valid,
  tanpa komentar, tanpa edge menggantung, tanpa elemen bertumpuk. Backup `input/backup/bpmn-to-be_sebelum-quotation_30-10-2026.drawio`;
  salinan final diperbarui. `[TODO]` Render belum dicek visual — draw.io CLI tidak terpasang.
- **2026-10-30** `[DECISION]` **Salinan final "Master - Quotation" di `specs/final/`.**
  Diminta user: FR & NFR, ERD, Use Case, Mock Up final bernama "Master - Quotation". Dipilih salinan (bukan rename) agar
  rujukan ke `docs/…` dan `ui-mockup/produk/` tidak rusak; empat FR & NFR digabung jadi satu file (Bagian 1–4: Master Data
  draf 11o, Master Program draf 11a, Penjualan Program draf 12a, Quotation draf 9b). File kerja tetap sumber; salinan dibuat
  ulang bila sumber berubah. Flow process (`bpmn-to-be.drawio`) ikut disalin sebagai `Flow Process Master - Quotation.drawio`.
  `[TODO]` Salinan tidak ikut terbarui otomatis. `[TODO]` Flow process belum diperbarui sejak 29-10: masih Sales Order dan
  Quotation "ditunda", belum ada halaman Level 1 Quotation.
- **2026-10-30** `[DECISION]` **Semua isian uang memakai pemisah ribuan titik saat diketik.**
  `input/Master - Buat Program/2026-10-30-format-rupiah-titik/`. NFR-12 (`fr-nfr.md` draf 11o) diperluas dari Default Cost
  ke semua isian uang; rujukan di MP-NFR-04, PJ-NFR-05, QTN-NFR-07 baru. Mockup draf 11z: input `money-in` (teks, format
  otomatis, kursor dijaga), diuji jsdom. Usulan: nilai disimpan tanpa titik.
- **2026-10-30** `[DECISION]` **Master Program: Fixed Cost langsung muncul semua, hapus dengan ikon ×.**
  `input/Master - Buat Program/2026-10-30-fixed-cost-otomatis/` — menggantikan `[BTS-1]` (28-10) untuk Fixed Cost: memilih
  satu per satu terasa lambat. Ditolak: kembali menampilkan semua item tanpa bisa dihapus (alasan `[BTS-1]`: halaman penuh) —
  kini item tidak dipakai dihapus ×. Variable Cost tetap pilih satu per satu. MP draf 11 (MP-FR-06, -07, UC-19 + workbook);
  mockup draf 11y diuji jsdom.
- **2026-10-30** `[DECISION]` **All-in hanya B2B; `tr_penawaran` tidak dipakai — tabel baru Quotation dikonfirmasi.**
  `input/Master - Buat Program/2026-10-30-allin-b2b-tabel-baru/`. Q-QTN-04 terjawab (ERD Quotation draf 1a, catatan
  diperbarui); Q-QTN-15 tinggal soal cetak satu baris paket. Spec quotation draf 9a; tooltip mockup diperbarui.
- **2026-10-30** `[DECISION]` **All-in B2B boleh diskon; B2B tanpa Confirmation Letter; min 10 juga saat Deal. ERD Quotation & UC Quotation dibuat.**
  `input/Master - Buat Program/2026-10-30-b2b-allin-diskon-konfirmasi/`. Usulan draf 8 "all-in tanpa diskon" ditolak klien;
  diskon all-in dibaca Rupiah **total** (usulan). Pecah invoice B2B = (all-in − diskon) ÷ peserta, disetujui sementara.
  ERD halaman baru *ERD Quotation* draf 1 (`quotation`, `quotation_peserta`, `quotation_keterangan`) + `ms_peserta.id_customer`
  di *ERD Master Data* draf 13 — dibuat lewat generator, XML valid. Workbook: modul Quotation, UC-33 – UC-37, 6 entity.
  Spec quotation draf 9; mockup draf 11x diuji jsdom. `[TODO]` Render ERD belum dicek visual — draw.io CLI tidak terpasang;
  Q-QTN-04 (tabel baru vs `tr_penawaran`) perlu konfirmasi tim dev.
- **2026-10-30** `[DECISION]` **B2B di Quotation: satu quotation, minimal 10 peserta, harga per peserta / all-in, tanpa Formulir Konfirmasi; Sales Order dihapus.**
  `input/Master - Buat Program/2026-10-30-b2b-quotation-allin-min10/`. Berawal dari pertanyaan user "B2B dan Public jadinya
  sama aja di quotation" — ciri B2B lama (harga paket, min 10) tertinggal di Sales Order yang ditunda. Klien: Sales Order
  tidak ada (Q-QTN-03 terjawab, Q-PJ-16 gugur); B2B satu quotation (Q-QTN-08); tanpa Formulir Konfirmasi (Q-QTN-13); min 10
  dari Penjualan Program sampai Quotation (PJ-FR-25, QTN-FR-21); skema all-in di Quotation (QTN-FR-20). Ditolak: all-in
  kembali di Penjualan Program — klien menyebut "di quotation". Usulan: all-in tanpa diskon & satu baris cetak, B2B tanpa
  Confirmation Letter, min 10 juga saat Deal. Spec quotation draf 8, PJ draf 12; mockup draf 11w diuji jsdom (layout langkah
  1 baru, gear di daftar Quotation, alur B2B). `[TODO]` Q-QTN-15 – Q-QTN-18; invoice B2B dipecah per peserta (modul Invoice).
- **2026-10-29** `[DECISION]` **Quotation tanpa pilihan cara pembayaran.**
  `input/Master - Buat Program/2026-10-29-tanpa-pilihan-pembayaran/`. Field Pembayaran (Cash In Advance/Termin/Tempo)
  dihapus dari form; baris Pembayaran di PDF Penawaran ikut tidak dicetak (usulan, Q-QTN-14 — template asli memuatnya).
  Spec Quotation draf 7; mockup draf 11v, regresi jsdom 151 cek lolos (skrip test-pdf disesuaikan). Pertanyaan user
  soal masa berlaku dijawab dengan pilihan (pertahankan / cetak / jadikan aturan / hapus) — belum diputuskan.

- **2026-10-29** `[DECISION]` **Tiga template Formulir Konfirmasi lewat master; form quotation jadi 4 langkah.**
  `input/Master - Buat Program/2026-10-29-template-formulir-konfirmasi/` (Workshop, Training Sertifikasi, Resertifikasi —
  .doc dibaca via konversi Word read-only). Beda utama: Resertifikasi punya isian Nomor Hp, LSP, Tanggal Habis Sertifikat
  dan pernyataan "akan mengikuti … (H-3 Bulan)" tanpa tanggal; label kegiatan di subject email beda per template.
  "Sama halnya" dibaca = pola Keterangan: master Template Formulir Konfirmasi (FR-51 – FR-53), default dari Jenis Produk
  paket (Pelatihan → Workshop, Sertifikasi → Training & Sertifikasi; Resertifikasi manual — Q-31), per quotation di
  langkah baru "Formulir Konfirmasi" (ganti template, centang isian, + isian lain, ubah pernyataan dengan pratinjau,
  kembalikan ke default), tampil di Preview. Spec Master Data draf 11n, Quotation draf 6 (QTN-FR-19 baru), ERD draf 12
  `ms_template_konfirmasi` (XML valid). Mockup draf 11u: jsdom 20 cek baru + regresi (151 cek); screenshot langkah 3 dan
  halaman formulir Resertifikasi dirender Chrome headless. `[TODO]` Render visual ERD masih belum (draw.io CLI tidak ada).

- **2026-10-29** `[DECISION]` **Form quotation 3 langkah + Preview; Keterangan jadi daftar centang.**
  `input/Master - Buat Program/2026-10-29-keterangan-ux-preview/`. User menilai blok Keterangan (5 kotak textarea) tidak
  rapi dan minta preview sebelum cetak. Usulan analis disetujui: Data Quotation → Keterangan → Preview (PDF lengkap
  sebelum disimpan); Keterangan = centang per kategori yang dapat dilipat, + Tambah teks lain, ✎ ubah teks, ringkasan
  "x dari y dipilih"; Klausul tanda tangan dari master saja (pertanyaan tidak dijawab → usulan). Kirim dan Cetak diberi
  label "Preview — belum dikirim" + tombol Tutup. Spec Quotation draf 5 (QTN-FR-17 ditulis ulang, QTN-FR-18 baru).
  Mockup draf 11t: jsdom 23 cek baru; skrip uji lama disesuaikan dengan alur 3 langkah (total 129 cek lolos);
  screenshot Chrome headless langkah 2 & 3 dicek (snapshot pertama tertangkap saat animasi modal — diulang tanpa animasi).

- **2026-10-29** `[DECISION]` **Keterangan quotation: master Template Keterangan + salin-lalu-edit per quotation.**
  `input/Master - Buat Program/2026-10-29-keterangan-quotation-default/`. User bertanya "master lalu select?"; analis
  mengusulkan master per kategori dengan butir Default yang otomatis tersalin (bukan dipilih satu per satu), user
  setuju ("coba buatkan"). Kategori: Syarat keikutsertaan, Termasuk, Tidak termasuk, Dokumen persyaratan, Klausul tanda
  tangan; kolom Berlaku untuk (Semua/Public/B2B); isi awal dari template + butir "contoh" — ketiganya usulan (Q-28 –
  Q-30). Form quotation: edit/hapus/tambah dari master/tulis bebas/kembalikan ke default; salinan tersimpan di
  quotation. Field Catatan (draf 3) dihapus. `docs/fr-nfr.md` draf 11m (FR-48 – FR-50, UC-39), Quotation draf 4
  (QTN-FR-17), ERD draf 11 `ms_template_keterangan` (XML valid). Mockup draf 11s diuji jsdom (19 cek + regresi
  PDF/Quotation/Master/Trainer) dan halaman Penawaran dirender Chrome headless ke PDF.

- **2026-10-29** `[DECISION]` **Cetak Quotation = satu PDF: Penawaran + Syarat & Ketentuan + Formulir Konfirmasi.**
  `input/Master - Buat Program/2026-10-29-cetak-quotation-snk-konfirmasi/` (template `input/Template Penawaran Labmania.pdf`,
  `input/SYARAT & KETENTUAN KEBIJAKAN LAYANAN PEMBELAJARAN.pdf`, `input/Tamplate Formulir Konfirmasi Workshop.doc` —
  .doc dibaca lewat konversi Word read-only ke scratchpad). Nomor quotation kini `LM-I/AM/PW/MM/YY-XXX`; field baru
  Pembayaran, Catatan (maks. 3), Biaya Lain-lain; Formulir Konfirmasi satu halaman per peserta, terisi dari Master
  Peserta (NIK, Jabatan, Masa Pengalaman kosong); Confirmation Letter memakai format formulir ini untuk peserta Deal.
  Field **Waktu** di Penjualan Program (usulan) karena template butuh jam. Spec Quotation draf 3 (QTN-FR-14 – 16,
  QTN-NFR-06, Q-QTN-11 – 13), PJ draf 11a (PJ-FR-24, Q-PJ-21), ERD `penjualan_program.waktu` (XML valid). Mockup draf
  11r diuji jsdom (15 cek) dan **dirender Chrome headless ke PDF**: 5 halaman A4 sesuai template (sempat ditemukan
  kolom menumpuk saat print karena media query layar sempit — diperbaiki dengan `@media screen`). `[TODO]` Cek lama
  "Confirmation Letter: surat kehadiran + denda" di skrip uji sudah tidak berlaku (format diganti).

- **2026-10-29** `[DECISION]` **Master Trainer baru (format CV Excel); Trainer dipilih di Penjualan Program.**
  `input/Master - Buat Program/2026-10-29-master-trainer/` (sumber `input/Master Trainer Ivan Sarifudin.xlsx`, 5 sheet).
  Master Data › Trainer: daftar, form bertab (Identitas + Pelatihan & Seminar, Pengalaman Kerja, Pengalaman Mengajar,
  Lampiran Dokumen), Lihat CV, Aktifkan/Nonaktifkan tanpa hapus. Penjualan Program memilih satu trainer aktif
  (1 event = 1 trainer, `[K3M-1]`), opsional (usulan). Usulan lain: tempat/tanggal lahir dipecah, keahlian satu per
  baris, file lampiran opsional. `docs/fr-nfr.md` draf 11l (FR-44 – FR-47, UC-38, Q-26, Q-27); PJ draf 11 (PJ-FR-23,
  Q-PJ-20); ERD draf 10: halaman baru "ERD Master Trainer" (5 tabel) + `penjualan_program.id_trainer` — XML valid,
  id unik, referensi utuh. Mockup draf 11q diuji jsdom (18 cek + regresi). Data contoh memakai nama & riwayat dari
  Excel, alamat/telepon/tanggal lahir diganti contoh. `[TODO]` Render ERD belum dicek visual; workbook belum memuat UC-38.

- **2026-10-29** `[DECISION]` **Detail batch Quotation tanpa kartu; tombol Create Quotation per batch.**
  `input/Master - Buat Program/2026-10-29-detail-batch-tanpa-kartu/`. Kartu alur status dan kartu ringkasan/bar progres
  di detail batch dihapus; info program menjadi teks di bawah judul; filter status lewat dropdown (usulan). Tombol
  **+ Create Quotation** membuka form dengan batch terisi; B2B dengan quotation aktif → tombol nonaktif. Index tidak
  berubah. `docs/fr-nfr-quotation.md` draf 2a (QTN-FR-02); mockup draf 11p diuji jsdom (38 cek).

- **2026-10-29** `[DECISION]` **Master Varian Pelaksanaan dihapus; NPWP Instansi di Master Peserta & Company.**
  `input/Master - Buat Program/2026-10-29-npwp-instansi-hapus-varian/`. Paket Produk kini tiga atribut (Produk, Bidang,
  Sertifikat); keunikan atas tiga atribut. Tempat yang dulu menampilkan varian paket kini menampilkan Jenis Sertifikat.
  Company: label NPWP → NPWP Instansi (opsional, kolom existing `customer.npwp`). Peserta: field baru NPWP Instansi
  opsional; bila terdaftar di Company ikut NPWP company dan terkunci (usulan) — juga di isian Peserta baru Quotation.
  `docs/fr-nfr.md` draf 11k; ERD draf 9 (hapus `ms_varian_pelaksanaan`, `paket_produk.id_varian_pelaksanaan` dan
  relasinya; tambah `ms_peserta.npwp_instansi`) — XML valid, referensi utuh; catatan BPMN Product Combine diperbarui.
  Mockup draf 11o diuji jsdom (28 cek + regresi Quotation). `[TODO]` Render ERD/BPMN belum dicek visual (draw.io CLI
  tidak terpasang); UC-04 di `docs/workbook.xlsx` belum ditandai dihapus.

- **2026-10-29** `[DECISION]` **Paket Produk dapat diedit; "Jenis Sertifikat"; dropdown Product Combine dapat dicari.**
  `input/Master - Buat Program/2026-10-29-edit-master-sertifikat-cari/`. "Hanya ada Hapus" merujuk `input/labmania.html`
  (tidak diubah — sumber `input/`); master atribut di mockup kita sudah Edit + Nonaktifkan, yang baru Edit Paket Produk
  (FR-43, Q-14 terjawab; field & efek ke Master Program usulan). Label master menjadi Jenis Sertifikat / kolom
  Sertifikat; nilai Jenis Produk "Sertifikasi" dan tabel ERD `ms_jenis_sertifikasi` tetap. Dropdown dengan kolom cari
  (FR-12, NFR-10) di Tambah & Edit Product. `docs/fr-nfr.md` draf 11j, mockup draf 11n diuji jsdom (18 cek + regresi
  Quotation 33 cek). `[TODO]` Nama UC-02 di `docs/workbook.xlsx` masih "Jenis Sertifikasi"; dropdown cari belum
  diterapkan di form lain (Peserta, Company, Rate) yang juga dituntut NFR-10; render belum dicek visual.

- **2026-10-29** `[DECISION]` **Quotation draf 2: Personal banyak peserta, diskon Rupiah, index per program, 4 aksi.**
  `input/Master - Buat Program/2026-10-29-quotation-diskon-aksi/`. Personal boleh banyak peserta; peserta baru diisi di
  form quotation dan langsung tersimpan di Master Peserta; company peserta ikut tampil. Diskon Rupiah per peserta
  (usulan, Q-QTN-06). Index dikelompokkan per Penjualan Program mengikuti `input/labmania.html`. Aksi Kirim dan
  Cetak / Deal / Batalkan / Confirmation Letter; status Draft → Terkirim → Deal / Batal (Selesai & Hapus dihapus).
  Deal membuka centang peserta (default semua) agar Deal/Batal per peserta `[JPP-15]` tetap ada — usulan, Q-QTN-07.
  B2B satu quotation aktif (usulan, Q-QTN-08). Spec `docs/fr-nfr-quotation.md` draf 2, `docs/fr-nfr.md` draf 11i;
  mockup draf 11m diuji jsdom (33 cek). Backup draf lama di `input/backup/*_29-10-2026*`. `[TODO]` Format Confirmation
  Letter menyusul (Q-QTN-05); ERD quotation belum (Q-QTN-04); render mockup belum dicek visual.

- **2026-10-28** `[DECISION]` **Public & B2B Final masuk ke modul Quotation (ke Company atau Personal).**
  `input/Master - Buat Program/2026-10-28-quotation-company-personal/` (acuan `input/labmania.html`). Quotation ke Company memilih
  peserta yang terdaftar di company itu, jadi Master Peserta diberi field Company opsional (usulan). Personal = satu
  peserta (usulan). Harga dari Penjualan Program, tanpa diskon, PPN 11% opsional. Status Draft → Terkirim → Selesai,
  Deal/Batal per peserta (`[JPP-15]`). Spec baru `docs/fr-nfr-quotation.md` draf 1; PJ draf 10, Master Data draf 11h;
  mockup draf 11l diuji jsdom + screenshot. `notes.md` intake menulis "spec belum ditulis" — sudah ditulis di entri ini.
  `[TODO]` Sales Order dipertahankan atau tidak (Q-QTN-03); ERD tabel quotation + `ms_peserta.id_customer` (Q-QTN-04).
- **2026-10-28** `[DECISION]` **Master User ditambahkan ke Master Data (sub modul Pengguna).**
  `input/Master - Buat Program/2026-10-28-master-user/` (gambar `input/Master User.png`). Form mengikuti gambar; ditambah
  bagian Akun Login (username, password, group akses), daftar, filter, Aktifkan/Nonaktifkan tanpa hapus,
  Reset Password — usulan. Diusulkan memakai tabel existing `employees` + `users`/`groups` (Q-25).
  FR-36 – FR-39, UC-32 di `docs/fr-nfr.md` draf 11; mockup draf 11d diuji jsdom. `[TODO]` ERD belum
  memuat `employees`/`users` — tunggu Q-25 (d) dan skema kolom tabel existing (tidak ada dump di repo).
- **2026-10-28** `[DECISION]` **15 pertanyaan Master/Penjualan Program dijawab; usulan sementara dipasang.**
  `input/Master - Buat Program/2026-10-28-jawaban-pertanyaan-program/`. Nomor sementara `MP-YYMM-NNN`, `PJP-`/`PJB-YYMM-NNN`
  (urut ulang per bulan); durasi Penjualan Program = selesai − mulai + 1 (tanggal wajib); hanya Draft
  yang bisa dibatalkan (MP & PJ); MP Final tidak disalin; Company aktif saja, Master Company kini punya
  Aktif/Nonaktif; SO min 10 peserta wajib, status SO sementara Draft → Final → Invoice. Spec: MP draf 10,
  PJ draf 9, Master Data draf 10; mockup draf 11c diuji jsdom. `[TODO]` Kolom aktor UC di
  `docs/workbook.xlsx` belum diganti ke Manajemen.
- **2026-10-28** `[DECISION]` **Input simulasi tampil ganda; Company B2B dipilih di Penjualan Program.**
  `input/Master - Buat Program/2026-10-28-input-simulasi-company-b2b/`. Input simulasi (durasi, target peserta, harga,
  target profit) kini di bagian 1 **dan** bagian 4, terikat ke nilai yang sama — opsi "pindah ke
  bagian 4" ditolak karena durasi & target peserta juga dipakai Qty di bagian 2–3. Berlaku juga di
  Penjualan Program (form sama, `[JLR-1]`). Company B2B (Master Company / `customer`) wajib di
  Penjualan Program B2B → PJ-FR-22, Q-PJ-18 terjawab, ERD `penjualan_program.id_customer` kembali
  (backup `input/backup/erd_sebelum-id-customer_28-10-2026.drawio`). Mockup diuji dengan jsdom.
  `[TODO]` Render ERD belum dicek visual — draw.io CLI tidak terpasang di mesin ini.
