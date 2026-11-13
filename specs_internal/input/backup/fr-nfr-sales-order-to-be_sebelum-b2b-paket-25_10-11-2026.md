# Kebutuhan Fungsional & Non-Fungsional — Sales Order (modul Quotation) Labmania · TO BE

- **Versi:** **TO BE** draf 1j — 10-11-2026 (dokumen baru; tidak ada pasangan as is)
- **Sumber:** `input/Master - Buat Program/2026-11-07-sales-order/` `[SO-n]`; jawaban `input/Master - Buat Program/2026-11-07-jawaban-sales-order/` `[JSO-n]`; Surat Kerja Trainer `input/Master - Buat Program/2026-11-07-surat-kerja-trainer/` `[SKT-n]`
- **Status:** DRAF TO BE — menunggu konfirmasi Labmania (pertanyaan di bagian 7)
- **Menggantikan (khusus to be):** `[B2B-3]` "Sales Order tidak ada" (`input/Master - Buat Program/2026-10-30-b2b-quotation-allin-min10/`); invoice langsung dari quotation Deal (`docs/fr-nfr-invoice.md` IVC-FR-02, `[IPQ-1]`)
- **Modul terkait:** hulu Quotation → `docs-to-be/fr-nfr-quotation.md` (QTN-FR-11 Deal membuat SO; link Konfirmasi Kehadiran QTN-FR-23 – QTN-FR-28); hilir Invoice → `docs/fr-nfr-invoice.md` (dampak di bagian 4.5); Activity Log → `docs/fr-nfr-activity-log.md`
- **Mockup:** `ui-mockup/produk-to-be/Labmania.html` → Quotation › Sales Order (`mod-sales-order.js`)
- **ERD:** `docs-to-be/diagrams/erd.drawio` halaman *ERD Quotation* — tabel `sales_order`
- **BPMN:** `docs-to-be/diagrams/bpmn-to-be.drawio` halaman *Level 1 — Quotation*

Penomoran memakai awalan **SO-**.

| Penanda | Arti |
|---|---|
| `[SO-n]` | `input/Master - Buat Program/2026-11-07-sales-order/` — butir catatan penafsiran n: (1) menu Sales Order di Quotation, (2) 1 quotation Deal = 1 SO, (3) listing per program, Lihat SO → halaman baru daftar SO, (4) aksi View / Print Out / Siap dibuatkan invoice, (5) invoice dari SO yang sudah siap |
| `[SKT-n]` | `input/Master - Buat Program/2026-11-07-surat-kerja-trainer/` — butir n: (1) tujuan SO = surat konfirmasi dari LabMania ke customer, (2) Surat Kerja Trainer di modul SO sebagai tab, (3) trainer wajib dipilih di Penjualan Program |
| `[JSO-n]` | `input/Master - Buat Program/2026-11-07-jawaban-sales-order/` — butir n: (1) nomor SO sendiri, bukan pola nomor quotation, format menyusul, (2) permission menyusul, (3) SO tidak dapat dibatalkan |
| `[USULAN]` | usulan analis, **belum** dinyatakan klien |

---

## 1. Ringkasan

Setiap quotation yang ditandai **Deal** otomatis membentuk **satu Sales Order (SO)** `[SO-2]`. SO adalah **surat konfirmasi pesanan dari LabMania ke customer** `[SKT-1]`
sekaligus dasar **invoice**: invoice tidak lagi dibuat langsung dari quotation, melainkan dari SO yang
sudah ditandai **Siap dibuatkan invoice** `[SO-5]`.

```
Quotation Terkirim ──Deal──► Quotation Deal ──otomatis──► Sales Order (Belum siap invoice)
                                                                │ View · Print Out SO
                                                                └─Siap dibuatkan invoice──► Siap invoice ──Invoice dibuat──► Invoiced
```

| | Quotation | Sales Order |
|---|---|---|
| Isi | Penawaran harga dari **Kuota peserta**; link Konfirmasi Kehadiran | Pesanan dari **peserta Deal** quotation itu; nilai = Nilai Deal |
| Dokumen | PDF Penawaran + S&K | PDF Sales Order |
| Dasar invoice | Tidak lagi (to be) | **Ya**, setelah Siap invoice |

## 2. Aktor

| Aktor | Peran | Sumber |
|---|---|---|
| *Menyusul* (permission belum ditetapkan) | Melihat SO, mencetak SO, menandai Siap dibuatkan invoice | `[SO-4]`, `[JSO-2]` (Q-SO-03) |
| Finance | Membuat invoice dari SO berstatus Siap invoice (modul Invoice) | `[SO-5]` + `docs/fr-nfr-invoice.md` |

## 3. Tabel Use Case

Nomor baru, tidak memakai ulang kode lain.

| Kode | Use case | Aktor | Prasyarat | Alur singkat | Hasil |
|---|---|---|---|---|---|
| UC-60 | Membentuk Sales Order | (otomatis saat UC-35 Menandai Deal) | Quotation Terkirim ditandai Deal | Sistem membuat SO dengan nomor baru, terhubung ke quotation dan Penjualan Program | SO berstatus Belum siap invoice |
| UC-61 | Melihat Sales Order per program | *Menyusul* `[JSO-2]` | Ada SO | Buka menu Sales Order, pilih program, klik **Lihat SO**, lalu **View** salah satu SO | Detail quotation, peserta Deal, dan surat konfirmasi tampil |
| UC-62 | Mencetak Sales Order | *Menyusul* `[JSO-2]` | Ada SO | **Print Out SO**, pratinjau A4, Cetak / Simpan PDF | PDF SO tercetak; tercatat di Activity Log |
| UC-63 | Menandai Siap dibuatkan invoice | *Menyusul* `[JSO-2]` | SO Belum siap invoice | **Siap dibuatkan invoice**, konfirmasi | SO Siap invoice; muncul di Invoicing tab Uninvoiced |
| UC-64 | Mencetak Surat Kerja Trainer | *Menyusul* `[JSO-2]` | Program punya Sales Order dan trainer | Menu Sales Order → tab **Surat Kerja Trainer** → **View** / **Print Out Surat Kerja** | PDF surat kerja untuk trainer program itu; tercatat di Activity Log |

## 4. Kebutuhan Fungsional (FR)

### 4.1 Pembentukan

| ID | Kebutuhan | Sumber |
|---|---|---|
| SO-FR-01 | Saat quotation ditandai **Deal** (QTN-FR-11), sistem membuat **satu** Sales Order untuk quotation itu, dalam transaksi yang sama dengan Deal. Satu quotation tidak dapat punya lebih dari satu SO. Field: **Nomor SO** otomatis dengan **penomoran sendiri**, tidak memakai pola nomor quotation; **format menyusul** dari Labmania `[JSO-1]` (Q-SO-01; mockup memakai nomor sementara `SO-nnn`); **Tanggal SO** = tanggal Deal `[USULAN]` (Q-SO-02); referensi quotation dan Penjualan Program; status awal **Belum siap invoice**. Isi pesanan (peserta Deal, harga, diskon, PPN, biaya lain) dibaca dari quotation Deal, tidak diinput ulang. | `[SO-2]` + `[USULAN]` (nomor, tanggal) |

### 4.2 Daftar

| ID | Kebutuhan | Sumber |
|---|---|---|
| SO-FR-02 | Menu **Quotation › Sales Order**. Index **per Penjualan Program** yang punya SO: kolom No, **Penjualan Program** (nomor, jenis Public/B2B, judul paket, Varian, tanggal, lokasi), **Sales Order** (jumlah SO + rincian per status), **Peserta deal** (jumlah dari semua SO program), **Nilai SO** (jumlah total SO), **Action**: tombol **Lihat SO**. Filter jenis program (Public & B2B / Public / B2B), **Tahun** dan **Bulan** (tanggal mulai acara program `input/Master - Buat Program/2026-11-07-filter-tahun-bulan/`), dan pencarian (nomor program, judul, nomor SO, nomor quotation, customer). Bila belum ada SO: pesan bahwa SO terbentuk saat quotation ditandai Deal. | `[SO-1]`, `[SO-3]` + `[USULAN]` (kolom) |
| SO-FR-03 | Badge jumlah di menu Sales Order = jumlah SO. | `[USULAN]` |
| SO-FR-04 | **Lihat SO** membuka **halaman baru** daftar SO satu program: judul "Sales Order: [No. Program]" dengan info program (jenis, judul, Varian, tanggal, lokasi, Company untuk B2B), tombol **← Semua program**. Tabel: No, **Nomor SO** (tanggal; klik = View), **No. Quotation**, **Kepada** (tag Company/Personal, nama, u.p.), **Peserta deal** (jumlah + dua nama pertama), **Nilai**, **Status** (+ siapa dan kapan ditandai siap), **Action** (menu gear: **View**, **Print Out SO**, **Siap dibuatkan invoice** bila status Belum siap invoice). | `[SO-3]`, `[SO-4]` + `[USULAN]` (kolom) |

### 4.3 View & Print Out

| ID | Kebutuhan | Sumber |
|---|---|---|
| SO-FR-05 | **View** membuka halaman detail SO `[SO-4]`: panel status (badge, langkah berikutnya, tombol **Siap dibuatkan invoice** bila belum siap, **Print Out SO**, **Buka quotation**); (1) **Data Sales Order** — Nomor SO, Tanggal, Status, nomor quotation asal, Sales, Penjualan Program; (2) **Ditujukan Kepada** — tujuan, Kepada, u.p., Alamat, Perlakuan PPN (Company); (3) **Program** — paket, Varian, Sertifikat, jadwal, waktu, lokasi, skema harga (B2B); (4) **Harga** dari peserta Deal — subtotal, diskon, setelah diskon, PPN, biaya lain-lain, **Total Sales Order**; (5) **Dokumen Deal** `input/Master - Buat Program/2026-11-09-dokumen-deal/` — daftar dokumen dari customer yang diunggah saat quotation ditandai Deal (PO, SPK, surat penunjukan, bukti bayar, dll.): No, Nama file, Ukuran, Diunggah (user + waktu), dan Keterangan dokumen bila diisi (QTN-FR-11); (6) **Peserta Deal & Surat Konfirmasi** — tabel peserta Deal (nama, jabatan, instansi, kontak, masuk lewat Link + waktu) dengan **Lihat** / **Unduh** **surat konfirmasi bertanda tangan yang diunggah** peserta lewat link quotation (QTN-FR-25, QTN-FR-28) `input/Master - Buat Program/2026-11-10-surat-konfirmasi-ttd-basah/`, dan tombol **Confirmation Letter (semua peserta Deal)**; berlaku untuk Public dan B2B `input/Master - Buat Program/2026-11-09-b2b-tetap-confirmation-letter/`; (7) **Invoice** — nomor invoice yang sudah dibuat, atau keterangan bahwa invoice menunggu status Siap invoice. | `[SO-4]` + `[USULAN]` (susunan) |
| SO-FR-06 | **Print Out SO** menghasilkan **PDF Sales Order** dari quotation itu `[SO-4]`, pratinjau A4 lalu **Cetak / Simpan PDF**. Tata letak usulan (Q-SO-04), mengikuti Penawaran: kop PT LabMania Indonesia Jaya; judul **SALES ORDER**; kotak **Kepada** (nama, alamat, u.p.); kotak data (**No. Sales Order**, Tanggal, **No. Penawaran**, No. Program); tabel No Catalog, Item (judul + Varian), Deskripsi (durasi, tanggal, waktu, lokasi), Kts. (peserta Deal), Harga, Diskon, Total Harga; **daftar Peserta Deal** (No, Nama, Instansi); ringkasan Sub Total, Diskon, PPN, Biaya Lain-lain, Total; tanda tangan Sales dan "Disetujui oleh Pelanggan"; kaki halaman kode dokumen (menyusul). Setiap cetak dicatat di Activity Log. | `[SO-4]` + `[USULAN]` (tata letak) |

### 4.4 Siap dibuatkan invoice

| ID | Kebutuhan | Sumber |
|---|---|---|
| SO-FR-07 | Aksi **Siap dibuatkan invoice** (gear di daftar SO dan tombol di View), hanya untuk SO **Belum siap invoice**. Konfirmasi menampilkan nomor SO, Kepada, jumlah peserta Deal, dan nilai; setelah disimpan status → **Siap invoice**, dicatat siapa dan kapan. Belum ada aksi membatalkan status siap `[USULAN]`. Siapa yang boleh menandai: permission menyusul `[JSO-2]` (Q-SO-03). | `[SO-4]`, `[SO-5]` + `[USULAN]` |
| SO-FR-10 | **Sales Order tidak dapat dibatalkan** setelah terbentuk: tidak ada aksi Batal/Hapus di daftar maupun View. Karena itu quotation yang sudah Deal (sudah punya SO) juga tidak dapat dibatalkan. | `[JSO-3]` |

### 4.5 Dampak ke Invoice (dokumen Invoice tetap as is sampai to be disetujui)

| ID | Kebutuhan | Sumber |
|---|---|---|
| SO-FR-08 | **Invoice dibuat dari Sales Order** `[SO-5]`: tab **Uninvoiced** di Invoicing hanya memuat SO berstatus **Siap invoice** yang peserta Deal-nya belum semua ditagih (menggantikan "quotation Deal" di IVC-FR-02). Add Invoicing ditolak bila SO belum siap. Kolom **No. Sales Order** ditambahkan di tab Uninvoiced dan Invoiced (No. Quotation tetap tampil). Aturan cara tagih (1 invoice / per peserta B2B), nilai, PPN, dan jurnal tetap seperti IVC-FR-06 – IVC-FR-11, dihitung dari quotation Deal yang menjadi isi SO. | `[SO-5]` + `[USULAN]` (kolom, aturan lain tetap) |
| SO-FR-09 | Status SO **Invoiced** diturunkan dari invoice: bila semua peserta Deal sudah ditagih, SO tampil Invoiced (tidak disimpan sebagai status terpisah) `[USULAN]`. Di detail quotation Deal, tombol **Buat Invoice** diganti **Lihat Sales Order**. | `[USULAN]` |

### 4.6 Surat Kerja Trainer (tab di modul Sales Order)

| ID | Kebutuhan | Sumber |
|---|---|---|
| SO-FR-11 | Halaman menu Sales Order punya dua tab `[SKT-2]`: **Sales Order** (SO-FR-02) dan **Surat Kerja Trainer**, masing-masing dengan jumlah baris di label tab. Filter jenis program, **Tahun**, **Bulan**, dan pencarian berlaku per tab `input/Master - Buat Program/2026-11-07-filter-tahun-bulan/`. | `[SKT-2]` |
| SO-FR-12 | **Satu Surat Kerja Trainer per Penjualan Program** (1 event = 1 trainer, `[K3M-1]`), ditujukan ke **trainer yang dipilih di Penjualan Program** (wajib, `docs-to-be/fr-nfr-penjualan-program.md` PJ-FR-23 `[SKT-3]`). **1 Penjualan Program = 1 Surat Kerja Trainer**, bukan per quotation/SO `input/Master - Buat Program/2026-11-07-jawaban-surat-kerja-trainer/`. Surat **dibuat otomatis** saat program mendapat **Sales Order pertama**, yaitu saat program pasti jalan `[USULAN]` (Q-SKT-02). Nomor surat sendiri, **format menyusul** `input/Master - Buat Program/2026-11-07-jawaban-surat-kerja-trainer/`; mockup memakai nomor sementara `SKT-nnn`. Surat **tidak dapat dibatalkan**, mengikuti SO `[USULAN]`. | `[SKT-2]`, `[SKT-3]` + `[USULAN]` |
| SO-FR-13 | **Tab Surat Kerja Trainer**: kolom No, **Nomor Surat** (tanggal; klik = View), **Penjualan Program** (nomor, jenis, judul, Varian, jadwal, lokasi), **Trainer** (nama + bidang keahlian dari Master Trainer), **Peserta deal** (jumlah dari semua SO program), **Action** (gear: **View**, **Print Out Surat Kerja**). **View**: halaman detail berisi Data Surat (nomor, tanggal, program), **Trainer** (nama, bidang keahlian, telepon, email, alamat), **Program yang ditugaskan** (paket, Varian, sertifikat, jadwal, waktu, durasi, lokasi, company untuk B2B, jumlah peserta Deal dari n SO), tombol Print Out dan Lihat Sales Order program. | `[SKT-2]` + `[USULAN]` (kolom, isi View) |
| SO-FR-14 | **Print Out Surat Kerja** menghasilkan PDF A4 portrait (pratinjau lalu Cetak / Simpan PDF); setiap cetak dicatat di Activity Log. **Format = Surat Tugas Labmania** `FR/LMI-TR-05/01/Ed.Rev-1.0` (`input/FR LMI TR 05-01 Surat Tugas OK (1).doc`, `input/Master - Buat Program/2026-11-10-format-cetak-skt-hadir-name-table/`), menggantikan tata letak usulan; judul tercetak **SURAT TUGAS**, nama menu dan tab tetap Surat Kerja Trainer. Isi: kop PT. Labmania Indonesia Jaya (alamat, website, email, Customer Care); **No.** = nomor surat; "Menindaklanjuti kegiatan [Varian] \"[judul program]\", saya yang bertanda tangan di bawah ini: Nama : … Jabatan : **Operational Manager**"; "Dengan ini menugaskan kepada :" tabel **No., Nama, NIK, Jabatan** (10 baris; baris 1 = trainer program, Jabatan "Trainer", sisa baris kosong); "sebagaimana ditugaskan sebagai **Trainer** dalam acara [Varian] \"[judul]\" bertempat di [lokasi] yang dilaksanakan pada : **Hari/ Tanggal** (hari dan tanggal jadwal pelaksanaan program) dan **Waktu** (waktu program)"; kalimat penutup format; tanda tangan kanan "Bekasi, [tanggal cetak]", **Operational Manager**, "(……)"; kaki `FR/LMI-TR-05/01/Ed.Rev-1.0` · "1 dari 1" · "Berlaku 01/08/2023". **Hari/Tanggal pelaksanaan = jadwal program**; hanya tanggal tanda tangan = tanggal cetak `input/Master - Buat Program/2026-11-10-jawaban-format-cetak/`. NIK trainer dan nama Operational Manager **sementara kosong** (titik-titik, diisi tangan) `input/Master - Buat Program/2026-11-10-jawaban-format-cetak/` (Q-SKT-04). Honor dan rundown tidak dicetak `input/Master - Buat Program/2026-11-07-jawaban-surat-kerja-trainer/`. | `input/Master - Buat Program/2026-11-10-format-cetak-skt-hadir-name-table/`, `input/Master - Buat Program/2026-11-07-jawaban-surat-kerja-trainer/` + `[USULAN]` (isian dari data sistem) |
| SO-FR-15 | **Tanggal pada print out** `input/Master - Buat Program/2026-11-09-tanggal-print-out/`: tanggal yang tercetak di PDF **Sales Order** (baris Tanggal dan tanggal di atas tanda tangan Sales) dan **Surat Kerja Trainer** (tanggal di atas tanda tangan Operational Manager, "Bekasi, ...") = **tanggal dokumen dicetak** (hari ini); ditegaskan lagi `input/Master - Buat Program/2026-11-10-format-cetak-skt-hadir-name-table/`. Tanggal SO dan tanggal surat yang tersimpan tidak berubah dan tetap tampil di daftar dan View (Q-SO-02). | `input/Master - Buat Program/2026-11-09-tanggal-print-out/` |

## 5. Kebutuhan Non-Fungsional (NFR)

| ID | Kategori | Kebutuhan | Sumber |
|---|---|---|---|
| SO-NFR-01 | Integritas data | SO dibuat di server dalam transaksi yang sama dengan aksi Deal; `sales_order.id_quotation` unik. Nomor SO diambil di dalam transaksi yang sama dengan kolom nomor unik, agar tidak ada nomor ganda. | `[USULAN]` |
| SO-NFR-02 | Integritas data | Server menolak Add Invoicing untuk SO yang belum Siap invoice, walau tombolnya dipanggil langsung. | `[SO-5]` + `[USULAN]` |
| SO-NFR-03 | Audit | Pembentukan SO (Tambah), Print Out SO (Cetak), dan Siap dibuatkan invoice (Siap Invoice, dengan nilai lama → baru) dicatat `write_log()` modul "Quotation" sub "Sales Order"; pembentukan dan cetak Surat Kerja Trainer dicatat di sub "Surat Kerja Trainer"; tabel memakai kolom jejak `created_by`, `created_date`, `updated_by`, `updated_date`. | `[STD]` (`AGENTS.md`) |
| SO-NFR-04 | Keamanan | Menu dan aksi mengikuti Role & Permission; hak akses Sales Order **menyusul** dari Labmania `[JSO-2]` (mockup sementara: role contoh Sales diberi View & Manage). | `[USULAN]` |
| SO-NFR-05 | Dokumen | PDF SO dibuat server-side dari template HTML/CSS yang sama dengan pratinjau (A4). | `[USULAN]` |
| SO-NFR-06 | Platform | CI3 HMVC, MySQL; tabel baru `sales_order` sesuai ERD Quotation to be; nama tabel/kolom = ERD. | `[STD]` |

## 6. Status Sales Order

| Dari | Aksi | Ke | Efek |
|---|---|---|---|
| — | Quotation ditandai Deal | Belum siap invoice | Nomor SO dibuat |
| Belum siap invoice | Siap dibuatkan invoice | Siap invoice | Muncul di Invoicing tab Uninvoiced |
| Siap invoice | Invoice dibuat untuk semua peserta Deal | Invoiced (turunan) | Hilang dari tab Uninvoiced |
| Semua status | View, Print Out SO | (tetap) | Cetak tercatat di Activity Log |
| Semua status | Batal | — | **Tidak ada**: Sales Order tidak dapat dibatalkan (SO-FR-10) |

## 7. Pertanyaan Terbuka

| ID | Pertanyaan | Mempengaruhi |
|---|---|---|
| Q-SO-01 | ~~Format nomor SO `LMI/SO/MM/yy-zzz`?~~ **Terjawab sebagian** `[JSO-1]`: nomor SO sendiri, bukan pola nomor quotation. Masih menunggu: **format nomor SO** dari Labmania. | SO-FR-01 |
| Q-SO-02 | Tanggal SO = tanggal Deal (usulan), atau dapat diisi/diubah? | SO-FR-01 |
| Q-SO-03 | **Permission menyusul** `[JSO-2]`. Siapa yang menandai **Siap dibuatkan invoice** (Sales, Manajemen, Admin)? Perlu aksi membatalkan status siap selama invoice belum dibuat? Perlu syarat tertentu (mis. SO sudah ditandatangani customer, surat konfirmasi lengkap)? | SO-FR-07 |
| Q-SO-04 | Apakah ada template/format resmi PDF Sales Order dan kode dokumennya? Tata letak saat ini usulan. | SO-FR-06 |
| Q-SKT-01 | ~~Format nomor Surat Kerja Trainer?~~ **Terjawab** `input/Master - Buat Program/2026-11-07-jawaban-surat-kerja-trainer/`: format nomor **menyusul**. Mockup: nomor sementara `SKT-nnn`. | SO-FR-12 |
| Q-SKT-02 | **Terjawab sebagian** `input/Master - Buat Program/2026-11-07-jawaban-surat-kerja-trainer/`: 1 Penjualan Program = 1 surat; aksi View dan Print Out seperti SO. Masih terbuka: surat dibuat otomatis saat SO pertama (usulan) sudah benar? Bila trainer diganti, apakah surat dibuat ulang? | SO-FR-12 |
| Q-SKT-03 | ~~Perlu honor/rundown? Siapa yang menandatangani?~~ **Terjawab** `input/Master - Buat Program/2026-11-07-jawaban-surat-kerja-trainer/`: tanpa honor dan rundown; ditandatangani atas nama LabMania. | SO-FR-14 |
| Q-SKT-04 | ~~**NIK trainer** dan **nama penanda tangan** di Surat Tugas? Hari/Tanggal pelaksanaan = jadwal program?~~ **Terjawab** `input/Master - Buat Program/2026-11-10-jawaban-format-cetak/`: Hari/Tanggal = tanggal program, tanggal tanda tangan = tanggal print; NIK trainer dan nama Operational Manager **sementara kosong** (diisi tangan). Field NIK di Master Trainer dibahas lagi bila diperlukan. | SO-FR-14 |
| Q-SO-05 | ~~Bila quotation Deal perlu dibatalkan, apa yang terjadi pada SO-nya?~~ **Terjawab** `[JSO-3]`: Sales Order tidak dapat dibatalkan (SO-FR-10). | — |

## 8. Riwayat Perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| **to be draf 1** | 07-11-2026 | Penyusunan awal dari `input/Master - Buat Program/2026-11-07-sales-order/`: menu Sales Order (SO-FR-02 – SO-FR-04), pembentukan otomatis saat Deal (SO-FR-01), View dengan surat konfirmasi dari link (SO-FR-05), Print Out SO (SO-FR-06), Siap dibuatkan invoice (SO-FR-07), invoice dari SO (SO-FR-08, SO-FR-09); UC-60 – UC-63; Q-SO-01 – Q-SO-05; mockup `ui-mockup/produk-to-be/mod-sales-order.js`. |
| **to be draf 1a** | 07-11-2026 | `input/Master - Buat Program/2026-11-07-jawaban-sales-order/`: nomor SO **penomoran sendiri, format menyusul** (SO-FR-01, Q-SO-01 sebagian); **permission menyusul** (aktor, UC-61 – UC-63, SO-FR-07, SO-NFR-04, Q-SO-03); **SO tidak dapat dibatalkan** (SO-FR-10 baru, bagian 6, Q-SO-05 terjawab). |
| **to be draf 1b** | 07-11-2026 | `input/Master - Buat Program/2026-11-07-surat-kerja-trainer/`: tujuan SO = **surat konfirmasi pesanan dari LabMania ke customer** (bagian 1); **Surat Kerja Trainer** sebagai tab di modul Sales Order (bagian 4.6, SO-FR-11 – SO-FR-14, UC-64, Q-SKT-01 – Q-SKT-03); trainer wajib di Penjualan Program (`docs-to-be/fr-nfr-penjualan-program.md`). |
| **to be draf 1c** | 07-11-2026 | `input/Master - Buat Program/2026-11-07-jawaban-surat-kerja-trainer/`: nomor Surat Kerja Trainer format menyusul (Q-SKT-01 terjawab); 1 Penjualan Program = 1 surat, aksi View & Print Out (SO-FR-12, Q-SKT-02 sebagian); tanpa honor & rundown, ttd atas nama LabMania (SO-FR-14, Q-SKT-03 terjawab). |
| **to be draf 1d** | 07-11-2026 | `input/Master - Buat Program/2026-11-07-filter-tahun-bulan/`: filter **Tahun** dan **Bulan** (tanggal mulai acara program) di tab Sales Order dan tab Surat Kerja Trainer (SO-FR-02, SO-FR-11). |
| **to be draf 1e** | 09-11-2026 | `input/Master - Buat Program/2026-11-09-tanggal-print-out/`: tanggal tercetak di PDF Sales Order dan Surat Kerja Trainer = tanggal cetak (SO-FR-15 baru). |
| **to be draf 1f** | 09-11-2026 | `input/Master - Buat Program/2026-11-09-dokumen-deal/`: View SO menampilkan bagian **Dokumen Deal** (dokumen dari customer yang diunggah saat Deal) (SO-FR-05). |
| **to be draf 1g** | 09-11-2026 | `input/Master - Buat Program/2026-11-09-b2b-tetap-confirmation-letter/`: Confirmation Letter di View SO juga untuk B2B (SO-FR-05). |
| **to be draf 1h** | 10-11-2026 | `input/Master - Buat Program/2026-11-10-format-cetak-skt-hadir-name-table/`: Surat Kerja Trainer dicetak dengan **format Surat Tugas** Labmania `FR/LMI-TR-05/01` (SO-FR-14 ditulis ulang, SO-FR-15 disesuaikan); Q-SKT-04 baru (NIK trainer, nama Operational Manager). |
| **to be draf 1i** | 10-11-2026 | `input/Master - Buat Program/2026-11-10-jawaban-format-cetak/`: Hari/Tanggal Surat Tugas = jadwal program dan tanggal tanda tangan = tanggal print (dikonfirmasi); NIK trainer & nama Operational Manager sementara kosong (Q-SKT-04 terjawab). |
| **to be draf 1j** | 10-11-2026 | `input/Master - Buat Program/2026-11-10-surat-konfirmasi-ttd-basah/`: surat konfirmasi di View SO = **surat bertanda tangan yang diunggah peserta**, aksi Lihat / Unduh (SO-FR-05). |
