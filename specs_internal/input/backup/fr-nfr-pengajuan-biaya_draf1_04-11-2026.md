# Kebutuhan Fungsional & Non-Fungsional — Modul Pengajuan Biaya Labmania

- **Versi:** draf 1 — 04-11-2026
- **Sumber:** `input/Invoice & Pengajuan/2026-11-04-pengajuan-biaya-as-is/` (permintaan user + video `input/Invoice & Pengajuan/Menu Pengajuan Biaya - As Is.mp4`)
- **Status:** DRAF. Sebagian besar pemetaan ke Labmania adalah usulan analis; pertanyaan terbuka ada di bagian 8.
- **Modul terkait:** hulu Penjualan Program (`docs/fr-nfr-penjualan-program.md`: costing per penjualan = budget, PJ-FR-26 PIC Program); Master User (`docs/fr-nfr.md` FR-36 – FR-39); Master Rate (`docs/fr-nfr.md` FR-18 – FR-20, COA per item); Activity Log (`docs/fr-nfr-activity-log.md`)
- **ERD:** `docs/diagrams/erd.drawio` halaman *ERD Pengajuan Biaya* (draf 1)
- **Flow Process:** `docs/diagrams/bpmn-to-be.drawio` halaman *Level 1 — Pengajuan Biaya*
- **Use case:** `docs/workbook.xlsx` sheet *UC Non-BPMN* (UC-43 – UC-48)
- **Mockup:** `ui-mockup/produk/Labmania.html`, modul **Pengajuan Biaya** (draf 14)

Penomoran memakai awalan **PB-**.

| Penanda | Arti |
|---|---|
| `[PBU]` | permintaan user 04-11-2026: PIC penanggung jawab program mengajukan biaya program ke manajemen untuk dicairkan; "kurang lebih seperti" video, disesuaikan dengan Labmania |
| `[VID-1]` | video, daftar Pengajuan per SPK: Nomor SPK & Paket, Customer, Team / PIC, Status, Action |
| `[VID-2]` | video, halaman pengajuan per SPK: blok per kategori biaya dengan kartu Budget / Aktual / Sisa Budget, tombol Add Pengajuan Kasbon / Direct Payment / PO, tabel pengajuan (Req Number, Description, Total, Tipe, Request By, Status, Date, Option), blok Overbudget (ID Request, Amount, Status, Pembuat Pengajuan) |
| `[VID-3]` | video, form Add Pengajuan: kepala SPK, Request By (+ peringatan bukan tim), Tgl, Deskripsi wajib; tabel Informasi Pengajuan (Estimasi / Terpakai / Overbudget per item); tabel Pengajuan (Qty, Price/Unit, Total; baris habis budget terkunci); Bank, Bank Number, Account Name wajib; Upload Bukti; Back / Save |
| `[FPN]` | pola nomor dokumen `LMI/XX/MM/yy-zzz` dari `input/Master - Buat Program/2026-11-02-format-penomoran-penawaran-invoice/` |
| `[STD]` | standar proyek di `AGENTS.md` |
| `[USULAN]` | usulan analis, **belum** dinyatakan klien |

---

## 1. Ringkasan Kebutuhan

**PIC penanggung jawab program** mengajukan biaya program ke **Manajemen** agar biayanya **dicairkan** `[PBU]`.
Tampilan dan alurnya mengikuti menu Pengajuan di sistem acuan `[VID-1]` – `[VID-3]`, dengan hulu yang
disesuaikan ke Labmania:

| Konsep di sistem acuan | Di Labmania |
|---|---|
| SPK (project) | **Penjualan Program Final** (Public & B2B) `[USULAN]` |
| Project Leader / Team SPK | **PIC Program** — field baru di Penjualan Program, dipilih dari Master User (PJ-FR-26) `[USULAN]` |
| Budget per item (Estimasi) | **Costing Penjualan Program Final** (`penjualan_program_biaya`): Qty dari satuan × durasi × peserta, Price/Unit = nominal item `[USULAN]` |
| Kategori biaya (Akomodasi, Others) | **Fixed Cost** dan **Variable Cost** (kelompok costing yang sudah ada) `[USULAN]` |
| Add Pengajuan Kasbon / Direct Payment | **Kasbon** dan **Direct Payment** `[VID-2]` |
| Add Pengajuan PO | **Tidak dipakai** — Labmania belum punya modul pembelian `[USULAN]` (Q-PB-05) |
| Overbudget | **Pengajuan Overbudget** per item, disetujui Manajemen, menambah budget item `[VID-2]` + `[USULAN]` |
| Approval | **Manajemen** `[PBU]` |
| Pencairan | **Keuangan** mencatat pencairan `[USULAN]` (Q-PB-02) |
| Expense Report Project (pertanggungjawaban kasbon) | **Ditunda** (Q-PB-06) |

```
Penjualan Program Final (PIC Program, costing = budget)
   │
   ├─ Fixed Cost  : Budget │ Aktual │ Sisa ──► + Kasbon / + Direct Payment ──► Menunggu Approval ─┬─ Setujui ─► Disetujui ─ Cairkan ─► Dicairkan
   │                                     └─► + Overbudget ─► Menunggu Approval ─┬─ Setujui ─► budget item bertambah  ├─ Tolak ───► Ditolak
   │                                                                           └─ Tolak ───► Ditolak               └─ Batalkan (pengaju) ─► Batal
   └─ Variable Cost: (sama)
```

Rumus per item costing (`fr-nfr-master-program.md` bagian 8, MP-FR-06/07) `[USULAN]`:

| Satuan | Qty Estimasi | Contoh (durasi 2 hari, target 25 peserta) |
|---|---|---|
| Event | 1 | Honor Trainer: 1 × 2.125.000 |
| Day | durasi | Hotel Trainer: 2 × 375.000 |
| Peserta/Event | target peserta | Modul: 25 × 50.000 |
| Peserta/Day | target peserta × durasi | Makan Siang: 50 × 50.000 |

- **Total Budget item** = Qty Estimasi × Price/Unit + total Overbudget item yang **Disetujui**.
- **Terpakai item** = jumlah detail pengajuan Kasbon/Direct Payment berstatus **Menunggu Approval, Disetujui, atau Dicairkan** (Ditolak dan Batal tidak dihitung) — agar budget yang sedang diajukan tidak dapat diajukan dua kali `[USULAN]`.
- **Sisa item** = Total Budget item − Terpakai item. Kartu **Budget / Aktual / Sisa Budget** per kategori adalah jumlah itemnya `[VID-2]`.

### 1.1 Cakupan

| Termasuk | Tidak termasuk (ditunda / modul lain) |
|---|---|
| Daftar program yang dapat diajukan biayanya, halaman pengajuan per program | Pengajuan PO / pembelian (Q-PB-05) |
| Pengajuan Kasbon dan Direct Payment per item costing | Pertanggungjawaban kasbon / Expense Report, pengembalian sisa kasbon (Q-PB-06) |
| Pengajuan Overbudget per item | Jurnal akuntansi pencairan (Q-PB-09) |
| Approval Manajemen (setujui / tolak) | Approval bertingkat / batas nominal (Q-PB-03) |
| Pencatatan pencairan oleh Keuangan | Laporan cashflow program (menu "Cashflow Project" acuan) |
| Field PIC Program di Penjualan Program (PJ-FR-26) | |

### 1.2 Istilah

| Istilah | Arti |
|---|---|
| PIC Program | User Labmania yang ditunjuk sebagai penanggung jawab satu Penjualan Program dan berhak mengajukan biayanya. |
| Kasbon | Uang muka yang ditransfer ke rekening pengaju (biasanya PIC) sebelum biaya dikeluarkan. |
| Direct Payment | Pembayaran langsung ke rekening penerima (vendor / pihak ketiga) atas tagihan biaya program. |
| Overbudget | Permintaan tambahan budget untuk satu atau lebih item costing yang sisanya tidak cukup. |
| Budget / Aktual / Sisa | Lihat rumus di atas. "Aktual" di kartu = Terpakai. |

---

## 2. Aktor

| Aktor | Peran | Sumber |
|---|---|---|
| PIC Program | Mengajukan Kasbon / Direct Payment / Overbudget untuk program yang menjadi tanggung jawabnya; membatalkan pengajuannya selama Menunggu Approval | `[PBU]` |
| Manajemen | Menyetujui / menolak pengajuan dan overbudget; melihat semua program; menetapkan PIC Program | `[PBU]` + `[USULAN]` (PIC) |
| Keuangan | Mencatat pencairan pengajuan yang sudah disetujui | `[USULAN]` (Q-PB-02) |

---

## 3. Tabel Use Case

| Kode | Nama Use Case | User | Modul | Sub Modul | Input | Deskripsi | Output | Entity/Objek Terkait | FR |
|---|---|---|---|---|---|---|---|---|---|
| UC-43 | Mengajukan Biaya Program (Kasbon / Direct Payment) | PIC Program | Pengajuan Biaya | Pengajuan | Penjualan Program Final, kategori (Fixed / Variable), jenis (Kasbon / Direct Payment), deskripsi, Qty & Price/Unit per item, Bank, No. Rekening, Nama Rekening, bukti | Aktor membuka program di daftar Pengajuan Biaya, memilih Add Kasbon atau Add Direct Payment pada kategori biaya. Sistem menampilkan Informasi Pengajuan (Estimasi / Terpakai / Overbudget per item) dan tabel isian; item yang sisanya habis terkunci. Aktor mengisi Qty (dan Price/Unit bila perlu), rekening tujuan, deskripsi, dan bukti, lalu Save. Sistem memeriksa total per item ≤ sisa, memberi nomor, dan mengirim ke Manajemen. | Pengajuan berstatus Menunggu Approval dengan nomor `LMI/KS/…` atau `LMI/DP/…` | Pengajuan Biaya, Detail Pengajuan Biaya, Lampiran Pengajuan, Penjualan Program, Biaya Penjualan Program | PB-FR-01 – PB-FR-11 |
| UC-44 | Mengajukan Overbudget | PIC Program | Pengajuan Biaya | Overbudget | Penjualan Program Final, kategori, item costing, Qty & Price/Unit tambahan, alasan | Aktor memilih Add Overbudget pada kategori biaya, memilih item costing, mengisi tambahan Qty dan Price/Unit serta alasan, lalu Save. | Overbudget berstatus Menunggu Approval dengan nomor `LMI/OB/…` | Pengajuan Overbudget, Detail Overbudget, Biaya Penjualan Program | PB-FR-12, PB-FR-13 |
| UC-45 | Menyetujui / Menolak Pengajuan | Manajemen | Pengajuan Biaya | Approval | Pengajuan atau Overbudget berstatus Menunggu Approval; alasan (wajib bila tolak) | Aktor membuka menu Approval Pengajuan, melihat rincian dan posisi budget program, lalu menekan Setujui atau Tolak (dengan alasan). | Pengajuan Disetujui / Ditolak; Overbudget Disetujui menambah budget item | Pengajuan Biaya, Pengajuan Overbudget | PB-FR-14, PB-FR-15 |
| UC-46 | Mencairkan Biaya | Keuangan | Pengajuan Biaya | Pencairan | Pengajuan Disetujui; tanggal cair, bukti transfer (opsional) | Aktor membuka menu Pencairan, memilih pengajuan Disetujui, mengisi tanggal cair dan bukti transfer, lalu Simpan. | Pengajuan berstatus Dicairkan | Pengajuan Biaya, Lampiran Pengajuan | PB-FR-16 |
| UC-47 | Membatalkan Pengajuan | PIC Program | Pengajuan Biaya | Pengajuan | Pengajuan / Overbudget miliknya berstatus Menunggu Approval | Aktor memilih Batalkan dan mengonfirmasi. Budget yang dipesan kembali tersedia. | Pengajuan / Overbudget berstatus Batal | Pengajuan Biaya, Pengajuan Overbudget | PB-FR-17 |
| UC-48 | Melihat Posisi Budget Program | PIC Program, Manajemen, Keuangan | Pengajuan Biaya | Pengajuan | Penjualan Program Final | Aktor membuka daftar Pengajuan Biaya dan halaman program: Budget, Aktual, Sisa per kategori, daftar pengajuan dan overbudget beserta statusnya. | Posisi budget program | Penjualan Program, Biaya Penjualan Program, Pengajuan Biaya, Pengajuan Overbudget | PB-FR-01 – PB-FR-04, PB-FR-18 |

Kode UC melanjutkan UC-40 – UC-42 (Invoice). Aktor PIC Program dan Keuangan belum dikonfirmasi (Q-PB-01, Q-PB-02).

---

## 4. Kebutuhan Fungsional (FR)

### 4.1 Daftar & halaman program

| ID | Kebutuhan | Sumber |
|---|---|---|
| PB-FR-01 | Menu: modul **Pengajuan Biaya** dengan menu **Pengajuan Biaya** (daftar program), **Approval Pengajuan** (Manajemen), dan **Pencairan** (Keuangan). | `[VID-1]` + `[USULAN]` (menu approval & pencairan) |
| PB-FR-02 | **Daftar Pengajuan Biaya**: satu baris per **Penjualan Program Final** (Public & B2B). Kolom: No, **No. Program** (jenis, nomor, tanggal acara), **Program** (judul paket, lokasi), **Customer** (Company untuk B2B; "Public" untuk Public), **PIC / Trainer**, **Budget**, **Aktual**, **Sisa**, **Status** (Belum ada pengajuan / n Menunggu Approval / Berjalan), **Action** (Lihat Pengajuan). Filter: jenis program, PIC (Semua / Program saya), tahun acara; pencarian nomor, judul, customer, PIC. Penjualan Program Draft / Batal tidak tampil. | `[VID-1]` + `[USULAN]` (pemetaan kolom, kolom angka, status) |
| PB-FR-03 | **Halaman Pengajuan per program**: kepala **No. Program**, Jenis, Customer, Program (judul), Lokasi, **PIC Program**, Trainer, **Waktu** (tanggal mulai – selesai), Durasi, Target peserta. Di bawahnya satu blok per kategori **Fixed Cost** dan **Variable Cost**. | `[VID-2]` + `[USULAN]` (kategori) |
| PB-FR-04 | Setiap blok kategori: kartu **Budget**, **Aktual**, **Sisa Budget** (rumus bagian 1); tombol **+ Add Kasbon** dan **+ Add Direct Payment**; tabel pengajuan: No, **No. Pengajuan**, Deskripsi, Total, **Jenis** (badge Kasbon / Direct Payment), **Request By**, **Status**, Tanggal, Option (gear: Lihat, Batalkan). Sub-blok **Overbudget [kategori]** dengan tombol **+ Add Overbudget** dan tabel: No, No. Overbudget, Item, Amount, Status, Pembuat Pengajuan, Option. Tombol Add hanya tampil bagi PIC Program dan Manajemen `[USULAN]`. | `[VID-2]` + `[USULAN]` |

### 4.2 Form Add Pengajuan (Kasbon / Direct Payment)

Form dibuka sebagai **halaman penuh** (seperti Add Invoicing, `[VID-3]`). Breadcrumb: Pengajuan Biaya / Pengajuan Biaya / Add Kasbon (atau Add Direct Payment) — [kategori].

| ID | Kebutuhan | Sumber |
|---|---|---|
| PB-FR-05 | **Kepala**: data program (PB-FR-03, hanya tampil); **Request By** = user login (tidak dapat diubah); bila user login bukan PIC Program, tampil peringatan "[nama] bukan PIC program ini" `[VID-3]`; **Tanggal** = hari ini (terkunci) `[VID-3]`; **Jenis** (Kasbon / Direct Payment, dari tombol yang ditekan); **Deskripsi / Keterangan** wajib `[VID-3]`. | `[VID-3]` |
| PB-FR-06 | **Informasi Pengajuan** (hanya tampil): satu baris per item costing kategori itu. Kolom **Estimasi** (Qty, Price/Unit, Total Budget), **Terpakai** (Qty, Price/Unit rata-rata, Total Terpakai), **Overbudget** (Qty, Total disetujui), **Sisa**; baris Total. | `[VID-3]` + `[USULAN]` (kolom Sisa) |
| PB-FR-07 | **Tabel Pengajuan**: satu baris per item costing kategori itu: Item, **Qty** (isian), **Price/Unit** (terisi Price/Unit budget, dapat diubah), **Total Pengajuan** = Qty × Price/Unit (dihitung), plus **Grand Total**. Item yang **sisanya 0** tampil abu-abu dan tidak dapat diisi `[VID-3]`. Minimal satu item dengan Total > 0. | `[VID-3]` + `[USULAN]` (Price/Unit dapat diubah) |
| PB-FR-08 | **Batas budget**: Total Pengajuan per item **tidak boleh melebihi Sisa** item. Bila melebihi, Save ditolak dengan pesan untuk mengajukan Overbudget item itu terlebih dahulu (PB-FR-12). Qty boleh desimal (mis. 0,5 hari) `[USULAN]`. | `[USULAN]` (Q-PB-04) |
| PB-FR-09 | **Rekening tujuan** wajib: **Bank**, **No. Rekening**, **Nama Rekening** `[VID-3]`. Kasbon: default rekening pengaju bila pernah diisi sebelumnya; Direct Payment: rekening penerima (vendor) `[USULAN]`. | `[VID-3]` + `[USULAN]` |
| PB-FR-10 | **Bukti** (Upload Bukti, tarik & letakkan, beberapa file) `[VID-3]`. **Wajib untuk Direct Payment** (tagihan / nota), **opsional untuk Kasbon** `[USULAN]`. | `[VID-3]` + `[USULAN]` |
| PB-FR-11 | **Save**: sistem memeriksa isian wajib dan batas budget, memberi **Nomor Pengajuan** otomatis — **`LMI/KS/MM/yy-zzz`** (Kasbon) atau **`LMI/DP/MM/yy-zzz`** (Direct Payment), pola `[FPN]`, urut per kode per bulan `[USULAN]` (Q-PB-08) — dan menyimpan dengan status **Menunggu Approval**. Tidak ada simpan Draft `[USULAN]`. **Back** kembali ke halaman program tanpa menyimpan. | `[VID-3]`, `[FPN]` + `[USULAN]` |

### 4.3 Overbudget

| ID | Kebutuhan | Sumber |
|---|---|---|
| PB-FR-12 | **Add Overbudget** (per kategori, pop up): pilih **item costing** kategori itu (satu atau lebih baris), isi **Qty tambahan** dan **Price/Unit**, **Total** dihitung; **Alasan** wajib. Save memberi nomor **`LMI/OB/MM/yy-zzz`** `[USULAN]` dan status **Menunggu Approval**. Item baru di luar costing **belum** didukung (Q-PB-10). | `[VID-2]` + `[USULAN]` |
| PB-FR-13 | Overbudget **Disetujui** menambah Qty dan Total Budget item (kolom Overbudget di Informasi Pengajuan); costing Penjualan Program Final **tidak berubah** `[USULAN]`. Ditolak / Batal tidak menambah budget. | `[VID-3]` + `[USULAN]` |

### 4.4 Approval & pencairan

| ID | Kebutuhan | Sumber |
|---|---|---|
| PB-FR-14 | **Approval Pengajuan** (Manajemen): daftar pengajuan dan overbudget berstatus **Menunggu Approval** dari semua program, dengan tab **Pengajuan** dan **Overbudget**. Kolom: No, No. Pengajuan, No. Program, Kategori, Jenis, Deskripsi, Total, Request By, Tanggal, Action (Lihat & Proses). Halaman / pop up proses menampilkan rincian item, posisi budget kategori (Budget, Aktual, Sisa), rekening tujuan, dan bukti. | `[PBU]` + `[USULAN]` |
| PB-FR-15 | **Setujui** → status **Disetujui** (tercatat penyetuju & waktu). **Tolak** → status **Ditolak**, **alasan wajib**, budget yang dipesan kembali tersedia. Tidak ada setuju sebagian `[USULAN]`. Pengaju melihat status dan alasan di halaman program. | `[PBU]` + `[USULAN]` |
| PB-FR-16 | **Pencairan** (Keuangan): daftar pengajuan **Disetujui** (tab **Belum Dicairkan** / **Sudah Dicairkan**). Aksi **Cairkan**: **Tanggal cair** wajib (default hari ini), **Bukti transfer** opsional; nominal = Total pengajuan (tidak diubah). Setelah Simpan status **Dicairkan**. | `[PBU]` + `[USULAN]` (Q-PB-02) |

### 4.5 Batal, lihat, dan PIC

| ID | Kebutuhan | Sumber |
|---|---|---|
| PB-FR-17 | Pengaju dapat **Batalkan** pengajuan / overbudget miliknya selama **Menunggu Approval** (konfirmasi). Status **Batal**; budget kembali tersedia. Pengajuan Disetujui / Dicairkan tidak dapat dibatalkan dan tidak dapat diubah `[USULAN]`. Perbaikan isian = batalkan lalu ajukan ulang `[USULAN]`. | `[USULAN]` |
| PB-FR-18 | **Lihat Pengajuan**: kepala program, jenis, kategori, nomor, tanggal, deskripsi, Request By, rincian item (Qty, Price/Unit, Total), rekening tujuan, bukti, dan riwayat status (diajukan, disetujui/ditolak beserta alasan, dicairkan beserta tanggal & bukti transfer). | `[VID-2]` + `[USULAN]` |
| PB-FR-19 | **PIC Program** ditetapkan di Penjualan Program (PJ-FR-26). Program Final tanpa PIC tetap tampil di daftar dengan label "PIC belum ditetapkan"; hanya Manajemen yang dapat mengajukan untuk program itu `[USULAN]`. | `[PBU]` + `[USULAN]` |
| PB-FR-20 | **Hak lihat** `[USULAN]` (Q-PB-07): Manajemen dan Keuangan melihat semua program; user lain hanya program yang ia PIC-i (filter "Program saya" terpasang otomatis). | `[USULAN]` |

---

## 5. Kebutuhan Non-Fungsional (NFR)

| ID | Kategori | Kebutuhan | Kriteria uji | Lingkup | Sumber |
|---|---|---|---|---|---|
| PB-NFR-01 | Integritas data | Budget, Terpakai, dan Sisa dihitung ulang **di server** dari `penjualan_program_biaya`, overbudget disetujui, dan detail pengajuan aktif — bukan dari isian klien. Pemeriksaan batas sisa (PB-FR-08) dan penyimpanan header + detail + lampiran berjalan dalam **satu transaksi** dengan penguncian baris program, agar dua pengajuan bersamaan tidak melebihi sisa. | Dua Save bersamaan yang masing-masing pas dengan sisa → satu ditolak | PB-FR-08, PB-FR-11 | `[USULAN]` |
| PB-NFR-02 | Integritas data | Nomor pengajuan / overbudget dibuat server saat Save, di transaksi yang sama, kolom `nomor` unik (pola IVC-NFR-03). | Tidak ada nomor ganda pada Save bersamaan | PB-FR-11, PB-FR-12 | `[USULAN]` |
| PB-NFR-03 | Integritas data | Transisi status hanya sesuai bagian 6, diperiksa di server (mis. Setujui hanya dari Menunggu Approval; Cairkan hanya dari Disetujui). Pengajuan Disetujui / Dicairkan tidak dapat diubah. | Request Cairkan untuk pengajuan Menunggu Approval ditolak (422) | PB-FR-14 – PB-FR-17 | `[USULAN]` |
| PB-NFR-04 | Audit | Ajukan, setujui, tolak, cairkan, batal, dan overbudget dicatat `write_log()` modul **"Pengajuan Biaya"** (sub modul Pengajuan / Overbudget / Approval / Pencairan), rincian item sebagai `detail_changes`. Tabel memakai kolom jejak `created_by`, `created_date`, `updated_by`, `updated_date`; aksi tampil di Activity Log. | Setujui 1 pengajuan → 1 log dengan status lama → baru | Semua UC | `[STD]` |
| PB-NFR-05 | Keamanan | Menu & aksi mengikuti permission: Add hanya PIC Program / Manajemen; Approval hanya Manajemen; Pencairan hanya Keuangan; pengaju tidak dapat menyetujui pengajuannya sendiri. | User tanpa hak → tombol tidak tampil, request ditolak (403) | PB-FR-04, PB-FR-14, PB-FR-16 | `[USULAN]` |
| PB-NFR-06 | File | Bukti & bukti transfer: PDF, JPG, PNG; maks. 5 MB per file; disimpan di luar web root dengan nama acak, diunduh lewat controller yang memeriksa hak akses. | File .exe ditolak; file 6 MB ditolak | PB-FR-10, PB-FR-16 | `[USULAN]` |
| PB-NFR-07 | Akurasi | Uang DECIMAL(15,2), Qty DECIMAL(10,2); tampil rupiah tanpa desimal, isian uang dengan pemisah ribuan titik (`fr-nfr.md` NFR-12). Rumus Qty Estimasi memakai fungsi faktor satuan yang sama dengan simulasi costing (PJ-NFR-05). | Budget item = angka costing di Penjualan Program | PB-FR-06 | `[USULAN]` |
| PB-NFR-08 | Platform | CI3 HMVC, MySQL, Bootstrap + jQuery; nama tabel/kolom = ERD halaman *ERD Pengajuan Biaya*. Tidak memakai tabel kasbon modul konsultan (`kasbon_project`) `[USULAN]` (Q-PB-11). | Code review | Semua | `[STD]` |

---

## 6. Status

**Pengajuan (Kasbon / Direct Payment)**

| Dari | Aksi | Oleh | Ke | Efek |
|---|---|---|---|---|
| — | Save | PIC Program / Manajemen | **Menunggu Approval** | Nomor dibuat; budget item terpakai |
| Menunggu Approval | Setujui | Manajemen | **Disetujui** | Masuk daftar Pencairan |
| Menunggu Approval | Tolak (alasan) | Manajemen | **Ditolak** | Budget kembali tersedia |
| Menunggu Approval | Batalkan | Pengaju | **Batal** | Budget kembali tersedia |
| Disetujui | Cairkan (tanggal, bukti transfer) | Keuangan | **Dicairkan** | — |

**Overbudget**: Menunggu Approval → **Disetujui** (budget item bertambah) / **Ditolak** / **Batal** (oleh pengaju).

Ditolak, Batal, dan Dicairkan adalah status akhir. Seluruh tabel status ini usulan `[USULAN]`; video hanya menunjukkan "Approved" `[VID-2]`.

**Status program di daftar** (PB-FR-02, turunan, tidak disimpan): **Belum ada pengajuan** (tidak ada pengajuan aktif) · **n Menunggu Approval** (ada pengajuan/overbudget menunggu) · **Berjalan** (ada pengajuan, tidak ada yang menunggu).

---

## 7. Data (ringkas — rinci di ERD)

| Tabel | Isi |
|---|---|
| `penjualan_program` | + `id_pic` (FK `users.id_user`, NULL) — PJ-FR-26 |
| `pengajuan_biaya` | nomor, id_penjualan, tipe_biaya (fixed / variable), jenis (kasbon / direct_payment), tanggal, deskripsi, total, bank, no_rekening, nama_rekening, status, request_by, approved_by, approved_date, alasan_tolak, cair_tanggal, cair_by, cair_date, closed_by, closed_date |
| `pengajuan_biaya_detail` | id_pengajuan, id_penjualan_biaya, item (salinan), qty, harga, total |
| `pengajuan_biaya_lampiran` | id_pengajuan, jenis (bukti / transfer), nama_file, file_path |
| `pengajuan_overbudget` | nomor, id_penjualan, tipe_biaya, tanggal, alasan, total, status, request_by, approved_by, approved_date, alasan_tolak, closed_by, closed_date |
| `pengajuan_overbudget_detail` | id_overbudget, id_penjualan_biaya, item (salinan), qty, harga, total |

---

## 8. Pertanyaan Terbuka

| ID | Pertanyaan | Mempengaruhi |
|---|---|---|
| Q-PB-01 | **PIC Program**: satu orang per Penjualan Program, dipilih dari Master User (usulan PJ-FR-26)? Siapa yang menetapkan, dan apakah wajib sebelum Finalkan? Apakah Trainer / Sales juga boleh mengajukan (di acuan: "tim SPK")? | PJ-FR-26, PB-FR-05, PB-FR-19 |
| Q-PB-02 | Siapa yang **mencairkan** — Manajemen sendiri atau Keuangan (usulan)? Perlu dicatat rekening sumber / metode bayar? | PB-FR-16, UC-46 |
| Q-PB-03 | **Kategori biaya**: cukup Fixed Cost / Variable Cost (usulan), atau perlu kategori seperti acuan (Akomodasi, Konsumsi, Others…) — mis. dari COA Master Rate? Perlu **approval bertingkat** atau batas nominal? | PB-FR-03, PB-FR-14 |
| Q-PB-04 | **Qty budget Variable Cost** memakai **target peserta** Penjualan Program Final (usulan) atau **peserta Deal** Quotation (aktual)? Bolehkah Price/Unit pengajuan berbeda dari budget selama total item ≤ sisa (usulan)? | Bagian 1, PB-FR-07, PB-FR-08 |
| Q-PB-05 | **Pengajuan PO** di acuan: dibutuhkan Labmania (perlu modul pembelian), atau cukup Kasbon & Direct Payment (usulan)? | PB-FR-04 |
| Q-PB-06 | **Pertanggungjawaban kasbon** (Expense Report): kapan dan bagaimana PIC melaporkan realisasi & mengembalikan sisa kasbon? Apakah "Aktual" seharusnya realisasi, bukan nilai yang diajukan? | Bagian 1, ruang lingkup |
| Q-PB-07 | **Hak lihat** dan **hak mengajukan**: user bukan PIC boleh mengajukan dengan peringatan (seperti acuan) atau diblokir? Siapa yang melihat semua program (usulan: Manajemen & Keuangan)? | PB-FR-05, PB-FR-20, PB-NFR-05 |
| Q-PB-08 | **Format nomor** `LMI/KS/MM/yy-zzz`, `LMI/DP/MM/yy-zzz`, `LMI/OB/MM/yy-zzz` (usulan, pola Penawaran/Invoice) — sesuai? Acuan memakai `KS-2026-00346`. | PB-FR-11, PB-FR-12 |
| Q-PB-09 | Apakah pencairan perlu **jurnal** (Kasbon: D Uang Muka Kasbon / K Kas-Bank; Direct Payment: D Biaya [COA item Master Rate] / K Kas-Bank)? | PB-FR-16 |
| Q-PB-10 | Biaya **di luar costing** (item baru) boleh diajukan lewat Overbudget, atau harus ditambahkan dulu ke costing? Costing Penjualan Program Final terkunci (PJ-FR-15). | PB-FR-12 |
| Q-PB-11 | Aplikasi sudah punya modul kasbon konsultan (`kasbon_project`). Labmania memakai **tabel baru** (usulan, bagian 7) atau tabel itu? | ERD, PB-NFR-08 |
| Q-PB-12 | Apakah pengajuan boleh dibuat untuk program yang **sudah lewat** tanggal selesainya, dan apakah ada batas waktu pengajuan? | PB-FR-02, PB-FR-11 |

---

## 9. Riwayat Perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| draf 1 | 04-11-2026 | Penyusunan awal dari `input/Invoice & Pengajuan/2026-11-04-pengajuan-biaya-as-is/` (permintaan user + video menu Pengajuan sistem acuan). SPK dipetakan ke Penjualan Program Final, budget = costing Penjualan Program (Fixed / Variable Cost), PIC Program baru (PJ-FR-26). Kasbon, Direct Payment, Overbudget, Approval Manajemen, Pencairan Keuangan. PB-FR-01 – PB-FR-20, PB-NFR-01 – PB-NFR-08, UC-43 – UC-48, Q-PB-01 – Q-PB-12. ERD halaman *ERD Pengajuan Biaya*, BPMN *Level 1 — Pengajuan Biaya*, mockup draf 14. |
