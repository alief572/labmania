# Kebutuhan Fungsional & Non-Fungsional — Modul Quotation Labmania

- **Versi:** draf 9a — 30-10-2026
- **Sumber:** `input/Master - Buat Program/2026-10-30-allin-b2b-tabel-baru/` (all-in hanya B2B, `tr_penawaran` tidak dipakai); `input/Master - Buat Program/2026-10-30-b2b-allin-diskon-konfirmasi/` (all-in boleh diskon, pecah invoice = all-in ÷ peserta, B2B tanpa Confirmation Letter, minimal 10 saat Deal); `input/Master - Buat Program/2026-10-30-b2b-quotation-allin-min10/` (B2B: skema harga per peserta / all-in, minimal 10 peserta, tanpa Sales Order, tanpa Formulir Konfirmasi, satu quotation); `input/Master - Buat Program/2026-10-29-cetak-quotation-snk-konfirmasi/` (template `input/Template Penawaran Labmania.pdf`, `input/SYARAT & KETENTUAN KEBIJAKAN LAYANAN PEMBELAJARAN.pdf`, `input/Tamplate Formulir Konfirmasi Workshop.doc`); `input/Master - Buat Program/2026-10-29-quotation-diskon-aksi/` (tampilan index acuan `input/labmania.html`, panel Quotation); `input/Master - Buat Program/2026-10-28-quotation-company-personal/`; keputusan terdahulu `input/Master - Buat Program/2026-10-25-quotation-so-persiapan-invoice/`, `input/Master - Buat Program/2026-10-25-surat-penawaran-rencana-aktual/`, `input/Master - Buat Program/2026-10-28-jawaban-pertanyaan-program/`
- **Status:** DRAF — pertanyaan terbuka di bagian 7
- **Modul terkait:** hulu Penjualan Program → `docs/fr-nfr-penjualan-program.md`; Master Data → `docs/fr-nfr.md` (Master Peserta, Master Company, Master User)
- **Mockup:** `ui-mockup/produk/index.html` → modul Quotation (draf 11x)
- **ERD:** `docs/diagrams/erd.drawio` halaman *ERD Quotation* (draf 1) — tabel `quotation`, `quotation_peserta`, `quotation_keterangan`; `ms_peserta.id_customer` di *ERD Master Data*
- **Draf sebelumnya:** `input/backup/fr-nfr-quotation_draf8_30-10-2026.md`

Penomoran memakai awalan **QTN-**.

| Penanda | Arti |
|---|---|
| `[QDA-n]` | `input/Master - Buat Program/2026-10-29-quotation-diskon-aksi/` — butir n: (1) Public & B2B → Quotation, (2) Company/Personal, peserta baru dari form, company peserta ikut, (3) diskon Rupiah, (4) Public berkali-kali, index seperti `labmania.html`, (5) empat aksi |
| `[CQP]` | `input/Master - Buat Program/2026-10-29-cetak-quotation-snk-konfirmasi/` — cetak quotation = PDF Penawaran + S&K + Formulir Konfirmasi, mengikuti tiga template |
| `[QCP-n]` | `input/Master - Buat Program/2026-10-28-quotation-company-personal/` — kalimat n: (3) Company → peserta dari company yang sama |
| `[LMH]` | tampilan acuan `input/labmania.html` |
| `[INV-1]` | Quotation = persiapan invoice, peserta sudah fix |
| `[SPA-2]` | harga jual tidak berubah; selisih peserta → laporan Rencana vs Aktual |
| `[JPP-15]` | Deal/Batal per peserta masih ada |
| `[ATB-n]` | `input/Master - Buat Program/2026-10-30-allin-b2b-tabel-baru/` — jawaban n: (1) all-in hanya B2B, (2) `tr_penawaran` tidak dipakai, tabel baru |
| `[BAD-n]` | `input/Master - Buat Program/2026-10-30-b2b-allin-diskon-konfirmasi/` — jawaban n: (1) all-in boleh diskon, (2) pecah invoice = all-in ÷ peserta (sementara), (3) B2B tanpa Confirmation Letter, (4) minimal 10 juga saat Deal |
| `[B2B-n]` | `input/Master - Buat Program/2026-10-30-b2b-quotation-allin-min10/` — jawaban n: (1) B2B = company, harga per peserta atau all-in 1 company, (2) minimal 10 peserta dari Penjualan Program sampai Quotation, (3) Sales Order tidak ada, (4) B2B tanpa Formulir Konfirmasi, (5) B2B satu quotation, invoice dapat dipecah per peserta |
| `[STD]` | standar proyek di `AGENTS.md` |
| `[USULAN]` | usulan analis, **belum** dinyatakan klien |

---

## 1. Ringkasan Kebutuhan

Penjualan Program berstatus **Final** — **Public maupun B2B** — dibuatkan **Quotation** `[QDA-1]`.
Quotation adalah **persiapan invoice** `[INV-1]`. Penjualan **Public** dapat dibuatkan quotation
**berkali-kali** `[QDA-4]`; penjualan **B2B** **satu quotation** `[B2B-5]` (selain yang Batal `[USULAN]`).
**Sales Order tidak ada** — Quotation satu-satunya dokumen setelah Penjualan Program Final `[B2B-3]`.

Setiap quotation ditujukan ke salah satu `[QDA-2]`:

| | **Company** | **Personal** |
|---|---|---|
| Kepada | Company dari Master Company + u.p. | Peserta (pertama) yang dipilih `[USULAN]` |
| Peserta | Dicentang dari peserta yang **terdaftar di company itu** `[QCP-3]` | **Satu atau lebih** dari Master Peserta; peserta baru dapat diisi di form dan otomatis masuk Master Peserta; company/instansi peserta ikut tampil `[QDA-2]` |
| Penjualan B2B | **Wajib** Company, terkunci ke Company Penjualan Program `[B2B-1]` + `[USULAN]` (terkunci) | Tidak tersedia `[B2B-1]` |

Beda **Public** dan **B2B** di Quotation:

| | **Public** | **B2B** (in-house, satu company) |
|---|---|---|
| Jumlah quotation | Berkali-kali `[QDA-4]` | **Satu** `[B2B-5]` |
| Tujuan | Company atau Personal | Company penjualan `[B2B-1]` |
| Peserta | Minimal 1 | **Minimal 10** `[B2B-2]` |
| Skema harga | Per peserta | **Per peserta** atau **All-in 1 company** `[B2B-1]` |
| Diskon | Rupiah per peserta | Per peserta: Rupiah per peserta; All-in: Rupiah **total** `[BAD-1]` |
| Formulir Konfirmasi / Confirmation Letter | Dicetak | **Tidak** `[B2B-4]`, `[BAD-3]` |
| Invoice (menyusul) | Per quotation | Dapat **dipecah per peserta** bila client minta `[B2B-5]`; all-in: per peserta = (all-in − diskon) ÷ peserta `[BAD-2]` |

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
| UC-33 | Membuat Quotation | Manajemen | Ada Penjualan Program Final (B2B: belum ada quotation selain Batal) | Pilih Penjualan Program, isi tanggal & sales, pilih Company/Personal (B2B: Company penjualan), pilih atau isi peserta baru (B2B ≥ 10), pilih skema harga (B2B: per peserta / all-in), isi diskon, Keterangan, Formulir Konfirmasi (Public), preview, simpan | Quotation Draft dengan nomor; peserta baru tersimpan di Master Peserta |
| UC-34 | Kirim dan Cetak Quotation | Manajemen | Quotation Draft / Terkirim | Buka pratinjau PDF (Penawaran + S&K + Formulir Konfirmasi per peserta; B2B tanpa formulir), Kirim & Cetak | Status Terkirim (menunggu deal) |
| UC-35 | Menandai Deal | Manajemen | Quotation Terkirim | Centang peserta yang deal (default semua; B2B ≥ 10), simpan | Status Deal; peserta deal = peserta aktual |
| UC-36 | Membatalkan Quotation | Manajemen | Quotation Draft / Terkirim | Konfirmasi batal | Status Batal; pesertanya dapat dipakai di quotation lain |
| UC-37 | Mencetak Confirmation Letter | Manajemen | Quotation Deal **Public** (B2B tidak ada `[BAD-3]`) | Buka pratinjau surat konfirmasi kehadiran, cetak | Surat tercetak (format menyusul) |

---

## 4. Kebutuhan Fungsional (FR)

### 4.1 Daftar (index)

| ID | Kebutuhan | Sumber |
|---|---|---|
| QTN-FR-01 | Menu: modul **Quotation**, menu **Daftar Quotation**. Index **dikelompokkan per Penjualan Program Final**, mengikuti `labmania.html`: tab **On going** / **Selesai** (Selesai = tanggal selesai acara sudah lewat `[USULAN]`); kartu alur **Draft › Terkirim › Deal** dan **Batal** (jumlah quotation, peserta, nilai Deal, tingkat deal) yang dapat diklik untuk menyaring; pencarian nomor, kepada, peserta, program. Baris per Penjualan Program: Penjualan Program (nomor, jenis, judul, tanggal & lokasi), **Peserta deal** / target (+ menunggu), **Progres** (bar deal & menunggu terhadap target, tanda BEP, teks "Lewat BEP" / "kurang n"), **Quotation** (jumlah + rincian per status), aksi **Lihat detail** dan **+ Quotation**. | `[QDA-4]`, `[LMH]` + `[USULAN]` (arti tab Selesai) |
| QTN-FR-02 | **Detail** satu Penjualan Program (batch) — **tanpa kartu** `input/Master - Buat Program/2026-10-29-detail-batch-tanpa-kartu/`: judul halaman dengan info program sebagai teks (jenis, judul, tanggal, lokasi, harga / peserta, Company B2B, peserta deal / target, menunggu, BEP); tombol **← Semua Penjualan Program** dan **+ Create Quotation** yang membuka form dengan Penjualan Program batch ini terisi (B2B yang sudah punya quotation aktif: tombol nonaktif, QTN-FR-03); filter tujuan dan **status** (dropdown), pencarian; tabel quotation: Nomor (tanggal, berlaku, sales), Kepada (tag Company/Personal, nama, u.p.), Peserta (jumlah + nama dengan company/instansi), Nilai (grand total, harga / peserta setelah diskon, PPN), Status, Action (gear, QTN-FR-09). | `input/Master - Buat Program/2026-10-29-detail-batch-tanpa-kartu/`, `[LMH]` + `[USULAN]` (info sebagai teks, filter status dropdown) |
| QTN-FR-03 | **+ Quotation**: Penjualan **Public** selalu dapat ditambah quotation `[QDA-4]`. Penjualan **B2B** **satu quotation** `[B2B-5]` — hanya bila belum ada quotation selain Batal `[USULAN]`. Dari daftar Penjualan Program, Penjualan Final juga punya aksi **"+ Buat Quotation"**. | `[QDA-4]`, `[B2B-5]` + `[USULAN]` (boleh baru setelah Batal) |

### 4.2 Form

| ID | Kebutuhan | Sumber |
|---|---|---|
| QTN-FR-04 | **Header**: Penjualan Program (wajib, hanya Final), **Nomor Quotation** otomatis berformat **`LM-I/AM/PW/MM/YY-XXX`** sesuai template `[CQP]` (arti `AM` & reset urutan: Q-QTN-01), **Tanggal quotation** (wajib, default hari ini), Berlaku sampai (default +30 hari; tidak boleh sebelum tanggal quotation), **Sales / marketing** (wajib, Master User aktif — juga penanda tangan Penawaran dan kontak di Formulir Konfirmasi `[USULAN]`), Sumber informasi (Sales, Media sosial, Website, Referensi / repeat order, Lainnya). Catatan bebas digantikan blok **Keterangan** (QTN-FR-17). **Tanpa pilihan cara pembayaran** `input/Master - Buat Program/2026-10-29-tanpa-pilihan-pembayaran/`. | `[LMH]`, `[CQP]`, `input/Master - Buat Program/2026-10-29-tanpa-pilihan-pembayaran/` + `[USULAN]` |
| QTN-FR-05 | **Quotation ke** wajib: **Company** atau **Personal** `[QDA-2]`. Company: **Company** wajib (Master Company aktif), **u.p.** (default PIC company), Alamat (default alamat + kota company). Personal: tanpa field Company; Kepada = peserta pertama, Alamat default kota + provinsi peserta pertama `[USULAN]`. Penjualan **B2B**: tujuan otomatis Company dan terkunci ke Company Penjualan Program `[B2B-1]`. Alamat dapat diubah untuk surat ini. | `[QDA-2]`, `[B2B-1]` + `[USULAN]` |
| QTN-FR-06 | **Peserta — Company**: daftar centang **hanya peserta Master Peserta yang terdaftar di company itu** (tombol "Pilih semua"), minimal 1; **Penjualan B2B minimal 10 peserta** (QTN-FR-21). | `[QCP-3]`, `[B2B-2]` |
| QTN-FR-07 | **Peserta — Personal**: pilih **satu atau lebih** peserta dari Master Peserta (tambah baris, hapus baris), minimal 1. Tiap peserta yang dipilih menampilkan **Company / Instansi**, kode, telepon, dan email dari Master Peserta `[QDA-2]`. Tombol **"+ Peserta baru"** membuka isian di dalam form quotation dengan field Master Peserta (FR-16 `docs/fr-nfr.md`; semua wajib kecuali Company) — saat **Tambahkan**, peserta **langsung tersimpan ke Master Peserta** dengan Kode Peserta baru dan masuk daftar quotation ini, walau quotation tidak jadi disimpan `[QDA-2]` + `[USULAN]` (field mengikuti FR-16, saat simpan). Peserta yang sudah ada di quotation lain (selain Batal; peserta tidak Batal) untuk Penjualan Program yang sama **tidak dapat dipilih** — berlaku juga untuk Company. | `[QDA-2]` + `[USULAN]` (larangan ganda) |
| QTN-FR-08 | **Harga**: harga / peserta = harga jual Penjualan Program Final, **tidak dapat diubah** `[SPA-2]`. **Diskon dalam Rupiah saja, tanpa %** `[QDA-3]`, diisi **per peserta** `[USULAN]` (mengikuti `labmania.html`), 0 ≤ diskon ≤ harga / peserta. Subtotal = jumlah peserta × harga; Diskon = jumlah peserta × diskon; DPP = subtotal − diskon; **PPN 11%** dapat dicentang (default dicentang), dihitung dari DPP; Grand total = DPP + PPN + **Biaya Lain-lain** (Rp, default 0; baris "Biaya Lain-lain" di template) `[CQP]`. Peringatan (tidak menghalangi) bila harga setelah diskon di bawah BEP harga Penjualan Program `[LMH]`. Form menampilkan target Penjualan Program, peserta di quotation lain, dan di quotation ini. | `[QDA-3]`, `[SPA-2]`, `[LMH]` + `[USULAN]` (per peserta, PPN dari DPP) |
| QTN-FR-20 | **Skema harga B2B** `[B2B-1]`: **Per peserta** (default; perhitungan QTN-FR-08) atau **All-in 1 company** — satu **Harga all-in (Rp)** untuk seluruh peserta, wajib > 0, terisi awal harga / peserta × jumlah peserta dan dapat diubah `[USULAN]`. Pada all-in **boleh diskon** `[BAD-1]` — Rupiah **total** quotation, 0 ≤ diskon ≤ harga all-in `[USULAN]`; DPP = harga all-in − diskon; PPN 11% dan Biaya Lain-lain tetap seperti QTN-FR-08; harga / peserta setara = DPP ÷ jumlah peserta (juga dasar pecah invoice per peserta `[BAD-2]`), dengan peringatan (tidak menghalangi) bila di bawah BEP harga. Penawaran mencetak **satu baris** paket (Kts. = jumlah peserta, Harga = harga all-in, Diskon = diskon total, Total = harga all-in) `[USULAN]`. **All-in hanya untuk B2B**; Public selalu per peserta `[ATB-1]`. | `[B2B-1]`, `[BAD-1]`, `[BAD-2]`, `[ATB-1]` + `[USULAN]` (default, diskon total, cetak) |
| QTN-FR-21 | **Minimal 10 peserta B2B** `[B2B-2]`: quotation untuk Penjualan B2B tidak dapat disimpan bila peserta < 10; saat **Deal**, peserta yang dicentang Deal juga minimal 10 `[BAD-4]`. Penjualan Program B2B sendiri wajib target peserta ≥ 10 (PJ-FR-25). | `[B2B-2]`, `[BAD-4]` |

### 4.3 Aksi

| ID | Kebutuhan | Sumber |
|---|---|---|
| QTN-FR-09 | Menu gear per status (bagian 6). Empat aksi dari klien: **Kirim dan Cetak Quotation**, **Deal**, **Batalkan**, **Confirmation Letter** `[QDA-5]`. Tambahan: **Edit** pada Draft `[USULAN]`; **Lihat** pada Batal. Tidak ada aksi Hapus — quotation yang tidak jadi dibatalkan `[QDA-5]` + `[USULAN]`. | `[QDA-5]` |
| QTN-FR-10 | **Kirim dan Cetak Quotation**: quotation yang sudah jadi dikirim untuk **menunggu deal** `[QDA-5]`. Menghasilkan **satu PDF** berisi berurutan: (1) **Penawaran** (QTN-FR-14), (2) **Syarat & Ketentuan** (QTN-FR-15), (3) **Formulir Konfirmasi Keikutsertaan** satu halaman per peserta quotation (QTN-FR-16) `[CQP]` — **tidak untuk B2B** `[B2B-4]`. Pratinjau halaman A4 di layar berlabel **"Preview — belum dikirim"** (Draft), tombol **Tutup** dan **Kirim & Cetak**, serta **Cetak / Simpan PDF** `input/Master - Buat Program/2026-10-29-keterangan-ux-preview/`. Dari Draft → **Terkirim**, tidak dapat diedit lagi. Pada Terkirim/Deal dapat dicetak ulang. | `[QDA-5]`, `[CQP]` |
| QTN-FR-11 | **Deal**: hanya pada Terkirim. Popup berisi peserta quotation dengan centang **Deal** (default semua dicentang); minimal satu dicentang — bila tidak ada yang deal, gunakan Batalkan. Peserta tidak dicentang = **Batal** dan bebas dipakai di quotation lain `[JPP-15]` + `[USULAN]`. Status → **Deal**. Peserta Deal = peserta aktual untuk laporan Rencana vs Aktual (PJ-FR-21), jumlah "deal" di daftar Penjualan Program, dan progres di index. Nilai Deal dihitung dari peserta Deal. | `[QDA-5]`, `[JPP-15]`, `[SPA-2]` + `[USULAN]` (centang peserta) |
| QTN-FR-12 | **Batalkan**: untuk quotation yang **tidak jadi deal** — Draft atau Terkirim `[QDA-5]`. Konfirmasi; status → **Batal**, tidak dapat diubah; pesertanya dapat dibuatkan quotation baru. | `[QDA-5]` |
| QTN-FR-13 | **Confirmation Letter**: hanya untuk quotation **Deal** `[QDA-5]`, **Public** saja — **B2B tidak ada Confirmation Letter** `[B2B-4]`, `[BAD-3]`. Mencetak **Formulir Konfirmasi Keikutsertaan** (format QTN-FR-16, **template formulir quotation**) untuk **peserta Deal** saja; tidak mengubah status. Format diambil dari `input/Tamplate Formulir Konfirmasi Workshop.doc` `[CQP]` + `[USULAN]` (Confirmation Letter = formulir ini, Q-QTN-05). | `[QDA-5]`, `[CQP]` + `[USULAN]` |
| QTN-FR-14 | **Halaman Penawaran** mengikuti `input/Template Penawaran Labmania.pdf` (kode `FR/LMI-TR-01/01/Ed.Rev-1.1`, berlaku 01/01/2025): kop PT LabMania Indonesia Jaya; **Kepada** (nama Company/peserta, alamat); blok **Penawaran** (Nomor, Tanggal — baris Pembayaran di template **tidak dicetak** `input/Master - Buat Program/2026-10-29-tanpa-pilihan-pembayaran/`, Q-QTN-14); tabel **No Catalog** (Nomor Paket Produk), **Item** (judul paket), **Deskripsi** (durasi, tanggal, **waktu**, tempat; + daftar nama peserta `[USULAN]`), **Kts.** (jumlah peserta), **Harga** (per peserta), **Diskon** (Rupiah per peserta — template menulis persentase, tetapi diskon diputuskan Rupiah saja `[QDA-3]`), **Total Harga** (Kts × Harga); **Keterangan**: "Up. Ibu/Bapak" (u.p. Company; Personal = peserta pertama), lalu isi **Keterangan quotation** (QTN-FR-17) per kategori — Syarat keikutsertaan, "Biaya sudah termasuk mendapatkan", "Biaya tidak termasuk", Dokumen persyaratan (butir berawalan "-"; kategori kosong tidak dicetak) — dan Klausul tanda tangan sebagai paragraf; ringkasan **Sub Total, Diskon, PPN (11%), Biaya Lain-lain, Total**; tanda tangan: tanggal + Divisi (Department Master User) + nama sales, dan "Disetujui oleh Pelanggan"; kaki halaman kode dokumen. | `[CQP]` + `[USULAN]` (pemetaan field) |
| QTN-FR-15 | **Halaman Syarat & Ketentuan Kebijakan Layanan Pembelajaran**: teks tetap sesuai `input/SYARAT & KETENTUAN KEBIJAKAN LAYANAN PEMBELAJARAN.pdf` (kode `DP/LMI-TR-01/01/Ed.Rev-1.1`, berlaku 21/10/2024, 2 halaman, tujuh bagian: Pembatalan Kepesertaan, Penarikan dan Pengembalian Uang; Pembayaran; Penggantian Barang Rusak/Hilang; Kerahasiaan; Sertifikat Pelatihan; Lisensi; Umpan Balik, Saran dan Keluhan; pengesahan Operational Director). Perubahan isi = versi dokumen baru (Q-QTN-12). | `[CQP]` + `[USULAN]` (teks tetap) |
| QTN-FR-16 | **Formulir Konfirmasi Keikutsertaan Peserta** (kode `FR/LMI-TR-01/02/Ed.Rev-1.0`, berlaku 01/08/2023), **satu halaman per peserta**, mengikuti **template formulir quotation** (QTN-FR-19; master `docs/fr-nfr.md` FR-51): isian sesuai template + isian tambahan quotation — Nama, Instansi, Alamat Instansi (alamat company atau kota peserta), Nomor WhatsApp, Email terisi dari Master Peserta; isian lain dikosongkan untuk diisi peserta (Q-32); pernyataan 1 dari quotation dengan penanda terisi data Penjualan Program; pernyataan 2 (S&K) tetap; tempat/tanggal, tanda tangan, Jabatan; catatan dari template dengan email & telepon **sales quotation** dan label kegiatan template. **Tidak dicetak untuk B2B / in-house** `[B2B-4]`. | `[CQP]`, `input/Master - Buat Program/2026-10-29-template-formulir-konfirmasi/` + `[USULAN]` (isian otomatis, kontak) |
| QTN-FR-17 | **Keterangan** (langkah 2 form, QTN-FR-18) berupa **daftar centang per kategori** yang dapat dilipat: Syarat keikutsertaan, Termasuk, Tidak termasuk, Dokumen persyaratan. Setiap kategori menampilkan semua butir aktif Template Keterangan (`docs/fr-nfr.md` FR-48) yang **berlaku untuk jenis penjualan**; butir **Default tercentang** otomatis; judul kategori menampilkan ringkasan "x dari y dipilih". Per quotation: **centang / hapus centang**; **+ Tambah teks lain** (isian satu baris, Enter); **✎ ubah teks** butir (butir yang diubah/ditambah ditandai "khusus quotation ini"); **Kembalikan ke default**. **Klausul tanda tangan tidak tampil** di form — dicetak dari butir Default master sesuai jenis penjualan. Teks tercentang disimpan di quotation (salinan), tidak mengubah master; terkunci setelah Terkirim. Mengganti Penjualan Program sebelum ada perubahan mengisi ulang default sesuai jenisnya. | `input/Master - Buat Program/2026-10-29-keterangan-ux-preview/`, `input/Master - Buat Program/2026-10-29-keterangan-quotation-default/` + `[USULAN]` (Klausul dari master saja) |
| QTN-FR-18 | **Form quotation 4 langkah** (B2B **3 langkah** — tanpa Formulir Konfirmasi `[B2B-4]`) dengan penanda langkah yang dapat diklik: **1 Data Quotation** (QTN-FR-04 – QTN-FR-08), **2 Keterangan** (QTN-FR-17), **3 Formulir Konfirmasi** (QTN-FR-19), **4 Preview** — PDF lengkap (Penawaran + S&K + Formulir Konfirmasi per peserta) **sebelum disimpan**, berlabel "Preview — belum disimpan". Tombol bawah: Batal, **← Kembali** (langkah 2–4), **Lanjut →** (langkah 1–3) / **Simpan Draft** (langkah 4; edit: Simpan Perubahan). Langkah 2–4 hanya terbuka bila langkah 1 valid; bila tidak, kembali ke langkah 1 dengan pesan galat. Isian tetap tersimpan saat berpindah langkah. | `input/Master - Buat Program/2026-10-29-keterangan-ux-preview/` + `[USULAN]` (urutan & tombol) |
| QTN-FR-19 | **Formulir Konfirmasi** (langkah 3 form; **tidak ada untuk B2B** `[B2B-4]`): **Template formulir** terpilih otomatis dari **Jenis Produk** paket Penjualan Program (FR-51 "Default untuk Jenis Produk"; bila tidak ada, template aktif pertama), ditandai "— default", dapat diganti. Per quotation: **centang isian** (chip), **+ Tambah isian lain** (Enter; ditandai garis putus), **ubah Pernyataan 1** dengan pratinjau kalimat tercetak langsung di bawahnya, **Kembalikan ke default**. Catatan dan pernyataan 2 tidak tampil — dari template. Ganti template mengganti isian & pernyataan ke isi template itu. Pilihan disimpan di quotation (salinan); mengganti Penjualan Program sebelum ada perubahan memilih ulang default. | `input/Master - Buat Program/2026-10-29-template-formulir-konfirmasi/` + `[USULAN]` |

Perubahan di Master Data (lihat `docs/fr-nfr.md` FR-15, FR-16): peserta dapat ditambah dari form Quotation
Personal dan langsung tersimpan di Master Peserta `[QDA-2]`. Peserta yang sudah ada di quotation tidak dapat
dihapus `[USULAN]`.

---

## 5. Kebutuhan Non-Fungsional (NFR)

| ID | Kategori | Kebutuhan | Sumber |
|---|---|---|---|
| QTN-NFR-01 | Audit | Simpan, ubah, kirim & cetak, Deal (peserta deal/batal), batal, cetak Confirmation Letter dicatat `write_log()` modul "Quotation" (termasuk perubahan Keterangan sebagai `detail_changes` per kategori); ubah dengan `old`/`new` dan `detail_changes` per peserta. Peserta baru dari form Quotation dicatat `write_log()` modul "Master Peserta" aksi Tambah. | `[STD]` |
| QTN-NFR-06 | Dokumen | PDF dibuat server-side dari template HTML/CSS (A4, satu halaman per bagian/peserta) agar identik dengan pratinjau; kode dokumen, nomor halaman, dan tanggal berlaku template dicetak di kaki halaman. | `[CQP]` + `[USULAN]` |
| QTN-NFR-02 | Integritas data | Harga diambil dari Penjualan Program Final di server, bukan dari isian klien; diskon divalidasi di server (Rupiah; per peserta: 0 ≤ diskon ≤ harga / peserta; all-in: 0 ≤ diskon ≤ harga all-in); quotation tidak mengubah Penjualan Program. | `[SPA-2]`, `[QDA-3]`, `[BAD-1]` + `[USULAN]` |
| QTN-NFR-03 | Integritas data | Larangan peserta ganda (QTN-FR-07), kecocokan company peserta (QTN-FR-06), dan satu quotation B2B (QTN-FR-03), dan minimal 10 peserta B2B saat simpan dan saat Deal (QTN-FR-21) diperiksa di server dalam transaksi; harga all-in divalidasi di server (> 0). | `[B2B-2]`, `[B2B-5]` + `[USULAN]` |
| QTN-NFR-04 | Keamanan | Menu & aksi mengikuti permission aplikasi. | `[USULAN]` |
| QTN-NFR-05 | Platform | CI3 HMVC, MySQL, Bootstrap + jQuery; nama tabel/kolom = ERD. Tabel baru `quotation`, `quotation_peserta`, `quotation_keterangan` (ERD Quotation draf 1); **tidak memakai** `tr_penawaran` (quotation lama aplikasi) `[ATB-2]`. | `[STD]`, `[ATB-2]` |

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
| Q-QTN-01 | Nomor dari template: `LM-I/AM/PW/MM/YY-XXX` `[CQP]`. Apa arti segmen **AM** (tetap, atau kode divisi/sales)? Urutan XXX di-reset per bulan, per tahun, atau berlanjut? | QTN-FR-04 |
| Q-QTN-02 | ~~Personal satu peserta atau beberapa?~~ **Terjawab** `[QDA-2]`: boleh lebih dari satu. | — |
| Q-QTN-03 | ~~Sales Order masih dipakai?~~ **Terjawab** `[B2B-3]`: Sales Order tidak ada, Quotation saja. | — |
| Q-QTN-04 | ~~Tabel baru atau `tr_penawaran`?~~ **Terjawab** `[ATB-2]`: `tr_penawaran` tidak dipakai; tabel baru `quotation`, `quotation_peserta`, `quotation_keterangan` + kolom `ms_peserta.id_customer`. | — |
| Q-QTN-05 | ~~Format Confirmation Letter~~ **Terjawab sebagian** `[CQP]`: memakai Formulir Konfirmasi Workshop (QTN-FR-13, QTN-FR-16). Masih perlu: apakah Confirmation Letter setelah Deal tetap dibutuhkan terpisah, atau cukup halaman konfirmasi di PDF quotation? | QTN-FR-13 |
| Q-QTN-06 | Diskon Rupiah **per peserta** (usulan, mengikuti `labmania.html`) atau **total per quotation**? | QTN-FR-08 |
| Q-QTN-07 | Aksi **Deal** untuk seluruh quotation, atau tetap memilih peserta yang deal (usulan: centang, default semua — menjaga jawaban Deal/Batal per peserta `[JPP-15]`)? | QTN-FR-11 |
| Q-QTN-08 | ~~B2B boleh lebih dari satu quotation?~~ **Terjawab** `[B2B-5]`: satu quotation. Masih usulan: quotation baru boleh dibuat setelah yang lama Batal. | QTN-FR-03 |
| Q-QTN-09 | Tab **Selesai** di index: kapan sebuah Penjualan Program dianggap selesai? (usulan: tanggal selesai acara sudah lewat) | QTN-FR-01 |
| Q-QTN-10 | Quotation **Draft** dapat diedit (usulan), dan apakah perlu dihapus atau cukup dibatalkan? | QTN-FR-09 |
| Q-QTN-11 | Kolom **Diskon** di template berupa persentase, sedangkan diskon diputuskan Rupiah saja `[QDA-3]`. Cetak Rupiah per peserta (usulan) atau tampilkan juga persentasenya? | QTN-FR-14 |
| Q-QTN-12 | **S&K** dicetak sebagai teks tetap (usulan), atau perlu dapat diubah lewat aplikasi (master template + versi dokumen)? *(Teks Keterangan Penawaran kini dapat diedit lewat Template Keterangan — QTN-FR-17.)* | QTN-FR-15 |
| Q-QTN-13 | ~~B2B tetap mencetak Formulir Konfirmasi?~~ **Terjawab** `[B2B-4]`: tidak perlu. | — |
| Q-QTN-14 | Template Penawaran memuat baris **Pembayaran** `[Cash In Advance/Termin/Tempo]`. Setelah pilihan pembayaran dihapus, baris itu dihilangkan (usulan) atau dicetak dengan teks tetap? | QTN-FR-14 |
| Q-QTN-15 | ~~All-in boleh diskon? Hanya B2B?~~ **Terjawab** `[BAD-1]`, `[ATB-1]`: boleh diskon (Rupiah total, usulan); hanya B2B. Masih terbuka: di Penawaran dicetak satu baris paket (usulan)? | QTN-FR-20 |
| Q-QTN-16 | ~~Invoice B2B dipecah per peserta~~ **Terjawab sementara** `[BAD-2]` ("coba begitu dulu"): per peserta = (all-in − diskon) ÷ jumlah peserta, dipilih saat membuat invoice. | Modul Invoice (menyusul) |
| Q-QTN-17 | ~~B2B Confirmation Letter?~~ **Terjawab** `[BAD-3]`: tidak ada. | — |
| Q-QTN-18 | ~~Minimal 10 saat Deal?~~ **Terjawab** `[BAD-4]`: ya, peserta Deal ≥ 10. | — |

---

## 8. Riwayat Perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| draf 1 | 28-10-2026 | Penyusunan awal dari `input/Master - Buat Program/2026-10-28-quotation-company-personal/` dan `input/labmania.html`: Public & B2B → Quotation; tujuan Company/Personal; peserta dari company yang sama; Deal/Batal per peserta; mockup draf 11l. |
| draf 2 | 29-10-2026 | `input/Master - Buat Program/2026-10-29-quotation-diskon-aksi/`: Personal boleh banyak peserta + isi peserta baru yang langsung masuk Master Peserta, company peserta ikut tampil (QTN-FR-07); **diskon Rupiah** (QTN-FR-08); Public berkali-kali, index per Penjualan Program seperti `labmania.html` (QTN-FR-01 – QTN-FR-03); empat aksi Kirim dan Cetak, Deal, Batalkan, Confirmation Letter (QTN-FR-09 – QTN-FR-13); status Draft → Terkirim → Deal / Batal menggantikan Selesai; Hapus dihapus; UC-37 baru; Q-QTN-02 terjawab, Q-QTN-05 – Q-QTN-10 baru; mockup draf 11m. |
| draf 2a | 29-10-2026 | `input/Master - Buat Program/2026-10-29-detail-batch-tanpa-kartu/`: detail batch tanpa kartu (kartu alur & kartu ringkasan/bar progres dihapus), tombol **+ Create Quotation** per batch, filter status lewat dropdown (QTN-FR-02). |
| draf 3 | 29-10-2026 | `input/Master - Buat Program/2026-10-29-cetak-quotation-snk-konfirmasi/`: cetak quotation = **satu PDF** Penawaran + S&K + Formulir Konfirmasi per peserta (QTN-FR-10, QTN-FR-14 – QTN-FR-16 baru, QTN-NFR-06); nomor `LM-I/AM/PW/MM/YY-XXX`; field Pembayaran, Catatan, Biaya Lain-lain (QTN-FR-04, QTN-FR-08); Confirmation Letter = Formulir Konfirmasi peserta Deal (QTN-FR-13); Q-QTN-01, -05 diperbarui; Q-QTN-11 – Q-QTN-13 baru; mockup draf 11r. |
| draf 4 | 29-10-2026 | `input/Master - Buat Program/2026-10-29-keterangan-quotation-default/`: blok **Keterangan** dari Template Keterangan Quotation, salin-lalu-edit per quotation (QTN-FR-17 baru); field Catatan dihapus (QTN-FR-04); Keterangan Penawaran per kategori (QTN-FR-14); Q-QTN-12 dipersempit ke S&K; mockup draf 11s. |
| draf 5 | 29-10-2026 | `input/Master - Buat Program/2026-10-29-keterangan-ux-preview/`: form quotation 3 langkah dengan **Preview sebelum disimpan** (QTN-FR-18 baru); Keterangan jadi daftar centang per kategori yang dapat dilipat, Klausul tanda tangan dari master saja (QTN-FR-17); label "Preview — belum dikirim" di Kirim dan Cetak (QTN-FR-10); mockup draf 11t. |
| draf 6 | 29-10-2026 | `input/Master - Buat Program/2026-10-29-template-formulir-konfirmasi/`: tiga template Formulir Konfirmasi (Workshop, Training & Sertifikasi, Resertifikasi) lewat master; langkah baru **Formulir Konfirmasi** — form jadi 4 langkah (QTN-FR-18, QTN-FR-19 baru); QTN-FR-16 dan QTN-FR-13 mengikuti template quotation; mockup draf 11u. |
| draf 7 | 29-10-2026 | `input/Master - Buat Program/2026-10-29-tanpa-pilihan-pembayaran/`: pilihan **Pembayaran** dihapus dari form (QTN-FR-04) dan baris Pembayaran tidak dicetak di Penawaran (QTN-FR-14); Q-QTN-14 baru; mockup draf 11v. |
| draf 8 | 30-10-2026 | `input/Master - Buat Program/2026-10-30-b2b-quotation-allin-min10/`: tabel beda Public vs B2B (bagian 1); **Sales Order tidak ada**; B2B **satu quotation** (QTN-FR-03), **skema harga per peserta / all-in** (QTN-FR-20 baru), **minimal 10 peserta** (QTN-FR-06, QTN-FR-21 baru), **tanpa Formulir Konfirmasi** — form 3 langkah, PDF tanpa formulir, Confirmation Letter hanya Public (QTN-FR-10, -13, -16, -18, -19); invoice B2B dapat dipecah per peserta. Q-QTN-03, -08, -13 terjawab; Q-QTN-15 – Q-QTN-18 baru; mockup draf 11w. |
| draf 9 | 30-10-2026 | `input/Master - Buat Program/2026-10-30-b2b-allin-diskon-konfirmasi/`: all-in **boleh diskon** (Rupiah total; QTN-FR-20, bagian 1, QTN-NFR-02); pecah invoice B2B = (all-in − diskon) ÷ peserta, sementara; B2B **tanpa Confirmation Letter** (QTN-FR-13, UC-37); minimal 10 peserta juga saat **Deal** (QTN-FR-21, UC-35). **ERD Quotation** draf 1 (tabel `quotation`, `quotation_peserta`, `quotation_keterangan`; `ms_peserta.id_customer`) — QTN-NFR-05, Q-QTN-04 diperbarui. UC-33 – UC-37 masuk workbook. Q-QTN-15 sebagian, Q-QTN-16 – Q-QTN-18 terjawab; mockup draf 11x. |
| draf 9a | 30-10-2026 | `input/Master - Buat Program/2026-10-30-allin-b2b-tabel-baru/`: all-in **hanya B2B** (QTN-FR-20); `tr_penawaran` **tidak dipakai**, tabel baru dikonfirmasi (QTN-NFR-05). Q-QTN-04 terjawab, Q-QTN-15 tinggal soal cetak satu baris. |
