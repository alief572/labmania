# Kebutuhan Fungsional & Non-Fungsional — Modul Quotation Labmania

- **Versi:** draf 1 — 28-10-2026
- **Sumber:** `input/Master - Buat Program/2026-10-28-quotation-company-personal/` (tampilan acuan `input/labmania.html`, panel Quotation); keputusan terdahulu `input/Master - Buat Program/2026-10-25-quotation-so-persiapan-invoice/`, `input/Master - Buat Program/2026-10-25-surat-penawaran-rencana-aktual/`, `input/Master - Buat Program/2026-10-23-tanpa-diskon/`, `input/Master - Buat Program/2026-10-28-jawaban-pertanyaan-program/`
- **Status:** DRAF — pertanyaan terbuka di bagian 7
- **Modul terkait:** hulu Penjualan Program → `docs/fr-nfr-penjualan-program.md`; Master Data → `docs/fr-nfr.md` (Master Peserta, Master Company, Master User)
- **Mockup:** `ui-mockup/produk/index.html` → modul Quotation (draf 11l)
- **ERD:** belum dibuat (bagian 7, Q-QTN-04)

Penomoran memakai awalan **QTN-**.

| Penanda | Arti |
|---|---|
| `[QCP-n]` | `input/Master - Buat Program/2026-10-28-quotation-company-personal/` — kalimat n: (1) Public & B2B masuk Quotation, (2) quotation ke Company atau Personal, (3) Company → peserta dari company yang sama, (4) Personal → pilih peserta |
| `[LMH]` | tampilan acuan `input/labmania.html` |
| `[INV-1]` | Quotation = persiapan invoice, peserta sudah fix |
| `[SPA-2]` | harga jual tidak berubah; selisih peserta → laporan Rencana vs Aktual |
| `[TDK-1]` | tanpa diskon |
| `[JPP-15]` | Deal/Batal per peserta masih ada |
| `[STD]` | standar proyek di `AGENTS.md` |
| `[USULAN]` | usulan analis, **belum** dinyatakan klien |

---

## 1. Ringkasan Kebutuhan

Penjualan Program berstatus **Final** — **Public maupun B2B** — dibuatkan **Quotation** `[QCP-1]`.
Quotation adalah **persiapan invoice**: peserta yang dicantumkan sudah fix `[INV-1]`. Satu Penjualan
Program dapat punya **banyak** quotation.

Setiap quotation ditujukan ke salah satu `[QCP-2]`:

| | **Company** | **Personal** |
|---|---|---|
| Kepada | Company dari Master Company + u.p. | Satu peserta |
| Peserta | Dipilih (centang) dari peserta yang **terdaftar di company itu** `[QCP-3]` | **Satu** peserta dari Master Peserta `[QCP-4]` + `[USULAN]` (satu) |
| Penjualan B2B | **Wajib** Company, terkunci ke Company Penjualan Program `[USULAN]` | Tidak tersedia `[USULAN]` |

```
Penjualan Program Final (Public / B2B) ──► Quotation (Draft → Terkirim → Selesai)  ──► Invoice (menyusul)
                                            per peserta: Menunggu → Deal / Batal
```

---

## 2. Aktor

| Aktor | Peran | Sumber |
|---|---|---|
| Manajemen | Membuat, mengirim/mencetak, menandai Deal/Batal, membatalkan quotation | `[JPP-11]` (sementara, sama dengan Penjualan Program) |
| Sales / marketing | Dicatat pada quotation (dipilih dari Master User) | `[LMH]` |

---

## 3. Tabel Use Case

| Kode | Use case | Aktor | Prasyarat | Alur singkat | Hasil |
|---|---|---|---|---|---|
| UC-33 | Membuat Quotation | Manajemen | Ada Penjualan Program Final | Pilih Penjualan Program, isi tanggal & sales, pilih tujuan Company/Personal, pilih peserta, simpan | Quotation Draft dengan nomor |
| UC-34 | Mengirim / mencetak Quotation | Manajemen | Quotation Draft | Buka pratinjau surat, Kirim & Cetak | Status Terkirim; peserta Menunggu |
| UC-35 | Mencatat Deal / Batal peserta | Manajemen | Quotation Terkirim / Selesai | Tandai tiap peserta Deal, Batal, atau Menunggu | Peserta Deal = peserta aktual; Selesai bila tidak ada yang Menunggu |
| UC-36 | Membatalkan Quotation | Manajemen | Quotation Draft | Konfirmasi batal | Status Batal; pesertanya dapat dipakai di quotation lain |

---

## 4. Kebutuhan Fungsional (FR)

| ID | Kebutuhan | Sumber |
|---|---|---|
| QTN-FR-01 | Menu: modul **Quotation**, menu **Daftar Quotation**. Daftar: No, Nomor (+ tanggal & berlaku), Penjualan Program (judul, jenis, nomor, tanggal acara), Kepada (tag Company/Personal, nama, u.p.), Peserta (jumlah + nama dengan status), Nilai (grand total, harga / peserta, PPN), Status (+ jumlah Deal / Menunggu), Action (gear). Kartu ringkas Draft / Terkirim / Selesai / Batal yang dapat diklik untuk menyaring; filter tujuan dan status; pencarian nomor, kepada, peserta, program. | `[LMH]` + `[USULAN]` (kolom, kartu) |
| QTN-FR-02 | Quotation dibuat dari **Penjualan Program Final**, Public atau B2B; Draft/Batal tidak dapat dipilih. Dari daftar Penjualan Program, Penjualan Final punya aksi **"+ Buat Quotation"** yang membuka form dengan Penjualan Program terisi. | `[QCP-1]` |
| QTN-FR-03 | **Header**: Nomor Quotation (otomatis, sementara `QT-YYMM-NNN`, Q-QTN-01), **Tanggal quotation** (wajib, default hari ini), Berlaku sampai (default +30 hari; tidak boleh sebelum tanggal quotation), **Sales / marketing** (wajib, dari Master User aktif), Sumber informasi (Sales, Media sosial, Website, Referensi / repeat order, Lainnya). | `[LMH]` + `[USULAN]` (format nomor, sales dari Master User) |
| QTN-FR-04 | **Tujuan** wajib: **Company** atau **Personal**. Company: **Company** wajib (Master Company aktif), **u.p.** (default PIC company), Alamat (default alamat + kota company, dapat diubah). Personal: tanpa company; Alamat default kota + provinsi peserta. Penjualan **B2B**: tujuan otomatis Company dan terkunci ke Company Penjualan Program. | `[QCP-2]`, `[QCP-3]` + `[USULAN]` (B2B terkunci, default u.p./alamat) |
| QTN-FR-05 | **Peserta**: Company → daftar centang **hanya peserta Master Peserta yang terdaftar di company itu** (tombol "Pilih semua"), minimal 1; Personal → pilih **satu** peserta dari Master Peserta. Peserta yang sudah ada di quotation lain (bukan Batal, peserta tidak Batal) untuk Penjualan Program yang sama **tidak dapat dipilih**. | `[QCP-3]`, `[QCP-4]` + `[USULAN]` (satu peserta, larangan ganda) |
| QTN-FR-06 | **Harga**: harga / peserta = harga jual Penjualan Program Final, **tidak dapat diubah**, tanpa diskon. Subtotal = jumlah peserta × harga; **PPN 11%** dapat dicentang (default dicentang); Grand total = subtotal + PPN. Form menampilkan target Penjualan Program, peserta di quotation lain, dan di quotation ini; selisih dengan target tidak menghalangi simpan. | `[SPA-2]`, `[TDK-1]`, `[LMH]` (PPN) + `[USULAN]` |
| QTN-FR-07 | **Status** (bagian 6): Draft (dapat diedit, dibatalkan, dihapus) → **Kirim / Cetak** (pratinjau surat: nomor, tanggal, berlaku, kepada, u.p., alamat, program, pelaksanaan, durasi, tabel peserta & harga, subtotal, PPN, grand total, sales) → **Terkirim**, tidak dapat diedit. Terkirim/Selesai dapat dicetak ulang. | `[LMH]` + `[USULAN]` |
| QTN-FR-08 | **Deal / Batal per peserta**: pada quotation Terkirim/Selesai, tiap peserta ditandai Menunggu / Deal / Batal (ada "Semua Deal", "Semua Batal"). Quotation **Selesai** otomatis bila tidak ada peserta Menunggu; kembali Terkirim bila diubah ke Menunggu. Peserta **Deal** = peserta aktual untuk laporan Rencana vs Aktual (PJ-FR-21) dan jumlah "deal" di daftar Penjualan Program. | `[JPP-15]`, `[SPA-2]` + `[USULAN]` (status Selesai) |

Perubahan di Master Data (lihat `docs/fr-nfr.md` FR-16): Master Peserta punya field **Company** (opsional,
Master Company aktif); bila diisi, Instansi = nama company. Peserta yang sudah ada di quotation tidak
dapat dihapus. `[QCP-3]` + `[USULAN]`

---

## 5. Kebutuhan Non-Fungsional (NFR)

| ID | Kategori | Kebutuhan | Sumber |
|---|---|---|---|
| QTN-NFR-01 | Audit | Simpan, ubah, kirim, Deal/Batal peserta, batal, hapus dicatat `write_log()` modul "Quotation"; ubah dengan `old`/`new` dan `detail_changes` per peserta. | `[STD]` |
| QTN-NFR-02 | Integritas data | Harga diambil dari Penjualan Program Final di server, bukan dari isian klien; quotation tidak mengubah Penjualan Program. | `[SPA-2]` + `[USULAN]` |
| QTN-NFR-03 | Integritas data | Larangan peserta ganda (QTN-FR-05) dan kecocokan company peserta diperiksa di server dalam transaksi. | `[USULAN]` |
| QTN-NFR-04 | Keamanan | Menu & aksi mengikuti permission aplikasi. | `[USULAN]` |
| QTN-NFR-05 | Platform | CI3 HMVC, MySQL, Bootstrap + jQuery; nama tabel/kolom = ERD. Tidak memakai `tr_penawaran` (quotation lama aplikasi) sebelum diputuskan (Q-QTN-04). | `[STD]` |

---

## 6. Status Quotation

| Dari | Aksi | Ke | Efek |
|---|---|---|---|
| — | Simpan baru | Draft | Nomor dibuat |
| Draft | Kirim / Cetak | Terkirim | Terkunci; semua peserta Menunggu |
| Draft | Batalkan | Batal | Peserta bebas dipakai quotation lain |
| Draft | Hapus | — | Data dihapus |
| Terkirim | Tandai peserta (tidak ada Menunggu) | Selesai | — |
| Selesai | Ubah peserta ke Menunggu | Terkirim | — |

Status peserta: **Menunggu → Deal / Batal** (dapat diubah selama quotation Terkirim/Selesai).

---

## 7. Pertanyaan Terbuka

| ID | Pertanyaan | Mempengaruhi |
|---|---|---|
| Q-QTN-01 | Format final nomor Quotation (sementara `QT-YYMM-NNN`). | QTN-FR-03 |
| Q-QTN-02 | Quotation Personal hanya satu peserta (usulan), atau boleh beberapa? | QTN-FR-05 |
| Q-QTN-03 | **Sales Order** masih dipakai (mis. setelah Deal, sebelum invoice) atau digantikan Quotation untuk B2B? PPN 11% tetap ada? | `fr-nfr-penjualan-program.md` 4.4 |
| Q-QTN-04 | Tabel baru (`quotation`, `quotation_peserta`) atau memakai tabel lama `tr_penawaran`; kolom `ms_peserta.id_customer` untuk company peserta. | ERD |

---

## 8. Riwayat Perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| draf 1 | 28-10-2026 | Penyusunan awal dari `input/Master - Buat Program/2026-10-28-quotation-company-personal/` dan `input/labmania.html`: Public & B2B → Quotation; tujuan Company/Personal; peserta dari company yang sama; Deal/Batal per peserta; mockup draf 11l. |
