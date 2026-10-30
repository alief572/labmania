# Kebutuhan Fungsional & Non-Fungsional — Modul Master Data Labmania

- **Versi:** draf 11h — 28-10-2026
- **Sumber:** `input/Product - Labmania.xlsx` (sheet *Master Data*, *Data Mentah Struktur Product*), `input/Master - Buat Program/2026-10-22-klarifikasi-produk/`, `input/Master - Buat Program/2026-10-23-review-mockup-master-data/`, `input/Master - Buat Program/2026-10-23-requirements-program/` (*Product rev (1)* sheet *Requirements Product*; *Program* sheet *Cost Master*), `input/Master - Buat Program/2026-10-23-klarifikasi-program/`, `input/Master - Buat Program/2026-10-23-requirements-3-modul/` (*Master Data - Labmania.xlsx*), `input/Master - Buat Program/2026-10-23-konfirmasi-3-modul/`, `input/Master - Buat Program/2026-10-23-arah-penjualan-program/`, `input/Master - Buat Program/2026-10-23-revisi-rate-harga-simulasi/`
- **Status:** DRAF — sebagian pertanyaan terbuka masih menunggu Labmania (bagian 6)
- **Modul lain:** Master Program → `docs/fr-nfr-master-program.md` (memakai Paket Produk dan Master Rate); Penjualan Program → `docs/fr-nfr-penjualan-program.md` (memakai Master Rate); Master Company dipakai Penjualan Program B2B dan Quotation; Master Peserta dipakai modul Quotation → `docs/fr-nfr-quotation.md`
- **Use case kanonis:** `docs/workbook.xlsx` sheet *UC Non-BPMN* (tabel di bagian 3 adalah salinan baca)
- **ERD:** `docs/diagrams/erd.drawio` halaman *ERD Master Data*
- **BPMN (to-be):** `docs/diagrams/bpmn-to-be.drawio` — satu proses: Product Combine, dari master atribut produk menjadi Paket Produk (UC-05)
- **Mockup:** `ui-mockup/produk/index.html`

Konvensi penanda sumber di setiap butir:

| Penanda | Arti |
|---|---|
| `[REQ-n]` | Sheet *Requirements Product*, bagian 1 (Fix Requirements) baris No n. No 1–6 dari file awal; No 7–8 dari *Product rev (1)*, `input/Master - Buat Program/2026-10-23-requirements-program/` |
| `[WISH-n]` | Sheet *Requirements Product*, bagian 3 (Wishlist) baris No n |
| `[SIPOC]` | Sheet *Requirements Product*, bagian 4 (SIPOC) |
| `[MD:<blok>]` | Sheet *Master Data*, blok mockup yang disebut |
| `[RAW]` | Sheet *Data Mentah Struktur Product* |
| `[CM]` | *Program - Labmania.xlsx* sheet *Cost Master*, `input/Master - Buat Program/2026-10-23-requirements-program/` |
| `[MD-n]` | *Master Data - Labmania.xlsx* sheet *Requirements Master Data* No n, `input/Master - Buat Program/2026-10-23-requirements-3-modul/` |
| `[MD-FORM:<blok>]` | *Master Data - Labmania.xlsx* sheet *Master Data* (mockup form), blok yang disebut |
| `[MD-RATE]` | *Master Data - Labmania.xlsx* sheet *Data Mentah Master Rate* |
| `[KLR-x]` | Klarifikasi 22-10-2026, `input/Master - Buat Program/2026-10-22-klarifikasi-produk/` — jawaban atas pertanyaan x |
| `[RMD-n]` | Review mockup 23-10-2026, `input/Master - Buat Program/2026-10-23-review-mockup-master-data/` — butir verbatim n |
| `[KPG-n]` | Klarifikasi 23-10-2026, `input/Master - Buat Program/2026-10-23-klarifikasi-program/` — butir verbatim n |
| `[K3M-n]` | Konfirmasi 23-10-2026, `input/Master - Buat Program/2026-10-23-konfirmasi-3-modul/` — butir verbatim n |
| `[REV-n]` | Revisi 23-10-2026, `input/Master - Buat Program/2026-10-23-revisi-rate-harga-simulasi/` — butir verbatim n |
| `[ARH-n]` | Arah modul 23-10-2026, `input/Master - Buat Program/2026-10-23-arah-penjualan-program/` — butir verbatim n |
| `[JLR-n]` | Jalur Public vs B2B 25-10-2026, `input/Master - Buat Program/2026-10-25-jalur-public-b2b/` — pesan n |
| `[JPP-n]` | jawaban pertanyaan Program 28-10-2026, `input/Master - Buat Program/2026-10-28-jawaban-pertanyaan-program/` — butir n |
| `[USR-1]` | format form Master User, `input/Master - Buat Program/2026-10-28-master-user/` (`input/Master User.png`) |
| `[GMP-1]` | gabung menu Master Program & Penjualan Program, `input/Master - Buat Program/2026-10-28-gabung-menu-penjualan-program/` |
| `[SBL-1]` | sidebar tanpa label sub modul, `input/Master - Buat Program/2026-10-28-sidebar-tanpa-label/` |
| `[DSH-1]` | permintaan dashboard halaman depan, `input/Master - Buat Program/2026-10-28-dashboard-depan/` — isi usulan analis |
| `[DSH-2]` | dashboard dirampingkan, `input/Master - Buat Program/2026-10-28-dashboard-ringkas/` |
| `[DSH-3]` | jadwal hanya Final, KPI total, `input/Master - Buat Program/2026-10-28-dashboard-jadwal-final/` |
| `[DSH-4]` | rincian kartu KPI, `input/Master - Buat Program/2026-10-28-dashboard-rincian-kartu/` |
| `[STD]` | Standar proyek di `AGENTS.md` root repository |
| `[USULAN]` | Usulan analis, **belum** dinyatakan klien — wajib dikonfirmasi sebelum dikodekan |

---

## 1. Ringkasan Kebutuhan

Labmania menjual produk pelatihan/sertifikasi. Aplikasi dibagi menjadi **tiga modul** yang berurutan
`[KPG-7]`, `[MD-1]`. Di sidebar, Master Program dan Penjualan Program digabung dalam satu menu modul
**Penjualan Program** (sub modul Master Program, lalu Penjualan Program) `[GMP-1]`:

| Modul | Isi | Dokumen |
|---|---|---|
| **Master Data** | Semua data master: atribut produk, Paket Produk, Peserta, Company, item biaya (Master Rate) | dokumen ini |
| **Master Program** | Program siap jual: Paket Produk + costing baseline (tanpa Public/B2B, tanpa customer) | `docs/fr-nfr-master-program.md` |
| **Penjualan Program** | Tarik Master Program, pilih Public/B2B (form sama), sesuaikan costing & harga; Final → Quotation (Public, banyak) / Sales Order (B2B, satu Company), keduanya ditunda | `docs/fr-nfr-penjualan-program.md` |

Satu **paket produk** tidak diinput langsung, melainkan dibentuk dengan **meng-combine empat
atribut master** yang berdiri sendiri. Dalam satu kali combine pengguna dapat menyusun beberapa
kombinasi sekaligus, dan saat disimpan **setiap baris kombinasi menjadi satu paket produk**.
Paket produk inilah yang dipilih saat membuat Master Program. Semua atribut hanya **dipilih** dari Master Data, tidak diinput ulang di Master Program maupun Penjualan Program `[MD-1]`.

```
Jenis Produk ───────┐
Jenis Sertifikasi ──┤                                    ┌─► Paket Produk ──► Master Program ──► Penjualan Program ──► Quotation / Sales Order
Jenis Bidang ───────┼──► Product Combine (judul manual) ─┤                          ▲                ▲   ▲   ▲
Varian Pelaksanaan ─┘     n baris → n paket produk        └─► Katalog Produk         │                │   │   │
                                                                                     │                │   │   │
                         Master Rate (item biaya + Default Cost) ────────────────────┴────────────────┘   │   │
                         Master Peserta (customer Public) ────────────────────────────────────────────────┘   │
                         Master Company (customer B2B) ───────────────────────────────────────────────────────┘
```

SIPOC `[MD-SIPOC]` (menggantikan `[SIPOC]` + `[KLR-SIPOC]`):

| Supplier | Input | Process | Output | Customer |
|---|---|---|---|---|
| Manajemen | Jenis produk, jenis sertifikasi, jenis bidang, jenis varian, judul/topik produk, item & default cost (Master Rate), data peserta, data company | Master Data | Product Combine (Paket Produk), Katalog Produk, Master Rate, Master Peserta & Master Company | Master Program, Quotation, Promosi |

Menu modul Master Data dikelompokkan per sub modul `[RMD-1]`:

| Modul | Sub Modul | Menu | Dipakai untuk |
|---|---|---|---|
| Master Data | Atribut Produk | Jenis Produk, Jenis Sertifikasi, Jenis Bidang, Varian Pelaksanaan | Master Program (lewat Product Combine) |
| Master Data | Produk | Product Combine, Katalog Produk | Master Program |
| Master Data | Customer | Master Peserta, Master Company | Peserta: Quotation · Company: Penjualan Program B2B, Quotation ke Company |
| Master Data | Biaya | Master Rate | Master Program, Penjualan Program |
| Master Data | Pengguna | Master User | Login & hak akses seluruh modul (Q-25) |

Nama sub modul masih usulan analis (Q-18). Draf 3 memakai satu sub modul *Peserta & Rate*;
sejak Master Company masuk, sub modul itu dipecah menjadi *Customer* dan *Biaya*. Kolom
"Dipakai untuk" mengikuti kolom *Dipakai Untuk* di `[MD-2]` – `[MD-10]`.

**Pain point:** bagian 2 sheet *Requirements Product* masih kosong dan masih digali Labmania `[KLR-Q-13]`.

### 1.1 Cakupan

| Termasuk | Tidak termasuk (dokumen ini) |
|---|---|
| Master Jenis Produk, Jenis Sertifikasi, Jenis Bidang, Varian Pelaksanaan | Master Program (costing baseline) → `fr-nfr-master-program.md` |
| Product Combine → Paket Produk (status aktif/nonaktif) | Penjualan Program → `fr-nfr-penjualan-program.md`; Quotation & Sales Order (ditunda) |
| Katalog Produk (ditunda sampai format tersedia) | Modul Promosi |
| Master Peserta, Master Company | |
| Master Rate — item Fixed Cost & Variable Cost dengan Default Cost | |
| Data awal dari sheet *Data Mentah* dan *Cost Master* | |
| Navigasi modul (menu, search menu, kolom Action) | |

### 1.2 Istilah

| Istilah | Arti di dokumen ini |
|---|---|
| Product Combine | Layar/proses menyusun kombinasi atribut. Bukan data tersimpan. |
| Paket Produk | Satu kombinasi Jenis Produk + Jenis Sertifikasi + Jenis Bidang + Varian Pelaksanaan beserta Judul, hasil simpan dari Product Combine. Diberi **Nomor Product Combine**. |
| Master Rate | Daftar item biaya. Di Excel Program disebut *Cost Master* / *Master Cost*; isinya sama. |
| Default Cost | Harga standar satu item biaya. Satu item hanya punya **satu** Default Cost `[KPG-4]`, yang dapat disesuaikan di Master Program dan di Penjualan Program tanpa mengubah Master Rate `[MD-8]`. |
| Public / B2B | Jenis **penjualan**, dipilih di Penjualan Program (bukan di Master Program) `[QT-2]`, `[ARH-4]`; form Penjualan Program sama untuk keduanya `[JLR-1]`. Peserta = customer Public (di Quotation), Company = customer B2B (di Sales Order) `[JLR-2]`. |

---

## 2. Aktor

| Aktor | Peran | Sumber |
|---|---|---|
| Manajemen | Menyediakan & memelihara master atribut produk dan menyusun Product Combine | `[SIPOC]` Supplier |
| Master Program, Penjualan Program, Quotation | Konsumen Paket Produk & Master Rate (Master Program), Master Rate (Penjualan Program), Peserta (Quotation, ditunda), Company (Sales Order, ditunda) | `[MD-SIPOC]`, `[ARH-4]` |
| Promosi | Konsumen Katalog Produk | `[SIPOC]` Customer |
| *Belum ditetapkan* | Pengelola Master Peserta, Master Company, Master Rate, dan pembuat Katalog — masih ditanyakan | `[KLR-Q-06]` |

---

## 3. Tabel Use Case

Derivasi Route 2 (FR → UC) sesuai `context/document-writer-only/workbook-conventions.md`.
Satu UC master mencakup alur tambah, ubah, dan aktif/nonaktif karena dilakukan aktor yang sama,
di satu layar, tanpa serah-terima ke aktor lain.

| Kode | Nama Use Case | User | Modul | Sub Modul | Input | Deskripsi | Output | Entity/Objek Terkait | FR |
|---|---|---|---|---|---|---|---|---|---|
| UC-01 | Mencatat Jenis Produk | Manajemen | Master Data | Atribut Produk | Nama Jenis Produk | Aktor membuka daftar Jenis Produk, menambah data baru (kode dibuat sistem), mengubah, atau mengaktifkan/menonaktifkan data. Data tidak dapat dihapus. | Data Jenis Produk tersimpan dengan kode otomatis dan status | Jenis Produk | FR-03, FR-04, FR-08, FR-09, FR-21 |
| UC-02 | Mencatat Jenis Sertifikasi | Manajemen | Master Data | Atribut Produk | Nama Jenis Sertifikasi | Sama dengan UC-01 untuk master Jenis Sertifikasi. | Data Jenis Sertifikasi tersimpan dengan kode otomatis dan status | Jenis Sertifikasi | FR-03, FR-05, FR-08, FR-09, FR-21 |
| UC-03 | Mencatat Jenis Bidang | Manajemen | Master Data | Atribut Produk | Nama Jenis Bidang | Sama dengan UC-01 untuk master Jenis Bidang. Jenis Bidang juga menjadi pilihan Bidang pada Master Peserta. | Data Jenis Bidang tersimpan dengan kode otomatis dan status | Jenis Bidang | FR-03, FR-06, FR-08, FR-09, FR-21 |
| UC-04 | Mencatat Varian Pelaksanaan | Manajemen | Master Data | Atribut Produk | Nama Varian | Sama dengan UC-01 untuk master Varian Pelaksanaan. | Data Varian Pelaksanaan tersimpan dengan kode otomatis dan status | Varian Pelaksanaan | FR-03, FR-07, FR-08, FR-09, FR-21 |
| UC-05 | Menyusun Product Combine | Manajemen | Master Data | Produk | Judul; satu atau lebih baris kombinasi Produk + Bidang + Varian + Sertifikasi | Aktor mengisi Judul secara manual, menambah baris kombinasi, memilih keempat atribut aktif per baris, menghapus baris yang tidak perlu, lalu menyimpan. Sistem menolak kombinasi yang keempat atributnya sama persis dengan paket lain atau baris lain. Setiap baris disimpan sebagai satu Paket Produk dengan Nomor Product Combine sendiri. | Satu Paket Produk per baris, berstatus aktif, tampil di daftar | Paket Produk, Jenis Produk, Jenis Sertifikasi, Jenis Bidang, Varian Pelaksanaan | FR-01, FR-02, FR-10 – FR-13, FR-22 – FR-24 |
| UC-06 | Menghasilkan Katalog Produk | *TBD (Q-06)* | Master Data | Produk | Paket Produk aktif | **Ditunda** — format katalog belum ada. Sistem menyusun katalog dari Paket Produk. | Katalog Produk | Paket Produk, Katalog Produk | FR-14 |
| UC-07 | Mencatat Peserta | *TBD (Q-06)* | Master Data | Customer | Nama, Gender, Instansi, Bidang, Provinsi, Kota/Kabupaten, No Telp/WA, Email (semua wajib) | Aktor menambah peserta lewat form (Kota/Kabupaten tersaring menurut Provinsi), mengubah, atau menghapus peserta dari daftar. Peserta menjadi customer penjualan Public dan dipilih di modul Quotation (ditunda). | Data Peserta tersimpan dengan Kode Peserta | Peserta, Jenis Bidang, Provinsi, Kota/Kabupaten | FR-15 – FR-17 |
| UC-08 | Mencatat Item Variable Cost | *TBD (Q-06)* | Master Data | Biaya | Item, Kategori (Variable), Satuan (Peserta/Event atau Peserta/Day), Default Cost, Keterangan, COA | Aktor membuka tab Variable Cost pada Master Rate, menambah item (kode dibuat sistem), mengubah, atau mengaktifkan/menonaktifkan item. Item tidak dapat dihapus. Pilihan Satuan hanya Peserta/Event dan Peserta/Day. | Item Variable Cost tersimpan dengan kode otomatis, Default Cost, dan status | Rate Variable Cost, Satuan, COA | FR-18, FR-19, FR-25, FR-33 – FR-35 |
| UC-09 | Mencatat Item Fixed Cost | *TBD (Q-06)* | Master Data | Biaya | Item, Kategori (Fixed), Satuan (Event atau Day), Default Cost, Keterangan, COA | Sama dengan UC-08 pada tab Fixed Cost. Pilihan Satuan hanya Event dan Day. | Item Fixed Cost tersimpan dengan kode otomatis, Default Cost, dan status | Rate Fixed Cost, Satuan, COA | FR-18, FR-20, FR-25, FR-33 – FR-35 |
| UC-10 | Mengaktifkan / Menonaktifkan Paket Produk | Manajemen | Master Data | Produk | Paket Produk terpilih | Aktor memilih paket pada daftar lalu mengubah statusnya menjadi aktif atau nonaktif. | Status Paket Produk berubah | Paket Produk | FR-10, FR-23 |
| UC-11 | Mencatat Company | *TBD (Q-06)* | Master Data | Customer | Nama, Jenis Instansi, Alamat, Provinsi, Kota, Telepon & Email kantor, PIC (Nama, Jabatan, Telp/WA, Email) — semua wajib; NPWP opsional | Aktor menambah company lewat form, mengubah, atau menghapus company dari daftar. Company menjadi customer penjualan B2B dan dipilih saat membuat Penjualan Program B2B. | Data Company tersimpan dengan Kode Company | Company, Provinsi, Kota/Kabupaten | FR-30 – FR-32 |
| UC-32 | Mencatat User | Manajemen | Master Data | Pengguna | Employee ID, Employee Name, Department, Division, Jabatan, Gender, Phone Number, Employee Type, Status (wajib); NIK, Birth Place/Date, Religion, Email, Address, NPWP, Join Date, Job Desc. (opsional); akun login: Username, Password, Group Akses | Aktor menambah user lewat form, mengubah, mengaktifkan/menonaktifkan, dan me-reset password. User yang diberi akun login dapat masuk ke sistem sesuai Group Akses; user nonaktif tidak dapat login. | Data user tersimpan; akun login aktif bila diberi | User (employees), Akun (users), Group Akses, Department, Division, Jabatan | FR-36 – FR-39 |

> UC-06 s.d. UC-09 dan UC-11 belum lolos uji admission no. 1 (aktor bernama) — aktor masih ditanyakan `[KLR-Q-06]`.
> Kode UC tidak pernah dipakai ulang. UC-12 – UC-18 (Program Public/B2B, draf 4) sudah dihapus dan tidak dipakai lagi; UC-19 – UC-21 = Master Program, UC-22 – UC-28 = Penjualan Program.
> Navigasi menu, search menu, dan kolom Action (FR-27 – FR-29) tidak menjadi use case sendiri: tidak ada tujuan aktor yang berdiri sendiri, melainkan cara mencapai UC-01 – UC-11.

---

## 4. Kebutuhan Fungsional (FR)

### 4.1 Struktur Produk

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-01 | Paket produk dibentuk dengan meng-combine empat master: **Jenis Produk**, **Jenis Sertifikasi**, **Jenis Bidang**, dan **Varian Pelaksanaan**. Paket produk tidak dapat dibuat tanpa memilih keempat atribut dari master tersebut. | `[REQ-1]` |
| FR-02 | Keempat atribut **berdiri sendiri (tidak bertingkat)** — tidak ada relasi induk-anak di antara master tersebut. Pilihan pada satu atribut tidak membatasi pilihan atribut lain. | `[REQ-2]` |
| FR-03 | Setiap master memiliki **kode yang dibuat otomatis oleh sistem** dengan format penomoran standar dari Labmania. Format per master menunggu Q-01. | `[WISH-1]`, `[MD:*]`, `[KLR-Q-10]` |

### 4.2 Master Atribut Produk

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-04 | **Master Jenis Produk.** Daftar menampilkan: No, Kode Jenis Product, Jenis Product, Status, Action. Form tambah: Kode Jenis Product (otomatis), Jenis Product (teks bebas). Data awal: Pelatihan, Sertifikasi. | `[MD:Jenis Product]`, `[RAW]`, `[KLR-Q-02]` |
| FR-05 | **Master Jenis Sertifikasi.** Daftar: No, Kode Sertifikasi, Jenis Sertifikasi, Status, Action. Form tambah: Kode Sertifikasi (otomatis), Jenis Sertifikasi (teks bebas). Nilainya adalah jenis sertifikat (mis. Sertifikat BNSP, Sertifikat KAN ISO 29993) sesuai *Data Mentah* — **bukan** "Pelatihan / Sertifikasi" seperti tertulis di REQ-3. | `[MD:Jenis Sertifikasi]`, `[RAW]`, `[KLR-Q-02]` |
| FR-06 | **Master Jenis Bidang** — bidang keilmuan/standar yang dibahas di pelatihan (mis. ISO 17025, Analisis Kimia, Kalibrasi). Daftar: No, Kode Bidang, Jenis Bidang, Status, Action. Form tambah: Kode Bidang (otomatis), Jenis Bidang (teks bebas). Jenis Bidang juga dipakai untuk **mengelompokkan peserta** (FR-16). | `[REQ-4]`, `[MD:Jenis Bidang]`, `[KLR-Q-10]` |
| FR-07 | **Master Varian Pelaksanaan** — cara melaksanakan suatu jenis bidang. Daftar: No, Kode Varian, Varian, Status, Action. Form tambah: Kode Varian (otomatis), Jenis Varian (teks bebas). | `[REQ-5]`, `[MD:Varian Pelaksanaan]`, `[KLR-Q-10]` |
| FR-08 | Kolom Action pada daftar master FR-04 s.d. FR-07 berisi opsi **Edit** dan **Aktifkan / Nonaktifkan** (lewat menu gear, FR-28). Data master **tidak dapat dihapus**; tombol Delete pada mockup diganti Nonaktifkan. | `[MD:*]`, `[KLR-Q-05]`, `[RMD-4]` |
| FR-09 | Field nama pada setiap master (FR-04 s.d. FR-07) wajib diisi sebelum disimpan. | `[USULAN]` |
| FR-21 | Master berstatus **nonaktif** tidak muncul sebagai pilihan pada form Product Combine dan form Peserta. Paket Produk dan Peserta yang sudah memakai master tersebut tetap menampilkan nilainya. | `[KLR-Q-05]` + `[USULAN]` (perilaku nonaktif) |

### 4.3 Product Combine & Paket Produk

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-10 | **Daftar Paket Produk** menampilkan satu baris per paket dengan kolom: No, Nomor (Nomor Product Combine), Judul Topik, **Produk, Bidang, Varian, Sertifikasi** (satu kolom per atribut, urutan sama dengan tabel kombinasi FR-12), Status, Action (menu gear: Aktifkan / Nonaktifkan). Atribut yang masternya sudah nonaktif tetap ditampilkan dengan penanda "(nonaktif)". | `[MD:Product (Combine)]`, `[KLR-Q-03]`, `[KLR-Q-04]`, `[RMD-2]` + `[USULAN]` (pemecahan kolom) |
| FR-11 | **Form Tambah Product** berisi field **Judul** (teks bebas, diinput manual oleh pengguna). | `[REQ-6]`, `[MD:Product (Combine)]` |
| FR-12 | Form Tambah Product memiliki **tabel kombinasi** berkolom: #, Produk, Bidang, Varian, Sertifikasi, Action. Setiap baris memilih (select) satu nilai aktif dari master Jenis Produk, Jenis Bidang, Varian Pelaksanaan, dan Jenis Sertifikasi. Pengguna dapat menambah baris dan **menghapus** baris sebelum menyimpan. | `[MD:Product (Combine)]`, `[KLR-Q-03]` |
| FR-13 | Saat disimpan, **setiap baris kombinasi menjadi satu Paket Produk** yang membawa Judul dari form, dan masing-masing mendapat **Nomor Product Combine** otomatis (format Q-01). Satu kali simpan dengan n baris menghasilkan n paket produk. | `[KLR-Q-03]`, `[WISH-1]` |
| FR-22 | **Keunikan kombinasi:** antar paket produk minimal **satu** dari empat atribut harus berbeda. Sistem menolak penyimpanan bila ada baris yang kombinasinya sama persis dengan paket yang sudah ada atau dengan baris lain di form yang sama, dan menunjukkan baris yang bentrok. Seluruh form gagal disimpan; tidak ada paket yang tersimpan sebagian. | `[KLR-Q-12]` + `[USULAN]` (perilaku gagal-semua) |
| FR-23 | Paket Produk memiliki status **Aktif / Nonaktif** yang dapat diubah dari daftar. Paket baru tersimpan berstatus Aktif. | `[KLR-Q-04]` + `[USULAN]` (default Aktif) |
| FR-24 | Paket Produk berstatus Aktif adalah output yang **siap dibuat menjadi Product Costing**, yaitu dapat dipilih saat membuat **Master Program**. | `[KLR-SIPOC]`, `[MD-7]` + `[USULAN]` (hanya paket aktif) |

### 4.4 Katalog Produk *(Wishlist — ditunda)*

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-14 | Dari Paket Produk sistem menghasilkan **Katalog Produk** untuk Promosi. Format katalog belum ada; FR ini tidak dikerjakan sampai Labmania menyiapkan format. | `[WISH-2]`, `[SIPOC]`, `[KLR-Q-09]` |

### 4.5 Master Peserta

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-15 | **Daftar Peserta** menampilkan: No, Kode Peserta, Nama Peserta, Bidang, Lokasi, Action (menu gear: Edit / Delete). Data diambil dari pengisian form Master Peserta. Peserta dipilih di modul **Quotation** (`docs/fr-nfr-quotation.md`); peserta yang sudah ada di quotation **tidak dapat dihapus** `input/Master - Buat Program/2026-10-28-quotation-company-personal/` + `[USULAN]`. | `[MD:Master Peserta]`, `[RMD-4]`, `[MD-9]`, `[MD-FORM:Master Peserta]` |
| FR-16 | **Form Tambah Peserta** — **semua field wajib** (*): Nama Peserta (teks bebas), Gender (pilihan Laki-laki / Perempuan), Instansi/Perusahaan (teks bebas), Bidang (pilihan dari master **Jenis Bidang** aktif), Lokasi Provinsi (pilihan), Kota/Kabupaten (pilihan, **tersaring menurut Provinsi** yang dipilih), No Telp/WhatsApp (teks), Email (teks). REQ-7 hanya mewajibkan Nama, Instansi, Bidang, dan Provinsi; Labmania memutuskan semua field wajib. Tambahan: **Company** (opsional, pilihan Master Company aktif, "Tanpa company (personal)"); bila diisi, Instansi/Perusahaan = nama company (terkunci). Dipakai Quotation ke Company: hanya peserta company itu yang dapat dipilih `input/Master - Buat Program/2026-10-28-quotation-company-personal/` + `[USULAN]` (field Company). | `[MD:Master Peserta]`, `[REQ-4]`, `[REQ-7]`, `[KLR-Q-07]`, `[KPG-2]` |
| FR-17 | Peserta memiliki **Kode Peserta** yang dibuat otomatis oleh sistem (format Q-01). | `[WISH-1]`, `[MD:Master Peserta]` |

### 4.6 Master Company

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-30 | **Master Company** menyimpan data perusahaan/instansi yang menjadi **customer penjualan B2B**. Company **aktif** dipilih (wajib) di **Penjualan Program B2B** (`fr-nfr-penjualan-program.md` PJ-FR-22) dan diteruskan ke Sales Order, satu SO untuk satu Company. Company baru dibuat di sini, tidak dari form Penjualan Program `[JPP-2]`. | `[REQ-8]`, `[MD-10]`, `[ARH-4]`, `[JLR-2]`, `[JPP-2]`, `[JPP-3]` |
| FR-31 | **Form Tambah Company** dibagi lima bagian: **Identitas** — Nama Company (wajib, teks bebas), Jenis Instansi (pilihan: Swasta, BUMN/BUMD, Pemerintah, Pendidikan, Lainnya); **Alamat** — Alamat lengkap (teks), Provinsi (pilihan), Kota/Kabupaten (pilihan, tersaring menurut Provinsi); **Kontak kantor** — Telepon, Email; **PIC** — Nama, Jabatan, Telp/WA, Email; **Pajak** — NPWP. **Semua field wajib kecuali NPWP.** | `[REQ-8]`, `[MD-10]`, `[MD-FORM:Master Company]` + `[USULAN]` (Kota tersaring Provinsi, mengikuti FR-16) |
| FR-32 | **Daftar Company** menampilkan: No, Kode Company, Nama Company, Jenis Instansi, Lokasi, Status, Action (menu gear: Edit / Aktifkan–Nonaktifkan / Delete). Company memiliki **Kode Company** otomatis (format Q-01). Company **nonaktif** tidak muncul di pilihan Penjualan Program `[JPP-3]` (memakai status aktif tabel `customer`); Delete masih mengikuti Q-16. | `[MD-FORM:Master Company]`, `[JPP-3]` |

### 4.7 Master Rate

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-18 | Halaman **Master Rate** hanya terdiri dari dua tab: **Fixed Cost** dan **Variable Cost**. | `[MD:Master Rate]`, `[KLR-Q-08]` |
| FR-19 | **Variable Cost.** Daftar: No, Kode Variable Cost, Item, Satuan, Default Cost, Status, Action (menu gear: Edit / Aktifkan-Nonaktifkan). Form tambah: Kode Rate (otomatis, standar Labmania), Item (teks bebas), **Kategori** (Fixed / Variable, terisi sesuai tab), Satuan (pilihan, **hanya Peserta/Event atau Peserta/Day**), Default Cost (Rp, wajib), Keterangan (teks, opsional), COA (pilihan dari master COA), Aktif. **Tidak ada mode harga** (Default / Fix / Input manual): semua item memakai Default Cost sebagai nilai awal yang **selalu dapat diedit** di Master Program dan Penjualan Program tanpa mengubah Master Rate. | `[MD:Master Rate]`, `[CM]`, `[MD-8]`, `[MD-FORM:Master Rate]`, `[RMD-3]`, `[RMD-4]`, `[KPG-3]`, `[REV-1]` |
| FR-20 | **Fixed Cost.** Daftar dan form sama dengan Variable Cost (FR-19), dengan Kode Fixed Cost dan Satuan **hanya Event atau Day**. | `[CM]` (kolom sama untuk semua kategori), `[RMD-3]`, `[KPG-1]` |
| FR-25 | Dasar perhitungan rate dicatat sebagai **Satuan** item, bukan sebagai tab atau field terpisah. Satuan dibatasi per tab: **Fixed Cost** = Event, Day; **Variable Cost** = Peserta/Event, Peserta/Day. Biaya yang di sumber bersatuan **Jam/Hours dicatat sebagai Event** (dihitung sekali per program): Hours hanya dipakai untuk biaya trainer, dan satu event selalu satu trainer `[K3M-1]`. **Mandays bukan satuan item**, melainkan hasil costing Penjualan Program B2B; rumusnya ditunda (`fr-nfr-penjualan-program.md`, Q-PJ-04). Form hanya menampilkan satuan milik tab yang sedang dibuka, dan server menolak satuan dari tab lain. *`[MD-8]` dan `[MP-13]` yang masih mencantumkan Hours (per jam) digantikan `[K3M-1]`.* | `[KLR-Q-08]`, `[RMD-3]`, `[KPG-1]`, `[K3M-1]` |
| ~~FR-33~~ | *Dihapus draf 8* — mode harga per item (Default / Fix / Input manual) tidak dipakai `[REV-1]`. | `[REV-1]` |
| FR-34 | Setiap item hanya punya **satu Default Cost** yang dipakai Program Public maupun B2B. Tidak ada harga terpisah per jenis program. | `[KPG-4]` |
| FR-35 | Item rate **tidak dapat dihapus**, hanya dinonaktifkan (field **Aktif** Ya/Tidak `[MD-8]`). Item nonaktif tidak muncul di costing program baru; program yang sudah memakai item tersebut tetap menampilkan nilainya. | `[CM]` (kolom Aktif), `[KPG-3]` + `[USULAN]` (perilaku pada program lama) |

### 4.8 Data Awal

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-26 | Data pada sheet *Data Mentah Struktur Product* dan *Data Mentah Master Rate* (`[MD-RATE]`, isi sama dengan *Cost Master*) dimuat sebagai **data awal** master Jenis Produk, Jenis Sertifikasi, Jenis Bidang, Varian Pelaksanaan, Fixed Cost, dan Variable Cost (lampiran bagian 7), termasuk Default Cost. Item ganda dimuat satu kali; baris judul kelompok (mis. "Fix Cost / Event") dan baris Total tidak dimuat. | `[RAW]`, `[CM]`, `[KLR-Q-11]`, `[KPG-6]` |

### 4.9 Navigasi & Tampilan

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-27 | Sidebar menampilkan menu **Dashboard** paling atas (halaman pertama setelah login, FR-40), lalu dua modul, **Master Data** dan **Penjualan Program**, masing-masing dapat dibuka/ditutup. Modul Penjualan Program berisi sub modul **Master Program** (menu Daftar Master Program) lalu **Penjualan Program** (menu Daftar Penjualan Program) `[GMP-1]`. Di dalam modul, menu diurutkan per sub modul (tabel di bagian 1), tetapi **label sub modul tidak ditampilkan** — menu langsung di bawah nama modul `[SBL-1]`. Breadcrumb menampilkan Modul / Menu. | `[RMD-1]`, `[KPG-7]`, `[GMP-1]`, `[SBL-1]` + `[USULAN]` (nama sub modul, Q-18) |
| FR-28 | Kolom **Action** pada setiap tabel daftar hanya menampilkan **ikon gear**. Opsi aksi (Edit, Aktifkan/Nonaktifkan, Delete — sesuai FR masing-masing daftar) baru muncul sebagai menu setelah gear diklik. Menu tertutup bila pengguna mengklik di luar menu, menekan Esc, atau menggulir halaman. | `[RMD-4]` + `[USULAN]` (perilaku tutup menu) |
| FR-29 | Sidebar memiliki kolom **search menu** tepat di bawah logo Labmania. Pencarian menyaring menu dari **semua modul** berdasarkan nama menu saat pengguna mengetik `[SBL-1]`; modul tanpa hasil disembunyikan dan bila tidak ada yang cocok ditampilkan "Menu tidak ditemukan". Enter membuka hasil pertama, Esc mengosongkan pencarian. | `[RMD-5]` + `[USULAN]` (Enter/Esc, pencocokan sub modul) |


### 4.10 Master User

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-36 | **Master User** (sub modul **Pengguna**, modul Master Data) menyimpan data karyawan Labmania beserta **akun login** ke sistem. Memakai tabel existing aplikasi: data karyawan di `employees`, akun di `users` dengan group akses `user_groups` / `groups` (Q-25). | `[USR-1]` + `[USULAN]` (sub modul, tabel) |
| FR-37 | **Form Tambah/Edit User** mengikuti format acuan, dua kolom. Kiri: **Employee ID** (wajib, unik, tidak dapat diubah setelah disimpan), **Employee Name** (wajib), Personal ID Number / NIK (opsional, 16 digit), Birth Place, Birth Date, **Department** (wajib, pilihan), **Division** (wajib, pilihan tersaring menurut Department — "Pilih Department terlebih dahulu."), **Jabatan** (wajib, pilihan), **Gender** (wajib, pilihan), Religion (pilihan). Kanan: **Phone Number** (wajib, format `+62…`, 9–15 digit), Email (format email), Address, NPWP (15/16 digit), Join Date, **Employee Type** (wajib, pilihan), Job Desc., **Status** (wajib, radio Active / Non Active, default Active). Tombol Save / Close. | `[USR-1]` + `[USULAN]` (validasi format, Employee ID terkunci) |
| FR-38 | Form memuat bagian tambahan **Akun Login**: centang "dapat login ke sistem"; bila dicentang, **Username** (wajib, unik, huruf kecil/angka/titik/garis bawah, 3–50 karakter), **Group Akses** (wajib, pilihan), **Password** + **Konfirmasi Password** (wajib saat akun dibuat, minimal 8 karakter, harus sama; saat edit dikosongkan bila tidak diganti). Password disimpan ter-hash, tidak pernah ditampilkan. User tanpa akun (mis. trainer lepas) tetap dapat dicatat. | `[USULAN]` (tambahan di luar form acuan, `[USR-1]` "kalo ada yang kurang ditambahin") |
| FR-39 | **Daftar User**: No, Employee ID, Nama (+ email/telepon), Department / Division, Jabatan (+ Employee Type), Akun login (username + group, atau "Tanpa akun"), Status, Action (menu gear: Edit, Aktifkan/Nonaktifkan, Reset Password bila punya akun). Filter Department dan Status; pencarian ID, nama, username, email. User **tidak dihapus**, hanya dinonaktifkan; user nonaktif **tidak dapat login**. Setiap tambah/ubah/aktif-nonaktif/reset password dicatat dengan `write_log()` (tanpa nilai password). | `[USR-1]`, `[STD]` + `[USULAN]` (kolom, tanpa hapus, reset password) |


### 4.11 Dashboard

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-40 | **Dashboard** adalah halaman depan aplikasi (dibuka pertama setelah login, menu paling atas di sidebar). Tanpa tombol cepat buat Master Program / Penjualan Program `[DSH-2]`. | `[DSH-1]`, `[DSH-2]` |
| FR-41 | Dashboard menampilkan **KPI**, semuanya **total** seluruh data tanpa filter periode `[DSH-3]`: (1) jumlah Penjualan Program **Final** (rincian Public / B2B); (2) **pendapatan rencana** = Σ harga jual / peserta × target peserta (uang masuk) dan jumlah peserta direncanakan; (3) **profit rencana** = Σ pendapatan − HPP (fixed + variable cost), + margin %; (4) jumlah Master Program + Penjualan Program yang masih **Draft**. Pendapatan & profit = angka rencana Penjualan Program Final (rumus `fr-nfr-master-program.md` bagian 8); data aktual menyusul modul Quotation / Sales Order. Setiap kartu **dapat diklik** dan membuka popup **rincian** `[DSH-4]`: sumber data (menu dan status yang dihitung), rumus, daftar data penyusun angka (Final: penjualan & target peserta; Pendapatan: harga jual × target peserta; Profit: pendapatan, Fixed Cost, Variable Cost, profit, margin; Draft: Master Program & Penjualan Program Draft), dan baris total = angka kartu. Baris di popup dapat diklik untuk membuka datanya. | `[DSH-1]`, `[DSH-3]`, `[DSH-4]` + `[USULAN]` (isi KPI & kolom rincian) |
| FR-42 | Dashboard menampilkan panel: (1) **Jadwal pelaksanaan terdekat** — maks. 5 Penjualan Program **Final** `[DSH-3]` yang tanggal selesainya belum lewat, urut tanggal mulai (tanggal, program, nomor, Company/lokasi, jenis, peserta); (2) **Perlu tindakan** — Master Program/Penjualan Program Draft, penjualan rugi, titik impas tidak tercapai atau di atas rencana peserta. Setiap baris dapat diklik untuk membuka datanya. Panel Public vs B2B dan Ringkasan Master Data **tidak dipakai** `[DSH-2]`. Hak lihat per Group Akses: Q-25. | `[DSH-1]` – `[DSH-3]` + `[USULAN]` (isi panel) |

---

## 5. Kebutuhan Non-Fungsional (NFR)

Setiap NFR punya kriteria uji yang terukur. Butir `[USULAN]` belum dinyatakan klien.

| ID | Kategori | Kebutuhan | Kriteria uji | Lingkup | Sumber |
|---|---|---|---|---|---|
| NFR-01 | Audit | Setiap tambah, ubah, aktif/nonaktif, dan hapus dicatat dengan `write_log()`. Untuk ubah, payload berisi snapshot `old` (diambil sebelum update) dan `new`. Simpan Product Combine mencatat semua Nomor Product Combine yang terbentuk. Perubahan Default Cost item rate tercatat Before/After. | Setelah 1 aksi ubah, Activity Log menampilkan tepat field yang berubah (Before/After); 1 simpan dengan 3 baris menghasilkan log yang menyebut 3 nomor paket | Semua UC | `[STD]` |
| NFR-02 | Integritas data | Kode setiap master, Nomor Product Combine, Kode Peserta, dan Kode Company **unik**; kombinasi 4 atribut Paket Produk juga **unik**. Keduanya dijaga di tingkat database (unique constraint/index). | Insert kode atau kombinasi duplikat langsung ke DB ditolak; 2 penyimpanan bersamaan tidak menghasilkan kode atau kombinasi kembar | FR-03, FR-13, FR-17, FR-22, FR-32 | `[WISH-1]`, `[KLR-Q-12]` + `[USULAN]` (constraint DB) |
| NFR-03 | Integritas data | Simpan Product Combine berjalan dalam **satu transaksi database**: semua baris tersimpan atau tidak satu pun. | Memaksa gagal pada baris ke-3 dari 3 → tidak ada paket baru di DB | FR-13, FR-22 | `[USULAN]` |
| NFR-04 | Integritas data | Master atribut produk, Paket Produk, dan item Master Rate tidak memiliki operasi hapus (tidak ada endpoint/tombol Delete); status diganti lewat aktif/nonaktif. | Tidak ada route hapus untuk 4 master, Paket Produk, dan Master Rate; jumlah baris tabel tidak berkurang setelah nonaktif | FR-08, FR-23, FR-35 | `[KLR-Q-05]`, `[KPG-3]` |
| NFR-05 | Validasi | Field wajib divalidasi di sisi klien **dan** server; penyimpanan ditolak dengan pesan yang menyebut field yang kosong. | Request POST tanpa field wajib (melewati JS) ditolak server dan tidak ada baris tersimpan | FR-09, FR-16, FR-19, FR-20, FR-31 | `[MD:Master Peserta]` (tanda *) + `[USULAN]` |
| NFR-06 | Validasi | Email (Peserta, Company, PIC) harus berformat email yang valid; No Telp/WA hanya angka (boleh diawali `+`), 9–15 digit. NPWP 15 atau 16 digit angka (tanda baca diabaikan). | `abc@` ditolak; `08123456789` diterima; NPWP `01.234.567.8-901.000` diterima | FR-16, FR-31 | `[USULAN]` |
| NFR-07 | Kinerja | Halaman daftar memakai paginasi dan pencarian sisi server. | Daftar dengan 10.000 baris tampil < 2 detik (p95) | Semua daftar | `[USULAN]` |
| NFR-08 | Keamanan | Akses menu dan aksi (lihat/tambah/ubah/aktif-nonaktif/hapus) dikendalikan oleh mekanisme permission yang sudah ada di aplikasi. | Pengguna tanpa hak ubah status tidak melihat tombol Nonaktifkan dan request langsung ditolak (HTTP 403 / redirect) | Semua UC | `[USULAN]` |
| NFR-09 | Platform | Dibangun di CodeIgniter 3 (HMVC, `application/modules/`), MySQL, Bootstrap 4/5 + jQuery. Nama tabel/kolom identik dengan ERD. | Code review: modul berada di `application/modules/`, nama tabel = ERD | Semua | `[STD]` |
| NFR-10 | Usability | Semua pilihan master pada form (Produk, Bidang, Varian, Sertifikasi, Satuan, COA, Provinsi, Kota, Jenis Instansi) memakai dropdown yang dapat dicari. | Mengetik 3 huruf menyaring opsi pada dropdown | FR-12, FR-16, FR-19, FR-20, FR-31 | `[USULAN]` |
| NFR-11 | Usability | Logo dan kolom search menu di sidebar **tetap terlihat (freeze)**; hanya daftar menu yang dapat digulir. | Dengan daftar menu lebih tinggi dari layar, setelah daftar menu digulir sampai bawah kolom search masih terlihat dan dapat diketik tanpa menggulir kembali; berlaku juga di layar ≤ 900 px (sidebar sebagai panel) | FR-29 | `[RMD-6]` |
| NFR-12 | Validasi | Default Cost berupa angka rupiah ≥ 0 tanpa desimal, ditampilkan dengan pemisah ribuan. | Default Cost `-1` atau `abc` ditolak; `1600000` tampil `1.600.000` | FR-19, FR-20 | `[CM]` + `[USULAN]` |

---

## 6. Pertanyaan Terbuka

### 6.1 Masih terbuka

| ID | Pertanyaan | Mempengaruhi |
|---|---|---|
| Q-01 | Bagaimana **format penomoran** kode untuk Jenis Produk, Jenis Sertifikasi, Jenis Bidang, Varian Pelaksanaan, Nomor Product Combine, Kode Peserta, Kode Company, dan Kode Fixed/Variable Cost? (prefix, panjang digit, reset per tahun/tidak) | FR-03, FR-13, FR-17, FR-19, FR-20, FR-32 |
| Q-06 | Siapa pengguna yang mengelola **Master Peserta**, **Master Company**, **Master Rate**, dan yang **membuat Katalog**? *(masih ditanyakan Labmania)* | UC-06 – UC-09, UC-11 |
| Q-07b | Kolom **Lokasi** di daftar peserta menampilkan Provinsi, Kota, atau keduanya? Dari mana sumber data Provinsi & Kota/Kabupaten (tabel wilayah yang ada di sistem, atau diinput Labmania)? | FR-15, FR-16, FR-31 |
| Q-09 | Format **Katalog Produk** *(belum ada, menunggu Labmania)*. | FR-14 |
| Q-13 | **Pain Point** proses saat ini *(masih digali Labmania)*. | Cakupan |
| Q-14 | Setelah tersimpan, apakah Paket Produk masih dapat **diubah** (judul atau atributnya), atau hanya dapat dinonaktifkan lalu dibuat paket baru? | FR-10, UC-10 |
| Q-16 | Apakah **Peserta** dan **Company** juga hanya boleh dinonaktifkan (tidak dihapus), seperti master lain dan Master Rate? (saat ini mengikuti mockup: Edit/Delete) | FR-15, FR-32 |
| Q-17 | Apakah paket yang **nonaktif** ikut dihitung dalam cek keunikan kombinasi? (mis. paket lama dinonaktifkan, lalu kombinasi yang sama dibuat lagi) | FR-22 |
| Q-18 | Apakah nama sub modul **Atribut Produk**, **Produk**, **Customer**, dan **Biaya** di bawah modul Master Data sudah sesuai? | FR-27 |
| Q-20 | Aplikasi sudah punya tabel **`customer`** (nama, alamat, provinsi, kota, telepon, email, NPWP, PIC, status aktif). Boleh Master Company memakai tabel itu (ditambah kolom Jenis Instansi), atau perlu tabel baru? *(ERD draf 4 memakai `customer`)* | FR-30 – FR-32, ERD |
| Q-25 | Master User: (a) isi pilihan **Group Akses** dan hak tiap group (contoh mockup: Manajemen, Admin, Staff); (b) data **Department, Division, Jabatan** — **menyusul dari Labmania** (`input/Master - Buat Program/2026-10-28-master-user-data-menyusul/`); mockup memakai contoh. Employee Type dan Religion — memakai master aplikasi atau diisi Labmania?; (c) **Employee ID** diinput manual (seperti form acuan) atau dibuat otomatis seperti modul `employees` (EMP…); (d) boleh memakai tabel existing `employees` / `users`? | FR-36 – FR-39, ERD |

### 6.2 Sudah terjawab

Sumber: 22-10-2026 `input/Master - Buat Program/2026-10-22-klarifikasi-produk/`; 23-10-2026 `input/Master - Buat Program/2026-10-23-review-mockup-master-data/` dan `input/Master - Buat Program/2026-10-23-klarifikasi-program/`.

| ID | Jawaban | Diterapkan di |
|---|---|---|
| SIPOC | Output juga berupa paket produk yang siap dibuat menjadi Product Costing | Bagian 1, FR-24 |
| Q-02 | Ikuti *Data Mentah*: Pelatihan/Sertifikasi = Jenis Produk; Sertifikat BNSP dst. = Jenis Sertifikasi | FR-04, FR-05 |
| Q-03 | Satu kali combine dapat menghasilkan lebih dari satu paket; saat disimpan menjadi per item paket | FR-10, FR-12, FR-13 |
| Q-04 | Paket Produk diberi opsi aktif / nonaktif | FR-10, FR-23 |
| Q-05 | Master tidak boleh dihapus, hanya dinonaktifkan | FR-08, FR-21, NFR-04 |
| Q-07 | Kota/Kabupaten tersaring berdasarkan Provinsi | FR-16 |
| Q-08 | Tab hanya Fixed Cost dan Variable Cost; Event/Day/Hours adalah satuan *(Hours dicatat sebagai Event, lihat Q-15)* | FR-18, FR-25 |
| Q-08b | *(23-10-2026)* Nominal tarif diinput di **Master Rate** sebagai Default Cost (satu harga per item) | FR-19, FR-20, FR-33, FR-34 |
| Q-10 | "No Bidang", "No Varian", dan label "Kode Sertifikasi" di form Jenis Bidang adalah Kode | FR-06, FR-07 |
| Q-11 | *Data Mentah* adalah data awal; item ganda dimuat satu kali | FR-26 |
| Q-11b | *(23-10-2026)* "Fix Cost / Event" adalah **judul kelompok** (di *Cost Master* baris itu tanpa kategori/harga), tidak dimuat. Hotel Trainer \*\* / \*\*\* = hotel bintang 2 / bintang 3 (*Cost Master* G16). | FR-26, bagian 7 |
| Q-12 | Kombinasi paket harus berbeda minimal di salah satu jenis | FR-22 |
| Q-15 | *(23-10-2026)* Satuan Fixed Cost hanya Event dan Day; satuan Variable Cost hanya Peserta/Event dan Peserta/Day. **Hours dicatat sebagai Event** `[KPG-1]` (menggantikan keputusan draf 3 "Day & Hours → Day"). Mandays adalah output costing B2B, bukan satuan. | FR-19, FR-20, FR-25, bagian 7 |
| Q-15b | *(23-10-2026)* Form Fixed Cost sama dengan Variable Cost (*Cost Master* memakai kolom yang sama untuk semua kategori) | FR-20 |
| Q-16 (Rate) | *(23-10-2026)* Item Master Rate dinonaktifkan, tidak dihapus `[KPG-3]`. Untuk Peserta & Company masih terbuka. | FR-35, NFR-04 |
| Peserta wajib | *(23-10-2026)* Semua field form Peserta wajib `[KPG-2]` | FR-16 |
| Harga B2B | *(23-10-2026)* Satu item satu Default Cost, tidak ada harga B2B terpisah `[KPG-4]`. `[MP-OP-4]` di Excel Master Program menanyakan hal yang sama — sudah terjawab. | FR-34 |
| Q-24 | *(23-10-2026)* Master Rate tidak dihapus, hanya dinonaktifkan `[ARH-6]` (mockup Excel Edit/Delete tidak diikuti) | FR-35, NFR-04 |
| Q-22 | *(23-10-2026)* Hours hanya untuk perhitungan biaya trainer dan jatuhnya per **event**; 1 event = 1 trainer `[K3M-1]`. Mandays Rate Charge & Honor Trainer bersatuan Event. | FR-25, bagian 7 |
| Q-23 | *(23-10-2026)* Hotel Trainer \*\* dan \*\*\* keduanya terisi: dibiarkan — tidak ditolak, hanya peringatan `[K3M-6]` | Master Program, Penjualan Program |
| Q-19 | *(23-10-2026)* Semua field Master Company wajib kecuali NPWP `[MD-FORM:Master Company]` | FR-31 |
| Q-11b (lanjutan) | *(23-10-2026)* Item ganda "Transport Trainer Luar Kota PP" juga dicatat `[MD-RATE]` sebagai perlu konfirmasi; spec tetap memuat satu kali | bagian 7 |

---

## 7. Lampiran — Data Awal `[RAW]` `[CM]` `[KLR-Q-11]` `[KPG-6]`

Koreksi dari *Cost Master*: "Transport Trainer Luar Kota PP" tercatat dua kali (baris kedua tanpa
harga) → dimuat satu kali; item bersatuan **Jam** dicatat sebagai **Event** `[KPG-1]`; baris judul
kelompok dan baris Total tidak dimuat. File Excel di `input/` tidak diubah.

| Jenis Produk | Jenis Sertifikasi | Varian Pelaksanaan |
|---|---|---|
| Pelatihan | Sertifikat BNSP | In House Training |
| Sertifikasi | Sertifikat KAN ISO 29993 | Workshop |
| | Sertifikat Labmania | Workshop Berbasis Kompetensi |
| | Sertifikat Labmania KAN ISO 29993 | Workshop Lisensi BSN |
| | Sertifikat Labmania Lisensi BSN | |

**Jenis Bidang (17):** Analisis Kimia, Analisis Mikrobiologi, Energi, ISO 17025, ISO 17025 Manajemen,
ISO 22000 Manajemen, ISO 9001 Manajemen, K3, Kalibrasi, Kimia, Mikrobiologi, Okupasi, Pangan,
Pengelolaan Limbah, Penyelia, Petugas Sampling, Trainer.

**Item rate** — semua item memakai Default Cost dari *Cost Master* / *Data Mentah Master Rate*, dapat diedit saat costing `[REV-1]`.

| Tab | Item | Satuan | Default Cost (Rp) | Keterangan |
|---|---|---|---:|---|
| Fixed Cost | Transport Trainer Luar Kota PP | Event | 250.000 | Transport / Hari |
| Fixed Cost | Marketing/Ads | Event | 0 | Iklan |
| Fixed Cost | Dokumentasi | Event | 0 | Dokumentasi |
| Fixed Cost | Banner/Promosi | Event | 100.000 | Promosi |
| Fixed Cost | Administrasi | Event | 0 | Administrasi |
| Fixed Cost | Mandays Rate Charge Labmania | Event *(sumber: Jam)* | 1.600.000 | — |
| Fixed Cost | Honor Trainer | Event *(sumber: Jam)* | 2.125.000 | — |
| Fixed Cost | Hotel Trainer \*\* | Day | 375.000 | Hotel bintang 2 |
| Fixed Cost | Hotel Trainer \*\*\* | Day | 0 | Hotel bintang 3 |
| Fixed Cost | Konsumsi Trainer, di luar konsumsi selama training | Day | 140.000 | Konsumsi trainer / Hari |
| Variable Cost | Modul | Peserta/Event | 50.000 | Modul |
| Variable Cost | Sertifikat | Peserta/Event | 5.000 | Sertifikat |
| Variable Cost | Seminar Kit | Peserta/Event | 35.000 | Kit |
| Variable Cost | Goodybag | Peserta/Event | 5.000 | Goodybag |
| Variable Cost | Merchandise | Peserta/Event | 0 | Merchandise |
| Variable Cost | Sertifikasi LSP | Peserta/Event | 0 | Sertifikasi LSP |
| Variable Cost | Ongkir Dokumen | Peserta/Event | 15.000 | Ongkir Dokumen |
| Variable Cost | Makan Siang Training | Peserta/Day | 50.000 | Makan/snack untuk peserta + trainer + asisten |
| Variable Cost | Coffee Break | Peserta/Day | 40.000 | Coffee break untuk peserta + trainer + asisten |

Catatan data awal:
- Total yang benar menurut data di atas (bukan rumus D39 di Excel yang menjumlahkan subtotal dua kali): Fixed/Event Rp 4.075.000, Fixed/Day Rp 515.000, Variable Peserta/Event Rp 110.000, Variable Peserta/Day Rp 90.000.

---

## 8. Riwayat Perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| draf 1 | 22-10-2026 | Penyusunan awal dari `input/Product - Labmania.xlsx` |
| draf 2 | 22-10-2026 | Klarifikasi `input/Master - Buat Program/2026-10-22-klarifikasi-produk/`: SIPOC output Paket Produk; Jenis Sertifikasi mengikuti *Data Mentah*; combine n baris → n paket; status aktif/nonaktif paket; master tanpa hapus; Kota tersaring Provinsi; Event/Day/Hours sebagai satuan; data awal; keunikan kombinasi. Tambah FR-21 – FR-26, UC-10 (aktif/nonaktif paket), NFR-03 (transaksi) & NFR-04 (tanpa hapus); entity *Product Combine Detail* diganti *Paket Produk*. |
| draf 3 | 23-10-2026 | Review mockup `input/Master - Buat Program/2026-10-23-review-mockup-master-data/`: semua menu dijadikan satu modul **Master Data** dengan sub modul (bagian 1, FR-27, kolom Sub Modul di tabel UC); daftar Paket Produk dipecah per atribut (FR-10); kolom Action memakai menu gear (FR-08, FR-10, FR-15, FR-19, FR-28); satuan rate dibatasi per tab dan Hours dihapus (FR-19, FR-20, FR-25, UC-08, UC-09, Q-15 terjawab, Q-15b sisa); search menu di sidebar (FR-29) yang tetap terlihat saat digulir (NFR-11); Q-18 baru. ERD pertama dibuat di `docs/diagrams/erd.drawio`. |
| draf 4 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-requirements-program/` dan `input/Master - Buat Program/2026-10-23-klarifikasi-program/`: aplikasi dibagi dua modul (Master Data, Program Penjualan — dokumen terpisah); judul dokumen menjadi *Modul Master Data*; sub modul *Peserta & Rate* dipecah menjadi *Customer* dan *Biaya*; **Master Company** baru (FR-30 – FR-32, UC-11, Q-19, Q-20); Peserta = customer Public, semua field wajib (FR-15, FR-16); Master Rate mendapat Default Cost, Mode Harga, Keterangan, status Aktif tanpa hapus (FR-19, FR-20, FR-33 – FR-35, NFR-04, NFR-12, UC-08, UC-09); Hours → Event, Mandays bukan satuan (FR-25); data awal ditambah harga & mode dari *Cost Master* (FR-26, bagian 7); Q-08b, Q-11b, Q-15b terjawab, Q-16 dipersempit, Q-21 baru; navigasi dua modul (FR-27, FR-29). |
| draf 5 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-requirements-3-modul/`: aplikasi menjadi tiga modul (Master Data, Master Program, Penjualan Program); Public/B2B dan customer pindah ke Quotation (FR-15, FR-30, UC-07, UC-11); Paket Produk dipilih di Master Program (FR-24); Master Company semua field wajib kecuali NPWP, daftar dengan kolom Lokasi (FR-31, FR-32, Q-19 terjawab); Master Rate mendapat field Kategori & Aktif, Default Cost dapat disesuaikan di Master Program dan Quotation (FR-19, FR-35); SIPOC dan kolom *Dipakai untuk* mengikuti Excel baru; Q-22 (Hours), Q-23 (Hotel), Q-24 (Delete Master Rate) baru. UC-12 – UC-18 dihapus. |
| draf 6 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-konfirmasi-3-modul/`: Q-22 terjawab — Hours = biaya trainer, dihitung per event (1 event = 1 trainer) (FR-25); Q-23 terjawab — Hotel \*\* dan \*\*\* tidak dibatasi, cukup peringatan seperti mockup. |
| draf 7 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-arah-penjualan-program/`: Q-24 terjawab — Master Rate hanya nonaktif; Company dipilih di Penjualan Program B2B (FR-30, UC-11); Peserta dipakai modul Quotation yang ditunda (FR-15, UC-07); rujukan Quotation diganti Penjualan Program. |
| draf 8 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-revisi-rate-harga-simulasi/`: mode harga Master Rate dihapus — semua item Default Cost yang dapat diedit (FR-19, FR-20, FR-26, FR-33 dihapus, NFR-12, UC-08, UC-09, bagian 7; Q-21 gugur); Seminar Kit dan Ongkir Dokumen kembali memakai Default Cost Rp 35.000 / Rp 15.000. |
| draf 9 | 25-10-2026 | `input/Master - Buat Program/2026-10-25-jalur-public-b2b/`: Company dipilih di Sales Order B2B (ditunda), bukan di Penjualan Program (FR-30, bagian 1); Penjualan Program sama untuk Public dan B2B. |
| draf 10 | 28-10-2026 | `input/Master - Buat Program/2026-10-28-jawaban-pertanyaan-program/`: Company aktif dipilih di Penjualan Program B2B, bukan Sales Order (FR-30); Master Company punya status Aktif/Nonaktif (FR-32). |
| draf 11 | 28-10-2026 | `input/Master - Buat Program/2026-10-28-master-user/`: sub modul **Pengguna** → **Master User** (FR-36 – FR-39, UC-32, bagian 1); form mengikuti `input/Master User.png` + bagian Akun Login (usulan); Q-25 baru. |
| draf 11a | 28-10-2026 | `input/Master - Buat Program/2026-10-28-master-user-data-menyusul/`: data Department, Division, Jabatan menyusul; modul Master User tetap dipakai dengan data contoh (Q-25 b). |
| draf 11b | 28-10-2026 | `input/Master - Buat Program/2026-10-28-gabung-menu-penjualan-program/`: sidebar dua modul — Master Program & Penjualan Program digabung di modul **Penjualan Program** (FR-27, bagian 1). |
| draf 11c | 28-10-2026 | `input/Master - Buat Program/2026-10-28-sidebar-tanpa-label/`: label sub modul tidak tampil di sidebar; breadcrumb Modul / Menu; search hanya nama menu (FR-27, FR-29). |
| draf 11d | 28-10-2026 | `input/Master - Buat Program/2026-10-28-dashboard-depan/`: **Dashboard** halaman depan (FR-40 – FR-42, isi usulan analis); menu Dashboard paling atas di sidebar (FR-27). |
| draf 11e | 28-10-2026 | `input/Master - Buat Program/2026-10-28-dashboard-ringkas/`: Dashboard tanpa tombol cepat, panel Public vs B2B, dan Ringkasan Master Data (FR-40, FR-42). |
| draf 11f | 28-10-2026 | `input/Master - Buat Program/2026-10-28-dashboard-jadwal-final/`: KPI Dashboard = total seluruh data, rumus pendapatan & profit dijelaskan (FR-41); jadwal terdekat hanya Penjualan Program Final (FR-42). |
| draf 11g | 28-10-2026 | `input/Master - Buat Program/2026-10-28-dashboard-rincian-kartu/`: kartu KPI Dashboard dapat diklik → popup rincian sumber data, rumus, dan daftar penyusun angka (FR-41). |
| draf 11h | 28-10-2026 | `input/Master - Buat Program/2026-10-28-quotation-company-personal/`: Master Peserta punya field **Company** opsional (FR-16) dan tidak dapat dihapus bila sudah di quotation (FR-15); modul Quotation → `docs/fr-nfr-quotation.md`. |
