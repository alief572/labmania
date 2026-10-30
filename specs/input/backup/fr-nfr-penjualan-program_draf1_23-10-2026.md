# Kebutuhan Fungsional & Non-Fungsional — Modul Penjualan Program (Quotation) Labmania

- **Versi:** draf 1 — 23-10-2026
- **Sumber:** `input/2026-10-23-requirements-3-modul/` (*Penjualan Program - Labmania.xlsx*: sheet *Requirements Quotation*, *Field Quotation*), `input/2026-10-23-klarifikasi-program/`, `input/2026-10-23-layout-program-public/` (layout)
- **Menggantikan:** `docs/fr-nfr-program-penjualan.md` draf 1 bagian Public/B2B, status, realisasi (backup: `input/backup/fr-nfr-program-penjualan_draf1_23-10-2026.md`)
- **Status:** DRAF — pertanyaan terbuka di bagian 7
- **Modul terkait:** Master Program → `docs/fr-nfr-master-program.md` (sumber program); Master Data → `docs/fr-nfr.md` (Master Peserta, Master Company, Master Rate)
- **Use case kanonis:** `docs/workbook.xlsx` sheet *UC Non-BPMN*
- **ERD:** `docs/diagrams/erd.drawio` halaman *ERD Penjualan Program*
- **Mockup:** `ui-mockup/produk/index.html` → modul Penjualan Program (layout form mengikuti `input/labmania.html`)

Penomoran memakai awalan **PJ-**. Kode UC satu urutan untuk seluruh workbook.

| Penanda | Arti |
|---|---|
| `[QT-n]` | *Penjualan Program - Labmania.xlsx* sheet *Requirements Quotation* bagian 1 No n |
| `[QT-FIELD]` | sheet *Field Quotation* (peta field Public vs B2B) |
| `[QT-SIPOC]` | sheet *Requirements Quotation* bagian 4 |
| `[USR-3M]` | penjelasan user 23-10-2026, `input/2026-10-23-requirements-3-modul/` |
| `[KPG-n]` | klarifikasi chat 23-10-2026, `input/2026-10-23-klarifikasi-program/` |
| `[STD]` | standar proyek di `AGENTS.md` |
| `[USULAN]` | usulan analis, **belum** dinyatakan klien |

---

## 1. Ringkasan Kebutuhan

**Quotation** = proposal penjualan. Pengguna **menarik Master Program berstatus Final**, lalu memilih
jenis penjualan **Public** atau **B2B**. Atribut produk dan costing awal terisi otomatis dari Master
Program, lalu **semua angka boleh disesuaikan** untuk quotation itu saja tanpa mengubah master
`[QT-1]`, `[QT-5]`, `[USR-3M]`. Tanggal dan lokasi acara diisi di sini `[USR-3M]`.

| | **Quotation Public** | **Quotation B2B** |
|---|---|---|
| Customer | Peserta dari Master Peserta, **bisa lebih dari satu** `[QT-3]` | **Satu** Company dari Master Company `[QT-4]` |
| Harga | Harga jual / peserta, target peserta, target profit (opsional). Harga jual tetap walau peserta kurang/lebih `[QT-6]` | Harga paket all-in, jumlah peserta (informasi), target profit (opsional) `[QT-7]` |
| Diskon | Per peserta `[QT-8]` | Belum dibahas `[QT-8]` |
| Hasil simulasi | Total fixed, total variable, HPP, profit, harga BEP / peserta, jumlah peserta BEP `[QT-9]` | Total biaya, harga paket, pendapatan bersih. BEP tidak dipakai `[QT-9]` |
| Deal / Batal | **Per peserta** `[QT-11]` | **Per quotation** `[QT-11]` |
| Surat | Ke calon peserta `[QT-13]` | Ke company `[QT-13]` |
| Setelah Deal | Peserta Deal = peserta fix, bisa ditagih; realisasi & As Is vs Aktual `[QT-11]`, `[QT-14]` | Kemungkinan Sales Order `[QT-15]` |

SIPOC `[QT-SIPOC]`:

| Supplier | Input | Process | Output | Customer |
|---|---|---|---|---|
| Master Program | Master Program Final (produk, costing, HPP, BEP) | Quotation | Harga jual, diskon, costing per quotation, surat quotation | Calon peserta (Public), Company (B2B) |
| Master Peserta | Data peserta (Public) | | | |
| Master Company | Data company (B2B) | | | |

Menu: modul **Penjualan Program**, sub modul **Quotation** (menu: Daftar Quotation) dan **Laporan**
(menu: As Is vs Aktual). Nama usulan, karena user menyebut "proposal/quotation (bingung namanya)" — Q-PJ-01.

### 1.1 Cakupan

| Termasuk | Tidak termasuk |
|---|---|
| Quotation Public & B2B dari Master Program Final | Menyusun Master Program → `fr-nfr-master-program.md` |
| Costing per quotation (salin & sesuaikan), tanggal & lokasi acara | Sales Order setelah Deal B2B `[QT-15]` |
| Peserta per quotation dengan diskon dan Deal/Batal per peserta | Format surat quotation (belum ditentukan) `[QT-13]` |
| Deal/Batal per quotation (B2B) | Approval — saat ini tidak ada `[QT-16]` |
| Realisasi & laporan As Is vs Aktual (Public) | Penagihan / invoice |

### 1.2 Istilah

| Istilah | Arti |
|---|---|
| Quotation | Satu proposal penjualan atas satu Master Program, berjenis Public atau B2B, dengan Nomor Quotation. |
| Peserta quotation | Satu peserta (dari Master Peserta) di Quotation Public, dengan diskon dan status Draft / Deal / Batal sendiri. |
| Peserta fix | Peserta quotation berstatus Deal. Hanya yang Deal yang bisa ditagih `[QT-11]`. |
| As Is | Angka quotation (target peserta, harga jual, HPP). |
| Aktual | Angka dari peserta fix. |

---

## 2. Aktor

| Aktor | Peran | Sumber |
|---|---|---|
| *Belum ditetapkan* (kemungkinan Sales / Marketing) | Menyusun quotation, mengonfirmasi Deal/Batal, mencetak surat | Q-PJ-02 |
| Calon peserta (Public), Company (B2B) | Penerima surat quotation; tidak memakai sistem | `[QT-SIPOC]` |

---

## 3. Tabel Use Case

| Kode | Nama Use Case | User | Modul | Sub Modul | Input | Deskripsi | Output | Entity/Objek Terkait | FR |
|---|---|---|---|---|---|---|---|---|---|
| UC-22 | Menyusun Quotation Public | *TBD (Q-PJ-02)* | Penjualan Program | Quotation | Master Program Final, jenis Public, tanggal & lokasi acara, durasi, target peserta, harga jual / peserta, target profit, costing, peserta (multi) + diskon | Aktor memilih Master Program Final dan jenis Public. Sistem menyalin durasi, target, harga jual standar, dan costing dari Master Program. Aktor mengisi tanggal & lokasi, menyesuaikan angka dan item biaya (termasuk biaya manual), menambah peserta dari Master Peserta beserta diskonnya. Simulasi dihitung ulang setiap angka berubah. Aktor menyimpan sebagai Draft. | Quotation Public Draft dengan Nomor Quotation | Quotation, Biaya Quotation, Peserta Quotation, Master Program, Peserta, Rate Fixed Cost, Rate Variable Cost, Satuan | PJ-FR-01 – PJ-FR-12 |
| UC-23 | Menyusun Quotation B2B | *TBD (Q-PJ-02)* | Penjualan Program | Quotation | Master Program Final, jenis B2B, Company, tanggal & lokasi acara, durasi, harga paket, jumlah peserta, target profit, costing | Sama dengan UC-22, tetapi customer satu Company dari Master Company, harga berupa harga paket all-in, dan hasil berupa total biaya serta pendapatan bersih. | Quotation B2B Draft dengan Nomor Quotation | Quotation, Biaya Quotation, Master Program, Company, Rate Fixed Cost, Rate Variable Cost, Satuan | PJ-FR-01 – PJ-FR-08, PJ-FR-13, PJ-FR-14 |
| UC-24 | Mengonfirmasi Deal / Batal Peserta | *TBD (Q-PJ-02)* | Penjualan Program | Quotation | Peserta quotation berstatus Draft (Quotation Public) | Aktor memilih Deal pada satu peserta; sistem menampilkan popup ringkasan (peserta, harga, diskon, total) dan peringatan bila hasil minus. Setelah dikonfirmasi peserta menjadi Deal (peserta fix, bisa ditagih) dan barisnya terkunci. Bila peserta tidak jadi, aktor memilih Batal. | Status peserta Deal atau Batal; realisasi diperbarui | Peserta Quotation, Quotation | PJ-FR-15 – PJ-FR-17, PJ-FR-20 |
| UC-25 | Mengonfirmasi Deal Quotation B2B | *TBD (Q-PJ-02)* | Penjualan Program | Quotation | Quotation B2B berstatus Draft | Aktor menekan Deal; sistem menampilkan popup ringkasan (company, durasi, harga paket, total biaya, pendapatan bersih) dan peringatan bila pendapatan bersih minus. Setelah dikonfirmasi status menjadi Deal dan quotation terkunci. | Quotation B2B Deal, terkunci | Quotation, Company | PJ-FR-15, PJ-FR-18 |
| UC-26 | Membatalkan Quotation | *TBD (Q-PJ-02)* | Penjualan Program | Quotation | Quotation Draft (B2B atau Public tanpa peserta Deal) | Aktor memilih Batalkan dan mengonfirmasi. Status menjadi Batal. Perubahan setelah Deal dilakukan dengan membatalkan lalu membuat quotation baru. | Quotation berstatus Batal | Quotation | PJ-FR-15, PJ-FR-19 |
| UC-27 | Mencetak Surat Quotation | *TBD (Q-PJ-02)* | Penjualan Program | Quotation | Quotation tersimpan | **Ditunda** — format surat belum ditentukan. Sistem menghasilkan surat penawaran: Public ke calon peserta, B2B ke company. | Surat quotation | Quotation, Peserta, Company | PJ-FR-21 |
| UC-28 | Melihat Laporan As Is vs Aktual | *TBD (Q-PJ-02)* | Penjualan Program | Laporan | Filter periode / Master Program | Sistem menampilkan per Quotation Public: peserta, pendapatan, HPP, dan profit versi As Is berdampingan dengan versi Aktual (dari peserta Deal), beserta selisih. | Laporan As Is vs Aktual | Quotation, Peserta Quotation, Biaya Quotation | PJ-FR-20, PJ-FR-22 |

> Semua UC belum lolos uji admission no. 1 (aktor bernama) — Q-PJ-02. UC-27 menunggu format surat.

---

## 4. Kebutuhan Fungsional (FR)

### 4.1 Quotation — umum

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-01 | **Daftar Quotation**: No, Nomor, Jenis (Public/B2B), Master Program (judul + nomor), Customer (jumlah peserta Deal/total untuk Public; nama company untuk B2B), Tanggal & lokasi, Harga (per peserta / paket), Status, Action (menu gear). Dapat disaring per jenis dan status. | `[QT-1]` + `[USULAN]` (kolom daftar) |
| PJ-FR-02 | Quotation dibuat dengan memilih **Master Program berstatus Final** (`fr-nfr-master-program.md` MP-FR-13). Master Program Draft/Batal tidak muncul. | `[QT-1]`, `[QT-FIELD]` |
| PJ-FR-03 | Pengguna memilih **jenis penjualan**: Public atau B2B. Setelah dipilih, form menyesuaikan customer, komponen harga, dan dokumen. Jenis tidak dapat diubah setelah quotation disimpan. | `[QT-2]` + `[USULAN]` (jenis terkunci) |
| PJ-FR-04 | Saat Master Program dipilih, sistem **menyalin** durasi, target peserta, harga jual standar, target profit, dan semua item costing (termasuk biaya manual). Untuk B2B, harga jual standar tidak disalin karena harga berupa paket. | `[QT-1]`, `[QT-5]`, `[QT-FIELD]` + `[USULAN]` (B2B) |
| PJ-FR-05 | Semua angka hasil salinan **dapat diedit per quotation**: durasi, target peserta / jumlah peserta, harga jual / harga paket, target profit, nominal item biaya, menambah/menghapus item dari Master Rate, dan menambah biaya manual. Perubahan hanya berlaku di quotation itu dan **tidak mengubah Master Program**. Item bermode **Fix** tetap terkunci (Q-PJ-05). | `[QT-5]`, `[QT-6]`, `[QT-7]`, `[QT-FIELD]`, `[USR-3M]` |
| PJ-FR-06 | Quotation memuat **tanggal pelaksanaan** (mulai, selesai) dan **lokasi acara**. | `[USR-3M]` + `[USULAN]` (nama & format field, Q-PJ-03) |
| PJ-FR-07 | Quotation mendapat **Nomor Quotation** otomatis saat pertama disimpan (format Q-PJ-08). | `[USULAN]` |
| PJ-FR-08 | Costing memakai model yang sama dengan Master Program (Fixed Cost per Event/Day, Variable Cost per Peserta/Event dan Peserta/Day, Mode Harga, biaya manual) dan layout yang sama. | `[QT-5]`, `[QT-FIELD]` |

### 4.2 Quotation Public

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-09 | Customer Public = satu atau lebih **peserta dari Master Peserta**. Satu peserta tidak boleh tercatat dua kali di quotation yang sama. | `[QT-3]` + `[USULAN]` (tanpa duplikat) |
| PJ-FR-10 | Input harga: **Harga jual / peserta**, **Target peserta**, **Target profit** (opsional). Harga jual / peserta tidak berubah walau peserta fix kurang atau lebih dari target. | `[QT-6]` |
| PJ-FR-11 | **Diskon per peserta** (nominal Rp, Q-PJ-06); harga net peserta = harga jual − diskon, tidak boleh negatif. | `[QT-8]` + `[USULAN]` (nominal) |
| PJ-FR-12 | **Simulasi** (atas target): Total Fixed Cost, Total Variable Cost, HPP, Profit (Pendapatan − HPP), Harga BEP / peserta, Jumlah peserta BEP — rumus sama dengan `fr-nfr-master-program.md` bagian 8. Peringatan: profit minus, BEP di atas target, profit < target profit. | `[QT-9]`, `[QT-FIELD]` |

### 4.3 Quotation B2B

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-13 | Customer B2B = **satu Company** dari Master Company (wajib). Input harga: **Harga paket all-in** (sudah termasuk biaya pendukung), **Jumlah peserta** (informasi), **Target profit** (opsional). Diskon B2B belum ditentukan. | `[QT-4]`, `[QT-7]`, `[QT-8]` |
| PJ-FR-14 | **Hasil B2B**: Total biaya = Total Fixed Cost + Total Variable Cost; **Pendapatan bersih = Harga paket − Total biaya**; peringatan bila minus atau di bawah target profit. BEP tidak dipakai. Variable Cost B2B dihitung × jumlah peserta (Q-PJ-04). Mandays sebagai output B2B belum ada rumusnya (Q-PJ-04). | `[QT-9]`, `[QT-FIELD]`, `[KPG-1]` |

### 4.4 Status, Deal, Batal

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-15 | Status: **Draft → Deal**, atau **Batal** (bagian 6). Quotation belum tentu deal; yang Deal baru bisa ditagih dan tercatat sebagai peserta fix. Tidak ada approval. | `[QT-10]`, `[QT-11]`, `[QT-16]` |
| PJ-FR-16 | **Public: Deal / Batal per peserta.** Aksi Deal pada satu peserta memunculkan popup ringkasan (peserta, harga, diskon, total) dan peringatan bila hasil minus; setelah dikonfirmasi peserta menjadi Deal dan barisnya terkunci. Batal per peserta untuk peserta yang tidak jadi. | `[QT-11]`, `[QT-12]` |
| PJ-FR-17 | Setelah ada **satu peserta Deal**, harga jual / peserta, target, dan costing Quotation Public **terkunci** (As Is tetap); peserta baru masih boleh ditambah dan dikonfirmasi. | `[QT-6]`, `[QT-12]` + `[USULAN]` (Q-PJ-07) |
| PJ-FR-18 | **B2B: Deal per quotation.** Aksi Deal memunculkan popup ringkasan (company, durasi, harga paket, total biaya, pendapatan bersih) dan peringatan bila pendapatan bersih minus. Setelah dikonfirmasi status Deal dan quotation terkunci (hanya bisa dilihat). Langkah sesudah Deal (kemungkinan Sales Order) belum ditentukan. | `[QT-11]`, `[QT-12]`, `[QT-15]` |
| PJ-FR-19 | **Perubahan setelah Deal**: batalkan lalu buat quotation baru. Quotation yang masih Draft dapat dibatalkan (B2B; Public selama belum ada peserta Deal). | `[QT-12]` + `[USULAN]` (syarat Public) |

### 4.5 Realisasi, Surat, Laporan

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-20 | **Realisasi Public**: peserta fix = peserta berstatus Deal. Bila jumlahnya kurang/lebih dari target, harga jual / peserta tetap, **cost tetap**, yang berubah hanya profit. Pendapatan aktual = Σ harga net peserta Deal; Profit aktual = Pendapatan aktual − HPP As Is. | `[QT-14]` (Q-PJ-09) |
| PJ-FR-21 | **Surat quotation**: Public ke calon peserta, B2B ke company. Format surat belum ditentukan; fitur ditunda sampai format ada. | `[QT-13]` |
| PJ-FR-22 | **Laporan As Is vs Aktual** (Public): per quotation — peserta (target vs Deal), pendapatan, HPP, profit, dan selisih. | `[QT-14]` |

---

## 5. Kebutuhan Non-Fungsional (NFR)

| ID | Kategori | Kebutuhan | Kriteria uji | Lingkup | Sumber |
|---|---|---|---|---|---|
| PJ-NFR-01 | Audit | Simpan, ubah costing, tambah/hapus peserta, Deal/Batal (peserta & quotation) dicatat `write_log()` modul "Penjualan Program"; ubah costing dengan `detail_changes` per item. | Deal 1 peserta → 1 log menyebut nomor quotation & nama peserta | UC-22 – UC-26 | `[STD]` |
| PJ-NFR-02 | Integritas data | Mengedit quotation tidak pernah mengubah baris Master Program (costing disalin ke tabel quotation). | Ubah harga item di quotation → `master_program_biaya` tidak berubah | PJ-FR-05 | `[QT-5]` |
| PJ-NFR-03 | Integritas data | Quotation / peserta yang Deal atau Batal tidak dapat diubah di server; bagian yang terkunci (PJ-FR-17) ditolak server. | Request ubah harga setelah ada peserta Deal ditolak | PJ-FR-16 – PJ-FR-19 | `[QT-12]` |
| PJ-NFR-04 | Integritas data | Simpan header + biaya + peserta dalam satu transaksi; konfirmasi Deal memeriksa status saat ini di dalam transaksi. | Dua klik Deal bersamaan → satu log Deal | UC-22 – UC-25 | `[USULAN]` |
| PJ-NFR-05 | Akurasi | Uang DECIMAL(15,2), tampil rupiah tanpa desimal; BEP dibulatkan ke atas; "tidak tercapai" bila harga ≤ biaya variable / peserta. | Sama dengan Master Program untuk angka yang sama | PJ-FR-12, PJ-FR-14 | `[USULAN]` |
| PJ-NFR-06 | Validasi | Master Program, jenis, dan customer (B2B) wajib; durasi > 0; tanggal selesai ≥ tanggal mulai; diskon ≤ harga jual. | Tanggal selesai < mulai ditolak; diskon > harga ditolak | PJ-FR-06, PJ-FR-11, PJ-FR-13 | `[USULAN]` |
| PJ-NFR-07 | Keamanan | Menu & aksi (buat, ubah, Deal, Batal, cetak, laporan) mengikuti permission aplikasi. | Tanpa hak Deal → tombol tidak tampil, request ditolak | Semua UC | `[USULAN]` |
| PJ-NFR-08 | Platform | CI3 HMVC, MySQL, Bootstrap + jQuery; nama tabel/kolom = ERD. Tidak memakai tabel `tr_penawaran` milik modul quotation lama. | Code review | Semua | `[STD]` |

---

## 6. Status

**Quotation (header)**

| Dari | Aksi | Ke | Jenis | Syarat / efek | Sumber |
|---|---|---|---|---|---|
| — | Simpan quotation baru | Draft | Semua | Nomor dibuat | `[QT-10]` |
| Draft | Deal (konfirmasi popup) | Deal | B2B | Terkunci | `[QT-11]`, `[QT-12]` |
| Draft | Batalkan | Batal | B2B; Public bila belum ada peserta Deal | — | `[QT-10]` + `[USULAN]` (syarat Public) |

Quotation Public tidak memakai status Deal di header; Deal ada per peserta. `[QT-11]`

**Peserta quotation (Public)**

| Dari | Aksi | Ke | Syarat / efek | Sumber |
|---|---|---|---|---|
| — | Tambah peserta | Draft | — | `[QT-3]` |
| Draft | Deal (konfirmasi popup) | Deal | Peserta fix, bisa ditagih, baris terkunci; costing quotation terkunci (PJ-FR-17) | `[QT-11]`, `[QT-12]` |
| Draft | Batal | Batal | — | `[QT-11]` |

---

## 7. Pertanyaan Terbuka

| ID | Pertanyaan | Mempengaruhi |
|---|---|---|
| Q-PJ-01 | Nama modul dan menu: "Penjualan Program" / "Quotation" / "Proposal"? | Bagian 1 |
| Q-PJ-02 | Siapa pengguna yang menyusun quotation, mengonfirmasi Deal/Batal, dan mencetak surat? | UC-22 – UC-28 |
| Q-PJ-03 | Tanggal & lokasi acara: cukup tanggal mulai–selesai dan lokasi teks bebas? Apakah durasi dihitung dari tanggal, atau tetap diisi terpisah? | PJ-FR-06 |
| Q-PJ-04 | **B2B**: Variable Cost dihitung × jumlah peserta, atau dilebur ke harga all-in (`[QT-FIELD]` "perlu konfirmasi")? Apa rumus **Mandays** sebagai output costing B2B? | PJ-FR-14 |
| Q-PJ-05 | User menyebut "semua bisa di edit" di quotation. Apakah item bermode **Fix** di Master Rate juga boleh diedit di quotation, atau tetap terkunci? | PJ-FR-05 |
| Q-PJ-06 | **Diskon** per peserta berupa nominal Rp, persen, atau keduanya? Diskon B2B? | PJ-FR-11, PJ-FR-13 |
| Q-PJ-07 | Public: setelah ada peserta Deal, apakah harga & costing quotation harus terkunci (usulan), atau masih boleh diubah? | PJ-FR-17 |
| Q-PJ-08 | Format **Nomor Quotation**; berbeda untuk Public dan B2B? | PJ-FR-07 |
| Q-PJ-09 | Realisasi "cost tetap": HPP aktual = HPP As Is (sesuai teks), atau Variable Cost ikut jumlah peserta fix? | PJ-FR-20 |
| Q-PJ-10 | Apa langkah sesudah Deal B2B — Sales Order? Dan sesudah peserta Public Deal — invoice? | PJ-FR-18 |
| Q-PJ-11 | Format **surat quotation** (Public dan B2B). | PJ-FR-21, UC-27 |

---

## 8. Riwayat Perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| draf 1 | 23-10-2026 | Penyusunan awal dari *Penjualan Program - Labmania.xlsx*. Menggantikan `fr-nfr-program-penjualan.md` (UC-12 – UC-18 dihapus): Public/B2B dipilih di quotation; customer Public multi-peserta dengan diskon & Deal/Batal per peserta; B2B satu company, Deal per quotation; costing disalin dari Master Program dan dapat diedit; tanggal & lokasi diisi di quotation; status Draft → Deal / Batal; surat quotation (ditunda); As Is vs Aktual. |
