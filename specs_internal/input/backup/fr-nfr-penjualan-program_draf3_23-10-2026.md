# Kebutuhan Fungsional & Non-Fungsional — Modul Penjualan Program Labmania

- **Versi:** draf 3 — 23-10-2026
- **Sumber:** `input/2026-10-23-requirements-3-modul/` (*Penjualan Program - Labmania.xlsx*: sheet *Requirements Quotation*, *Field Quotation*), `input/2026-10-23-konfirmasi-3-modul/`, `input/2026-10-23-arah-penjualan-program/`, `input/2026-10-23-layout-program-public/` (layout)
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
Master Program (Final, As Is) ──tarik──► Penjualan Program (Draft → Final) ──► Public: Quotation   (ditunda)
                                          sesuaikan angka & costing           └► B2B:    Sales Order (ditunda)
```

| | **Public** | **B2B** |
|---|---|---|
| Customer | Belum diisi di sini — calon peserta dipilih di Quotation `[ARH-5]` | **Satu Company** dari Master Company (wajib) `[QT-4]` |
| Harga | Harga jual / peserta, target peserta, target profit (opsional) `[QT-6]` | Harga paket all-in, **diskon** (Rp atau %), jumlah peserta **default 10, minimal 10**, target profit (opsional) `[QT-7]`, `[K3M-3]`, `[ARH-2]` |
| Hasil | Total fixed, total variable, HPP, profit, harga BEP / peserta, jumlah peserta BEP `[QT-9]` | Total biaya, harga paket, diskon, pendapatan bersih. BEP tidak dipakai. Mandays ditunda `[QT-9]`, `[ARH-1]` |
| Setelah Final | Masuk ke **Quotation** (ditunda) | Masuk ke **Sales Order** (ditunda) |

**As Is vs Penjualan** `[ARH-3]`: angka Master Program adalah **As Is**; angka Penjualan Program
mengikuti perubahan yang dibuat di sini. Keduanya dibandingkan di form dan di laporan.

Menu: modul **Penjualan Program**, sub modul **Penjualan** (menu: Daftar Penjualan Program) dan
**Laporan** (menu: As Is vs Penjualan). Nama usulan — Q-PJ-01.

### 1.1 Cakupan

| Termasuk | Tidak termasuk (ditunda / modul lain) |
|---|---|
| Penjualan Program Public & B2B dari Master Program Final | Modul **Quotation**: pilih calon peserta, diskon per peserta, Deal/Batal per peserta, surat quotation, realisasi peserta fix `[ARH-5]` |
| Tanggal & lokasi acara; costing per penjualan (salin & sesuaikan) | Modul **Sales Order** (B2B) `[ARH-4]` |
| Company dan diskon B2B | Rumus **Mandays** `[ARH-1]` |
| Status Draft → Final, atau Batal | Approval — saat ini tidak ada `[QT-16]` |
| Perbandingan As Is (Master Program) vs Penjualan | |

### 1.2 Istilah

| Istilah | Arti |
|---|---|
| Penjualan Program | Satu penyesuaian Master Program untuk satu penjualan, berjenis Public atau B2B, dengan Nomor Penjualan dan status. |
| As Is | Angka Master Program yang ditarik (baseline) `[ARH-3]`. |
| Penjualan | Angka Penjualan Program setelah disesuaikan `[ARH-3]`. |
| Harga net (B2B) | Harga paket all-in − diskon. |

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
| UC-22 | Menyusun Penjualan Program Public | *TBD (Q-PJ-02)* | Penjualan Program | Penjualan | Master Program Final, jenis Public, tanggal & lokasi acara, durasi, target peserta, harga jual / peserta, target profit, costing | Aktor memilih Master Program Final dan jenis Public. Sistem menyalin durasi, target, harga jual standar, dan costing dari Master Program. Aktor mengisi tanggal & lokasi, menyesuaikan angka dan item biaya (termasuk item Fix dan biaya lain). Simulasi dan perbandingan As Is vs Penjualan dihitung ulang setiap angka berubah. Aktor menyimpan sebagai Draft. | Penjualan Program Public Draft dengan Nomor Penjualan | Penjualan Program, Biaya Penjualan Program, Master Program, Rate Fixed Cost, Rate Variable Cost, Satuan | PJ-FR-01 – PJ-FR-10 |
| UC-23 | Menyusun Penjualan Program B2B | *TBD (Q-PJ-02)* | Penjualan Program | Penjualan | Master Program Final, jenis B2B, Company, tanggal & lokasi acara, durasi, harga paket, diskon (Rp / %), jumlah peserta (default 10, min 10), target profit, costing | Sama dengan UC-22, tetapi customer satu Company dari Master Company, harga berupa harga paket all-in dengan diskon, dan hasil berupa total biaya serta pendapatan bersih. | Penjualan Program B2B Draft dengan Nomor Penjualan | Penjualan Program, Biaya Penjualan Program, Master Program, Company, Rate Fixed Cost, Rate Variable Cost, Satuan | PJ-FR-01 – PJ-FR-08, PJ-FR-11 – PJ-FR-13 |
| UC-29 | Memfinalkan Penjualan Program | *TBD (Q-PJ-02)* | Penjualan Program | Penjualan | Penjualan Program berstatus Draft | Aktor menekan "Finalkan". Sistem menampilkan popup ringkasan (Public: durasi, target, harga jual, HPP, profit, BEP; B2B: company, durasi, jumlah peserta, harga paket, diskon, total biaya, pendapatan bersih) dan peringatan bila hasil minus. Setelah dikonfirmasi status menjadi Final dan terkunci; Public siap masuk ke Quotation, B2B ke Sales Order (modul berikutnya). | Penjualan Program Final, terkunci | Penjualan Program, Company | PJ-FR-14 – PJ-FR-16 |
| UC-26 | Membatalkan Penjualan Program | *TBD (Q-PJ-02)* | Penjualan Program | Penjualan | Penjualan Program berstatus Draft | Aktor memilih Batalkan dan mengonfirmasi. Status menjadi Batal dan tidak dapat diubah. | Penjualan Program berstatus Batal | Penjualan Program | PJ-FR-14, PJ-FR-17 |
| UC-28 | Melihat Laporan As Is vs Penjualan | *TBD (Q-PJ-02)* | Penjualan Program | Laporan | Filter jenis / status | Sistem menampilkan per Penjualan Program: HPP, pendapatan, dan profit versi As Is (Master Program) berdampingan dengan versi Penjualan, beserta selisih. | Laporan As Is vs Penjualan | Penjualan Program, Biaya Penjualan Program, Master Program | PJ-FR-18, PJ-FR-19 |

> Semua UC belum lolos uji admission no. 1 (aktor bernama) — Q-PJ-02.
> Dihapus pada draf 3 dan tidak dipakai ulang: UC-24 (Deal/Batal peserta), UC-25 (Deal B2B), UC-27 (surat quotation) — pindah ke modul Quotation / diganti UC-29 `[ARH-4]`, `[ARH-5]`.

---

## 4. Kebutuhan Fungsional (FR)

### 4.1 Penjualan Program — umum

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-01 | **Daftar Penjualan Program**: No, Nomor, Jenis (Public/B2B), Master Program (judul + nomor), Customer (B2B: nama company; Public: "calon peserta di Quotation"), Pelaksanaan (tanggal & lokasi), Harga (per peserta / paket net), Status, Action (menu gear). Dapat disaring per jenis dan status. | `[QT-1]` + `[USULAN]` (kolom daftar) |
| PJ-FR-02 | Penjualan Program dibuat dengan memilih **Master Program berstatus Final**. Master Program Draft/Batal tidak muncul. | `[QT-1]`, `[QT-FIELD]` |
| PJ-FR-03 | Pengguna memilih **jenis penjualan**: Public atau B2B; form menyesuaikan customer dan komponen harga. Jenis tidak dapat diubah setelah disimpan. | `[QT-2]` + `[USULAN]` (jenis terkunci) |
| PJ-FR-04 | Saat Master Program dipilih, sistem **menyalin** durasi, target profit, dan semua item costing (termasuk biaya lain). Public juga menyalin target peserta dan harga jual standar. B2B tidak menyalin harga jual standar dan target peserta: harga berupa paket, dan **jumlah peserta terisi default 10**. | `[QT-1]`, `[QT-5]`, `[K3M-3]` |
| PJ-FR-05 | Semua angka hasil salinan **dapat diedit**: durasi, target / jumlah peserta, harga, target profit, nominal item biaya **termasuk item bermode Fix**, menambah/menghapus item dari Master Rate, dan menambah biaya lain. Perubahan hanya berlaku di Penjualan Program itu dan **tidak mengubah Master Program**. | `[QT-5]`, `[USR-3M]`, `[K3M-2]` |
| PJ-FR-06 | Penjualan Program memuat **tanggal pelaksanaan** (mulai, selesai) dan **lokasi acara**. | `[USR-3M]` + `[USULAN]` (nama & format field, Q-PJ-03) |
| PJ-FR-07 | **Nomor Penjualan** otomatis saat pertama disimpan (format Q-PJ-08). | `[USULAN]` |
| PJ-FR-08 | Costing memakai model dan layout yang sama dengan Master Program: Fixed Cost per Event/Day, Variable Cost per Peserta/Event dan Peserta/Day, biaya lain. Biaya trainer dihitung per event (1 event = 1 trainer). | `[QT-5]`, `fr-nfr.md` FR-25 |

### 4.2 Public

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-09 | Input harga: **Harga jual / peserta**, **Target peserta**, **Target profit** (opsional). Calon peserta dan diskon per peserta **tidak** diisi di sini — keduanya di modul Quotation. | `[QT-6]`, `[ARH-5]` |
| PJ-FR-10 | **Simulasi**: Total Fixed Cost, Total Variable Cost, HPP, Profit (Pendapatan − HPP), Harga BEP / peserta, Jumlah peserta BEP — rumus `fr-nfr-master-program.md` bagian 8. Peringatan: profit minus, BEP di atas target, profit < target profit. | `[QT-9]`, `[QT-FIELD]` |

### 4.3 B2B

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-11 | Customer B2B = **satu Company** dari Master Company (wajib). | `[QT-4]`, `[ARH-4]` |
| PJ-FR-12 | Input harga: **Harga paket all-in** (sudah termasuk biaya pendukung), **Diskon** — nominal Rp atau persen atas harga paket, **Jumlah peserta** (default 10, dapat diedit, **minimal 10**, dipakai menghitung Variable Cost), **Target profit** (opsional). Harga net = harga paket − diskon. | `[QT-7]`, `[K3M-3]`, `[ARH-2]` + `[USULAN]` (bentuk diskon mengikuti `[K3M-5]`) |
| PJ-FR-13 | **Hasil B2B**: Total biaya = Total Fixed Cost + biaya variable per peserta × jumlah peserta; **Pendapatan bersih = Harga net − Total biaya**; peringatan bila minus atau di bawah target profit. BEP tidak dipakai. **Mandays ditunda** (belum ditampilkan sebagai angka). | `[QT-9]`, `[K3M-3]`, `[ARH-1]` |

### 4.4 Status

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-14 | Status: **Draft → Final**, atau **Batal** (bagian 6). Penjualan baru tersimpan Draft; selama Draft semua isian dapat diubah. Tidak ada approval. | `[ARH-4]`, `[QT-16]` |
| PJ-FR-15 | Aksi **"Finalkan"** memunculkan popup ringkasan (Public: durasi, target peserta, harga jual, HPP, profit, BEP; B2B: company, durasi, jumlah peserta, harga paket, diskon, harga net, total biaya, pendapatan bersih) dan peringatan bila hasil minus. Setelah dikonfirmasi status **Final** dan Penjualan Program **terkunci**. | `[ARH-4]`, `[QT-12]` (pola popup) |
| PJ-FR-16 | Penjualan Program **Final**: Public **masuk ke modul Quotation**, B2B **masuk ke modul Sales Order**. Kedua modul belum dikerjakan; sampai ada, Final hanya mengunci dan menandai "siap Quotation" / "siap Sales Order". | `[ARH-4]` |
| PJ-FR-17 | Penjualan Program **Draft** dapat **dibatalkan** (status Batal, tidak dapat diubah). Perubahan setelah Final: Q-PJ-12. | `[QT-12]` (pola batal) + `[USULAN]` |

### 4.5 As Is vs Penjualan

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-18 | **As Is** = angka Master Program yang ditarik (target/jumlah peserta, harga, HPP, profit). Angka Penjualan Program **mengikuti perubahan** yang dibuat di sana. Form menampilkan perbandingan As Is vs Penjualan dan selisihnya. | `[ARH-3]` |
| PJ-FR-19 | **Laporan As Is vs Penjualan**: per Penjualan Program (bukan Batal) — HPP, pendapatan, dan profit versi As Is dan Penjualan, beserta selisih. Pendapatan As Is = harga jual standar × target peserta Master Program; pendapatan Penjualan = harga jual × target (Public) atau harga net (B2B). Realisasi berdasarkan peserta fix menunggu modul Quotation. | `[ARH-3]`, `[QT-14]` + `[USULAN]` (kolom) |

---

## 5. Kebutuhan Non-Fungsional (NFR)

| ID | Kategori | Kebutuhan | Kriteria uji | Lingkup | Sumber |
|---|---|---|---|---|---|
| PJ-NFR-01 | Audit | Simpan, ubah costing, finalkan, dan batal dicatat `write_log()` modul "Penjualan Program"; ubah costing dengan `detail_changes` per item. | Ubah 1 harga item + durasi → 1 log dengan 2 perubahan | UC-22, UC-23, UC-26, UC-29 | `[STD]` |
| PJ-NFR-02 | Integritas data | Mengedit Penjualan Program tidak pernah mengubah Master Program (costing disalin ke tabel penjualan). | Ubah harga item di penjualan → `master_program_biaya` tetap | PJ-FR-05 | `[QT-5]` |
| PJ-NFR-03 | Integritas data | Penjualan Program Final/Batal tidak dapat diubah di server. | Request ubah ke penjualan Final ditolak (403/422) | PJ-FR-15 – PJ-FR-17 | `[ARH-4]` |
| PJ-NFR-04 | Integritas data | Simpan header + biaya dalam satu transaksi; finalkan memeriksa status di dalam transaksi. | Dua klik Finalkan bersamaan → satu log | UC-22, UC-23, UC-29 | `[USULAN]` |
| PJ-NFR-05 | Akurasi | Uang DECIMAL(15,2), tampil rupiah tanpa desimal; BEP dibulatkan ke atas; "tidak tercapai" bila harga ≤ biaya variable / peserta. | Angka sama dengan Master Program untuk input yang sama | PJ-FR-10, PJ-FR-13 | `[USULAN]` |
| PJ-NFR-06 | Validasi | Master Program, jenis, dan Company (B2B) wajib; durasi > 0; jumlah peserta B2B ≥ 10; tanggal selesai ≥ tanggal mulai; diskon nominal ≤ harga paket, diskon persen 0–100. | Jumlah peserta 9 ditolak; diskon 120% ditolak | PJ-FR-06, PJ-FR-11, PJ-FR-12 | `[K3M-3]` + `[USULAN]` |
| PJ-NFR-07 | Keamanan | Menu & aksi (buat, ubah, finalkan, batal, laporan) mengikuti permission aplikasi. | Tanpa hak finalkan → tombol tidak tampil, request ditolak | Semua UC | `[USULAN]` |
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
| Q-PJ-13 | **Diskon B2B**: nominal Rp atau persen (usulan: keduanya, seperti diskon Public)? | PJ-FR-12 |

**Sudah terjawab** (23-10-2026): Item Fix boleh diedit (`[K3M-2]`); Variable Cost B2B per peserta, jumlah peserta default/minimal 10 (`[K3M-3]`); B2B ada diskon (`[ARH-2]`); HPP As Is = Master Program, Penjualan mengikuti perubahan (`[ARH-3]`, menggantikan Q-PJ-09); setelah Final → Quotation / Sales Order (`[ARH-4]`, menggantikan Q-PJ-10). **Ditunda ke modul Quotation** `[ARH-5]`: diskon per peserta, Deal/Batal per peserta, kunci costing setelah peserta Deal (`[K3M-4]`, `[K3M-5]`), surat quotation (Q-PJ-11), realisasi peserta fix.

---

## 8. Riwayat Perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| draf 1 | 23-10-2026 | Penyusunan awal dari *Penjualan Program - Labmania.xlsx* sebagai modul Quotation (Public multi-peserta, B2B Deal). |
| draf 2 | 23-10-2026 | `input/2026-10-23-konfirmasi-3-modul/`: item Fix dapat diedit; B2B variable per peserta, jumlah peserta default/min 10; kunci costing setelah peserta Deal; diskon per peserta Rp / %. |
| draf 3 | 23-10-2026 | `input/2026-10-23-arah-penjualan-program/` — ditulis ulang: dokumen bernama **Penjualan Program** (bukan quotation) dengan status Draft → Final / Batal; Final Public → Quotation, B2B → Sales Order (ditunda); semua fitur quotation (peserta, diskon per peserta, Deal per peserta, surat, realisasi) ditunda; diskon B2B baru; As Is = Master Program vs Penjualan (form & laporan); Mandays ditunda. UC-24, UC-25, UC-27 dihapus; UC-29 baru; UC-22, UC-23, UC-26, UC-28 diperbarui. |
