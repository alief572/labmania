# Kebutuhan Fungsional & Non-Fungsional — Modul Program Penjualan Labmania

- **Versi:** draf 1 — 23-10-2026
- **Sumber:** `input/2026-10-23-requirements-program/` (*Program - Labmania.xlsx* sheet *Requirements Costing*, *Cost Master*, *Product Costing*), `input/2026-10-23-klarifikasi-program/`
- **Status:** DRAF — aturan B2B dan beberapa transisi status masih menunggu Labmania (bagian 7)
- **Modul terkait:** Master Data → `docs/fr-nfr.md` (Paket Produk, Master Peserta, Master Company, Master Rate)
- **Use case kanonis:** `docs/workbook.xlsx` sheet *UC Non-BPMN* (tabel di bagian 3 adalah salinan baca)
- **ERD:** `docs/diagrams/erd.drawio` halaman *ERD Program Penjualan*
- **Mockup:** `ui-mockup/produk/index.html` (modul Program Penjualan di sidebar). Layout form Program Public mengikuti `input/labmania.html` (form Product Costing) — hanya layout, lihat `input/2026-10-23-layout-program-public/`

Penomoran FR, NFR, dan pertanyaan di dokumen ini memakai awalan **PP-** agar tidak bentrok
dengan `docs/fr-nfr.md`. Kode UC tetap satu urutan untuk seluruh workbook (UC-12 dst.).

| Penanda | Arti |
|---|---|
| `[PRG-n]` | Sheet *Requirements Costing*, bagian 1 (Fix Requirements) baris No n |
| `[PRG-WISH-n]` | Sheet *Requirements Costing*, bagian 3 (Wishlist) baris No n |
| `[PRG-SIPOC]` | Sheet *Requirements Costing*, bagian 4 (SIPOC) |
| `[CM]` | Sheet *Cost Master* |
| `[PCS]` | Sheet *Product Costing* (simulasi 6 skenario dengan rumus) |
| `[REQ-7]`, `[REQ-8]` | *Product - Labmania rev (1).xlsx* sheet *Requirements Product* No 7 / 8 |
| `[KPG-n]` | Klarifikasi 23-10-2026, `input/2026-10-23-klarifikasi-program/` — butir verbatim n |
| `[STD]` | Standar proyek di `AGENTS.md` root repository |
| `[USULAN]` | Usulan analis, **belum** dinyatakan klien — wajib dikonfirmasi sebelum dikodekan |

---

## 1. Ringkasan Kebutuhan

**Program** = Paket Produk yang dipilih dari Product Combine + costing-nya, menjadi satu program
siap jual `[PRG-1]`. Program dibuat dari **satu menu**, lalu berpisah menjadi dua jalur setelah
jenis program dipilih:

| | **Program Public** | **Program B2B** |
|---|---|---|
| Pola jual | Dipromosikan dulu, peserta mendaftar di akhir, harga **per peserta** `[PRG-1]` | Company sudah diketahui dari awal, harga **paket all-in** `[PRG-1]` |
| Customer | Tidak diisi saat program dibuat. Peserta (Master Peserta) didaftarkan setelah promosi lewat quotation `[PRG-2]`, `[REQ-7]` | Company **wajib** dipilih dari Master Company saat program dibuat `[PRG-3]`, `[REQ-8]` |
| Target | Target peserta, harga jual per peserta, target profit (opsional) `[PRG-4]` | Harga paket all-in (termasuk biaya pendukung), jumlah peserta (informasi), target profit (opsional) `[PRG-5]` |
| Biaya | Fixed Cost (semua item tampil sekaligus) + Variable Cost (ditambah per item) `[PRG-18]`, `[PRG-26]` | Hanya item Fixed Cost, ditambah satu per satu, plus biaya manual di luar master `[PRG-25]`, `[PRG-26]` |
| Hasil costing | Total Fixed, Total Variable, HPP, Profit, Harga BEP/Peserta, Jumlah Peserta BEP `[PRG-22]` | Total biaya, pendapatan bersih, **Mandays** `[PRG-15]`, `[KPG-1]` — rumus menunggu konfirmasi `[KPG-5]` |
| Status | Draft → **Final** → Selesai, atau Batal `[PRG-6]` | Draft → **Deal** → Selesai, atau Batal `[PRG-6]` |
| Setelah dikunci | Muncul di modul **Quotation** `[PRG-8]` | Tidak lewat Quotation; langkah berikutnya kemungkinan **Sales Order** `[PRG-9]`, `[PRG-14]` |

SIPOC `[PRG-SIPOC]`:

| Supplier | Input | Process | Output | Customer |
|---|---|---|---|---|
| Product Combined (Master Data) | Produk (Paket Produk) | Product Costing | Target peserta, Target profit, HPP, BEP Peserta, BEP Harga Jual, Total Cost (Fixed & Variable) | *(kosong di sumber — kemungkinan Quotation / Sales Order)* |
| Master Cost (Master Rate) | Item Fixed Cost, Item Variable Cost, Cost Fixed, Cost Variable | | | |

Menu modul Program Penjualan:

| Modul | Sub Modul | Menu | Berlaku untuk |
|---|---|---|---|
| Program Penjualan | Program | Daftar Program (buat Program Public / B2B) | Semua |
| Program Penjualan | Laporan | As Is vs Aktual | Public |

Nama sub modul dan menu adalah usulan analis (Q-PP-09).

**Pain point:** bagian 2 sheet *Requirements Costing* masih kosong.

### 1.1 Cakupan

| Termasuk | Tidak termasuk (dokumen ini) |
|---|---|
| Membuat program Public dan B2B dari Paket Produk aktif | Data master (Paket Produk, Peserta, Company, Master Rate) → `fr-nfr.md` |
| Costing & simulasi Public; costing B2B | Modul Quotation (pendaftaran peserta Public) |
| Status program dan konfirmasi Final / Deal | Sales Order setelah Deal |
| Pembatalan dan penyelesaian program | Dokumen penawaran / proposal ke company — saat ini tidak ada `[PRG-17]` |
| Laporan As Is vs Aktual (Public) | Approval sebelum Final — saat ini tidak ada `[PRG-19]` |

### 1.2 Istilah

| Istilah | Arti di dokumen ini |
|---|---|
| Program | Satu Paket Produk + costing + target, dengan jenis Public atau B2B dan status. Diberi **Nomor Program**. |
| Biaya Program | Satu baris item biaya di costing program: diambil dari Master Rate (dengan harga yang bisa disesuaikan sesuai Mode Harga) atau, khusus B2B, biaya manual. |
| Durasi (Hari) | Jumlah hari pelaksanaan program. Pengali item bersatuan Day dan Peserta/Day `[PCS]`. |
| As Is | Angka saat program dikonfirmasi Final (target). |
| Aktual | Angka realisasi setelah program berjalan. |
| Mandays | Hasil (output) costing B2B, bukan satuan item biaya `[KPG-1]`. Rumusnya belum ada `[PRG-27]`. |

---

## 2. Aktor

| Aktor | Peran | Sumber |
|---|---|---|
| *Belum ditetapkan* | Membuat program, mengisi costing, mengonfirmasi Final/Deal, membatalkan, menyelesaikan — tidak disebut di sumber | Q-PP-01 |
| Quotation (modul) | Menerima program Public berstatus Final dan mendaftarkan peserta | `[PRG-8]`, `[REQ-7]` |
| Sales Order (modul) | Kemungkinan langkah berikutnya program B2B setelah Deal | `[PRG-14]` |

---

## 3. Tabel Use Case

Derivasi Route 2 (FR → UC). Menyusun program dan mengonfirmasinya dipisah menjadi dua UC: draft
boleh disusun dan diubah di beberapa sesi, sedangkan konfirmasi adalah keputusan tersendiri yang
mengunci program.

| Kode | Nama Use Case | User | Modul | Sub Modul | Input | Deskripsi | Output | Entity/Objek Terkait | FR |
|---|---|---|---|---|---|---|---|---|---|
| UC-12 | Menyusun Program Public | *TBD (Q-PP-01)* | Program Penjualan | Program | Paket Produk aktif, Durasi (hari), Target Peserta, Harga Jual/Peserta, Target Profit (opsional), harga item Fixed & Variable Cost | Aktor memilih jenis Public dan Paket Produk, mengisi target. Sistem menampilkan semua item Fixed Cost aktif dengan Default Cost; aktor menyesuaikan harga sesuai Mode Harga dan menambah item Variable Cost satu per satu. Sistem menghitung simulasi (Total Fixed, Total Variable, HPP, Profit, Harga BEP/Peserta, Jumlah Peserta BEP) setiap kali angka berubah. Aktor menyimpan sebagai Draft dan dapat mengubahnya lagi selama Draft. | Program Public berstatus Draft dengan Nomor Program, costing, dan hasil simulasi | Program, Biaya Program, Paket Produk, Rate Fixed Cost, Rate Variable Cost, Satuan | PP-FR-01 – PP-FR-04, PP-FR-06 – PP-FR-12 |
| UC-13 | Mengonfirmasi Program Public (Final) | *TBD (Q-PP-01)* | Program Penjualan | Program | Program Public berstatus Draft | Aktor menekan "Create quotation". Sistem menampilkan popup ringkasan (durasi, target peserta, harga jual, HPP, profit, BEP) dan peringatan bila profit minus atau Jumlah Peserta BEP di atas target peserta. Setelah aktor mengonfirmasi, status menjadi Final tanpa berpindah halaman, program terkunci, dan muncul di modul Quotation. | Program Public berstatus Final, terkunci, tersedia di Quotation | Program | PP-FR-20 – PP-FR-22, PP-FR-25 |
| UC-14 | Menyusun Program B2B | *TBD (Q-PP-01)* | Program Penjualan | Program | Company, Paket Produk aktif, Durasi (hari), Harga Paket all-in, Jumlah Peserta (informasi), Target Profit (opsional), item Fixed Cost dan biaya manual | Aktor memilih jenis B2B, Company, dan Paket Produk, mengisi target. Aktor menambah item Fixed Cost satu per satu (pilih dari Master Rate lalu "+ Tambah") dan biaya manual di luar master. Sistem menghitung total biaya dan pendapatan bersih. Aktor menyimpan sebagai Draft dan dapat mengubahnya lagi selama Draft. | Program B2B berstatus Draft dengan Nomor Program dan costing | Program, Biaya Program, Paket Produk, Company, Rate Fixed Cost, Satuan | PP-FR-01 – PP-FR-03, PP-FR-05, PP-FR-06, PP-FR-13 – PP-FR-17 |
| UC-15 | Mengonfirmasi Deal Program B2B | *TBD (Q-PP-01)* | Program Penjualan | Program | Program B2B berstatus Draft | Aktor menekan "Deal" (tersedia langsung setelah program dibuat). Sistem menampilkan popup ringkasan (company, durasi, harga paket, total biaya, pendapatan bersih) dan peringatan bila pendapatan bersih minus. Setelah aktor mengonfirmasi, status menjadi Deal dan program terkunci. Program tidak melalui Quotation. | Program B2B berstatus Deal, terkunci | Program, Company | PP-FR-20, PP-FR-23 – PP-FR-25 |
| UC-16 | Membatalkan Program | *TBD (Q-PP-01)* | Program Penjualan | Program | Program berstatus Draft, Final, atau Deal | Aktor memilih Batalkan pada program dan mengonfirmasi. Status menjadi Batal; program tidak dapat diubah lagi. Bila perlu perubahan pada program yang sudah Final/Deal, aktor membatalkannya lalu membuat program baru. | Program berstatus Batal | Program | PP-FR-20, PP-FR-26 |
| UC-17 | Menyelesaikan Program | *TBD (Q-PP-01)* | Program Penjualan | Program | Program berstatus Final atau Deal; untuk Public: jumlah peserta aktual | Aktor memilih Selesaikan pada program yang sudah berjalan. Untuk Public, aktor mengisi jumlah peserta aktual. Status menjadi Selesai dan angka realisasi menjadi angka akhir. | Program berstatus Selesai dengan angka realisasi | Program | PP-FR-20, PP-FR-27, PP-FR-28 |
| UC-18 | Melihat Laporan As Is vs Aktual | *TBD (Q-PP-01)* | Program Penjualan | Laporan | Filter periode / status | Aktor membuka laporan. Sistem menampilkan per program Public: peserta, pendapatan, HPP, dan profit versi As Is (saat Final) berdampingan dengan versi Aktual, beserta selisihnya. | Laporan perbandingan As Is vs Aktual | Program, Biaya Program | PP-FR-29 |

> Semua UC belum lolos uji admission no. 1 (aktor bernama) — Q-PP-01.
> UC-17 dan sebagian UC-16 bergantung pada transisi status yang belum dinyatakan sumber (Q-PP-05, Q-PP-06).

---

## 4. Kebutuhan Fungsional (FR)

### 4.1 Program — umum

| ID | Kebutuhan | Sumber |
|---|---|---|
| PP-FR-01 | **Daftar Program** menampilkan: No, Nomor Program, Jenis (Public/B2B), Judul (dari Paket Produk), Customer (Company untuk B2B; "—" untuk Public), Durasi, Harga (harga jual/peserta atau harga paket), Status, Action (menu gear, isi sesuai status). Daftar dapat disaring per Jenis dan Status. | `[PRG-1]`, `[PRG-6]` + `[USULAN]` (kolom daftar) |
| PP-FR-02 | Program dibuat dari **satu menu**. Langkah pertama memilih **Jenis Program**: Public atau B2B. Setelah jenis dipilih, form dan costing mengikuti jalur masing-masing (PP-FR-04 / PP-FR-05). Jenis tidak dapat diubah setelah program disimpan. | `[PRG-1]` + `[USULAN]` (jenis terkunci) |
| PP-FR-03 | Setiap program memilih tepat satu **Paket Produk berstatus Aktif** dari modul Master Data, mengisi **Durasi (hari)**, dan mendapat **Nomor Program** otomatis (format Q-PP-08). Satu Paket Produk boleh dipakai lebih dari satu program. | `[PRG-1]`, `[PCS]` (kolom Hari) + `[USULAN]` (nomor, paket berulang) |
| PP-FR-04 | **Target Program Public:** Target Peserta (wajib, bilangan bulat > 0), Harga Jual per Peserta (wajib), Target Profit (opsional). Customer tidak diisi. | `[PRG-2]`, `[PRG-4]` |
| PP-FR-05 | **Target Program B2B:** Company (wajib, dari Master Company), Harga Paket all-in (wajib, sudah termasuk biaya pendukung), Jumlah Peserta (informasi, tidak dipakai menghitung), Target Profit (opsional). | `[PRG-3]`, `[PRG-5]` |
| PP-FR-06 | Item biaya diambil dari **Master Rate** beserta Default Cost-nya. Harga di costing mengikuti **Mode Harga** item (`fr-nfr.md` FR-33): **Default** — terisi Default Cost dan boleh diubah; **Fix** — terisi Default Cost dan tidak bisa diubah; **Input manual** — kosong dan wajib diisi. Item Master Rate yang nonaktif tidak dapat dipilih. Nama item, satuan, dan harga disimpan di program saat item dipakai, sehingga perubahan Master Rate kemudian tidak mengubah program yang sudah ada. | `[PRG-21]`, `[PRG-29]`, `[CM]`, `[KPG-3]`, `[KPG-4]` + `[USULAN]` (simpan salinan) |

### 4.2 Costing & Simulasi Program Public

| ID | Kebutuhan | Sumber |
|---|---|---|
| PP-FR-07 | Biaya Public dibagi **Fixed Cost** (tidak bergantung jumlah peserta) dan **Variable Cost** (bergantung jumlah peserta). | `[PRG-18]` |
| PP-FR-08 | Bagian **Fixed Cost** langsung menampilkan **semua** item Fixed Cost aktif dari Master Rate. Satuan: **Event** (sekali per program) dan **Day** (× durasi). Item yang di sumber bersatuan Hours dihitung sama dengan Event. | `[PRG-19]`, `[PRG-26]` (catatan: Public menampilkan semua fixed cost sekaligus), `[KPG-1]` |
| PP-FR-09 | Bagian **Variable Cost** awalnya kosong; item ditambah satu per satu (pilih dari Master Rate tab Variable Cost, lalu "+ Tambah") dan dapat dihapus selama Draft. Satuan: **Peserta/Event** (per peserta untuk seluruh acara) dan **Peserta/Day** (per peserta per hari). Item yang sama tidak dapat ditambahkan dua kali. | `[PRG-20]`, `[PRG-26]` ("sama seperti cara input variable cost di Public") + `[USULAN]` (tanpa duplikat) |
| PP-FR-10 | **Simulasi** dihitung ulang setiap kali durasi, target, atau harga item berubah, dan menampilkan: Total Fixed Cost, Total Variable Cost, HPP, Pendapatan, Profit, Harga BEP/Peserta, Jumlah Peserta BEP, Target Profit, Harga Jual. Rumus di bagian 8. | `[PRG-22]`, `[PRG-WISH-1]`, `[PRG-WISH-2]`, `[PCS]`, `[KPG-5]` |
| PP-FR-11 | Profit Public = Pendapatan − (Total Fixed + Total Variable). Rumus di Wishlist No 1 yang memakai tanda minus di dalam kurung adalah salah ketik. | `[KPG-5]`, `[PCS]` |
| PP-FR-12 | Simulasi menandai kondisi yang perlu diperhatikan: **profit minus**, **Jumlah Peserta BEP > Target Peserta**, dan **profit < Target Profit** (bila Target Profit diisi). Penanda ini tidak mencegah penyimpanan Draft. | `[PRG-12]` (dua peringatan pertama) + `[USULAN]` (perbandingan Target Profit) |

### 4.3 Costing Program B2B

| ID | Kebutuhan | Sumber |
|---|---|---|
| PP-FR-13 | B2B **tidak memakai** pembagian Fixed/Variable berbasis peserta. Semua biaya diambil dari item **Fixed Cost** Master Rate; tidak ada biaya per peserta karena basisnya mandays. | `[PRG-25]` |
| PP-FR-14 | Item biaya **tidak tampil semua**: aktor menambah item satu per satu (pilih dari Master Rate tab Fixed Cost, lalu "+ Tambah") sesuai kondisi program, dan dapat menghapusnya selama Draft. | `[PRG-26]` |
| PP-FR-15 | Aktor dapat menambah **biaya manual** di luar master: Nama Item (teks), Satuan (Event atau Day), Harga. Biaya manual hanya berlaku untuk program itu dan tidak menambah item ke Master Rate. | `[PRG-26]` ("ada opsi biaya manual di luar master") + `[USULAN]` (field & satuan) |
| PP-FR-16 | Subtotal item B2B: satuan **Event** = harga × 1 (sekali per program); satuan **Day** = harga × durasi. Item bersumber Hours dihitung sebagai Event. | `[PRG-27]`, `[KPG-1]` |
| PP-FR-17 | Costing B2B menampilkan **Total Biaya**, **Pendapatan Bersih** (Harga Paket − Total Biaya), dan **Mandays**. Komponen biaya terdiri dari biaya mandays (honor trainer/konsultan, mandays rate charge) dan biaya pendukung (akomodasi, transport, konsumsi trainer, perlengkapan, dsb.). Rumus Mandays dan rumus hasil B2B lainnya **menunggu konfirmasi Labmania**; sampai ada, Mandays ditampilkan "belum ditentukan". | `[PRG-15]`, `[PRG-27]`, `[PRG-28]`, `[KPG-1]`, `[KPG-5]` (Q-PP-02, Q-PP-03) |

### 4.4 Status & Konfirmasi

| ID | Kebutuhan | Sumber |
|---|---|---|
| PP-FR-20 | Status program mengikuti tabel transisi di bagian 6. Program baru tersimpan berstatus **Draft**. Selama Draft, angka belum pasti dan semua isian dapat diubah. | `[PRG-6]`, `[PRG-7]` |
| PP-FR-21 | Aksi **"Create quotation"** (Public, status Draft) memunculkan **popup ringkasan**: durasi, target peserta, harga jual, HPP, profit, Jumlah Peserta BEP / Harga BEP, beserta **peringatan** bila profit minus atau BEP di atas target. Setelah aktor mengonfirmasi, status menjadi **Final** **tanpa berpindah halaman**. | `[PRG-12]` |
| PP-FR-22 | Program Public berstatus **Final**: angka menjadi target resmi (As Is), program **terkunci** (hanya bisa dilihat), dan **muncul di modul Quotation**. | `[PRG-8]` |
| PP-FR-23 | Aksi **"Deal"** (B2B) tersedia **langsung setelah program dibuat** (status Draft). Aksi memunculkan popup ringkasan: company, durasi, harga paket, total biaya, pendapatan bersih, beserta peringatan bila pendapatan bersih minus. Setelah aktor mengonfirmasi, status menjadi **Deal**. | `[PRG-13]` |
| PP-FR-24 | Program B2B berstatus **Deal** sudah disepakati dengan company, **terkunci** (hanya bisa dilihat), dan **tidak** melalui modul Quotation. Langkah sesudah Deal (kemungkinan Sales Order) belum ditetapkan. | `[PRG-9]`, `[PRG-14]` (Q-PP-04) |
| PP-FR-25 | Tidak ada approval sebelum Final/Deal; konfirmasi dilakukan langsung oleh aktor. | `[PRG-19]` |
| PP-FR-26 | Program **Final/Deal tidak dapat diubah**. Bila perlu perubahan, aktor **membatalkan** program (status **Batal**) lalu membuat program baru. Batal juga dipakai bila program tidak jadi, mis. target peserta tidak tercapai. Program Batal tidak dapat diaktifkan kembali. | `[PRG-11]`, `[PRG-16]` + `[USULAN]` (tidak dapat dipulihkan) |

### 4.5 Realisasi & Laporan

| ID | Kebutuhan | Sumber |
|---|---|---|
| PP-FR-27 | Aksi **Selesaikan** mengubah program Final/Deal menjadi **Selesai** (program sudah berjalan / ditutup); realisasi menjadi angka akhir. Untuk Public, aktor mengisi **Jumlah Peserta Aktual**. | `[PRG-10]` + `[USULAN]` (aksi & isian, Q-PP-05, Q-PP-06) |
| PP-FR-28 | Realisasi Public: bila jumlah peserta aktual berbeda dari target, **harga jual per peserta tetap**, **cost tetap**, dan **yang berubah hanya profit**. Profit Aktual = Harga Jual × Peserta Aktual − HPP As Is. | `[PRG-23]`, `[PRG-24]` (Q-PP-07) |
| PP-FR-29 | **Laporan As Is vs Aktual** (Public) membandingkan per program: Peserta, Pendapatan, HPP, Profit — kolom As Is (saat Final), Aktual, dan Selisih. Program yang belum Selesai menampilkan Aktual "—". | `[PRG-24]` + `[USULAN]` (kolom laporan) |

---

## 5. Kebutuhan Non-Fungsional (NFR)

| ID | Kategori | Kebutuhan | Kriteria uji | Lingkup | Sumber |
|---|---|---|---|---|---|
| PP-NFR-01 | Audit | Setiap simpan, ubah costing, konfirmasi Final/Deal, batal, dan selesai dicatat dengan `write_log()` modul "Program Penjualan". Ubah costing mencatat header `old`/`new` dan perubahan per item di `detail_changes` (`Item [nama] - Harga`). | Mengubah harga 1 item dan durasi menghasilkan 1 log dengan 2 perubahan (Durasi; Item X - Harga) | UC-12 – UC-17 | `[STD]` |
| PP-NFR-02 | Integritas data | Program Final, Deal, Selesai, dan Batal **tidak dapat diubah** di server (bukan hanya tombol disembunyikan). | Request ubah costing ke program Final ditolak (HTTP 403/422) dan data tidak berubah | PP-FR-22, PP-FR-24, PP-FR-26 | `[PRG-8]`, `[PRG-9]`, `[PRG-16]` |
| PP-NFR-03 | Integritas data | Simpan program (header + semua baris biaya) berjalan dalam **satu transaksi database**. Konfirmasi status memeriksa status saat ini di dalam transaksi, sehingga dua konfirmasi bersamaan tidak menghasilkan transisi ganda. | Memaksa gagal pada baris biaya terakhir → tidak ada program baru; dua klik Final bersamaan → satu log Final | UC-12 – UC-17 | `[USULAN]` |
| PP-NFR-04 | Akurasi | Uang disimpan sebagai DECIMAL(15,2) dan ditampilkan dalam rupiah tanpa desimal dengan pemisah ribuan. Jumlah Peserta BEP **dibulatkan ke atas**. Harga BEP/Peserta dibulatkan ke rupiah terdekat. | Skenario `[PCS]` no 1 menghasilkan angka yang sama dengan Excel setelah koreksi data awal; BEP 10,2 tampil 11 | PP-FR-10, PP-FR-17 | `[PRG-WISH-2]` (dibulatkan ke atas) + `[USULAN]` |
| PP-NFR-05 | Validasi | Durasi dan Target/Jumlah Peserta bilangan bulat > 0; harga ≥ 0. Bila Harga Jual/Peserta ≤ Biaya Variable/Peserta, Jumlah Peserta BEP ditampilkan "tidak tercapai" (bukan error pembagian). | Harga jual 100.000 dengan biaya variable/peserta 150.000 → BEP "tidak tercapai"; durasi 0 ditolak | PP-FR-04, PP-FR-05, PP-FR-10 | `[USULAN]` |
| PP-NFR-06 | Kinerja | Simulasi dihitung di sisi klien saat mengetik, lalu dihitung ulang di server saat simpan/konfirmasi (angka server yang disimpan). | Hasil simulasi tampil < 200 ms setelah input berubah; nilai tersimpan sama dengan hitungan server | PP-FR-10, PP-FR-17 | `[USULAN]` |
| PP-NFR-07 | Keamanan | Akses menu dan aksi (buat, ubah, konfirmasi, batal, selesai, laporan) dikendalikan permission yang sudah ada di aplikasi. | Pengguna tanpa hak konfirmasi tidak melihat "Create quotation"/"Deal" dan request langsung ditolak | Semua UC | `[USULAN]` |
| PP-NFR-08 | Platform | CodeIgniter 3 (HMVC, `application/modules/`), MySQL, Bootstrap 4/5 + jQuery. Nama tabel/kolom identik dengan ERD. | Code review | Semua | `[STD]` |

---

## 6. Status Program

| Dari | Aksi | Ke | Jenis | Syarat / efek | Sumber |
|---|---|---|---|---|---|
| — | Simpan program baru | Draft | Semua | Nomor Program dibuat | `[PRG-7]` |
| Draft | Create quotation (konfirmasi popup) | Final | Public | Terkunci; muncul di Quotation | `[PRG-8]`, `[PRG-12]` |
| Draft | Deal (konfirmasi popup) | Deal | B2B | Terkunci | `[PRG-9]`, `[PRG-13]` |
| Final | Selesaikan | Selesai | Public | Isi peserta aktual; realisasi jadi angka akhir | `[PRG-10]`, `[PRG-23]` + `[USULAN]` |
| Deal | Selesaikan | Selesai | B2B | Realisasi jadi angka akhir | `[PRG-10]` + `[USULAN]` |
| Draft | Batalkan | Batal | Semua | — | `[PRG-11]` |
| Final | Batalkan | Batal | Public | Untuk perubahan atau target peserta tidak tercapai | `[PRG-11]`, `[PRG-16]` |
| Deal | Batalkan | Batal | B2B | Disimpulkan dari `[PRG-16]` ("Semua") | `[PRG-16]` (Q-PP-06) |

Selesai dan Batal adalah status akhir. Siapa dan kapan program diselesaikan belum dinyatakan
sumber (Q-PP-05).

---

## 7. Pertanyaan Terbuka

| ID | Pertanyaan | Mempengaruhi |
|---|---|---|
| Q-PP-01 | Siapa pengguna yang membuat program, mengonfirmasi Final/Deal, membatalkan, dan menyelesaikan program? Apakah sama untuk Public dan B2B? | UC-12 – UC-18 |
| Q-PP-02 | **Rumus B2B** *(menunggu konfirmasi Labmania, `[KPG-5]`)*: apakah Pendapatan Bersih = Harga Paket − Total Biaya? Bagaimana Target Profit B2B dibandingkan? | PP-FR-17, PP-FR-23 |
| Q-PP-03 | **Rumus Mandays** sebagai output costing B2B (mis. durasi × jumlah trainer, atau total biaya mandays ÷ rate harian?) | PP-FR-17 |
| Q-PP-04 | Apa langkah sesudah **Deal** — dibuat Sales Order dari program? | PP-FR-24 |
| Q-PP-05 | Siapa dan kapan program diubah menjadi **Selesai** (manual setelah acara, otomatis setelah tanggal tertentu)? Apakah program perlu **tanggal pelaksanaan**? | PP-FR-27, bagian 6 |
| Q-PP-06 | Apakah program B2B berstatus **Deal** boleh dibatalkan? | PP-FR-26, bagian 6 |
| Q-PP-07 | Realisasi Public "cost tetap": apakah HPP aktual memakai HPP As Is (sesuai teks), atau Variable Cost ikut jumlah peserta aktual? Dari mana **jumlah peserta aktual** diambil — diisi manual atau dihitung dari Quotation? | PP-FR-28, PP-FR-29 |
| Q-PP-08 | Format **Nomor Program** (prefix, digit, reset tahunan)? Apakah berbeda untuk Public dan B2B? | PP-FR-03 |
| Q-PP-09 | Apakah nama sub modul **Program** dan **Laporan** sudah sesuai? | Bagian 1 |
| Q-PP-10 | Apakah biaya manual (PP-FR-15) juga boleh di Program **Public**? | PP-FR-09, PP-FR-15 |
| Q-PP-11 | Bila program dibatalkan, apakah perlu **alasan batal**? | PP-FR-26 |

---

## 8. Lampiran — Rumus Program Public `[PCS]` `[PRG-WISH-1]` `[PRG-WISH-2]` `[KPG-5]`

| Hasil | Rumus |
|---|---|
| Total Fixed Cost | Σ harga item Fixed/**Event** + (Σ harga item Fixed/**Day** × Durasi) |
| Biaya Variable / Peserta | Σ harga item **Peserta/Event** + (Σ harga item **Peserta/Day** × Durasi) |
| Total Variable Cost | Biaya Variable / Peserta × Target Peserta |
| HPP | Total Fixed Cost + Total Variable Cost |
| Pendapatan | Harga Jual / Peserta × Target Peserta |
| Profit | Pendapatan − HPP |
| Harga BEP / Peserta | HPP ÷ Target Peserta |
| Jumlah Peserta BEP | Total Fixed Cost ÷ (Harga Jual / Peserta − Biaya Variable / Peserta), dibulatkan ke atas |

Contoh dengan data awal (`fr-nfr.md` bagian 7, item dengan Default Cost; durasi 2 hari, target 25 peserta):
Total Fixed = 4.075.000 + 515.000 × 2 = 5.105.000; Biaya Variable/Peserta = 60.000 + 90.000 × 2 = 240.000;
Total Variable = 240.000 × 25 = 6.000.000; HPP = 11.105.000; Harga BEP/Peserta = 444.200.

**Program B2B:** rumus menunggu konfirmasi (Q-PP-02, Q-PP-03).

---

## 9. Riwayat Perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| draf 1 | 23-10-2026 | Penyusunan awal dari `input/2026-10-23-requirements-program/` dan `input/2026-10-23-klarifikasi-program/`: Program Public & B2B, costing, simulasi, status, realisasi; UC-12 – UC-18; Hours dihitung sebagai Event, Mandays sebagai output B2B, profit Public = Pendapatan − (Fixed + Variable), rumus B2B ditunda. |
| draf 1a | 23-10-2026 | Mockup: layout form Program Public mengikuti `input/labmania.html` (`input/2026-10-23-layout-program-public/`). Isi FR tidak berubah. |
