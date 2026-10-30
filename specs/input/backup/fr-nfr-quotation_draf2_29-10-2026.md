# Kebutuhan Fungsional & Non-Fungsional — Modul Quotation Labmania

- **Versi:** draf 2 — 29-10-2026
- **Sumber:** `input/Master - Buat Program/2026-10-29-quotation-diskon-aksi/` (tampilan index acuan `input/labmania.html`, panel Quotation); `input/Master - Buat Program/2026-10-28-quotation-company-personal/`; keputusan terdahulu `input/Master - Buat Program/2026-10-25-quotation-so-persiapan-invoice/`, `input/Master - Buat Program/2026-10-25-surat-penawaran-rencana-aktual/`, `input/Master - Buat Program/2026-10-28-jawaban-pertanyaan-program/`
- **Status:** DRAF — pertanyaan terbuka di bagian 7
- **Modul terkait:** hulu Penjualan Program → `docs/fr-nfr-penjualan-program.md`; Master Data → `docs/fr-nfr.md` (Master Peserta, Master Company, Master User)
- **Mockup:** `ui-mockup/produk/index.html` → modul Quotation (draf 11m)
- **ERD:** belum dibuat (bagian 7, Q-QTN-04)
- **Draf sebelumnya:** `input/backup/fr-nfr-quotation_draf1_28-10-2026.md`

Penomoran memakai awalan **QTN-**.

| Penanda | Arti |
|---|---|
| `[QDA-n]` | `input/Master - Buat Program/2026-10-29-quotation-diskon-aksi/` — butir n: (1) Public & B2B → Quotation, (2) Company/Personal, peserta baru dari form, company peserta ikut, (3) diskon Rupiah, (4) Public berkali-kali, index seperti `labmania.html`, (5) empat aksi |
| `[QCP-n]` | `input/Master - Buat Program/2026-10-28-quotation-company-personal/` — kalimat n: (3) Company → peserta dari company yang sama |
| `[LMH]` | tampilan acuan `input/labmania.html` |
| `[INV-1]` | Quotation = persiapan invoice, peserta sudah fix |
| `[SPA-2]` | harga jual tidak berubah; selisih peserta → laporan Rencana vs Aktual |
| `[JPP-15]` | Deal/Batal per peserta masih ada |
| `[STD]` | standar proyek di `AGENTS.md` |
| `[USULAN]` | usulan analis, **belum** dinyatakan klien |

---

## 1. Ringkasan Kebutuhan

Penjualan Program berstatus **Final** — **Public maupun B2B** — dibuatkan **Quotation** `[QDA-1]`.
Quotation adalah **persiapan invoice** `[INV-1]`. Penjualan **Public** dapat dibuatkan quotation
**berkali-kali** `[QDA-4]`; penjualan **B2B** satu quotation aktif `[USULAN]`.

Setiap quotation ditujukan ke salah satu `[QDA-2]`:

| | **Company** | **Personal** |
|---|---|---|
| Kepada | Company dari Master Company + u.p. | Peserta (pertama) yang dipilih `[USULAN]` |
| Peserta | Dicentang dari peserta yang **terdaftar di company itu** `[QCP-3]` | **Satu atau lebih** dari Master Peserta; peserta baru dapat diisi di form dan otomatis masuk Master Peserta; company/instansi peserta ikut tampil `[QDA-2]` |
| Penjualan B2B | **Wajib** Company, terkunci ke Company Penjualan Program `[USULAN]` | Tidak tersedia `[USULAN]` |

```
Penjualan Program Final (Public / B2B)
   └─► Quotation: Draft ──Kirim & Cetak──► Terkirim ──Deal──► Deal ──► Confirmation Letter
                    │                         │                      └─► Invoice (menyusul)
                    └──────── Batalkan ───────┴──► Batal
```

---

## 2. Aktor

| Aktor | Peran | Sumber |
|---|---|---|
| Manajemen | Membuat, mengirim/mencetak, menandai Deal, membatalkan quotation, mencetak Confirmation Letter | `[JPP-11]` (sementara, sama dengan Penjualan Program) |
| Sales / marketing | Dicatat pada quotation (dipilih dari Master User) | `[LMH]` |

---

## 3. Tabel Use Case

| Kode | Use case | Aktor | Prasyarat | Alur singkat | Hasil |
|---|---|---|---|---|---|
| UC-33 | Membuat Quotation | Manajemen | Ada Penjualan Program Final | Pilih Penjualan Program, isi tanggal & sales, pilih Company/Personal, pilih atau isi peserta baru, isi diskon, simpan | Quotation Draft dengan nomor; peserta baru tersimpan di Master Peserta |
| UC-34 | Kirim dan Cetak Quotation | Manajemen | Quotation Draft / Terkirim | Buka pratinjau surat, Kirim & Cetak | Status Terkirim (menunggu deal) |
| UC-35 | Menandai Deal | Manajemen | Quotation Terkirim | Centang peserta yang deal (default semua), simpan | Status Deal; peserta deal = peserta aktual |
| UC-36 | Membatalkan Quotation | Manajemen | Quotation Draft / Terkirim | Konfirmasi batal | Status Batal; pesertanya dapat dipakai di quotation lain |
| UC-37 | Mencetak Confirmation Letter | Manajemen | Quotation Deal | Buka pratinjau surat konfirmasi kehadiran, cetak | Surat tercetak (format menyusul) |

---

## 4. Kebutuhan Fungsional (FR)

### 4.1 Daftar (index)

| ID | Kebutuhan | Sumber |
|---|---|---|
| QTN-FR-01 | Menu: modul **Quotation**, menu **Daftar Quotation**. Index **dikelompokkan per Penjualan Program Final**, mengikuti `labmania.html`: tab **On going** / **Selesai** (Selesai = tanggal selesai acara sudah lewat `[USULAN]`); kartu alur **Draft › Terkirim › Deal** dan **Batal** (jumlah quotation, peserta, nilai Deal, tingkat deal) yang dapat diklik untuk menyaring; pencarian nomor, kepada, peserta, program. Baris per Penjualan Program: Penjualan Program (nomor, jenis, judul, tanggal & lokasi), **Peserta deal** / target (+ menunggu), **Progres** (bar deal & menunggu terhadap target, tanda BEP, teks "Lewat BEP" / "kurang n"), **Quotation** (jumlah + rincian per status), aksi **Lihat detail** dan **+ Quotation**. | `[QDA-4]`, `[LMH]` + `[USULAN]` (arti tab Selesai) |
| QTN-FR-02 | **Detail** satu Penjualan Program: kembali ke daftar, ringkasan program dan harga / peserta, bar progres besar (deal, menunggu, target, BEP), tombol **+ Quotation**, dan tabel quotation: Nomor (tanggal, berlaku, sales), Kepada (tag Company/Personal, nama, u.p.), Peserta (jumlah + nama dengan company/instansi), Nilai (grand total, harga / peserta setelah diskon, PPN), Status, Action (gear, QTN-FR-09). | `[LMH]` |
| QTN-FR-03 | **+ Quotation**: Penjualan **Public** selalu dapat ditambah quotation `[QDA-4]`. Penjualan **B2B** hanya bila belum ada quotation selain Batal `[USULAN]`. Dari daftar Penjualan Program, Penjualan Final juga punya aksi **"+ Buat Quotation"**. | `[QDA-4]` + `[USULAN]` (B2B) |

### 4.2 Form

| ID | Kebutuhan | Sumber |
|---|---|---|
| QTN-FR-04 | **Header**: Penjualan Program (wajib, hanya Final), Nomor Quotation (otomatis, sementara `QT-YYMM-NNN`, Q-QTN-01), **Tanggal quotation** (wajib, default hari ini), Berlaku sampai (default +30 hari; tidak boleh sebelum tanggal quotation), **Sales / marketing** (wajib, Master User aktif), Sumber informasi (Sales, Media sosial, Website, Referensi / repeat order, Lainnya). | `[LMH]` + `[USULAN]` (format nomor, sales dari Master User) |
| QTN-FR-05 | **Quotation ke** wajib: **Company** atau **Personal** `[QDA-2]`. Company: **Company** wajib (Master Company aktif), **u.p.** (default PIC company), Alamat (default alamat + kota company). Personal: tanpa field Company; Kepada = peserta pertama, Alamat default kota + provinsi peserta pertama `[USULAN]`. Penjualan **B2B**: tujuan otomatis Company dan terkunci ke Company Penjualan Program `[USULAN]`. Alamat dapat diubah untuk surat ini. | `[QDA-2]` + `[USULAN]` |
| QTN-FR-06 | **Peserta — Company**: daftar centang **hanya peserta Master Peserta yang terdaftar di company itu** (tombol "Pilih semua"), minimal 1. | `[QCP-3]` |
| QTN-FR-07 | **Peserta — Personal**: pilih **satu atau lebih** peserta dari Master Peserta (tambah baris, hapus baris), minimal 1. Tiap peserta yang dipilih menampilkan **Company / Instansi**, kode, telepon, dan email dari Master Peserta `[QDA-2]`. Tombol **"+ Peserta baru"** membuka isian di dalam form quotation dengan field Master Peserta (FR-16 `docs/fr-nfr.md`; semua wajib kecuali Company) — saat **Tambahkan**, peserta **langsung tersimpan ke Master Peserta** dengan Kode Peserta baru dan masuk daftar quotation ini, walau quotation tidak jadi disimpan `[QDA-2]` + `[USULAN]` (field mengikuti FR-16, saat simpan). Peserta yang sudah ada di quotation lain (selain Batal; peserta tidak Batal) untuk Penjualan Program yang sama **tidak dapat dipilih** — berlaku juga untuk Company. | `[QDA-2]` + `[USULAN]` (larangan ganda) |
| QTN-FR-08 | **Harga**: harga / peserta = harga jual Penjualan Program Final, **tidak dapat diubah** `[SPA-2]`. **Diskon dalam Rupiah saja, tanpa %** `[QDA-3]`, diisi **per peserta** `[USULAN]` (mengikuti `labmania.html`), 0 ≤ diskon ≤ harga / peserta. Subtotal = jumlah peserta × harga; Diskon = jumlah peserta × diskon; DPP = subtotal − diskon; **PPN 11%** dapat dicentang (default dicentang), dihitung dari DPP; Grand total = DPP + PPN. Peringatan (tidak menghalangi) bila harga setelah diskon di bawah BEP harga Penjualan Program `[LMH]`. Form menampilkan target Penjualan Program, peserta di quotation lain, dan di quotation ini. | `[QDA-3]`, `[SPA-2]`, `[LMH]` + `[USULAN]` (per peserta, PPN dari DPP) |

### 4.3 Aksi

| ID | Kebutuhan | Sumber |
|---|---|---|
| QTN-FR-09 | Menu gear per status (bagian 6). Empat aksi dari klien: **Kirim dan Cetak Quotation**, **Deal**, **Batalkan**, **Confirmation Letter** `[QDA-5]`. Tambahan: **Edit** pada Draft `[USULAN]`; **Lihat** pada Batal. Tidak ada aksi Hapus — quotation yang tidak jadi dibatalkan `[QDA-5]` + `[USULAN]`. | `[QDA-5]` |
| QTN-FR-10 | **Kirim dan Cetak Quotation**: quotation yang sudah jadi dikirim untuk **menunggu deal** `[QDA-5]`. Pratinjau surat (nomor, tanggal, berlaku, kepada, u.p., alamat, program, pelaksanaan, durasi, tabel peserta dengan company/instansi & harga, subtotal, diskon, PPN, grand total, sales). Dari Draft → **Terkirim**, tidak dapat diedit lagi. Pada Terkirim/Deal dapat dicetak ulang. | `[QDA-5]`, `[LMH]` |
| QTN-FR-11 | **Deal**: hanya pada Terkirim. Popup berisi peserta quotation dengan centang **Deal** (default semua dicentang); minimal satu dicentang — bila tidak ada yang deal, gunakan Batalkan. Peserta tidak dicentang = **Batal** dan bebas dipakai di quotation lain `[JPP-15]` + `[USULAN]`. Status → **Deal**. Peserta Deal = peserta aktual untuk laporan Rencana vs Aktual (PJ-FR-21), jumlah "deal" di daftar Penjualan Program, dan progres di index. Nilai Deal dihitung dari peserta Deal. | `[QDA-5]`, `[JPP-15]`, `[SPA-2]` + `[USULAN]` (centang peserta) |
| QTN-FR-12 | **Batalkan**: untuk quotation yang **tidak jadi deal** — Draft atau Terkirim `[QDA-5]`. Konfirmasi; status → **Batal**, tidak dapat diubah; pesertanya dapat dibuatkan quotation baru. | `[QDA-5]` |
| QTN-FR-13 | **Confirmation Letter**: hanya untuk quotation **Deal** `[QDA-5]`. Surat **konfirmasi kehadiran** peserta Deal; memuat ketentuan bahwa peserta yang tidak hadir **dikenai denda**. **Format surat menyusul** (Q-QTN-05); mockup menampilkan kerangka sementara: nomor quotation, kepada, program, pelaksanaan, lokasi, daftar peserta Deal, paragraf denda (placeholder). Dapat dicetak berulang; tidak mengubah status. | `[QDA-5]` + `[USULAN]` (isi kerangka) |

Perubahan di Master Data (lihat `docs/fr-nfr.md` FR-15, FR-16): peserta dapat ditambah dari form Quotation
Personal dan langsung tersimpan di Master Peserta `[QDA-2]`. Peserta yang sudah ada di quotation tidak dapat
dihapus `[USULAN]`.

---

## 5. Kebutuhan Non-Fungsional (NFR)

| ID | Kategori | Kebutuhan | Sumber |
|---|---|---|---|
| QTN-NFR-01 | Audit | Simpan, ubah, kirim & cetak, Deal (peserta deal/batal), batal, cetak Confirmation Letter dicatat `write_log()` modul "Quotation"; ubah dengan `old`/`new` dan `detail_changes` per peserta. Peserta baru dari form Quotation dicatat `write_log()` modul "Master Peserta" aksi Tambah. | `[STD]` |
| QTN-NFR-02 | Integritas data | Harga diambil dari Penjualan Program Final di server, bukan dari isian klien; diskon divalidasi di server (Rupiah, 0 ≤ diskon ≤ harga); quotation tidak mengubah Penjualan Program. | `[SPA-2]`, `[QDA-3]` + `[USULAN]` |
| QTN-NFR-03 | Integritas data | Larangan peserta ganda (QTN-FR-07), kecocokan company peserta (QTN-FR-06), dan satu quotation aktif B2B (QTN-FR-03) diperiksa di server dalam transaksi. | `[USULAN]` |
| QTN-NFR-04 | Keamanan | Menu & aksi mengikuti permission aplikasi. | `[USULAN]` |
| QTN-NFR-05 | Platform | CI3 HMVC, MySQL, Bootstrap + jQuery; nama tabel/kolom = ERD. Tidak memakai `tr_penawaran` (quotation lama aplikasi) sebelum diputuskan (Q-QTN-04). | `[STD]` |

---

## 6. Status Quotation

| Dari | Aksi | Ke | Efek |
|---|---|---|---|
| — | Simpan baru | Draft | Nomor dibuat |
| Draft | Edit | Draft | `[USULAN]` |
| Draft | Kirim dan Cetak Quotation | Terkirim | Terkunci; menunggu deal |
| Terkirim | Kirim dan Cetak Quotation | Terkirim | Cetak ulang |
| Terkirim | Deal (≥ 1 peserta dicentang) | Deal | Peserta dicentang = Deal, lainnya Batal |
| Draft / Terkirim | Batalkan | Batal | Peserta bebas dipakai quotation lain |
| Deal | Confirmation Letter | Deal | Cetak surat, status tetap |
| Deal | Kirim dan Cetak Quotation | Deal | Cetak ulang |
| Batal | Lihat | Batal | — |

Status peserta dalam quotation: **Menunggu** (Draft/Terkirim) → **Deal** / **Batal** (saat aksi Deal), atau
ikut **Batal** bila quotation dibatalkan.

---

## 7. Pertanyaan Terbuka

| ID | Pertanyaan | Mempengaruhi |
|---|---|---|
| Q-QTN-01 | Format final nomor Quotation (sementara `QT-YYMM-NNN`). | QTN-FR-04 |
| Q-QTN-02 | ~~Personal satu peserta atau beberapa?~~ **Terjawab** `[QDA-2]`: boleh lebih dari satu. | — |
| Q-QTN-03 | **Sales Order** masih dipakai (mis. setelah Deal, sebelum invoice) atau digantikan Quotation untuk B2B? | `fr-nfr-penjualan-program.md` 4.4 |
| Q-QTN-04 | Tabel baru (`quotation`, `quotation_peserta`) atau memakai tabel lama `tr_penawaran`; kolom `ms_peserta.id_customer` untuk company peserta. | ERD |
| Q-QTN-05 | **Format Confirmation Letter** (menyusul): kop, nomor surat sendiri atau nomor quotation, besaran/ketentuan denda, tanda tangan. | QTN-FR-13 |
| Q-QTN-06 | Diskon Rupiah **per peserta** (usulan, mengikuti `labmania.html`) atau **total per quotation**? | QTN-FR-08 |
| Q-QTN-07 | Aksi **Deal** untuk seluruh quotation, atau tetap memilih peserta yang deal (usulan: centang, default semua — menjaga jawaban Deal/Batal per peserta `[JPP-15]`)? | QTN-FR-11 |
| Q-QTN-08 | Penjualan **B2B** boleh lebih dari satu quotation seperti Public? (usulan: satu quotation aktif) | QTN-FR-03 |
| Q-QTN-09 | Tab **Selesai** di index: kapan sebuah Penjualan Program dianggap selesai? (usulan: tanggal selesai acara sudah lewat) | QTN-FR-01 |
| Q-QTN-10 | Quotation **Draft** dapat diedit (usulan), dan apakah perlu dihapus atau cukup dibatalkan? | QTN-FR-09 |

---

## 8. Riwayat Perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| draf 1 | 28-10-2026 | Penyusunan awal dari `input/Master - Buat Program/2026-10-28-quotation-company-personal/` dan `input/labmania.html`: Public & B2B → Quotation; tujuan Company/Personal; peserta dari company yang sama; Deal/Batal per peserta; mockup draf 11l. |
| draf 2 | 29-10-2026 | `input/Master - Buat Program/2026-10-29-quotation-diskon-aksi/`: Personal boleh banyak peserta + isi peserta baru yang langsung masuk Master Peserta, company peserta ikut tampil (QTN-FR-07); **diskon Rupiah** (QTN-FR-08); Public berkali-kali, index per Penjualan Program seperti `labmania.html` (QTN-FR-01 – QTN-FR-03); empat aksi Kirim dan Cetak, Deal, Batalkan, Confirmation Letter (QTN-FR-09 – QTN-FR-13); status Draft → Terkirim → Deal / Batal menggantikan Selesai; Hapus dihapus; UC-37 baru; Q-QTN-02 terjawab, Q-QTN-05 – Q-QTN-10 baru; mockup draf 11m. |
