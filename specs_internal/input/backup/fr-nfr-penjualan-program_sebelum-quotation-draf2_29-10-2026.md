# Kebutuhan Fungsional & Non-Fungsional — Modul Penjualan Program Labmania

- **Versi:** draf 10 — 28-10-2026
- **Sumber:** `input/Master - Buat Program/2026-10-23-requirements-3-modul/` (*Penjualan Program - Labmania.xlsx*: sheet *Requirements Quotation*, *Field Quotation*), `input/Master - Buat Program/2026-10-23-konfirmasi-3-modul/`, `input/Master - Buat Program/2026-10-23-arah-penjualan-program/`, `input/Master - Buat Program/2026-10-23-revisi-rate-harga-simulasi/`, `input/Master - Buat Program/2026-10-23-simulasi-bep/`, `input/Master - Buat Program/2026-10-23-tanpa-diskon/`, `input/Master - Buat Program/2026-10-25-jalur-public-b2b/`, `input/Master - Buat Program/2026-10-25-quotation-so-persiapan-invoice/`, `input/Master - Buat Program/2026-10-25-surat-penawaran-rencana-aktual/`, `input/Master - Buat Program/2026-10-28-input-simulasi-company-b2b/`, `input/Master - Buat Program/2026-10-28-konfirmasi-simulasi-company-b2b/`, `input/Master - Buat Program/2026-10-28-jawaban-pertanyaan-program/`, `input/Master - Buat Program/2026-10-28-quotation-company-personal/`, `input/Master - Buat Program/2026-10-23-layout-program-public/` (layout)
- **Status:** DRAF — pertanyaan terbuka di bagian 7
- **Modul terkait:** hulu Master Program → `docs/fr-nfr-master-program.md`; Master Data → `docs/fr-nfr.md` (Master Rate); hilir **Quotation** untuk Public **dan** B2B → `docs/fr-nfr-quotation.md` `[QCP-1]`; nasib **Sales Order** belum jelas (Q-QTN-03)
- **Use case kanonis:** `docs/workbook.xlsx` sheet *UC Non-BPMN*
- **ERD:** `docs/diagrams/erd.drawio` halaman *ERD Penjualan Program*
- **Mockup:** `ui-mockup/produk/index.html` → modul Penjualan Program (layout form mengikuti `input/labmania.html`)

Penomoran memakai awalan **PJ-**. Kode UC satu urutan untuk seluruh workbook; kode yang dihapus tidak dipakai ulang.

| Penanda | Arti |
|---|---|
| `[QT-n]` | *Penjualan Program - Labmania.xlsx* sheet *Requirements Quotation* bagian 1 No n |
| `[QT-FIELD]` | sheet *Field Quotation* |
| `[USR-3M]` | penjelasan user, `input/Master - Buat Program/2026-10-23-requirements-3-modul/` |
| `[K3M-n]` | konfirmasi, `input/Master - Buat Program/2026-10-23-konfirmasi-3-modul/` — butir n |
| `[ARH-n]` | arah modul, `input/Master - Buat Program/2026-10-23-arah-penjualan-program/` — butir n |
| `[REV-n]` | revisi, `input/Master - Buat Program/2026-10-23-revisi-rate-harga-simulasi/` — butir n |
| `[SIMX]` | tampilan simulasi, `input/Master - Buat Program/2026-10-23-simulasi-hasil-html/` (`input/simulasi_hasil.html`) |
| `[TDK-1]` | Penjualan Program tanpa diskon, `input/Master - Buat Program/2026-10-23-tanpa-diskon/` |
| `[BEP-1]` | permintaan data BEP, `input/Master - Buat Program/2026-10-23-simulasi-bep/` |
| `[JLR-n]` | jalur Public vs B2B, `input/Master - Buat Program/2026-10-25-jalur-public-b2b/` — pesan n |
| `[INV-1]` | Quotation & SO = persiapan invoice, `input/Master - Buat Program/2026-10-25-quotation-so-persiapan-invoice/` |
| `[SPA-n]` | surat penawaran & Rencana vs Aktual, `input/Master - Buat Program/2026-10-25-surat-penawaran-rencana-aktual/` — butir n |
| `[ISC-n]` | input simulasi ganda & Company B2B, `input/Master - Buat Program/2026-10-28-input-simulasi-company-b2b/` — butir n |
| `[KSC-n]` | konfirmasi (1) input simulasi ganda, (2) Company wajib B2B, `input/Master - Buat Program/2026-10-28-konfirmasi-simulasi-company-b2b/` |
| `[JPP-n]` | jawaban 15 pertanyaan, `input/Master - Buat Program/2026-10-28-jawaban-pertanyaan-program/` — butir n |
| `[BTS-1]` | hanya biaya terpakai yang tampil, `input/Master - Buat Program/2026-10-28-biaya-terpakai-saja/` |
| `[QCP-1]` | Public & B2B masuk Quotation (ke Company atau Personal), `input/Master - Buat Program/2026-10-28-quotation-company-personal/` |
| `[STD]` | standar proyek di `AGENTS.md` |
| `[USULAN]` | usulan analis, **belum** dinyatakan klien |

---

## 1. Ringkasan Kebutuhan

**Penjualan Program** = rencana **satu acara** dari satu Master Program. Pengguna **menarik Master
Program berstatus Final**, memilih jenis **Public** atau **B2B**, mengisi tanggal dan lokasi acara,
lalu **semua angka boleh disesuaikan** tanpa mengubah master `[QT-1]`, `[QT-5]`, `[USR-3M]`.

**Penjualan Program adalah tahap menuju finalisasi** `[INV-1]`: peserta, harga, dan costing dirancang
dan disesuaikan di sini sampai Final. **Quotation dan Sales Order adalah persiapan invoice**; saat
masuk ke sana, jumlah peserta **sudah fix** dan tidak ada lagi target atau simulasi `[INV-1]`.
Penjualan Program **Final** dapat **dicetak sebagai surat penawaran**; Final adalah tanda verifikasi di
sistem dan pemicu ke Sales Order `[SPA-1]`. Selisih peserta rencana vs fix **diterima** dan dipantau lewat
**laporan Rencana vs Aktual** `[SPA-2]`.

> **Pembaruan 28-10-2026** `[QCP-1]`: Public **dan** B2B Final kini sama-sama masuk ke **Quotation** (`docs/fr-nfr-quotation.md`);
> B2B ke Company penjualannya, Public ke Company atau Personal. Diagram dan tabel di bawah (B2B → Sales Order) adalah
> rancangan lama — berlaku hanya bila Sales Order dipertahankan (Q-QTN-03).

**Form dan simulasi sama untuk Public dan B2B** `[JLR-1]`. Jenis hanya menentukan **jalur setelah
Final** `[JLR-3]`:

```
Master Program (Final) ──tarik──► Penjualan Program (Draft → Final) ──┬─► Public: Quotation   — 1 : banyak (ditunda) ─┐
                                   menuju finalisasi                    │                                               ├─► Invoice
                                   form & simulasi SAMA                 │   (peserta sudah fix, persiapan invoice)     │
                                   jenis = penanda jalur                └─► B2B:    Sales Order — 1 : 1     (ditunda) ─┘
```

| | **Public → Quotation** | **B2B → Sales Order** |
|---|---|---|
| Relasi ke Penjualan Program | Satu Penjualan Program → **banyak** quotation `[JLR-2]` | Satu Penjualan Program → **satu** SO `[JLR-2]` |
| Customer | Calon peserta, **tidak terikat company** `[JLR-2]` | **Satu Company** dari Master Company, **dipilih di Penjualan Program** dan diteruskan ke Sales Order `[ISC-2]`, `[QT-4]` |
| Harga | Per peserta; diskon per peserta (ditunda) `[ARH-5]` | **Detail harga per peserta** `[JLR-2]`, `[JLR-3]`; harga paket = akumulasi per peserta `[K3M-3]` |

Yang **tidak lagi** ada di Penjualan Program (pindah ke Sales Order, ditunda): skema harga all-in,
harga paket, jumlah peserta minimal 10 `[JLR-2]`. **Company** B2B kembali dipilih di Penjualan Program
(PJ-FR-22) `[ISC-2]`; satu-satunya field yang membedakan form B2B dari Public. Mandays tidak dipakai `[JLR-1]`.

Menu: modul **Penjualan Program**, sub modul **Penjualan Program** (sub modul kedua, setelah Master Program;
menu: Daftar Penjualan Program) `input/Master - Buat Program/2026-10-28-gabung-menu-penjualan-program/`. Laporan **Rencana vs Aktual** (PJ-FR-21) menggantikan konsep As Is vs Aktual `[SPA-2]`.

### 1.1 Cakupan

| Termasuk | Tidak termasuk (ditunda / modul lain) |
|---|---|
| Penjualan Program Public & B2B dari Master Program Final, satu form | Modul **Quotation** (Public): calon peserta, diskon per peserta, Deal/Batal per peserta, surat quotation, realisasi peserta fix `[ARH-5]` |
| Tanggal & lokasi acara; costing per penjualan (salin & sesuaikan) | Modul **Sales Order** (B2B): detail harga per peserta, harga paket, aturan jumlah peserta `[ARH-4]`, `[JLR-2]` |
| Simulasi per peserta (sama dengan Master Program) | Mandays `[ARH-1]`, `[JLR-1]`; diskon `[TDK-1]` |
| Status Draft → Final, atau Batal; cetak surat penawaran | Approval `[QT-16]` |
| Laporan Rencana vs Aktual (data aktual menunggu Quotation / SO) | |

### 1.2 Istilah

| Istilah | Arti |
|---|---|
| Penjualan Program | Rencana satu acara dari satu Master Program, berjenis Public atau B2B, dengan Nomor Penjualan dan status. |
| Jenis | Public atau B2B. Hanya menentukan jalur setelah Final (Quotation / Sales Order); tidak mengubah form. |
| Titik impas (BEP) | Jumlah peserta minimal agar tidak rugi, dan harga minimal per peserta pada jumlah peserta yang direncanakan. |

---

## 2. Aktor

| Aktor | Peran | Sumber |
|---|---|---|
| *Belum ditetapkan* (kemungkinan Sales / Marketing) | Menyusun, memfinalkan, dan membatalkan Penjualan Program | Q-PJ-02 |
| Quotation (modul, ditunda) | Menerima Penjualan Program Public berstatus Final | `[ARH-4]` |
| Sales Order (modul, ditunda) | Menerima Penjualan Program B2B berstatus Final | `[ARH-4]`, `[JLR-2]` |

---

## 3. Tabel Use Case

| Kode | Nama Use Case | User | Modul | Sub Modul | Input | Deskripsi | Output | Entity/Objek Terkait | FR |
|---|---|---|---|---|---|---|---|---|---|
| UC-22 | Menyusun Penjualan Program | Manajemen | Penjualan Program | Penjualan | Master Program Final, jenis (Public / B2B), tanggal & lokasi acara, durasi, target peserta, harga jual / peserta, target profit, costing | Aktor memilih Master Program Final dan jenis Public atau B2B. Sistem menyalin durasi, target peserta, harga jual standar, target profit, dan costing dari Master Program. Aktor mengisi tanggal & lokasi, menyesuaikan angka dan item biaya (semua nominal dapat diedit, termasuk biaya lain). Form dan simulasi sama untuk kedua jenis; simulasi dihitung ulang setiap angka berubah. Aktor menyimpan sebagai Draft. | Penjualan Program Draft dengan Nomor Penjualan | Penjualan Program, Biaya Penjualan Program, Master Program, Rate Fixed Cost, Rate Variable Cost, Satuan | PJ-FR-01 – PJ-FR-10 |
| UC-29 | Memfinalkan Penjualan Program | Manajemen | Penjualan Program | Penjualan | Penjualan Program berstatus Draft | Aktor menekan "Finalkan". Sistem menampilkan popup ringkasan (jenis, durasi, target peserta, harga jual / peserta, pendapatan, HPP, profit, titik impas) dan peringatan bila profit minus. Setelah dikonfirmasi status menjadi Final dan terkunci; Public siap masuk ke Quotation, B2B ke Sales Order (modul berikutnya). | Penjualan Program Final, terkunci | Penjualan Program | PJ-FR-14 – PJ-FR-16 |
| UC-30 | Mencetak Surat Penawaran | Manajemen | Penjualan Program | Penjualan | Penjualan Program berstatus Final | Aktor membuka Penjualan Program Final dan menekan "Cetak Surat Penawaran". Sistem menampilkan surat penawaran dari angka Final untuk dicetak. | Surat penawaran | Penjualan Program | PJ-FR-20 |
| UC-31 | Melihat Laporan Rencana vs Aktual | Manajemen | Penjualan Program | Laporan | Penjualan Program Final, peserta fix dari Quotation / SO | Aktor membuka laporan dan memilih penjualan / periode. Sistem menampilkan rencana, aktual, dan selisih (peserta, pendapatan, HPP, profit). | Laporan Rencana vs Aktual | Penjualan Program, Biaya Penjualan Program | PJ-FR-21 |
| UC-26 | Membatalkan Penjualan Program | Manajemen | Penjualan Program | Penjualan | Penjualan Program berstatus Draft | Aktor memilih Batalkan dan mengonfirmasi. Status menjadi Batal dan tidak dapat diubah. | Penjualan Program berstatus Batal | Penjualan Program | PJ-FR-14, PJ-FR-17 |

> Semua UC belum lolos uji admission no. 1 (aktor bernama) — Q-PJ-02.
> Dihapus dan tidak dipakai ulang: UC-24, UC-25, UC-27 (draf 3); UC-28 (draf 4, `[REV-3]`); **UC-23** Menyusun Penjualan Program B2B (draf 8 — digabung ke UC-22 karena form sama `[JLR-1]`).

---

## 4. Kebutuhan Fungsional (FR)

### 4.1 Penjualan Program

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-01 | **Daftar Penjualan Program**: No, Nomor, Jenis (Public/B2B; nama Company untuk B2B), Master Program (judul + nomor), Pelaksanaan (tanggal & lokasi), Target peserta, Harga jual / peserta, Status, Action (menu gear). Dapat disaring per jenis dan status. | `[QT-1]` + `[USULAN]` (kolom daftar) |
| PJ-FR-02 | Penjualan Program dibuat dengan memilih **Master Program berstatus Final**. Master Program Draft/Batal tidak muncul. | `[QT-1]`, `[QT-FIELD]` |
| PJ-FR-03 | Pengguna memilih **jenis penjualan** (Public atau B2B) **saat membuat** Penjualan Program. Jenis hanya menentukan jalur setelah Final (PJ-FR-16); **form, field, dan simulasi sama** untuk kedua jenis. Jenis tidak dapat diubah setelah disimpan. | `[QT-2]`, `[JLR-1]`, `[JLR-3]` + `[USULAN]` (jenis terkunci) |
| PJ-FR-04 | Saat Master Program dipilih, sistem **menyalin** durasi, target peserta, harga jual standar / peserta, target profit, dan semua item costing (termasuk biaya lain). | `[QT-1]`, `[QT-5]`, `[JLR-2]` |
| PJ-FR-05 | Semua angka hasil salinan **dapat diedit**: target peserta, harga jual / peserta, target profit, nominal semua item biaya, menambah/menghapus item dari Master Rate, dan menambah biaya lain. Perubahan hanya berlaku di Penjualan Program itu dan **tidak mengubah Master Program**. **Durasi** tidak diedit manual — dihitung dari tanggal (PJ-FR-06) `[JPP-6]`. Hanya item **terpakai** yang tampil; item bernilai 0 dibuang saat disimpan (MP-FR-07) `[BTS-1]`. | `[QT-5]`, `[USR-3M]`, `[K3M-2]`, `[REV-1]`, `[JLR-2]` |
| PJ-FR-06 | Penjualan Program memuat **tanggal mulai** dan **tanggal selesai** (keduanya wajib) dan **lokasi acara** (teks bebas). **Durasi (hari) dihitung otomatis** = tanggal selesai − tanggal mulai + 1, tidak diisi manual; sebelum tanggal lengkap, durasi sementara = durasi Master Program. Tanggal selesai tidak boleh sebelum tanggal mulai. | `[USR-3M]`, `[JPP-6]` + `[USULAN]` (rumus inklusif, tanggal wajib) |
| PJ-FR-07 | **Nomor Penjualan** otomatis saat pertama disimpan, **dibedakan untuk Public dan B2B** `[JPP-7]`. Format sementara: Public `PJP-YYMM-NNN`, B2B `PJB-YYMM-NNN`, nomor urut ulang tiap bulan per jenis (mis. PJB-2610-001). Format final: Q-PJ-08. | `[JPP-7]` + `[USULAN]` (format) |
| PJ-FR-08 | Costing memakai model dan layout yang sama dengan Master Program: Fixed Cost per Event/Day, Variable Cost per Peserta/Event dan Peserta/Day, biaya lain. Biaya trainer dihitung per event (1 event = 1 trainer). | `[QT-5]`, `fr-nfr.md` FR-25 |
| PJ-FR-09 | Input harga (Public dan B2B): **Harga jual / peserta**, **Target peserta**, **Target profit** (opsional). Tidak ada skema all-in, harga paket, atau diskon di sini. Calon peserta (Public) dan detail harga per peserta (B2B) diisi di modul berikutnya; Company B2B di PJ-FR-22. Input simulasi (durasi, target peserta, harga jual / peserta, target profit) tampil **di bagian 1 dan di bagian 4 (Simulasi Hasil)**, terikat ke **satu nilai** — cukup diisi sekali di salah satu tempat, tempat lain ikut berubah. | `[QT-6]`, `[ARH-5]`, `[TDK-1]`, `[JLR-2]`, `[ISC-1]`, `[KSC-1]` |
| PJ-FR-10 | **Simulasi** sama dengan Master Program (MP-FR-10, rumus `fr-nfr-master-program.md` bagian 8), untuk Public dan B2B: (1) **Titik impas (BEP)** — BEP jumlah peserta dan BEP harga jual; (2) **Hasil rencana** — Pendapatan, Biaya (HPP), Profit dan margin %, target profit tercapai/kurang, minimal peserta untuk capai target; (3) **Posisi di skala jumlah peserta**; (4) **Rincian** yang dapat dibuka/tutup, ditulis **sederhana dan mudah dipahami** (langkah hitung dalam bahasa sehari-hari, tabel profit pendek). Peringatan: profit minus, BEP di atas target peserta, profit < target profit. | `[QT-9]`, `[REV-4]`, `[BEP-1]`, `[SIMX]`, `[JLR-1]`, `[JLR-3]` |

| PJ-FR-22 | Penjualan Program **B2B** memuat **Company** yang dipilih dari **Master Company** (`customer`, `fr-nfr.md` FR-30 – FR-32) di bagian 1. **Wajib** untuk B2B sebelum disimpan; Public tidak memilih company. Hanya Company **aktif** yang dapat dipilih `[JPP-3]`. Company tidak ditambah dari form ini — dibuat di Master Company, lalu dipilih `[JPP-2]`. Company dapat diubah selama Draft, terkunci setelah Final `[JPP-1]`. Company tampil di daftar (PJ-FR-01), popup Finalkan (PJ-FR-15), dan surat penawaran (PJ-FR-20), serta menjadi Company Sales Order. | `[ISC-2]`, `[KSC-2]`, `[JPP-1]` – `[JPP-3]` + `[USULAN]` (isi tampilan) |

> PJ-FR-11 – PJ-FR-13 (Company, skema harga, perhitungan B2B) **dihapus** pada draf 8 `[JLR-2]` dan tidak dipakai ulang; isinya menjadi bahan modul Sales Order (bagian 4.4).

### 4.2 Status

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-14 | Status: **Draft → Final**, atau **Batal** (bagian 6). Penjualan baru tersimpan Draft; selama Draft semua isian dapat diubah. Tidak ada approval. | `[ARH-4]`, `[QT-16]` |
| PJ-FR-15 | Aksi **"Finalkan"** memunculkan popup ringkasan (jenis, durasi, target peserta, harga jual / peserta, pendapatan, HPP, profit, titik impas) dan peringatan bila profit minus. Setelah dikonfirmasi status **Final** dan Penjualan Program **terkunci**. | `[ARH-4]`, `[QT-12]` (pola popup) |
| PJ-FR-16 | Penjualan Program **Final**, **Public maupun B2B, masuk ke modul Quotation** (boleh banyak quotation; B2B ke Company penjualan) `[QCP-1]`; aksi "+ Buat Quotation" di daftar, kolom target menampilkan jumlah peserta Deal. *(Rumusan lama: Public → Quotation, B2B → Sales Order (satu SO).)* Final adalah **tanda verifikasi** dan **pemicu** ke SO `[SPA-1]`; angka Final menjadi dasar dokumen persiapan invoice di kedua modul `[INV-1]`. Kedua modul belum dikerjakan; sampai ada, Final hanya mengunci dan menandai "→ Quotation" / "→ Sales Order". | `[ARH-4]`, `[JLR-2]`, `[INV-1]`, `[SPA-1]` |
| PJ-FR-17 | Hanya Penjualan Program **Draft** yang dapat **dibatalkan** (status Batal, tidak dapat diubah). Penjualan Program **Final tidak dapat dibatalkan** `[JPP-5]`. | `[QT-12]` (pola batal), `[JPP-5]` |

> PJ-FR-18 dan PJ-FR-19 (As Is vs Penjualan, laporan) **dihapus** pada draf 4 `[REV-3]` dan tidak dipakai ulang.

### 4.3 Surat penawaran & laporan

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-20 | Penjualan Program **Final** dapat **dicetak sebagai surat penawaran** (tombol "Cetak Surat Penawaran" di halaman Lihat). Isi minimal: Nomor Penjualan, judul program, tanggal & lokasi, durasi, harga jual / peserta, jumlah peserta, total. Penjualan Draft/Batal tidak dapat dicetak. B2B ditujukan ke **Company** penjualan (nama, PIC, alamat — PJ-FR-22) `[ISC-2]`; Public juga dapat dicetak (**sementara**), ditujukan ke calon peserta `[JPP-4]`. | `[SPA-1]`, `[ISC-2]`, `[JPP-4]` + `[USULAN]` (isi surat) |
| PJ-FR-21 | **Laporan Rencana vs Aktual** per Penjualan Program Final: **Rencana** = angka Final Penjualan Program; **Aktual** = jumlah peserta fix dari Quotation (Public, akumulasi) / SO (B2B). Kolom: peserta, pendapatan, HPP, profit — rencana, aktual, selisih. Harga jual / peserta tidak berubah; selisih peserta hanya memengaruhi pendapatan, biaya variable, dan profit. Data aktual tersedia setelah modul Quotation / SO ada. | `[SPA-2]` + `[USULAN]` (kolom & rumus aktual: Fixed Cost tetap, Variable Cost × peserta aktual) |

### 4.4 Catatan untuk modul berikutnya (ditunda, bukan cakupan saat ini)

| Modul | Yang sudah disampaikan | Sumber |
|---|---|---|
| Quotation (Public & B2B) | **Sudah dikerjakan** → `docs/fr-nfr-quotation.md` `[QCP-1]`. Catatan lama: **Persiapan invoice**, peserta sudah fix `[INV-1]`. Dibagikan ke **banyak** calon peserta dari satu Penjualan Program; tidak terikat company. Dari sumber lama: diskon per peserta, surat quotation; **Deal/Batal per peserta masih ada** `[JPP-15]`. Harga jual / peserta tidak berubah; selisih peserta hanya memengaruhi profit `[SPA-2]`. | `[JLR-2]`, `[ARH-5]`, `[INV-1]`, `[SPA-2]` |
| Sales Order (B2B) | **Status belum jelas** setelah B2B masuk Quotation (Q-QTN-03). Catatan lama: **Persiapan invoice**, peserta sudah fix `[INV-1]`. **Satu** SO per Penjualan Program; terikat **satu Company** — diambil dari Penjualan Program (PJ-FR-22) `[ISC-2]`; memuat **detail harga per peserta** (harga per peserta × jumlah peserta = harga paket). **Minimal 10 peserta wajib** `[JPP-13]`. Status (usulan sementara `[JPP-14]`): Draft → Final → Invoice, atau Batal dari Draft. Dipicu oleh Penjualan Program Final `[SPA-1]`. Perbedaan peserta dengan rencana tidak masalah karena company yang mendanai `[SPA-2]`. | `[JLR-2]`, `[JLR-3]`, `[K3M-3]`, `[INV-1]`, `[SPA-n]` |

---

## 5. Kebutuhan Non-Fungsional (NFR)

| ID | Kategori | Kebutuhan | Kriteria uji | Lingkup | Sumber |
|---|---|---|---|---|---|
| PJ-NFR-01 | Audit | Simpan, ubah costing, finalkan, dan batal dicatat `write_log()` modul "Penjualan Program"; ubah costing dengan `detail_changes` per item. | Ubah 1 harga item + durasi → 1 log dengan 2 perubahan | UC-22, UC-26, UC-29 | `[STD]` |
| PJ-NFR-02 | Integritas data | Mengedit Penjualan Program tidak pernah mengubah Master Program (costing disalin ke tabel penjualan). | Ubah harga item di penjualan → `master_program_biaya` tetap | PJ-FR-05 | `[QT-5]` |
| PJ-NFR-03 | Integritas data | Penjualan Program Final/Batal tidak dapat diubah di server. | Request ubah ke penjualan Final ditolak (403/422) | PJ-FR-15 – PJ-FR-17 | `[ARH-4]` |
| PJ-NFR-04 | Integritas data | Simpan header + biaya dalam satu transaksi; finalkan memeriksa status di dalam transaksi. | Dua klik Finalkan bersamaan → satu log | UC-22, UC-29 | `[USULAN]` |
| PJ-NFR-05 | Akurasi | Simulasi memakai **satu fungsi hitung** yang sama dengan Master Program (klien dan server). Uang DECIMAL(15,2), tampil rupiah tanpa desimal; titik impas peserta dibulatkan ke atas; "tidak tercapai" bila harga per peserta ≤ biaya variable / peserta. | Angka sama dengan Master Program untuk input yang sama | PJ-FR-10 | `[JLR-1]` + `[USULAN]` |
| PJ-NFR-06 | Validasi | Master Program dan jenis wajib; durasi dan target peserta bilangan bulat > 0; harga jual / peserta wajib; tanggal selesai ≥ tanggal mulai. | Jenis kosong ditolak; tanggal selesai < mulai ditolak | PJ-FR-03, PJ-FR-06, PJ-FR-09 | `[USULAN]` |
| PJ-NFR-07 | Keamanan | Menu & aksi (buat, ubah, finalkan, batal) mengikuti permission aplikasi. | Tanpa hak finalkan → tombol tidak tampil, request ditolak | Semua UC | `[USULAN]` |
| PJ-NFR-09 | Integritas data | Surat penawaran dan laporan Rencana vs Aktual hanya membaca angka Penjualan Program Final, tidak mengubahnya. | Cetak / buka laporan → data penjualan tetap | PJ-FR-20, PJ-FR-21 | `[USULAN]` |
| PJ-NFR-08 | Platform | CI3 HMVC, MySQL, Bootstrap + jQuery; nama tabel/kolom = ERD. Tidak memakai `tr_penawaran` (modul quotation lama aplikasi). | Code review | Semua | `[STD]` |

---

## 6. Status Penjualan Program

| Dari | Aksi | Ke | Jenis | Syarat / efek | Sumber |
|---|---|---|---|---|---|
| — | Simpan penjualan baru | Draft | Semua | Nomor dibuat | `[USULAN]` |
| Draft | Finalkan (konfirmasi popup) | Final | Public | Terkunci; dapat dibuatkan Quotation (Company / Personal) | `[ARH-4]`, `[QCP-1]` |
| Draft | Finalkan (konfirmasi popup) | Final | B2B | Terkunci; dapat dibuatkan Quotation ke Company penjualan | `[QCP-1]` (sebelumnya Sales Order `[ARH-4]`) |
| Draft | Batalkan | Batal | Semua | — | `[USULAN]` |

Final dan Batal tidak kembali ke Draft. Final tidak dapat dibatalkan `[JPP-5]`.

---

## 7. Pertanyaan Terbuka

| ID | Pertanyaan | Mempengaruhi |
|---|---|---|
| Q-PJ-02 | Aktor saat ini **Manajemen** `[JPP-11]`. Pengguna lain (user) yang ikut menyusun / memfinalkan / membatalkan — menyusul. | UC-22, UC-26, UC-29 – UC-31 |
| Q-PJ-08 | Format **final** Nomor Penjualan (sementara `PJP-YYMM-NNN` / `PJB-YYMM-NNN`, PJ-FR-07) `[JPP-7]`. | PJ-FR-07 |
| Q-PJ-16 | Status **final** Sales Order (sementara Draft → Final → Invoice, Batal dari Draft) `[JPP-14]`. | Modul Sales Order (ditunda) |

**Sudah terjawab** (23-10-2026): item Fix / mode harga tidak ada lagi, semua nominal dapat diedit (`[K3M-2]`, `[REV-1]`); diskon (Public & B2B) ditunda (`[TDK-1]`); setelah Final → Quotation / Sales Order (`[ARH-4]`). **(25-10-2026, `[JLR-n]`)**: Penjualan Program sama untuk kedua jenis; jenis dipilih saat membuat Penjualan Program; Mandays tidak dipakai (Q-PJ-04 gugur); dokumen B2B = Sales Order. `[SPA-n]`: penawaran = cetak Penjualan Program Final (Q-PJ-16 lama); selisih peserta diterima dan dipantau lewat laporan Rencana vs Aktual (Q-PJ-14, Q-PJ-17 lama). **(28-10-2026, `[ISC-n]`)**: Company B2B dipilih di Penjualan Program dari Master Company (Q-PJ-18 terjawab → PJ-FR-22). **(28-10-2026, `[JPP-n]`)**: nama menu sesuai (Q-PJ-01); aktor Manajemen (Q-PJ-02, sebagian); durasi otomatis dari tanggal (Q-PJ-03); Public juga dicetak, sementara (Q-PJ-19); hanya Draft yang dibatalkan (Q-PJ-12); SO minimal 10 peserta wajib (Q-PJ-15); Quotation masih Deal/Batal per peserta (Q-PJ-17). **Ditunda ke modul Quotation** `[ARH-5]`: diskon per peserta, Deal/Batal per peserta, surat quotation (Q-PJ-11), realisasi peserta fix.

---

## 8. Riwayat Perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| draf 1 | 23-10-2026 | Penyusunan awal dari *Penjualan Program - Labmania.xlsx* sebagai modul Quotation (Public multi-peserta, B2B Deal). |
| draf 2 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-konfirmasi-3-modul/`: item Fix dapat diedit; B2B variable per peserta, jumlah peserta default/min 10; kunci costing setelah peserta Deal; diskon per peserta Rp / %. |
| draf 3 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-arah-penjualan-program/` — ditulis ulang sebagai **Penjualan Program** dengan status Draft → Final / Batal; Final Public → Quotation, B2B → Sales Order (ditunda); fitur quotation ditunda; diskon B2B; As Is vs Penjualan; UC-24, UC-25, UC-27 dihapus; UC-29 baru. |
| draf 4 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-revisi-rate-harga-simulasi/`: B2B punya **skema harga** per peserta (default) atau all-in — harga paket tidak wajib (PJ-FR-04, PJ-FR-12, PJ-FR-13, PJ-NFR-06); As Is vs Penjualan dan laporannya dihapus (PJ-FR-18, PJ-FR-19, UC-28 dihapus; Q-PJ-14 baru); simulasi ditata ulang agar mudah dibaca (PJ-FR-10); tidak ada mode harga (PJ-FR-05). |
| draf 5 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-simulasi-bep/`: simulasi ditambah **Data BEP** — perhitungan BEP, tabel profit menurut jumlah peserta, dan batas peserta untuk B2B all-in (PJ-FR-10). |
| draf 6 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-simulasi-hasil-html/`: tampilan simulasi mengikuti `input/simulasi_hasil.html` (PJ-FR-10); B2B all-in memakai harga paket minimal & maksimal peserta pada kotak BEP dan skala. |
| draf 6a | 23-10-2026 | Istilah "sumbangan per peserta" dihapus atas permintaan user (chat 23-10-2026); BEP peserta ditulis langsung sebagai Fixed Cost ÷ (harga − biaya variable per peserta). |
| draf 7 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-tanpa-diskon/`: **diskon dihapus untuk sementara** (Public & B2B) — PJ-FR-12, PJ-FR-13, PJ-FR-15, PJ-NFR-06, UC-23, UC-29; Q-PJ-13 gugur. |
| draf 8 | 25-10-2026 | `input/Master - Buat Program/2026-10-25-jalur-public-b2b/`: **form & simulasi sama** untuk Public dan B2B; jenis hanya penanda jalur (PJ-FR-03). Company, skema all-in, harga paket, jumlah peserta min 10 keluar ke **Sales Order** (ditunda) — PJ-FR-11 – PJ-FR-13 dihapus, bagian 4.3 baru. UC-23 digabung ke UC-22. Target peserta disalin dari Master Program (default 10). Rincian simulasi disederhanakan (PJ-FR-10). Q-PJ-04 gugur; Q-PJ-15, Q-PJ-16 baru. |
| draf 8a | 25-10-2026 | `input/Master - Buat Program/2026-10-25-quotation-so-persiapan-invoice/`: Penjualan Program = tahap menuju finalisasi; Quotation & SO = persiapan invoice, peserta sudah fix (bagian 1, PJ-FR-16, bagian 4.3). Usulan status SO Dikirim → Deal dan usulan Aktual di Q-PJ-14 dicabut; Q-PJ-16 ditulis ulang; Q-PJ-17 baru. |
| draf 8b | 25-10-2026 | `input/Master - Buat Program/2026-10-25-surat-penawaran-rencana-aktual/`: Penjualan Program Final dapat dicetak sebagai **surat penawaran** dan memicu SO (PJ-FR-16, PJ-FR-20, UC-30 baru); **laporan Rencana vs Aktual** (PJ-FR-21, UC-31 baru) menggantikan As Is vs Aktual; Q-PJ-14 terjawab; Q-PJ-16, Q-PJ-17 ditulis ulang; Q-PJ-18, Q-PJ-19 baru; bagian 4.3 → 4.4. |
| draf 8c | 28-10-2026 | `input/Master - Buat Program/2026-10-28-input-simulasi-company-b2b/`: input simulasi tampil di bagian 1 **dan** bagian 4, tersinkron (PJ-FR-09); **Company B2B dipilih di Penjualan Program** dari Master Company (PJ-FR-22 baru; PJ-FR-01, PJ-FR-20, bagian 1, 4.4); Q-PJ-18 terjawab. ERD: `penjualan_program.id_customer` kembali. |
| draf 8d | 28-10-2026 | `input/Master - Buat Program/2026-10-28-konfirmasi-simulasi-company-b2b/`: input simulasi ganda (PJ-FR-09) dan Company wajib untuk B2B (PJ-FR-22) dikonfirmasi klien. |
| draf 9 | 28-10-2026 | `input/Master - Buat Program/2026-10-28-jawaban-pertanyaan-program/`: aktor Manajemen (UC); durasi otomatis dari tanggal, tanggal wajib (PJ-FR-05, PJ-FR-06); nomor dibedakan Public/B2B, format sementara (PJ-FR-07); hanya Draft dibatalkan (PJ-FR-17, bagian 6); surat Public sementara boleh (PJ-FR-20); Company aktif, tidak ditambah dari form (PJ-FR-22); SO min 10 wajib, status SO sementara, Quotation Deal/Batal per peserta (4.4). Q-PJ-01, -03, -12, -15, -17, -19 terjawab; Q-PJ-02, -08, -16 ditulis ulang. |
| draf 9a | 28-10-2026 | `input/Master - Buat Program/2026-10-28-gabung-menu-penjualan-program/`: Master Program & Penjualan Program satu modul menu **Penjualan Program**; sub modul Penjualan → Penjualan Program (bagian 1). |
| draf 9b | 28-10-2026 | `input/Master - Buat Program/2026-10-28-biaya-terpakai-saja/`: hanya item biaya terpakai yang tampil; item bernilai 0 dibuang saat disimpan (PJ-FR-05). |
| draf 10 | 28-10-2026 | `input/Master - Buat Program/2026-10-28-quotation-company-personal/`: Public **dan** B2B Final masuk ke **Quotation** (PJ-FR-16, bagian 1, 4.4, 6); modul Quotation di `docs/fr-nfr-quotation.md`. Sales Order menunggu Q-QTN-03. |
