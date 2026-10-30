# FR & NFR Master - Quotation — Labmania

- **Isi:** gabungan lima dokumen FR & NFR, urut alur: Master Data → Master Program → Penjualan Program → Quotation, ditambah Jejak Data & Activity Log (lintas modul)
- **Tanggal salinan:** 30-10-2026
- **Sumber kerja (yang tetap diperbarui):** `specs/docs/fr-nfr.md`, `fr-nfr-master-program.md`, `fr-nfr-penjualan-program.md`, `fr-nfr-quotation.md`, `fr-nfr-activity-log.md`. Dokumen ini salinan final; perubahan berikutnya dibuat di file sumber lalu salinan dibuat ulang.
- **Pasangan final:** `ERD Master - Quotation.drawio`, `Flow Process Master - Quotation.drawio`, `Use Case Master - Quotation.xlsx`, `Mock Up Master - Quotation/index.html`

## Daftar isi

| Bagian | Modul | Versi | Awalan kode | File sumber |
|---|---|---|---|---|
| Bagian 1 | Modul Master Data Labmania | draf 11q — 30-10-2026 | FR- / NFR- / Q- | `docs/fr-nfr.md` |
| Bagian 2 | Modul Master Program Labmania | draf 11b — 30-10-2026 | MP- | `docs/fr-nfr-master-program.md` |
| Bagian 3 | Modul Penjualan Program Labmania | draf 12b — 30-10-2026 | PJ- | `docs/fr-nfr-penjualan-program.md` |
| Bagian 4 | Modul Quotation Labmania | draf 9c — 30-10-2026 | QTN- | `docs/fr-nfr-quotation.md` |
| Bagian 5 | Jejak Data & Modul Activity Log Labmania | draf 2 — 30-10-2026 | LOG- | `docs/fr-nfr-activity-log.md` |

Penomoran FR/NFR, UC, dan pertanyaan terbuka (Q-…) tetap milik masing-masing bagian. Penanda sumber (`[…]`) dan rujukan `input/…` dibaca relatif terhadap folder `specs/`.

---

# Bagian 1 — Modul Master Data Labmania

- **Versi:** draf 11q — 30-10-2026
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
Jenis Sertifikat ──┤                                    ┌─► Paket Produk ──► Master Program ──► Penjualan Program ──► Quotation / Sales Order
Jenis Bidang ───────┴──► Product Combine (judul manual) ─┤                          ▲                ▲   ▲   ▲
                          n baris → n paket produk        └─► Katalog Produk         │                │   │   │
                                                                                     │                │   │   │
                         Master Rate (item biaya + Default Cost) ────────────────────┴────────────────┘   │   │
                         Master Peserta (customer Public) ────────────────────────────────────────────────┘   │
                         Master Company (customer B2B) ───────────────────────────────────────────────────────┘
```

SIPOC `[MD-SIPOC]` (menggantikan `[SIPOC]` + `[KLR-SIPOC]`):

| Supplier | Input | Process | Output | Customer |
|---|---|---|---|---|
| Manajemen | Jenis produk, jenis sertifikasi, jenis bidang, judul/topik produk, item & default cost (Master Rate), data peserta, data company | Master Data | Product Combine (Paket Produk), Katalog Produk, Master Rate, Master Peserta & Master Company | Master Program, Quotation, Promosi |

Menu modul Master Data dikelompokkan per sub modul `[RMD-1]`:

| Modul | Sub Modul | Menu | Dipakai untuk |
|---|---|---|---|
| Master Data | Atribut Produk | Jenis Produk, Jenis Sertifikat, Jenis Bidang | Master Program (lewat Product Combine) |
| Master Data | Produk | Product Combine, Katalog Produk | Master Program |
| Master Data | Customer | Master Peserta, Master Company | Peserta: Quotation · Company: Penjualan Program B2B, Quotation ke Company |
| Master Data | Biaya | Master Rate | Master Program, Penjualan Program |
| Master Data | Trainer | Master Trainer | Penjualan Program (pilih trainer) `input/Master - Buat Program/2026-10-29-master-trainer/` |
| Master Data | Dokumen | Template Keterangan Quotation, Template Formulir Konfirmasi | Quotation (blok Keterangan Penawaran; Formulir Konfirmasi) `input/Master - Buat Program/2026-10-29-keterangan-quotation-default/` |
| Master Data | Pengguna | Master User | Login & hak akses seluruh modul (Q-25) |

Nama sub modul masih usulan analis (Q-18). Draf 3 memakai satu sub modul *Peserta & Rate*;
sejak Master Company masuk, sub modul itu dipecah menjadi *Customer* dan *Biaya*. Kolom
"Dipakai untuk" mengikuti kolom *Dipakai Untuk* di `[MD-2]` – `[MD-10]`.

**Pain point:** bagian 2 sheet *Requirements Product* masih kosong dan masih digali Labmania `[KLR-Q-13]`.

### 1.1 Cakupan

| Termasuk | Tidak termasuk (dokumen ini) |
|---|---|
| Master Jenis Produk, Jenis Sertifikat, Jenis Bidang | Master Program (costing baseline) → `fr-nfr-master-program.md` |
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
| Paket Produk | Satu kombinasi Jenis Produk + Jenis Sertifikat + Jenis Bidang beserta Judul, hasil simpan dari Product Combine. Diberi **Nomor Product Combine**. |
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
| UC-02 | Mencatat Jenis Sertifikat | Manajemen | Master Data | Atribut Produk | Nama Jenis Sertifikat | Sama dengan UC-01 untuk master Jenis Sertifikat. | Data Jenis Sertifikat tersimpan dengan kode otomatis dan status | Jenis Sertifikat | FR-03, FR-05, FR-08, FR-09, FR-21 |
| UC-03 | Mencatat Jenis Bidang | Manajemen | Master Data | Atribut Produk | Nama Jenis Bidang | Sama dengan UC-01 untuk master Jenis Bidang. Jenis Bidang juga menjadi pilihan Bidang pada Master Peserta. | Data Jenis Bidang tersimpan dengan kode otomatis dan status | Jenis Bidang | FR-03, FR-06, FR-08, FR-09, FR-21 |
| UC-04 | ~~Mencatat Varian Pelaksanaan~~ **Dihapus** `input/Master - Buat Program/2026-10-29-npwp-instansi-hapus-varian/` | Manajemen | Master Data | Atribut Produk | Nama Varian | Sama dengan UC-01 untuk master Varian Pelaksanaan. | Data Varian Pelaksanaan tersimpan dengan kode otomatis dan status | Varian Pelaksanaan | FR-03, FR-07, FR-08, FR-09, FR-21 |
| UC-05 | Menyusun Product Combine | Manajemen | Master Data | Produk | Judul; satu atau lebih baris kombinasi Produk + Bidang + Sertifikat | Aktor mengisi Judul secara manual, menambah baris kombinasi, memilih keempat atribut aktif per baris, menghapus baris yang tidak perlu, lalu menyimpan. Sistem menolak kombinasi yang keempat atributnya sama persis dengan paket lain atau baris lain. Setiap baris disimpan sebagai satu Paket Produk dengan Nomor Product Combine sendiri. | Satu Paket Produk per baris, berstatus aktif, tampil di daftar | Paket Produk, Jenis Produk, Jenis Sertifikat, Jenis Bidang, Varian Pelaksanaan | FR-01, FR-02, FR-10 – FR-13, FR-22 – FR-24 |
| UC-06 | Menghasilkan Katalog Produk | *TBD (Q-06)* | Master Data | Produk | Paket Produk aktif | **Ditunda** — format katalog belum ada. Sistem menyusun katalog dari Paket Produk. | Katalog Produk | Paket Produk, Katalog Produk | FR-14 |
| UC-07 | Mencatat Peserta | *TBD (Q-06)* | Master Data | Customer | Nama, Gender, Instansi, Bidang, Provinsi, Kota/Kabupaten, No Telp/WA, Email (semua wajib); NPWP Instansi opsional | Aktor menambah peserta lewat form (Kota/Kabupaten tersaring menurut Provinsi), mengubah, atau menghapus peserta dari daftar. Peserta menjadi customer penjualan Public dan dipilih di modul Quotation (ditunda). | Data Peserta tersimpan dengan Kode Peserta | Peserta, Jenis Bidang, Provinsi, Kota/Kabupaten | FR-15 – FR-17 |
| UC-08 | Mencatat Item Variable Cost | *TBD (Q-06)* | Master Data | Biaya | Item, Kategori (Variable), Satuan (Peserta/Event atau Peserta/Day), Default Cost, Keterangan, COA | Aktor membuka tab Variable Cost pada Master Rate, menambah item (kode dibuat sistem), mengubah, atau mengaktifkan/menonaktifkan item. Item tidak dapat dihapus. Pilihan Satuan hanya Peserta/Event dan Peserta/Day. | Item Variable Cost tersimpan dengan kode otomatis, Default Cost, dan status | Rate Variable Cost, Satuan, COA | FR-18, FR-19, FR-25, FR-33 – FR-35 |
| UC-09 | Mencatat Item Fixed Cost | *TBD (Q-06)* | Master Data | Biaya | Item, Kategori (Fixed), Satuan (Event atau Day), Default Cost, Keterangan, COA | Sama dengan UC-08 pada tab Fixed Cost. Pilihan Satuan hanya Event dan Day. | Item Fixed Cost tersimpan dengan kode otomatis, Default Cost, dan status | Rate Fixed Cost, Satuan, COA | FR-18, FR-20, FR-25, FR-33 – FR-35 |
| UC-10 | Mengedit, Mengaktifkan / Menonaktifkan Paket Produk | Manajemen | Master Data | Produk | Paket Produk terpilih | Aktor memilih paket pada daftar lalu mengedit judul/atributnya (FR-43) atau mengubah statusnya menjadi aktif atau nonaktif. | Status Paket Produk berubah | Paket Produk | FR-10, FR-23 |
| UC-11 | Mencatat Company | *TBD (Q-06)* | Master Data | Customer | Nama, Jenis Instansi, Alamat, Provinsi, Kota, Telepon & Email kantor, PIC (Nama, Jabatan, Telp/WA, Email) — semua wajib; NPWP Instansi opsional | Aktor menambah company lewat form, mengubah, atau menghapus company dari daftar. Company menjadi customer penjualan B2B dan dipilih saat membuat Penjualan Program B2B. | Data Company tersimpan dengan Kode Company | Company, Provinsi, Kota/Kabupaten | FR-30 – FR-32 |
| UC-32 | Mencatat User | Manajemen | Master Data | Pengguna | Employee ID, Employee Name, Department, Division, Jabatan, Gender, Phone Number, Employee Type, Status (wajib); NIK, Birth Place/Date, Religion, Email, Address, NPWP, Join Date, Job Desc. (opsional); akun login: Username, Password, Group Akses | Aktor menambah user lewat form, mengubah, mengaktifkan/menonaktifkan, dan me-reset password. User yang diberi akun login dapat masuk ke sistem sesuai Group Akses; user nonaktif tidak dapat login. | Data user tersimpan; akun login aktif bila diberi | User (employees), Akun (users), Group Akses, Department, Division, Jabatan | FR-36 – FR-39 |
| UC-38 | Mencatat Trainer | Manajemen *(sementara, Q-26)* | Master Data | Trainer | Identitas (Nama lengkap wajib), pendidikan formal, bidang keahlian, pengesahan CV; daftar Pelatihan & Seminar, Pengalaman Kerja, Pengalaman Mengajar, Lampiran Dokumen | Aktor menambah/mengubah trainer lewat form bertab, melihat CV, atau menonaktifkan trainer. Trainer aktif dipilih di Penjualan Program. | Data Trainer tersimpan dengan ID Trainer | Trainer, Riwayat Trainer | FR-44 – FR-47 |
| UC-39 | Mencatat Template Keterangan Quotation | Manajemen *(sementara)* | Master Data | Dokumen | Kategori, Teks, Berlaku untuk, Default | Aktor menambah/mengubah butir keterangan per kategori atau menonaktifkannya. Butir Default tersalin ke quotation baru. | Butir tersimpan dengan kode | Template Keterangan | FR-48 – FR-50 |
| UC-40 | Mencatat Template Formulir Konfirmasi | Manajemen *(sementara)* | Master Data | Dokumen | Nama template, Label kegiatan, Default untuk Jenis Produk, Isian, Pernyataan, Catatan | Aktor menambah/mengubah template, melihat contoh, atau menonaktifkannya. Template default terpilih otomatis di quotation menurut Jenis Produk paket. | Template tersimpan dengan kode | Template Formulir Konfirmasi, Jenis Produk | FR-51 – FR-53 |

> UC-06 s.d. UC-09 dan UC-11 belum lolos uji admission no. 1 (aktor bernama) — aktor masih ditanyakan `[KLR-Q-06]`.
> Kode UC tidak pernah dipakai ulang. UC-12 – UC-18 (Program Public/B2B, draf 4) sudah dihapus dan tidak dipakai lagi; UC-19 – UC-21 = Master Program, UC-22 – UC-28 = Penjualan Program.
> Navigasi menu, search menu, dan kolom Action (FR-27 – FR-29) tidak menjadi use case sendiri: tidak ada tujuan aktor yang berdiri sendiri, melainkan cara mencapai UC-01 – UC-11.

---

## 4. Kebutuhan Fungsional (FR)

### 4.1 Struktur Produk

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-01 | Paket produk dibentuk dengan meng-combine **tiga** master: **Jenis Produk**, **Jenis Sertifikat**, dan **Jenis Bidang** (Varian Pelaksanaan dihapus `input/Master - Buat Program/2026-10-29-npwp-instansi-hapus-varian/`). Paket produk tidak dapat dibuat tanpa memilih ketiga atribut dari master tersebut. | `[REQ-1]` |
| FR-02 | Ketiga atribut **berdiri sendiri (tidak bertingkat)** — tidak ada relasi induk-anak di antara master tersebut. Pilihan pada satu atribut tidak membatasi pilihan atribut lain. | `[REQ-2]` |
| FR-03 | Setiap master memiliki **kode yang dibuat otomatis oleh sistem** dengan format penomoran standar dari Labmania. Format per master menunggu Q-01. | `[WISH-1]`, `[MD:*]`, `[KLR-Q-10]` |

### 4.2 Master Atribut Produk

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-04 | **Master Jenis Produk.** Daftar menampilkan: No, Kode Jenis Product, Jenis Product, Status, Action. Form tambah: Kode Jenis Product (otomatis), Jenis Product (teks bebas). Data awal: Pelatihan, Sertifikasi. | `[MD:Jenis Product]`, `[RAW]`, `[KLR-Q-02]` |
| FR-05 | **Master Jenis Sertifikat.** Daftar: No, Kode Sertifikat, Jenis Sertifikat, Status, Action. Form tambah: Kode Sertifikat (otomatis), Jenis Sertifikat (teks bebas). Nilainya adalah jenis sertifikat (mis. Sertifikat BNSP, Sertifikat KAN ISO 29993) sesuai *Data Mentah* — **bukan** "Pelatihan / Sertifikasi" seperti tertulis di REQ-3. Nama master ditampilkan **Jenis Sertifikat** (sebelumnya Jenis Sertifikasi) `input/Master - Buat Program/2026-10-29-edit-master-sertifikat-cari/`; nama tabel ERD `ms_jenis_sertifikasi` belum diubah. | `[MD:Jenis Sertifikat]`, `[RAW]`, `[KLR-Q-02]` |
| FR-06 | **Master Jenis Bidang** — bidang keilmuan/standar yang dibahas di pelatihan (mis. ISO 17025, Analisis Kimia, Kalibrasi). Daftar: No, Kode Bidang, Jenis Bidang, Status, Action. Form tambah: Kode Bidang (otomatis), Jenis Bidang (teks bebas). Jenis Bidang juga dipakai untuk **mengelompokkan peserta** (FR-16). | `[REQ-4]`, `[MD:Jenis Bidang]`, `[KLR-Q-10]` |
| FR-07 | ~~**Master Varian Pelaksanaan** — cara melaksanakan suatu jenis bidang.~~ **Dihapus** `input/Master - Buat Program/2026-10-29-npwp-instansi-hapus-varian/`: master Varian Pelaksanaan tidak dipakai lagi dan tidak menjadi atribut Paket Produk. | `input/Master - Buat Program/2026-10-29-npwp-instansi-hapus-varian/` (menggantikan `[REQ-5]`) |
| FR-08 | Kolom Action pada daftar master FR-04 s.d. FR-06 berisi opsi **Edit** dan **Aktifkan / Nonaktifkan** (lewat menu gear, FR-28). Data master **tidak dapat dihapus**; tombol Delete pada mockup diganti Nonaktifkan. Hal yang sama berlaku untuk **Paket Produk** (Produk): Edit dan Aktifkan / Nonaktifkan (FR-43) `input/Master - Buat Program/2026-10-29-edit-master-sertifikat-cari/`. | `[MD:*]`, `[KLR-Q-05]`, `[RMD-4]` |
| FR-09 | Field nama pada setiap master (FR-04 s.d. FR-06) wajib diisi sebelum disimpan. | `[USULAN]` |
| FR-21 | Master berstatus **nonaktif** tidak muncul sebagai pilihan pada form Product Combine dan form Peserta. Paket Produk dan Peserta yang sudah memakai master tersebut tetap menampilkan nilainya. | `[KLR-Q-05]` + `[USULAN]` (perilaku nonaktif) |

### 4.3 Product Combine & Paket Produk

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-10 | **Daftar Paket Produk** menampilkan satu baris per paket dengan kolom: No, Nomor (Nomor Product Combine), Judul Topik, **Produk, Bidang, Sertifikat** (satu kolom per atribut, urutan sama dengan tabel kombinasi FR-12), Status, Action (menu gear: **Edit** (FR-43) dan Aktifkan / Nonaktifkan). Atribut yang masternya sudah nonaktif tetap ditampilkan dengan penanda "(nonaktif)". | `[MD:Product (Combine)]`, `[KLR-Q-03]`, `[KLR-Q-04]`, `[RMD-2]` + `[USULAN]` (pemecahan kolom) |
| FR-11 | **Form Tambah Product** berisi field **Judul** (teks bebas, diinput manual oleh pengguna). | `[REQ-6]`, `[MD:Product (Combine)]` |
| FR-12 | Form Tambah Product memiliki **tabel kombinasi** berkolom: #, Produk, Bidang, Sertifikat, Action. Setiap baris memilih (select) satu nilai aktif dari master Jenis Produk, Jenis Bidang, dan Jenis Sertifikat. Setiap dropdown punya **kolom cari** (terutama Bidang yang daftarnya panjang) `input/Master - Buat Program/2026-10-29-edit-master-sertifikat-cari/`, NFR-10. Pengguna dapat menambah baris dan **menghapus** baris sebelum menyimpan. | `[MD:Product (Combine)]`, `[KLR-Q-03]` |
| FR-13 | Saat disimpan, **setiap baris kombinasi menjadi satu Paket Produk** yang membawa Judul dari form, dan masing-masing mendapat **Nomor Product Combine** otomatis (format Q-01). Satu kali simpan dengan n baris menghasilkan n paket produk. | `[KLR-Q-03]`, `[WISH-1]` |
| FR-22 | **Keunikan kombinasi:** antar paket produk minimal **satu** dari tiga atribut harus berbeda. Sistem menolak penyimpanan bila ada baris yang kombinasinya sama persis dengan paket yang sudah ada atau dengan baris lain di form yang sama, dan menunjukkan baris yang bentrok. Seluruh form gagal disimpan; tidak ada paket yang tersimpan sebagian. | `[KLR-Q-12]` + `[USULAN]` (perilaku gagal-semua) |
| FR-23 | Paket Produk memiliki status **Aktif / Nonaktif** yang dapat diubah dari daftar. Paket baru tersimpan berstatus Aktif. | `[KLR-Q-04]` + `[USULAN]` (default Aktif) |
| FR-24 | Paket Produk berstatus Aktif adalah output yang **siap dibuat menjadi Product Costing**, yaitu dapat dipilih saat membuat **Master Program**. | `[KLR-SIPOC]`, `[MD-7]` + `[USULAN]` (hanya paket aktif) |
| FR-43 | **Edit Paket Produk** lewat menu gear daftar Paket Produk: form berisi Nomor Product Combine (tetap), **Judul**, dan ketiga atribut (dropdown dapat dicari; nilai yang masternya sudah nonaktif tetap tampil sebagai pilihan terpilih). Cek keunikan FR-22 berlaku, tidak termasuk paket itu sendiri. Master Program yang memakai paket ikut menampilkan judul/atribut baru. | `input/Master - Buat Program/2026-10-29-edit-master-sertifikat-cari/` + `[USULAN]` (field yang dapat diubah, efek ke Master Program) |

### 4.4 Katalog Produk *(Wishlist — ditunda)*

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-14 | Dari Paket Produk sistem menghasilkan **Katalog Produk** untuk Promosi. Format katalog belum ada; FR ini tidak dikerjakan sampai Labmania menyiapkan format. | `[WISH-2]`, `[SIPOC]`, `[KLR-Q-09]` |

### 4.5 Master Peserta

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-15 | **Daftar Peserta** menampilkan: No, Kode Peserta, Nama Peserta, Bidang, Lokasi, Action (menu gear: Edit / Delete). Data diambil dari pengisian form Master Peserta. Peserta dipilih di modul **Quotation** (`docs/fr-nfr-quotation.md`); peserta yang sudah ada di quotation **tidak dapat dihapus** `input/Master - Buat Program/2026-10-28-quotation-company-personal/` + `[USULAN]`. Peserta juga dapat **ditambah dari form Quotation Personal** dan langsung tersimpan di Master Peserta (QTN-FR-07) `input/Master - Buat Program/2026-10-29-quotation-diskon-aksi/`. | `[MD:Master Peserta]`, `[RMD-4]`, `[MD-9]`, `[MD-FORM:Master Peserta]` |
| FR-16 | **Form Tambah Peserta** — **semua field wajib** (*): Nama Peserta (teks bebas), Gender (pilihan Laki-laki / Perempuan), Instansi/Perusahaan (teks bebas), Bidang (pilihan dari master **Jenis Bidang** aktif), Lokasi Provinsi (pilihan), Kota/Kabupaten (pilihan, **tersaring menurut Provinsi** yang dipilih), No Telp/WhatsApp (teks), Email (teks). REQ-7 hanya mewajibkan Nama, Instansi, Bidang, dan Provinsi; Labmania memutuskan semua field wajib. Tambahan: **Company** (opsional, pilihan Master Company aktif, "Tanpa company (personal)"); bila diisi, Instansi/Perusahaan = nama company (terkunci). Dipakai Quotation ke Company: hanya peserta company itu yang dapat dipilih `input/Master - Buat Program/2026-10-28-quotation-company-personal/` + `[USULAN]` (field Company). Tambahan: **NPWP Instansi** (opsional, 15/16 digit, NFR-06) `input/Master - Buat Program/2026-10-29-npwp-instansi-hapus-varian/`; bila Company dipilih, terisi NPWP Instansi company dan terkunci `[USULAN]`. | `[MD:Master Peserta]`, `[REQ-4]`, `[REQ-7]`, `[KLR-Q-07]`, `[KPG-2]` |
| FR-17 | Peserta memiliki **Kode Peserta** yang dibuat otomatis oleh sistem (format Q-01). | `[WISH-1]`, `[MD:Master Peserta]` |

### 4.6 Master Company

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-30 | **Master Company** menyimpan data perusahaan/instansi yang menjadi **customer penjualan B2B**. Company **aktif** dipilih (wajib) di **Penjualan Program B2B** (`fr-nfr-penjualan-program.md` PJ-FR-22) dan diteruskan ke Sales Order, satu SO untuk satu Company. Company baru dibuat di sini, tidak dari form Penjualan Program `[JPP-2]`. | `[REQ-8]`, `[MD-10]`, `[ARH-4]`, `[JLR-2]`, `[JPP-2]`, `[JPP-3]` |
| FR-31 | **Form Tambah Company** dibagi lima bagian: **Identitas** — Nama Company (wajib, teks bebas), Jenis Instansi (pilihan: Swasta, BUMN/BUMD, Pemerintah, Pendidikan, Lainnya); **Alamat** — Alamat lengkap (teks), Provinsi (pilihan), Kota/Kabupaten (pilihan, tersaring menurut Provinsi); **Kontak kantor** — Telepon, Email; **PIC** — Nama, Jabatan, Telp/WA, Email; **Pajak** — **NPWP Instansi** (label diganti dari NPWP `input/Master - Buat Program/2026-10-29-npwp-instansi-hapus-varian/`). **Semua field wajib kecuali NPWP Instansi.** | `[REQ-8]`, `[MD-10]`, `[MD-FORM:Master Company]` + `[USULAN]` (Kota tersaring Provinsi, mengikuti FR-16) |
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
| FR-26 | Data pada sheet *Data Mentah Struktur Product* dan *Data Mentah Master Rate* (`[MD-RATE]`, isi sama dengan *Cost Master*) dimuat sebagai **data awal** master Jenis Produk, Jenis Sertifikat, Jenis Bidang, Fixed Cost, dan Variable Cost (lampiran bagian 7), termasuk Default Cost. Item ganda dimuat satu kali; baris judul kelompok (mis. "Fix Cost / Event") dan baris Total tidak dimuat. | `[RAW]`, `[CM]`, `[KLR-Q-11]`, `[KPG-6]` |

### 4.9 Navigasi & Tampilan

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-27 | Sidebar menampilkan menu **Dashboard** paling atas (halaman pertama setelah login, FR-40), lalu dua modul, **Master Data** dan **Penjualan Program**, masing-masing dapat dibuka/ditutup. Modul Penjualan Program berisi sub modul **Master Program** (menu Daftar Master Program) lalu **Penjualan Program** (menu Daftar Penjualan Program) `[GMP-1]`. Di dalam modul, menu diurutkan per sub modul (tabel di bagian 1), tetapi **label sub modul tidak ditampilkan** — menu langsung di bawah nama modul `[SBL-1]`. Breadcrumb menampilkan Modul / Menu. | `[RMD-1]`, `[KPG-7]`, `[GMP-1]`, `[SBL-1]` + `[USULAN]` (nama sub modul, Q-18) |
| FR-28 | Kolom **Action** pada setiap tabel daftar hanya menampilkan **ikon gear**. Opsi aksi (Edit, Aktifkan/Nonaktifkan, Delete — sesuai FR masing-masing daftar) baru muncul sebagai menu setelah gear diklik. Menu tertutup bila pengguna mengklik di luar menu, menekan Esc, atau menggulir halaman. | `[RMD-4]` + `[USULAN]` (perilaku tutup menu) |
| FR-29 | Sidebar memiliki kolom **search menu** tepat di bawah logo Labmania. Pencarian menyaring menu dari **semua modul** berdasarkan nama menu saat pengguna mengetik `[SBL-1]`; modul tanpa hasil disembunyikan dan bila tidak ada yang cocok ditampilkan "Menu tidak ditemukan". Enter membuka hasil pertama, Esc mengosongkan pencarian. | `[RMD-5]` + `[USULAN]` (Enter/Esc, pencocokan sub modul) |


### 4.10 Master User

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-36 | **Master User** (sub modul **Pengguna**, modul Master Data) menyimpan data karyawan Labmania beserta **akun login** ke sistem. Memakai tabel existing aplikasi: data karyawan di `employees`, akun di `users` dengan group akses `user_groups` / `groups` (Q-25). Group **Admin Developer** wajib ada — bersama **Manajemen** menjadi satu-satunya group yang dapat membuka Activity Log (`docs/fr-nfr-activity-log.md` LOG-FR-09) `input/Master - Buat Program/2026-10-30-activity-log-akses-retensi/`. | `[USR-1]` + `[USULAN]` (sub modul, tabel) |
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

### 4.12 Master Trainer

Sumber: `input/Master - Buat Program/2026-10-29-master-trainer/`, format `input/Master Trainer Ivan Sarifudin.xlsx` (5 sheet: *Identitas Trainer*, *Pelatihan dan Seminar*, *Pengalaman Kerja*, *Pengalaman Mengajar*, *Lampiran Dokumen*).

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-44 | **Daftar Master Trainer** menampilkan: No, ID Trainer, Nama Trainer (+ telepon), Bidang Keahlian, Riwayat (jumlah baris tiap daftar), jumlah Penjualan Program yang memakai trainer, Status, Action (menu gear: **Edit**, **Lihat CV**, **Aktifkan / Nonaktifkan**). Pencarian ID, nama, bidang keahlian; filter status. | `input/Master - Buat Program/2026-10-29-master-trainer/` + `[USULAN]` (kolom) |
| FR-45 | **Form Trainer** bertab mengikuti sheet Excel. **Identitas Trainer**: ID Trainer (otomatis, format Q-01), **Nama lengkap** (wajib), Tempat lahir, Tanggal lahir (Excel: satu sel "Tempat, tanggal lahir" — dipecah `[USULAN]`), Alamat rumah, No. telepon rumah/HP; **Pendidikan formal** — SMU/sederajat, Diploma, Sarjana, Pascasarjana (teks); **Bidang keahlian** (daftar, satu per baris); Pengalaman lainnya; Tempat & Tanggal pengesahan CV; Nama pada tanda tangan. Empat tab daftar dengan baris dapat ditambah/dihapus: **Pelatihan & Seminar** (Judul, Penyelenggara/Tempat, Waktu), **Pengalaman Kerja** (Jabatan, Institusi/Perusahaan, Tahun — boleh rentang, mis. "2018–sekarang"), **Pengalaman Mengajar** (Topik Pembelajaran, Tempat, Tahun), **Lampiran Dokumen** (Dokumen / Sertifikat, Tahun, **File** unggahan PDF/JPG/PNG `[USULAN]`, Q-27). Baris kosong dibuang saat simpan; baris berisi wajib punya kolom pertama. Field wajib lain menunggu Q-26. | `input/Master - Buat Program/2026-10-29-master-trainer/`, `input/Master Trainer Ivan Sarifudin.xlsx` + `[USULAN]` (pemecahan tempat/tanggal lahir, file lampiran, validasi baris) |
| FR-46 | Trainer **tidak dapat dihapus**, hanya **Aktifkan / Nonaktifkan** (seperti FR-08). Trainer nonaktif tidak muncul di pilihan Penjualan Program; Penjualan Program yang sudah memakainya tetap menampilkan namanya. | `[USULAN]` (mengikuti FR-08, FR-21) |
| FR-47 | **Lihat CV**: tampilan baca seluruh data trainer dalam urutan Excel (identitas, pendidikan, keahlian, pengesahan, lalu empat daftar bernomor). Trainer dipilih di **Penjualan Program** — satu trainer per event (`docs/fr-nfr-penjualan-program.md` PJ-FR-23). | `input/Master - Buat Program/2026-10-29-master-trainer/`, `[K3M-1]` + `[USULAN]` (Lihat CV) |

### 4.13 Template Keterangan Quotation

Sumber: `input/Master - Buat Program/2026-10-29-keterangan-quotation-default/`. Dipakai `docs/fr-nfr-quotation.md` QTN-FR-17.

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-48 | **Template Keterangan Quotation** menyimpan butir teks per **kategori**: Syarat keikutsertaan, Termasuk, Tidak termasuk, Dokumen persyaratan, Klausul tanda tangan (Q-28). Setiap butir: Kode (otomatis, format Q-01), Kategori (wajib), **Teks** (wajib, satu butir), **Berlaku untuk** (Semua / Public / B2B, default Semua), **Default** (ya/tidak — otomatis masuk quotation baru), Status Aktif/Nonaktif. | `input/Master - Buat Program/2026-10-29-keterangan-quotation-default/` + `[USULAN]` (kategori, Berlaku untuk) |
| FR-49 | **Daftar** butir: No, Kode, Kategori, Teks (penanda "contoh" bila isi awal dari analis), Berlaku untuk, Default, Status, Action (Edit, Aktifkan / Nonaktifkan); diurutkan per kategori; filter kategori & status; pencarian kode/teks. Butir tidak dapat dihapus, hanya dinonaktifkan. | `[USULAN]` (mengikuti FR-08) |
| FR-50 | Perubahan master hanya berlaku untuk quotation **baru**: quotation menyimpan salinan teksnya sendiri (QTN-FR-17), sehingga PDF quotation lama tidak berubah. Isi awal: dari Template Penawaran (Termasuk: Materi, Sertifikat pelatihan, Pajak; dua kalimat klausul tanda tangan), dari S&K (Dokumen: formulir konfirmasi — Public, karena in-house tidak memerlukannya), dan butir **contoh** usulan analis (Q-30). | `input/Master - Buat Program/2026-10-29-keterangan-quotation-default/`, `input/Template Penawaran Labmania.pdf`, `input/SYARAT & KETENTUAN KEBIJAKAN LAYANAN PEMBELAJARAN.pdf` + `[USULAN]` |

### 4.14 Template Formulir Konfirmasi

Sumber: `input/Master - Buat Program/2026-10-29-template-formulir-konfirmasi/` (`input/Tamplate Formulir Konfirmasi Workshop.doc`, `input/Template Formulir Konfirmasi Training Sertifikasi .doc`, `input/Template Formulir Konfirmasi Resertifikasi.doc`). Dipakai `docs/fr-nfr-quotation.md` QTN-FR-16, QTN-FR-19.

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-51 | **Template Formulir Konfirmasi**: Kode (otomatis), **Nama template** (wajib), **Label kegiatan** (wajib; dipakai di catatan "subject email … kegiatan <label> dengan judul …"), **Default untuk Jenis Produk** (opsional, master Jenis Produk aktif), **Isian** (minimal 1, dari daftar: Nama Lengkap Peserta*, NIK, Jabatan, Nama Instansi, Alamat Instansi, Nomor Hp, Nomor WhatsApp, Alamat Email Aktif, Masa Pengalaman Bekerja Sesuai Skema, LSP, Tanggal Habis Sertifikat), **Kalimat pernyataan 1** (wajib; penanda {judul} {tanggal} {waktu} {durasi} {lokasi} diisi otomatis, `**teks**` = tebal), **Catatan** (satu butir per baris; penanda {email} {telp} {kegiatan} {judul}), Status Aktif/Nonaktif. Pernyataan 2 (membaca & menyetujui S&K), kop, dan kode dokumen `FR/LMI-TR-01/02/Ed.Rev-1.0` sama untuk semua template. | `input/Master - Buat Program/2026-10-29-template-formulir-konfirmasi/` + `[USULAN]` (bentuk field, penanda) |
| FR-52 | **Data awal** tiga template sesuai file: **Workshop** (default Pelatihan; 8 isian termasuk Masa Pengalaman; "akan hadir … tanggal … pukul …"), **Training & Sertifikasi** (default Sertifikasi; isian sama; label kegiatan "Training dan Sertifikasi"), **Resertifikasi** (tanpa default — dipilih manual; isian + Nomor Hp, LSP, Tanggal Habis Sertifikat, tanpa Masa Pengalaman; "akan mengikuti … Resertifikasi … (H-3 Bulan)", tanpa tanggal). Catatan ketujuh butir sama untuk ketiganya. | `input/Tamplate Formulir Konfirmasi Workshop.doc`, `input/Template Formulir Konfirmasi Training Sertifikasi .doc`, `input/Template Formulir Konfirmasi Resertifikasi.doc` + `[USULAN]` (pemetaan Jenis Produk, Q-31) |
| FR-53 | **Daftar** template: No, Kode, Nama (+ label kegiatan), Default untuk Jenis Produk, Isian (jumlah + isian khusus), Pernyataan (ringkas), Status, Action (**Edit**, **Lihat contoh** — pratinjau formulir dengan data contoh, **Aktifkan / Nonaktifkan**). Tidak dapat dihapus. Perubahan hanya berlaku untuk quotation baru (quotation menyimpan salinan pilihannya). | `input/Master - Buat Program/2026-10-29-template-formulir-konfirmasi/` + `[USULAN]` |

## 5. Kebutuhan Non-Fungsional (NFR)

Setiap NFR punya kriteria uji yang terukur. Butir `[USULAN]` belum dinyatakan klien.

| ID | Kategori | Kebutuhan | Kriteria uji | Lingkup | Sumber |
|---|---|---|---|---|---|
| NFR-01 | Audit | Setiap tambah, ubah, aktif/nonaktif, dan hapus dicatat dengan `write_log()`. Untuk ubah, payload berisi snapshot `old` (diambil sebelum update) dan `new`. Simpan Product Combine mencatat semua Nomor Product Combine yang terbentuk. Perubahan Default Cost item rate tercatat Before/After. **Jejak data & Activity Log**: semua tabel modul ini memakai kolom `created_by`, `created_date`, `updated_by`, `updated_date` (+ `deleted_by`, `deleted_date` bila dapat dihapus); daftar menampilkan kolom "Dibuat / Diubah"; semua aksi tampil di modul **Activity Log** (`docs/fr-nfr-activity-log.md` LOG-FR-01 – LOG-FR-08) `input/Master - Buat Program/2026-10-30-histori-activity-log/`. | Setelah 1 aksi ubah, Activity Log menampilkan tepat field yang berubah (Before/After); 1 simpan dengan 3 baris menghasilkan log yang menyebut 3 nomor paket | Semua UC | `[STD]` |
| NFR-02 | Integritas data | Kode setiap master, Nomor Product Combine, Kode Peserta, dan Kode Company **unik**; kombinasi 4 atribut Paket Produk juga **unik**. Keduanya dijaga di tingkat database (unique constraint/index). | Insert kode atau kombinasi duplikat langsung ke DB ditolak; 2 penyimpanan bersamaan tidak menghasilkan kode atau kombinasi kembar | FR-03, FR-13, FR-17, FR-22, FR-32 | `[WISH-1]`, `[KLR-Q-12]` + `[USULAN]` (constraint DB) |
| NFR-03 | Integritas data | Simpan Product Combine berjalan dalam **satu transaksi database**: semua baris tersimpan atau tidak satu pun. | Memaksa gagal pada baris ke-3 dari 3 → tidak ada paket baru di DB | FR-13, FR-22 | `[USULAN]` |
| NFR-04 | Integritas data | Master atribut produk, Paket Produk, dan item Master Rate tidak memiliki operasi hapus (tidak ada endpoint/tombol Delete); status diganti lewat aktif/nonaktif. | Tidak ada route hapus untuk 4 master, Paket Produk, dan Master Rate; jumlah baris tabel tidak berkurang setelah nonaktif | FR-08, FR-23, FR-35 | `[KLR-Q-05]`, `[KPG-3]` |
| NFR-05 | Validasi | Field wajib divalidasi di sisi klien **dan** server; penyimpanan ditolak dengan pesan yang menyebut field yang kosong. | Request POST tanpa field wajib (melewati JS) ditolak server dan tidak ada baris tersimpan | FR-09, FR-16, FR-19, FR-20, FR-31 | `[MD:Master Peserta]` (tanda *) + `[USULAN]` |
| NFR-06 | Validasi | Email (Peserta, Company, PIC) harus berformat email yang valid; No Telp/WA hanya angka (boleh diawali `+`), 9–15 digit. NPWP / NPWP Instansi 15 atau 16 digit angka (tanda baca diabaikan). | `abc@` ditolak; `08123456789` diterima; NPWP `01.234.567.8-901.000` diterima | FR-16, FR-31 | `[USULAN]` |
| NFR-07 | Kinerja | Halaman daftar memakai paginasi dan pencarian sisi server. | Daftar dengan 10.000 baris tampil < 2 detik (p95) | Semua daftar | `[USULAN]` |
| NFR-08 | Keamanan | Akses menu dan aksi (lihat/tambah/ubah/aktif-nonaktif/hapus) dikendalikan oleh mekanisme permission yang sudah ada di aplikasi. | Pengguna tanpa hak ubah status tidak melihat tombol Nonaktifkan dan request langsung ditolak (HTTP 403 / redirect) | Semua UC | `[USULAN]` |
| NFR-09 | Platform | Dibangun di CodeIgniter 3 (HMVC, `application/modules/`), MySQL, Bootstrap 4/5 + jQuery. Nama tabel/kolom identik dengan ERD. | Code review: modul berada di `application/modules/`, nama tabel = ERD | Semua | `[STD]` |
| NFR-10 | Usability | Semua pilihan master pada form (Produk, Bidang, Sertifikat, Satuan, COA, Provinsi, Kota, Jenis Instansi) memakai dropdown yang dapat dicari. Mockup draf 11n menerapkannya di Product Combine (tambah & edit) `input/Master - Buat Program/2026-10-29-edit-master-sertifikat-cari/`. | Mengetik 3 huruf menyaring opsi pada dropdown | FR-12, FR-16, FR-19, FR-20, FR-31 | `[USULAN]` |
| NFR-11 | Usability | Logo dan kolom search menu di sidebar **tetap terlihat (freeze)**; hanya daftar menu yang dapat digulir. | Dengan daftar menu lebih tinggi dari layar, setelah daftar menu digulir sampai bawah kolom search masih terlihat dan dapat diketik tanpa menggulir kembali; berlaku juga di layar ≤ 900 px (sidebar sebagai panel) | FR-29 | `[RMD-6]` |
| NFR-12 | Validasi / Usability | **Semua isian uang** (Default Cost, Harga jual / peserta, Target profit, nominal biaya Master Program & Penjualan Program, Harga all-in, Diskon, Biaya lain-lain Quotation) berupa angka rupiah ≥ 0 tanpa desimal dan **tampil dengan pemisah ribuan titik saat diketik** (600.000, bukan 600000); hanya angka diterima, nilai disimpan sebagai angka tanpa titik. | Ketik `1600000` → tampil `1.600.000`, tersimpan 1600000; `-1` atau `abc` ditolak | FR-19, FR-20, semua modul | `[CM]`, `input/Master - Buat Program/2026-10-30-format-rupiah-titik/` + `[USULAN]` (simpan tanpa titik) |

---

## 6. Pertanyaan Terbuka

### 6.1 Masih terbuka

| ID | Pertanyaan | Mempengaruhi |
|---|---|---|
| Q-01 | Bagaimana **format penomoran** kode untuk Jenis Produk, Jenis Sertifikat, Jenis Bidang, Nomor Product Combine, Kode Peserta, Kode Company, dan Kode Fixed/Variable Cost? (prefix, panjang digit, reset per tahun/tidak) | FR-03, FR-13, FR-17, FR-19, FR-20, FR-32 |
| Q-06 | Siapa pengguna yang mengelola **Master Peserta**, **Master Company**, **Master Rate**, dan yang **membuat Katalog**? *(masih ditanyakan Labmania)* | UC-06 – UC-09, UC-11 |
| Q-07b | Kolom **Lokasi** di daftar peserta menampilkan Provinsi, Kota, atau keduanya? Dari mana sumber data Provinsi & Kota/Kabupaten (tabel wilayah yang ada di sistem, atau diinput Labmania)? | FR-15, FR-16, FR-31 |
| Q-09 | Format **Katalog Produk** *(belum ada, menunggu Labmania)*. | FR-14 |
| Q-13 | **Pain Point** proses saat ini *(masih digali Labmania)*. | Cakupan |
| Q-14 | ~~Setelah tersimpan, apakah Paket Produk masih dapat diubah?~~ **Terjawab** `input/Master - Buat Program/2026-10-29-edit-master-sertifikat-cari/`: Paket Produk punya aksi **Edit** (FR-43). | FR-10, UC-10 |
| Q-16 | Apakah **Peserta** dan **Company** juga hanya boleh dinonaktifkan (tidak dihapus), seperti master lain dan Master Rate? (saat ini mengikuti mockup: Edit/Delete) | FR-15, FR-32 |
| Q-17 | Apakah paket yang **nonaktif** ikut dihitung dalam cek keunikan kombinasi? (mis. paket lama dinonaktifkan, lalu kombinasi yang sama dibuat lagi) | FR-22 |
| Q-18 | Apakah nama sub modul **Atribut Produk**, **Produk**, **Customer**, dan **Biaya** di bawah modul Master Data sudah sesuai? | FR-27 |
| Q-20 | Aplikasi sudah punya tabel **`customer`** (nama, alamat, provinsi, kota, telepon, email, NPWP, PIC, status aktif). Boleh Master Company memakai tabel itu (ditambah kolom Jenis Instansi), atau perlu tabel baru? *(ERD draf 4 memakai `customer`)* | FR-30 – FR-32, ERD |
| Q-25 | Master User: (a) isi pilihan **Group Akses** dan hak tiap group (contoh mockup: Manajemen, Admin, Staff); (b) data **Department, Division, Jabatan** — **menyusul dari Labmania** (`input/Master - Buat Program/2026-10-28-master-user-data-menyusul/`); mockup memakai contoh. Employee Type dan Religion — memakai master aplikasi atau diisi Labmania?; (c) **Employee ID** diinput manual (seperti form acuan) atau dibuat otomatis seperti modul `employees` (EMP…); (d) boleh memakai tabel existing `employees` / `users`? | FR-36 – FR-39, ERD |
| Q-26 | Master Trainer: field mana yang **wajib** selain Nama lengkap (mis. telepon, bidang keahlian)? Siapa pengguna yang mengelola? | FR-45, UC-38 |
| Q-27 | Lampiran Dokumen trainer perlu **file unggahan** (ijazah, sertifikat), atau cukup nama dokumen & tahun seperti Excel? (usulan: dapat diunggah, opsional) | FR-45 |
| Q-28 | Template Keterangan: apakah lima **kategori** (Syarat keikutsertaan, Termasuk, Tidak termasuk, Dokumen persyaratan, Klausul tanda tangan) sudah lengkap? Perlu kategori lain (mis. Catatan lain-lain)? | FR-48 |
| Q-29 | Butir default **dibedakan Public vs B2B** (usulan: kolom Berlaku untuk), atau sama untuk semua? | FR-48 |
| Q-30 | **Isi default awal** dari Labmania untuk tiap kategori? Mockup memakai isi template + butir bertanda "contoh". | FR-50 |
| Q-31 | Template Formulir Konfirmasi default dipilih dari **Jenis Produk** paket (usulan: Pelatihan → Workshop, Sertifikasi → Training & Sertifikasi). **Resertifikasi** dipilih manual — perlu atribut produk tersendiri (mis. Jenis Produk "Resertifikasi") agar otomatis? | FR-51, FR-52 |
| Q-32 | Isian yang tidak ada di Master Peserta (NIK, Jabatan, **Nomor Hp**, Masa Pengalaman, LSP, Tanggal Habis Sertifikat) dibiarkan kosong untuk diisi tangan (usulan), atau perlu ditambahkan ke Master Peserta agar terisi otomatis? | FR-51, FR-16 |

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

| Jenis Produk | Jenis Sertifikat |
|---|---|
| Pelatihan | Sertifikat BNSP |
| Sertifikasi | Sertifikat KAN ISO 29993 |
| | Sertifikat Labmania |
| | Sertifikat Labmania KAN ISO 29993 |
| | Sertifikat Labmania Lisensi BSN |

Varian Pelaksanaan (In House Training, Workshop, Workshop Berbasis Kompetensi, Workshop Lisensi BSN) tidak dimuat lagi — master dihapus `input/Master - Buat Program/2026-10-29-npwp-instansi-hapus-varian/`.

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
| draf 11i | 29-10-2026 | `input/Master - Buat Program/2026-10-29-quotation-diskon-aksi/`: peserta dapat ditambah dari form Quotation Personal dan langsung tersimpan di Master Peserta (FR-15). |
| draf 11j | 29-10-2026 | `input/Master - Buat Program/2026-10-29-edit-master-sertifikat-cari/`: Paket Produk dapat diedit (FR-43, FR-08, FR-10, UC-10; Q-14 terjawab); master "Jenis Sertifikasi" menjadi **Jenis Sertifikat** (FR-05 dan semua sebutan nama master; nilai Jenis Produk "Sertifikasi" tetap); dropdown Product Combine dapat dicari (FR-12, NFR-10). |
| draf 11k | 29-10-2026 | `input/Master - Buat Program/2026-10-29-npwp-instansi-hapus-varian/`: master **Varian Pelaksanaan dihapus** — Paket Produk = Jenis Produk + Jenis Sertifikat + Jenis Bidang (FR-01, FR-02, FR-07, FR-10, FR-12, FR-22, FR-26, FR-43, UC-04, lampiran 7); field **NPWP Instansi** di Master Peserta (FR-16, UC-07) dan Master Company (FR-31, label dari NPWP). ERD draf 9. |
| draf 11l | 29-10-2026 | `input/Master - Buat Program/2026-10-29-master-trainer/`: **Master Trainer** baru (sub modul Trainer) mengikuti `input/Master Trainer Ivan Sarifudin.xlsx` — FR-44 – FR-47, UC-38, bagian 4.12, Q-26, Q-27; dipilih di Penjualan Program (PJ-FR-23). ERD draf 10. |
| draf 11m | 29-10-2026 | `input/Master - Buat Program/2026-10-29-keterangan-quotation-default/`: **Template Keterangan Quotation** baru (sub modul Dokumen) — FR-48 – FR-50, UC-39, bagian 4.13, Q-28 – Q-30; ERD `ms_template_keterangan`. |
| draf 11n | 29-10-2026 | `input/Master - Buat Program/2026-10-29-template-formulir-konfirmasi/`: **Template Formulir Konfirmasi** baru (sub modul Dokumen) dengan tiga template awal Workshop, Training & Sertifikasi, Resertifikasi — FR-51 – FR-53, UC-40, bagian 4.14, Q-31, Q-32; ERD `ms_template_konfirmasi`. |
| draf 11o | 30-10-2026 | `input/Master - Buat Program/2026-10-30-format-rupiah-titik/`: **NFR-12** diperluas — semua isian uang di semua modul memakai pemisah ribuan titik saat diketik; mockup draf 11z. |
| draf 11p | 30-10-2026 | `input/Master - Buat Program/2026-10-30-histori-activity-log/`: kolom jejak (created/updated/deleted) di semua tabel dan modul **Activity Log** — rujukan ke `docs/fr-nfr-activity-log.md` di NFR audit; mockup draf 12. |
| draf 11q | 30-10-2026 | `input/Master - Buat Program/2026-10-30-activity-log-akses-retensi/`: group akses **Admin Developer** di Master User (FR-36) untuk akses Activity Log. |

---

# Bagian 2 — Modul Master Program Labmania

- **Versi:** draf 11b — 30-10-2026
- **Sumber:** `input/Master - Buat Program/2026-10-23-requirements-3-modul/` (*Master Program - Labmania.xlsx*: sheet *Requirements Program*, *Cost Master*, *Simulasi Costing*), `input/Master - Buat Program/2026-10-23-klarifikasi-program/`, `input/Master - Buat Program/2026-10-23-konfirmasi-3-modul/`, `input/Master - Buat Program/2026-10-23-arah-penjualan-program/`, `input/Master - Buat Program/2026-10-23-revisi-rate-harga-simulasi/`, `input/Master - Buat Program/2026-10-23-simulasi-bep/`, `input/Master - Buat Program/2026-10-25-jalur-public-b2b/`, `input/Master - Buat Program/2026-10-28-input-simulasi-company-b2b/`, `input/Master - Buat Program/2026-10-28-konfirmasi-simulasi-company-b2b/`, `input/Master - Buat Program/2026-10-28-jawaban-pertanyaan-program/`, `input/Master - Buat Program/2026-10-23-layout-program-public/` (layout)
- **Menggantikan:** `docs/fr-nfr-program-penjualan.md` draf 1 bagian Program/costing (backup: `input/backup/fr-nfr-program-penjualan_draf1_23-10-2026.md`)
- **Status:** DRAF — pertanyaan terbuka di bagian 7
- **Modul terkait:** hulu Master Data → `docs/fr-nfr.md` (Paket Produk, Master Rate); hilir Penjualan Program → `docs/fr-nfr-penjualan-program.md` (menarik Master Program Final)
- **Use case kanonis:** `docs/workbook.xlsx` sheet *UC Non-BPMN*
- **ERD:** `docs/diagrams/erd.drawio` halaman *ERD Master Program*
- **Mockup:** `ui-mockup/produk/index.html` → modul Master Program (layout form mengikuti `input/labmania.html`, hanya layout)

Penomoran memakai awalan **MP-** agar tidak bentrok dengan dokumen lain. Kode UC satu urutan untuk seluruh workbook.

| Penanda | Arti |
|---|---|
| `[MP-n]` | *Master Program - Labmania.xlsx* sheet *Requirements Program* bagian 1 No n |
| `[MP-WISH-n]` | idem, bagian 3 (Wishlist) No n |
| `[MP-OP-n]` | idem, bagian 5 (Open Points) No n |
| `[MP-SIPOC]` | idem, bagian 4 |
| `[MP-SIM]` | sheet *Simulasi Costing* |
| `[MD-8]` | *Master Data - Labmania.xlsx* Requirements No 8 (Master Rate) |
| `[USR-3M]` | penjelasan user 23-10-2026, `input/Master - Buat Program/2026-10-23-requirements-3-modul/` |
| `[KPG-n]` | klarifikasi chat 23-10-2026, `input/Master - Buat Program/2026-10-23-klarifikasi-program/` |
| `[OPS-1]` | opsi menu per status, `input/Master - Buat Program/2026-10-23-opsi-status-master-program/` (menggantikan `input/Master - Buat Program/2026-10-23-opsi-draft-master-program/`) |
| `[SIMX]` | tampilan simulasi, `input/Master - Buat Program/2026-10-23-simulasi-hasil-html/` (`input/simulasi_hasil.html`) |
| `[BEP-1]` | permintaan data BEP 23-10-2026, `input/Master - Buat Program/2026-10-23-simulasi-bep/` |
| `[JLR-n]` | jalur Public vs B2B 25-10-2026, `input/Master - Buat Program/2026-10-25-jalur-public-b2b/` — pesan n |
| `[SPA-2]` | laporan Rencana vs Aktual, `input/Master - Buat Program/2026-10-25-surat-penawaran-rencana-aktual/` butir 2 |
| `[REV-n]` | revisi 23-10-2026, `input/Master - Buat Program/2026-10-23-revisi-rate-harga-simulasi/` |
| `[ISC-1]` | input simulasi ganda, `input/Master - Buat Program/2026-10-28-input-simulasi-company-b2b/` butir 1 |
| `[KSC-1]` | konfirmasi input simulasi ganda, `input/Master - Buat Program/2026-10-28-konfirmasi-simulasi-company-b2b/` |
| `[JPP-n]` | jawaban 15 pertanyaan, `input/Master - Buat Program/2026-10-28-jawaban-pertanyaan-program/` — butir n |
| `[BTS-1]` | hanya biaya terpakai yang tampil, `input/Master - Buat Program/2026-10-28-biaya-terpakai-saja/` (untuk Fixed Cost digantikan `[FCO-1]`) |
| `[FCO-1]` | Fixed Cost langsung muncul semua, hapus dengan ikon ×, `input/Master - Buat Program/2026-10-30-fixed-cost-otomatis/` |
| `[K3M-n]` | konfirmasi chat 23-10-2026, `input/Master - Buat Program/2026-10-23-konfirmasi-3-modul/` |
| `[STD]` | standar proyek di `AGENTS.md` |
| `[USULAN]` | usulan analis, **belum** dinyatakan klien |

---

## 1. Ringkasan Kebutuhan

**Master Program** = program siap jual yang dibentuk dari **Product Combine** (Paket Produk) +
**costing** (Fixed Cost & Variable Cost) `[MP-1]`. Program dibuat **secara umum**: tidak ada
pemisahan Public/B2B, tidak ada customer, tidak ada tanggal dan lokasi acara `[MP-1]`, `[MP-4]`,
`[USR-3M]`. Semua itu diisi di Penjualan Program, yang **menarik** Master Program
berstatus Final dan boleh menyesuaikan angkanya tanpa mengubah master `[MP-3]`, `[MP-10]`.

```
Master Data ──► Master Program (baseline: durasi, target peserta, harga jual standar, costing) ──Final──► Penjualan Program (Public / B2B)
  Paket Produk        Draft → Final, atau Batal                                                      salin & sesuaikan per penjualan
  Master Rate
```

SIPOC `[MP-SIPOC]`:

| Supplier | Input | Process | Output | Customer |
|---|---|---|---|---|
| Master Data (Product Combine) | Produk (hasil Product Combine) | Master Program (costing) | Target peserta, target profit, HPP, BEP peserta, BEP harga jual, total cost (Fixed & Variable) | Penjualan Program |
| Master Data (Master Rate) | Item Fixed Cost, item Variable Cost, default cost | | | |

Menu: modul **Penjualan Program**, sub modul **Master Program** (sub modul pertama), menu **Daftar Master Program** `input/Master - Buat Program/2026-10-28-gabung-menu-penjualan-program/`.

### 1.1 Cakupan

| Termasuk | Tidak termasuk |
|---|---|
| Menyusun Master Program dari Paket Produk aktif + costing | Public/B2B, customer, tanggal, lokasi, diskon → Penjualan Program |
| Simulasi costing (HPP, profit, BEP) atas angka baseline | Peserta fix & realisasi → Quotation (ditunda) `[MP-18]` |
| Status Draft → Final, atau Batal | Approval — saat ini tidak ada `[MP-11]` |

### 1.2 Istilah

| Istilah | Arti |
|---|---|
| Master Program | Satu Paket Produk + costing + angka baseline, dengan Nomor Master Program dan status. |
| Baseline | Angka acuan (durasi, target peserta, harga jual standar, costing) yang bisa diubah per Penjualan Program tanpa mengubah master `[MP-3]`. |
| Biaya Master Program | Satu baris item biaya: dari Master Rate (Default Cost sebagai nilai awal, dapat diedit) atau biaya manual di luar master. |

---

## 2. Aktor

| Aktor | Peran | Sumber |
|---|---|---|
| *Belum ditetapkan* | Menyusun, memfinalkan, dan membatalkan Master Program | Q-MP-01 |
| Penjualan Program (modul) | Menarik Master Program Final ke Penjualan Program | `[MP-7]`, `[MP-SIPOC]` |

---

## 3. Tabel Use Case

| Kode | Nama Use Case | User | Modul | Sub Modul | Input | Deskripsi | Output | Entity/Objek Terkait | FR |
|---|---|---|---|---|---|---|---|---|---|
| UC-19 | Menyusun Master Program | Manajemen | Master Program | Program | Paket Produk aktif, Durasi (hari), Target peserta, Harga jual standar / peserta, Target profit (opsional), nominal item Fixed & Variable Cost, biaya manual | Aktor memilih Paket Produk dan mengisi angka baseline. Sistem menampilkan semua item Fixed Cost aktif dengan Default Cost; aktor menghapus (×) item yang tidak dipakai, menyesuaikan nominal bila perlu, menambah item Variable Cost satu per satu, dan bila perlu biaya manual di luar master. Simulasi (Total Fixed, Total Variable, HPP, Profit, Harga BEP / peserta, Jumlah peserta BEP) dihitung ulang setiap angka berubah. Aktor menyimpan sebagai Draft dan dapat mengubahnya lagi selama Draft. | Master Program berstatus Draft dengan Nomor Master Program, costing, dan simulasi | Master Program, Biaya Master Program, Paket Produk, Rate Fixed Cost, Rate Variable Cost, Satuan | MP-FR-01 – MP-FR-10 |
| UC-20 | Memfinalkan Master Program | Manajemen | Master Program | Program | Master Program berstatus Draft | Aktor menekan "Finalkan program". Sistem menampilkan popup ringkasan (durasi, target peserta, harga jual, HPP, profit, BEP) dan peringatan bila profit minus atau BEP di atas target. Setelah dikonfirmasi status menjadi Final tanpa berpindah halaman; program terkunci dan muncul sebagai pilihan di Penjualan Program. | Master Program Final, terkunci, tersedia di Penjualan Program | Master Program | MP-FR-11 – MP-FR-13, MP-FR-15, MP-FR-16 |
| UC-21 | Membatalkan Master Program | Manajemen | Master Program | Program | Master Program berstatus Draft | Aktor memilih Batalkan dan mengonfirmasi. Status menjadi Batal; program tidak muncul lagi di pilihan Penjualan Program. Penjualan Program yang sudah terbentuk dari program ini tidak terpengaruh. | Master Program berstatus Batal | Master Program | MP-FR-11, MP-FR-14, MP-FR-15 |

> Semua UC belum lolos uji admission no. 1 (aktor bernama) — Q-MP-01.

---

## 4. Kebutuhan Fungsional (FR)

### 4.1 Data Master Program

| ID | Kebutuhan | Sumber |
|---|---|---|
| MP-FR-01 | **Daftar Master Program** menampilkan: No, Nomor, Judul (dari Paket Produk), Durasi, Target peserta, Harga jual standar / peserta, HPP, Status, Action (menu gear sesuai status: **Draft → Edit, Finalkan, Batalkan**; **Final → Lihat**; **Batal → Lihat**). Dapat disaring per status. | `[MP-1]`, `[OPS-1]` + `[USULAN]` (kolom daftar) |
| MP-FR-02 | Master Program berisi: (1) **Product Combine** — satu Paket Produk berstatus Aktif dari Master Data; (2) **Durasi (hari)**; (3) **Target peserta** (baseline) — terisi otomatis **10** saat program baru dibuat, dapat diedit; (4) **Harga jual standar / peserta** (baseline); (5) **Target profit** (opsional); (6) item costing Fixed Cost & Variable Cost. **Tidak ada** pilihan Public/B2B, customer, tanggal, atau lokasi acara. | `[MP-1]`, `[MP-3]`, `[MP-4]`, `[USR-3M]`, `[JLR-2]` (default 10) |
| MP-FR-03 | Master Program mendapat **Nomor Master Program** otomatis saat pertama disimpan. Format sementara `MP-YYMM-NNN`, nomor urut ulang tiap bulan (mis. MP-2610-001) `[JPP-10]`; format final Q-MP-02. Satu Paket Produk boleh dipakai lebih dari satu Master Program. | `[USULAN]` |
| MP-FR-04 | Semua atribut hanya **dipilih** dari Master Data (Paket Produk, Master Rate), tidak diinput ulang. | `[MP-2]` |

### 4.2 Costing

| ID | Kebutuhan | Sumber |
|---|---|---|
| MP-FR-05 | Satu model costing untuk semua program: **Fixed Cost** (tidak bergantung jumlah peserta) dan **Variable Cost** (bergantung jumlah peserta). Berlaku sama untuk penjualan Public maupun B2B; Mandays tidak dipakai. | `[MP-12]`, `[JLR-1]` |
| MP-FR-06 | **Fixed Cost**: saat membuat Master Program baru, **semua item Fixed Cost aktif** Master Rate langsung tampil dengan Default Cost; item yang **tidak dipakai dihapus dengan ikon ×** per baris selama Draft `[FCO-1]`. Item yang terhapus dapat ditambah lagi lewat "+ Tambah" `[USULAN]`. Penjualan Program menyalin item yang tersisa dan masih dapat mengedit/menghapus/menambah (PJ-FR-05) `[FCO-1]`. Dikelompokkan per satuan: **Event** (sekali per acara) dan **Day** (× durasi). Biaya trainer yang di sumber "per jam" (Mandays Rate Charge, Honor Trainer) dihitung **sekali per event**; 1 event = 1 trainer. | `[MP-13]`, `[MP-16]`, `[KPG-1]`, `[K3M-1]`, `[FCO-1]` + `[USULAN]` (tambah lagi) |
| MP-FR-07 | **Variable Cost** ditambah satu per satu (pilih dari Master Rate, lalu "+ Tambah") dan dapat dihapus (ikon ×) selama Draft, dikelompokkan per satuan: **Peserta/Event** dan **Peserta/Day** (× durasi). Item yang sama tidak dapat ditambahkan dua kali. Berlaku untuk Fixed & Variable Cost: item bernilai **0 dianggap tidak dipakai dan dibuang saat disimpan**; kelompok satuan tanpa item tidak menampilkan tabel kosong, dan tidak ditampilkan sama sekali saat terkunci `[BTS-1]`. | `[MP-14]`, `[MP-16]`, `[BTS-1]` + `[USULAN]` (tanpa duplikat, buang item 0) |
| MP-FR-08 | Nominal item terisi **Default Cost** dari Master Rate dan **selalu dapat diedit** (tidak ada mode Fix / Input manual). Nama item, satuan, dan nominal disimpan sebagai salinan di Master Program. | `[MP-15]`, `[MD-8]`, `[REV-1]` + `[USULAN]` (salinan) |
| MP-FR-09 | Ada opsi **biaya manual di luar master** (tombol "+ Biaya lain") pada Fixed Cost dan Variable Cost: Nama biaya, Nominal; satuannya mengikuti kelompok tempat biaya ditambahkan. Biaya manual tidak menambah item ke Master Rate. | `[MP-16]` + `[USULAN]` (penempatan per kelompok) |
| MP-FR-10 | **Simulasi costing** dihitung ulang setiap angka berubah dan menampilkan: Total Fixed Cost, Total Variable Cost, HPP, Profit, Harga BEP / peserta, Jumlah peserta BEP, Target Profit, Harga jual (rumus bagian 8). Tampilan mengikuti `input/simulasi_hasil.html` `[SIMX]`: (1) **Titik impas (BEP)** — kotak *BEP jumlah peserta* = Fixed Cost ÷ (harga per peserta − biaya variable per peserta), dibulatkan ke atas, dan *BEP harga jual* = HPP ÷ jumlah peserta, masing-masing dengan kalimat penjelas (aman/kurang terhadap rencana); (2) **Hasil rencana simulasi** — kotak Pendapatan, Biaya (HPP), Profit (untung/rugi, margin % dari pendapatan), lalu baris **target profit** (tercapai / kurang) beserta **minimal peserta untuk capai target** = ⌈(Fixed Cost + target profit) ÷ (harga − biaya variable per peserta)⌉; (3) **Posisi di skala jumlah peserta** — batang zona rugi / untung belum capai target / target tercapai dengan penanda BEP, Target profit, Rencana; (4) **Rincian perhitungan** yang dapat dibuka/tutup, ditulis **sederhana dan mudah dipahami** `[JLR-3]`: langkah hitung bernomor dalam bahasa sehari-hari (biaya tetap acara → biaya per peserta → total biaya → pendapatan → untung/rugi → titik impas), masing-masing satu kalimat + angka, lalu tabel profit **pendek** (± 5 baris: BEP, rencana, dan beberapa jumlah peserta di sekitarnya). **Input simulasi** (durasi, target peserta, harga jual standar / peserta, target profit) tampil di bagian 1 *Informasi Program* **dan** di atas hasil di bagian 4 *Simulasi Hasil*, terikat ke **satu nilai**: cukup diisi sekali di salah satu tempat, tempat lain ikut berubah `[ISC-1]`, `[KSC-1]`. Simulasi yang sama dipakai di Penjualan Program. Peringatan (tidak menghalangi simpan Draft): profit minus; BEP > target peserta; profit < target profit; Hotel Trainer \*\* dan \*\*\* keduanya terisi (hanya peringatan `[K3M-6]`). | `[MP-17]`, `[MP-WISH-1]`, `[MP-WISH-2]`, `[MP-SIM]`, `[MP-9]`, `[MP-OP-2]`, `[REV-4]`, `[BEP-1]`, `[SIMX]`, `[JLR-3]`, `[ISC-1]`, `[KSC-1]` |

### 4.3 Status

| ID | Kebutuhan | Sumber |
|---|---|---|
| MP-FR-11 | Status: **Draft → Final**, atau **Batal** (transisi di bagian 6). Program baru tersimpan Draft; selama Draft semua isian dapat diubah. | `[MP-5]`, `[MP-6]` |
| MP-FR-12 | Aksi **"Finalkan program"** (status Draft) memunculkan popup ringkasan: durasi, target peserta, harga jual, HPP, profit, BEP, beserta peringatan bila profit minus atau BEP di atas target. Setelah dikonfirmasi, status menjadi **Final** tanpa berpindah halaman. | `[MP-9]` |
| MP-FR-13 | Master Program **Final**: angka menjadi baseline resmi, **terkunci** (hanya bisa dilihat), dan **muncul sebagai pilihan** di Penjualan Program. | `[MP-7]` |
| MP-FR-14 | Hanya Master Program **Draft** yang dapat dibatalkan `[JPP-8]`. Master Program **Batal**: tidak dipakai lagi dan **tidak muncul** di pilihan Penjualan Program. Penjualan Program yang sudah terbentuk dari program ini **tidak terpengaruh**. | `[MP-8]`, `[JPP-8]` |
| MP-FR-15 | Master Program Final **tidak bisa diubah, dibatalkan, maupun disalin** `[JPP-8]`, `[JPP-9]`. Bila perlu perubahan master, buat Master Program baru. Penyesuaian harga/costing untuk penjualan dilakukan di Penjualan Program. | `[MP-10]`, `[JPP-8]`, `[JPP-9]` |
| MP-FR-16 | Tidak ada approval sebelum Final. | `[MP-11]` |
| MP-FR-17 | Master Program hanya menyimpan angka **baseline** yang disalin ke Penjualan Program. Perbandingan rencana dan realisasi dilakukan di **laporan Rencana vs Aktual** Penjualan Program (PJ-FR-21): rencana = Penjualan Program Final, aktual = peserta fix di Quotation / SO. | `[MP-18]`, `[REV-3]`, `[SPA-2]` |

---

## 5. Kebutuhan Non-Fungsional (NFR)

| ID | Kategori | Kebutuhan | Kriteria uji | Lingkup | Sumber |
|---|---|---|---|---|---|
| MP-NFR-01 | Audit | Simpan, ubah costing, finalkan, dan batal dicatat `write_log()` modul "Master Program"; ubah costing mencatat header `old`/`new` dan `detail_changes` per item (`Item [nama] - Harga`). **Jejak data & Activity Log**: semua tabel modul ini memakai kolom `created_by`, `created_date`, `updated_by`, `updated_date` (+ `deleted_by`, `deleted_date` bila dapat dihapus); daftar menampilkan kolom "Dibuat / Diubah"; semua aksi tampil di modul **Activity Log** (`docs/fr-nfr-activity-log.md` LOG-FR-01 – LOG-FR-08) `input/Master - Buat Program/2026-10-30-histori-activity-log/`. | Mengubah 1 harga + durasi → 1 log dengan 2 perubahan | UC-19 – UC-21 | `[STD]` |
| MP-NFR-02 | Integritas data | Master Program Final/Batal tidak dapat diubah di server. | Request ubah costing ke program Final ditolak (403/422), data tetap | MP-FR-13 – MP-FR-15 | `[MP-7]`, `[MP-10]` |
| MP-NFR-03 | Integritas data | Simpan header + semua baris biaya dalam satu transaksi; konfirmasi Final memeriksa status di dalam transaksi. | Gagal di baris terakhir → tidak ada program baru; dua klik Final bersamaan → satu log | UC-19, UC-20 | `[USULAN]` |
| MP-NFR-04 | Akurasi | Uang DECIMAL(15,2), tampil rupiah tanpa desimal; isian uang (harga jual, target profit, nominal biaya) memakai pemisah ribuan titik saat diketik (`fr-nfr.md` NFR-12). Jumlah peserta BEP dibulatkan ke atas; bila harga jual ≤ biaya variable / peserta, BEP tampil "tidak tercapai". | Skenario `[MP-SIM]` no 1 sama dengan Excel setelah koreksi data awal; BEP 10,2 tampil 11 | MP-FR-10 | `[MP-WISH-2]` + `[USULAN]` |
| MP-NFR-05 | Validasi | Target peserta default 10. Durasi dan target peserta bilangan bulat > 0; nominal ≥ 0; nama dan nominal biaya manual wajib diisi sebelum simpan. | Durasi 0 ditolak; biaya manual tanpa nama ditolak | MP-FR-02, MP-FR-09 | `[USULAN]` |
| MP-NFR-06 | Kinerja | Simulasi dihitung di klien saat mengetik, dihitung ulang di server saat simpan/finalkan. | Hasil tampil < 200 ms; nilai tersimpan = hitungan server | MP-FR-10 | `[USULAN]` |
| MP-NFR-07 | Keamanan | Menu dan aksi (buat, ubah, finalkan, batal) mengikuti permission aplikasi. | Pengguna tanpa hak finalkan tidak melihat tombol dan request ditolak | Semua UC | `[USULAN]` |
| MP-NFR-08 | Platform | CI3 HMVC, MySQL, Bootstrap + jQuery; nama tabel/kolom = ERD. | Code review | Semua | `[STD]` |

---

## 6. Status Master Program

| Dari | Aksi | Ke | Syarat / efek | Sumber |
|---|---|---|---|---|
| — | Simpan program baru | Draft | Nomor dibuat | `[MP-5]` |
| Draft | Finalkan program (konfirmasi popup) | Final | Terkunci; muncul di pilihan Penjualan Program | `[MP-7]`, `[MP-9]` |
| Draft | Batalkan | Batal | — | `[MP-8]` |

Final dan Batal tidak kembali ke Draft. Batal adalah status akhir. Final tidak dapat dibatalkan `[JPP-8]`.

---

## 7. Pertanyaan Terbuka

| ID | Pertanyaan | Mempengaruhi |
|---|---|---|
| Q-MP-01 | Aktor saat ini **Manajemen** `[JPP-11]`. Pengguna lain (user) — menyusul. | UC-19 – UC-21 |
| Q-MP-02 | Format **final** Nomor Master Program (sementara `MP-YYMM-NNN`, MP-FR-03) `[JPP-10]`. | MP-FR-03 |

**Sudah terjawab (28-10-2026, `[JPP-n]`)**: nama menu sesuai (Q-MP-04); aktor Manajemen (Q-MP-01, sebagian); Final tidak dapat dibatalkan (Q-MP-08) maupun disalin (Q-MP-06).

**Sudah terjawab dari Excel ini** (sebelumnya di `fr-nfr-program-penjualan.md`): biaya manual di luar master boleh (Q-PP-10 → MP-FR-09); program tidak lagi punya status Deal/Selesai (Q-PP-05, Q-PP-06 gugur); `[MP-OP-3]` item duplikat Transport dan `[MP-OP-4]` rate B2B vs Public sudah dijawab di `fr-nfr.md` bagian 6.2. *(23-10-2026, `input/Master - Buat Program/2026-10-23-konfirmasi-3-modul/`)* Q-MP-03 — `[MP-OP-1]` Jam vs Hari: biaya trainer per event, 1 event = 1 trainer; *Simulasi Costing* di Excel yang mengalikan dengan hari tidak dipakai. Q-MP-05 — Hotel \*\*/\*\*\*: cukup peringatan.

---

## 8. Lampiran — Rumus Simulasi `[MP-SIM]` `[MP-WISH-1]` `[MP-WISH-2]`

| Hasil | Rumus |
|---|---|
| Total Fixed Cost | Σ nominal Fixed/**Event** + (Σ nominal Fixed/**Day** × Durasi) |
| Biaya Variable / Peserta | Σ nominal **Peserta/Event** + (Σ nominal **Peserta/Day** × Durasi) |
| Total Variable Cost | Biaya Variable / Peserta × Target Peserta |
| HPP | Total Fixed Cost + Total Variable Cost |
| Pendapatan | Harga Jual / Peserta × Target Peserta |
| Profit | Pendapatan − (Total Fixed Cost + Total Variable Cost) |
| Harga BEP / Peserta | HPP ÷ Target Peserta |
| Jumlah Peserta BEP | Total Fixed Cost ÷ (Harga Jual / Peserta − Biaya Variable / Peserta), dibulatkan ke atas |

Catatan: *Cost Master* D39 di Excel baru sudah `=D11+D19` (tidak lagi dobel hitung), tetapi D19 masih memasukkan Mandays Rate Charge & Honor Trainer ke kelompok per hari. Spec menghitung keduanya per event `[K3M-1]`, sehingga HPP mockup berbeda dari Excel untuk durasi > 1 hari.

---

## 9. Riwayat Perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| draf 1 | 23-10-2026 | Penyusunan awal dari *Master Program - Labmania.xlsx*. Menggantikan bagian Program Public/B2B di `fr-nfr-program-penjualan.md` (UC-12 – UC-18 dihapus): program kini generik tanpa Public/B2B, customer, tanggal, lokasi; status Draft → Final / Batal; biaya manual boleh. |
| draf 2 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-konfirmasi-3-modul/`: biaya trainer "per jam" dihitung per event (MP-FR-06, Q-MP-03 terjawab); Hotel \*\*/\*\*\* cukup peringatan (MP-FR-10, Q-MP-05 terjawab). |
| draf 3 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-arah-penjualan-program/`: hilir Master Program adalah **Penjualan Program** (bukan quotation); MP-FR-17 — angka Master Program = As Is, Penjualan Program mengikuti perubahan; realisasi peserta fix pindah ke modul Quotation (ditunda). |
| draf 4 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-revisi-rate-harga-simulasi/`: tanpa mode harga — nominal item selalu dapat diedit (MP-FR-08, MP-NFR-05); simulasi ditata ulang agar mudah dibaca (MP-FR-10); As Is dihapus (MP-FR-17, Q-MP-07 baru). |
| draf 5 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-simulasi-bep/`: simulasi ditambah **Data BEP** — perhitungan BEP langkah demi langkah dan tabel profit menurut jumlah peserta (MP-FR-10). |
| draf 6 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-simulasi-hasil-html/`: tampilan simulasi mengikuti `input/simulasi_hasil.html` — kotak BEP, hasil rencana, skala peserta, rincian yang dapat dibuka/tutup; tambah margin % dan minimal peserta untuk target profit (MP-FR-10). |
| draf 6a | 23-10-2026 | Istilah "sumbangan per peserta" dihapus atas permintaan user (chat 23-10-2026); BEP peserta ditulis langsung sebagai Fixed Cost ÷ (harga − biaya variable per peserta). |
| draf 7 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-opsi-draft-master-program/`: menu gear Master Program Draft hanya Finalkan dan Batalkan; form dibuka lewat tautan nomor/judul (MP-FR-01). |
| draf 8 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-opsi-status-master-program/`: menu gear Draft = Edit, Finalkan, Batalkan; Final = Lihat; Batal = Lihat; tautan nomor/judul dihapus (MP-FR-01); Q-MP-08 baru. |
| draf 9 | 25-10-2026 | `input/Master - Buat Program/2026-10-25-jalur-public-b2b/`: target peserta default **10**, dapat diedit (MP-FR-02, MP-NFR-05); rincian simulasi disederhanakan — langkah hitung bahasa sehari-hari dan tabel profit pendek (MP-FR-10); Mandays tidak dipakai (MP-FR-05). |
| draf 9a | 25-10-2026 | `input/Master - Buat Program/2026-10-25-surat-penawaran-rencana-aktual/`: As Is vs Aktual menjadi laporan **Rencana vs Aktual** di Penjualan Program (MP-FR-17); Q-MP-07 terjawab. |
| draf 9b | 28-10-2026 | `input/Master - Buat Program/2026-10-28-input-simulasi-company-b2b/`: input simulasi tampil di bagian 1 dan bagian 4 *Simulasi Hasil*, tersinkron (MP-FR-10). |
| draf 9c | 28-10-2026 | `input/Master - Buat Program/2026-10-28-konfirmasi-simulasi-company-b2b/`: input simulasi ganda dikonfirmasi klien (MP-FR-10). |
| draf 10 | 28-10-2026 | `input/Master - Buat Program/2026-10-28-jawaban-pertanyaan-program/`: aktor Manajemen (UC-19 – UC-21); format nomor sementara MP-YYMM-NNN (MP-FR-03); Final tidak dapat dibatalkan maupun disalin (MP-FR-14, MP-FR-15, UC-21, bagian 6). Q-MP-04, -06, -08 terjawab; Q-MP-01, -02 ditulis ulang. |
| draf 10a | 28-10-2026 | `input/Master - Buat Program/2026-10-28-gabung-menu-penjualan-program/`: menu Master Program pindah ke modul Penjualan Program sebagai sub modul pertama (bagian 1). |
| draf 10b | 28-10-2026 | `input/Master - Buat Program/2026-10-28-biaya-terpakai-saja/`: Fixed Cost tidak lagi menampilkan semua item Master Rate — hanya item terpakai, ditambah satu per satu seperti Variable Cost; item bernilai 0 dibuang saat disimpan (MP-FR-06, MP-FR-07). |
| draf 11 | 30-10-2026 | `input/Master - Buat Program/2026-10-30-fixed-cost-otomatis/`: **Fixed Cost langsung muncul semua** (item aktif Master Rate, Default Cost) saat membuat Master Program; yang tidak dipakai dihapus dengan **ikon ×** (MP-FR-06); hapus baris costing memakai ikon × (MP-FR-07). Variable Cost tetap dipilih satu per satu; item 0 tetap dibuang saat disimpan. Mockup draf 11y. |
| draf 11a | 30-10-2026 | `input/Master - Buat Program/2026-10-30-format-rupiah-titik/`: isian uang dengan pemisah ribuan titik (MP-NFR-04). |
| draf 11b | 30-10-2026 | `input/Master - Buat Program/2026-10-30-histori-activity-log/`: kolom jejak (created/updated/deleted) di semua tabel dan modul **Activity Log** — rujukan ke `docs/fr-nfr-activity-log.md` di NFR audit; mockup draf 12. |

---

# Bagian 3 — Modul Penjualan Program Labmania

- **Versi:** draf 12b — 30-10-2026
- **Sumber:** `input/Master - Buat Program/2026-10-23-requirements-3-modul/` (*Penjualan Program - Labmania.xlsx*: sheet *Requirements Quotation*, *Field Quotation*), `input/Master - Buat Program/2026-10-23-konfirmasi-3-modul/`, `input/Master - Buat Program/2026-10-23-arah-penjualan-program/`, `input/Master - Buat Program/2026-10-23-revisi-rate-harga-simulasi/`, `input/Master - Buat Program/2026-10-23-simulasi-bep/`, `input/Master - Buat Program/2026-10-23-tanpa-diskon/`, `input/Master - Buat Program/2026-10-25-jalur-public-b2b/`, `input/Master - Buat Program/2026-10-25-quotation-so-persiapan-invoice/`, `input/Master - Buat Program/2026-10-25-surat-penawaran-rencana-aktual/`, `input/Master - Buat Program/2026-10-28-input-simulasi-company-b2b/`, `input/Master - Buat Program/2026-10-28-konfirmasi-simulasi-company-b2b/`, `input/Master - Buat Program/2026-10-28-jawaban-pertanyaan-program/`, `input/Master - Buat Program/2026-10-28-quotation-company-personal/`, `input/Master - Buat Program/2026-10-23-layout-program-public/` (layout), `input/Master - Buat Program/2026-10-29-master-trainer/`, `input/Master - Buat Program/2026-10-30-b2b-quotation-allin-min10/`
- **Status:** DRAF — pertanyaan terbuka di bagian 7
- **Modul terkait:** hulu Master Program → `docs/fr-nfr-master-program.md`; Master Data → `docs/fr-nfr.md` (Master Rate); hilir **Quotation** untuk Public **dan** B2B → `docs/fr-nfr-quotation.md` `[QCP-1]`; **Sales Order tidak ada** `[B2B-3]`
- **Use case kanonis:** `docs/workbook.xlsx` sheet *UC Non-BPMN*
- **ERD:** `docs/diagrams/erd.drawio` halaman *ERD Penjualan Program*
- **Mockup:** `ui-mockup/produk/index.html` → modul Penjualan Program (layout form mengikuti `input/labmania.html`)

Penomoran memakai awalan **PJ-**. Kode UC satu urutan untuk seluruh workbook; kode yang dihapus tidak dipakai ulang.

| Penanda | Arti |
|---|---|
| `[QT-n]` | *Penjualan Program - Labmania.xlsx* sheet *Requirements Quotation* bagian 1 No n |
| `[QT-FIELD]` | sheet *Field Quotation* |
| `[USR-3M]` | penjelasan user, `input/Master - Buat Program/2026-10-23-requirements-3-modul/` |
| `[K3M-n]` | konfirmasi, `input/Master - Buat Program/2026-10-23-konfirmasi-3-modul/` — butir n |
| `[ARH-n]` | arah modul, `input/Master - Buat Program/2026-10-23-arah-penjualan-program/` — butir n |
| `[REV-n]` | revisi, `input/Master - Buat Program/2026-10-23-revisi-rate-harga-simulasi/` — butir n |
| `[SIMX]` | tampilan simulasi, `input/Master - Buat Program/2026-10-23-simulasi-hasil-html/` (`input/simulasi_hasil.html`) |
| `[TDK-1]` | Penjualan Program tanpa diskon, `input/Master - Buat Program/2026-10-23-tanpa-diskon/` |
| `[BEP-1]` | permintaan data BEP, `input/Master - Buat Program/2026-10-23-simulasi-bep/` |
| `[JLR-n]` | jalur Public vs B2B, `input/Master - Buat Program/2026-10-25-jalur-public-b2b/` — pesan n |
| `[INV-1]` | Quotation & SO = persiapan invoice, `input/Master - Buat Program/2026-10-25-quotation-so-persiapan-invoice/` |
| `[SPA-n]` | surat penawaran & Rencana vs Aktual, `input/Master - Buat Program/2026-10-25-surat-penawaran-rencana-aktual/` — butir n |
| `[ISC-n]` | input simulasi ganda & Company B2B, `input/Master - Buat Program/2026-10-28-input-simulasi-company-b2b/` — butir n |
| `[KSC-n]` | konfirmasi (1) input simulasi ganda, (2) Company wajib B2B, `input/Master - Buat Program/2026-10-28-konfirmasi-simulasi-company-b2b/` |
| `[JPP-n]` | jawaban 15 pertanyaan, `input/Master - Buat Program/2026-10-28-jawaban-pertanyaan-program/` — butir n |
| `[B2B-n]` | `input/Master - Buat Program/2026-10-30-b2b-quotation-allin-min10/` — jawaban n: (1) B2B = company, harga per peserta / all-in di Quotation, (2) minimal 10 peserta dari Penjualan Program sampai Quotation, (3) Sales Order tidak ada, (4) B2B tanpa Formulir Konfirmasi, (5) B2B satu quotation |
| `[BTS-1]` | hanya biaya terpakai yang tampil, `input/Master - Buat Program/2026-10-28-biaya-terpakai-saja/` |
| `[QCP-1]` | Public & B2B masuk Quotation (ke Company atau Personal), `input/Master - Buat Program/2026-10-28-quotation-company-personal/` |
| `[STD]` | standar proyek di `AGENTS.md` |
| `[USULAN]` | usulan analis, **belum** dinyatakan klien |

---

## 1. Ringkasan Kebutuhan

**Penjualan Program** = rencana **satu acara** dari satu Master Program. Pengguna **menarik Master
Program berstatus Final**, memilih jenis **Public** atau **B2B**, mengisi tanggal dan lokasi acara,
lalu **semua angka boleh disesuaikan** tanpa mengubah master `[QT-1]`, `[QT-5]`, `[USR-3M]`.

**Penjualan Program adalah tahap menuju finalisasi** `[INV-1]`: peserta, harga, dan costing dirancang
dan disesuaikan di sini sampai Final. **Quotation adalah persiapan invoice**; saat
masuk ke sana, jumlah peserta **sudah fix** dan tidak ada lagi target atau simulasi `[INV-1]`.
Penjualan Program **Final** dapat **dicetak sebagai surat penawaran**; Final adalah tanda verifikasi di
sistem dan pemicu ke Quotation `[SPA-1]`. Selisih peserta rencana vs fix **diterima** dan dipantau lewat
**laporan Rencana vs Aktual** `[SPA-2]`.

> **Pembaruan 30-10-2026** `[B2B-3]`: **Sales Order tidak ada.** Public **dan** B2B Final sama-sama masuk ke **Quotation**
> (`docs/fr-nfr-quotation.md`) `[QCP-1]`: B2B ke Company penjualannya (satu quotation, minimal 10 peserta, harga per peserta
> atau all-in), Public ke Company atau Personal (berkali-kali).

**Form dan simulasi sama untuk Public dan B2B** `[JLR-1]`. Jenis hanya menentukan **jalur setelah
Final** `[JLR-3]`:

```
Master Program (Final) ──tarik──► Penjualan Program (Draft → Final) ──┬─► Public: Quotation — 1 : banyak ─┐
                                   menuju finalisasi                    │                                  ├─► Invoice (menyusul)
                                   form & simulasi SAMA                 │   (peserta fix, persiapan invoice)│
                                   jenis = penanda jalur                └─► B2B:    Quotation — 1 : 1     ─┘
```

| | **Public → Quotation** | **B2B → Quotation** |
|---|---|---|
| Relasi ke Penjualan Program | Satu Penjualan Program → **banyak** quotation `[JLR-2]` | Satu Penjualan Program → **satu** quotation `[B2B-5]` |
| Customer | Company atau Personal (calon peserta) `[QDA-2]` | **Satu Company** dari Master Company, **dipilih di Penjualan Program** dan diteruskan ke Quotation `[ISC-2]`, `[B2B-1]` |
| Jumlah peserta | Bebas | **Minimal 10** — target Penjualan Program dan peserta quotation `[B2B-2]` |
| Harga | Per peserta; diskon Rupiah per peserta di Quotation `[QDA-3]` | Di Quotation: **per peserta** atau **all-in 1 company** `[B2B-1]` |

Yang **tidak** ada di Penjualan Program: skema harga all-in / harga paket — ada di **Quotation** B2B `[B2B-1]`.
Yang membedakan form B2B dari Public: **Company** (PJ-FR-22) `[ISC-2]` dan **target peserta minimal 10** (PJ-FR-25) `[B2B-2]`. Mandays tidak dipakai `[JLR-1]`.

Menu: modul **Penjualan Program**, sub modul **Penjualan Program** (sub modul kedua, setelah Master Program;
menu: Daftar Penjualan Program) `input/Master - Buat Program/2026-10-28-gabung-menu-penjualan-program/`. Laporan **Rencana vs Aktual** (PJ-FR-21) menggantikan konsep As Is vs Aktual `[SPA-2]`.

### 1.1 Cakupan

| Termasuk | Tidak termasuk (ditunda / modul lain) |
|---|---|
| Penjualan Program Public & B2B dari Master Program Final, satu form | Modul **Quotation** (Public & B2B): peserta, diskon, skema all-in B2B, Deal/Batal per peserta, surat quotation, realisasi peserta fix `[ARH-5]`, `[B2B-1]` |
| Tanggal & lokasi acara; costing per penjualan (salin & sesuaikan) | Modul **Invoice** (menyusul) |
| Simulasi per peserta (sama dengan Master Program) | Mandays `[ARH-1]`, `[JLR-1]`; diskon `[TDK-1]` |
| Status Draft → Final, atau Batal; cetak surat penawaran | Approval `[QT-16]` |
| Laporan Rencana vs Aktual (data aktual dari Quotation) | |

### 1.2 Istilah

| Istilah | Arti |
|---|---|
| Penjualan Program | Rencana satu acara dari satu Master Program, berjenis Public atau B2B, dengan Nomor Penjualan dan status. |
| Jenis | Public atau B2B. Keduanya ke Quotation setelah Final; form sama, kecuali B2B wajib Company dan target peserta minimal 10. |
| Titik impas (BEP) | Jumlah peserta minimal agar tidak rugi, dan harga minimal per peserta pada jumlah peserta yang direncanakan. |

---

## 2. Aktor

| Aktor | Peran | Sumber |
|---|---|---|
| *Belum ditetapkan* (kemungkinan Sales / Marketing) | Menyusun, memfinalkan, dan membatalkan Penjualan Program | Q-PJ-02 |
| Quotation (modul) | Menerima Penjualan Program Public dan B2B berstatus Final | `[QCP-1]`, `[B2B-3]` |

---

## 3. Tabel Use Case

| Kode | Nama Use Case | User | Modul | Sub Modul | Input | Deskripsi | Output | Entity/Objek Terkait | FR |
|---|---|---|---|---|---|---|---|---|---|
| UC-22 | Menyusun Penjualan Program | Manajemen | Penjualan Program | Penjualan | Master Program Final, jenis (Public / B2B), tanggal & lokasi acara, durasi, target peserta, harga jual / peserta, target profit, costing | Aktor memilih Master Program Final dan jenis Public atau B2B. Sistem menyalin durasi, target peserta, harga jual standar, target profit, dan costing dari Master Program. Aktor mengisi tanggal & lokasi, menyesuaikan angka dan item biaya (semua nominal dapat diedit, termasuk biaya lain). Form dan simulasi sama untuk kedua jenis; simulasi dihitung ulang setiap angka berubah. Aktor menyimpan sebagai Draft. | Penjualan Program Draft dengan Nomor Penjualan | Penjualan Program, Biaya Penjualan Program, Master Program, Rate Fixed Cost, Rate Variable Cost, Satuan | PJ-FR-01 – PJ-FR-10 |
| UC-29 | Memfinalkan Penjualan Program | Manajemen | Penjualan Program | Penjualan | Penjualan Program berstatus Draft | Aktor menekan "Finalkan". Sistem menampilkan popup ringkasan (jenis, durasi, target peserta, harga jual / peserta, pendapatan, HPP, profit, titik impas) dan peringatan bila profit minus. Setelah dikonfirmasi status menjadi Final dan terkunci; Public dan B2B siap masuk ke Quotation. | Penjualan Program Final, terkunci | Penjualan Program | PJ-FR-14 – PJ-FR-16 |
| UC-30 | Mencetak Surat Penawaran | Manajemen | Penjualan Program | Penjualan | Penjualan Program berstatus Final | Aktor membuka Penjualan Program Final dan menekan "Cetak Surat Penawaran". Sistem menampilkan surat penawaran dari angka Final untuk dicetak. | Surat penawaran | Penjualan Program | PJ-FR-20 |
| UC-31 | Melihat Laporan Rencana vs Aktual | Manajemen | Penjualan Program | Laporan | Penjualan Program Final, peserta fix dari Quotation | Aktor membuka laporan dan memilih penjualan / periode. Sistem menampilkan rencana, aktual, dan selisih (peserta, pendapatan, HPP, profit). | Laporan Rencana vs Aktual | Penjualan Program, Biaya Penjualan Program | PJ-FR-21 |
| UC-26 | Membatalkan Penjualan Program | Manajemen | Penjualan Program | Penjualan | Penjualan Program berstatus Draft | Aktor memilih Batalkan dan mengonfirmasi. Status menjadi Batal dan tidak dapat diubah. | Penjualan Program berstatus Batal | Penjualan Program | PJ-FR-14, PJ-FR-17 |

> Semua UC belum lolos uji admission no. 1 (aktor bernama) — Q-PJ-02.
> Dihapus dan tidak dipakai ulang: UC-24, UC-25, UC-27 (draf 3); UC-28 (draf 4, `[REV-3]`); **UC-23** Menyusun Penjualan Program B2B (draf 8 — digabung ke UC-22 karena form sama `[JLR-1]`).

---

## 4. Kebutuhan Fungsional (FR)

### 4.1 Penjualan Program

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-01 | **Daftar Penjualan Program**: No, Nomor, Jenis (Public/B2B; nama Company untuk B2B), Master Program (judul + nomor), Pelaksanaan (tanggal & lokasi), Target peserta, Harga jual / peserta, Status, Action (menu gear). Dapat disaring per jenis dan status. | `[QT-1]` + `[USULAN]` (kolom daftar) |
| PJ-FR-02 | Penjualan Program dibuat dengan memilih **Master Program berstatus Final**. Master Program Draft/Batal tidak muncul. | `[QT-1]`, `[QT-FIELD]` |
| PJ-FR-03 | Pengguna memilih **jenis penjualan** (Public atau B2B) **saat membuat** Penjualan Program. Jenis hanya menentukan jalur setelah Final (PJ-FR-16); **form, field, dan simulasi sama** untuk kedua jenis. Jenis tidak dapat diubah setelah disimpan. | `[QT-2]`, `[JLR-1]`, `[JLR-3]` + `[USULAN]` (jenis terkunci) |
| PJ-FR-04 | Saat Master Program dipilih, sistem **menyalin** durasi, target peserta, harga jual standar / peserta, target profit, dan semua item costing (termasuk biaya lain). | `[QT-1]`, `[QT-5]`, `[JLR-2]` |
| PJ-FR-05 | Semua angka hasil salinan **dapat diedit**: target peserta, harga jual / peserta, target profit, nominal semua item biaya, menambah/menghapus item dari Master Rate, dan menambah biaya lain. Perubahan hanya berlaku di Penjualan Program itu dan **tidak mengubah Master Program**. **Durasi** tidak diedit manual — dihitung dari tanggal (PJ-FR-06) `[JPP-6]`. Hanya item **terpakai** yang tampil; item bernilai 0 dibuang saat disimpan (MP-FR-07) `[BTS-1]`. | `[QT-5]`, `[USR-3M]`, `[K3M-2]`, `[REV-1]`, `[JLR-2]` |
| PJ-FR-06 | Penjualan Program memuat **tanggal mulai** dan **tanggal selesai** (keduanya wajib) dan **lokasi acara** (teks bebas). **Durasi (hari) dihitung otomatis** = tanggal selesai − tanggal mulai + 1, tidak diisi manual; sebelum tanggal lengkap, durasi sementara = durasi Master Program. Tanggal selesai tidak boleh sebelum tanggal mulai. | `[USR-3M]`, `[JPP-6]` + `[USULAN]` (rumus inklusif, tanggal wajib) |
| PJ-FR-07 | **Nomor Penjualan** otomatis saat pertama disimpan, **dibedakan untuk Public dan B2B** `[JPP-7]`. Format sementara: Public `PJP-YYMM-NNN`, B2B `PJB-YYMM-NNN`, nomor urut ulang tiap bulan per jenis (mis. PJB-2610-001). Format final: Q-PJ-08. | `[JPP-7]` + `[USULAN]` (format) |
| PJ-FR-08 | Costing memakai model dan layout yang sama dengan Master Program: Fixed Cost per Event/Day, Variable Cost per Peserta/Event dan Peserta/Day, biaya lain. Biaya trainer dihitung per event (1 event = 1 trainer). | `[QT-5]`, `fr-nfr.md` FR-25 |
| PJ-FR-09 | Input harga (Public dan B2B): **Harga jual / peserta**, **Target peserta**, **Target profit** (opsional). Tidak ada skema all-in, harga paket, atau diskon di sini. Peserta, diskon, dan skema all-in B2B diisi di Quotation; Company B2B di PJ-FR-22; target peserta B2B minimal 10 (PJ-FR-25). Input simulasi (durasi, target peserta, harga jual / peserta, target profit) tampil **di bagian 1 dan di bagian 4 (Simulasi Hasil)**, terikat ke **satu nilai** — cukup diisi sekali di salah satu tempat, tempat lain ikut berubah. | `[QT-6]`, `[ARH-5]`, `[TDK-1]`, `[JLR-2]`, `[ISC-1]`, `[KSC-1]` |
| PJ-FR-10 | **Simulasi** sama dengan Master Program (MP-FR-10, rumus `fr-nfr-master-program.md` bagian 8), untuk Public dan B2B: (1) **Titik impas (BEP)** — BEP jumlah peserta dan BEP harga jual; (2) **Hasil rencana** — Pendapatan, Biaya (HPP), Profit dan margin %, target profit tercapai/kurang, minimal peserta untuk capai target; (3) **Posisi di skala jumlah peserta**; (4) **Rincian** yang dapat dibuka/tutup, ditulis **sederhana dan mudah dipahami** (langkah hitung dalam bahasa sehari-hari, tabel profit pendek). Peringatan: profit minus, BEP di atas target peserta, profit < target profit. | `[QT-9]`, `[REV-4]`, `[BEP-1]`, `[SIMX]`, `[JLR-1]`, `[JLR-3]` |

| PJ-FR-22 | Penjualan Program **B2B** memuat **Company** yang dipilih dari **Master Company** (`customer`, `fr-nfr.md` FR-30 – FR-32) di bagian 1. **Wajib** untuk B2B sebelum disimpan; Public tidak memilih company. Hanya Company **aktif** yang dapat dipilih `[JPP-3]`. Company tidak ditambah dari form ini — dibuat di Master Company, lalu dipilih `[JPP-2]`. Company dapat diubah selama Draft, terkunci setelah Final `[JPP-1]`. Company tampil di daftar (PJ-FR-01), popup Finalkan (PJ-FR-15), dan surat penawaran (PJ-FR-20), serta menjadi Company tujuan Quotation B2B `[B2B-1]`. | `[ISC-2]`, `[KSC-2]`, `[JPP-1]` – `[JPP-3]` + `[USULAN]` (isi tampilan) |
| PJ-FR-23 | Penjualan Program (Public & B2B) memuat **Trainer** yang dipilih dari **Master Trainer** aktif (dropdown dapat dicari, menampilkan bidang keahlian) — **satu trainer** per penjualan, karena 1 event = 1 trainer `[K3M-1]`. Opsional `[USULAN]` (Q-PJ-20). Terkunci setelah Final seperti field lain. Trainer tampil di daftar Penjualan Program (kolom Pelaksanaan) dan surat penawaran `[USULAN]`. Honor Trainer tetap item Master Rate, tidak terhubung ke trainer (Q-PJ-20). | `input/Master - Buat Program/2026-10-29-master-trainer/`, `[K3M-1]` |
| PJ-FR-24 | Penjualan Program memuat **Waktu** pelaksanaan (teks, mis. "09.00–15.15 WIB"), opsional; dicetak di Penawaran (Deskripsi) dan Formulir Konfirmasi ("pukul …") quotation. | `input/Master - Buat Program/2026-10-29-cetak-quotation-snk-konfirmasi/` + `[USULAN]` (field di Penjualan Program, Q-PJ-21) |
| PJ-FR-25 | Penjualan Program **B2B**: **Target peserta minimal 10**, berlaku sampai Quotation (peserta quotation B2B minimal 10, QTN-FR-21). Tidak dapat disimpan bila target < 10; target bawaan dari Master Program di bawah 10 dinaikkan ke 10 saat jenis B2B dipilih `[USULAN]`. Public tanpa batas minimal. | `[B2B-2]`, `[JPP-13]` + `[USULAN]` (naik otomatis) |

> PJ-FR-11 – PJ-FR-13 (Company, skema harga, perhitungan B2B) **dihapus** pada draf 8 `[JLR-2]` dan tidak dipakai ulang; skema all-in kini di Quotation B2B (QTN-FR-20) `[B2B-1]`; Sales Order tidak ada `[B2B-3]`.

### 4.2 Status

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-14 | Status: **Draft → Final**, atau **Batal** (bagian 6). Penjualan baru tersimpan Draft; selama Draft semua isian dapat diubah. Tidak ada approval. | `[ARH-4]`, `[QT-16]` |
| PJ-FR-15 | Aksi **"Finalkan"** memunculkan popup ringkasan (jenis, durasi, target peserta, harga jual / peserta, pendapatan, HPP, profit, titik impas) dan peringatan bila profit minus. Setelah dikonfirmasi status **Final** dan Penjualan Program **terkunci**. | `[ARH-4]`, `[QT-12]` (pola popup) |
| PJ-FR-16 | Penjualan Program **Final**, **Public maupun B2B, masuk ke modul Quotation** (Public boleh banyak quotation; B2B satu quotation ke Company penjualan `[B2B-5]`) `[QCP-1]`; aksi "+ Buat Quotation" di daftar, kolom target menampilkan jumlah peserta Deal. *(Rumusan lama: Public → Quotation, B2B → Sales Order (satu SO).)* Final adalah **tanda verifikasi** dan **pemicu** ke Quotation `[SPA-1]`; angka Final menjadi dasar quotation, persiapan invoice `[INV-1]`. **Sales Order tidak ada** `[B2B-3]`. | `[ARH-4]`, `[JLR-2]`, `[INV-1]`, `[SPA-1]` |
| PJ-FR-17 | Hanya Penjualan Program **Draft** yang dapat **dibatalkan** (status Batal, tidak dapat diubah). Penjualan Program **Final tidak dapat dibatalkan** `[JPP-5]`. | `[QT-12]` (pola batal), `[JPP-5]` |

> PJ-FR-18 dan PJ-FR-19 (As Is vs Penjualan, laporan) **dihapus** pada draf 4 `[REV-3]` dan tidak dipakai ulang.

### 4.3 Surat penawaran & laporan

| ID | Kebutuhan | Sumber |
|---|---|---|
| PJ-FR-20 | Penjualan Program **Final** dapat **dicetak sebagai surat penawaran** (tombol "Cetak Surat Penawaran" di halaman Lihat). Isi minimal: Nomor Penjualan, judul program, tanggal & lokasi, durasi, harga jual / peserta, jumlah peserta, total. Penjualan Draft/Batal tidak dapat dicetak. B2B ditujukan ke **Company** penjualan (nama, PIC, alamat — PJ-FR-22) `[ISC-2]`; Public juga dapat dicetak (**sementara**), ditujukan ke calon peserta `[JPP-4]`. | `[SPA-1]`, `[ISC-2]`, `[JPP-4]` + `[USULAN]` (isi surat) |
| PJ-FR-21 | **Laporan Rencana vs Aktual** per Penjualan Program Final: **Rencana** = angka Final Penjualan Program; **Aktual** = jumlah peserta fix dari Quotation (Public, akumulasi) / SO (B2B). Kolom: peserta, pendapatan, HPP, profit — rencana, aktual, selisih. Harga jual / peserta tidak berubah; selisih peserta hanya memengaruhi pendapatan, biaya variable, dan profit. Data aktual tersedia setelah modul Quotation ada. | `[SPA-2]` + `[USULAN]` (kolom & rumus aktual: Fixed Cost tetap, Variable Cost × peserta aktual) |

### 4.4 Catatan untuk modul berikutnya (ditunda, bukan cakupan saat ini)

| Modul | Yang sudah disampaikan | Sumber |
|---|---|---|
| Quotation (Public & B2B) | **Sudah dikerjakan** → `docs/fr-nfr-quotation.md` `[QCP-1]`. Draf 2 (29-10-2026, `input/Master - Buat Program/2026-10-29-quotation-diskon-aksi/`): Public berkali-kali, diskon Rupiah, aksi Kirim dan Cetak / Deal / Batalkan / Confirmation Letter. Catatan lama: **Persiapan invoice**, peserta sudah fix `[INV-1]`. Dibagikan ke **banyak** calon peserta dari satu Penjualan Program; tidak terikat company. Dari sumber lama: diskon per peserta, surat quotation; **Deal/Batal per peserta masih ada** `[JPP-15]`. Harga jual / peserta tidak berubah; selisih peserta hanya memengaruhi profit `[SPA-2]`. | `[JLR-2]`, `[ARH-5]`, `[INV-1]`, `[SPA-2]` |
| ~~Sales Order (B2B)~~ | **Dihapus** `[B2B-3]` — Sales Order tidak ada; B2B lewat Quotation (satu quotation, minimal 10 peserta, harga per peserta / all-in). Catatan lama di bawah hanya arsip. Catatan lama: **Persiapan invoice**, peserta sudah fix `[INV-1]`. **Satu** SO per Penjualan Program; terikat **satu Company** — diambil dari Penjualan Program (PJ-FR-22) `[ISC-2]`; memuat **detail harga per peserta** (harga per peserta × jumlah peserta = harga paket). **Minimal 10 peserta wajib** `[JPP-13]`. Status (usulan sementara `[JPP-14]`): Draft → Final → Invoice, atau Batal dari Draft. Dipicu oleh Penjualan Program Final `[SPA-1]`. Perbedaan peserta dengan rencana tidak masalah karena company yang mendanai `[SPA-2]`. | `[JLR-2]`, `[JLR-3]`, `[K3M-3]`, `[INV-1]`, `[SPA-n]` |

---

## 5. Kebutuhan Non-Fungsional (NFR)

| ID | Kategori | Kebutuhan | Kriteria uji | Lingkup | Sumber |
|---|---|---|---|---|---|
| PJ-NFR-01 | Audit | Simpan, ubah costing, finalkan, dan batal dicatat `write_log()` modul "Penjualan Program"; ubah costing dengan `detail_changes` per item. **Jejak data & Activity Log**: semua tabel modul ini memakai kolom `created_by`, `created_date`, `updated_by`, `updated_date` (+ `deleted_by`, `deleted_date` bila dapat dihapus); daftar menampilkan kolom "Dibuat / Diubah"; semua aksi tampil di modul **Activity Log** (`docs/fr-nfr-activity-log.md` LOG-FR-01 – LOG-FR-08) `input/Master - Buat Program/2026-10-30-histori-activity-log/`. | Ubah 1 harga item + durasi → 1 log dengan 2 perubahan | UC-22, UC-26, UC-29 | `[STD]` |
| PJ-NFR-02 | Integritas data | Mengedit Penjualan Program tidak pernah mengubah Master Program (costing disalin ke tabel penjualan). | Ubah harga item di penjualan → `master_program_biaya` tetap | PJ-FR-05 | `[QT-5]` |
| PJ-NFR-03 | Integritas data | Penjualan Program Final/Batal tidak dapat diubah di server. | Request ubah ke penjualan Final ditolak (403/422) | PJ-FR-15 – PJ-FR-17 | `[ARH-4]` |
| PJ-NFR-04 | Integritas data | Simpan header + biaya dalam satu transaksi; finalkan memeriksa status di dalam transaksi. | Dua klik Finalkan bersamaan → satu log | UC-22, UC-29 | `[USULAN]` |
| PJ-NFR-05 | Akurasi | Simulasi memakai **satu fungsi hitung** yang sama dengan Master Program (klien dan server). Uang DECIMAL(15,2), tampil rupiah tanpa desimal, isian uang dengan pemisah ribuan titik saat diketik (`fr-nfr.md` NFR-12); titik impas peserta dibulatkan ke atas; "tidak tercapai" bila harga per peserta ≤ biaya variable / peserta. | Angka sama dengan Master Program untuk input yang sama | PJ-FR-10 | `[JLR-1]` + `[USULAN]` |
| PJ-NFR-06 | Validasi | Master Program dan jenis wajib; durasi dan target peserta bilangan bulat > 0, **target peserta B2B ≥ 10** `[B2B-2]`; harga jual / peserta wajib; tanggal selesai ≥ tanggal mulai. | Jenis kosong ditolak; tanggal selesai < mulai ditolak | PJ-FR-03, PJ-FR-06, PJ-FR-09 | `[USULAN]` |
| PJ-NFR-07 | Keamanan | Menu & aksi (buat, ubah, finalkan, batal) mengikuti permission aplikasi. | Tanpa hak finalkan → tombol tidak tampil, request ditolak | Semua UC | `[USULAN]` |
| PJ-NFR-09 | Integritas data | Surat penawaran dan laporan Rencana vs Aktual hanya membaca angka Penjualan Program Final, tidak mengubahnya. | Cetak / buka laporan → data penjualan tetap | PJ-FR-20, PJ-FR-21 | `[USULAN]` |
| PJ-NFR-08 | Platform | CI3 HMVC, MySQL, Bootstrap + jQuery; nama tabel/kolom = ERD. Tidak memakai `tr_penawaran` (modul quotation lama aplikasi). | Code review | Semua | `[STD]` |

---

## 6. Status Penjualan Program

| Dari | Aksi | Ke | Jenis | Syarat / efek | Sumber |
|---|---|---|---|---|---|
| — | Simpan penjualan baru | Draft | Semua | Nomor dibuat | `[USULAN]` |
| Draft | Finalkan (konfirmasi popup) | Final | Public | Terkunci; dapat dibuatkan Quotation (Company / Personal) | `[ARH-4]`, `[QCP-1]` |
| Draft | Finalkan (konfirmasi popup) | Final | B2B | Terkunci; dapat dibuatkan **satu** Quotation ke Company penjualan | `[QCP-1]`, `[B2B-5]` |
| Draft | Batalkan | Batal | Semua | — | `[USULAN]` |

Final dan Batal tidak kembali ke Draft. Final tidak dapat dibatalkan `[JPP-5]`.

---

## 7. Pertanyaan Terbuka

| ID | Pertanyaan | Mempengaruhi |
|---|---|---|
| Q-PJ-02 | Aktor saat ini **Manajemen** `[JPP-11]`. Pengguna lain (user) yang ikut menyusun / memfinalkan / membatalkan — menyusul. | UC-22, UC-26, UC-29 – UC-31 |
| Q-PJ-08 | Format **final** Nomor Penjualan (sementara `PJP-YYMM-NNN` / `PJB-YYMM-NNN`, PJ-FR-07) `[JPP-7]`. | PJ-FR-07 |
| Q-PJ-16 | ~~Status final Sales Order~~ **Gugur** `[B2B-3]`: Sales Order tidak ada. | — |
| Q-PJ-20 | **Trainer** wajib dipilih (mis. sebelum Final) atau boleh kosong (usulan: opsional)? Apakah Honor Trainer berbeda per trainer (diambil dari Master Trainer), atau tetap dari Master Rate? | PJ-FR-23 |
| Q-PJ-21 | **Waktu** (jam) pelaksanaan: cukup teks bebas di Penjualan Program (usulan), atau jam mulai–selesai terpisah / berbeda per hari? | PJ-FR-24 |

**Sudah terjawab** (23-10-2026): item Fix / mode harga tidak ada lagi, semua nominal dapat diedit (`[K3M-2]`, `[REV-1]`); diskon (Public & B2B) ditunda (`[TDK-1]`); setelah Final → Quotation / Sales Order (`[ARH-4]`). **(25-10-2026, `[JLR-n]`)**: Penjualan Program sama untuk kedua jenis; jenis dipilih saat membuat Penjualan Program; Mandays tidak dipakai (Q-PJ-04 gugur); dokumen B2B = Sales Order. `[SPA-n]`: penawaran = cetak Penjualan Program Final (Q-PJ-16 lama); selisih peserta diterima dan dipantau lewat laporan Rencana vs Aktual (Q-PJ-14, Q-PJ-17 lama). **(28-10-2026, `[ISC-n]`)**: Company B2B dipilih di Penjualan Program dari Master Company (Q-PJ-18 terjawab → PJ-FR-22). **(28-10-2026, `[JPP-n]`)**: nama menu sesuai (Q-PJ-01); aktor Manajemen (Q-PJ-02, sebagian); durasi otomatis dari tanggal (Q-PJ-03); Public juga dicetak, sementara (Q-PJ-19); hanya Draft yang dibatalkan (Q-PJ-12); SO minimal 10 peserta wajib (Q-PJ-15); Quotation masih Deal/Batal per peserta (Q-PJ-17). **Ditunda ke modul Quotation** `[ARH-5]`: diskon per peserta, Deal/Batal per peserta, surat quotation (Q-PJ-11), realisasi peserta fix.

---

## 8. Riwayat Perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| draf 1 | 23-10-2026 | Penyusunan awal dari *Penjualan Program - Labmania.xlsx* sebagai modul Quotation (Public multi-peserta, B2B Deal). |
| draf 2 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-konfirmasi-3-modul/`: item Fix dapat diedit; B2B variable per peserta, jumlah peserta default/min 10; kunci costing setelah peserta Deal; diskon per peserta Rp / %. |
| draf 3 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-arah-penjualan-program/` — ditulis ulang sebagai **Penjualan Program** dengan status Draft → Final / Batal; Final Public → Quotation, B2B → Sales Order (ditunda); fitur quotation ditunda; diskon B2B; As Is vs Penjualan; UC-24, UC-25, UC-27 dihapus; UC-29 baru. |
| draf 4 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-revisi-rate-harga-simulasi/`: B2B punya **skema harga** per peserta (default) atau all-in — harga paket tidak wajib (PJ-FR-04, PJ-FR-12, PJ-FR-13, PJ-NFR-06); As Is vs Penjualan dan laporannya dihapus (PJ-FR-18, PJ-FR-19, UC-28 dihapus; Q-PJ-14 baru); simulasi ditata ulang agar mudah dibaca (PJ-FR-10); tidak ada mode harga (PJ-FR-05). |
| draf 5 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-simulasi-bep/`: simulasi ditambah **Data BEP** — perhitungan BEP, tabel profit menurut jumlah peserta, dan batas peserta untuk B2B all-in (PJ-FR-10). |
| draf 6 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-simulasi-hasil-html/`: tampilan simulasi mengikuti `input/simulasi_hasil.html` (PJ-FR-10); B2B all-in memakai harga paket minimal & maksimal peserta pada kotak BEP dan skala. |
| draf 6a | 23-10-2026 | Istilah "sumbangan per peserta" dihapus atas permintaan user (chat 23-10-2026); BEP peserta ditulis langsung sebagai Fixed Cost ÷ (harga − biaya variable per peserta). |
| draf 7 | 23-10-2026 | `input/Master - Buat Program/2026-10-23-tanpa-diskon/`: **diskon dihapus untuk sementara** (Public & B2B) — PJ-FR-12, PJ-FR-13, PJ-FR-15, PJ-NFR-06, UC-23, UC-29; Q-PJ-13 gugur. |
| draf 8 | 25-10-2026 | `input/Master - Buat Program/2026-10-25-jalur-public-b2b/`: **form & simulasi sama** untuk Public dan B2B; jenis hanya penanda jalur (PJ-FR-03). Company, skema all-in, harga paket, jumlah peserta min 10 keluar ke **Sales Order** (ditunda) — PJ-FR-11 – PJ-FR-13 dihapus, bagian 4.3 baru. UC-23 digabung ke UC-22. Target peserta disalin dari Master Program (default 10). Rincian simulasi disederhanakan (PJ-FR-10). Q-PJ-04 gugur; Q-PJ-15, Q-PJ-16 baru. |
| draf 8a | 25-10-2026 | `input/Master - Buat Program/2026-10-25-quotation-so-persiapan-invoice/`: Penjualan Program = tahap menuju finalisasi; Quotation & SO = persiapan invoice, peserta sudah fix (bagian 1, PJ-FR-16, bagian 4.3). Usulan status SO Dikirim → Deal dan usulan Aktual di Q-PJ-14 dicabut; Q-PJ-16 ditulis ulang; Q-PJ-17 baru. |
| draf 8b | 25-10-2026 | `input/Master - Buat Program/2026-10-25-surat-penawaran-rencana-aktual/`: Penjualan Program Final dapat dicetak sebagai **surat penawaran** dan memicu SO (PJ-FR-16, PJ-FR-20, UC-30 baru); **laporan Rencana vs Aktual** (PJ-FR-21, UC-31 baru) menggantikan As Is vs Aktual; Q-PJ-14 terjawab; Q-PJ-16, Q-PJ-17 ditulis ulang; Q-PJ-18, Q-PJ-19 baru; bagian 4.3 → 4.4. |
| draf 8c | 28-10-2026 | `input/Master - Buat Program/2026-10-28-input-simulasi-company-b2b/`: input simulasi tampil di bagian 1 **dan** bagian 4, tersinkron (PJ-FR-09); **Company B2B dipilih di Penjualan Program** dari Master Company (PJ-FR-22 baru; PJ-FR-01, PJ-FR-20, bagian 1, 4.4); Q-PJ-18 terjawab. ERD: `penjualan_program.id_customer` kembali. |
| draf 8d | 28-10-2026 | `input/Master - Buat Program/2026-10-28-konfirmasi-simulasi-company-b2b/`: input simulasi ganda (PJ-FR-09) dan Company wajib untuk B2B (PJ-FR-22) dikonfirmasi klien. |
| draf 9 | 28-10-2026 | `input/Master - Buat Program/2026-10-28-jawaban-pertanyaan-program/`: aktor Manajemen (UC); durasi otomatis dari tanggal, tanggal wajib (PJ-FR-05, PJ-FR-06); nomor dibedakan Public/B2B, format sementara (PJ-FR-07); hanya Draft dibatalkan (PJ-FR-17, bagian 6); surat Public sementara boleh (PJ-FR-20); Company aktif, tidak ditambah dari form (PJ-FR-22); SO min 10 wajib, status SO sementara, Quotation Deal/Batal per peserta (4.4). Q-PJ-01, -03, -12, -15, -17, -19 terjawab; Q-PJ-02, -08, -16 ditulis ulang. |
| draf 9a | 28-10-2026 | `input/Master - Buat Program/2026-10-28-gabung-menu-penjualan-program/`: Master Program & Penjualan Program satu modul menu **Penjualan Program**; sub modul Penjualan → Penjualan Program (bagian 1). |
| draf 9b | 28-10-2026 | `input/Master - Buat Program/2026-10-28-biaya-terpakai-saja/`: hanya item biaya terpakai yang tampil; item bernilai 0 dibuang saat disimpan (PJ-FR-05). |
| draf 10 | 28-10-2026 | `input/Master - Buat Program/2026-10-28-quotation-company-personal/`: Public **dan** B2B Final masuk ke **Quotation** (PJ-FR-16, bagian 1, 4.4, 6); modul Quotation di `docs/fr-nfr-quotation.md`. Sales Order menunggu Q-QTN-03. |
| draf 11 | 29-10-2026 | `input/Master - Buat Program/2026-10-29-master-trainer/`: field **Trainer** dari Master Trainer (PJ-FR-23); Q-PJ-20 baru. |
| draf 11a | 29-10-2026 | `input/Master - Buat Program/2026-10-29-cetak-quotation-snk-konfirmasi/`: field **Waktu** pelaksanaan (PJ-FR-24) untuk cetak quotation; Q-PJ-21 baru. |
| draf 12 | 30-10-2026 | `input/Master - Buat Program/2026-10-30-b2b-quotation-allin-min10/`: **Sales Order tidak ada** — B2B juga ke Quotation, satu quotation (bagian 1, 1.1, 1.2, 2, UC-29, PJ-FR-16, PJ-FR-22, 4.4, 6); B2B **target peserta minimal 10** sampai Quotation (PJ-FR-25 baru, PJ-NFR-06); skema all-in pindah ke Quotation B2B. Q-PJ-16 gugur. Draf 11a: `input/backup/fr-nfr-penjualan-program_sebelum-b2b-quotation_30-10-2026.md`. |
| draf 12a | 30-10-2026 | `input/Master - Buat Program/2026-10-30-format-rupiah-titik/`: isian uang dengan pemisah ribuan titik (PJ-NFR-05). |
| draf 12b | 30-10-2026 | `input/Master - Buat Program/2026-10-30-histori-activity-log/`: kolom jejak (created/updated/deleted) di semua tabel dan modul **Activity Log** — rujukan ke `docs/fr-nfr-activity-log.md` di NFR audit; mockup draf 12. |

---

# Bagian 4 — Modul Quotation Labmania

- **Versi:** draf 9c — 30-10-2026
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
| QTN-NFR-01 | Audit | Simpan, ubah, kirim & cetak, Deal (peserta deal/batal), batal, cetak Confirmation Letter dicatat `write_log()` modul "Quotation" (termasuk perubahan Keterangan sebagai `detail_changes` per kategori); ubah dengan `old`/`new` dan `detail_changes` per peserta. Peserta baru dari form Quotation dicatat `write_log()` modul "Master Peserta" aksi Tambah. **Jejak data & Activity Log**: semua tabel modul ini memakai kolom `created_by`, `created_date`, `updated_by`, `updated_date` (+ `deleted_by`, `deleted_date` bila dapat dihapus); daftar menampilkan kolom "Dibuat / Diubah"; semua aksi tampil di modul **Activity Log** (`docs/fr-nfr-activity-log.md` LOG-FR-01 – LOG-FR-08) `input/Master - Buat Program/2026-10-30-histori-activity-log/`. | `[STD]` |
| QTN-NFR-06 | Dokumen | PDF dibuat server-side dari template HTML/CSS (A4, satu halaman per bagian/peserta) agar identik dengan pratinjau; kode dokumen, nomor halaman, dan tanggal berlaku template dicetak di kaki halaman. | `[CQP]` + `[USULAN]` |
| QTN-NFR-02 | Integritas data | Harga diambil dari Penjualan Program Final di server, bukan dari isian klien; diskon divalidasi di server (Rupiah; per peserta: 0 ≤ diskon ≤ harga / peserta; all-in: 0 ≤ diskon ≤ harga all-in); quotation tidak mengubah Penjualan Program. | `[SPA-2]`, `[QDA-3]`, `[BAD-1]` + `[USULAN]` |
| QTN-NFR-03 | Integritas data | Larangan peserta ganda (QTN-FR-07), kecocokan company peserta (QTN-FR-06), dan satu quotation B2B (QTN-FR-03), dan minimal 10 peserta B2B saat simpan dan saat Deal (QTN-FR-21) diperiksa di server dalam transaksi; harga all-in divalidasi di server (> 0). | `[B2B-2]`, `[B2B-5]` + `[USULAN]` |
| QTN-NFR-07 | Usability | Isian Harga all-in, Diskon, dan Biaya lain-lain memakai pemisah ribuan titik saat diketik (600.000), hanya angka (`fr-nfr.md` NFR-12). | `input/Master - Buat Program/2026-10-30-format-rupiah-titik/` |
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
| draf 9b | 30-10-2026 | `input/Master - Buat Program/2026-10-30-format-rupiah-titik/`: isian uang dengan pemisah ribuan titik (QTN-NFR-07 baru). |
| draf 9c | 30-10-2026 | `input/Master - Buat Program/2026-10-30-histori-activity-log/`: kolom jejak (created/updated/deleted) di semua tabel dan modul **Activity Log** — rujukan ke `docs/fr-nfr-activity-log.md` di NFR audit; mockup draf 12. |

---

# Bagian 5 — Jejak Data & Modul Activity Log Labmania

- **Versi:** draf 2 — 30-10-2026
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
| LOG-FR-09 | **Hak akses**: menu **Activity Log** dan menu gear **Lihat Riwayat** hanya tampil dan dapat dibuka oleh group akses **Manajemen** dan **Admin Developer** (group baru di Master User, `docs/fr-nfr.md` FR-36); pengguna lain tidak melihat menu tersebut dan ditolak di server. | `[AKR-4]` |

---

## 5. Kebutuhan Non-Fungsional (NFR)

| ID | Kategori | Kebutuhan | Sumber |
|---|---|---|---|
| LOG-NFR-01 | Audit | Pencatatan memakai `write_log()` sesuai standar `AGENTS.md`: Edit menyimpan snapshot `old` (diambil **sebelum** update) dan `new`; header–detail menyertakan `detail_changes`; perbedaan format angka (0 vs 0.00) dan NULL ↔ 0 pada kolom nominal tidak dianggap perubahan; nama modul tampil otomatis di filter (`SELECT DISTINCT module`). | `[STD]` |
| LOG-NFR-02 | Integritas data | Simpan data, kolom jejak, dan `write_log()` dalam **satu transaksi**: bila log gagal, simpan dibatalkan. Kolom jejak diisi di server (bukan dari isian klien). | `[USULAN]` |
| LOG-NFR-03 | Keamanan | Tabel `logs` hanya-tambah (tanpa endpoint ubah/hapus dari aplikasi). Akses Activity Log & Lihat Riwayat dibatasi ke group Manajemen dan Admin Developer lewat permission aplikasi, diperiksa di server (LOG-FR-09). | `[AKR-4]` + `[USULAN]` |
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

