# FR & NFR Invoice - Pengajuan — Labmania

- **Isi:** 4 dokumen FR & NFR paket Invoice - Pengajuan
- **Tanggal salinan:** 05-11-2026
- **Sumber kerja (yang tetap diperbarui):** `specs/docs/fr-nfr-invoice.md`, `specs/docs/fr-nfr-manage-peserta.md`, `specs/docs/fr-nfr-pengajuan-biaya.md`, `specs/docs/fr-nfr-activity-log.md`. Dokumen ini salinan final; perubahan dibuat di file sumber lalu paket dibangun ulang dengan `final/build_final.py`.
- **Pasangan final:** `ERD Invoice - Pengajuan.drawio`, `Flow Process Invoice - Pengajuan.drawio`, `Use Case Invoice - Pengajuan.xlsx`, `Mock Up Invoice - Pengajuan/index.html`
- **Catatan:** Menu umum Master User, Master Role, Role & Permission (FR-36 – FR-39, FR-54 – FR-58, FR-64, FR-65) ada di `docs/fr-nfr.md`, Bagian 1 paket **Master - Quotation**; tidak disalin ke paket ini. Invoice belum punya halaman ERD (menunggu Q-IVC-01) dan use case-nya belum masuk workbook. Halaman ERD Penjualan Program ikut sebagai rujukan kolom Close Program (Manage Peserta).

## Daftar isi

| Bagian | Modul | Versi | File sumber |
|---|---|---|---|
| Bagian 1 | Modul Invoice | draf 2i — 03-11-2026 | `docs/fr-nfr-invoice.md` |
| Bagian 2 | Modul Manage Peserta | draf 1 — 04-11-2026 | `docs/fr-nfr-manage-peserta.md` |
| Bagian 3 | Modul Pengajuan Biaya | draf 4a — 05-11-2026 | `docs/fr-nfr-pengajuan-biaya.md` |
| Bagian 4 | Jejak Data & Modul Activity Log | draf 2b — 05-11-2026 | `docs/fr-nfr-activity-log.md` |

Penomoran FR/NFR, UC, dan pertanyaan terbuka (Q-…) tetap milik masing-masing bagian. Penanda sumber (`[…]`) dan rujukan `input/…` dibaca relatif terhadap folder `specs/`.

---

# Bagian 1 — Modul Invoice Labmania

- **Versi:** draf 2i — 03-11-2026
- **Sumber:** `input/Invoice & Pengajuan/2026-11-03-invoice-tanpa-nomor-po/` (Nomor PO dihapus, Nomor Faktur per invoice, COA Penjualan tetap); `input/Invoice & Pengajuan/2026-11-03-b2b-per-peserta-pecah-otomatis/` (B2B per peserta dipecah otomatis, nomor invoice berbeda per peserta); `input/Master - Buat Program/2026-11-02-format-penomoran-penawaran-invoice/` (format nomor Invoice `LMI/INV/MM/yy-zzz`); `input/Invoice & Pengajuan/2026-10-30-print-invoice/` (Print Invoice, template usulan); `input/Invoice & Pengajuan/2026-10-30-tanpa-report-invoice/` (menu report ditunda); `input/Invoice & Pengajuan/2026-10-30-add-invoice-halaman-penuh/` (Add Invoice halaman penuh); `input/Invoice & Pengajuan/2026-10-30-tab-invoiced-filter-jenis/` (status Uninvoiced/Invoiced, tab Invoiced, filter Public/B2B); `input/Invoice & Pengajuan/2026-10-30-invoice-b2b-ikut-skema/` (invoice B2B mengikuti skema quotation); `input/Invoice & Pengajuan/2026-10-30-invoice-per-quotation-tab/` (satu quotation Deal = satu invoice, tab Public/B2B, kolom nomor program); `input/Invoice & Pengajuan/2026-10-30-invoice-consulting/` (`input/Invoice & Pengajuan/Invoice Consulting.xlsx`, `input/Invoice & Pengajuan/Invoice Consulting.html`); keputusan hulu dari modul Quotation: `input/Master - Buat Program/2026-10-25-quotation-so-persiapan-invoice/`, `input/Master - Buat Program/2026-10-30-b2b-quotation-allin-min10/`, `input/Master - Buat Program/2026-10-30-b2b-allin-diskon-konfirmasi/`
- **Status:** DRAF. Pertanyaan terbuka ada di bagian 8.
- **Modul terkait:** hulu Quotation (`docs/fr-nfr-quotation.md`); Master Company (`docs/fr-nfr.md` FR-30, FR-31); Activity Log (`docs/fr-nfr-activity-log.md`)
- **Mockup:** `ui-mockup/produk/index.html`, modul **Invoice** (draf 13h)
- **Draf sebelumnya:** `input/backup/fr-nfr-invoice_draf2h_03-11-2026.md`
- **ERD:** belum digambar. Menunggu Q-IVC-01 (tabel baru atau tabel `tr_invoice_*` yang sudah ada). Usulan tabel ada di bagian 7.

Penomoran memakai awalan **IVC-**. Awalan `INV-` sudah dipakai sebagai penanda sumber `[INV-1]` di spec Quotation.

| Penanda | Arti |
|---|---|
| `[ICX-R n]` | `Invoice Consulting.xlsx` sheet **Requirements**, baris nomor n. (1) Progress tanpa DP. (2) Jenis PPN: WAPU, PKP, Kawasan Berikat. (3) 1 TOP = 1 invoice. (5) Tidak ada deposit/proforma. (7) Pembalikan uang muka PPN WAPU saat bukti setor diterima. (8) Non PKP tidak dipotong PPh 23. |
| `[ICX-C1]` | sheet Requirements, Catatan Konsep 1: jurnal dibebankan ke company depan |
| `[ICX-DEF]` | sheet **Definisi**: WAPU, Penjualan, DPP, PPN, Piutang |
| `[ICX-BP]` | sheet **Business Process**: tabel jurnal (WAPU, Terima FP WAPU, PKP, Kawasan Berikat) dan daftar report |
| `[ICX-K]` | sheet **Konsep**: field form Create Invoice, perhitungan, kolom jurnal |
| `[ICX-TC]` | sheet **Test Case**: simulasi angka WAPU, PKP, Kawasan Berikat |
| `[ICH]` | `Invoice Consulting.html`: daftar Uninvoiced/Invoiced, form Add Invoicing, kartu perhitungan, tabel jurnal, pilihan COA Penjualan |
| `[IPQ-n]` | `input/Invoice & Pengajuan/2026-10-30-invoice-per-quotation-tab/` — butir n: (1) satu quotation Deal = satu invoice, tidak digabung per program, (2) ~~tab Public / B2B~~ (digantikan `[TIF]`), (3) kolom nomor program di setiap item invoice |
| `[BSK]` | `input/Invoice & Pengajuan/2026-10-30-invoice-b2b-ikut-skema/` — invoice B2B mengikuti skema quotation: all-in ke company = 1 invoice; per peserta = quotation pecah = 1 invoice per peserta. Menggantikan `[BAD-2]` |
| `[PPO]` | `input/Invoice & Pengajuan/2026-11-03-b2b-per-peserta-pecah-otomatis/` — B2B per peserta dipecah otomatis di invoicing: satu kali Add Invoicing membuat satu invoice per peserta Deal, masing-masing dengan nomor invoice sendiri (tidak satu nomor bersama). Menggantikan usulan dropdown "Peserta yang ditagih" |
| `[TPO]` | `input/Invoice & Pengajuan/2026-11-03-invoice-tanpa-nomor-po/` — Nomor PO tidak ada di invoice (semua jenis); Nomor Faktur per invoice; COA Penjualan sementara tetap seperti rancangan |
| `[TIF]` | `input/Invoice & Pengajuan/2026-10-30-tab-invoiced-filter-jenis/` — status hanya Uninvoiced / Invoiced; tab Uninvoiced dan tab Invoiced; Public / B2B lewat filter |
| `[AHP]` | `input/Invoice & Pengajuan/2026-10-30-add-invoice-halaman-penuh/` — Add Invoice berupa halaman penuh, bukan pop up |
| `[TRI]` | `input/Invoice & Pengajuan/2026-10-30-tanpa-report-invoice/` — menu Report Jurnal Invoice dan Report Uninvoiced & Piutang ditunda |
| `[PRI]` | `input/Invoice & Pengajuan/2026-10-30-print-invoice/` — Print Invoice untuk invoice yang sudah dibuat; template dibuatkan analis |
| `[FPN]` | `input/Master - Buat Program/2026-11-02-format-penomoran-penawaran-invoice/` — format nomor Invoice `LMI/INV/MM/yy-zzz` (pola sama dengan Penawaran `LMI/PW/MM/yy-zzz`) |
| `[INV-1]` | Quotation = persiapan invoice; peserta sudah fix |
| `[B2B-5]` | B2B satu quotation; invoice dapat dipecah per peserta bila client minta |
| `[BAD-2]` | ~~pecah invoice B2B: per peserta = (all-in − diskon) ÷ peserta (sementara)~~, digantikan `[BSK]` |
| `[STD]` | standar proyek di `AGENTS.md` |
| `[USULAN]` | usulan analis, **belum** dinyatakan klien |

---

## 1. Ringkasan Kebutuhan

Quotation yang sudah **Deal** adalah persiapan invoice `[INV-1]`, lalu dibuatkan **Invoice**. Perhitungan,
perlakuan PPN, dan jurnalnya mengikuti konsep Invoicing modul konsultasi `[ICX-K]`, `[ICH]`. Hulunya diganti
dengan Quotation Labmania:

| Konsep di file acuan (konsultasi) | Di Labmania |
|---|---|
| SPK | **Quotation Deal** `[INV-1]` |
| Term of Payment (TOP) dari plan tagih; 1 TOP = 1 invoice `[ICX-R 3]` | **Satu quotation Deal = satu invoice** `[IPQ-1]`, tanpa termin `[USULAN]` (Quotation tidak punya pilihan pembayaran, `input/Master - Buat Program/2026-10-29-tanpa-pilihan-pembayaran/`). Quotation Deal dalam satu Penjualan Program **tidak digabung**: 1 quotation Deal → 1 invoice, 10 quotation Deal → 10 invoice `[IPQ-1]`. **B2B mengikuti skema quotation** `[BSK]`: all-in ke company = 1 invoice; per peserta = quotation pecah per peserta = 1 invoice per peserta Deal, dipecah **otomatis** dengan nomor invoice berbeda per peserta `[PPO]` |
| Project / No. SPK di daftar | **No. Program** (nomor Penjualan Program `PJP-`/`PJB-`) di setiap item invoice `[IPQ-3]`; daftar dipisah tab **Uninvoiced** / **Invoiced**, dengan Public / B2B lewat filter jenis `[TIF]` |
| Kepada, Alamat, Ditujukan dari SPK | Kepada, Alamat, u.p. dari quotation |
| Perlakuan PPN dari master customer | Field baru **Perlakuan PPN** di Master Company `[USULAN]`. Personal = PKP `[USULAN]` |
| Company depan (STM/VUCA) untuk pembebanan jurnal `[ICX-C1]` | Satu company: **PT LabMania Indonesia Jaya (LMI)** `[USULAN]` |
| COA Penjualan: Konsultasi, In House Training, Public Training & Seminar, HSE Pengujian `[ICH]` | Default **Penjualan Public Training & Seminar** untuk Public, **Penjualan In House Training** untuk B2B; dapat diganti `[USULAN]` |

```
Penjualan Program (No. Program PJP-/PJB-)
   ├─ Quotation Deal 1 ──► Invoice 1
   ├─ Quotation Deal 2 ──► Invoice 2        (tidak digabung, [IPQ-1])
   └─ Quotation Deal n ──► Invoice n

Quotation Deal ──► Uninvoiced ──Add Invoicing (Save)──► Invoiced ──(WAPU) Terima Bukti Setor──► Invoiced · PPN WAPU disetor
                      │
                      └─ B2B skema per peserta [BSK], [PPO]: sekali Save dipecah otomatis, tiap peserta Deal 1 invoice
                         dengan nomor invoice sendiri; quotation langsung Invoiced
                         (B2B all-in ke company: 1 invoice)
```

Perhitungan `[ICX-DEF]`, `[ICX-K]`:

| Baris | Rumus | Contoh `[ICX-TC]` (WAPU) |
|---|---|---|
| Penjualan | nilai unit tagih (bagian 4.3) | 291.000.000 |
| DPP | Penjualan × 11/12 | 266.750.000 |
| PPN | DPP × 12% | 32.010.000 |
| Total Tagihan | Penjualan + PPN | 323.010.000 |

Piutang dan jurnal per perlakuan PPN `[ICX-DEF]`, `[ICX-BP]`, `[ICH]`:

| Perlakuan | Piutang | Jurnal (Debit / Credit) |
|---|---|---|
| **PKP** | Penjualan + PPN | D Piutang Dagang Usaha = Total Tagihan · K Hutang PPN DN = PPN · K Penjualan = Penjualan |
| **WAPU** (biasanya BUMN; PPN disetor sendiri oleh customer) | Penjualan | D Uang Muka PPN WAPU = PPN · D Piutang Dagang Usaha = Penjualan · K Hutang PPN WAPU = PPN · K Penjualan = Penjualan |
| **Kawasan Berikat** (tidak bayar PPN) | Penjualan | D Piutang Dagang Usaha = Penjualan · K Penjualan = Penjualan |
| WAPU, saat **Terima Bukti Setor** `[ICX-R 7]` | — | D Hutang PPN WAPU = PPN · K Uang Muka PPN WAPU = PPN |

---

## 2. Aktor

| Aktor | Peran | Sumber |
|---|---|---|
| Finance / Keuangan | Membuat invoice, memilih COA Penjualan, mencatat bukti setor PPN WAPU | `[ICH]` (menu Finance) + `[USULAN]` (aktor Labmania belum ditetapkan, Q-IVC-13) |
| Manajemen | Melihat daftar | `[USULAN]` |

---

## 3. Tabel Use Case

| Kode | Use case | Aktor | Prasyarat | Alur singkat | Hasil |
|---|---|---|---|---|---|
| UC-40 | Membuat Invoice | Finance | Quotation Deal dengan unit tagih yang belum ditagih | Pilih quotation Uninvoiced, Add Invoicing (cara tagih otomatis dari skema quotation). Isi Tanggal dan Nomor Faktur (B2B per peserta: Nomor Faktur per invoice). Tidak ada Nomor PO `[TPO]`. Cek perhitungan, pilih COA Penjualan, Save | Invoice tersimpan dengan nomor dan jurnal yang balance. B2B per peserta: sistem membuat satu invoice per peserta Deal sekaligus, masing-masing bernomor sendiri `[PPO]`. Quotation menjadi Invoiced |
| UC-41 | Mencatat Bukti Setor PPN WAPU | Finance | Invoice WAPU yang bukti setornya belum dicatat | Pilih invoice, isi tanggal (dan nomor) bukti setor, Simpan | Jurnal pembalikan Uang Muka PPN WAPU tersimpan |
| ~~UC-42~~ | ~~Melihat Report Invoice~~ — **ditunda** `[TRI]` (IVC-FR-14) | | | | |

Kode UC melanjutkan UC-38/UC-39 (Activity Log). Kode ini belum masuk `docs/workbook.xlsx`.

---

## 4. Kebutuhan Fungsional (FR)

### 4.1 Daftar

| ID | Kebutuhan | Sumber |
|---|---|---|
| IVC-FR-01 | Menu: modul **Invoice** dengan menu **Invoicing** saja `[ICH]`. Menu **Report Jurnal Invoice** dan **Report Uninvoiced & Piutang** **belum dibuat (ditunda)** `[TRI]`, IVC-FR-14. Report Actual Tagih (forecast TOP) **tidak dibuat** karena tidak ada termin `[USULAN]`. | `[ICH]`, `[TRI]` + `[USULAN]` |
| IVC-FR-02 | **Daftar Invoicing** dipisah dalam dua tab menurut status `[TIF]`, dengan jumlah baris di label tab. **Status hanya Uninvoiced dan Invoiced** `[TIF]`. **Tab Uninvoiced**: satu baris per **quotation Deal** yang masih punya peserta Deal belum ditagih; beberapa quotation Deal dalam satu program tampil sebagai baris terpisah `[IPQ-1]`. Kolom: No, **No. Program** (jenis, nomor Penjualan Program, tanggal acara) `[IPQ-3]`, **No. Quotation**, **Customer** (Kepada, tag Company/Personal, perlakuan PPN), **Program** (judul paket, lokasi; pengganti Project), **Sales**, **Cara tagih** (1 invoice, atau "Per peserta · m invoice" `[PPO]`), **Nilai quotation**, **Status**, **Action**. **Tab Invoiced** (khusus yang sudah terinvoice) `[TIF]`: **satu baris per invoice** `[USULAN]`. Kolom: No, **No. Program**, **No. Invoice** (tanggal), **No. Quotation**, **Customer** (+ nama peserta untuk invoice per peserta), **No. Faktur** (Nomor PO tidak ada `[TPO]`), **Perlakuan PPN** (+ status bukti setor untuk WAPU), **Total Tagihan** (+ piutang), **Status**, **Action**. Filter di kedua tab: **Jenis program** (Public & B2B / Program Public / Program B2B) `[TIF]`, **Program** (nomor program) `[USULAN]`, **Tahun**, pencarian (juga nomor program, nomor invoice, faktur); tombol **Excel** `[ICH]`. Kolom Company (STM/VUCA) dan Project Leader **tidak dipakai** `[USULAN]`. | `[TIF]`, `[IPQ-1]`, `[IPQ-3]`, `[ICH]` + `[USULAN]` (pemetaan kolom, satu baris per invoice di tab Invoiced, filter program) |
| IVC-FR-03 | **Action**: tab Uninvoiced, **Add Invoicing**. Tab Invoiced, **Lihat Invoice** dan **Terima Bukti Setor WAPU** (invoice WAPU yang belum dicatat). "View Plan Tagih" di acuan tidak dipakai karena tidak ada plan tagih `[USULAN]`. Dari daftar Quotation, quotation Deal yang belum ditagih juga punya aksi **Buat Invoice** `[USULAN]`. | `[ICH]` + `[USULAN]` |

### 4.2 Form Add Invoicing

**Add Invoicing dibuka sebagai halaman penuh**, bukan pop up `[AHP]`, seperti halaman Add Invoicing di `[ICH]`. Breadcrumb: Invoice / Invoicing / Add Invoicing; menu Invoicing tetap aktif. Isinya dibagi menjadi bagian bernomor menurut urutan kerja `[USULAN]`: **1 Data Invoice** (IVC-FR-04, IVC-FR-05), **2 Cara Tagih** (B2B saja, IVC-FR-06), **3 Rincian Tagihan** (IVC-FR-07), **4 Perhitungan Invoice** (IVC-FR-08), **5 Jurnal** (IVC-FR-10). Tombol **Back** dan **Save** di bawah, tetap terlihat saat halaman digulir `[USULAN]`. Dibuka dari daftar Invoicing (tab Uninvoiced) atau dari aksi Buat Invoice di Quotation; Back/Save kembali ke halaman asal `[USULAN]`.

| ID | Kebutuhan | Sumber |
|---|---|---|
| IVC-FR-04 | Di atas form tampil **No. Program** (nomor Penjualan Program dan jenisnya), judul paket, dan nomor quotation `[IPQ-3]`. **Header kiri** (hanya tampil, tidak dapat diubah): **Kepada** (nama Company, atau peserta pertama untuk Personal), **Alamat**, **Ditujukan** (u.p. quotation; untuk Personal: nama peserta), **Perlakuan PPN** (dari Master Company; Personal = PKP) `[ICX-K]`, `[ICH]`. | `[ICX-K]`, `[ICH]` + `[USULAN]` (Personal) |
| IVC-FR-05 | **Header kanan**: **Tanggal Invoice** (pilih tanggal, default hari ini) `[ICX-K]`; **Nomor Invoice** otomatis oleh sistem `[ICX-K]`, berformat **`LMI/INV/MM/yy-zzz`** `[FPN]` — `LMI` kode PT LabMania Indonesia Jaya, `INV` kode Invoice, `MM` bulan 2 digit, `yy` 2 digit terakhir tahun, `zzz` nomor urut 3 digit (contoh `LMI/INV/11/26-001`); `MM/yy` dari Tanggal Invoice, urutan di-reset tiap bulan dan terpisah dari urutan Penawaran `[USULAN]` (Q-IVC-04); **tidak ada Nomor PO** (di acuan wajib `[ICX-K]`, untuk Labmania dihapus `[TPO]`); **Nomor Faktur** wajib `[ICX-K]` (tidak tampil bila quotation tanpa PPN `[USULAN]`, Q-IVC-07). | `[ICX-K]`, `[FPN]`, `[TPO]` + `[USULAN]` |
| IVC-FR-06 | **Cara tagih** tidak dipilih di form, tetapi **mengikuti quotation** `[BSK]`. **B2B skema All-in 1 company**: **satu invoice** untuk seluruh peserta Deal. **B2B skema Per peserta**: quotation dianggap pecah per peserta, jadi dibuat **satu invoice per peserta Deal**. Pemecahan dilakukan **otomatis** `[PPO]`: satu kali Add Invoicing → Save membuat semua invoice peserta Deal sekaligus; user **tidak memilih peserta** satu per satu. Setiap invoice mendapat **Nomor Invoice sendiri** (berurutan); satu nomor invoice **tidak boleh** dipakai bersama oleh beberapa peserta `[PPO]`. Form menampilkan keterangan cara tagih dan tabel **daftar invoice yang akan dibuat**: No, Peserta, Nomor Invoice (pratinjau), Nominal, **Nomor Faktur** (diisi per invoice). **Nomor Faktur per invoice** `[TPO]`. **Tanggal Invoice** dan **COA Penjualan** diisi sekali dan berlaku untuk semua invoice hasil pecahan (COA Penjualan: sementara `[TPO]`; tanggal sama: `[USULAN]`). **Public selalu satu invoice per quotation** `[IPQ-1]`. | `[BSK]`, `[PPO]`, `[TPO]`, `[IPQ-1]` + `[USULAN]` (tanggal sama) |
| IVC-FR-07 | **Tabel rincian tagihan** (pengganti tabel Term of Payment `[ICH]`). Kolom: No, **Item** (judul paket dan nomor Penjualan Program), **Peserta** (jumlah dan nama), **Harga / peserta**, **Diskon**, **Nominal**. B2B per peserta: satu baris per peserta Deal (= satu invoice), dengan Nomor Invoice pratinjau di setiap baris `[PPO]`. Kartu perhitungan (IVC-FR-08) dan jurnal (IVC-FR-10) ditampilkan **per invoice** plus total gabungan `[USULAN]`. | `[ICH]`, `[PPO]` + `[USULAN]` |
| IVC-FR-08 | **Kartu perhitungan** `[ICX-K]`, `[ICH]`: **Penjualan** (nilai unit tagih, IVC-FR-09); **DPP** = Penjualan × 11/12; **PPN** = DPP × 12%; **Biaya Lain-lain** (dari quotation, tanpa PPN `[USULAN]`, Q-IVC-09); **Total Tagihan** = Penjualan + PPN + Biaya Lain-lain; baris **Piutang** sesuai perlakuan PPN `[ICX-DEF]`. Setiap baris menampilkan rumusnya. Pembulatan ke rupiah di setiap tahap `[ICH]` (Q-IVC-08). Quotation **tanpa PPN**: DPP dan PPN = 0 `[USULAN]` (Q-IVC-07). | `[ICX-K]`, `[ICX-DEF]`, `[ICH]` + `[USULAN]` |
| IVC-FR-09 | **Nilai unit tagih (Penjualan)**: yang ditagih hanya **peserta Deal** hasil aksi Deal di Quotation (QTN-FR-11); peserta Batal tidak masuk invoice `[INV-1]`. Public dan B2B all-in = nilai quotation setelah diskon untuk **peserta Deal** (QTN-FR-08 / QTN-FR-20: subtotal − diskon, atau harga all-in − diskon). B2B per peserta = **harga − diskon per peserta** `[BSK]`. Biaya Lain-lain quotation per peserta dibagi rata, dan sisa pembulatan masuk ke invoice peserta **terakhir** agar jumlah semua invoice = nilai quotation `[USULAN]` (Q-IVC-09). | `[BSK]` + `[USULAN]` (Biaya Lain-lain) |
| IVC-FR-10 | **Tabel jurnal** `[ICX-K]`, `[ICH]`: kolom Tanggal Jurnal (= Tanggal Invoice), Nama Company (LMI `[USULAN]`), **No. Program** `[IPQ-3]`, COA, Nama COA, Deskripsi, Debit, Credit; baris **Balancing** berisi jumlah debit dan jumlah credit, yang harus sama. Baris jurnal sesuai perlakuan PPN (tabel bagian 1); baris bernilai 0 tidak tampil `[ICH]`. **Deskripsi** = "Nama COA - No Invoice - No Quotation" (acuan: "- No SPK"), ditambah "- Kawasan Berikat" untuk perlakuan KB `[ICX-K]`, `[ICH]`. COA selain Penjualan bersifat tetap (*hard code*) `[ICX-K]`. **COA Penjualan** dipilih di baris jurnal (dropdown dengan pencarian) dan wajib diisi `[ICH]`. Defaultnya mengikuti jenis penjualan `[USULAN]`. Biaya Lain-lain dikreditkan ke COA Penjualan yang sama `[USULAN]` (Q-IVC-09). | `[ICX-K]`, `[ICH]` + `[USULAN]` |
| IVC-FR-11 | **Save**: memeriksa Nomor Faktur dan COA Penjualan (bila wajib), serta jurnal balance. B2B per peserta: Nomor Faktur setiap invoice wajib diisi `[TPO]` dan tidak boleh sama `[USULAN]`. Invoice tersimpan dengan status **Invoiced** dan jurnalnya ikut tersimpan; B2B per peserta menyimpan semua invoice pecahan sekaligus `[PPO]`. Quotation tidak dapat diubah lagi `[USULAN]`. **Back** kembali ke daftar tanpa menyimpan `[ICH]`. Setelah Save, halaman kembali ke daftar asal `[AHP]` + `[USULAN]`. | `[ICH]`, `[AHP]` + `[USULAN]` |

### 4.3 Setelah invoice dibuat

| ID | Kebutuhan | Sumber |
|---|---|---|
| IVC-FR-12 | **Terima Bukti Setor WAPU** `[ICX-R 7]`, `[ICX-BP]`: hanya untuk invoice WAPU. Isi Tanggal bukti setor (wajib) dan Nomor bukti setor (opsional) `[USULAN]`. Membentuk jurnal pembalikan **D Hutang PPN WAPU / K Uang Muka PPN WAPU** sebesar PPN invoice. Satu kali per invoice. | `[ICX-R 7]`, `[ICX-BP]` + `[USULAN]` (isian, tempat aksi) |
| IVC-FR-13 | **Lihat Invoice**: **No. Program** `[IPQ-3]`, header, rincian, perhitungan, jurnal invoice, dan jurnal bukti setor (bila ada). Tombol **Print Invoice** (IVC-FR-15). Belum ada aksi batal/revisi invoice (Q-IVC-11). | `[PRI]` + `[USULAN]` |
| IVC-FR-14 | **DITUNDA** `[TRI]`: catatan di bawah ini disimpan sebagai rancangan, belum dibangun. **Report** `[ICX-BP]`. Setiap baris report memuat **No. Program** dan dapat dicari dengannya `[IPQ-3]`. **Report Jurnal Invoice**: semua jurnal invoice dan bukti setor yang sudah diposting, saring periode, perlakuan PPN, dan COA, dengan total debit/credit. **Report Uninvoiced & Piutang**: (a) quotation Deal atau peserta yang belum ditagih, beserta nilainya; (b) invoice yang sudah menjadi piutang, beserta nilai piutangnya. Pelunasan belum termasuk (modul penerimaan belum dibahas, Q-IVC-12). | `[ICX-BP]`, `[TRI]` + `[USULAN]` |
| IVC-FR-15 | **Print Invoice** `[PRI]`: aksi di tab Invoiced (gear) dan di Lihat Invoice, untuk setiap invoice yang sudah dibuat. B2B per peserta dicetak satu dokumen per invoice per peserta. Pratinjau A4 lalu **Cetak / Simpan PDF**; setiap cetak dicatat di Activity Log (aksi Cetak). **Format usulan** (Q-IVC-10): kop PT LabMania Indonesia Jaya (sama dengan Penawaran); judul **INVOICE**; kotak **Kepada** (nama Company/peserta, alamat, u.p., NPWP bila ada); kotak data (**No. Invoice**, Tanggal, **No. Program**, No. Quotation, No. Faktur Pajak, Perlakuan PPN; tanpa No. PO `[TPO]`); tabel **No, Deskripsi** (judul paket, durasi, tanggal, waktu, lokasi, No. Program & Quotation, nama peserta), **Kts.**, **Harga** (per peserta, atau all-in), **Diskon**, **Jumlah**, plus baris Biaya Lain-lain bila ada; ringkasan **Jumlah, DPP Nilai Lain (11/12), PPN 12%, Biaya Lain-lain, Total Tagihan**; untuk WAPU baris "PPN dipungut & disetor oleh Pemungut (WAPU)", untuk Kawasan Berikat "PPN tidak dipungut"; **Jumlah yang harus dibayar** (= piutang) dan **Terbilang**; blok **Pembayaran** (rekening, a.n. PT LabMania Indonesia Jaya, cantumkan No. Invoice; WAPU: mohon kirim bukti setor/NTPN); tanda tangan "Bekasi, [tanggal invoice] · Hormat kami · Finance"; kaki halaman kode dokumen `FR/LMI-FN-01/01` (usulan). Quotation tanpa PPN tidak mencetak baris DPP/PPN. | `[PRI]` + `[USULAN]` (seluruh format) |

Perubahan di Master Data: **Master Company** mendapat field **Perlakuan PPN** (wajib; pilihan PKP, WAPU,
Kawasan Berikat; default PKP). Field ini diusulkan di bagian Pajak form Company (FR-31 `docs/fr-nfr.md`)
`[ICX-R 2]` + `[USULAN]` (Q-IVC-05).

---

## 5. Kebutuhan Non-Fungsional (NFR)

| ID | Kategori | Kebutuhan | Sumber |
|---|---|---|---|
| IVC-NFR-01 | Integritas data | Nilai invoice dihitung ulang di server dari quotation Deal (peserta Deal, harga, diskon, skema, PPN), bukan dari isian klien. Satu unit tagih hanya boleh punya satu invoice, diperiksa di server dalam transaksi. B2B per peserta: semua invoice pecahan disimpan dalam **satu transaksi** (semua berhasil atau tidak sama sekali) `[USULAN]`. | `[PPO]` + `[USULAN]` |
| IVC-NFR-02 | Integritas data | Jurnal disimpan hanya bila debit = credit. Header invoice, detail, dan jurnal disimpan dalam satu transaksi. | `[ICX-K]` (Balancing) + `[USULAN]` |
| IVC-NFR-03 | Integritas data | Nomor Invoice (`LMI/INV/MM/yy-zzz`, IVC-FR-05) dibuat server saat Save (bukan saat form dibuka) agar tidak ada nomor ganda; nomor urut diambil di dalam transaksi yang sama dengan penyimpanan invoice dan kolom nomor unik `[USULAN]`. B2B per peserta mengambil nomor berurutan, satu nomor per invoice peserta `[PPO]`. | `[ICX-K]` + `[USULAN]` |
| IVC-NFR-04 | Audit | Save invoice dan bukti setor WAPU dicatat `write_log()` modul "Invoice" (jurnal sebagai `detail_changes`). Tabel memakai kolom jejak `created_by`, `created_date`, `updated_by`, `updated_date`; aksi tampil di Activity Log (`docs/fr-nfr-activity-log.md`). | `[STD]` |
| IVC-NFR-05 | Pajak | Pelanggan Non PKP tidak dipotong PPh 23 `[ICX-R 8]`. Catatan ini untuk modul penerimaan, bukan pembuatan invoice. | `[ICX-R 8]` |
| IVC-NFR-06 | Platform | CI3 HMVC, MySQL, Bootstrap + jQuery; nama tabel/kolom = ERD (setelah Q-IVC-01). Isian uang memakai pemisah ribuan titik (`fr-nfr.md` NFR-12). Menu dan aksi mengikuti permission aplikasi. | `[STD]` |
| IVC-NFR-07 | Dokumen | PDF invoice dibuat server-side dari template HTML/CSS (A4) agar identik dengan pratinjau, sama dengan QTN-NFR-06; nilai dicetak dari data invoice tersimpan (bukan dihitung ulang dari quotation). | `[PRI]` + `[USULAN]` |

---

## 6. Status

| Objek | Dari | Aksi | Ke |
|---|---|---|---|
| Quotation (di daftar Invoicing) | Deal, belum ada invoice | — | **Uninvoiced** |
| Quotation | Uninvoiced | Save invoice (satu invoice; B2B per peserta: semua invoice peserta Deal dibuat sekaligus `[PPO]`) | **Invoiced**, pindah dari tab Uninvoiced |
| Invoice | — | Save | **Invoiced** |
| Invoice WAPU | Invoiced | Terima Bukti Setor | Invoiced, bukti setor tercatat |

---

## 7. Usulan Data (sebelum ERD)

Ini **bukan ERD**. Daftar ini disusun untuk menjawab Q-IVC-01 dan baru masuk `docs/diagrams/erd.drawio`
setelah diputuskan.

| Usulan tabel | Isi |
|---|---|
| `invoice` | nomor, tanggal, id_quotation, id_penjualan_program (salinan untuk telusur `[IPQ-3]`, usulan), cara_tagih (satu/peserta, diturunkan dari skema quotation), perlakuan_ppn, no_faktur (tanpa no_po `[TPO]`), penjualan, dpp, ppn, biaya_lain, total_tagihan, piutang, coa_penjualan, kolom jejak |
| `invoice_peserta` | id_invoice, id_quotation_peserta, nominal |
| `invoice_jurnal` (atau tabel `jurnal` yang sudah ada) | id_invoice, jenis (invoice / bukti setor WAPU), tanggal, company, coa, nama_coa, deskripsi, debit, credit |
| `invoice_bukti_setor` | id_invoice, tanggal, nomor |
| `customer` (Master Company) | + `perlakuan_ppn` |

---

## 8. Pertanyaan Terbuka

| ID | Pertanyaan | Mempengaruhi |
|---|---|---|
| Q-IVC-01 | Aplikasi sudah punya modul `invoicing` (membaca tabel konsultasi `kons_tr_actual_plan_tagih`) serta tabel `tr_invoice_header`, `tr_invoice_detail`, `tr_invoice_payment`, dan `jurnal`. Untuk Labmania, buat **tabel baru** (bagian 7) atau pakai tabel yang ada? Hal yang sama diputuskan untuk Quotation (`tr_penawaran` tidak dipakai, `[ATB-2]`). | ERD, IVC-NFR-06 |
| Q-IVC-02 | **Termin**: apakah Labmania memang tanpa termin (usulan: 1 quotation / 1 peserta = 1 invoice)? Atau ada DP/pelunasan seperti "Termin" di baris Pembayaran template Penawaran (Q-QTN-14)? | IVC-FR-06, IVC-FR-07 |
| Q-IVC-03 | ~~Public ke Company boleh dipecah per peserta?~~ **Terjawab** `[IPQ-1]`: satu quotation Deal = satu invoice; Public tidak dipecah. | — |
| Q-IVC-04 | ~~Format Nomor Invoice?~~ **Terjawab** `[FPN]`: `LMI/INV/MM/yy-zzz`. Masih usulan: `MM/yy` dari Tanggal Invoice dan urutan `zzz` di-reset tiap bulan — benar, atau berlanjut sepanjang tahun? | IVC-FR-05 |
| Q-IVC-05 | **Perlakuan PPN** disimpan di Master Company (usulan, default PKP)? Bolehkah diganti per invoice? Di acuan, field ini terkunci kecuali untuk case demo. Personal selalu PKP? | IVC-FR-04, Master Company |
| Q-IVC-06 | ~~Nomor PO wajib untuk Personal?~~ **Tidak relevan** `[TPO]`: Nomor PO tidak ada di invoice. | — |
| Q-IVC-07 | Quotation dapat dibuat **tanpa PPN** (centang PPN 11% di QTN-FR-08). Di invoice, apakah ini "Non PPN" (usulan: DPP & PPN = 0, Nomor Faktur tidak wajib, jurnal seperti Kawasan Berikat tanpa keterangan KB), atau semua invoice wajib PPN? | IVC-FR-05, IVC-FR-08, IVC-FR-10 |
| Q-IVC-08 | **PPN quotation** = 11% × DPP (QTN-FR-08), sedangkan **PPN invoice** = (Penjualan × 11/12) × 12%. Nilainya sama, tetapi pembulatannya bisa beda Rp 1. Apakah quotation ikut rumus invoice? Apakah aturan pembulatannya (bulat di setiap tahap) sudah sesuai dengan tim pajak? | IVC-FR-08, QTN-FR-08 |
| Q-IVC-09 | **Biaya Lain-lain** quotation: masuk invoice tanpa PPN dan dikreditkan ke COA Penjualan (usulan), ke COA lain, atau ikut dikenai PPN? | IVC-FR-08, IVC-FR-10 |
| Q-IVC-10 | **Format cetak invoice**: template **usulan** sudah dibuat (IVC-FR-15) karena belum ada template klien `[PRI]`. Masih perlu dari klien: (a) **rekening bank** tujuan transfer; (b) **nama & jabatan penanda tangan** (usulan: Finance); (c) **jatuh tempo / termin pembayaran** dicetak atau tidak; (d) **kode dokumen** resmi (usulan `FR/LMI-FN-01/01`); (e) kota & tanggal tanda tangan (usulan: Bekasi, tanggal invoice); (f) apakah perlu tanda tangan/stempel digital. | IVC-FR-15 |
| Q-IVC-11 | Bila invoice salah (misalnya nomor faktur), apakah bisa **dibatalkan/direvisi**, dan apa yang terjadi pada jurnalnya (jurnal balik)? | IVC-FR-13 |
| Q-IVC-12 | **Penerimaan/pelunasan** piutang dan jurnal penerimaan (di acuan ada menu Jurnal Penerimaan) masuk ruang lingkup Labmania sekarang atau menyusul? | IVC-FR-14 |
| Q-IVC-13 | Siapa **aktor** invoice di Labmania (group Keuangan, Manajemen)? Apakah perlu approval sebelum Save? | Bagian 2 |
| Q-IVC-15 | ~~Pecah invoice B2B per peserta tetap ada?~~ **Terjawab** `[BSK]`: invoice B2B mengikuti skema quotation. All-in ke company = 1 invoice; per peserta = 1 invoice per peserta. | — |
| Q-IVC-16 | ~~Nomor PO sekali, Nomor Faktur per invoice?~~ **Terjawab** `[TPO]`: Nomor PO tidak ada di invoice; Nomor Faktur per invoice; COA Penjualan sementara tetap (sama untuk semua invoice pecahan). | — |
| Q-IVC-14 | **Kode COA** final untuk Piutang Dagang Usaha (`1102-01-01`), Hutang PPN DN (`2104-01-07`), Uang Muka PPN WAPU (`1106-01-XX`), Hutang PPN WAPU (`2104-01-XX`), dan COA Penjualan (`0000-00-00`). Apakah Labmania memakai daftar COA yang sama? | IVC-FR-10 |

---

## 9. Riwayat Perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| draf 1 | 30-10-2026 | Penyusunan awal dari `input/Invoice & Pengajuan/2026-10-30-invoice-consulting/`: konsep Invoicing modul konsultasi dipetakan ke Quotation Deal Labmania. Perhitungan 11/12 & 12%, perlakuan PPN PKP/WAPU/Kawasan Berikat, jurnal dan Balancing, COA Penjualan, bukti setor WAPU, dua report. Pecah per peserta untuk B2B (`[B2B-5]`, `[BAD-2]`). Field Perlakuan PPN di Master Company (usulan). Q-IVC-01 – Q-IVC-14. Mockup draf 13. |
| draf 2 | 30-10-2026 | `input/Invoice & Pengajuan/2026-10-30-invoice-per-quotation-tab/`: **satu quotation Deal = satu invoice**, tidak digabung per program (bagian 1, IVC-FR-02, IVC-FR-06); daftar dipisah **tab Program Public / Program B2B** (IVC-FR-02); kolom **No. Program** di daftar, form, jurnal, Lihat Invoice, dan kedua report, plus filter program (IVC-FR-02, -04, -10, -13, -14); usulan kolom `invoice.id_penjualan_program`. Q-IVC-03 terjawab; Q-IVC-15 baru. Mockup draf 13a. |
| draf 2a | 30-10-2026 | `input/Invoice & Pengajuan/2026-10-30-invoice-b2b-ikut-skema/`: cara tagih B2B **mengikuti skema quotation** (all-in = 1 invoice, per peserta = 1 invoice per peserta); pilihan manual di form dihapus (bagian 1, UC-40, IVC-FR-06, IVC-FR-09). `[BAD-2]` (all-in ÷ peserta) tidak dipakai lagi. Q-IVC-15 terjawab. Mockup draf 13b (seed program B2B per peserta). |
| draf 2b | 30-10-2026 | `input/Invoice & Pengajuan/2026-10-30-tab-invoiced-filter-jenis/`: status hanya **Uninvoiced / Invoiced** ("Sebagian" dihapus); tab **Uninvoiced** dan tab **Invoiced** (satu baris per invoice), menggantikan tab Program Public / B2B; Public / B2B lewat **filter jenis program** (bagian 1, IVC-FR-02, IVC-FR-03, bagian 6). Mockup draf 13c. |
| draf 2c | 30-10-2026 | `input/Invoice & Pengajuan/2026-10-30-add-invoice-halaman-penuh/`: **Add Invoicing halaman penuh**, bukan pop up, dengan lima bagian bernomor dan tombol Back/Save tetap di bawah (bagian 4.2, IVC-FR-11). Mockup draf 13d (render dicek lewat screenshot Chrome headless; kolom tabel jurnal dikunci agar Debit/Credit terlihat). |
| draf 2d | 30-10-2026 | `input/Invoice & Pengajuan/2026-10-30-tanpa-report-invoice/`: menu **Report Jurnal Invoice** dan **Report Uninvoiced & Piutang** **ditunda** (IVC-FR-01, IVC-FR-14, UC-42, bagian 2). Modul Invoice hanya menu Invoicing. Mockup draf 13e. |
| draf 2f | 02-11-2026 | `input/Master - Buat Program/2026-11-02-format-penomoran-penawaran-invoice/`: Nomor Invoice menjadi **`LMI/INV/MM/yy-zzz`** (IVC-FR-05, IVC-NFR-03), menggantikan usulan `NNN/LMI/YYYY`; Q-IVC-04 terjawab sebagian (sumber MM/yy dan reset per bulan masih usulan); penanda `[FPN]` baru. |
| draf 2e | 30-10-2026 | `input/Invoice & Pengajuan/2026-10-30-print-invoice/`: **Print Invoice** (IVC-FR-15 baru, IVC-FR-13, IVC-NFR-07 baru) dengan **template usulan analis**; Q-IVC-10 diperbarui menjadi daftar isian yang perlu dari klien. Mockup draf 13f (render dicek lewat screenshot). |
| draf 2g | 03-11-2026 | IVC-FR-09: penegasan bahwa yang ditagih hanya peserta Deal dari Quotation (QTN-FR-11); peserta Batal tidak masuk invoice `[INV-1]`. Tidak ada aturan baru. |
| draf 2h | 03-11-2026 | `input/Invoice & Pengajuan/2026-11-03-b2b-per-peserta-pecah-otomatis/` (`[PPO]`): invoice B2B skema per peserta **dipecah otomatis**: satu kali Add Invoicing membuat satu invoice per peserta Deal, masing-masing bernomor sendiri; dropdown "Peserta yang ditagih" dihapus (bagian 1, UC-40, IVC-FR-02, IVC-FR-06, IVC-FR-07, IVC-FR-11, IVC-NFR-01, IVC-NFR-03, bagian 6: status "sebagian peserta" dihapus). Nomor PO sekali / Nomor Faktur per invoice masih usulan (Q-IVC-16 baru). Mockup draf 13g. |
| draf 2i | 03-11-2026 | `input/Invoice & Pengajuan/2026-11-03-invoice-tanpa-nomor-po/` (`[TPO]`): **Nomor PO dihapus** dari invoice (UC-40, IVC-FR-02 kolom & pencarian, IVC-FR-05, IVC-FR-11, IVC-FR-15 cetak, bagian 7 kolom `no_po`); Nomor Faktur per invoice dikonfirmasi (IVC-FR-06, IVC-FR-11); COA Penjualan sementara tetap. Q-IVC-06 tidak relevan, Q-IVC-16 terjawab. Mockup draf 13h. |

---

# Bagian 2 — Modul Manage Peserta Labmania

- **Versi:** draf 1 — 04-11-2026
- **Sumber:** `input/Invoice & Pengajuan/2026-11-04-manage-peserta/` (permintaan user + jawaban atas usulan analis)
- **Status:** DRAF. Cakupan **Fase 1**: daftar program, peserta per program, cetak Daftar Peserta & Daftar Hadir, export Excel, Close Program. **Fase 2 ditunda** (bagian 2). Pertanyaan terbuka di bagian 7.
- **Modul terkait:** hulu Quotation (`docs/fr-nfr-quotation.md`: peserta **Deal** per quotation, QTN-FR-11) dan Penjualan Program (`docs/fr-nfr-penjualan-program.md`: program Final, trainer, tanggal, lokasi); Master Peserta (`docs/fr-nfr.md` FR-15 – FR-17); hilir Pengajuan Biaya (`docs/fr-nfr-pengajuan-biaya.md` PB-FR-20: program Closed tidak dapat diajukan); hak menu lewat Role & Permission (`docs/fr-nfr.md` FR-54 – FR-58); Activity Log (`docs/fr-nfr-activity-log.md`)
- **ERD:** `docs/diagrams/erd.drawio` halaman *ERD Penjualan Program* (`penjualan_program.closed_by`, `closed_date`) — **tanpa tabel baru**
- **Use case:** `docs/workbook.xlsx` sheet *UC Non-BPMN* (UC-50 – UC-52)
- **Mockup:** `ui-mockup/produk/Labmania.html`, modul **Manage Peserta** (draf 14e)

Penomoran memakai awalan **MP-**; pertanyaan modul ini **Q-MPS-nn** (Q-MP-nn sudah dipakai `fr-nfr-master-program.md`).

| Penanda | Arti |
|---|---|
| `[MPS-1]` | permintaan user 04-11-2026: modul "Manage Peserta", setiap program terlist, bisa print daftar peserta dsb. |
| `[MPS-2]` | jawaban user 04-11-2026: setelah Deal quotation; Halaman 1 setuju + action **Close Program**; Halaman 2 setuju semua; Fase 2 nanti; hak pakai disetting sendiri; format cetak belum ada — dibuatkan |
| `[JPB-12]` | `input/Invoice & Pengajuan/2026-11-04-jawaban-pengajuan-biaya/` butir PB-12: pengajuan boleh selama program belum **Closed** |
| `[STD]` | standar proyek di `AGENTS.md` (`write_log()`) |
| `[USULAN]` | usulan analis, **belum** dinyatakan klien |

---

## 1. Ringkasan

Setelah quotation **Deal**, peserta program sudah fix `[MPS-2]`. Modul **Manage Peserta** menampilkan setiap
Penjualan Program **Final** beserta peserta Deal-nya, untuk dicetak (Daftar Peserta, Daftar Hadir per hari)
dan diekspor ke Excel, lalu **Close Program** saat program ditutup. Modul ini **tidak menambah atau mengubah
peserta**: sumbernya hanya peserta yang dicentang Deal di quotation Deal; perbaikan data peserta lewat Master Peserta `[USULAN]`.

```
Penjualan Program Final ──► Quotation Deal (peserta Deal) ──► Manage Peserta ──► Close Program ──► Pengajuan Biaya tertutup
                                                              (lihat · cetak · export)
```

## 2. Cakupan

| Termasuk (Fase 1) | Ditunda (Fase 2) `[MPS-2]` |
|---|---|
| Daftar Program (tab On going / Selesai) | Input kehadiran per hari |
| Peserta per program | Hasil (Kompeten / Belum Kompeten), nomor & masa berlaku sertifikat |
| Cetak Daftar Peserta, Cetak Daftar Hadir (template usulan) | Rekap rencana vs aktual peserta |
| Export Excel | Status pembayaran per peserta (tidak dijawab — tidak dimasukkan) |
| Close Program | |

## 3. Aktor

| Aktor | Peran | Sumber |
|---|---|---|
| Pengguna dengan hak menu **Manage Peserta** | View = lihat, cetak, export; Manage = Close Program. Role mana yang diberi hak **diatur user sendiri** di Role & Permission | `[MPS-2]` |

## 4. Use Case (salinan baca; kanonis di workbook)

| Kode | Nama | Aktor | Prasyarat | Alur singkat | Hasil |
|---|---|---|---|---|---|
| UC-50 | Melihat Peserta Program | Pengguna (hak View) | Penjualan Program Final | Buka Manage Peserta, pilih tab, cari / saring, pilih **Lihat Peserta** | Daftar peserta Deal program tampil |
| UC-51 | Mencetak & Mengekspor Peserta | Pengguna (hak View) | Program punya ≥ 1 peserta Deal | Di halaman peserta pilih Cetak Daftar Peserta / Cetak Daftar Hadir / Export Excel | PDF / Excel peserta |
| UC-52 | Close Program | Pengguna (hak Manage) | Program Final belum Closed | Pilih **Close Program** di menu gear, baca peringatan, konfirmasi | Program Closed; pindah ke tab Selesai; tidak dapat diajukan biaya |

## 5. Kebutuhan Fungsional

| ID | Kebutuhan | Sumber |
|---|---|---|
| MP-FR-01 | Menu: modul **Manage Peserta** dengan menu **Manage Peserta** (sidebar setelah Invoice). Hak membuka menu dan aksinya diatur di **Role & Permission** (`fr-nfr.md` FR-54 – FR-58): **View** = lihat daftar & peserta, cetak, export; **Manage** = Close Program. | `[MPS-1]`, `[MPS-2]` + `[USULAN]` (pemetaan aksi) |
| MP-FR-02 | **Daftar Program**: semua Penjualan Program **Final**, tab **On going** / **Selesai** (Selesai = tanggal selesai acara sudah lewat **atau** program sudah Closed `[USULAN]`, Q-MPS-01). Kolom: No, **No. Program**, **Judul Program** (+ Company untuk B2B), **Jenis** (Public / B2B), **Tanggal & Lokasi**, **Trainer**, **Peserta (Deal / Target)**, Status (Berjalan / Selesai / Closed `[USULAN]`), Action (menu gear: **Lihat Peserta**, **Close Program**). Cari nomor / judul / lokasi / trainer; filter jenis. Urut tanggal mulai. | `[MPS-2]` |
| MP-FR-03 | **Close Program** (menu gear, hanya program belum Closed dan pengguna punya hak Manage): popup konfirmasi; menampilkan **peringatan** (tidak memblokir `[USULAN]`, Q-MPS-02) bila: acara belum selesai, masih ada quotation Draft / Terkirim, quotation Deal belum dibuatkan invoice, atau pengajuan biaya Menunggu Approval. Setelah konfirmasi: program **Closed** (siapa & kapan disimpan), pindah ke tab Selesai, menu Close tidak tampil lagi, **tidak dapat dibuka kembali** `[USULAN]` (Q-MPS-02). | `[MPS-2]` + `[USULAN]` |
| MP-FR-04 | **Peserta per Program** (Lihat Peserta): kepala halaman berisi info program — jenis, judul, tanggal & durasi, lokasi, trainer, Company (B2B), peserta Deal / target, status. Tombol **← Semua Program**, **Export Excel**, **Cetak Daftar Hadir**, **Cetak Daftar Peserta** (nonaktif bila belum ada peserta Deal). Program Closed menampilkan pemberitahuan siapa & kapan di-Close; tetap dapat dilihat, dicetak, diekspor. | `[MPS-2]` |
| MP-FR-05 | **Tabel peserta**: No, **Kode**, **Nama**, **Instansi / Company**, **No. WA**, **Email**, **No. Quotation**. Sumber: peserta berstatus **Deal** di quotation berstatus **Deal** untuk program itu (QTN-FR-11) — peserta Batal / menunggu tidak tampil. Pencarian kode, nama, instansi, kontak, nomor quotation. **Tidak ada tambah / ubah / hapus** peserta di modul ini; data diperbaiki di Master Peserta. | `[MPS-2]` + `[USULAN]` (tanpa edit) |
| MP-FR-06 | **Cetak Daftar Peserta** (PDF A4, template usulan — format Labmania belum ada `[MPS-2]`, Q-MPS-03): kop PT LabMania Indonesia Jaya; judul **DAFTAR PESERTA**; info Program, No. Program, Tanggal, Lokasi, Trainer, Company (B2B), Jumlah peserta; tabel No, Nama Peserta, Instansi / Company, No. WA, Email; maksimal 18 baris per halaman; kaki: kode dokumen (menyusul), "Halaman x dari y". | `[MPS-2]` + `[USULAN]` |
| MP-FR-07 | **Cetak Daftar Hadir** (PDF A4, template usulan, Q-MPS-04): **satu lembar per hari** pelaksanaan (durasi Penjualan Program; tanggal = tanggal mulai + hari ke-n); judul **DAFTAR HADIR PESERTA**; info program + "Hari ke n dari N — tanggal"; tabel No, Nama Peserta, Instansi / Company, **Tanda Tangan** (dua kolom selang-seling, nomor di kotak); di akhir: tanda tangan **Trainer** (nama trainer program) dan **Penyelenggara**. | `[MPS-2]` + `[USULAN]` |
| MP-FR-08 | **Export Excel**: berkas `.xlsx` "Peserta_[No. Program]" berisi baris judul (nomor, judul, tanggal, lokasi program) dan kolom No, Kode, Nama, Instansi / Company, No. WA (format teks agar angka 0 di depan tidak hilang), Email, No. Quotation. | `[MPS-2]` + `[USULAN]` (kolom) |
| MP-FR-09 | **Dampak Close ke Pengajuan Biaya** `[JPB-12]`: program Closed tidak dapat ditambah pengajuan / overbudget baru (tombol Add tidak tampil, server menolak); pengajuan yang ada tetap dapat dilihat dan yang masih Menunggu tetap dapat diproses `[USULAN]`. Lihat `fr-nfr-pengajuan-biaya.md` PB-FR-20. Dampak ke Quotation / Invoice **belum ditetapkan** (Q-MPS-05). | `[JPB-12]` + `[USULAN]` |
| MP-FR-10 | **Activity Log** `[STD]`: `write_log()` modul "Manage Peserta" untuk **Close Program** (status Final → Closed, `old`/`new`), **Cetak** (Daftar Peserta / Daftar Hadir) dan **Export** (nomor program). | `[STD]` + `[USULAN]` (catat cetak/export) |

## 6. Kebutuhan Non-Fungsional

| ID | Kategori | Kebutuhan | Kriteria uji | Sumber |
|---|---|---|---|---|
| MP-NFR-01 | Keamanan | Hak View / Manage diperiksa di server (`has_permission()`), bukan hanya menyembunyikan tombol; Close Program ditolak bila program sudah Closed atau bukan Final. | Request Close tanpa hak Manage → 403; Close dua kali → ditolak | `[USULAN]` |
| MP-NFR-02 | Konsistensi | Peserta dibaca langsung dari quotation Deal (tanpa salinan), sehingga perubahan Master Peserta langsung tampil. | Ubah nama peserta di Master Peserta → halaman & cetak ikut berubah | `[USULAN]` |
| MP-NFR-03 | Dokumen | PDF dibuat server-side dari template HTML/CSS (A4), identik dengan pratinjau — pola QTN-NFR-06. Excel memakai pustaka spreadsheet aplikasi (mis. PhpSpreadsheet). | Pratinjau = PDF unduhan; Excel terbuka tanpa peringatan format | `[USULAN]` |

## 7. Data

Tanpa tabel baru `[USULAN]`. Close Program memakai kolom yang sudah ada di `penjualan_program`:
`closed_by` (user) dan `closed_date` (waktu) — **terisi = Closed**; `status` tetap `final`. Peserta dibaca dari
`quotation` (status `deal`) → `quotation_peserta` (peserta Deal) → `ms_peserta`; trainer dari `penjualan_program.id_trainer`.

## 8. Pertanyaan Terbuka

| ID | Pertanyaan | Terkait |
|---|---|---|
| Q-MPS-01 | Kapan program masuk tab **Selesai**? Usulan: tanggal selesai acara lewat atau sudah Closed (sama dengan Quotation Q-QTN-09). | MP-FR-02 |
| Q-MPS-02 | **Close Program** boleh kapan saja dengan peringatan (usulan), atau hanya setelah acara selesai / semua invoice dibuat? Perlu tombol **Buka Kembali**? | MP-FR-03 |
| Q-MPS-03 | Format **Daftar Peserta**: kolom sudah cukup? Kode dokumen (FR/LMI-…)? | MP-FR-06 |
| Q-MPS-04 | Format **Daftar Hadir**: perlu kolom jam / sesi (pagi–siang)? Penanda tangan selain Trainer & Penyelenggara? | MP-FR-07 |
| Q-MPS-05 | Setelah Closed, apakah **Quotation** (buat / Deal) dan **Invoice** juga dikunci? | MP-FR-09 |

## 9. Riwayat

| Versi | Tanggal | Perubahan |
|---|---|---|
| draf 1 | 04-11-2026 | Penyusunan awal dari `input/Invoice & Pengajuan/2026-11-04-manage-peserta/` `[MPS-1]`, `[MPS-2]`: Fase 1 (MP-FR-01 – MP-FR-10), template cetak usulan, Close Program mengisi status Closed `[JPB-12]`; mockup draf 14e. |

---

# Bagian 3 — Modul Pengajuan Biaya Labmania

- **Versi:** draf 4a — 05-11-2026
- **Sumber:** `input/Invoice & Pengajuan/2026-11-04-pengajuan-biaya-as-is/` (permintaan user + video `input/Invoice & Pengajuan/Menu Pengajuan Biaya - As Is.mp4`); `input/Invoice & Pengajuan/2026-11-04-jawaban-pengajuan-biaya/` (jawaban Q-PB-01 – Q-PB-12, Matrix Akses Menu); `input/Invoice & Pengajuan/2026-11-04-jawaban-pengajuan-biaya-2/` (jawaban Q-PB-13 – Q-PB-16, Q-34)
- **Status:** DRAF. Cakupan sekarang: **pengajuan + approval + tanda Paid** (sementara, sebelum modul pencairan). Pencairan Finance, realisasi, dan jurnal menyusul. Pertanyaan terbuka di bagian 8.
- **Modul terkait:** hulu Penjualan Program (`docs/fr-nfr-penjualan-program.md`: costing Final = budget, Trainer) dan Quotation (`docs/fr-nfr-quotation.md`: Sales); Master User & **Role & Permission** (`docs/fr-nfr.md` FR-36 – FR-39, FR-54 – FR-58); Master Trainer (`docs/fr-nfr.md` FR-44 – FR-47); Activity Log (`docs/fr-nfr-activity-log.md`)
- **ERD:** `docs/diagrams/erd.drawio` halaman *ERD Pengajuan Biaya* (draf 2) dan *ERD Matrix Akses*
- **Flow Process:** `docs/diagrams/bpmn-to-be.drawio` halaman *Level 1 — Pengajuan Biaya*
- **Use case:** `docs/workbook.xlsx` sheet *UC Non-BPMN* (UC-43 – UC-45, UC-47, UC-48, UC-53, UC-54)
- **Mockup:** `ui-mockup/produk/Labmania.html`, modul **Pengajuan Biaya** (draf 14g)
- **Draf sebelumnya:** `input/backup/fr-nfr-pengajuan-biaya_draf2_04-11-2026.md` (draf 1: `input/backup/fr-nfr-pengajuan-biaya_draf1_04-11-2026.md`)

Penomoran memakai awalan **PB-**.

| Penanda | Arti |
|---|---|
| `[PBU]` | permintaan user 04-11-2026: PIC penanggung jawab program mengajukan biaya program ke manajemen untuk dicairkan; "kurang lebih seperti" video, disesuaikan dengan Labmania |
| `[VID-1]` | video, daftar Pengajuan per SPK: Nomor SPK & Paket, Customer, Team / PIC, Status, Action |
| `[VID-2]` | video, halaman pengajuan per SPK: blok per kategori biaya dengan kartu Budget / Aktual / Sisa Budget, tabel pengajuan (Req Number, Description, Total, Tipe, Request By, Status, Date, Option), blok Overbudget |
| `[VID-3]` | video, form Add Pengajuan: kepala SPK, Request By (+ peringatan bukan tim), Tgl, Deskripsi wajib; Informasi Pengajuan (Estimasi / Terpakai / Overbudget per item); tabel Pengajuan (Qty, Price/Unit, Total; baris habis budget terkunci); Bank, Bank Number, Account Name wajib; Upload Bukti; Back / Save |
| `[JPB-n]` | jawaban user 04-11-2026, `input/Invoice & Pengajuan/2026-11-04-jawaban-pengajuan-biaya/`, butir PB-n |
| `[JPB2-n]` | jawaban user 04-11-2026 kedua, `input/Invoice & Pengajuan/2026-11-04-jawaban-pengajuan-biaya-2/`, butir Q-PB-n / Q-n |
| `[FPN]` | pola nomor dokumen `LMI/XX/MM/yy-zzz` dari `input/Master - Buat Program/2026-11-02-format-penomoran-penawaran-invoice/` |
| `[STD]` | standar proyek di `AGENTS.md` |
| `[USULAN]` | usulan analis, **belum** dinyatakan klien |

---

## 1. Ringkasan Kebutuhan

**Tim program** mengajukan biaya program ke **Manajemen** untuk disetujui `[PBU]`, `[JPB-01]`. Tampilan dan alurnya
mengikuti menu Pengajuan sistem acuan `[VID-1]` – `[VID-3]`, disesuaikan ke Labmania. Cakupan sekarang hanya
**pengajuan dan approval** `[JPB-06]`; pencairan oleh **Finance** menyusul `[JPB-02]`.

| Konsep di sistem acuan | Di Labmania |
|---|---|
| SPK (project) | **Penjualan Program Final** (Public & B2B) |
| Team SPK (Project Leader, Sales) | **Tim program** `[JPB-01]`: **Sales** yang dipilih di Quotation **Deal** program itu `[JPB2-16]`, **Trainer** yang dipilih di Penjualan Program (lewat akun login trainer `[JPB2-13]`), dan user **Manajemen** (posisi di Master User, daftar menyusul `[JPB2-14]`) |
| Budget per item (Estimasi) | **Costing Penjualan Program Final** — budget sudah menjadi konsekuensi sejak Penjualan Program dibuat; selisih peserta hanya memengaruhi profit `[JPB-04]` |
| Kategori biaya (± 5 di acuan) | **2 kategori: Fixed Cost dan Variable Cost** (sementara) `[JPB-03]` |
| Add Pengajuan Kasbon / Direct Payment / PO | **Satu tombol "Add Pengajuan"**; jenis dipilih di form: **Kasbon** atau **Direct Payment** `[JPB-05]`. **PO tidak ada** `[JPB-05]` |
| Overbudget | Tambahan budget item costing **atau item baru** di luar costing `[JPB-10]` |
| Approval | **1 level, Manajemen** (sementara) `[JPB-03]` |
| Pencairan (Request Payment), Expense Report, jurnal | **Menyusul** `[JPB-02]`, `[JPB-06]`, `[JPB-09]`; sementara pengajuan dapat **ditandai Paid** (PB-FR-23) `input/Invoice & Pengajuan/2026-11-05-tab-pengajuan-progress/` |
| Daftar pengajuan & progres | Menu Pengajuan Biaya dibagi tab **Ajukan Biaya** dan **Sudah Diajukan**; progres **Waiting Approval → Waiting Payment → Paid** `input/Invoice & Pengajuan/2026-11-05-tab-pengajuan-progress/` |
| Hak akses | Menu lewat **Role & Permission** (`fr-nfr.md` FR-54 – FR-58, FR-65; role dari Master Role FR-64, ditambah Override Permission per user); data program hanya untuk **user terkait**; **Superadmin** (developer) dan **Admin** (user Labmania) melihat semua `[JPB-07]`, `[JPB2-34]` |

```
Penjualan Program Final (costing = budget; Trainer)   Quotation (Sales)   Group Manajemen
                 └──────────────── tim program ───────────────┘──────────────┘
   │
   ├─ Fixed Cost   : Budget │ Aktual │ Sisa ──► + Add Pengajuan (pilih Kasbon / Direct Payment) ──► Waiting Approval ─┬─ Setujui ─► Waiting Payment ─► Tandai Paid ─► Paid
   │                                    └─► + Add Overbudget (item costing / item baru) ──► Menunggu Approval ─┐   ├─ Tolak ───► Ditolak
   └─ Variable Cost: (sama)                                                              Setujui: budget naik ┘   └─ Batalkan (pengaju) ─► Batal
```

Rumus per item costing (`fr-nfr-master-program.md` bagian 8) — budget dari **Penjualan Program Final** `[JPB-04]`:

| Satuan | Qty Estimasi | Contoh (durasi 2 hari, target 25 peserta) |
|---|---|---|
| Event | 1 | Honor Trainer: 1 × 2.125.000 |
| Day | durasi | Hotel Trainer: 2 × 375.000 |
| Peserta/Event | target peserta | Modul: 25 × 50.000 |
| Peserta/Day | target peserta × durasi | Makan Siang: 50 × 50.000 |

- **Total Budget item** = Qty Estimasi × Price/Unit + total Overbudget item yang **Disetujui**. Item baru dari Overbudget punya budget = total overbudget itu saja.
- **Terpakai item** = jumlah detail pengajuan berstatus **Menunggu Approval** (Waiting Approval), **Disetujui** (Waiting Payment), atau **Paid** (Ditolak dan Batal tidak dihitung), agar budget yang sedang diajukan tidak dapat diajukan dua kali `[USULAN]`.
- **Sisa item** = Total Budget item − Terpakai item (informasi). Kartu **Budget / Aktual / Sisa Budget** per kategori = jumlah itemnya `[VID-2]`.
- **Budget mencukupi dihitung per kategori** `[JPB2-15]`: total pengajuan Fixed Cost ≤ **Sisa total Fixed Cost**, total pengajuan Variable Cost ≤ **Sisa total Variable Cost**. Satu item boleh melebihi budget item-nya selama sisa kategori cukup.

### 1.1 Cakupan

| Termasuk | Ditunda / tidak termasuk |
|---|---|
| Daftar program untuk user terkait, halaman pengajuan per program | **Pencairan oleh Finance** `[JPB-02]` |
| Add Pengajuan (Kasbon / Direct Payment) per item | Pengajuan **PO** `[JPB-05]` |
| Add Overbudget (item costing / item baru) | **Pelaporan realisasi** / pertanggungjawaban kasbon `[JPB-06]` |
| Approval 1 level oleh Manajemen | **Jurnal** `[JPB-09]` |
| Batalkan pengajuan selama menunggu approval | Status **Closed** program sebagai batas pengajuan `[JPB-12]` |
| Role & Permission (spec di `docs/fr-nfr.md`) | Approval bertingkat `[JPB-03]` |

### 1.2 Istilah

| Istilah | Arti |
|---|---|
| Tim program | User yang terkait dengan satu Penjualan Program: Sales di quotation **Deal**-nya, Trainer-nya (lewat akun login trainer), dan user Manajemen `[JPB-01]`, `[JPB2-16]`. |
| Kasbon | Uang muka yang ditransfer ke rekening pengaju sebelum biaya dikeluarkan. |
| Direct Payment | Pembayaran langsung ke rekening penerima (vendor / pihak ketiga) atas tagihan biaya program. |
| Overbudget | Permintaan tambahan budget: menambah item costing yang sisanya tidak cukup, atau menambah item baru `[JPB-10]`. |
| Budget / Aktual / Sisa | Lihat rumus di atas. "Aktual" di kartu = Terpakai. |

---

## 2. Aktor

| Aktor | Peran | Sumber |
|---|---|---|
| Sales (tim program) | Mengajukan biaya & overbudget untuk program yang quotation **Deal**-nya ia tangani; membatalkan pengajuannya selama Menunggu Approval | `[JPB-01]`, `[JPB2-16]` |
| Trainer (tim program) | Sama dengan Sales, untuk program yang ia latih, lewat **akun login** di Master Trainer (`fr-nfr.md` FR-59) | `[JPB-01]`, `[JPB2-13]` |
| Manajemen | Termasuk tim semua program (dapat mengajukan); **menyetujui / menolak** pengajuan dan overbudget. Siapa yang termasuk Manajemen ditentukan dari **Role di Master User** (Master Role, `fr-nfr.md` FR-64) `input/Invoice & Pengajuan/2026-11-05-master-role-override/`, daftar role menyusul (Q-PB-14, Q-37); sementara (mockup) role **Direktur** | `[JPB-01]`, `[JPB-03]`, `[JPB2-14]` |
| Superadmin | Akun developer (group id 1): semua menu, semua data, tidak dapat dibatasi | `[JPB2-34]` |
| Admin | Akun user Labmania: membuka semua data program `[JPB-07]`; menu & aksinya mengikuti hak role-nya di Role & Permission (dapat dibatasi) | `[JPB-07]`, `[JPB2-34]` |
| Finance | Mencairkan — **menyusul** | `[JPB-02]` |

---

## 3. Tabel Use Case

| Kode | Nama Use Case | User | Modul | Sub Modul | Input | Deskripsi | Output | Entity/Objek Terkait | FR |
|---|---|---|---|---|---|---|---|---|---|
| UC-43 | Mengajukan Biaya Program | Tim program (Sales, Trainer, Manajemen) | Pengajuan Biaya | Pengajuan | Penjualan Program Final, kategori (Fixed / Variable), jenis (Kasbon / Direct Payment), deskripsi, Qty & Price/Unit per item, Bank, No. Rekening, Nama Rekening, bukti | Aktor membuka program di daftar Pengajuan Biaya dan menekan Add Pengajuan pada kategori biaya. Di form, aktor memilih jenis Kasbon atau Direct Payment. Sistem menampilkan Informasi Pengajuan (Estimasi / Terpakai / Overbudget / Sisa per item); isian terkunci bila sisa kategori habis. Aktor mengisi Qty (Price/Unit boleh diubah), rekening, deskripsi, dan bukti, lalu Save. Sistem memeriksa total pengajuan ≤ sisa kategori, memberi nomor, dan meneruskan ke Manajemen. | Pengajuan berstatus Menunggu Approval dengan nomor `LMI/KS/…` atau `LMI/DP/…` | Pengajuan Biaya, Detail Pengajuan Biaya, Lampiran Pengajuan, Penjualan Program, Biaya Penjualan Program, Detail Overbudget | PB-FR-01 – PB-FR-11 |
| UC-44 | Mengajukan Overbudget | Tim program | Pengajuan Biaya | Overbudget | Penjualan Program Final, kategori, item costing atau item baru, Qty & Price/Unit tambahan, alasan | Aktor menekan Add Overbudget pada kategori biaya, mengisi tambahan Qty dan Price/Unit untuk item costing dan/atau menambah item baru (nama, Qty, Price/Unit), mengisi alasan, lalu Save. | Overbudget berstatus Menunggu Approval dengan nomor `LMI/OB/…` | Pengajuan Overbudget, Detail Overbudget, Biaya Penjualan Program | PB-FR-12, PB-FR-13 |
| UC-45 | Menyetujui / Menolak Pengajuan | Manajemen | Pengajuan Biaya | Approval | Pengajuan atau Overbudget berstatus Menunggu Approval; alasan (wajib bila tolak) | Aktor membuka menu Approval Pengajuan, melihat rincian dan posisi budget program, lalu Setujui atau Tolak (dengan alasan). Pengajuan sendiri tidak dapat diproses. | Pengajuan Disetujui / Ditolak; Overbudget Disetujui menambah budget | Pengajuan Biaya, Pengajuan Overbudget | PB-FR-14, PB-FR-15 |
| ~~UC-46~~ | ~~Mencairkan Biaya~~ — **ditunda** `[JPB-02]`; kode tidak dipakai ulang | | | | | | | | |
| UC-47 | Membatalkan Pengajuan | Pengaju (tim program) | Pengajuan Biaya | Pengajuan | Pengajuan / Overbudget miliknya berstatus Menunggu Approval | Aktor memilih Batalkan dan mengonfirmasi. Budget yang dipesan kembali tersedia. | Pengajuan / Overbudget berstatus Batal | Pengajuan Biaya, Pengajuan Overbudget | PB-FR-17 |
| UC-48 | Melihat Posisi Budget Program | Tim program, Superadmin, Admin | Pengajuan Biaya | Pengajuan | Penjualan Program Final | Aktor membuka daftar Pengajuan Biaya (hanya program terkait; Superadmin & Admin semua) dan halaman program: Budget, Aktual, Sisa per kategori, daftar pengajuan dan overbudget beserta statusnya. | Posisi budget program | Penjualan Program, Biaya Penjualan Program, Pengajuan Biaya, Pengajuan Overbudget | PB-FR-01 – PB-FR-04, PB-FR-18 – PB-FR-20 |
| UC-53 | Memantau Progres Pengajuan | Tim program, Superadmin, Admin | Pengajuan Biaya | Pengajuan | Pengajuan Kasbon / Direct Payment pada program terkait; filter jenis & progres (opsional) | Aktor membuka tab **Sudah Diajukan** dan melihat progres setiap pengajuan: Waiting Approval → Waiting Payment → Paid (Ditolak / Batal menghentikan progres). | Daftar pengajuan beserta progresnya | Pengajuan Biaya, Penjualan Program | PB-FR-21, PB-FR-22 |
| UC-54 | Menandai Pengajuan Paid | Hak Manage Pengajuan Biaya (sementara, Q-PB-17) | Pengajuan Biaya | Pengajuan | Pengajuan berstatus Waiting Payment; tanggal bayar, keterangan / bukti transfer (opsional) | Aktor memilih Tandai Paid pada pengajuan Waiting Payment, mengisi tanggal bayar, lalu menyimpan. Sementara sebelum modul pencairan Finance dibuat. | Pengajuan berstatus Paid | Pengajuan Biaya | PB-FR-23 |

UC-49 (Mengatur Role & Permission) ada di `docs/fr-nfr.md`.

---

## 4. Kebutuhan Fungsional (FR)

### 4.1 Daftar & halaman program

| ID | Kebutuhan | Sumber |
|---|---|---|
| PB-FR-01 | Menu: modul **Pengajuan Biaya** dengan menu **Pengajuan Biaya** (daftar program) dan **Approval Pengajuan** (Manajemen). Menu **Pencairan** belum dibuat `[JPB-02]`. Hak membuka menu dan aksinya diatur di **Role & Permission** (`fr-nfr.md` FR-54 – FR-58). | `[VID-1]`, `[JPB-01]`, `[JPB-02]` |
| PB-FR-02 | Tab **Ajukan Biaya** (PB-FR-21) — **Daftar Pengajuan Biaya**: satu baris per **Penjualan Program Final** yang **terkait dengan user login** (PB-FR-19); **Superadmin dan Admin melihat semua** `[JPB-07]`, `[JPB2-34]`. Kolom: No, **No. Program** (jenis, nomor, tanggal acara), **Program** (judul paket, lokasi), **Customer** (Company untuk B2B; "Public" untuk Public), **Tim** (Sales dari quotation, Trainer), **Budget**, **Aktual**, **Sisa**, **Status** (Belum ada pengajuan / n Waiting Approval / Berjalan), **Action** (Lihat Pengajuan). Filter jenis program; pencarian nomor, judul, customer, sales, trainer. Penjualan Program Draft / Batal tidak tampil. | `[VID-1]`, `[JPB-07]` + `[USULAN]` (kolom angka, status) |
| PB-FR-03 | **Halaman Pengajuan per program**: kepala **No. Program**, Jenis, Customer, Program (judul), Lokasi, **Sales** (dari quotation Deal), **Trainer**, **Waktu** (tanggal mulai – selesai), Durasi, Target peserta. Di bawahnya satu blok per kategori **Fixed Cost** dan **Variable Cost** `[JPB-03]`. | `[VID-2]`, `[JPB-03]` |
| PB-FR-04 | Setiap blok kategori: kartu **Budget**, **Aktual**, **Sisa Budget** (rumus bagian 1); **satu tombol "+ Add Pengajuan"** `[JPB-05]`; tabel pengajuan: No, **No. Pengajuan**, Deskripsi, Total, **Jenis** (badge Kasbon / Direct Payment), **Request By**, **Status** (label progres PB-FR-22), Tanggal, Option (gear: Lihat, Batalkan). Sub-blok **Overbudget [kategori]** dengan tombol **+ Add Overbudget** dan tabel: No, No. Overbudget, Item, Amount, Status, Pembuat Pengajuan, Option. Tombol Add tampil bila user punya hak **Add** di menu Pengajuan Biaya (Role & Permission) **dan** termasuk tim program; **Superadmin** selalu dapat; **Admin** dapat bila punya hak Add (keduanya dengan peringatan bukan tim, PB-FR-05). | `[VID-2]`, `[JPB-05]`, `[JPB-01]` |

### 4.2 Form Add Pengajuan

Form dibuka sebagai **halaman penuh** `[VID-3]`. Breadcrumb: Pengajuan Biaya / Pengajuan Biaya / [No. Program] / Add Pengajuan.

| ID | Kebutuhan | Sumber |
|---|---|---|
| PB-FR-05 | **Kepala**: data program (PB-FR-03, hanya tampil); **Jenis** wajib dipilih di form: **Kasbon** atau **Direct Payment** `[JPB-05]`; **Kategori** (dari blok yang ditekan, terkunci); **Request By** = user login (terkunci); bila user login bukan tim program (mis. Admin), tampil peringatan "[nama] bukan tim program ini" `[VID-3]`; **Tanggal** = hari ini (terkunci) `[VID-3]`; **Deskripsi / Keterangan** wajib `[VID-3]`. | `[VID-3]`, `[JPB-05]` |
| PB-FR-06 | **Informasi Pengajuan** (hanya tampil): satu baris per item kategori itu — item costing dan item baru dari Overbudget yang disetujui. Kolom **Estimasi** (Qty, Price/Unit, Total Budget), **Terpakai** (Qty, Total Terpakai), **Overbudget** (Qty, Total disetujui), **Sisa**; baris **Total kategori** dengan Sisa kategori. | `[VID-3]`, `[JPB-10]` + `[USULAN]` (kolom Sisa) |
| PB-FR-07 | **Tabel Pengajuan**: satu baris per item: Item, **Qty** (isian), **Price/Unit** (terisi Price/Unit budget, **boleh diubah**) `[JPB-04]`, **Total Pengajuan** = Qty × Price/Unit, plus **Grand Total**, **Sisa kategori**, dan **sisa kategori setelah pengajuan ini**. Semua item kategori dapat diisi selama Sisa kategori > 0; bila Sisa kategori 0 seluruh isian terkunci (acuan mengunci per item `[VID-3]`; Labmania per kategori `[JPB2-15]`). Minimal satu item dengan Total > 0. | `[VID-3]`, `[JPB-04]`, `[JPB2-15]` |
| PB-FR-08 | **Budget harus mencukupi per kategori** `[JPB-04]`, `[JPB2-15]`: **Grand Total** pengajuan ≤ **Sisa total kategori** (Fixed Cost atau Variable Cost). Satu item boleh melebihi sisa item-nya selama total kategori cukup; tabel menandai item yang melebihi sisa item-nya (informasi, tidak menghalangi). Bila Grand Total melebihi sisa kategori, Save ditolak dengan pesan untuk mengajukan Overbudget. Qty boleh desimal (mis. 0,5 hari) `[USULAN]`. | `[JPB-04]`, `[JPB2-15]` |
| PB-FR-09 | **Rekening tujuan** wajib: **Bank**, **No. Rekening**, **Nama Rekening** `[VID-3]`. Kasbon: rekening pengaju (terisi dari pengajuan Kasbon sebelumnya bila ada); Direct Payment: rekening penerima (vendor) `[USULAN]`. Label bagian berganti mengikuti jenis. | `[VID-3]` + `[USULAN]` |
| PB-FR-10 | **Bukti** (Upload Bukti, tarik & letakkan, beberapa file) `[VID-3]`. **Wajib untuk Direct Payment** (tagihan / nota), **opsional untuk Kasbon** `[USULAN]`. | `[VID-3]` + `[USULAN]` |
| PB-FR-11 | **Save**: memeriksa isian wajib dan batas budget, memberi **Nomor Pengajuan** otomatis dengan format **sementara** `[JPB-08]` **`LMI/KS/MM/yy-zzz`** (Kasbon) atau **`LMI/DP/MM/yy-zzz`** (Direct Payment), pola `[FPN]`, urut per kode per bulan, lalu menyimpan dengan status **Menunggu Approval**. Tidak ada simpan Draft `[USULAN]`. **Back** kembali ke halaman program tanpa menyimpan. | `[VID-3]`, `[FPN]`, `[JPB-08]` |

### 4.3 Overbudget

| ID | Kebutuhan | Sumber |
|---|---|---|
| PB-FR-12 | **Add Overbudget** (per kategori, pop up): (a) tabel **item costing** kategori itu — isi **Qty tambahan** dan **Price/Unit** (default harga budget), Total dihitung; (b) **+ Item baru** di luar costing `[JPB-10]` — **Nama item**, Qty, Price/Unit; (c) **Alasan** wajib. Minimal satu baris dengan Total > 0. Save memberi nomor **`LMI/OB/MM/yy-zzz`** (sementara `[JPB-08]`) dan status **Menunggu Approval**. | `[VID-2]`, `[JPB-10]`, `[JPB-08]` |
| PB-FR-13 | Overbudget **Disetujui**: item costing — Qty dan Total Budget item bertambah (kolom Overbudget di Informasi Pengajuan); item baru — muncul sebagai baris item baru di kategori itu (tanda "item baru") dan dapat diajukan. Costing Penjualan Program Final **tidak berubah** `[USULAN]`. Ditolak / Batal tidak menambah budget. | `[VID-3]`, `[JPB-10]` + `[USULAN]` |

### 4.4 Approval

| ID | Kebutuhan | Sumber |
|---|---|---|
| PB-FR-14 | **Approval Pengajuan** (Manajemen, 1 level `[JPB-03]`): daftar pengajuan dan overbudget berstatus **Menunggu Approval**, tab **Pengajuan** dan **Overbudget**. Kolom: No, No. Pengajuan, No. Program, Kategori / Jenis, Deskripsi, Total, Request By, Tanggal, Action (Lihat, Proses). Proses menampilkan rincian item, posisi budget kategori, rekening tujuan, dan bukti. Hak proses = hak **Manage** di menu Approval Pengajuan (Role & Permission; default role Manajemen) `[USULAN]`. | `[PBU]`, `[JPB-03]` + `[USULAN]` (hak Manage) |
| PB-FR-15 | **Setujui** → status **Disetujui** (tercatat penyetuju & waktu). **Tolak** → status **Ditolak**, **alasan wajib**, budget kembali tersedia. Tidak ada setuju sebagian; pengaju **tidak dapat memproses pengajuannya sendiri** `[USULAN]`. Pengajuan Disetujui menunggu pencairan Finance (menyusul `[JPB-02]`). | `[PBU]`, `[JPB-02]` + `[USULAN]` |
| PB-FR-16 | **Pencairan** — **ditunda** `[JPB-02]`. Catatan rancangan: dilakukan Finance atas pengajuan Waiting Payment (tanggal cair, bukti transfer); kemungkinan lewat modul `request_payment` aplikasi (Q-PB-11). Sampai modul itu ada, pembayaran dicatat dengan **Tandai Paid** (PB-FR-23). | `[JPB-02]` |

### 4.4a Tab & progres pengajuan

| ID | Kebutuhan | Sumber |
|---|---|---|
| PB-FR-21 | Menu **Pengajuan Biaya** dibagi **2 tab**: (1) **Ajukan Biaya** — daftar program (PB-FR-02) untuk membuka halaman program dan Add Pengajuan; (2) **Sudah Diajukan** — daftar semua pengajuan **Kasbon / Direct Payment** pada program yang dapat dilihat user (PB-FR-20), kolom: No, **No. Pengajuan** (nomor, badge jenis, tanggal), **Program** (nomor — membuka halaman program, judul), **Kategori / Deskripsi**, **Total**, **Request By**, **Progres**, Action (gear: Lihat; Batalkan bila miliknya dan Waiting Approval; **Tandai Paid** bila Waiting Payment dan punya hak). Filter **jenis** (Kasbon / Direct Payment) dan **progres**, pencarian nomor, program, deskripsi, pengaju; urut tanggal terbaru. Setiap tab menampilkan jumlah datanya. Overbudget tidak tampil di tab ini `[USULAN]`. Nomor tetap per jenis: Kasbon `LMI/KS/…`, Direct Payment `LMI/DP/…` (PB-FR-11). | `input/Invoice & Pengajuan/2026-11-05-tab-pengajuan-progress/` + `[USULAN]` (kolom, filter, overbudget tidak ikut) |
| PB-FR-22 | **Progres pengajuan** tampil sebagai langkah **Waiting Approval → Waiting Payment → Paid** (langkah selesai bertanda ✓, langkah sekarang menyala). **Ditolak** / **Batal** menghentikan progres di langkah pertama dan ditandai ×. Label yang sama dipakai di tabel halaman program, Approval, dan Lihat Pengajuan (baris Progres + riwayat status). Overbudget tetap memakai label Menunggu Approval / Disetujui / Ditolak / Batal. | `input/Invoice & Pengajuan/2026-11-05-tab-pengajuan-progress/` + `[USULAN]` (tampilan Ditolak/Batal) |
| PB-FR-23 | **Tandai Paid** (sementara, sebelum modul pencairan PB-FR-16): pada pengajuan **Waiting Payment**, user dengan hak **Manage** di menu Pengajuan Biaya (Role & Permission; Superadmin dan Admin selalu dapat) mengisi **Tanggal bayar** (wajib; tidak sebelum tanggal pengajuan dan tidak di masa depan) dan **Keterangan / bukti transfer** (opsional). Status menjadi **Paid** (tercatat oleh & tanggal), tidak dapat diubah lagi, dan tetap dihitung sebagai Aktual. Dicatat di Activity Log (aksi **Tandai Paid**). | `input/Invoice & Pengajuan/2026-11-05-tab-pengajuan-progress/` + `[USULAN]` (pelaku, isian) — Q-PB-17 |

### 4.5 Batal, lihat, dan akses

| ID | Kebutuhan | Sumber |
|---|---|---|
| PB-FR-17 | Pengaju dapat **Batalkan** pengajuan / overbudget miliknya selama **Menunggu Approval** (konfirmasi). Status **Batal**; budget kembali tersedia. Pengajuan Disetujui tidak dapat diubah atau dibatalkan `[USULAN]`. Perbaikan isian = batalkan lalu ajukan ulang `[USULAN]`. | `[USULAN]` |
| PB-FR-18 | **Lihat Pengajuan**: kepala program, jenis, kategori, nomor, tanggal, deskripsi, Request By, rincian item (Qty, Price/Unit, Total), rekening tujuan, bukti, dan riwayat status (diajukan; disetujui / ditolak beserta alasan; batal). | `[VID-2]` + `[USULAN]` |
| PB-FR-19 | **Tim program** `[JPB-01]` = (a) user **Sales** pada quotation program itu yang berstatus **Deal** (`quotation.id_sales`; Public dapat lebih dari satu sales) `[JPB2-16]`; (b) **Trainer** Penjualan Program, lewat **akun login** di Master Trainer (`ms_trainer.id_user`, `fr-nfr.md` FR-59) `[JPB2-13]`; (c) user **Manajemen** — ditentukan dari **posisi di Master User**, bukan Department / Division; daftar posisi **menyusul** `[JPB2-14]` (sementara: jabatan Direktur). Program tanpa quotation Deal / trainer tetap dapat diajukan oleh Manajemen. | `[JPB-01]`, `[JPB2-13]`, `[JPB2-14]`, `[JPB2-16]` |
| PB-FR-20 | **Hak data** `[JPB-07]`: halaman program, form, dan Lihat Pengajuan hanya dapat dibuka **tim program**; **Superadmin** (akun developer, group id 1, lolos semua permission) dan **Admin** (akun user Labmania) membuka semua data program `[JPB2-34]`. Hak **menu & aksi** mengikuti **Role & Permission**: Pengajuan Biaya (View, Add), Approval Pengajuan (View, Manage). Batas waktu: selama program belum **Closed** `[JPB-12]` — Closed dilakukan lewat **Close Program** di modul Manage Peserta (`docs/fr-nfr-manage-peserta.md` MP-FR-03, MP-FR-09, `input/Invoice & Pengajuan/2026-11-04-manage-peserta/`): program Closed tidak dapat ditambah pengajuan / overbudget baru (tombol Add tidak tampil, server menolak); pengajuan yang ada tetap dapat dilihat dan yang Menunggu tetap dapat diproses `[USULAN]`. | `[JPB-07]`, `[JPB-12]` + `[USULAN]` (pemetaan aksi) |

---

## 5. Kebutuhan Non-Fungsional (NFR)

| ID | Kategori | Kebutuhan | Kriteria uji | Lingkup | Sumber |
|---|---|---|---|---|---|
| PB-NFR-01 | Integritas data | Budget, Terpakai, dan Sisa dihitung ulang **di server** dari `penjualan_program_biaya`, overbudget disetujui, dan detail pengajuan aktif — bukan dari isian klien. Pemeriksaan batas **sisa kategori** (PB-FR-08) dan penyimpanan header + detail + lampiran dalam **satu transaksi** dengan penguncian baris program. | Dua Save bersamaan yang masing-masing pas dengan sisa kategori → satu ditolak | PB-FR-08, PB-FR-11 | `[USULAN]` |
| PB-NFR-02 | Integritas data | Nomor pengajuan / overbudget dibuat server saat Save, di transaksi yang sama, kolom `nomor` unik (pola IVC-NFR-03). | Tidak ada nomor ganda pada Save bersamaan | PB-FR-11, PB-FR-12 | `[USULAN]` |
| PB-NFR-03 | Integritas data | Transisi status hanya sesuai bagian 6, diperiksa di server (Setujui / Tolak hanya dari Menunggu Approval). Pengajuan Disetujui tidak dapat diubah. | Request Setujui untuk pengajuan Batal ditolak (422) | PB-FR-14 – PB-FR-17 | `[USULAN]` |
| PB-NFR-04 | Audit | Ajukan, setujui, tolak, batal, dan overbudget dicatat `write_log()` modul **"Pengajuan Biaya"** (sub modul Pengajuan / Overbudget / Approval), rincian item sebagai `detail_changes`. Tabel memakai kolom jejak `created_by`, `created_date`, `updated_by`, `updated_date`; aksi tampil di Activity Log. | Setujui 1 pengajuan → 1 log dengan status lama → baru | Semua UC | `[STD]` |
| PB-NFR-05 | Keamanan | **Dua lapis** `[JPB-07]`: (1) menu & aksi via permission role (Role & Permission, `Pengajuan_Biaya.View/Add`, `Approval_Pengajuan_Biaya.View/Manage`, diperiksa `has_permission()` / `auth->restrict()`); (2) **data** — setiap request ke program / pengajuan memeriksa user ∈ tim program, Superadmin, atau Admin. Pengaju tidak dapat memproses pengajuannya sendiri. | User bukan tim membuka URL program → 403; tanpa `Add` → tombol tidak tampil, request ditolak | PB-FR-04, PB-FR-14, PB-FR-19, PB-FR-20 | `[JPB-07]` + `[USULAN]` (nama permission) |
| PB-NFR-06 | File | Bukti: PDF, JPG, PNG; maks. 5 MB per file; disimpan di luar web root dengan nama acak, diunduh lewat controller yang memeriksa hak akses (PB-NFR-05). | File .exe ditolak; file 6 MB ditolak | PB-FR-10 | `[USULAN]` |
| PB-NFR-07 | Akurasi | Uang DECIMAL(15,2), Qty DECIMAL(10,2); tampil rupiah tanpa desimal, isian uang dengan pemisah ribuan titik (`fr-nfr.md` NFR-12). Qty Estimasi memakai fungsi faktor satuan yang sama dengan simulasi costing (PJ-NFR-05). | Budget item = angka costing Penjualan Program Final | PB-FR-06 | `[JPB-04]` + `[USULAN]` |
| PB-NFR-08 | Platform | CI3 HMVC, MySQL, Bootstrap + jQuery; nama tabel/kolom = ERD halaman *ERD Pengajuan Biaya*. Tabel kasbon konsultan (`kons_tr_kasbon_project_*`) **tidak dipakai** (rekomendasi, Q-PB-11). | Code review | Semua | `[STD]` + `[USULAN]` |

---

## 6. Status

**Pengajuan (Kasbon / Direct Payment)**

| Dari | Aksi | Oleh | Ke | Efek |
|---|---|---|---|---|
| — | Save | Tim program | **Waiting Approval** | Nomor dibuat (`LMI/KS/…` Kasbon, `LMI/DP/…` Direct Payment); budget item terpakai |
| Waiting Approval | Setujui | Manajemen | **Waiting Payment** | Menunggu pembayaran |
| Waiting Payment | Tandai Paid (tanggal bayar) | Hak Manage Pengajuan Biaya (sementara, Q-PB-17) | **Paid** | Tercatat pembayar & tanggal bayar; tetap dihitung Aktual |
| Waiting Approval | Tolak (alasan) | Manajemen | **Ditolak** | Budget kembali tersedia |
| Waiting Approval | Batalkan | Pengaju | **Batal** | Budget kembali tersedia |

Nilai di database tetap `menunggu` (Waiting Approval), `disetujui` (Waiting Payment), `paid`, `ditolak`, `batal`; label tampilan memakai istilah klien `input/Invoice & Pengajuan/2026-11-05-tab-pengajuan-progress/`.

**Overbudget**: Menunggu Approval → **Disetujui** (budget item bertambah / item baru tersedia) / **Ditolak** / **Batal** (oleh pengaju).

Paid, Ditolak, dan Batal adalah status akhir pengajuan. Saat modul pencairan Finance dibuat, Paid diisi dari pencairan itu (PB-FR-16) `[JPB-02]`.

**Status program di daftar** (turunan, tidak disimpan): **Belum ada pengajuan** · **n Waiting Approval** · **Berjalan**.

---

## 7. Data (ringkas — rinci di ERD)

| Tabel | Isi |
|---|---|
| `pengajuan_biaya` | nomor, id_penjualan, tipe_biaya (fixed / variable), jenis (kasbon / direct_payment), tanggal, deskripsi, total, bank, no_rekening, nama_rekening, status (menunggu / disetujui / **paid** / ditolak / batal), request_by, approved_by, approved_date, alasan_tolak, closed_by, closed_date, **paid_by, paid_date, paid_keterangan** (PB-FR-23) |
| `pengajuan_biaya_detail` | id_pengajuan, **id_penjualan_biaya** (item costing) **atau id_overbudget_detail** (item baru), item (salinan), qty, harga, total |
| `pengajuan_biaya_lampiran` | id_pengajuan, nama_file, file_path |
| `pengajuan_overbudget` | nomor, id_penjualan, tipe_biaya, tanggal, alasan, total, status, request_by, approved_by, approved_date, alasan_tolak, closed_by, closed_date |
| `pengajuan_overbudget_detail` | id_overbudget, id_penjualan_biaya (NULL = **item baru**), item, qty, harga, total |
| `ms_trainer` | + `id_user` (akun login trainer, NULL) — disetujui `[JPB2-13]`, `fr-nfr.md` FR-59 |
| Hak akses | tabel existing `groups`, `user_groups`, `permissions`, `group_permissions`, `user_permissions`, `menus` — halaman *ERD Matrix Akses* |

---

## 8. Pertanyaan Terbuka

### 8.1 Masih terbuka

| ID | Pertanyaan | Mempengaruhi |
|---|---|---|
| Q-PB-11 | **Tabel kasbon yang sudah ada** — jawaban analis: folder `application/` memakai basis kode ERP **"CONSULTANT"** (aplikasi yang sama dengan video acuan). Di dalamnya sudah ada modul kasbon milik **modul konsultan**: tabel `kons_tr_kasbon_project_header` dan rinciannya per kategori (`_akomodasi`, `_others`, `_lab`, `_subcont…`), overbudget `kons_tr_kasbon_req_ovb_*`, bukti `kons_tr_kasbon_project_bukti_penggunaan`; semuanya terhubung ke **SPK konsultan** (`kons_tr_spk_penawaran`, `kons_tr_spk_budgeting_*`, `kons_master_biaya`). Modul terkait: `kasbon_project`, `approval_kasbon_project`, `approval_kasbon_overbudget`, `request_payment` + `approval_request_payment` (pencairan, membaca header kasbon), `expense_report_project` (realisasi). Jadi tabel itu **bukan** buatan program Labmania — ikut terbawa dari aplikasi asal. **Rekomendasi:** tabel baru Labmania (bagian 7), karena budget Labmania berasal dari Penjualan Program dengan 2 kategori, bukan SPK dengan tabel per kategori. **Perlu dikonfirmasi IT developer:** (a) setuju tabel baru; (b) pencairan nanti memakai modul `request_payment` yang ada (perlu penyesuaian) atau dibuat baru. | ERD, PB-NFR-08, PB-FR-16 |
| Q-PB-17 | **Tandai Paid**: siapa yang menandai (Finance / Admin)? Bukti transfer wajib diunggah? Setelah modul pencairan ada, tetap ditandai manual atau otomatis dari pencairan? Sementara: hak Manage Pengajuan Biaya, tanggal bayar wajib, keterangan opsional. `input/Invoice & Pengajuan/2026-11-05-tab-pengajuan-progress/` | PB-FR-16, PB-FR-23 |
| Q-PB-12 | Status **Closed** program (batas akhir pengajuan) — **sebagian terjawab** `input/Invoice & Pengajuan/2026-11-04-manage-peserta/`: ditutup lewat **Close Program** di Manage Peserta oleh role yang diberi hak Manage. Kapan boleh di-Close masih Q-MPS-02 (`docs/fr-nfr-manage-peserta.md`). | PB-FR-20 |
| Q-PB-14 | **Posisi** apa saja di Master User yang termasuk **Manajemen**? Terjawab sebagian `[JPB2-14]`: ditentukan dari Master User (bukan Department / Division); **daftar posisi menyusul dari user**. Group Akses dihapus `input/Invoice & Pengajuan/2026-11-04-role-permission/`; sementara memakai jabatan **Direktur** — disetujui user sebagai data sementara `input/Invoice & Pengajuan/2026-11-04-jawaban-role-permission/`. 05-11-2026 `input/Invoice & Pengajuan/2026-11-05-master-role-override/`: Division dan Jabatan diganti **Role** dari Master Role, jadi Manajemen = role tertentu; sementara role **Direktur** (`fr-nfr.md` Q-37). | PB-FR-19 |

### 8.2 Sudah terjawab (04-11-2026, `[JPB-n]`, `[JPB2-n]`)

| ID | Jawaban |
|---|---|
| Q-PB-01 | Tim program = Sales (quotation), Trainer (Penjualan Program), Manajemen; tidak ada field PIC terpisah. Dibuatkan **Matrix Akses Menu** (`fr-nfr.md` FR-54 – FR-58). |
| Q-PB-02 | Pencairan oleh **Finance**, **menyusul**; sekarang hanya modul pengajuan. |
| Q-PB-03 | Kategori **Fixed Cost / Variable Cost**; approval **1 level** — sementara. |
| Q-PB-04 | Budget dari **Penjualan Program Final** (target peserta); Price/Unit boleh berbeda asal budget cukup (rincian Q-PB-15). |
| Q-PB-05 | **PO tidak ada**; satu tombol **Add Pengajuan**, jenis Kasbon / Direct Payment dipilih di form. |
| Q-PB-06 | Pelaporan realisasi **menyusul**; cakupan pengajuan + approval. |
| Q-PB-07 | Hanya **user terkait** yang membuka; **Admin** membuka semua. |
| Q-PB-08 | Format nomor **sementara**: `LMI/KS/MM/yy-zzz`, `LMI/DP/MM/yy-zzz`, `LMI/OB/MM/yy-zzz`. |
| Q-PB-09 | Jurnal **menyusul**. |
| Q-PB-10 | Overbudget **boleh** menambah item baru. |
| Q-PB-13 | **Setuju** `[JPB2-13]`: field Akun login di Master Trainer (`fr-nfr.md` FR-59). |
| Q-PB-15 | **Per kategori** `[JPB2-15]`: total Fixed Cost dan total Variable Cost. |
| Q-PB-16 | Hanya quotation **Deal** `[JPB2-16]`. |

---

## 9. Riwayat Perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| draf 1 | 04-11-2026 | Penyusunan awal dari `input/Invoice & Pengajuan/2026-11-04-pengajuan-biaya-as-is/`: SPK → Penjualan Program Final, budget = costing, PIC Program (PJ-FR-26), Kasbon / Direct Payment / Overbudget, Approval Manajemen, Pencairan Keuangan. Q-PB-01 – Q-PB-12. |
| draf 2 | 04-11-2026 | `input/Invoice & Pengajuan/2026-11-04-jawaban-pengajuan-biaya/` (`[JPB-n]`): **tim program** (Sales quotation, Trainer, Manajemen) menggantikan field PIC — PJ-FR-26 dibatalkan; **Matrix Akses Menu** (`fr-nfr.md` FR-54 – FR-58); **pencairan ditunda** — menu Pencairan, aktor Keuangan, status Dicairkan, kolom `cair_*`, UC-46 dihapus dari cakupan; **satu tombol Add Pengajuan** + pilih jenis di form; overbudget **item baru** (`id_overbudget_detail`, `id_penjualan_biaya` NULL); hak data user terkait / Admin semua; format nomor sementara; batas Closed menyusul. Q-PB-01 – Q-PB-10 terjawab; Q-PB-11 dijawab analis (perlu konfirmasi IT); Q-PB-13 – Q-PB-16 baru. Mockup draf 14a. |
| draf 3 | 04-11-2026 | `input/Invoice & Pengajuan/2026-11-04-jawaban-pengajuan-biaya-2/` (`[JPB2-n]`): batas budget **per kategori** (bagian 1, UC-43, PB-FR-06 – PB-FR-08, PB-NFR-01); tim Sales hanya quotation **Deal** (PB-FR-19); akun login trainer disetujui (`fr-nfr.md` FR-59); Manajemen dari posisi Master User, daftar menyusul (Q-PB-14); **Superadmin** (developer, group id 1) dan **Admin** (user Labmania, menu dapat dibatasi) — keduanya melihat semua data (aktor, PB-FR-02, PB-FR-04, PB-FR-20, PB-NFR-05). Q-PB-13, -15, -16 terjawab. Mockup draf 14b. |
| draf 3a | 04-11-2026 | `input/Invoice & Pengajuan/2026-11-04-role-permission/`: rujukan **Matrix Akses Menu** → **Role & Permission** (role = Division + Jabatan); Manajemen sementara = jabatan Direktur (Q-PB-14 masih menunggu). Draf 3: `input/backup/fr-nfr-pengajuan-biaya_sebelum-role-permission_04-11-2026.md`. |
| draf 3b | 04-11-2026 | `input/Invoice & Pengajuan/2026-11-04-jawaban-role-permission/`: Manajemen = jabatan Direktur disetujui sebagai data sementara (Q-PB-14 tetap menunggu daftar posisi). Draf 3a: `input/backup/fr-nfr-pengajuan-biaya_sebelum-superadmin-bawaan_04-11-2026.md`. |
| draf 3c | 04-11-2026 | `input/Invoice & Pengajuan/2026-11-04-manage-peserta/`: status **Closed** kini ada lewat **Close Program** (Manage Peserta) — PB-FR-20 menolak pengajuan baru untuk program Closed; Q-PB-12 sebagian terjawab. Draf 3b: `input/backup/fr-nfr-pengajuan-biaya_sebelum-manage-peserta_04-11-2026.md`. |
| draf 4 | 05-11-2026 | `input/Invoice & Pengajuan/2026-11-05-tab-pengajuan-progress/`: menu Pengajuan Biaya dibagi tab **Ajukan Biaya** / **Sudah Diajukan** (PB-FR-21); progres **Waiting Approval → Waiting Payment → Paid** (PB-FR-22, bagian 6); **Tandai Paid** sementara (PB-FR-23, Q-PB-17); nomor per jenis (KS / DP) ditegaskan. ERD: `pengajuan_biaya.status` + `paid`, kolom `paid_by`, `paid_date`, `paid_keterangan`. Draf 3c: `input/backup/fr-nfr-pengajuan-biaya_sebelum-tab-progress_05-11-2026.md`. |
| draf 4a | 05-11-2026 | `input/Invoice & Pengajuan/2026-11-05-master-role-override/`: hak akses memakai Role dari **Master Role** + **Override Permission** (bagian 1); Manajemen = **role** Direktur (sebelumnya jabatan Direktur; bagian 2, Q-PB-14). Draf 4: `input/backup/fr-nfr-pengajuan-biaya_sebelum-master-role_05-11-2026.md`. |

---

# Bagian 4 — Jejak Data & Modul Activity Log Labmania

- **Versi:** draf 2b — 05-11-2026
- **Sumber:** `input/Master - Buat Program/2026-10-30-activity-log-akses-retensi/` (tanpa login/logout, simpan 60 hari, akses Manajemen & Admin Developer); `input/Master - Buat Program/2026-10-30-histori-activity-log/`; standar `write_log()` di `AGENTS.md` `[STD]`
- **Status:** DRAF — pertanyaan terbuka di bagian 7
- **Modul terkait:** berlaku lintas modul — Master Data (`docs/fr-nfr.md`), Master Program (`docs/fr-nfr-master-program.md`), Penjualan Program (`docs/fr-nfr-penjualan-program.md`), Quotation (`docs/fr-nfr-quotation.md`)
- **Mockup:** `ui-mockup/produk/index.html` → kolom "Dibuat / Diubah" di setiap daftar, menu gear "Lihat Riwayat", modul **Activity Log** (draf 12a)
- **ERD:** `docs/diagrams/erd.drawio` halaman *ERD Activity Log*
- **Flow:** `docs/diagrams/bpmn-to-be.drawio` halaman *Level 1 — Activity Log*

Penomoran memakai awalan **LOG-**.

| Penanda | Arti |
|---|---|
| `[HAL-1]` | `input/Master - Buat Program/2026-10-30-histori-activity-log/` — jejak "created by, created date & hours" di semua modul; satu modul khusus untuk melihat pergerakan di sistem: menambah, mengedit, menghapus, lengkap |
| `[AKR-n]` | `input/Master - Buat Program/2026-10-30-activity-log-akses-retensi/` — jawaban n: (2) login/logout tidak dicatat, (3) log disimpan 60 hari, (4) akses Manajemen & Admin Developer, (5) tampil di daftar dan modul khusus |
| `[STD]` | standar proyek di `AGENTS.md` (`write_log()`, Before/After, `detail_changes`) |
| `[USULAN]` | usulan analis, **belum** dinyatakan klien |

---

## 1. Ringkasan Kebutuhan

Setiap data di **Master Data, Master Program, Penjualan Program, dan Quotation** menyimpan **siapa yang membuat dan
kapan (tanggal & jam)** `[HAL-1]`, serta siapa dan kapan terakhir mengubah `[USULAN]`. Semua pergerakan data di sistem
— **tambah, edit, hapus**, dan aksi lain (aktif/nonaktif, finalkan, batalkan, kirim & cetak, deal, cetak dokumen) — tercatat
dan dapat dilihat di **satu modul khusus: Activity Log** `[HAL-1]`, lengkap dengan field yang berubah sebelum → sesudah `[STD]`.

```
Aksi pengguna di modul mana pun ──► data tersimpan + kolom jejak (created_* / updated_* / deleted_*)
                                   └► write_log() ──► tabel logs ──► menu Activity Log (semua) / "Lihat Riwayat" (satu data)
```

---

## 2. Aktor

| Aktor | Peran | Sumber |
|---|---|---|
| Semua pengguna yang login | Aksinya tercatat otomatis (tidak ada langkah tambahan) | `[HAL-1]` |
| Manajemen, Admin Developer | Melihat Activity Log dan riwayat data | `[AKR-4]` |

---

## 3. Tabel Use Case

| Kode | Use case | Aktor | Prasyarat | Alur singkat | Hasil |
|---|---|---|---|---|---|
| UC-38 | Melihat Activity Log | Manajemen, Admin Developer | Login sebagai group Manajemen / Admin Developer | Buka menu Activity Log, saring (modul, aksi, pengguna, rentang tanggal) atau cari, buka **Detail** satu catatan | Daftar pergerakan data; detail field sebelum → sesudah |
| UC-39 | Melihat riwayat satu data | Manajemen, Admin Developer | Data ada di daftar mana pun | Pilih menu gear **Lihat Riwayat** pada baris data | Activity Log tersaring ke data tersebut (dibuat, diubah, status, dihapus) |

---

## 4. Kebutuhan Fungsional (FR)

| ID | Kebutuhan | Sumber |
|---|---|---|
| LOG-FR-01 | **Kolom jejak** pada semua tabel data milik Master Data, Master Program, Penjualan Program, dan Quotation (header maupun detail): **`created_by`** (pengguna pembuat), **`created_date`** (tanggal & jam dibuat), **`updated_by`**, **`updated_date`** (pengguna & tanggal-jam perubahan terakhir); tabel yang datanya dapat dihapus memakai **hapus lunak** `deleted_by`, `deleted_date`. Terisi otomatis oleh sistem dari pengguna login dan jam server; tidak dapat diisi/diubah pengguna. | `[HAL-1]` + `[USULAN]` (updated_*, deleted_*, hapus lunak) |
| LOG-FR-02 | Setiap **daftar** di keempat modul menampilkan kolom **Dibuat / Diubah**: "Dibuat **nama** DD-MM-YYYY HH:mm" dan, bila pernah diubah, "Diubah **nama** DD-MM-YYYY HH:mm", sebelum kolom Action; terlihat oleh semua pengguna yang membuka daftar `[USULAN]`. Jejak tampil **di daftar dan di modul khusus Activity Log** `[AKR-5]`. | `[HAL-1]`, `[AKR-5]` + `[USULAN]` (letak kolom) |
| LOG-FR-03 | Modul **Activity Log** (menu sendiri, satu modul) menampilkan **semua pergerakan data** di sistem, terbaru di atas: No, **Waktu** (DD-MM-YYYY HH:mm), **Pengguna**, **Modul** & sub modul, **Aksi**, **Data** (kode/nomor), **Perubahan** (ringkas, field sebelum → sesudah), Action **Detail**. | `[HAL-1]` + `[USULAN]` (kolom) |
| LOG-FR-04 | Aksi yang tercatat: **Tambah, Edit, Hapus** `[HAL-1]`; **Aktifkan / Nonaktifkan**, **Finalkan**, **Batalkan**, **Kirim & Cetak**, **Deal**, **Cetak** (Surat Penawaran, cetak ulang Quotation, Confirmation Letter), termasuk peserta baru yang ditambahkan dari form Quotation `[USULAN]`. Melihat/membuka data tidak dicatat `[USULAN]`. **Login / logout tidak dicatat** `[AKR-2]`. | `[HAL-1]`, `[AKR-2]` + `[USULAN]` |
| LOG-FR-05 | **Saring & cari**: modul, aksi, pengguna, rentang tanggal (dari – s.d.), dan pencarian teks (data, keterangan, pengguna); paginasi. | `[USULAN]` |
| LOG-FR-06 | **Detail** satu catatan (popup): waktu, pengguna, modul › sub modul, aksi, keterangan, dan tabel **Field — Sebelum (merah) — Sesudah (hijau)**; perubahan baris detail (item biaya, peserta, Keterangan) ditampilkan per baris (`detail_changes`). Tambah menampilkan isi data baru; Hapus menampilkan isi data terakhir. | `[STD]` + `[USULAN]` |
| LOG-FR-07 | **Lihat Riwayat** di menu gear setiap baris data membuka Activity Log yang tersaring ke data itu, dengan tombol "Tampilkan semua". | `[USULAN]` |
| LOG-FR-08 | Catatan Activity Log **tidak dapat diubah atau dihapus** oleh pengguna; hanya penghapusan otomatis setelah masa simpan (LOG-NFR-06). Data yang dihapus tetap terlacak selama masa simpan (hapus lunak + catatan Hapus). | `[AKR-3]` + `[USULAN]` |
| LOG-FR-09 | **Hak akses**: menu **Activity Log** dan menu gear **Lihat Riwayat** hanya tampil dan dapat dibuka oleh **Manajemen** dan **Admin Developer** — kini **role Superadmin** (`docs/fr-nfr.md` FR-56); Manajemen ditentukan dari **Role** di Master User (sementara role Direktur; `docs/fr-nfr-pengajuan-biaya.md` Q-PB-14, `docs/fr-nfr.md` Q-37). Role **Admin** dapat diberi hak lewat **Role & Permission** (`docs/fr-nfr.md` FR-54 – FR-58, `input/Invoice & Pengajuan/2026-11-04-role-permission/`); hak View Activity Log lewat **Override Permission** (FR-65) juga tetap dibatasi aturan ini `input/Invoice & Pengajuan/2026-11-05-master-role-override/`; pengguna lain tidak melihat menu tersebut dan ditolak di server. | `[AKR-4]` |

---

## 5. Kebutuhan Non-Fungsional (NFR)

| ID | Kategori | Kebutuhan | Sumber |
|---|---|---|---|
| LOG-NFR-01 | Audit | Pencatatan memakai `write_log()` sesuai standar `AGENTS.md`: Edit menyimpan snapshot `old` (diambil **sebelum** update) dan `new`; header–detail menyertakan `detail_changes`; perbedaan format angka (0 vs 0.00) dan NULL ↔ 0 pada kolom nominal tidak dianggap perubahan; nama modul tampil otomatis di filter (`SELECT DISTINCT module`). | `[STD]` |
| LOG-NFR-02 | Integritas data | Simpan data, kolom jejak, dan `write_log()` dalam **satu transaksi**: bila log gagal, simpan dibatalkan. Kolom jejak diisi di server (bukan dari isian klien). | `[USULAN]` |
| LOG-NFR-03 | Keamanan | Tabel `logs` hanya-tambah (tanpa endpoint ubah/hapus dari aplikasi). Akses Activity Log & Lihat Riwayat dibatasi ke Manajemen dan Superadmin (Admin bila diberi hak) lewat permission role aplikasi, diperiksa di server (LOG-FR-09). | `[AKR-4]` + `[USULAN]` |
| LOG-NFR-04 | Kinerja | Daftar Activity Log: paginasi & penyaringan di server, indeks pada (`created_date`), (`module`, `action`), (`ref_table`, `ref_id`), (`created_by`). | `[USULAN]` |
| LOG-NFR-06 | Retensi | Log **disimpan 60 hari**; catatan dengan `created_date` lebih dari 60 hari dihapus otomatis oleh job harian (cron) `[USULAN]` (mekanisme). Kolom jejak pada data (Dibuat/Diubah oleh) tidak ikut terhapus. | `[AKR-3]` + `[USULAN]` |
| LOG-NFR-05 | Format | Tanggal & jam tampil **DD-MM-YYYY HH:mm** (24 jam, waktu server WIB); disimpan DATETIME. | `[STD]` (format tanggal) + `[USULAN]` |

---

## 6. Data

| Tabel | Isi | Sumber |
|---|---|---|
| `logs` | `id`, `module`, `sub_module`, `action`, `description`, `ref_table`, `ref_id`, `ref_no` (kode/nomor data), `data` (JSON: `old`, `new`, `detail_changes`), `created_by` → `users.id_user`, `created_date`, `ip_address` | `[STD]` + `[USULAN]` (kolom) |
| Semua tabel modul | Kolom jejak `created_by`, `created_date`, `updated_by`, `updated_date` (+ `deleted_by`, `deleted_date` bila dapat dihapus) → `users.id_user` | LOG-FR-01 |

---

## 7. Pertanyaan Terbuka

| ID | Pertanyaan | Mempengaruhi |
|---|---|---|
| Q-LOG-01 | Helper `write_log()` (`log_helper.php`) dan tabel log yang disebut `AGENTS.md` **tidak ada** di kode Labmania (diperiksa 30-10-2026). Dibuat baru sesuai ERD ini, atau disalin dari aplikasi Sentral lain yang sudah memakainya (nama kolom `logs` lalu mengikuti tabel aslinya, ERD diperbarui)? Keputusan tim dev. | LOG-NFR-01, ERD |
| Q-LOG-02 | ~~Login/logout dicatat?~~ **Terjawab** `[AKR-2]`: tidak, untuk saat ini. | — |
| Q-LOG-03 | ~~Retensi?~~ **Terjawab** `[AKR-3]`: 60 hari. Masih terbuka: perlu ekspor Excel/PDF? (usulan: tidak) | LOG-NFR-06 |
| Q-LOG-04 | ~~Siapa yang boleh membuka?~~ **Terjawab** `[AKR-4]`: Manajemen dan Admin Developer. | — |
| Q-LOG-05 | ~~Tampil di mana?~~ **Terjawab** `[AKR-5]`: di daftar dan di modul khusus Activity Log. | — |

---

## 8. Riwayat Perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| draf 1 | 30-10-2026 | Penyusunan awal dari `input/Master - Buat Program/2026-10-30-histori-activity-log/`: kolom jejak di semua modul (LOG-FR-01, -02), modul Activity Log (LOG-FR-03 – LOG-FR-08), NFR, tabel `logs`, UC-38, UC-39; mockup draf 12. |
| draf 2 | 30-10-2026 | `input/Master - Buat Program/2026-10-30-activity-log-akses-retensi/`: login/logout tidak dicatat (LOG-FR-04); log disimpan **60 hari** (LOG-NFR-06 baru, LOG-FR-08); akses **Manajemen & Admin Developer** (LOG-FR-09 baru, LOG-NFR-03, UC-38, UC-39); tampil di daftar + modul khusus dikonfirmasi (LOG-FR-02). Q-LOG-02, -04, -05 terjawab, Q-LOG-03 sebagian; Q-LOG-01 diperjelas. Mockup draf 12a. |
| draf 2a | 04-11-2026 | `input/Invoice & Pengajuan/2026-11-04-role-permission/`: group akses diganti **role** (Division + Jabatan) di Role & Permission — LOG-FR-09, LOG-NFR-03 menyebut Superadmin / Manajemen / Admin. Draf 2: `input/backup/fr-nfr-activity-log_sebelum-role-permission_04-11-2026.md`. |
| draf 2b | 05-11-2026 | `input/Invoice & Pengajuan/2026-11-05-master-role-override/`: Manajemen ditentukan dari **Role** (Master Role), bukan posisi; Override Permission tidak melewati aturan LOG-FR-09. Draf 2a: `input/backup/fr-nfr-activity-log_sebelum-master-role_05-11-2026.md`. |
