# Kebutuhan Fungsional & Non-Fungsional — Modul Pengajuan Biaya Labmania

- **Versi:** draf 4 — 05-11-2026
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
| Hak akses | Menu lewat **Role & Permission** (`fr-nfr.md` FR-54 – FR-58; role = Division + Jabatan); data program hanya untuk **user terkait**; **Superadmin** (developer) dan **Admin** (user Labmania) melihat semua `[JPB-07]`, `[JPB2-34]` |

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
| Manajemen | Termasuk tim semua program (dapat mengajukan); **menyetujui / menolak** pengajuan dan overbudget. Siapa yang termasuk Manajemen ditentukan dari **posisi di Master User** — daftar posisi menyusul (Q-PB-14); sementara (mockup) jabatan **Direktur** | `[JPB-01]`, `[JPB-03]`, `[JPB2-14]` |
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
| Q-PB-14 | **Posisi** apa saja di Master User yang termasuk **Manajemen**? Terjawab sebagian `[JPB2-14]`: ditentukan dari Master User (bukan Department / Division); **daftar posisi menyusul dari user**. Group Akses dihapus `input/Invoice & Pengajuan/2026-11-04-role-permission/`; sementara memakai jabatan **Direktur** — disetujui user sebagai data sementara `input/Invoice & Pengajuan/2026-11-04-jawaban-role-permission/`. | PB-FR-19 |

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
