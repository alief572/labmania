# Dokumen TO BE — Labmania

Folder ini berisi rancangan **to be** yang terpisah dari dokumen **as is** di `docs/`
(`input/Master - Buat Program/2026-11-07-dokumen-to-be-terpisah/`). Isinya mengikuti perubahan di
`input/Master - Buat Program/2026-11-06-to-be-kuota-link-konfirmasi/` dan mockup `ui-mockup/produk-to-be/`.

- **Status:** DRAF TO BE draf 1 — 07-11-2026, menunggu konfirmasi Labmania.
- **Selama belum disetujui**, program dan paket final tetap mengikuti `docs/` (as is). Dokumen di sini
  tidak mengubah `docs/`, dan `docs/` tidak mengubah dokumen di sini.

## Isi folder

| File | Pasangan as is | Isi to be |
|---|---|---|
| `fr-nfr.md` | `docs/fr-nfr.md` (draf 11x) | Master Data: **Varian Pelaksanaan kembali**, Product Combine 4 atribut; **Lingkup** per paket di Katalog Produk; Master Peserta bukan sumber peserta Quotation, tetapi **peserta Deal disimpan ke Master Peserta** saat Deal |
| `fr-nfr-quotation.md` | `docs/fr-nfr-quotation.md` (draf 10a) | Quotation: **Kuota peserta**, **link Konfirmasi Kehadiran**, tab **Data Peserta**, Confirmation Letter dari isian (tanpa tambah manual) |
| `fr-nfr-sales-order.md` | — (baru; as is: "Sales Order tidak ada" `[B2B-3]`) | Sales Order di modul Quotation: 1 quotation Deal = 1 SO, daftar per program, View (quotation + surat konfirmasi dari link), Print Out SO, **Siap dibuatkan invoice**; invoice dibuat dari SO. SO = surat konfirmasi pesanan ke customer; nomor format menyusul; tidak dapat dibatalkan. Tab **Surat Kerja Trainer** |
| `fr-nfr-master-program.md` | `docs/fr-nfr-master-program.md` (draf 11b) | Master Program memilih **satu atau lebih Product**; costing tetap satu |
| `fr-nfr-manage-peserta.md` | `docs/fr-nfr-manage-peserta.md` (draf 1) | Tab **Sertifikat** di halaman peserta program (**sertifikat pelatihan dibuat di sistem**, pengiriman, data LSP/BNSP) dan sub menu **Monitoring Peserta** (membaca data sistem + catatan); tab **Survey**: link survey per program diisi peserta |
| `fr-nfr-penjualan-program.md` | `docs/fr-nfr-penjualan-program.md` (draf 12b) | **Trainer wajib** dipilih (PJ-FR-23), untuk Surat Kerja Trainer; sisanya sama dengan as is |
| `diagrams/erd.drawio` | `docs/diagrams/erd.drawio` | Halaman *ERD Master Data* (Varian, Lingkup), *ERD Quotation* (kuota, link, peserta, `sales_order`, `surat_kerja_trainer`), *ERD Penjualan Program* dan *ERD Pengajuan Biaya* (`id_trainer` wajib) diubah; halaman baru *ERD Survey Peserta*; halaman lain sama dengan as is |
| `diagrams/bpmn-to-be.drawio` | `docs/diagrams/bpmn-to-be.drawio` | Halaman *Product Combine* (4 atribut), *Level 1 — Penjualan Program* (trainer wajib), dan *Level 1 — Quotation* (pool Calon Peserta, link, Data Peserta, Sales Order, Surat Kerja Trainer); halaman lain sama dengan as is |

Modul yang **tidak** punya file di sini (Invoice,
Pengajuan Biaya, Activity Log) tetap memakai dokumen as is di `docs/`, dengan catatan dampak di bawah.

## Ringkasan perubahan

| # | Perubahan (butir di `input/.../2026-11-06-to-be-kuota-link-konfirmasi/`) | Dokumen |
|---|---|---|
| 1 | Master **Varian Pelaksanaan** kembali (In House Training, Workshop, Workshop Berbasis Kompetensi, Workshop Lisensi BSN); Product Combine = Produk + Bidang + Varian + Sertifikat; kombinasi unik atas 4 atribut | `fr-nfr.md` FR-01, FR-02, FR-07 – FR-10, FR-12, FR-22, FR-26, FR-43, UC-04, UC-05, lampiran 7 · ERD `ms_varian_pelaksanaan`, `paket_produk.id_varian_pelaksanaan` |
| 2 | Form quotation: pilihan peserta diganti **Kuota peserta** | `fr-nfr-quotation.md` QTN-FR-05 – QTN-FR-08, QTN-FR-21, QTN-FR-22 · ERD `quotation.kuota_peserta`, `quotation.kepada_nama` |
| 3 | Peserta quotation **tidak** dari Master Peserta; data ada setelah Konfirmasi Kehadiran diisi. Sejak 11-11-2026 peserta **Deal** disimpan ke Master Peserta (baris MP) | `fr-nfr.md` FR-15 · `fr-nfr-quotation.md` QTN-FR-25 · ERD `quotation_peserta.id_peserta` diisi saat Deal |
| 4 | **Link Konfirmasi Kehadiran** dibuat otomatis saat quotation disimpan, khusus quotation itu; tiap calon peserta mengisi sendiri | QTN-FR-23 – QTN-FR-25, QTN-NFR-08, QTN-NFR-10 · ERD `quotation.link_token`, `link_dibuat` · BPMN pool Calon Peserta |
| 5 | Hasil isian masuk tab **Data Peserta** quotation, dengan preview dan download | QTN-FR-26, QTN-FR-28, QTN-NFR-09 |
| 6 | ~~Sales tetap dapat menambah peserta manual~~ **dibatalkan** `input/Master - Buat Program/2026-11-09-peserta-hanya-lewat-link/`: peserta hanya lewat link | ~~QTN-FR-27~~, Q-TB-08 |
| CL | **Confirmation Letter = dokumen output quotation**, dibentuk dari isian; PDF quotation = Penawaran + S&K | QTN-FR-10, QTN-FR-13, QTN-FR-16 |
| MON | Sub menu **Monitoring Peserta** di Manage Peserta, mengacu file Excel Labmania (`input/Invoice & Pengajuan/2026-11-09-monitoring-peserta/`) | `fr-nfr-manage-peserta.md` SRT-FR-01 – SRT-FR-03, MON-FR-01 – MON-FR-06 · ERD `quotation_peserta_sertifikat`; tab Sertifikat `input/Invoice & Pengajuan/2026-11-09-sertifikat-monitoring-sesuai-sistem/` |
| SO | **Sales Order**: dibentuk otomatis saat quotation Deal; invoice hanya dari SO berstatus Siap invoice (`input/Master - Buat Program/2026-11-07-sales-order/`) | `fr-nfr-sales-order.md` SO-FR-01 – SO-FR-09, UC-60 – UC-63 · `fr-nfr-quotation.md` QTN-FR-09, QTN-FR-11 · ERD `sales_order` |
| SKT | **Surat Kerja Trainer** sebagai tab di modul Sales Order, satu per program; **trainer wajib** di Penjualan Program (`input/Master - Buat Program/2026-11-07-surat-kerja-trainer/`) | `fr-nfr-sales-order.md` SO-FR-11 – SO-FR-14, UC-64, Q-SKT-01 – Q-SKT-03 · `fr-nfr-penjualan-program.md` PJ-FR-23 · ERD `surat_kerja_trainer` |
| FT | **Filter Tahun & Bulan** (tanggal mulai acara program) di Penjualan Program, Quotation, dan Sales Order (kedua tab) (`input/Master - Buat Program/2026-11-07-filter-tahun-bulan/`) | `fr-nfr-penjualan-program.md` PJ-FR-01 · `fr-nfr-quotation.md` QTN-FR-01 · `fr-nfr-sales-order.md` SO-FR-02, SO-FR-11 |
| RS | **Reschedule** Penjualan Program Final sebelum ada quotation Deal, alasan wajib, riwayat jadwal (`input/Master - Buat Program/2026-11-08-jawaban-reschedule/`) | `fr-nfr-penjualan-program.md` PJ-FR-26 – PJ-FR-28, UC-65, PJ-NFR-10 · ERD `penjualan_program_reschedule` |
| SM | **Sub menu Master Data** di sidebar: Product, User, Peserta & Trainer (+ Dokumen sementara, Q-39), dapat dibuka/ditutup (`input/Master - Buat Program/2026-11-07-sub-menu-master-data/`) | `fr-nfr.md` bagian 1, FR-27, Q-18, Q-39 |
| LK | **Lingkup per Paket Produk** diisi dari Katalog Produk (boleh lebih dari 1 item) dan tampil di PDF katalog (`input/Master - Buat Program/2026-11-07-katalog-lingkup/`) | `fr-nfr.md` FR-61, FR-62, FR-67, FR-68, Q-38 · ERD `paket_produk_lingkup` |
| JP | **Jam Pembelajaran** (isian angka + satuan JP) di Penjualan Program, dicetak di Penawaran; **Berlaku sampai** quotation default 31 Desember tahun Tanggal quotation (`input/Master - Buat Program/2026-11-09-jam-pembelajaran-berlaku-quotation/`) | `fr-nfr-penjualan-program.md` PJ-FR-29, Q-PJ-25 · `fr-nfr-quotation.md` QTN-FR-04, QTN-FR-14 · ERD `penjualan_program.jam_pembelajaran` |
| LB | **Link Konfirmasi Kehadiran berlaku sampai tanggal Berlaku sampai** quotation, lalu tertutup otomatis (`input/Master - Buat Program/2026-11-09-link-berlaku-quotation/`) | `fr-nfr-quotation.md` QTN-FR-23, QTN-FR-24, Q-TB-02 |
| DP | Tab **Data Peserta** di Daftar Quotation: daftar batch Penjualan Program seperti On going; **Lihat detail** menampilkan peserta batch itu dengan **Confirmation Letter** per peserta dan **Export PDF** (daftar + Confirmation Letter) (`input/Master - Buat Program/2026-11-09-tab-data-peserta-batch/`, `input/Master - Buat Program/2026-11-09-tab-data-peserta-batch-detail/`, `input/Master - Buat Program/2026-11-09-batch-cl-export-pdf/`) | `fr-nfr-quotation.md` QTN-FR-29, Q-QTN-19 |
| AQ | Aksi quotation **tanpa Lihat Sales Order dan Confirmation Letter** (lewat menu Sales Order dan tab Data Peserta) (`input/Master - Buat Program/2026-11-09-aksi-quotation-tanpa-so-cl/`) | `fr-nfr-quotation.md` QTN-FR-09, QTN-FR-26 |
| TC | **Tanggal print out** Quotation, Sales Order, Surat Kerja Trainer = tanggal cetak; **Confirmation Letter** = waktu peserta mengisi link (`input/Master - Buat Program/2026-11-09-tanggal-print-out/`) | `fr-nfr-quotation.md` QTN-FR-30 · `fr-nfr-sales-order.md` SO-FR-15 |
| PR | Menu **Product Combine** → **Product**; Master Program memilih **lebih dari satu Product**, costing tetap satu; **Reschedule** hanya tanggal + trainer dengan **evidence** wajib JPG/PNG/DOC/DOCX/PDF (`input/Master - Buat Program/2026-11-09-product-multi-reschedule-evidence/`) | `fr-nfr.md` · `fr-nfr-master-program.md` MP-FR-01 – MP-FR-03 · `fr-nfr-penjualan-program.md` PJ-FR-26, PJ-FR-28, Q-PJ-26 · ERD `master_program_produk`, `penjualan_program_reschedule_evidence` |
| DD | **Dokumen dari customer** (PO, SPK, dokumen apa pun) **wajib diunggah saat Deal**, tampil di Sales Order (`input/Master - Buat Program/2026-11-09-dokumen-deal/`) | `fr-nfr-quotation.md` UC-35, QTN-FR-11, Q-QTN-20 · `fr-nfr-sales-order.md` SO-FR-05 · ERD `quotation_dokumen` |
| CL | **B2B tetap punya Confirmation Letter** (formulir konfirmasi + pernyataan seperti Public) (`input/Master - Buat Program/2026-11-09-b2b-tetap-confirmation-letter/`) | `fr-nfr-quotation.md` QTN-FR-13, QTN-FR-19, QTN-FR-25, Q-TB-03 · `fr-nfr-sales-order.md` SO-FR-05 |
| FC | **Format cetak resmi Labmania**: Surat Kerja Trainer dicetak sebagai **Surat Tugas** `FR/LMI-TR-05/01`; **Daftar Hadir** `FR/LMI-TR-05/17` (A4 landscape, 10 baris/halaman, Hari/Tanggal = tanggal program, satu set per hari, `input/Master - Buat Program/2026-11-10-daftar-hadir-tanggal-program/`); **Name Table** baru di Manage Peserta, satu peserta satu lembar (`input/Master - Buat Program/2026-11-10-format-cetak-skt-hadir-name-table/`) | `fr-nfr-sales-order.md` SO-FR-14, SO-FR-15, Q-SKT-04 · `fr-nfr-manage-peserta.md` MP-FR-04, MP-FR-07, MP-FR-10, MP-FR-11, UC-51, Q-MPS-04 · tanpa perubahan ERD |
| TT | **Surat konfirmasi bertanda tangan basah**: di link Konfirmasi Kehadiran calon peserta mencetak surat yang sudah terisi, menandatangani, lalu **wajib mengunggahnya**; Sales/marketing melihat surat yang diunggah (`input/Master - Buat Program/2026-11-10-surat-konfirmasi-ttd-basah/`) | `fr-nfr-quotation.md` UC-56, QTN-FR-13, QTN-FR-16, QTN-FR-25, QTN-FR-28, QTN-FR-29 · `fr-nfr-sales-order.md` SO-FR-05 · ERD `quotation_peserta.surat_konfirmasi` |
| B2 | **B2B hanya all-in**: harga paket s.d. **25 peserta**, peserta ke-26 dst **+ Rp300.000/orang**, minimal 10 tetap; **konfirmasi atas nama company**: link diisi satu kali oleh **PIC company** (data company & PIC + daftar peserta), satu **surat konfirmasi company** + lampiran, tanda tangan & cap, unggah wajib — menggantikan baris CL "B2B sama dengan Public" (`input/Master - Buat Program/2026-11-10-b2b-paket-25-konfirmasi-company/`). Dampak: invoice B2B jadi satu ke company (paket Invoice - Pengajuan, belum diubah) | `fr-nfr-quotation.md` QTN-FR-20, QTN-FR-31, QTN-FR-32, UC-68, Q-QTN-21, Q-QTN-22 · `fr-nfr-sales-order.md` SO-FR-05, SO-FR-08 · ERD `quotation.paket_maks_peserta`, `tambahan_per_peserta`, `kolom_peserta_b2b`, `quotation_konfirmasi_company` |
| RD | **Reschedule setelah Deal** selama acara belum berjalan dan program belum Closed; Sales Order **tidak dihapus**, jadwalnya ikut berubah, nomor SO mendapat **revisi** (`SO-001 Rev-1`) (`input/Master - Buat Program/2026-11-11-reschedule-setelah-deal/`). Masih dikonfirmasi: peserta yang tidak bisa ikut, invoice yang sudah terbit, surat konfirmasi bertanggal lama (Q-PJ-22) | `fr-nfr-penjualan-program.md` PJ-FR-26, PJ-FR-27, PJ-NFR-10, Q-PJ-22 · `fr-nfr-sales-order.md` SO-FR-16, Q-SO-06 · ERD `sales_order.revisi` |
| MP | **Peserta Deal ke Master Peserta**: saat Deal, peserta Deal disimpan ke Master Peserta (cocok NIK/email/WA: hanya mengisi field kosong; tidak cocok: peserta baru). Link tidak berubah; **Bidang** dari Product program dan **Provinsi/Kota** dari Master Company terisi otomatis; field lain dilengkapi admin, ditandai **Belum lengkap** (`input/Master - Buat Program/2026-11-11-peserta-deal-ke-master-peserta/`) | `fr-nfr.md` FR-15, FR-16, UC-07 · `fr-nfr-quotation.md` QTN-FR-11, QTN-FR-33, Q-TB-04, Q-QTN-23 · ERD `ms_peserta.nik`, `quotation_peserta.id_peserta` |
| SV | **Survey Peserta**: tab **Survey** di halaman peserta program (Manage Peserta); satu link survey per program, dibuka/ditutup admin, diisi peserta Deal tanpa login (pilih nama, sekali isi); rekap jawaban dan Export Excel. **Pertanyaan masih contoh**, daftar final menyusul (`input/Invoice & Pengajuan/2026-11-11-link-survey-peserta/`) | `fr-nfr-manage-peserta.md` SRV-FR-01 – SRV-FR-06, UC-69, UC-70, Q-SRV-01 – Q-SRV-05 · ERD halaman *ERD Survey Peserta* |

## Dampak ke modul yang dokumennya tetap as is

Dicatat di sini, belum ditulis ulang, karena paket **Invoice - Pengajuan** dipisah dari paket **Master - Quotation**.
Q-TB-04 terjawab 11-11-2026: peserta Deal disimpan ke Master Peserta (QTN-FR-33).

| Modul (dokumen as is) | Yang terdampak di to be |
|---|---|
| Master Program (`docs/fr-nfr-master-program.md`) | Pilihan dan tampilan Paket Produk kembali menyertakan **Varian** (mis. "judul · Varian · Sertifikat"), seperti mockup `ui-mockup/produk-to-be/mod-program.js`. Tidak ada aturan baru. |
| Invoice (`docs/fr-nfr-invoice.md`) | Peserta Deal dibaca dari `quotation_peserta` (data isian), bukan dari `ms_peserta`. IVC-FR-04 "Kepada … peserta pertama untuk Personal" menjadi **Kepada** quotation (`quotation.kepada_nama`). **Invoice dibuat dari Sales Order berstatus Siap invoice**, bukan langsung dari quotation Deal: tab Uninvoiced dan kolom No. Sales Order (`fr-nfr-sales-order.md` SO-FR-08, SO-FR-09). Atas permintaan user, ini sudah diterapkan di mockup to be (`mod-invoice.js`); dokumen Invoice belum ditulis ulang. |
| Manage Peserta (`docs/fr-nfr-manage-peserta.md`) | MP-FR-05 / MP-NFR-02: kolom **Kode** peserta tidak ada lagi untuk peserta quotation, dan "perbaikan data lewat Master Peserta" tidak berlaku. Perbaikan data peserta perlu tempat baru (usulan: edit di tab Data Peserta quotation) — belum diputuskan. |
| Activity Log (`docs/fr-nfr-activity-log.md`) | Sumber log baru: modul Quotation sub **Data Peserta** (isian link tanpa login, unduh), **Sales Order** (Tambah, Cetak, Siap Invoice), **Surat Kerja Trainer** (Tambah, Cetak). |

## Pertanyaan terbuka to be

Q-TB-01 – Q-TB-07 di `fr-nfr-quotation.md` bagian 7; Q-SO-01 – Q-SO-05 dan Q-SKT-01 – Q-SKT-03 di `fr-nfr-sales-order.md` bagian 7; Q-38 di `fr-nfr.md`. Untuk Master Data tidak ada pertanyaan baru: Varian kembali
persis seperti sebelum 29-10-2026.

## Penomoran

Nomor baru di dokumen to be tidak memakai ulang kode as is: UC-56 – UC-68, QTN-FR-22 – QTN-FR-33,
QTN-NFR-08 – QTN-NFR-10, Q-TB-01 – Q-TB-07, FR-67 – FR-68, Q-38, Q-39, PJ-FR-29, Q-PJ-25, SO-FR-01 – SO-FR-14, SO-NFR-01 – SO-NFR-06, Q-SO-01 – Q-SO-05, Q-SKT-01 – Q-SKT-03, UC-69 – UC-70, SRV-FR-01 – SRV-FR-06, MP-NFR-04, Q-SRV-01 – Q-SRV-05. Bila as is menambah nomor sebelum to be disetujui, nomor to be yang
bentrok diganti saat penggabungan.

## Bila to be disetujui

Pindahkan isi file di sini ke pasangannya di `docs/` (dengan backup ke `input/backup/` seperti biasa), catat di
riwayat perubahan masing-masing, lalu tulis ulang dokumen Invoice dan Manage Peserta sesuai tabel dampak di atas.
