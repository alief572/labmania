# Kebutuhan Fungsional & Non-Fungsional — Modul Penjualan Program Labmania

- **Versi:** draf 8 — 25-10-2026
- **Sumber:** `input/2026-10-23-requirements-3-modul/` (*Penjualan Program - Labmania.xlsx*: sheet *Requirements Quotation*, *Field Quotation*), `input/2026-10-23-konfirmasi-3-modul/`, `input/2026-10-23-arah-penjualan-program/`, `input/2026-10-23-revisi-rate-harga-simulasi/`, `input/2026-10-23-simulasi-bep/`, `input/2026-10-23-tanpa-diskon/`, `input/2026-10-25-jalur-public-b2b/`, `input/2026-10-23-layout-program-public/` (layout)
- **Status:** DRAF — pertanyaan terbuka di bagian 7
- **Modul terkait:** hulu Master Program → `docs/fr-nfr-master-program.md`; Master Data → `docs/fr-nfr.md` (Master Rate); hilir **Quotation** (Public) dan **Sales Order** (B2B, memakai Master Company) — **belum dikerjakan** `[ARH-4]`, `[JLR-2]`
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
| `[JLR-n]` | jalur Public vs B2B, `input/2026-10-25-jalur-public-b2b/` — pesan n |
| `[STD]` | standar proyek di `AGENTS.md` |
| `[USULAN]` | usulan analis, **belum** dinyatakan klien |

---

## 1. Ringkasan Kebutuhan

**Penjualan Program** = rencana **satu acara** dari satu Master Program. Pengguna **menarik Master
Program berstatus Final**, memilih jenis **Public** atau **B2B**, mengisi tanggal dan lokasi acara,
lalu **semua angka boleh disesuaikan** tanpa mengubah master `[QT-1]`, `[QT-5]`, `[USR-3M]`.

**Form dan simulasi sama untuk Public dan B2B** `[JLR-1]`. Jenis hanya menentukan **jalur setelah
Final** `[JLR-3]`:

```
Master Program (Final) ──tarik──► Penjualan Program (Draft → Final) ──┬─► Public: Quotation   — 1 : banyak (ditunda)
                                   form & simulasi SAMA                 │
                                   jenis = penanda jalur                └─► B2B:    Sales Order — 1 : 1     (ditunda)
```

| | **Public → Quotation** | **B2B → Sales Order** |
|---|---|---|
| Relasi ke Penjualan Program | Satu Penjualan Program → **banyak** quotation `[JLR-2]` | Satu Penjualan Program → **satu** SO `[JLR-2]` |
| Customer | Calon peserta, **tidak terikat company** `[JLR-2]` | **Satu Company** dari Master Company `[JLR-2]`, `[QT-4]` |
| Harga | Per peserta; diskon per peserta (ditunda) `[ARH-5]` | **Detail harga per peserta** `[JLR-2]`, `[JLR-3]`; harga paket = akumulasi per peserta `[K3M-3]` |

Yang **tidak lagi** ada di Penjualan Program (pindah ke Sales Order, ditunda): pilih Company, skema
harga all-in, harga paket, jumlah peserta minimal 10 `[JLR-2]`. Mandays tidak dipakai `[JLR-1]`.

Menu: modul **Penjualan Program**, sub modul **Penjualan** (menu: Daftar Penjualan Program). Nama
usulan — Q-PJ-01. Laporan As Is vs Aktual **dihapus** sampai konsepnya dijelaskan `[REV-3]` (Q-PJ-14).

### 1.1 Cakupan

| Termasuk | Tidak termasuk (ditunda / modul lain) |
|---|---|
| Penjualan Program Public & B2B dari Master Program Final, satu form | Modul **Quotation** (Public): calon peserta, diskon per peserta, Deal/Batal per peserta, surat quotation, realisasi peserta fix `[ARH-5]` |
| Tanggal & lokasi acara; costing per penjualan (salin & sesuaikan) | Modul **Sales Order** (B2B): Company, detail harga per peserta, harga paket, aturan jumlah peserta `[ARH-4]`, `[JLR-2]` |
| Simulasi per peserta (sama dengan Master Program) | Mandays `[ARH-1]`, `[JLR-1]`; diskon `[TDK-1]` |
| Status Draft → Final, atau Batal | As Is vs Aktual `[REV-3]`; approval `[QT-16]` |

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
| UC-22 | Menyusun Penjualan Program | *TBD (Q-PJ-02)* | Penjualan Program | Penjualan | Master Program Final, jenis (Public / B2B), tanggal & lokasi acara, durasi, target peserta, harga jual / peserta, target profit, costing | Aktor memilih Master Program Final dan jenis Public atau B2B. Sistem menyalin durasi, target peserta, harga jual standar, target profit, dan costing dari Master Program. Aktor mengisi tanggal & lokasi, menyesuaikan angka dan item biaya (semua nominal dapat diedit, termasuk biaya lain). Form dan simulasi sama untuk kedua jenis; simulasi dihitung ulang setiap angka berubah. Aktor menyimpan sebagai Draft. | Penjualan Program Draft dengan Nomor Penjualan | Penjualan Program, Biaya Penjualan Program, Master Program, Rate Fixed Cost, Rate Variable Cost, Satuan | PJ-FR-01 – PJ-FR-10 |
| UC-29 | Memfinalkan Penjualan Program | *TBD (Q-PJ-02)* | Penjualan Program | Penjualan | Penjualan Program berstatus Draft | Aktor menekan "Finalkan". Sistem menampilkan popup ringkasan (jenis, durasi, target peserta, harga jual / peserta, pendapatan, HPP, profit, titik impas) dan peringatan bila profit minus. Setelah dikonfirmasi status menjadi Final dan terkunci; Public siap masuk ke Quotation, B2B ke Sales Order (modul berikutnya). | Penjualan Program Final, terkunci | Penjualan Program | PJ-FR-14 – PJ-FR-16 |
| UC-26 | Membatalkan Penjualan Program | *TBD (Q-PJ-02)* | Penjualan Program | Penjualan | Penjualan Program berstatus Draft | Aktor memilih Batalkan dan mengonfirmasi. Status menjadi Batal dan tidak dapat diubah. | Penjualan Program berstatus Batal | Penjualan Program | PJ-FR-14, PJ-FR-17 |

> Semua UC belum lolos uji admission no. 1 (aktor bernama) — Q-PJ-02.
> Dihapus dan tidak dipakai ulang: UC-24, UC-25, UC-27 (draf 3); UC-28 (draf 4, `[REV-3]`); **UC-23** Menyusun Penjualan Program B2B (draf 8 — digabung ke UC-22 karena form sama `[JLR-1]`).

---

## 4. Kebutuhan Fungsional (FR)

### 4.1 Penjualan Program

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-01 | **Daftar Penjualan Program**: No, Nomor, Jenis (Public/B2B), Master Program (judul + nomor), Pelaksanaan (tanggal & lokasi), Target peserta, Harga jual / peserta, Status, Action (menu gear). Dapat disaring per jenis dan status. | `[QT-1]` + `[USULAN]` (kolom daftar) |
| PJ-FR-02 | Penjualan Program dibuat dengan memilih **Master Program berstatus Final**. Master Program Draft/Batal tidak muncul. | `[QT-1]`, `[QT-FIELD]` |
| PJ-FR-03 | Pengguna memilih **jenis penjualan** (Public atau B2B) **saat membuat** Penjualan Program. Jenis hanya menentukan jalur setelah Final (PJ-FR-16); **form, field, dan simulasi sama** untuk kedua jenis. Jenis tidak dapat diubah setelah disimpan. | `[QT-2]`, `[JLR-1]`, `[JLR-3]` + `[USULAN]` (jenis terkunci) |
| PJ-FR-04 | Saat Master Program dipilih, sistem **menyalin** durasi, target peserta, harga jual standar / peserta, target profit, dan semua item costing (termasuk biaya lain). | `[QT-1]`, `[QT-5]`, `[JLR-2]` |
| PJ-FR-05 | Semua angka hasil salinan **dapat diedit**: durasi, target peserta, harga jual / peserta, target profit, nominal semua item biaya, menambah/menghapus item dari Master Rate, dan menambah biaya lain. Perubahan hanya berlaku di Penjualan Program itu dan **tidak mengubah Master Program**. | `[QT-5]`, `[USR-3M]`, `[K3M-2]`, `[REV-1]`, `[JLR-2]` |
| PJ-FR-06 | Penjualan Program memuat **tanggal pelaksanaan** (mulai, selesai) dan **lokasi acara**. | `[USR-3M]` + `[USULAN]` (nama & format field, Q-PJ-03) |
| PJ-FR-07 | **Nomor Penjualan** otomatis saat pertama disimpan (format Q-PJ-08). | `[USULAN]` |
| PJ-FR-08 | Costing memakai model dan layout yang sama dengan Master Program: Fixed Cost per Event/Day, Variable Cost per Peserta/Event dan Peserta/Day, biaya lain. Biaya trainer dihitung per event (1 event = 1 trainer). | `[QT-5]`, `fr-nfr.md` FR-25 |
| PJ-FR-09 | Input harga (Public dan B2B): **Harga jual / peserta**, **Target peserta**, **Target profit** (opsional). Tidak ada skema all-in, harga paket, Company, atau diskon di sini. Calon peserta (Public) dan Company + detail harga per peserta (B2B) diisi di modul berikutnya. | `[QT-6]`, `[ARH-5]`, `[TDK-1]`, `[JLR-2]` |
| PJ-FR-10 | **Simulasi** sama dengan Master Program (MP-FR-10, rumus `fr-nfr-master-program.md` bagian 8), untuk Public dan B2B: (1) **Titik impas (BEP)** — BEP jumlah peserta dan BEP harga jual; (2) **Hasil rencana** — Pendapatan, Biaya (HPP), Profit dan margin %, target profit tercapai/kurang, minimal peserta untuk capai target; (3) **Posisi di skala jumlah peserta**; (4) **Rincian** yang dapat dibuka/tutup, ditulis **sederhana dan mudah dipahami** (langkah hitung dalam bahasa sehari-hari, tabel profit pendek). Peringatan: profit minus, BEP di atas target peserta, profit < target profit. | `[QT-9]`, `[REV-4]`, `[BEP-1]`, `[SIMX]`, `[JLR-1]`, `[JLR-3]` |

> PJ-FR-11 – PJ-FR-13 (Company, skema harga, perhitungan B2B) **dihapus** pada draf 8 `[JLR-2]` dan tidak dipakai ulang; isinya menjadi bahan modul Sales Order (bagian 4.3).

### 4.2 Status

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-14 | Status: **Draft → Final**, atau **Batal** (bagian 6). Penjualan baru tersimpan Draft; selama Draft semua isian dapat diubah. Tidak ada approval. | `[ARH-4]`, `[QT-16]` |
| PJ-FR-15 | Aksi **"Finalkan"** memunculkan popup ringkasan (jenis, durasi, target peserta, harga jual / peserta, pendapatan, HPP, profit, titik impas) dan peringatan bila profit minus. Setelah dikonfirmasi status **Final** dan Penjualan Program **terkunci**. | `[ARH-4]`, `[QT-12]` (pola popup) |
| PJ-FR-16 | Penjualan Program **Final**: Public **masuk ke modul Quotation** (boleh banyak quotation), B2B **masuk ke modul Sales Order** (satu SO). Kedua modul belum dikerjakan; sampai ada, Final hanya mengunci dan menandai "→ Quotation" / "→ Sales Order". | `[ARH-4]`, `[JLR-2]` |
| PJ-FR-17 | Penjualan Program **Draft** dapat **dibatalkan** (status Batal, tidak dapat diubah). Pembatalan setelah Final: Q-PJ-12. | `[QT-12]` (pola batal) + `[USULAN]` |

> PJ-FR-18 dan PJ-FR-19 (As Is vs Penjualan, laporan) **dihapus** pada draf 4 `[REV-3]` dan tidak dipakai ulang.

### 4.3 Catatan untuk modul berikutnya (ditunda, bukan cakupan saat ini)

| Modul | Yang sudah disampaikan | Sumber |
|---|---|---|
| Quotation (Public) | Dibagikan ke **banyak** calon peserta dari satu Penjualan Program; tidak terikat company; diskon per peserta, Deal/Batal per peserta, surat quotation, realisasi peserta fix. | `[JLR-2]`, `[ARH-5]` |
| Sales Order (B2B) | **Satu** SO per Penjualan Program; terikat **satu Company**; memuat **detail harga per peserta** (harga per peserta × jumlah peserta = harga paket); jumlah peserta default 10 — batas minimal 10 masih ditanyakan (Q-PJ-15). Usulan analis: status Draft → Dikirim (surat penawaran) → Deal / Batal. | `[JLR-2]`, `[JLR-3]`, `[K3M-3]` + `[USULAN]` (status SO) |

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
| Q-PJ-02 | Siapa pengguna yang menyusun, memfinalkan, dan membatalkan Penjualan Program? | UC-22, UC-26, UC-29 |
| Q-PJ-03 | Tanggal & lokasi acara: cukup tanggal mulai–selesai dan lokasi teks bebas? Durasi dihitung dari tanggal atau tetap diisi terpisah? | PJ-FR-06 |
| Q-PJ-08 | Format **Nomor Penjualan**; berbeda untuk Public dan B2B? | PJ-FR-07 |
| Q-PJ-12 | Apakah Penjualan Program yang sudah **Final** boleh dibatalkan (mis. bila quotation / sales order tidak jadi)? | PJ-FR-17 |
| Q-PJ-14 | Bagaimana konsep **As Is vs Aktual** yang dimaksud — angka apa dibandingkan dengan apa, dan di modul mana? *(usulan analis: As Is = simulasi Penjualan Program, Aktual = hitung ulang dengan peserta riil di Quotation / SO)* | — |
| Q-PJ-15 | Sales Order B2B: apakah **minimal 10 peserta** masih berlaku (dicek di detail SO), atau cukup default 10? | Modul Sales Order (ditunda) |
| Q-PJ-16 | Sales Order B2B: setuju status **Draft → Dikirim → Deal / Batal** (surat penawaran dicetak dari SO), atau ada dokumen penawaran terpisah sebelum SO? | Modul Sales Order (ditunda) |

**Sudah terjawab** (23-10-2026): item Fix / mode harga tidak ada lagi, semua nominal dapat diedit (`[K3M-2]`, `[REV-1]`); diskon (Public & B2B) ditunda (`[TDK-1]`); setelah Final → Quotation / Sales Order (`[ARH-4]`). **(25-10-2026, `[JLR-n]`)**: Penjualan Program sama untuk kedua jenis; jenis dipilih saat membuat Penjualan Program; Mandays tidak dipakai (Q-PJ-04 gugur); dokumen B2B = Sales Order. **Ditunda ke modul Quotation** `[ARH-5]`: diskon per peserta, Deal/Batal per peserta, surat quotation (Q-PJ-11), realisasi peserta fix.

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
| draf 8 | 25-10-2026 | `input/2026-10-25-jalur-public-b2b/`: **form & simulasi sama** untuk Public dan B2B; jenis hanya penanda jalur (PJ-FR-03). Company, skema all-in, harga paket, jumlah peserta min 10 keluar ke **Sales Order** (ditunda) — PJ-FR-11 – PJ-FR-13 dihapus, bagian 4.3 baru. UC-23 digabung ke UC-22. Target peserta disalin dari Master Program (default 10). Rincian simulasi disederhanakan (PJ-FR-10). Q-PJ-04 gugur; Q-PJ-15, Q-PJ-16 baru. |
