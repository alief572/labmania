# Kebutuhan Fungsional & Non-Fungsional — Modul Penjualan Program Labmania

- **Versi:** draf 7 — 23-10-2026
- **Sumber:** `input/2026-10-23-requirements-3-modul/` (*Penjualan Program - Labmania.xlsx*: sheet *Requirements Quotation*, *Field Quotation*), `input/2026-10-23-konfirmasi-3-modul/`, `input/2026-10-23-arah-penjualan-program/`, `input/2026-10-23-revisi-rate-harga-simulasi/`, `input/2026-10-23-simulasi-bep/`, `input/2026-10-23-layout-program-public/` (layout)
- **Status:** DRAF — pertanyaan terbuka di bagian 7
- **Modul terkait:** hulu Master Program → `docs/fr-nfr-master-program.md`; Master Data → `docs/fr-nfr.md` (Master Company, Master Rate); hilir **Quotation** (Public) dan **Sales Order** (B2B) — **belum dikerjakan** `[ARH-4]`
- **Use case kanonis:** `docs/workbook.xlsx` sheet *UC Non-BPMN*
- **ERD:** `docs/diagrams/erd.drawio` halaman *ERD Penjualan Program*
- **Mockup:** `ui-mockup/produk/index.html` → modul Penjualan Program (layout form mengikuti `input/labmania.html`)

Penomoran memakai awalan **PJ-**. Kode UC satu urutan untuk seluruh workbook; kode yang dihapus tidak dipakai ulang.

| Penanda | Arti |
|---|---|
| `[QT-n]` | *Penjualan Program - Labmania.xlsx* sheet *Requirements Quotation* bagian 1 No n |
| `[QT-FIELD]` | sheet *Field Quotation* |
| `[USR-3M]` | penjelasan user, `input/2026-10-23-requirements-3-modul/` |
| `[K3M-n]` | konfirmasi, `input/2026-10-23-konfirmasi-3-modul/` — butir n |
| `[ARH-n]` | arah modul, `input/2026-10-23-arah-penjualan-program/` — butir n |
| `[REV-n]` | revisi, `input/2026-10-23-revisi-rate-harga-simulasi/` — butir n |
| `[SIMX]` | tampilan simulasi, `input/2026-10-23-simulasi-hasil-html/` (`input/simulasi_hasil.html`) |
| `[TDK-1]` | Penjualan Program tanpa diskon, `input/2026-10-23-tanpa-diskon/` |
| `[BEP-1]` | permintaan data BEP, `input/2026-10-23-simulasi-bep/` |
| `[STD]` | standar proyek di `AGENTS.md` |
| `[USULAN]` | usulan analis, **belum** dinyatakan klien |

---

## 1. Ringkasan Kebutuhan

**Penjualan Program** = penyesuaian satu Master Program untuk satu penjualan. Pengguna **menarik
Master Program berstatus Final**, memilih jenis **Public** atau **B2B**, mengisi tanggal dan lokasi
acara, lalu **semua angka boleh disesuaikan** tanpa mengubah master `[QT-1]`, `[QT-5]`, `[USR-3M]`.
Setelah **Final**, penjualan Public masuk ke modul **Quotation** dan penjualan B2B masuk ke modul
**Sales Order** — keduanya modul berikutnya yang belum dikerjakan `[ARH-4]`, `[ARH-5]`.

```
Master Program (Final) ──tarik──► Penjualan Program (Draft → Final) ──► Public: Quotation   (ditunda)
                                   sesuaikan angka & costing           └► B2B:    Sales Order (ditunda)
```

| | **Public** | **B2B** |
|---|---|---|
| Customer | Belum diisi di sini — calon peserta dipilih di Quotation `[ARH-5]` | **Satu Company** dari Master Company (wajib) `[QT-4]` |
| Skema harga | Per peserta | **Per peserta** (default) atau **All-in** (harga paket) — dipilih per penjualan `[REV-2]` |
| Harga | Harga jual / peserta, target peserta, target profit (opsional) `[QT-6]` | Harga jual / peserta **atau** harga paket all-in; jumlah peserta **default 10, minimal 10**; target profit (opsional) `[K3M-3]`, `[ARH-2]`, `[REV-2]` |
| Hasil | Pendapatan, HPP, profit, titik impas (BEP) | Pendapatan, HPP, profit; titik impas hanya untuk skema per peserta. Mandays ditunda `[ARH-1]` |
| Setelah Final | Masuk ke **Quotation** (ditunda) | Masuk ke **Sales Order** (ditunda) |

Menu: modul **Penjualan Program**, sub modul **Penjualan** (menu: Daftar Penjualan Program). Nama
usulan — Q-PJ-01. Laporan As Is vs Aktual **dihapus** sampai konsepnya dijelaskan `[REV-3]` (Q-PJ-14).

### 1.1 Cakupan

| Termasuk | Tidak termasuk (ditunda / modul lain) |
|---|---|
| Penjualan Program Public & B2B dari Master Program Final | Modul **Quotation**: calon peserta, diskon per peserta, Deal/Batal per peserta, surat quotation, realisasi peserta fix `[ARH-5]` |
| Tanggal & lokasi acara; costing per penjualan (salin & sesuaikan) | Modul **Sales Order** (B2B) `[ARH-4]` |
| Company dan skema harga B2B | Rumus **Mandays** `[ARH-1]`; **diskon** (Public & B2B) — ditunda `[TDK-1]` |
| Status Draft → Final, atau Batal | As Is vs Aktual `[REV-3]`; approval `[QT-16]` |

### 1.2 Istilah

| Istilah | Arti |
|---|---|
| Penjualan Program | Satu penyesuaian Master Program untuk satu penjualan, berjenis Public atau B2B, dengan Nomor Penjualan dan status. |
| Skema harga (B2B) | **Per peserta**: pendapatan = harga jual / peserta × jumlah peserta. **All-in**: pendapatan = harga paket. |
| Titik impas (BEP) | Jumlah peserta minimal agar tidak rugi, dan harga minimal per peserta pada jumlah peserta yang direncanakan. |

---

## 2. Aktor

| Aktor | Peran | Sumber |
|---|---|---|
| *Belum ditetapkan* (kemungkinan Sales / Marketing) | Menyusun, memfinalkan, dan membatalkan Penjualan Program | Q-PJ-02 |
| Quotation (modul, ditunda) | Menerima Penjualan Program Public berstatus Final | `[ARH-4]` |
| Sales Order (modul, ditunda) | Menerima Penjualan Program B2B berstatus Final | `[ARH-4]` |

---

## 3. Tabel Use Case

| Kode | Nama Use Case | User | Modul | Sub Modul | Input | Deskripsi | Output | Entity/Objek Terkait | FR |
|---|---|---|---|---|---|---|---|---|---|
| UC-22 | Menyusun Penjualan Program Public | *TBD (Q-PJ-02)* | Penjualan Program | Penjualan | Master Program Final, jenis Public, tanggal & lokasi acara, durasi, target peserta, harga jual / peserta, target profit, costing | Aktor memilih Master Program Final dan jenis Public. Sistem menyalin durasi, target, harga jual standar, dan costing dari Master Program. Aktor mengisi tanggal & lokasi, menyesuaikan angka dan item biaya (semua nominal dapat diedit, termasuk biaya lain). Simulasi dihitung ulang setiap angka berubah. Aktor menyimpan sebagai Draft. | Penjualan Program Public Draft dengan Nomor Penjualan | Penjualan Program, Biaya Penjualan Program, Master Program, Rate Fixed Cost, Rate Variable Cost, Satuan | PJ-FR-01 – PJ-FR-10 |
| UC-23 | Menyusun Penjualan Program B2B | *TBD (Q-PJ-02)* | Penjualan Program | Penjualan | Master Program Final, jenis B2B, Company, tanggal & lokasi acara, durasi, skema harga (per peserta / all-in), harga jual / peserta atau harga paket, jumlah peserta (default 10, min 10), target profit, costing | Sama dengan UC-22, tetapi customer satu Company dari Master Company, aktor memilih skema harga per peserta (default) atau all-in. Tidak ada diskon. | Penjualan Program B2B Draft dengan Nomor Penjualan | Penjualan Program, Biaya Penjualan Program, Master Program, Company, Rate Fixed Cost, Rate Variable Cost, Satuan | PJ-FR-01 – PJ-FR-08, PJ-FR-10 – PJ-FR-13 |
| UC-29 | Memfinalkan Penjualan Program | *TBD (Q-PJ-02)* | Penjualan Program | Penjualan | Penjualan Program berstatus Draft | Aktor menekan "Finalkan". Sistem menampilkan popup ringkasan (durasi, peserta, harga / skema, pendapatan, HPP, profit, titik impas bila per peserta) dan peringatan bila profit minus. Setelah dikonfirmasi status menjadi Final dan terkunci; Public siap masuk ke Quotation, B2B ke Sales Order (modul berikutnya). | Penjualan Program Final, terkunci | Penjualan Program, Company | PJ-FR-14 – PJ-FR-16 |
| UC-26 | Membatalkan Penjualan Program | *TBD (Q-PJ-02)* | Penjualan Program | Penjualan | Penjualan Program berstatus Draft | Aktor memilih Batalkan dan mengonfirmasi. Status menjadi Batal dan tidak dapat diubah. | Penjualan Program berstatus Batal | Penjualan Program | PJ-FR-14, PJ-FR-17 |

> Semua UC belum lolos uji admission no. 1 (aktor bernama) — Q-PJ-02.
> Dihapus dan tidak dipakai ulang: UC-24, UC-25, UC-27 (draf 3, pindah ke Quotation / diganti UC-29); **UC-28** Laporan As Is vs Penjualan (draf 4, `[REV-3]`).

---

## 4. Kebutuhan Fungsional (FR)

### 4.1 Penjualan Program — umum

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-01 | **Daftar Penjualan Program**: No, Nomor, Jenis (Public/B2B), Master Program (judul + nomor), Customer (B2B: nama company; Public: "calon peserta di Quotation"), Pelaksanaan (tanggal & lokasi), Harga (per peserta, atau paket untuk B2B all-in), Status, Action (menu gear). Dapat disaring per jenis dan status. | `[QT-1]` + `[USULAN]` (kolom daftar) |
| PJ-FR-02 | Penjualan Program dibuat dengan memilih **Master Program berstatus Final**. Master Program Draft/Batal tidak muncul. | `[QT-1]`, `[QT-FIELD]` |
| PJ-FR-03 | Pengguna memilih **jenis penjualan**: Public atau B2B; form menyesuaikan customer dan komponen harga. Jenis tidak dapat diubah setelah disimpan. | `[QT-2]` + `[USULAN]` (jenis terkunci) |
| PJ-FR-04 | Saat Master Program dipilih, sistem **menyalin** durasi, harga jual standar / peserta, target profit, dan semua item costing (termasuk biaya lain). Public juga menyalin target peserta. B2B: skema harga terisi **per peserta**, jumlah peserta terisi **default 10**. | `[QT-1]`, `[QT-5]`, `[K3M-3]`, `[REV-2]` |
| PJ-FR-05 | Semua angka hasil salinan **dapat diedit**: durasi, target / jumlah peserta, harga, target profit, nominal semua item biaya, menambah/menghapus item dari Master Rate, dan menambah biaya lain. Perubahan hanya berlaku di Penjualan Program itu dan **tidak mengubah Master Program**. | `[QT-5]`, `[USR-3M]`, `[K3M-2]`, `[REV-1]` |
| PJ-FR-06 | Penjualan Program memuat **tanggal pelaksanaan** (mulai, selesai) dan **lokasi acara**. | `[USR-3M]` + `[USULAN]` (nama & format field, Q-PJ-03) |
| PJ-FR-07 | **Nomor Penjualan** otomatis saat pertama disimpan (format Q-PJ-08). | `[USULAN]` |
| PJ-FR-08 | Costing memakai model dan layout yang sama dengan Master Program: Fixed Cost per Event/Day, Variable Cost per Peserta/Event dan Peserta/Day, biaya lain. Biaya trainer dihitung per event (1 event = 1 trainer). | `[QT-5]`, `fr-nfr.md` FR-25 |

### 4.2 Public

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-09 | Input harga: **Harga jual / peserta**, **Target peserta**, **Target profit** (opsional). Calon peserta dan diskon per peserta **tidak** diisi di sini — keduanya di modul Quotation. | `[QT-6]`, `[ARH-5]` |
| PJ-FR-10 | **Simulasi** (Public dan B2B) dihitung ulang setiap angka berubah. Tampilan mengikuti `input/simulasi_hasil.html` `[SIMX]`: (1) **Titik impas (BEP)** — kotak *BEP jumlah peserta* = Fixed Cost ÷ (harga per peserta − biaya variable per peserta), dibulatkan ke atas, dan *BEP harga jual* = HPP ÷ jumlah peserta, masing-masing dengan kalimat penjelas (aman/kurang terhadap rencana); (2) **Hasil rencana simulasi** — kotak Pendapatan, Biaya (HPP), Profit (untung/rugi, margin % dari pendapatan), lalu baris **target profit** (tercapai / kurang) beserta **minimal peserta untuk capai target** = ⌈(Fixed Cost + target profit) ÷ (harga − biaya variable per peserta)⌉; (3) **Posisi di skala jumlah peserta** — batang zona rugi / untung belum capai target / target tercapai dengan penanda BEP, Target profit, Rencana; (4) **Rincian perhitungan** yang dapat dibuka/tutup — tabel komponen, cara hitung, nilai dan tabel profit menurut jumlah peserta di sekitar BEP dan rencana. **B2B all-in**: kotak BEP menjadi *harga paket minimal* (= HPP) dan *maksimal peserta agar tidak rugi* = ⌊(pendapatan − Fixed Cost) ÷ biaya variable per peserta⌋; skala dibalik (untung di bawah batas, rugi di atasnya). Peringatan: profit minus, BEP di atas jumlah peserta, profit < target profit. | `[QT-9]`, `[QT-FIELD]`, `[REV-4]`, `[BEP-1]`, `[SIMX]` + `[USULAN]` (all-in) |

### 4.3 B2B

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-11 | Customer B2B = **satu Company** dari Master Company (wajib). | `[QT-4]`, `[ARH-4]` |
| PJ-FR-12 | **Skema harga** B2B dipilih per penjualan: **Per peserta** (default) — input **Harga jual / peserta**; atau **All-in** — input **Harga paket**. Untuk kedua skema: **Jumlah peserta** (default 10, minimal 10, dipakai menghitung Variable Cost), **Target profit** (opsional). Harga paket all-in **tidak wajib** bila skema per peserta. | `[QT-7]`, `[K3M-3]`, `[ARH-2]`, `[REV-2]` , `[TDK-1]` (tanpa diskon) |
| PJ-FR-13 | **Perhitungan B2B**: Pendapatan kotor = harga jual / peserta × jumlah peserta (per peserta) atau harga paket (all-in); **Pendapatan** = harga jual / peserta × jumlah peserta (per peserta) atau harga paket (all-in), tanpa diskon `[TDK-1]`; HPP = Total Fixed Cost + biaya variable per peserta × jumlah peserta; **Profit** = pendapatan bersih − HPP. Titik impas dihitung hanya untuk skema per peserta . **Mandays ditunda**. | `[QT-9]`, `[K3M-3]`, `[ARH-1]`, `[REV-2]` + `[USULAN]` (BEP per peserta B2B) |

### 4.4 Status

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-14 | Status: **Draft → Final**, atau **Batal** (bagian 6). Penjualan baru tersimpan Draft; selama Draft semua isian dapat diubah. Tidak ada approval. | `[ARH-4]`, `[QT-16]` |
| PJ-FR-15 | Aksi **"Finalkan"** memunculkan popup ringkasan (Public: durasi, target peserta, harga jual, pendapatan, HPP, profit, titik impas; B2B: company, durasi, jumlah peserta, skema & harga, pendapatan, HPP, profit) dan peringatan bila profit minus. Setelah dikonfirmasi status **Final** dan Penjualan Program **terkunci**. | `[ARH-4]`, `[QT-12]` (pola popup) |
| PJ-FR-16 | Penjualan Program **Final**: Public **masuk ke modul Quotation**, B2B **masuk ke modul Sales Order**. Kedua modul belum dikerjakan; sampai ada, Final hanya mengunci dan menandai "→ Quotation" / "→ Sales Order". | `[ARH-4]` |
| PJ-FR-17 | Penjualan Program **Draft** dapat **dibatalkan** (status Batal, tidak dapat diubah). Pembatalan setelah Final: Q-PJ-12. | `[QT-12]` (pola batal) + `[USULAN]` |

> PJ-FR-18 dan PJ-FR-19 (As Is vs Penjualan, laporan) **dihapus** pada draf 4 `[REV-3]` dan tidak dipakai ulang.

---

## 5. Kebutuhan Non-Fungsional (NFR)

| ID | Kategori | Kebutuhan | Kriteria uji | Lingkup | Sumber |
|---|---|---|---|---|---|
| PJ-NFR-01 | Audit | Simpan, ubah costing, finalkan, dan batal dicatat `write_log()` modul "Penjualan Program"; ubah costing dengan `detail_changes` per item. | Ubah 1 harga item + durasi → 1 log dengan 2 perubahan | UC-22, UC-23, UC-26, UC-29 | `[STD]` |
| PJ-NFR-02 | Integritas data | Mengedit Penjualan Program tidak pernah mengubah Master Program (costing disalin ke tabel penjualan). | Ubah harga item di penjualan → `master_program_biaya` tetap | PJ-FR-05 | `[QT-5]` |
| PJ-NFR-03 | Integritas data | Penjualan Program Final/Batal tidak dapat diubah di server. | Request ubah ke penjualan Final ditolak (403/422) | PJ-FR-15 – PJ-FR-17 | `[ARH-4]` |
| PJ-NFR-04 | Integritas data | Simpan header + biaya dalam satu transaksi; finalkan memeriksa status di dalam transaksi. | Dua klik Finalkan bersamaan → satu log | UC-22, UC-23, UC-29 | `[USULAN]` |
| PJ-NFR-05 | Akurasi | Uang DECIMAL(15,2), tampil rupiah tanpa desimal; titik impas peserta dibulatkan ke atas; "tidak tercapai" bila harga bersih per peserta ≤ biaya variable / peserta. | Angka sama dengan Master Program untuk input yang sama | PJ-FR-10, PJ-FR-13 | `[USULAN]` |
| PJ-NFR-06 | Validasi | Master Program, jenis, dan Company (B2B) wajib; durasi > 0; jumlah peserta B2B ≥ 10; harga jual / peserta wajib bila skema per peserta, harga paket wajib bila all-in; tanggal selesai ≥ tanggal mulai. | Jumlah peserta 9 ditolak; skema all-in tanpa harga paket ditolak | PJ-FR-06, PJ-FR-11, PJ-FR-12 | `[K3M-3]`, `[REV-2]` + `[USULAN]` |
| PJ-NFR-07 | Keamanan | Menu & aksi (buat, ubah, finalkan, batal) mengikuti permission aplikasi. | Tanpa hak finalkan → tombol tidak tampil, request ditolak | Semua UC | `[USULAN]` |
| PJ-NFR-08 | Platform | CI3 HMVC, MySQL, Bootstrap + jQuery; nama tabel/kolom = ERD. Tidak memakai `tr_penawaran` (modul quotation lama aplikasi). | Code review | Semua | `[STD]` |

---

## 6. Status Penjualan Program

| Dari | Aksi | Ke | Jenis | Syarat / efek | Sumber |
|---|---|---|---|---|---|
| — | Simpan penjualan baru | Draft | Semua | Nomor dibuat | `[USULAN]` |
| Draft | Finalkan (konfirmasi popup) | Final | Public | Terkunci; masuk ke Quotation (modul berikutnya) | `[ARH-4]` |
| Draft | Finalkan (konfirmasi popup) | Final | B2B | Terkunci; masuk ke Sales Order (modul berikutnya) | `[ARH-4]` |
| Draft | Batalkan | Batal | Semua | — | `[USULAN]` |

Final dan Batal tidak kembali ke Draft (Q-PJ-12).

---

## 7. Pertanyaan Terbuka

| ID | Pertanyaan | Mempengaruhi |
|---|---|---|
| Q-PJ-01 | Nama modul dan menu: "Penjualan Program" / "Daftar Penjualan Program" sudah sesuai? | Bagian 1 |
| Q-PJ-02 | Siapa pengguna yang menyusun, memfinalkan, dan membatalkan Penjualan Program? | UC-22 – UC-29 |
| Q-PJ-03 | Tanggal & lokasi acara: cukup tanggal mulai–selesai dan lokasi teks bebas? Durasi dihitung dari tanggal atau tetap diisi terpisah? | PJ-FR-06 |
| Q-PJ-04 | Rumus **Mandays** B2B — *ditunda, menyusul* `[ARH-1]`. | PJ-FR-13 |
| Q-PJ-08 | Format **Nomor Penjualan**; berbeda untuk Public dan B2B? | PJ-FR-07 |
| Q-PJ-12 | Apakah Penjualan Program yang sudah **Final** boleh dibatalkan (mis. bila quotation / sales order tidak jadi)? | PJ-FR-17 |
| Q-PJ-14 | Bagaimana konsep **As Is vs Aktual** yang dimaksud — angka apa dibandingkan dengan apa, dan di modul mana? *(versi Master Program vs Penjualan dihapus `[REV-3]`)* | — |

**Sudah terjawab** (23-10-2026): item Fix / mode harga tidak ada lagi, semua nominal dapat diedit (`[K3M-2]`, `[REV-1]`); Variable Cost B2B per peserta, jumlah peserta default/minimal 10 (`[K3M-3]`); diskon (Public & B2B) ditunda dulu (`[TDK-1]`, menggantikan `[ARH-2]`); harga all-in hanya opsi, default per peserta (`[REV-2]`); setelah Final → Quotation / Sales Order (`[ARH-4]`). **Ditunda ke modul Quotation** `[ARH-5]`: diskon per peserta, Deal/Batal per peserta, surat quotation (Q-PJ-11), realisasi peserta fix.

---

## 8. Riwayat Perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| draf 1 | 23-10-2026 | Penyusunan awal dari *Penjualan Program - Labmania.xlsx* sebagai modul Quotation (Public multi-peserta, B2B Deal). |
| draf 2 | 23-10-2026 | `input/2026-10-23-konfirmasi-3-modul/`: item Fix dapat diedit; B2B variable per peserta, jumlah peserta default/min 10; kunci costing setelah peserta Deal; diskon per peserta Rp / %. |
| draf 3 | 23-10-2026 | `input/2026-10-23-arah-penjualan-program/` — ditulis ulang sebagai **Penjualan Program** dengan status Draft → Final / Batal; Final Public → Quotation, B2B → Sales Order (ditunda); fitur quotation ditunda; diskon B2B; As Is vs Penjualan; UC-24, UC-25, UC-27 dihapus; UC-29 baru. |
| draf 4 | 23-10-2026 | `input/2026-10-23-revisi-rate-harga-simulasi/`: B2B punya **skema harga** per peserta (default) atau all-in — harga paket tidak wajib (PJ-FR-04, PJ-FR-12, PJ-FR-13, PJ-NFR-06); As Is vs Penjualan dan laporannya dihapus (PJ-FR-18, PJ-FR-19, UC-28 dihapus; Q-PJ-14 baru); simulasi ditata ulang agar mudah dibaca (PJ-FR-10); tidak ada mode harga (PJ-FR-05). |
| draf 5 | 23-10-2026 | `input/2026-10-23-simulasi-bep/`: simulasi ditambah **Data BEP** — perhitungan BEP, tabel profit menurut jumlah peserta, dan batas peserta untuk B2B all-in (PJ-FR-10). |
| draf 6 | 23-10-2026 | `input/2026-10-23-simulasi-hasil-html/`: tampilan simulasi mengikuti `input/simulasi_hasil.html` (PJ-FR-10); B2B all-in memakai harga paket minimal & maksimal peserta pada kotak BEP dan skala. |
| draf 6a | 23-10-2026 | Istilah "sumbangan per peserta" dihapus atas permintaan user (chat 23-10-2026); BEP peserta ditulis langsung sebagai Fixed Cost ÷ (harga − biaya variable per peserta). |
| draf 7 | 23-10-2026 | `input/2026-10-23-tanpa-diskon/`: **diskon dihapus untuk sementara** (Public & B2B) — PJ-FR-12, PJ-FR-13, PJ-FR-15, PJ-NFR-06, UC-23, UC-29; Q-PJ-13 gugur. |
