# Kebutuhan Fungsional & Non-Fungsional — Modul Invoice Labmania

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
