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

- **2026-11-11** `[DECISION]` **Peserta Deal disimpan ke Master Peserta saat Deal.** `input/Master - Buat Program/2026-11-11-peserta-deal-ke-master-peserta/` (user: "saat deal aja deh"; "a. setuju gitu, sisanya dari admin yang mengisi / b. diisiin sama internal labmania / c. iya otomatis aja"). Link konfirmasi tidak berubah. Saat Deal, peserta Deal dicocokkan ke Master Peserta (NIK, lalu email, lalu No. WA; cocok = hanya mengisi field kosong, tidak cocok = peserta baru, Kode otomatis). Bidang terisi dari Product program (dikosongkan bila Bidang Product berbeda, usulan), Provinsi/Kota dari Master Company bila quotation ke Company; Gender dan field lain dilengkapi admin, peserta bertanda "Belum lengkap" (filter Kelengkapan, keterangan "Dari Deal [No. Quotation]"). Peserta yang terhubung lewat Deal tidak dapat dihapus. Master Data to be draf 1e (FR-15, FR-16 + NIK opsional, UC-07); Quotation draf 1q (QTN-FR-33 baru, QTN-FR-11, Q-TB-04 terjawab, Q-QTN-23 baru); README baris MP; ERD: `ms_peserta.nik`, kolom wajib form boleh NULL di tabel, `quotation_peserta.id_peserta` + tabel acuan `ms_peserta` dan relasi di halaman ERD Quotation (XML valid, tanpa tumpang tindih di halaman Quotation; render belum dilihat, draw.io CLI tidak terpasang). Mockup (antislop during): `mpSyncDeal` di mod-quotation.js, daftar/form Master Peserta di mod-master-data.js, cek hapus di main.js; uji jsdom 22/22 lulus, uji lama (reschedule, link B2B, unduh, hadir) tetap lulus. Backup `input/backup/*_sebelum-peserta-deal-ke-master_11-11-2026.*`. Shared file (main.js, ERD, fr-nfr.md) dipakai paket Master - Quotation; paket Invoice - Pengajuan tidak diubah.
- **2026-11-11** `[TODO]` **Manage Peserta (paket Invoice - Pengajuan) masih menulis "perbaikan data lewat Master Peserta" dan membaca `ms_peserta` (MP-FR-05, MP-NFR-02).** Dengan QTN-FR-33, dokumen hilir tetap memakai salinan isian link (usulan, Q-QTN-23 d). Selaraskan saat paket itu dikerjakan.
- **2026-11-11** `[STATUS]` **Ringkasan modul dan flow user (Excel) untuk review.** User: FR/NFR terlalu panjang untuk review ulang. `pm/ringkasan-modul-dan-flow-user_11-11-2026.xlsx`: Alur Besar (11 tahap), Modul (21 menu: fungsi, pemakai, langkah user, aturan, ref. spec), Public vs B2B (8 tahap), Status (9 dokumen), kolom kuning Catatan review. Sumber spec to be + spec as is Invoice, Pengajuan Biaya, Activity Log; hal yang masih usulan/ditanyakan ditandai [MENUNGGU]. Catatan: invoice B2B ditulis "1 invoice ke company" (to be), sedangkan `docs/fr-nfr-invoice.md` belum diperbarui.
- **2026-11-11** `[STATUS]` **Versi ringkas daftar pertanyaan: 14 poin utama.** User: "bisa buat gausah banyak-banyak ga? yang penting poin-poin utamanya aja". `pm/pertanyaan-utama-labmania_11-11-2026.xlsx`: reschedule (3), revisi peserta (5), B2B (4), invoice (2); hanya pertanyaan yang menghalangi alur atau menyangkut uang, beberapa digabung. File lengkap 59 pertanyaan tetap sebagai cadangan.
- **2026-11-11** `[STATUS]` **Daftar pertanyaan ke Labmania (Excel): reschedule, quotation, SO, surat konfirmasi, invoice.** Permintaan user: semua pertanyaan yang masih rancu dan janggal, Public dan B2B, untuk ditanyakan sekaligus. `pm/pertanyaan-labmania_reschedule-quotation-so-invoice_11-11-2026.xlsx`: 59 pertanyaan (sheet Pertanyaan dengan kolom jawaban, Ringkasan berumus COUNTIF/COUNTIFS, Petunjuk), diambil dari pertanyaan terbuka `docs-to-be/fr-nfr-penjualan-program.md`, `fr-nfr-quotation.md`, `fr-nfr-sales-order.md`, `docs/fr-nfr-invoice.md`, ditambah temuan baru dari diskusi (revisi quotation karena peserta aktual, perubahan peserta setelah Deal, B2B kurang dari 10 peserta, daftar PIC melebihi kuota, koreksi nama peserta, revisi Penawaran saat reschedule). Jawaban Labmania 11-11 soal kuota vs aktual dicatat di `input/Master - Buat Program/2026-11-11-jawaban-kuota-vs-aktual/` (Q-TB-01 terjawab), belum diterapkan ke spec. Rumus tidak dihitung ulang dengan LibreOffice (tidak terpasang); dicek lewat Python dan diset hitung ulang saat dibuka.
- **2026-11-11** `[FIX]` **Mockup: Unduh surat konfirmasi tidak mengunduh apa pun pada data contoh.** User: "di link surat konfirmasi B2B kan ada unduh dokumen, tapi di aku gabisa". Sebab: surat data contoh (B2B `konfCo` dan peserta Public) tidak punya berkas asli, tetapi tombol Unduh tetap menampilkan toast "diunduh". Kini: berkas asli diunduh langsung; data contoh membuka surat terisi dengan Cetak / Simpan PDF; berkas yang terlalu besar untuk localStorage memberi peringatan, bukan klaim "diunduh". Dicek pula: tombol Cetak surat konfirmasi di halaman link B2B berjalan (10 nama tercetak; 2 nama ditolak "Minimal 10 peserta", sesuai QTN-FR-31). Tanpa perubahan spec. `mod-quotation.js`; diuji jsdom (B2B dan Public, tanpa error konsol). Backup `input/backup/mod-quotation-to-be_sebelum-fix-unduh-surat_11-11-2026.js`.
- **2026-11-11** `[DECISION]` **Daftar Hadir tanpa kalimat "Keterangan".** `input/Master - Buat Program/2026-11-11-daftar-hadir-tanpa-keterangan/` (user: "didalamnya ada \"Keterangan:...\" itu gausah ada ya"). Kalimat catatan di bawah tabel format `FR/LMI-TR-05/17` dihapus; kolom tabel Ket. tetap (penafsiran: yang diminta kalimat bertitik dua, bukan kolom). Manage Peserta draf 1g (MP-FR-07); mockup `mod-manage-peserta.js`, aturan `.dh-ket` dibuang dari `style.css` (tidak terpakai lagi). Backup `input/backup/*_sebelum-hadir-tanpa-keterangan_11-11-2026.*`.
- **2026-11-11** `[DECISION]` **Reschedule boleh setelah Deal; Sales Order direvisi, tidak dihapus.** `input/Master - Buat Program/2026-11-11-reschedule-setelah-deal/`. Labmania: "biasanya di hapus PO nya dulu kemudian revisi tanggal setelah itu di PO ulang" (PO = SO). BA: hapus-ulang hanya karena proses masih manual; di sistem "otomatis ajaa berubah dengan penomoran ada revisinya", "buat ada rev aja", hanya "saat belum berjalan". Alternatif ditolak: meniru hapus SO lalu buat ulang (bertentangan dengan SO-FR-10 `[JSO-3]`, nomor SO yang sudah dikirim hangus, invoice dan Surat Kerja Trainer kehilangan induk). Penjualan Program draf 1h (UC-65, PJ-FR-26, PJ-FR-27, PJ-NFR-10, bagian 6, Q-PJ-22 sebagian), Sales Order draf 1l (SO-FR-16 baru, SO-FR-10, SO-NFR-03, bagian 6, Q-SO-06 baru), ERD `sales_order.revisi` (XML valid, tanpa tumpang tindih), README. Usulan analis: penanda " Rev-n", revisi hanya naik bila tanggal berubah, "belum berjalan" = hari ini sebelum tanggal mulai dan belum Closed. Mockup `mod-program.js`, `mod-sales-order.js`, `mod-quotation.js` (antislop during), diuji jsdom 16 cek tanpa error konsol. Backup `input/backup/*_sebelum-reschedule-setelah-deal_11-11-2026.*`. `[TODO]` Q-PJ-22 (b) peserta yang tidak bisa ikut di tanggal baru, (c) invoice yang sudah terbit/dibayar, (d) surat konfirmasi bertanggal lama: BA mengonfirmasi ke Labmania; sementara peserta, invoice, dan surat tidak berubah. `[TODO]` Nomor SO di Invoicing (`mod-invoice.js`, paket Invoice - Pengajuan) belum menampilkan Rev-n.
- **2026-11-11** `[DECISION]` **B2B: all-in paket s.d. 25 peserta + Rp300.000/orang, konfirmasi atas nama company.** `input/Master - Buat Program/2026-11-10-b2b-paket-25-konfirmasi-company/`. B2B hanya all-in (pilihan per peserta dihapus); harga paket mencakup s.d. 25 peserta, kurang dari 25 harga tetap, peserta ke-26 dst + Rp300.000/orang; minimal 10 tetap. Alternatif ditolak: 25 sebagai batas keras (user: lebih dari 25 tetap boleh dengan tambahan) dan mempertahankan dua skema. Konfirmasi B2B: link diisi satu kali oleh PIC company (data company & PIC, daftar peserta — Nama wajib, kolom lain opsional), satu surat konfirmasi company + lampiran daftar peserta, tanda tangan & cap, unggah wajib; menggantikan "B2B sama dengan Public" (09-11). Usulan analis: batas & tarif disimpan per quotation, tambahan kena PPN dan tidak didiskon (Q-QTN-21); format surat company, satu kiriman, perubahan lewat Sales (Q-QTN-22). Quotation to be draf 1p (QTN-FR-20 ditulis ulang, QTN-FR-31, QTN-FR-32, UC-68, aktor PIC company baru), Sales Order draf 1k (SO-FR-05, SO-FR-08), `docs-to-be/README.md`. ERD to be: `quotation.paket_maks_peserta`, `tambahan_per_peserta`, `kolom_peserta_b2b`, `quotation_peserta.sumber` + `company`, tabel `quotation_konfirmasi_company` (tabel di bawah quotation digeser +78 px; XML valid, tanpa komentar, tanpa tumpang tindih — dicek skrip). Mockup `mod-quotation.js`, `konfirmasi.html`, `style.css` dikerjakan 10-11 di sesi sebelumnya; spec/ERD/log baru ditulis di sesi ini. Backup `input/backup/*_sebelum-b2b-paket-25_10-11-2026.*`. `[TODO]` Invoice B2B kini satu invoice ke company: `docs/fr-nfr-invoice.md` IVC-FR-06/09 dan contoh data paket Invoice - Pengajuan belum diubah (paket terpisah). `[TODO]` Render ERD belum dicek visual (draw.io CLI tidak terpasang).
- **2026-11-10** `[FIX]` **Tata letak surat konfirmasi disamakan dengan template Word Labmania** (`input/Master - Buat Program/Tamplate Formulir Konfirmasi Workshop.doc`: A4, margin kiri-kanan 2,5 cm, Calibri 11 pt). Sebelumnya kertas sudah A4 tetapi margin sekitar 1,3 cm dan huruf Arial lebih kecil, sehingga isi tampak seperti di kertas yang lebih besar. Mockup: class `.kf-page` di `style.css`, `pageKonfirmasi` di `mod-quotation.js`; cetak dikunci satu halaman.
- **2026-11-10** `[DECISION]` **Surat konfirmasi bertanda tangan basah, unggahan wajib.** `input/Master - Buat Program/2026-11-10-surat-konfirmasi-ttd-basah/`. Link Konfirmasi Kehadiran mendapat template surat (format `FR/LMI-TR-01/02`) yang tercetak terisi data diri, ditandatangani dengan pena, lalu diunggah; konfirmasi tidak dapat dikirim tanpa berkas. Sales/marketing melihat dan mengunduh surat yang diunggah (Data Peserta, detail batch, View SO). Usulan: satu berkas PDF/JPG/PNG maks. 5 MB, tanggal template = tanggal cetak (Q-TB-09). Quotation to be draf 1o, Sales Order draf 1j, ERD `quotation_peserta` +2 kolom; mockup `konfirmasi.html`, `mod-quotation.js`, `mod-sales-order.js`, `style.css`.
- **2026-11-10** `[DECISION]` **Daftar Hadir: Hari/Tanggal = tanggal program.** `input/Master - Buat Program/2026-11-10-daftar-hadir-tanggal-program/` (user: "tanggal program ya"). Sekali cetak = satu set per hari pelaksanaan, tiap set format `FR/LMI-TR-05/17` dengan nomor halaman per set (usulan). Alternatif ditolak: tanggal print dan dicetak tiap hari acara. Q-MPS-04 terjawab penuh; Manage Peserta draf 1f; mockup `mod-manage-peserta.js`.
- **2026-11-10** `[DECISION]` **Jawaban konfirmasi format cetak.** `input/Master - Buat Program/2026-11-10-jawaban-format-cetak/`. Dikonfirmasi: Hari/Tanggal Surat Tugas = jadwal program, tanggal tanda tangan = tanggal print; NIK trainer & nama Operational Manager sementara kosong (Q-SKT-04 terjawab); Jenis Kegiatan Daftar Hadir = Varian Pelaksanaan. SO draf 1i, Manage Peserta draf 1e, teks Q-SKT-04 di mockup. `[TODO]` Q-MPS-04: Daftar Hadir dicetak per hari dengan tanggal print atau satu set per hari pelaksanaan dengan tanggal program; user belum paham pertanyaannya, dijelaskan ulang.
- **2026-11-10** `[DECISION]` **Format cetak resmi: Surat Tugas (Surat Kerja Trainer), Daftar Hadir, Name Table (to be).** Permintaan user: tiga format dari Labmania diletakkan di `input/` (`FR LMI TR 05-01 Surat Tugas OK (1).doc`, `Name Table.pptx`, `FR LMI TR 05 17 Daftar Hadir Peserta OKk - Copy.pdf`; tidak dipindah), dan "kalo didalamnya ada tanggal maka dibuat ke tanggal dimana dokumen tersebut di print". Intake `input/Master - Buat Program/2026-11-10-format-cetak-skt-hadir-name-table/`. `docs-to-be/fr-nfr-sales-order.md` draf 1h (SO-FR-14 format Surat Tugas, SO-FR-15, Q-SKT-04), `docs-to-be/fr-nfr-manage-peserta.md` draf 1d (MP-FR-07 format Daftar Hadir, MP-FR-11 Name Table, Q-MPS-04 terjawab), `docs-to-be/README.md`. Penafsiran `[USULAN]`: Hari/Tanggal pelaksanaan di Surat Tugas tetap jadwal program (hanya tanggal tanda tangan = tanggal cetak); Daftar Hadir Hari/Tanggal = tanggal cetak sehingga tidak lagi satu lembar per hari (dicetak tiap hari). Alternatif ditolak: mengisi Hari/Tanggal Surat Tugas dengan tanggal cetak (isi surat penugasan jadi salah). Mockup to be: `mod-sales-order.js`, `mod-manage-peserta.js`, `main.js`, `style.css` (halaman A4 landscape lewat `@page land`); diuji Chrome headless `--print-to-pdf`: Surat Tugas 1 hal portrait, Daftar Hadir 1 hal landscape (10 peserta), Name Table 10 lembar landscape. Tanpa perubahan ERD. Workbook (`docs/` as is) tidak diubah. Backup `input/backup/*_sebelum-format-cetak_10-11-2026.*`. `[TODO]` Q-SKT-04: NIK trainer dan nama Operational Manager belum ada di data, dicetak titik-titik.
- **2026-11-05** `[DECISION]` **Sidebar: tombol hide.** Permintaan user: "side bar ditambahkan button hide". Intake `input/Master - Buat Program/2026-11-05-sidebar-hide/`; `docs/fr-nfr.md` draf 11x FR-66; mockup draf 14i (tombol di kanan logo; layar lebar menyembunyikan sidebar dan tombol menu topbar memunculkannya lagi; layar sempit menutup drawer). Perilaku dan tidak disimpan per user = `[USULAN]`. Dikerjakan dengan antislop mode during. Backup `input/backup/*_sebelum-hide-sidebar_05-11-2026.*`.
- **2026-11-05** `[FIX]` **Sidebar tersembunyi: area kosong di kanan.** User: "ketika di hide, layout dari halamannya jadi aneh, kayak 3/4 kosong". Sebab: `.content` dibatasi `max-width: 1280px` rata kiri. Saat sidebar tersembunyi (layar >900px) batas dilepas, konten mengisi lebar penuh. Dicek di 1920 dan 2560 px. Backup `input/backup/style_sebelum-fix-layout-hide-sidebar_05-11-2026.css`.
- **2026-11-05** `[DECISION]` **Paket final `specs/final/` disinkronkan ke versi terbaru.** Permintaan user: "untuk FR & NFR juga update ke yang terbaru, ERD, Flow Process, Use Case, dan Mock Up". FR & NFR gabungan kini delapan bagian (tambahan Invoice draf 2i, Manage Peserta draf 1, Pengajuan Biaya draf 4a; Activity Log pindah ke Bagian 8). ERD, Flow Process, Use Case (`workbook.xlsx`) dan Mock Up (draf 14i + fix lebar konten) disalin dari sumber; nama file final tetap "Master - Quotation". `Labmania.zip` dibuat ulang. Backup `input/backup/final-master-quotation_sebelum-update_05-11-2026/`, `input/backup/Labmania_sebelum-update-final_05-11-2026.zip`. Catatan: awalan MP-FR dipakai Master Program dan Manage Peserta (dicatat di daftar isi).
- **2026-11-05** `[DECISION]` **Paket dipisah: "Master - Quotation" dan "Invoice - Pengajuan".** Permintaan user: modul baru tidak boleh membawa atau mengubah modul paket lain; nama paket memakai "-", bukan "&". (1) `final/` kini satu folder per paket, dibangun per paket dengan `final/build_final.py "<paket>"` (FR gabungan, halaman ERD/flow, baris use case, mockup sesuai paket). Penggabungan 8 bagian sebelumnya dibatalkan. (2) Mockup `app.js` dipecah per modul (`core.js`, `mod-*.js`, `main.js`; baris dipindah tanpa diubah) — diuji: 22 menu + 31 form/baris identik dengan versi lama, tanpa error. Mockup draf 14j. (3) `<html data-paket>` + `PAKET_MENU` di `core.js` membatasi sidebar dan matriks Role & Permission per paket. (4) Aturan "Packages stay separate" di `CLAUDE.md` (AGENTS.md disinkronkan). Folder intake `input/Invoice & Pengajuan/` tidak diganti nama (append-only, banyak rujukan). Backup `input/backup/final_sebelum-pisah-paket_05-11-2026/`, `*_sebelum-pecah-modul_05-11-2026.*`, `*_sebelum-paket-final_05-11-2026.md`. `[TODO]` Kode MP-FR dipakai Master Program dan Manage Peserta. `[TODO]` Pemisahan menu umum dari `fr-nfr.md` ke dokumen sendiri belum dikerjakan.
- **2026-11-05** `[DECISION]` **Master User: Role dari Master Role + Override Permission.** Permintaan user: Division dan Position diganti satu field Role; Role dikelola di Master Role (input manual) dan jadi sumber pilihan di Master User dan Role & Permission; Override Permission memberi satu user akses menu tambahan. Intake `input/Invoice & Pengajuan/2026-11-05-master-role-override/`. `docs/fr-nfr.md` draf 11w (FR-36 – FR-39, FR-54 – FR-58, FR-64 Master Role, FR-65 Override Permission, UC-32, UC-49, UC-55, Q-25, Q-35 (c) terjawab, Q-37 baru); `fr-nfr-pengajuan-biaya.md` draf 4a dan `fr-nfr-activity-log.md` draf 2b (Manajemen = role Direktur); ERD halaman Matrix Akses draf 3 (`employees.id_group` usulan, relasi ke `users` dan `groups`); workbook UC-49, UC-55, Entities. Mockup draf 14h, dikerjakan dengan antislop mode during. Usulan analis ditandai `[USULAN]`: Role wajib untuk semua user, Override hanya menambah hak, isian Master Role hanya Nama Role. ERD belum dirender visual (draw.io CLI tidak ada); tumpang tindih tabel dicek lewat skrip. Backup `input/backup/*_sebelum-master-role_05-11-2026.*`.
- **2026-11-05** `[DECISION]` **Skill antislop dipasang untuk semua mockup.** Permintaan user: "untuk semua mock up tolong tambahkan skill anti slop". Skill antislop v3.2.25 (MIT, `miqdadbadjuber/anti-slop`, commit `5d90a9a`) disalin utuh ke `.claude/skills/antislop*` (6 skill); dikreditkan di `THIRD_PARTY.md` (sebelumnya hanya tercantum sebagai rekomendasi). `CLAUDE.md` bagian *Mockups use antislop*: setiap perubahan mockup memuat antislop, mode *during*, tanpa mengubah sistem visual mockup yang sudah disepakati kecuali diminta; `AGENTS.md` digenerate ulang. Mockup yang sudah ada belum diaudit. Backup: `input/backup/CLAUDE_sebelum-antislop_05-11-2026.md`, `AGENTS_…`, `THIRD_PARTY_…`.
- **2026-11-05** `[DECISION]` **Pengajuan Biaya: tab Ajukan / Sudah Diajukan, progres sampai Paid.**
  `input/Invoice & Pengajuan/2026-11-05-tab-pengajuan-progress/`. Tab **Sudah Diajukan** berisi pengajuan Kasbon /
  Direct Payment dengan progres Waiting Approval → Waiting Payment → Paid (label baru untuk status lama
  menunggu/disetujui; `paid` status baru). Nomor per jenis (`LMI/KS`, `LMI/DP`) sudah ada sejak draf 2 — tidak berubah.
  Karena pencairan Finance belum dibuat, Paid ditandai lewat **Tandai Paid** (hak Manage) — usulan, Q-PB-17; alternatif
  "Paid hanya tampil, tanpa aksi" ditolak karena progres tidak bisa didemokan. Spec draf 4 (PB-FR-21 – PB-FR-23,
  UC-53, UC-54), ERD Pengajuan Biaya draf 4 (`paid_by`, `paid_date`, `paid_keterangan`, enum + `paid`), mockup
  draf 14g. Diuji Chrome headless: kedua tab, progres, alur Tandai Paid. `[TODO]` Render ERD belum dicek visual —
  draw.io CLI tidak terpasang; geometri diperiksa skrip (tabel tidak bertumpuk).
- **2026-11-05** `[DECISION]` **Katalog Produk dibuat: filter Bidang & Sertifikat, cetak PDF katalog.**
  `input/Master - Buat Program/2026-11-05-katalog-produk/`. FR-14 tidak lagi ditunda; isi = Paket Produk Aktif,
  tidak disimpan (ERD tetap). PDF A4: sampul, daftar isi per Bidang, kartu paket 2 kolom per Bidang
  ("lanjutan" bila terpotong), sampul belakang kontak — tata letak usulan karena klien hanya minta "seperti
  katalog pada umumnya". Spec draf 11v (FR-60 – FR-63, Q-09 terjawab, Q-36 baru), workbook UC-06 & Entities,
  mockup draf 14f (+2 paket contoh). Diuji render Chrome headless: layar, filter, PDF 5 paket (4 hlm) dan
  27 paket (8 hlm, paginasi & daftar isi benar). `[TODO]` Isi tambahan per paket (deskripsi/durasi/harga/foto)
  menunggu Q-36; aktor pembuat katalog masih Q-06.
- **2026-11-04** `[DECISION]` **Modul baru Manage Peserta (Fase 1) + Close Program; pemilih user mockup diperbaiki.**
  `input/Invoice & Pengajuan/2026-11-04-manage-peserta/`. Usulan analis disetujui user: Daftar Program (Final, tab On going / Selesai) +
  action Close Program; halaman peserta Deal per program, Cetak Daftar Peserta, Cetak Daftar Hadir per hari (template usulan — format
  Labmania belum ada), Export Excel. Fase 2 (kehadiran, sertifikat, rekap) ditunda; hak pakai diatur user di Role & Permission (View, Manage
  = Close). Close memakai kolom existing `penjualan_program.closed_by/closed_date` (status tetap final) — ditolak alternatif ENUM `closed`
  karena Quotation / Pengajuan / Dashboard menyaring `status = final`. Close mengisi status Closed yang "menyusul" di PB-FR-20 `[JPB-12]`.
  Dokumen: `fr-nfr-manage-peserta.md` draf 1 (MP-FR-01 – MP-FR-10, Q-MPS-01 – 05), `fr-nfr-pengajuan-biaya.md` draf 3c, ERD catatan
  *ERD Penjualan Program* (kotak catatan dipertinggi; render belum dicek — draw.io CLI tidak ada), workbook UC-50 – UC-52 + Modules.
  Mockup draf 14e diuji harness Chrome headless (list → peserta → cetak → hadir 3 hari → export → close → PB terkunci → log) tanpa error JS.
  `[TODO]` Q-MPS-01 – Q-MPS-05.
- **2026-11-04** `[DECISION]` **Superadmin = role bawaan sistem; hapus role tanpa user disetujui; mengoreksi usulan entri di bawah.**
  `input/Invoice & Pengajuan/2026-11-04-jawaban-role-permission/`. Superadmin tidak lagi "IT – Developer" (Division + Jabatan) melainkan role
  bawaan yang dibuat dari awal dan tidak dapat ditambah; user IT – Developer baru = role biasa. Role tanpa user boleh dihapus. Manajemen =
  jabatan Direktur disetujui sebagai data sementara. `fr-nfr.md` draf 11u (FR-38, FR-55, FR-56, Q-35 sisa: role Admin, hak per user);
  `fr-nfr-pengajuan-biaya.md` draf 3b; mockup draf 14d diuji ulang harness Chrome headless tanpa error JS.
  `[TODO]` Q-35 sisa, Q-PB-14 daftar posisi, Q-33.
- **2026-11-04** `[DECISION]` **Matrix Akses Menu → Role & Permission; role = Division + Jabatan; Department dihapus dari Master User.**
  `input/Invoice & Pengajuan/2026-11-04-role-permission/` (chat + video `Role & Permission.mp4`, frame disimpan). Pilihan Group Akses di form
  user ditolak user ("select role malah salah"): role kini terbentuk otomatis dari Division + Jabatan saat akun disimpan, lalu hak menu × aksi
  diatur di menu Role & Permission (daftar role + modal matrix mengikuti video; tombol "Add New Role" video tidak dipakai). `fr-nfr.md` draf 11t
  (FR-36 – FR-39, FR-54 – FR-58, UC-32, UC-49, NFR-13, Q-25, Q-35 baru); `fr-nfr-pengajuan-biaya.md` draf 3a; `fr-nfr-activity-log.md` draf 2a;
  ERD *ERD Matrix Akses* (label/catatan saja, tanpa tabel baru — XML valid, render belum dicek: draw.io CLI tidak terpasang); workbook UC-49,
  Modules, Entities. Mockup draf 14c diuji harness Chrome headless (tambah user → role baru → atur akses → login sebagai role baru) tanpa error JS.
  `[TODO]` Q-35 (role mana = Superadmin/Admin, hapus role tanpa user), Q-PB-14 (Manajemen sementara jabatan Direktur), Q-33.
- **2026-11-04** `[DECISION]` **Pengajuan Biaya draf 3: budget per kategori, sales hanya quotation Deal, Superadmin vs Admin.**
  `input/Invoice & Pengajuan/2026-11-04-jawaban-pengajuan-biaya-2/`. Batas pengajuan = sisa total Fixed Cost / Variable Cost (bukan per item;
  item boleh melebihi budget item-nya, hanya ditandai). Tim Sales hanya dari quotation Deal. Akun login trainer disetujui (`fr-nfr.md` FR-59).
  Q-34: Superadmin (developer, group id 1, terkunci) dan Admin (user Labmania, hak modul dapat dibatasi, data semua) — FR-56, FR-58, NFR-13
  (`fr-nfr.md` draf 11s). Manajemen ditentukan dari posisi Master User, daftar posisi menyusul (Q-PB-14). ERD (catatan, label), BPMN
  (gateway "Sisa kategori cukup?"), workbook UC-43/48/49 disinkronkan; mockup draf 14b diuji 11 langkah harness tanpa error JS.
  `[TODO]` Q-33 dicek user; Q-PB-11 (IT), Q-PB-12, Q-PB-14 (daftar posisi Manajemen).
- **2026-11-04** `[DECISION]` **Pengajuan Biaya draf 2 + Matrix Akses Menu; mengoreksi entri draf 1 di bawah.**
  `input/Invoice & Pengajuan/2026-11-04-jawaban-pengajuan-biaya/` (jawaban Q-PB-01 – Q-PB-12). Field **PIC Program (PJ-FR-26) dibatalkan**:
  pengaju = **tim program** (Sales di quotation, Trainer Penjualan Program lewat akun login, group Manajemen); `fr-nfr-penjualan-program.md`
  dikembalikan ke draf 12b, `penjualan_program.id_pic` dihapus dari ERD. **Pencairan / aktor Keuangan ditunda** (status Dicairkan, kolom `cair_*`,
  menu Pencairan, UC-46 dikeluarkan); satu tombol **Add Pengajuan** dengan pilihan jenis di form; overbudget boleh **item baru**
  (`pengajuan_biaya_detail.id_overbudget_detail`). `[DISCOVERY]` `application/` adalah basis kode ERP CONSULTANT (aplikasi di video):
  tabel kasbon `kons_tr_kasbon_project_*` milik modul konsultan, dan hak akses sudah ada (`groups`, `permissions` View/Add/Manage/Delete,
  `group_permissions`, `user_permissions`, `menus`; group id 1 = Admin lolos semua) — halaman per group belum ada, maka **Matrix Akses Menu**
  dispesifikasikan tanpa tabel baru (`fr-nfr.md` draf 11r FR-54 – FR-58, UC-49; ERD halaman *ERD Matrix Akses*). BPMN Level 1 draf 2 (pool Tim Program,
  Manajemen). Workbook dipulihkan dari backup lalu diisi UC-43 – UC-45, UC-47 – UC-49. Mockup draf 14a dibangun ulang dari backup: sidebar
  mengikuti matrix, hak data tim program, Admin melihat semua, akun login di Master Trainer; diuji 22 langkah harness Chrome headless tanpa error JS.
  `[TODO]` Q-PB-11 (tabel baru vs kasbon konsultan, pencairan via `request_payment`) perlu IT developer; Q-PB-12 – Q-PB-16, Q-33, Q-34.
- **2026-11-04** `[DECISION]` **Modul baru Pengajuan Biaya (draf 1): PIC Program mengajukan biaya, Manajemen menyetujui, Keuangan mencairkan.**
  `input/Invoice & Pengajuan/2026-11-04-pengajuan-biaya-as-is/` (permintaan user + video `Menu Pengajuan Biaya - As Is.mp4`, dibaca
  per frame). SPK acuan dipetakan ke **Penjualan Program Final**; budget = costing Penjualan Program per item (qty dari satuan ×
  durasi × target peserta), kategori Fixed / Variable Cost menggantikan Akomodasi / Others; Kasbon & Direct Payment, Overbudget,
  Approval, Pencairan. PO dan pertanggungjawaban kasbon tidak diambil. Hampir semua pemetaan `[USULAN]` → Q-PB-01 – Q-PB-12.
  Spec baru `docs/fr-nfr-pengajuan-biaya.md`; PJ draf 12c (PJ-FR-26 PIC Program); ERD halaman *ERD Pengajuan Biaya* + `penjualan_program.id_pic`;
  BPMN *Level 1 — Pengajuan Biaya* (pool PIC Program / Manajemen / Keuangan) + kotak Level 0; workbook UC-43 – UC-48, modul, 5 entity;
  mockup draf 14. ERD & BPMN dicek render (viewer diagrams.net + Chrome headless); mockup diuji harness Chrome headless
  (ajukan, tolak lewat sisa, overbudget, approval, blok approval diri sendiri, pencairan, hak lihat PIC, Activity Log) tanpa error JS.
  Backup: `input/backup/*_sebelum-pengajuan-biaya_04-11-2026.*`.
  `[TODO]` Konfirmasi klien: aktor PIC & Keuangan (Q-PB-01, Q-PB-02), kategori biaya (Q-PB-03), basis qty Variable Cost (Q-PB-04).
- **2026-11-02** `[DECISION]` **Format nomor Penawaran `LMI/PW/MM/yy-zzz` dan Invoice `LMI/INV/MM/yy-zzz`.**
  `input/Master - Buat Program/2026-11-02-format-penomoran-penawaran-invoice/`. Menggantikan nomor template
  `LM-I/AM/PW/MM/YY-XXX` (segmen AM dibuang) dan usulan invoice `NNN/LMI/YYYY`. Quotation draf 10 (QTN-FR-04,
  Q-QTN-01), Invoice draf 2f (IVC-FR-05, IVC-NFR-03, Q-IVC-04). Mockup: satu fungsi `lmiNo(kode, tanggal)` dipakai
  quotation & invoice; diuji jsdom (form quotation baru `LMI/PW/11/26-001`, invoice contoh `LMI/INV/10/26-001`).
  `[TODO]` Konfirmasi klien: `MM/yy` dari tanggal dokumen dan urutan reset tiap bulan masih `[USULAN]`.
  `specs/final/` (paket final Quotation) belum disinkronkan.
- **2026-10-30** `[DECISION]` **Invoice draf 2e: Print Invoice dengan template usulan analis.**
  `input/Invoice & Pengajuan/2026-10-30-print-invoice/`. Aksi Print Invoice di tab Invoiced dan di Lihat Invoice (IVC-FR-15,
  IVC-NFR-07). Format usulan: kop dan kaki halaman sama dengan Penawaran, data invoice + No. Program, rincian, DPP/PPN, baris
  WAPU/KB, Jumlah yang harus dibayar + terbilang, blok rekening, tanda tangan Finance. Q-IVC-10 menjadi daftar isian dari klien
  (rekening, penanda tangan, jatuh tempo, kode dokumen). Mockup draf 13f: render dicek lewat screenshot; uji jsdom end-to-end 71 cek
  (termasuk isi cetak WAPU & B2B per peserta). Test case Excel v2 (35 TC) di
  `input/Invoice & Pengajuan/test_case_master_program_sampai_invoice_v2_30-10-2026.xlsx` — v1 sedang terbuka sehingga tidak ditimpa.
  Backup `input/backup/fr-nfr-invoice_draf2d_30-10-2026.md`, `input/backup/app_sebelum-print-invoice_30-10-2026.js`.
- **2026-10-30** `[DECISION]` **Invoice draf 2d: menu Report Jurnal Invoice dan Report Uninvoiced & Piutang ditunda.**
  `input/Invoice & Pengajuan/2026-10-30-tanpa-report-invoice/`. Modul Invoice hanya menu Invoicing; IVC-FR-14 dan UC-42 ditandai
  ditunda (rancangan tetap disimpan). Mockup draf 13e: menu dan kode kedua report dihapus, diuji jsdom 58 cek. Backup
  `input/backup/fr-nfr-invoice_draf2c_30-10-2026.md`, `input/backup/app_sebelum-hapus-report-invoice_30-10-2026.js` (berisi kode report).
- **2026-10-30** `[DECISION]` **Invoice draf 2c: Add Invoicing halaman penuh, bukan pop up.**
  `input/Invoice & Pengajuan/2026-10-30-add-invoice-halaman-penuh/`. Lima bagian bernomor (Data Invoice, Cara Tagih B2B,
  Rincian, Perhitungan, Jurnal), tombol Back/Save tetap di bawah; Lihat Invoice dan Bukti Setor tetap pop up. Mockup draf 13d
  diuji jsdom 64 cek. Render dicek lewat screenshot Chrome headless: kolom Debit/Credit jurnal sempat terdorong keluar,
  diperbaiki dengan lebar kolom tetap. Backup `input/backup/fr-nfr-invoice_draf2b_30-10-2026.md`,
  `input/backup/app_sebelum-add-invoice-halaman_30-10-2026.js`.
- **2026-10-30** `[DECISION]` **Invoice draf 2b: status Uninvoiced/Invoiced saja, tab Invoiced, Public/B2B lewat filter.**
  `input/Invoice & Pengajuan/2026-10-30-tab-invoiced-filter-jenis/`. Status "Sebagian" (usulan analis) dihapus: B2B per peserta
  tetap Uninvoiced sampai semua peserta ditagih. Tab Program Public/B2B (`[IPQ-2]`) diganti tab Uninvoiced (per quotation)
  dan Invoiced (per invoice, usulan); Public/B2B lewat filter jenis. Mockup draf 13c diuji jsdom 59 cek. Backup
  `input/backup/fr-nfr-invoice_draf2a_30-10-2026.md`, `input/backup/app_sebelum-tab-invoiced_30-10-2026.js`.
- **2026-10-30** `[DECISION]` **Invoice draf 2a: invoice B2B mengikuti skema quotation.**
  `input/Invoice & Pengajuan/2026-10-30-invoice-b2b-ikut-skema/`. B2B all-in ke company = 1 invoice; B2B per peserta =
  quotation pecah = 1 invoice per peserta Deal (harga − diskon). Pilihan manual di form invoice dihapus. Ditolak: memecah
  all-in ÷ peserta (`[BAD-2]`, kini digantikan). Q-IVC-15 terjawab. Spec Quotation draf 9d diselaraskan (tabel Public vs B2B,
  Q-QTN-16). Mockup draf 13b (seed program B2B per peserta), diuji jsdom 67 cek. Backup
  `input/backup/fr-nfr-invoice_draf2_30-10-2026.md`, `input/backup/fr-nfr-quotation_draf9c_30-10-2026.md`.
- **2026-10-30** `[DECISION]` **Invoice draf 2: satu quotation Deal = satu invoice, tab Public/B2B, kolom No. Program.**
  `input/Invoice & Pengajuan/2026-10-30-invoice-per-quotation-tab/`. Quotation Deal dalam satu program tidak digabung
  (10 Deal → 10 invoice). Daftar Invoicing dipisah tab Program Public / Program B2B. No. Program ada di daftar, form,
  jurnal, Lihat Invoice, kedua report, dan filter program. Q-IVC-03 terjawab. Q-IVC-15 baru: pecah B2B per peserta tetap
  dipertahankan sebagai pengecualian sampai dijawab. Mockup draf 13a (seed: 3 quotation Deal di satu program Public), diuji jsdom
  70 cek. Backup `input/backup/fr-nfr-invoice_draf1_30-10-2026.md`, `input/backup/app_sebelum-invoice-tab_30-10-2026.js`.
- **2026-10-30** `[DECISION]` **Invoice draf 1: konsep Invoicing konsultasi dipetakan ke Quotation Deal.**
  `input/Invoice & Pengajuan/2026-10-30-invoice-consulting/` (`Invoice Consulting.xlsx` + `.html`). Spec baru
  `docs/fr-nfr-invoice.md` (IVC-FR-01 – -14, IVC-NFR-01 – -06, UC-40 – UC-42, Q-IVC-01 – -14). Rumus, perlakuan PPN
  (PKP/WAPU/Kawasan Berikat), jurnal + Balancing, COA Penjualan, dan pembalikan PPN WAPU diambil dari acuan. SPK/TOP
  diganti dengan unit tagih tanpa termin (usulan): satu invoice per quotation, B2B dapat per peserta (`[B2B-5]`, `[BAD-2]`).
  Ditolak: menyalin termin/plan tagih, karena Quotation Labmania tidak punya pilihan pembayaran. Field baru **Perlakuan PPN** di
  Master Company (usulan, belum masuk `fr-nfr.md` FR-31). Mockup draf 13 (menu Invoice: Invoicing, Report Jurnal,
  Report Uninvoiced & Piutang; aksi "Buat Invoice" di quotation Deal). Diuji jsdom 56 cek; rumus cocok dengan sheet Test Case
  (WAPU, PKP, KB). Backup `input/backup/*_sebelum-invoice_30-10-2026*`. `[TODO]` ERD invoice menunggu Q-IVC-01 (aplikasi
  sudah punya `invoicing`/`tr_invoice_*`/`jurnal`); UC-40 – UC-42 belum masuk workbook; render mockup belum dicek visual.
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
  `input/Master - Buat Program/2026-10-29-cetak-quotation-snk-konfirmasi/` (template `input/Master - Buat Program/Template Penawaran Labmania.pdf`,
  `input/Master - Buat Program/SYARAT & KETENTUAN KEBIJAKAN LAYANAN PEMBELAJARAN.pdf`, `input/Master - Buat Program/Tamplate Formulir Konfirmasi Workshop.doc` —
  .doc dibaca lewat konversi Word read-only ke scratchpad). Nomor quotation kini `LM-I/AM/PW/MM/YY-XXX`; field baru
  Pembayaran, Catatan (maks. 3), Biaya Lain-lain; Formulir Konfirmasi satu halaman per peserta, terisi dari Master
  Peserta (NIK, Jabatan, Masa Pengalaman kosong); Confirmation Letter memakai format formulir ini untuk peserta Deal.
  Field **Waktu** di Penjualan Program (usulan) karena template butuh jam. Spec Quotation draf 3 (QTN-FR-14 – 16,
  QTN-NFR-06, Q-QTN-11 – 13), PJ draf 11a (PJ-FR-24, Q-PJ-21), ERD `penjualan_program.waktu` (XML valid). Mockup draf
  11r diuji jsdom (15 cek) dan **dirender Chrome headless ke PDF**: 5 halaman A4 sesuai template (sempat ditemukan
  kolom menumpuk saat print karena media query layar sempit — diperbaiki dengan `@media screen`). `[TODO]` Cek lama
  "Confirmation Letter: surat kehadiran + denda" di skrip uji sudah tidak berlaku (format diganti).

- **2026-10-29** `[DECISION]` **Master Trainer baru (format CV Excel); Trainer dipilih di Penjualan Program.**
  `input/Master - Buat Program/2026-10-29-master-trainer/` (sumber `input/Master - Buat Program/Master Trainer Ivan Sarifudin.xlsx`, 5 sheet).
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
  `input/Master - Buat Program/2026-10-29-edit-master-sertifikat-cari/`. "Hanya ada Hapus" merujuk `input/Master - Buat Program/labmania.html`
  (tidak diubah — sumber `input/`); master atribut di mockup kita sudah Edit + Nonaktifkan, yang baru Edit Paket Produk
  (FR-43, Q-14 terjawab; field & efek ke Master Program usulan). Label master menjadi Jenis Sertifikat / kolom
  Sertifikat; nilai Jenis Produk "Sertifikasi" dan tabel ERD `ms_jenis_sertifikasi` tetap. Dropdown dengan kolom cari
  (FR-12, NFR-10) di Tambah & Edit Product. `docs/fr-nfr.md` draf 11j, mockup draf 11n diuji jsdom (18 cek + regresi
  Quotation 33 cek). `[TODO]` Nama UC-02 di `docs/workbook.xlsx` masih "Jenis Sertifikasi"; dropdown cari belum
  diterapkan di form lain (Peserta, Company, Rate) yang juga dituntut NFR-10; render belum dicek visual.

- **2026-10-29** `[DECISION]` **Quotation draf 2: Personal banyak peserta, diskon Rupiah, index per program, 4 aksi.**
  `input/Master - Buat Program/2026-10-29-quotation-diskon-aksi/`. Personal boleh banyak peserta; peserta baru diisi di
  form quotation dan langsung tersimpan di Master Peserta; company peserta ikut tampil. Diskon Rupiah per peserta
  (usulan, Q-QTN-06). Index dikelompokkan per Penjualan Program mengikuti `input/Master - Buat Program/labmania.html`. Aksi Kirim dan
  Cetak / Deal / Batalkan / Confirmation Letter; status Draft → Terkirim → Deal / Batal (Selesai & Hapus dihapus).
  Deal membuka centang peserta (default semua) agar Deal/Batal per peserta `[JPP-15]` tetap ada — usulan, Q-QTN-07.
  B2B satu quotation aktif (usulan, Q-QTN-08). Spec `docs/fr-nfr-quotation.md` draf 2, `docs/fr-nfr.md` draf 11i;
  mockup draf 11m diuji jsdom (33 cek). Backup draf lama di `input/backup/*_29-10-2026*`. `[TODO]` Format Confirmation
  Letter menyusul (Q-QTN-05); ERD quotation belum (Q-QTN-04); render mockup belum dicek visual.

- **2026-10-28** `[DECISION]` **Public & B2B Final masuk ke modul Quotation (ke Company atau Personal).**
  `input/Master - Buat Program/2026-10-28-quotation-company-personal/` (acuan `input/Master - Buat Program/labmania.html`). Quotation ke Company memilih
  peserta yang terdaftar di company itu, jadi Master Peserta diberi field Company opsional (usulan). Personal = satu
  peserta (usulan). Harga dari Penjualan Program, tanpa diskon, PPN 11% opsional. Status Draft → Terkirim → Selesai,
  Deal/Batal per peserta (`[JPP-15]`). Spec baru `docs/fr-nfr-quotation.md` draf 1; PJ draf 10, Master Data draf 11h;
  mockup draf 11l diuji jsdom + screenshot. `notes.md` intake menulis "spec belum ditulis" — sudah ditulis di entri ini.
  `[TODO]` Sales Order dipertahankan atau tidak (Q-QTN-03); ERD tabel quotation + `ms_peserta.id_customer` (Q-QTN-04).
- **2026-10-28** `[DECISION]` **Master User ditambahkan ke Master Data (sub modul Pengguna).**
  `input/Master - Buat Program/2026-10-28-master-user/` (gambar `input/Master - Buat Program/Master User.png`). Form mengikuti gambar; ditambah
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
