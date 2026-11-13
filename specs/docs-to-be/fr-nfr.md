# Kebutuhan Fungsional & Non-Fungsional — Modul Master Data Labmania

- **Versi:** **TO BE** draf 1g — 13-11-2026 (berangkat dari as is draf 11x, `docs/fr-nfr.md`)
- **Perubahan to be:** `input/Master - Buat Program/2026-11-06-to-be-kuota-link-konfirmasi/` butir 1 — master **Varian Pelaksanaan kembali** dan Product Combine kembali **empat** atribut (menggantikan `input/Master - Buat Program/2026-10-29-npwp-instansi-hapus-varian/` butir 2, khusus to be); butir 3 — peserta quotation tidak lagi diambil dari Master Peserta (FR-15); sebaliknya, peserta **Deal** disimpan ke Master Peserta saat Deal `input/Master - Buat Program/2026-11-11-peserta-deal-ke-master-peserta/` (FR-15, FR-16). Dokumen to be dipisah dari as is: `input/Master - Buat Program/2026-11-07-dokumen-to-be-terpisah/`. Penanda `[TB-n]` = butir verbatim n di `input/Master - Buat Program/2026-11-06-to-be-kuota-link-konfirmasi/`.
- **Ganti nama menu (to be):** `input/Master - Buat Program/2026-11-09-product-multi-reschedule-evidence/` — menu **Product Combine** menjadi **Product** (judul halaman, Nomor Product, Activity Log); tabel tetap `paket_produk`. Master Program dapat memilih lebih dari satu Product (`docs-to-be/fr-nfr-master-program.md` MP-FR-02).
- **Sumber:** `input/Product - Labmania.xlsx` (sheet *Master Data*, *Data Mentah Struktur Product*), `input/Master - Buat Program/2026-10-22-klarifikasi-produk/`, `input/Master - Buat Program/2026-10-23-review-mockup-master-data/`, `input/Master - Buat Program/2026-10-23-requirements-program/` (*Product rev (1)* sheet *Requirements Product*; *Program* sheet *Cost Master*), `input/Master - Buat Program/2026-10-23-klarifikasi-program/`, `input/Master - Buat Program/2026-10-23-requirements-3-modul/` (*Master Data - Labmania.xlsx*), `input/Master - Buat Program/2026-10-23-konfirmasi-3-modul/`, `input/Master - Buat Program/2026-10-23-arah-penjualan-program/`, `input/Master - Buat Program/2026-10-23-revisi-rate-harga-simulasi/`
- **Status:** DRAF TO BE — menunggu konfirmasi Labmania. Dokumen as is `docs/fr-nfr.md` tetap acuan program sampai to be disetujui.
- **Modul lain:** Master Program → `docs/fr-nfr-master-program.md` (memakai Paket Produk dan Master Rate); Penjualan Program → `docs/fr-nfr-penjualan-program.md` (memakai Master Rate); Master Company dipakai Penjualan Program B2B dan Quotation; Master Peserta **tidak lagi** menjadi sumber peserta Quotation di to be → `docs-to-be/fr-nfr-quotation.md`
- **Use case kanonis:** `docs/workbook.xlsx` sheet *UC Non-BPMN* (tabel di bagian 3 adalah salinan baca)
- **ERD:** `docs-to-be/diagrams/erd.drawio` halaman *ERD Master Data* (to be: tabel `ms_varian_pelaksanaan`, kolom `paket_produk.id_varian_pelaksanaan`, tabel `paket_produk_lingkup`)
- **BPMN (to-be):** `docs-to-be/diagrams/bpmn-to-be.drawio` halaman *Product Combine* (atribut produk menjadi Product, UC-05; diperbarui 13-11-2026: cek kombinasi hanya Product aktif) dan *Level 0 — Lanskap*
- **Mockup:** `ui-mockup/produk-to-be/Labmania.html` (as is: `ui-mockup/produk/`)

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
| `[TB-n]` | To be 06-11-2026, `input/Master - Buat Program/2026-11-06-to-be-kuota-link-konfirmasi/` — butir verbatim n: (1) Varian kembali, (2) kuota peserta, (3) peserta tidak dari Master Peserta, (4) link per quotation, (5) Data Peserta di quotation, (6) tambah manual |
| `[JLR-n]` | Jalur Public vs B2B 25-10-2026, `input/Master - Buat Program/2026-10-25-jalur-public-b2b/` — pesan n |
| `[JPP-n]` | jawaban pertanyaan Program 28-10-2026, `input/Master - Buat Program/2026-10-28-jawaban-pertanyaan-program/` — butir n |
| `[JPT-n]` | jawaban pertanyaan terbuka 12-11-2026, `input/Master - Buat Program/2026-11-12-jawaban-pertanyaan-terbuka/` (`input/Update_Labmania_ERP_rapi.xlsx`) — baris No. n |
| `[USR-1]` | format form Master User, `input/Master - Buat Program/2026-10-28-master-user/` (`input/Master - Buat Program/Master User.png`) |
| `[GMP-1]` | gabung menu Master Program & Penjualan Program, `input/Master - Buat Program/2026-10-28-gabung-menu-penjualan-program/` |
| `[SMD]` | sub menu Master Data (Product, User, Peserta & Trainer), `input/Master - Buat Program/2026-11-07-sub-menu-master-data/` |
| `[SBL-1]` | sidebar tanpa label sub modul, `input/Master - Buat Program/2026-10-28-sidebar-tanpa-label/` |
| `[DSH-1]` | permintaan dashboard halaman depan, `input/Master - Buat Program/2026-10-28-dashboard-depan/` — isi usulan analis |
| `[DSH-2]` | dashboard dirampingkan, `input/Master - Buat Program/2026-10-28-dashboard-ringkas/` |
| `[DSH-3]` | jadwal hanya Final, KPI total, `input/Master - Buat Program/2026-10-28-dashboard-jadwal-final/` |
| `[DSH-4]` | rincian kartu KPI, `input/Master - Buat Program/2026-10-28-dashboard-rincian-kartu/` |
| `[MAM]` | permintaan Matrix Akses Menu 04-11-2026, `input/Invoice & Pengajuan/2026-11-04-jawaban-pengajuan-biaya/` (jawaban PB-01, PB-07) |
| `[MAM2]` | jawaban 04-11-2026 kedua, `input/Invoice & Pengajuan/2026-11-04-jawaban-pengajuan-biaya-2/`: Superadmin (developer) & Admin (user Labmania, dapat dibatasi); akun login trainer disetujui |
| `[RNP]` | Role & Permission 04-11-2026, `input/Invoice & Pengajuan/2026-11-04-role-permission/` (chat + video `input/Invoice & Pengajuan/Role & Permission.mp4`): role terbentuk dari Division + Jabatan akun user, diatur di menu Role & Permission; Department dihapus dari Master User |
| `[RNP2]` | jawaban 04-11-2026, `input/Invoice & Pengajuan/2026-11-04-jawaban-role-permission/`: Superadmin role bawaan sistem (dibuat dari awal, tidak dapat ditambah); role tanpa user boleh dihapus; Manajemen sementara jabatan Direktur |
| `[MRO]` | permintaan 05-11-2026, `input/Invoice & Pengajuan/2026-11-05-master-role-override/`: field Division dan Position (Jabatan) di Master User diganti satu field **Role**; Role dikelola di **Master Role** (input manual) dan jadi sumber pilihan di Master User dan Role & Permission; **Override Permission** di Master User memberi akses menu tambahan ke satu user di luar permission role-nya |
| `[STD]` | Standar proyek di `AGENTS.md` root repository |
| `[MMB]` | permintaan BA 13-11-2026, `input/Master - Buat Program/2026-11-13-multi-master-batal-top-refund/` (`[MMB-n]` = butir n): (1) Penjualan Program berisi satu atau lebih Master Program dengan costing masing-masing, Master Program kembali satu Product; (2) Batalkan Penjualan Program setelah quotation, proses sesudahnya ikut Batal; (3) invoice B2B ke company saja; (4) cara pembayaran Full payment / TOP, satu termin satu invoice; (5) harga "start from" di Katalog Produk; (6) modul Refund dengan Approval Refund |
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
Jenis Bidang ───────┼──► Product (judul manual) ─┤                          ▲                ▲   ▲   ▲
Varian Pelaksanaan ─┘     n baris → n paket produk        └─► Katalog Produk         │                │   │   │
                                                                                     │                │   │   │
                         Master Rate (item biaya + Default Cost) ────────────────────┴────────────────┘   │   │
                         Master Peserta (customer Public) ────────────────────────────────────────────────┘   │
                         Master Company (customer B2B) ───────────────────────────────────────────────────────┘
```

SIPOC `[MD-SIPOC]` (menggantikan `[SIPOC]` + `[KLR-SIPOC]`):

| Supplier | Input | Process | Output | Customer |
|---|---|---|---|---|
| Manajemen | Jenis produk, jenis sertifikasi, jenis bidang, jenis varian, judul/topik produk, item & default cost (Master Rate), data peserta, data company | Master Data | Product (Paket Produk), Katalog Produk, Master Rate, Master Peserta & Master Company | Master Program, Quotation, Promosi |

Menu modul Master Data dikelompokkan per **sub menu** yang tampil di sidebar dan dapat dibuka/ditutup (to be `input/Master - Buat Program/2026-11-07-sub-menu-master-data/` `[SMD]`, menggantikan pengelompokan `[RMD-1]` dan label tersembunyi `[SBL-1]` untuk Master Data):

| Modul | Sub Menu | Menu | Dipakai untuk |
|---|---|---|---|
| Master Data | **Product** | Jenis Produk, Jenis Sertifikat, Jenis Bidang, Varian Pelaksanaan `[TB-1]`, Product, Katalog Produk, Master Rate | Master Program (lewat Product), Penjualan Program (Master Rate), Promosi (Katalog) |
| Master Data | **User** | Master User, Master Role, Role & Permission | Login & hak akses seluruh modul (Q-25); Role dipilih dari **Master Role** (input manual, FR-64), hak menu per Role (FR-54 – FR-58) dan **Override Permission** per user (FR-65) `[MRO]` `input/Invoice & Pengajuan/2026-11-04-jawaban-pengajuan-biaya/` |
| Master Data | **Peserta & Trainer** | Master Peserta, Master Company, Master Trainer | Peserta: tidak lagi dipilih di Quotation `[TB-3]` · Company: Penjualan Program B2B, Quotation ke Company · Trainer: Penjualan Program (wajib) dan Surat Kerja Trainer |
| Master Data | Dokumen *(tidak disebut user; tetap sub sendiri `[USULAN]`, Q-39)* | Template Keterangan Quotation, Template Formulir Konfirmasi | Quotation (blok Keterangan Penawaran; Formulir Konfirmasi) `input/Master - Buat Program/2026-10-29-keterangan-quotation-default/` |

Q-18 (nama sub modul) terjawab oleh `input/Master - Buat Program/2026-11-07-sub-menu-master-data/`. As is: Atribut Produk, Produk, Customer, Biaya, Trainer, Dokumen, Pengguna (`docs/fr-nfr.md`).

**Pain point:** bagian 2 sheet *Requirements Product* masih kosong dan masih digali Labmania `[KLR-Q-13]`.

### 1.1 Cakupan

| Termasuk | Tidak termasuk (dokumen ini) |
|---|---|
| Master Jenis Produk, Jenis Sertifikat, Jenis Bidang, Varian Pelaksanaan | Master Program (costing baseline) → `fr-nfr-master-program.md` |
| Product → Paket Produk (status aktif/nonaktif) | Penjualan Program → `fr-nfr-penjualan-program.md`; Quotation & Sales Order (ditunda) |
| Katalog Produk — filter Bidang & Sertifikat, cetak PDF katalog | Modul Promosi |
| Master Peserta, Master Company | |
| Master Rate — item Fixed Cost & Variable Cost dengan Default Cost | |
| Data awal dari sheet *Data Mentah* dan *Cost Master* | |
| Navigasi modul (menu, search menu, kolom Action) | |

### 1.2 Istilah

| Istilah | Arti di dokumen ini |
|---|---|
| Product | Layar/proses menyusun kombinasi atribut. Bukan data tersimpan. |
| Paket Produk | Satu kombinasi Jenis Produk + Jenis Sertifikat + Jenis Bidang + Varian Pelaksanaan beserta Judul, hasil simpan dari Product. Diberi **Nomor Product**. |
| Master Rate | Daftar item biaya. Di Excel Program disebut *Cost Master* / *Master Cost*; isinya sama. |
| Default Cost | Harga standar satu item biaya. Satu item hanya punya **satu** Default Cost `[KPG-4]`, yang dapat disesuaikan di Master Program dan di Penjualan Program tanpa mengubah Master Rate `[MD-8]`. |
| Public / B2B | Jenis **penjualan**, dipilih di Penjualan Program (bukan di Master Program) `[QT-2]`, `[ARH-4]`; form Penjualan Program sama untuk keduanya `[JLR-1]`. Peserta = customer Public (di Quotation), Company = customer B2B (di Sales Order) `[JLR-2]`. |

---

## 2. Aktor

| Aktor | Peran | Sumber |
|---|---|---|
| Manajemen | Menyediakan & memelihara master atribut produk dan menyusun Product | `[SIPOC]` Supplier |
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
| UC-04 | Mencatat Varian Pelaksanaan (kembali di to be `[TB-1]`) | Manajemen | Master Data | Atribut Produk | Nama Varian | Sama dengan UC-01 untuk master Varian Pelaksanaan. | Data Varian Pelaksanaan tersimpan dengan kode otomatis dan status | Varian Pelaksanaan | FR-03, FR-07, FR-08, FR-09, FR-21 |
| UC-05 | Menyusun Product | Manajemen | Master Data | Produk | Judul; satu atau lebih baris kombinasi Produk + Bidang + Varian + Sertifikat | Aktor mengisi Judul secara manual, menambah baris kombinasi, memilih keempat atribut aktif per baris, menghapus baris yang tidak perlu, lalu menyimpan. Sistem menolak kombinasi yang keempat atributnya sama persis dengan paket lain atau baris lain. Setiap baris disimpan sebagai satu Paket Produk dengan Nomor Product sendiri. | Satu Paket Produk per baris, berstatus aktif, tampil di daftar | Paket Produk, Jenis Produk, Jenis Sertifikat, Jenis Bidang, Varian Pelaksanaan | FR-01, FR-02, FR-10 – FR-13, FR-22 – FR-24 |
| UC-06 | Menghasilkan Katalog Produk | *TBD (Q-06)* | Master Data | Produk | Paket Produk aktif; filter Bidang, Sertifikat, kata kunci (opsional) | Aktor membuka Katalog Produk, menyaring menurut Bidang dan/atau Sertifikat, lalu mencetak hasilnya. Sistem menyusun katalog dari Paket Produk aktif, dikelompokkan per Bidang, dan menghasilkan PDF katalog (sampul, daftar isi, isi, sampul belakang). | Tampilan katalog dan PDF Katalog Produk | Paket Produk, Jenis Produk, Jenis Bidang, Jenis Sertifikat | FR-14, FR-60 – FR-63 |
| UC-07 | Mencatat Peserta | *TBD (Q-06)* | Master Data | Customer | Nama, Gender, Instansi, Bidang, Provinsi, Kota/Kabupaten, No Telp/WA, Email (semua wajib); NPWP Instansi opsional | Aktor menambah peserta lewat form (Kota/Kabupaten tersaring menurut Provinsi), mengubah, atau menghapus peserta dari daftar. Peserta menjadi customer penjualan Public. To be: peserta quotation **tidak** dipilih dari Master Peserta `[TB-3]`; peserta Deal masuk otomatis saat Deal dan yang **Belum lengkap** dilengkapi aktor lewat Edit. | Data Peserta tersimpan dengan Kode Peserta | Peserta, Jenis Bidang, Provinsi, Kota/Kabupaten | FR-15 – FR-17 |
| UC-08 | Mencatat Item Variable Cost | *TBD (Q-06)* | Master Data | Biaya | Item, Kategori (Variable), Satuan (Peserta/Event atau Peserta/Day), Default Cost, Keterangan, COA | Aktor membuka tab Variable Cost pada Master Rate, menambah item (kode dibuat sistem), mengubah, atau mengaktifkan/menonaktifkan item. Item tidak dapat dihapus. Pilihan Satuan hanya Peserta/Event dan Peserta/Day. | Item Variable Cost tersimpan dengan kode otomatis, Default Cost, dan status | Rate Variable Cost, Satuan, COA | FR-18, FR-19, FR-25, FR-33 – FR-35 |
| UC-09 | Mencatat Item Fixed Cost | *TBD (Q-06)* | Master Data | Biaya | Item, Kategori (Fixed), Satuan (Event atau Day), Default Cost, Keterangan, COA | Sama dengan UC-08 pada tab Fixed Cost. Pilihan Satuan hanya Event dan Day. | Item Fixed Cost tersimpan dengan kode otomatis, Default Cost, dan status | Rate Fixed Cost, Satuan, COA | FR-18, FR-20, FR-25, FR-33 – FR-35 |
| UC-10 | Mengedit, Mengaktifkan / Menonaktifkan Paket Produk | Manajemen | Master Data | Produk | Paket Produk terpilih | Aktor memilih paket pada daftar lalu mengedit judul/atributnya (FR-43) atau mengubah statusnya menjadi aktif atau nonaktif. | Status Paket Produk berubah | Paket Produk | FR-10, FR-23 |
| UC-11 | Mencatat Company | *TBD (Q-06)* | Master Data | Customer | Nama, Jenis Instansi, Alamat, Provinsi, Kota, Telepon & Email kantor, PIC (Nama, Jabatan, Telp/WA, Email) — semua wajib; NPWP Instansi opsional | Aktor menambah company lewat form, mengubah, atau menghapus company dari daftar. Company menjadi customer penjualan B2B dan dipilih saat membuat Penjualan Program B2B. | Data Company tersimpan dengan Kode Company | Company, Provinsi, Kota/Kabupaten | FR-30 – FR-32 |
| UC-32 | Mencatat User | Manajemen | Master Data | Pengguna | Employee ID, Employee Name, **Role**, Gender, Phone Number, Employee Type, Status (wajib); NIK, Birth Place/Date, Religion, Email, Address, NPWP, Join Date, Job Desc. (opsional); akun login: Username, Password, Override Permission (opsional) | Aktor menambah user lewat form, memilih **Role** dari Master Role, mengubah, mengaktifkan/menonaktifkan, dan me-reset password. User yang diberi akun login dapat masuk ke sistem sesuai hak role-nya ditambah **Override Permission** bila diberi; user nonaktif tidak dapat login. | Data user tersimpan; akun login aktif bila diberi | User (employees), Akun (users), Role, Override Permission | FR-36 – FR-39, FR-65 |
| UC-49 | Mengatur Role & Permission | Superadmin, Admin (bila diberi hak) | Master Data | Pengguna | Role (dari Master Role); centang View / Add / Manage / Delete per menu | Aktor membuka Role & Permission, memilih **Atur Akses** pada sebuah role, mencentang hak setiap menu (dikelompokkan per modul; Pilih Semua per menu / semua modul, Kosongkan, cari), lalu Simpan. Role Superadmin selalu punya semua akses dan tidak dapat diubah; role Admin dapat dibatasi. Sidebar dan tombol aksi user dengan role itu mengikuti hak yang disimpan. | Hak akses role tersimpan | Role & Permission, Role, Master Role | FR-54 – FR-58 |
| UC-55 | Mencatat Role | Superadmin, Admin (bila diberi hak) | Master Data | Pengguna | Nama Role (wajib, unik) | Aktor menambah role baru, mengubah nama role, atau menghapus role yang tidak dipakai user. Role baru belum punya hak sampai diatur di Role & Permission. Role Superadmin terkunci. | Role tersimpan; tampil sebagai pilihan di Master User dan baris di Role & Permission | Role (groups) | FR-64 |
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
| FR-01 | Paket produk dibentuk dengan meng-combine **empat** master: **Jenis Produk**, **Jenis Sertifikat**, **Jenis Bidang**, dan **Varian Pelaksanaan** (Varian kembali di to be `[TB-1]`, menggantikan `input/Master - Buat Program/2026-10-29-npwp-instansi-hapus-varian/` butir 2). Paket produk tidak dapat dibuat tanpa memilih keempat atribut dari master tersebut. | `[REQ-1]`, `[TB-1]` |
| FR-02 | Keempat atribut **berdiri sendiri (tidak bertingkat)** — tidak ada relasi induk-anak di antara master tersebut. Pilihan pada satu atribut tidak membatasi pilihan atribut lain. | `[REQ-2]` |
| FR-03 | Setiap master memiliki **kode yang dibuat otomatis oleh sistem** dengan format penomoran standar dari Labmania. Format per master menunggu Q-01. **To be 12-11-2026:** kode **tidak direset tiap tahun** (data master jarang berubah); prefix dan jumlah digit tetap usulan mockup (Q-01). | `[WISH-1]`, `[MD:*]`, `[KLR-Q-10]`, `[JPT-7]` |

### 4.2 Master Atribut Produk

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-04 | **Master Jenis Produk.** Daftar menampilkan: No, Kode Jenis Product, Jenis Product, Status, Action. Form tambah: Kode Jenis Product (otomatis), Jenis Product (teks bebas). Data awal: Pelatihan, Sertifikasi. | `[MD:Jenis Product]`, `[RAW]`, `[KLR-Q-02]` |
| FR-05 | **Master Jenis Sertifikat.** Daftar: No, Kode Sertifikat, Jenis Sertifikat, Status, Action. Form tambah: Kode Sertifikat (otomatis), Jenis Sertifikat (teks bebas). Nilainya adalah jenis sertifikat (mis. Sertifikat BNSP, Sertifikat KAN ISO 29993) sesuai *Data Mentah* — **bukan** "Pelatihan / Sertifikasi" seperti tertulis di REQ-3. Nama master ditampilkan **Jenis Sertifikat** (sebelumnya Jenis Sertifikasi) `input/Master - Buat Program/2026-10-29-edit-master-sertifikat-cari/`; nama tabel ERD `ms_jenis_sertifikasi` belum diubah. | `[MD:Jenis Sertifikat]`, `[RAW]`, `[KLR-Q-02]` |
| FR-06 | **Master Jenis Bidang** — bidang keilmuan/standar yang dibahas di pelatihan (mis. ISO 17025, Analisis Kimia, Kalibrasi). Daftar: No, Kode Bidang, Jenis Bidang, Status, Action. Form tambah: Kode Bidang (otomatis), Jenis Bidang (teks bebas). Jenis Bidang juga dipakai untuk **mengelompokkan peserta** (FR-16). | `[REQ-4]`, `[MD:Jenis Bidang]`, `[KLR-Q-10]` |
| FR-07 | **Master Varian Pelaksanaan** — cara melaksanakan suatu jenis bidang. Daftar: No, Kode Varian, Varian, Status, Action. Form tambah: Kode Varian (otomatis), Jenis Varian (teks bebas). Kembali di to be seperti sebelum 29-10-2026 `[TB-1]`. | `[REQ-5]`, `[MD:Varian Pelaksanaan]`, `[KLR-Q-10]`, `[TB-1]` |
| FR-08 | Kolom Action pada daftar master FR-04 s.d. FR-07 berisi opsi **Edit** dan **Aktifkan / Nonaktifkan** (lewat menu gear, FR-28). Data master **tidak dapat dihapus**; tombol Delete pada mockup diganti Nonaktifkan. Hal yang sama berlaku untuk **Paket Produk** (Produk): Edit dan Aktifkan / Nonaktifkan (FR-43) `input/Master - Buat Program/2026-10-29-edit-master-sertifikat-cari/`. | `[MD:*]`, `[KLR-Q-05]`, `[RMD-4]` |
| FR-09 | Field nama pada setiap master (FR-04 s.d. FR-07) wajib diisi sebelum disimpan. | `[USULAN]` |
| FR-21 | Master berstatus **nonaktif** tidak muncul sebagai pilihan pada form Product dan form Peserta. Paket Produk dan Peserta yang sudah memakai master tersebut tetap menampilkan nilainya. | `[KLR-Q-05]` + `[USULAN]` (perilaku nonaktif) |

### 4.3 Product & Paket Produk

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-10 | **Daftar Paket Produk** menampilkan satu baris per paket dengan kolom: No, Nomor (Nomor Product), Judul Topik, **Produk, Bidang, Varian, Sertifikat** (satu kolom per atribut, urutan sama dengan tabel kombinasi FR-12), Status, Action (menu gear: **Edit** (FR-43) dan Aktifkan / Nonaktifkan). Atribut yang masternya sudah nonaktif tetap ditampilkan dengan penanda "(nonaktif)". | `[MD:Product (Combine)]`, `[KLR-Q-03]`, `[KLR-Q-04]`, `[RMD-2]` + `[USULAN]` (pemecahan kolom) |
| FR-11 | **Form Tambah Product** berisi field **Judul** (teks bebas, diinput manual oleh pengguna). | `[REQ-6]`, `[MD:Product (Combine)]` |
| FR-12 | Form Tambah Product memiliki **tabel kombinasi** berkolom: #, Produk, Bidang, Varian, Sertifikat, Action. Setiap baris memilih (select) satu nilai aktif dari master Jenis Produk, Jenis Bidang, Varian Pelaksanaan, dan Jenis Sertifikat `[TB-1]`. Setiap dropdown punya **kolom cari** (terutama Bidang yang daftarnya panjang) `input/Master - Buat Program/2026-10-29-edit-master-sertifikat-cari/`, NFR-10. Pengguna dapat menambah baris dan **menghapus** baris sebelum menyimpan. | `[MD:Product (Combine)]`, `[KLR-Q-03]` |
| FR-13 | Saat disimpan, **setiap baris kombinasi menjadi satu Paket Produk** yang membawa Judul dari form, dan masing-masing mendapat **Nomor Product** otomatis (format Q-01). Satu kali simpan dengan n baris menghasilkan n paket produk. | `[KLR-Q-03]`, `[WISH-1]` |
| FR-22 | **Keunikan kombinasi:** antar paket produk minimal **satu** dari empat atribut (Produk, Bidang, Varian, Sertifikat) harus berbeda `[TB-1]`. Sistem menolak penyimpanan bila ada baris yang kombinasinya sama persis dengan paket yang sudah ada atau dengan baris lain di form yang sama, dan menunjukkan baris yang bentrok. Seluruh form gagal disimpan; tidak ada paket yang tersimpan sebagian. **To be 12-11-2026:** cek hanya terhadap Product **aktif**; Product nonaktif tidak dihitung. Mengaktifkan kembali Product ditolak bila kombinasinya sudah dipakai Product aktif lain `[USULAN]`. | `[KLR-Q-12]` + `[USULAN]` (perilaku gagal-semua), `[JPT-9]` |
| FR-23 | Paket Produk memiliki status **Aktif / Nonaktif** yang dapat diubah dari daftar. Paket baru tersimpan berstatus Aktif. | `[KLR-Q-04]` + `[USULAN]` (default Aktif) |
| FR-24 | Paket Produk berstatus Aktif adalah output yang **siap dibuat menjadi Product Costing**, yaitu dapat dipilih saat membuat **Master Program**. | `[KLR-SIPOC]`, `[MD-7]` + `[USULAN]` (hanya paket aktif) |
| FR-43 | **Edit Paket Produk** lewat menu gear daftar Paket Produk: form berisi Nomor Product (tetap), **Judul**, dan keempat atribut (dropdown dapat dicari; nilai yang masternya sudah nonaktif tetap tampil sebagai pilihan terpilih). Cek keunikan FR-22 berlaku, tidak termasuk paket itu sendiri. Master Program yang memakai paket ikut menampilkan judul/atribut baru. | `input/Master - Buat Program/2026-10-29-edit-master-sertifikat-cari/` + `[USULAN]` (field yang dapat diubah, efek ke Master Program) |

### 4.4 Katalog Produk

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-14 | Dari Paket Produk sistem menghasilkan **Katalog Produk** untuk Promosi. Isi katalog = Paket Produk berstatus **Aktif** (FR-24); katalog **tidak disimpan** sebagai data tersendiri, melainkan disusun dari `paket_produk` setiap kali dibuka/dicetak. Format sebelumnya ditunda (Q-09); kini dibuat `input/Master - Buat Program/2026-11-05-katalog-produk/`. | `[WISH-2]`, `[SIPOC]`, `[KLR-Q-09]`, `input/Master - Buat Program/2026-11-05-katalog-produk/` + `[USULAN]` (hanya paket aktif) |
| FR-60 | Halaman **Katalog Produk** dapat **difilter berdasarkan Bidang dan Sertifikat** (dapat dipakai bersamaan), ditambah kolom cari (Judul, Nomor, atribut). Pilihan filter hanya Bidang/Sertifikat yang dipakai paket aktif, disertai jumlah paketnya. | `input/Master - Buat Program/2026-11-05-katalog-produk/` + `[USULAN]` (kolom cari, isi pilihan filter) |
| FR-61 | Tampilan layar mengelompokkan paket **per Bidang** (urut nama Bidang; di dalamnya urut Judul lalu Sertifikat). Tiap paket tampil sebagai kartu: Jenis Produk, Nomor Product, Judul, **Varian** (to be `[TB-1]` + `[USULAN]`), Sertifikat, dan **Lingkup** (FR-67; bila kosong: "Lingkup belum diisi.") dengan tombol **Isi lingkup** / **Ubah lingkup (n)**. Kolom cari juga mencari teks Lingkup. Bila tidak ada hasil, tampil pesan agar filter diubah. | `input/Master - Buat Program/2026-11-05-katalog-produk/`, `input/Master - Buat Program/2026-11-07-katalog-lingkup/` + `[USULAN]` |
| FR-62 | Tombol **Cetak Katalog (PDF)** menghasilkan PDF A4 bergaya katalog produk dari **hasil filter yang sedang tampil**: (1) sampul depan — logo, "Katalog Produk", tahun, jumlah paket/bidang/jenis sertifikat, filter yang dipakai, tanggal cetak; (2) Daftar Isi per Bidang dengan nomor halaman dan daftar jenis sertifikat; (3) isi per Bidang, kartu paket 2 kolom (Jenis Produk, Nomor, Judul, Bidang, **Varian** (to be `[USULAN]`), **Lingkup** berupa daftar butir bila ada (FR-68), Sertifikat), Bidang yang berlanjut ke halaman berikut diberi "(lanjutan)", tiap halaman bernomor; (4) sampul belakang berisi kontak LabMania. Cetak ditolak bila hasil filter kosong. | `input/Master - Buat Program/2026-11-05-katalog-produk/` + `[USULAN]` (tata letak) |
| FR-63 | Setiap cetak Katalog Produk dicatat di **Activity Log** (modul Master Data, sub Katalog Produk, aksi Cetak, keterangan berisi jumlah paket dan filter). | `[USULAN]` |
| FR-67 | **Isi Lingkup per Paket Produk** (to be) dari halaman Katalog Produk: tombol **Isi lingkup** / **Ubah lingkup (n)** di kartu paket membuka form *Lingkup: [Judul]* yang menampilkan Nomor dan keempat atribut paket. Isian berupa **daftar item, boleh lebih dari satu**: tiap baris satu item teks (maks. 200 karakter), bernomor urut; **+ Tambah item lingkup** dan tombol Enter di baris menambah baris baru; **Hapus** per baris; maksimal **20 item** per paket. Baris kosong diabaikan; item yang sama (tanpa beda huruf besar/kecil) dalam satu paket ditolak dengan pesan pada baris itu. Simpan dengan semua baris kosong = Lingkup dikosongkan. Urutan tampil = urutan input. Perubahan dicatat di Activity Log sebagai perubahan Paket Produk (modul Master Data, sub Product, aksi Edit, field **Lingkup**, nilai lama → baru) `[USULAN]`. **To be 12-11-2026:** boleh juga diisi dari form lain (Q-38); yang boleh mengubah = role yang punya hak ke master itu (diatur Labmania di Role & Permission). | `input/Master - Buat Program/2026-11-07-katalog-lingkup/` + `[USULAN]` (tempat input di Katalog, batas 20 × 200, tolak kembar), `[JPT-12]` |
| FR-68 | **Lingkup di PDF katalog** (to be): kartu paket di halaman isi menampilkan judul kecil "Lingkup" dan daftar butir item sesuai urutan; paket tanpa Lingkup tetap tampil tanpa blok itu. Pembagian halaman memperhitungkan tinggi kartu yang bertambah karena Lingkup, agar kartu tidak terpotong antarhalaman. Daftar Isi menyebut bahwa setiap paket mencantumkan lingkupnya bila sudah diisi. | `input/Master - Buat Program/2026-11-07-katalog-lingkup/` + `[USULAN]` (tata letak) |
| FR-69 | **Harga "start from" per Paket Produk** `[MMB-5]`: form dari kartu Katalog Produk menjadi **Lingkup & Harga** dengan isian **Harga start from** (rupiah, opsional, pemisah ribuan). Harga ini **perkiraan untuk promosi, bukan harga final**: tidak dipakai di Master Program, Penjualan Program, quotation, atau invoice. Kartu katalog menampilkan **"Start from Rp …"** dengan keterangan "perkiraan, belum harga final" (bila kosong: "Harga start from belum diisi"); PDF katalog menampilkan "Start from Rp …" dengan catatan "*harga perkiraan". Perubahan tercatat di Activity Log sebagai Edit Paket Produk, field Harga start from. | `[MMB-5]` |

### 4.5 Master Peserta

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-15 | **Daftar Peserta** menampilkan: No, Kode Peserta, Nama Peserta, Bidang, Lokasi, Action (menu gear: Edit / Delete). Data diambil dari pengisian form Master Peserta. **To be** `[TB-3]`: peserta quotation **tidak** diambil dari Master Peserta. Data peserta quotation berasal dari isian Konfirmasi Kehadiran lewat link atau tambah manual oleh Sales, dan disimpan di quotation itu (`docs-to-be/fr-nfr-quotation.md` QTN-FR-21 – QTN-FR-28). Aturan as is "peserta yang ditambah dari form Quotation Personal masuk Master Peserta" **tidak berlaku** di to be. **Peserta Deal disimpan ke Master Peserta saat Deal** `input/Master - Buat Program/2026-11-11-peserta-deal-ke-master-peserta/` (`docs-to-be/fr-nfr-quotation.md` QTN-FR-33): peserta baru mendapat Kode Peserta otomatis, peserta yang sudah ada (cocok NIK, email, atau No. WhatsApp) hanya dilengkapi field yang kosong. Isian link tidak berubah, sehingga sebagian field wajib FR-16 (Gender; Bidang dan Provinsi/Kota bila tidak dapat diturunkan) bisa kosong: daftar menampilkan tanda **Belum lengkap** dan admin Labmania melengkapinya lewat **Edit**. Tambahan daftar `[USULAN]`: filter **Kelengkapan** (Semua / Lengkap / Belum lengkap) dan keterangan asal "Dari Deal [No. Quotation]" di bawah nama. Aturan as is "peserta yang sudah ada di quotation tidak dapat dihapus" berlaku lagi untuk peserta yang terhubung ke quotation lewat Deal `[USULAN]`. **To be 12-11-2026:** Peserta **boleh dihapus**; peserta yang sudah terhubung ke quotation tidak dapat dihapus (pesan menyebut nomor quotation) `[USULAN]`. | `[MD:Master Peserta]`, `[RMD-4]`, `[MD-9]`, `[MD-FORM:Master Peserta]`, `[JPT-8]` |
| FR-16 | **Form Tambah Peserta** — **semua field wajib** (*): Nama Peserta (teks bebas), Gender (pilihan Laki-laki / Perempuan), Instansi/Perusahaan (teks bebas), Bidang (pilihan dari master **Jenis Bidang** aktif), Lokasi Provinsi (pilihan), Kota/Kabupaten (pilihan, **tersaring menurut Provinsi** yang dipilih), No Telp/WhatsApp (teks), Email (teks). REQ-7 hanya mewajibkan Nama, Instansi, Bidang, dan Provinsi; Labmania memutuskan semua field wajib. Tambahan: **Company** (opsional, pilihan Master Company aktif, "Tanpa company (personal)"); bila diisi, Instansi/Perusahaan = nama company (terkunci). Dipakai Quotation ke Company: hanya peserta company itu yang dapat dipilih `input/Master - Buat Program/2026-10-28-quotation-company-personal/` + `[USULAN]` (field Company). Tambahan: **NPWP Instansi** (opsional, 15/16 digit, NFR-06) `input/Master - Buat Program/2026-10-29-npwp-instansi-hapus-varian/`; bila Company dipilih, terisi NPWP Instansi company dan terkunci `[USULAN]`. **To be** `input/Master - Buat Program/2026-11-11-peserta-deal-ke-master-peserta/`: field **NIK** (opsional, terisi dari isian link bila ada); peserta dari Deal yang Belum lengkap dibuka lewat Edit dengan field yang sudah terisi dari link, dan semua field wajib di atas tetap divalidasi saat disimpan, sehingga setelah disimpan peserta menjadi Lengkap. | `[MD:Master Peserta]`, `[REQ-4]`, `[REQ-7]`, `[KLR-Q-07]`, `[KPG-2]` |
| FR-17 | Peserta memiliki **Kode Peserta** yang dibuat otomatis oleh sistem (format Q-01). | `[WISH-1]`, `[MD:Master Peserta]` |

### 4.6 Master Company

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-30 | **Master Company** menyimpan data perusahaan/instansi yang menjadi **customer penjualan B2B**. Company **aktif** dipilih (wajib) di **Penjualan Program B2B** (`fr-nfr-penjualan-program.md` PJ-FR-22) dan diteruskan ke Sales Order, satu SO untuk satu Company. Company baru dibuat di sini, tidak dari form Penjualan Program `[JPP-2]`. | `[REQ-8]`, `[MD-10]`, `[ARH-4]`, `[JLR-2]`, `[JPP-2]`, `[JPP-3]` |
| FR-31 | **Form Tambah Company** dibagi lima bagian: **Identitas** — Nama Company (wajib, teks bebas), Jenis Instansi (pilihan: Swasta, BUMN/BUMD, Pemerintah, Pendidikan, Lainnya); **Alamat** — Alamat lengkap (teks), Provinsi (pilihan), Kota/Kabupaten (pilihan, tersaring menurut Provinsi); **Kontak kantor** — Telepon, Email; **PIC** — Nama, Jabatan, Telp/WA, Email; **Pajak** — **NPWP Instansi** (label diganti dari NPWP `input/Master - Buat Program/2026-10-29-npwp-instansi-hapus-varian/`). **Semua field wajib kecuali NPWP Instansi.** **To be 12-11-2026:** **NPWP Company wajib** (15 atau 16 digit), menggantikan "semua wajib kecuali NPWP" (Q-19). NIK dan NPWP Instansi peserta tetap opsional. | `[REQ-8]`, `[MD-10]`, `[MD-FORM:Master Company]` + `[USULAN]` (Kota tersaring Provinsi, mengikuti FR-16), `[JPT-41]` |
| FR-32 | **Daftar Company** menampilkan: No, Kode Company, Nama Company, Jenis Instansi, Lokasi, Status, Action (menu gear: Edit / Aktifkan–Nonaktifkan / Delete). Company memiliki **Kode Company** otomatis (format Q-01). Company **nonaktif** tidak muncul di pilihan Penjualan Program `[JPP-3]` (memakai status aktif tabel `customer`); Delete masih mengikuti Q-16. **To be 12-11-2026:** Company **boleh dihapus** selama belum dipakai Penjualan Program, Quotation, atau peserta; bila sudah dipakai, hapus ditolak dengan pesan dan Company cukup dinonaktifkan `[USULAN]`. | `[MD-FORM:Master Company]`, `[JPP-3]`, `[JPT-8]` |

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
| FR-26 | Data pada sheet *Data Mentah Struktur Product* dan *Data Mentah Master Rate* (`[MD-RATE]`, isi sama dengan *Cost Master*) dimuat sebagai **data awal** master Jenis Produk, Jenis Sertifikat, Jenis Bidang, Varian Pelaksanaan `[TB-1]`, Fixed Cost, dan Variable Cost (lampiran bagian 7), termasuk Default Cost. Item ganda dimuat satu kali; baris judul kelompok (mis. "Fix Cost / Event") dan baris Total tidak dimuat. | `[RAW]`, `[CM]`, `[KLR-Q-11]`, `[KPG-6]` |

### 4.9 Navigasi & Tampilan

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-27 | Sidebar menampilkan menu **Dashboard** paling atas (halaman pertama setelah login, FR-40), lalu dua modul, **Master Data** dan **Penjualan Program**, masing-masing dapat dibuka/ditutup. Modul Penjualan Program berisi sub modul **Master Program** (menu Daftar Master Program) lalu **Penjualan Program** (menu Daftar Penjualan Program) `[GMP-1]`. Di dalam modul, menu diurutkan per sub modul (tabel di bagian 1), tetapi **label sub modul tidak ditampilkan** — menu langsung di bawah nama modul `[SBL-1]`. Breadcrumb menampilkan Modul / Menu. **To be** `[SMD]`: modul **Master Data** menampilkan **sub menu** Product, User, Peserta & Trainer (dan Dokumen, Q-39) yang **dapat dibuka/ditutup**; sub yang berisi menu aktif terbuka otomatis, saat search menu semua sub yang punya hasil terbuka, dan breadcrumb menjadi Modul / Sub / Menu `[USULAN]` (perilaku). Modul lain tetap tanpa label sub `[USULAN]`. | `[RMD-1]`, `[KPG-7]`, `[GMP-1]`, `[SBL-1]`, `[SMD]` + `[USULAN]` |
| FR-28 | Kolom **Action** pada setiap tabel daftar hanya menampilkan **ikon gear**. Opsi aksi (Edit, Aktifkan/Nonaktifkan, Delete — sesuai FR masing-masing daftar) baru muncul sebagai menu setelah gear diklik. Menu tertutup bila pengguna mengklik di luar menu, menekan Esc, atau menggulir halaman. | `[RMD-4]` + `[USULAN]` (perilaku tutup menu) |
| FR-29 | Sidebar memiliki kolom **search menu** tepat di bawah logo Labmania. Pencarian menyaring menu dari **semua modul** berdasarkan nama menu saat pengguna mengetik `[SBL-1]`; modul tanpa hasil disembunyikan dan bila tidak ada yang cocok ditampilkan "Menu tidak ditemukan". Enter membuka hasil pertama, Esc mengosongkan pencarian. | `[RMD-5]` + `[USULAN]` (Enter/Esc, pencocokan sub modul) |
| FR-66 | Sidebar memiliki tombol **sembunyikan sidebar** di kanan logo Labmania `input/Master - Buat Program/2026-11-05-sidebar-hide/`. Di layar lebar, sidebar disembunyikan penuh sehingga area kerja melebar, lalu tombol menu di kiri topbar memunculkannya kembali. Di layar sempit (sidebar berupa drawer), tombol yang sama menutup drawer. Kedua tombol dapat dipakai dengan keyboard, punya label yang terbaca pembaca layar, dan fokus berpindah ke tombol lawannya. Status tersembunyi berlaku selama halaman terbuka, tidak disimpan per user. | `input/Master - Buat Program/2026-11-05-sidebar-hide/` + `[USULAN]` (perilaku layar lebar/sempit, tidak disimpan) |


### 4.10 Master User

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-36 | **Master User** (sub modul **Pengguna**, to be: sub menu **User** `[SMD]`, modul Master Data) menyimpan data karyawan Labmania beserta **akun login** ke sistem. Memakai tabel existing aplikasi: data karyawan di `employees`, akun di `users`; **role** = record `groups` yang dipilih dari **Master Role** (FR-64) `[MRO]`, relasi akun lewat `user_groups` (Q-25). Hanya **Manajemen** dan **Superadmin** yang dapat membuka Activity Log (`docs/fr-nfr-activity-log.md` LOG-FR-09) `input/Master - Buat Program/2026-10-30-activity-log-akses-retensi/`. | `[USR-1]`, `[RNP]`, `[MRO]` + `[USULAN]` (sub modul, tabel) |
| FR-37 | **Form Tambah/Edit User** mengikuti format acuan, dua kolom. Kiri: **Employee ID** (wajib, unik, tidak dapat diubah setelah disimpan), **Employee Name** (wajib), Personal ID Number / NIK (opsional, 16 digit), Birth Place, Birth Date, **Role** (wajib, pilihan dari Master Role kecuali Superadmin) `[MRO]`, **Gender** (wajib, pilihan), Religion (pilihan). Kanan: **Phone Number** (wajib, format `+62…`, 9–15 digit), Email (format email), Address, NPWP (15/16 digit), Join Date, **Employee Type** (wajib, pilihan), Job Desc., **Status** (wajib, radio Active / Non Active, default Active). Tombol Save / Close. Field **Division** dan **Jabatan (Position)** **tidak dipakai lagi**, diganti Role `[MRO]`; field **Department tidak dipakai** `[RNP]`. Role wajib juga untuk user tanpa akun login dan disimpan di kolom baru `employees.id_group` `[USULAN]` (Q-37). | `[USR-1]`, `[RNP]`, `[MRO]` + `[USULAN]` (validasi format, Employee ID terkunci, Role wajib untuk semua user) |
| FR-38 | Form memuat bagian tambahan **Akun Login**: centang "dapat login ke sistem"; bila dicentang, **Username** (wajib, unik, huruf kecil/angka/titik/garis bawah, 3–50 karakter), **Password** + **Konfirmasi Password** (wajib saat akun dibuat, minimal 8 karakter, harus sama; saat edit dikosongkan bila tidak diganti), dan **Override Permission** (opsional, FR-65). Saat disimpan, akun dihubungkan ke Role yang dipilih di form (`user_groups`); mengganti Role memindahkan user ke role itu. Pengecualian: akun **Superadmin** (developer) memegang role bawaan **Superadmin** (FR-56) yang tampil hanya-baca dan tidak dapat diganti; Superadmin tidak muncul di pilihan Role user lain `[RNP2]`. Password disimpan ter-hash, tidak pernah ditampilkan. User tanpa akun (mis. trainer lepas) tetap dapat dicatat; Override Permission hanya untuk user berakun. Role tidak lagi terbentuk otomatis dari Division + Jabatan `[MRO]`. | `[RNP]`, `[RNP2]`, `[MRO]` + `[USULAN]` (tambahan di luar form acuan, `[USR-1]` "kalo ada yang kurang ditambahin") |
| FR-39 | **Daftar User**: No, Employee ID, Nama (+ email/telepon), **Role** (+ Employee Type), **Akun login** (username dan penanda "Override n hak" bila user punya Override Permission, atau "Tanpa akun"), Status, Action (menu gear: Edit, Aktifkan/Nonaktifkan, Reset Password bila punya akun). Filter **Role** dan Status; pencarian ID, nama, username, email, role. User **tidak dihapus**, hanya dinonaktifkan; user nonaktif **tidak dapat login**. Setiap tambah/ubah/aktif-nonaktif/reset password dicatat dengan `write_log()` (tanpa nilai password), termasuk perubahan Role dan Override Permission. | `[USR-1]`, `[STD]`, `[RNP]`, `[MRO]` + `[USULAN]` (kolom, tanpa hapus, reset password) |


### 4.11 Dashboard

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-40 | **Dashboard** adalah halaman depan aplikasi (dibuka pertama setelah login, menu paling atas di sidebar). Tanpa tombol cepat buat Master Program / Penjualan Program `[DSH-2]`. | `[DSH-1]`, `[DSH-2]` |
| FR-41 | Dashboard menampilkan **KPI**, semuanya **total** seluruh data tanpa filter periode `[DSH-3]`: (1) jumlah Penjualan Program **Final** (rincian Public / B2B); (2) **pendapatan rencana** = Σ harga jual / peserta × target peserta (uang masuk) dan jumlah peserta direncanakan; (3) **profit rencana** = Σ pendapatan − HPP (fixed + variable cost), + margin %; (4) jumlah Master Program + Penjualan Program yang masih **Draft**. Pendapatan & profit = angka rencana Penjualan Program Final (rumus `fr-nfr-master-program.md` bagian 8); data aktual menyusul modul Quotation / Sales Order. Setiap kartu **dapat diklik** dan membuka popup **rincian** `[DSH-4]`: sumber data (menu dan status yang dihitung), rumus, daftar data penyusun angka (Final: penjualan & target peserta; Pendapatan: harga jual × target peserta; Profit: pendapatan, Fixed Cost, Variable Cost, profit, margin; Draft: Master Program & Penjualan Program Draft), dan baris total = angka kartu. Baris di popup dapat diklik untuk membuka datanya. | `[DSH-1]`, `[DSH-3]`, `[DSH-4]` + `[USULAN]` (isi KPI & kolom rincian) |
| FR-42 | Dashboard menampilkan panel: (1) **Jadwal pelaksanaan terdekat** — maks. 5 Penjualan Program **Final** `[DSH-3]` yang tanggal selesainya belum lewat, urut tanggal mulai (tanggal, program, nomor, Company/lokasi, jenis, peserta); (2) **Perlu tindakan** — Master Program/Penjualan Program Draft, penjualan rugi, titik impas tidak tercapai atau di atas rencana peserta. Setiap baris dapat diklik untuk membuka datanya. Panel Public vs B2B dan Ringkasan Master Data **tidak dipakai** `[DSH-2]`. Hak lihat per Group Akses: Q-25. | `[DSH-1]` – `[DSH-3]` + `[USULAN]` (isi panel) |

---

### 4.12 Master Trainer

Sumber: `input/Master - Buat Program/2026-10-29-master-trainer/`, format `input/Master - Buat Program/Master Trainer Ivan Sarifudin.xlsx` (5 sheet: *Identitas Trainer*, *Pelatihan dan Seminar*, *Pengalaman Kerja*, *Pengalaman Mengajar*, *Lampiran Dokumen*).

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-44 | **Daftar Master Trainer** menampilkan: No, ID Trainer, Nama Trainer (+ telepon), Bidang Keahlian, Riwayat (jumlah baris tiap daftar), jumlah Penjualan Program yang memakai trainer, Status, Action (menu gear: **Edit**, **Lihat CV**, **Aktifkan / Nonaktifkan**). Pencarian ID, nama, bidang keahlian; filter status. | `input/Master - Buat Program/2026-10-29-master-trainer/` + `[USULAN]` (kolom) |
| FR-45 | **Form Trainer** bertab mengikuti sheet Excel. **Identitas Trainer**: ID Trainer (otomatis, format Q-01), **Nama lengkap** (wajib), Tempat lahir, Tanggal lahir (Excel: satu sel "Tempat, tanggal lahir" — dipecah `[USULAN]`), Alamat rumah, No. telepon rumah/HP; **Pendidikan formal** — SMU/sederajat, Diploma, Sarjana, Pascasarjana (teks); **Bidang keahlian** (daftar, satu per baris); Pengalaman lainnya; Tempat & Tanggal pengesahan CV; Nama pada tanda tangan. Empat tab daftar dengan baris dapat ditambah/dihapus: **Pelatihan & Seminar** (Judul, Penyelenggara/Tempat, Waktu), **Pengalaman Kerja** (Jabatan, Institusi/Perusahaan, Tahun — boleh rentang, mis. "2018–sekarang"), **Pengalaman Mengajar** (Topik Pembelajaran, Tempat, Tahun), **Lampiran Dokumen** (Dokumen / Sertifikat, Tahun, **File** unggahan PDF/JPG/PNG `[USULAN]`, Q-27). Baris kosong dibuang saat simpan; baris berisi wajib punya kolom pertama. Field wajib lain menunggu Q-26. | `input/Master - Buat Program/2026-10-29-master-trainer/`, `input/Master - Buat Program/Master Trainer Ivan Sarifudin.xlsx` + `[USULAN]` (pemecahan tempat/tanggal lahir, file lampiran, validasi baris) |
| FR-46 | Trainer **tidak dapat dihapus**, hanya **Aktifkan / Nonaktifkan** (seperti FR-08). Trainer nonaktif tidak muncul di pilihan Penjualan Program; Penjualan Program yang sudah memakainya tetap menampilkan namanya. | `[USULAN]` (mengikuti FR-08, FR-21) |
| FR-47 | **Lihat CV**: tampilan baca seluruh data trainer dalam urutan Excel (identitas, pendidikan, keahlian, pengesahan, lalu empat daftar bernomor). Trainer dipilih di **Penjualan Program** — satu trainer per event, **wajib di to be** (`docs-to-be/fr-nfr-penjualan-program.md` PJ-FR-23), dan menjadi tujuan **Surat Kerja Trainer** (`docs-to-be/fr-nfr-sales-order.md` SO-FR-11 – SO-FR-14). | `input/Master - Buat Program/2026-10-29-master-trainer/`, `[K3M-1]` + `[USULAN]` (Lihat CV) |
| FR-59 | **Akun login trainer**: form Trainer (tab Identitas) memuat pilihan **Akun login (Master User)** — user ber-akun yang menjadi akun trainer itu (opsional; satu akun untuk satu trainer). Dipakai agar trainer termasuk **tim program** Penjualan Program yang ia latih dan dapat mengajukan biaya (`docs/fr-nfr-pengajuan-biaya.md` PB-FR-19). Kolom `ms_trainer.id_user`. Trainer lepas tanpa akun tetap dapat dicatat. | `[MAM2]` + `[USULAN]` (opsional, satu akun satu trainer) |

### 4.13 Template Keterangan Quotation

Sumber: `input/Master - Buat Program/2026-10-29-keterangan-quotation-default/`. Dipakai `docs/fr-nfr-quotation.md` QTN-FR-17.

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-48 | **Template Keterangan Quotation** menyimpan butir teks per **kategori**: Syarat keikutsertaan, Termasuk, Tidak termasuk, Dokumen persyaratan, Klausul tanda tangan (Q-28). Setiap butir: Kode (otomatis, format Q-01), Kategori (wajib), **Teks** (wajib, satu butir), **Berlaku untuk** (Semua / Public / B2B, default Semua), **Default** (ya/tidak — otomatis masuk quotation baru), Status Aktif/Nonaktif. | `input/Master - Buat Program/2026-10-29-keterangan-quotation-default/` + `[USULAN]` (kategori, Berlaku untuk) |
| FR-49 | **Daftar** butir: No, Kode, Kategori, Teks (penanda "contoh" bila isi awal dari analis), Berlaku untuk, Default, Status, Action (Edit, Aktifkan / Nonaktifkan); diurutkan per kategori; filter kategori & status; pencarian kode/teks. Butir tidak dapat dihapus, hanya dinonaktifkan. | `[USULAN]` (mengikuti FR-08) |
| FR-50 | Perubahan master hanya berlaku untuk quotation **baru**: quotation menyimpan salinan teksnya sendiri (QTN-FR-17), sehingga PDF quotation lama tidak berubah. Isi awal: dari Template Penawaran (Termasuk: Materi, Sertifikat pelatihan, Pajak; dua kalimat klausul tanda tangan), dari S&K (Dokumen: formulir konfirmasi — Public, karena in-house tidak memerlukannya), dan butir **contoh** usulan analis (Q-30). | `input/Master - Buat Program/2026-10-29-keterangan-quotation-default/`, `input/Master - Buat Program/Template Penawaran Labmania.pdf`, `input/Master - Buat Program/SYARAT & KETENTUAN KEBIJAKAN LAYANAN PEMBELAJARAN.pdf` + `[USULAN]` |

### 4.14 Template Formulir Konfirmasi

Sumber: `input/Master - Buat Program/2026-10-29-template-formulir-konfirmasi/` (`input/Master - Buat Program/Tamplate Formulir Konfirmasi Workshop.doc`, `input/Master - Buat Program/Template Formulir Konfirmasi Training Sertifikasi .doc`, `input/Master - Buat Program/Template Formulir Konfirmasi Resertifikasi.doc`). Dipakai `docs/fr-nfr-quotation.md` QTN-FR-16, QTN-FR-19.

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-51 | **Template Formulir Konfirmasi**: Kode (otomatis), **Nama template** (wajib), **Label kegiatan** (wajib; dipakai di catatan "subject email … kegiatan <label> dengan judul …"), **Default untuk Jenis Produk** (opsional, master Jenis Produk aktif), **Isian** (minimal 1, dari daftar: Nama Lengkap Peserta*, NIK, Jabatan, Nama Instansi, Alamat Instansi, Nomor Hp, Nomor WhatsApp, Alamat Email Aktif, Masa Pengalaman Bekerja Sesuai Skema, LSP, Tanggal Habis Sertifikat), **Kalimat pernyataan 1** (wajib; penanda {judul} {tanggal} {waktu} {durasi} {lokasi} diisi otomatis, `**teks**` = tebal), **Catatan** (satu butir per baris; penanda {email} {telp} {kegiatan} {judul}), Status Aktif/Nonaktif. Pernyataan 2 (membaca & menyetujui S&K), kop, dan kode dokumen `FR/LMI-TR-01/02/Ed.Rev-1.0` sama untuk semua template. | `input/Master - Buat Program/2026-10-29-template-formulir-konfirmasi/` + `[USULAN]` (bentuk field, penanda) |
| FR-52 | **Data awal** tiga template sesuai file: **Workshop** (default Pelatihan; 8 isian termasuk Masa Pengalaman; "akan hadir … tanggal … pukul …"), **Training & Sertifikasi** (default Sertifikasi; isian sama; label kegiatan "Training dan Sertifikasi"), **Resertifikasi** (tanpa default — dipilih manual; isian + Nomor Hp, LSP, Tanggal Habis Sertifikat, tanpa Masa Pengalaman; "akan mengikuti … Resertifikasi … (H-3 Bulan)", tanpa tanggal). Catatan ketujuh butir sama untuk ketiganya. | `input/Master - Buat Program/Tamplate Formulir Konfirmasi Workshop.doc`, `input/Master - Buat Program/Template Formulir Konfirmasi Training Sertifikasi .doc`, `input/Master - Buat Program/Template Formulir Konfirmasi Resertifikasi.doc` + `[USULAN]` (pemetaan Jenis Produk, Q-31) |
| FR-53 | **Daftar** template: No, Kode, Nama (+ label kegiatan), Default untuk Jenis Produk, Isian (jumlah + isian khusus), Pernyataan (ringkas), Status, Action (**Edit**, **Lihat contoh** — pratinjau formulir dengan data contoh, **Aktifkan / Nonaktifkan**). Tidak dapat dihapus. Perubahan hanya berlaku untuk quotation baru (quotation menyimpan salinan pilihannya). | `input/Master - Buat Program/2026-10-29-template-formulir-konfirmasi/` + `[USULAN]` |

### 4.15 Role & Permission

Diminta user 04-11-2026 untuk mengatur siapa yang dapat membuka modul (pertama untuk Pengajuan Biaya) `input/Invoice & Pengajuan/2026-11-04-jawaban-pengajuan-biaya/` `[MAM]`. Draf 11t `[RNP]`: menu diganti nama **Role & Permission** (sebelumnya Matrix Akses Menu) dan disusun per **role**. Draf 11w `[MRO]`: role **diinput manual di Master Role** (FR-64) dan dipilih di form Master User; tidak lagi terbentuk dari Division + Jabatan. Hak tambahan untuk satu user diberikan lewat **Override Permission** di Master User (FR-65). Tampilan mengikuti video acuan `input/Invoice & Pengajuan/Role & Permission.mp4` (tombol "Add New Role" di video tidak dipakai).
Aplikasi sudah punya struktur hak akses: `groups`, `user_groups`, `permissions` (satu baris per menu per aksi, nama `Nama_Menu.View` / `.Add` / `.Manage` / `.Delete`), `group_permissions`, `user_permissions`, `menus.permission_id`; sidebar (`Menu_generator`) hanya menampilkan menu yang permission View-nya dimiliki; group **id 1 = Admin** lolos semua permission (`Auth::is_admin()`). Halaman hak akses **per user** sudah ada (`users/setting/permission`); halaman **per group** belum ada.

| ID | Kebutuhan | Sumber |
|---|---|---|
| FR-54 | Menu **Role & Permission** (modul Master Data, sub modul **Pengguna**; to be: **User** `[SMD]`) mengatur hak setiap **Role** atas setiap menu aplikasi: menu apa yang dapat dibuka dan apa yang dapat dilakukan di menu itu `[RNP]`. Daftar role = isi **Master Role** (FR-64) `[MRO]`; tambah, ubah nama, dan hapus role dilakukan di Master Role, bukan di halaman ini. Memakai tabel existing `groups` (satu record per role), `user_groups`, `permissions`, `group_permissions`, `menus`. Hak tambahan per user (`user_permissions`) diatur lewat **Override Permission** di Master User (FR-65). | `[MAM]`, `[RNP]`, `[MRO]` + `[USULAN]` (tabel existing) |
| FR-55 | Tampilan mengikuti video `[RNP]`: kartu ringkas (**Total Role**, **User berakun**, **Menu diatur**); cari nama role; tabel **Role** (Superadmin bertanda *Full Access*), **User terdaftar**, **Total hak** ("n hak diberikan" / "Semua hak aktif" / "Belum diatur"), **Cakupan** (preview kode hak, mis. `qtn.view`, +n lagi), Action **Atur Akses** (Superadmin: Lihat). **Atur Akses** membuka modal: Nama Role (hanya baca), **Matrix Hak Akses** "x dari y dipilih", tombol **Pilih Semua Modul**, **Kosongkan**, cari menu / hak; baris **menu dikelompokkan per modul** (urut sidebar), tiap baris: centang semua, chip aksi **View**, **Add**, **Manage**, **Delete** yang berlaku, tombol **Pilih Semua / Batal Semua**, **Scope** n/m; kaki "Total: n hak dipilih". Aksi yang tidak berlaku tidak ditampilkan (mis. Dashboard hanya View). Arti aksi `[USULAN]`: **View** = membuka menu & daftar; **Add** = tambah / ajukan; **Manage** = ubah, finalkan, dan **proses approval** pada menu approval; **Delete** = hapus / batalkan / nonaktifkan. Mencentang Add/Manage/Delete otomatis mencentang View; melepas View melepas semua hak menu itu. Kolom User terdaftar juga menyebut jumlah user role itu yang punya Override Permission. Hapus role ada di Master Role (FR-64): role tanpa user **boleh dihapus** `[RNP2]`; role yang masih dipakai user dan role Superadmin tidak dapat dihapus. **To be 12-11-2026:** aksi kelima **Approve** = Finalkan Master Program, Finalkan Penjualan Program, dan proses Approval Pengajuan (sebelumnya Manage). Aksi per menu: Dashboard, Katalog, Activity Log = View; Approval Pengajuan = View, Approve; Manage Peserta = View, Manage (Close Program); Daftar Master Program dan Daftar Penjualan Program = View, Add, Manage, Delete, Approve; menu lain = View, Add, Manage, Delete. Tombol/menu Finalkan hanya tampil bagi pemilik hak Approve. | `[MAM]`, `[RNP]`, `[RNP2]` + `[USULAN]`, `[JPT-3]` |
| FR-56 | Dua akun khusus `[MAM2]`: **Superadmin** — akun **developer**, group id 1 aplikasi, role **bawaan sistem** yang **dibuat dari awal** (seed) dan **tidak dapat ditambah lagi** `[RNP2]`; tampil di Master Role sebagai baris terkunci, tidak dapat diubah, dihapus, atau dipilih untuk user lain; selalu memiliki **semua** akses dan semua data; barisnya di Role & Permission tampil terkunci "Full Access — semua hak aktif". **Admin** — akun **user Labmania**, role biasa di Master Role yang **hak modulnya dapat dibatasi**; hak data tetap semua (mis. semua program di Pengajuan Biaya, PB-FR-20). Di mockup, Admin = role **Admin** — contoh, menunggu Q-35. | `[MAM]`, `[MAM2]`, `[RNP]`, `[RNP2]`, `[MRO]` |
| FR-57 | **Simpan** mengganti hak role terpilih (`group_permissions`). Akibatnya: sidebar user dengan role itu hanya menampilkan menu dengan View; tombol aksi tanpa hak tidak tampil; request tanpa hak ditolak server. Role baru dari Master Role (FR-64) belum punya hak sampai disimpan di sini. Hak **data** (mis. hanya program terkait di Pengajuan Biaya, PB-FR-20) tetap diatur di masing-masing modul. Perubahan dicatat `write_log()` modul "Master Data", sub modul "Role & Permission" (hak sebelum → sesudah per menu). | `[MAM]`, `[RNP]`, `[STD]` + `[USULAN]` |
| FR-58 | **Isi awal** hak per role `[USULAN]` (Q-33, sedang dicek user), contoh role mockup: **Direktur** (Manajemen) — semua menu kecuali Role & Permission, termasuk Approval Pengajuan (View, Manage); **Superadmin** — otomatis semua (FR-56); **Admin** — semua menu termasuk Role & Permission, **kecuali Activity Log** (LOG-FR-09; dapat diberikan lewat Role & Permission); role Sales / Trainer (**Sales Public**, **Sales B2B**, **Trainer**); role **Keuangan** belum diatur — Dashboard (View), Quotation (View, Add, Manage), Pengajuan Biaya (View, Add). **To be 12-11-2026:** role contoh dipakai sebagai daftar sementara (Q-37); **Sales Public** dan **Sales B2B** (= Sales/Marketing) mendapat tambahan hak penuh di Master Peserta, Master Company, Master Rate, Katalog Produk (Q-06), Daftar Master Program dan Daftar Penjualan Program termasuk Approve (Q-MP-01, Q-PJ-02), serta Manage Peserta (Close Program, Q-QTN-09). Role Admin ditentukan sendiri oleh Labmania (Q-35). | `[USULAN]`, `[MAM2]`, `[RNP]`, `[JPT-1]`, `[JPT-4]`, `[JPT-19]`, `[JPT-30]` |
| FR-64 | **Master Role** (menu baru, modul Master Data, sub modul **Pengguna** (to be: **User** `[SMD]`), di antara Master User dan Role & Permission) `[MRO]`: daftar role yang diinput manual. Isian: **Nama Role** (wajib, unik tanpa membedakan huruf besar/kecil, maks. 50 karakter) `[USULAN]`. Daftar: No, Nama Role, **User terdaftar**, **Hak di Role & Permission** ("n hak diberikan" / "Belum diatur"), Dibuat / Diubah, Action (gear: Edit, **Atur Akses** membuka modal Role & Permission role itu, **Hapus** hanya bila tidak dipakai user). Role baru belum punya hak (pesan mengarahkan ke Role & Permission). Mengubah nama role tidak memutus user maupun haknya (relasi lewat id). Role **Superadmin** tampil terkunci (FR-56). Isi Master Role menjadi **satu-satunya sumber** pilihan Role di Master User (FR-37) dan daftar role di Role & Permission (FR-54). Tabel existing `groups` (`id_group`, `nm_group`); tambah / ubah / hapus dicatat `write_log()` modul "Master Data", sub modul "Master Role". Isi contoh mockup: Direktur, Admin, Sales Public, Sales B2B, Trainer, Keuangan (Q-37). | `[MRO]` + `[USULAN]` (isian, aturan hapus, letak menu, isi contoh) |
| FR-65 | **Override Permission** `[MRO]`: bagian dalam **Akun Login** form Master User (dapat dilipat; judul menyebut "n hak tambahan") untuk memberi **satu user** hak menu **tambahan** di luar hak role-nya. Isinya matrix menu × aksi yang sama dengan Role & Permission (dikelompokkan per modul, aksi yang berlaku saja). Hak yang sudah dimiliki role tampil **tercentang dan terkunci** (penanda "role"); hak lain dapat dicentang. Mencentang Add / Manage / Delete otomatis mencentang View; melepas View melepas hak tambahan menu itu. Override **hanya menambah**, tidak dapat mengurangi hak role `[USULAN]` (Q-37). Saat Role di form diganti, hak override yang sudah tercakup role baru dibuang. Hak efektif user = hak role (`group_permissions` lewat `user_groups`) **+** override (`user_permissions`), diperiksa di server (NFR-13). Tidak tersedia untuk Superadmin (sudah semua hak) dan user tanpa akun. Daftar User menandai user ber-override ("Override n hak"); perubahan tercatat di log Master User (jumlah hak sebelum → sesudah). | `[MRO]`, `[RNP]` + `[USULAN]` (letak di form, tampilan, hanya menambah) |

## 5. Kebutuhan Non-Fungsional (NFR)

Setiap NFR punya kriteria uji yang terukur. Butir `[USULAN]` belum dinyatakan klien.

| ID | Kategori | Kebutuhan | Kriteria uji | Lingkup | Sumber |
|---|---|---|---|---|---|
| NFR-01 | Audit | Setiap tambah, ubah, aktif/nonaktif, dan hapus dicatat dengan `write_log()`. Untuk ubah, payload berisi snapshot `old` (diambil sebelum update) dan `new`. Simpan Product mencatat semua Nomor Product yang terbentuk. Perubahan Default Cost item rate tercatat Before/After. **Jejak data & Activity Log**: semua tabel modul ini memakai kolom `created_by`, `created_date`, `updated_by`, `updated_date` (+ `deleted_by`, `deleted_date` bila dapat dihapus); daftar menampilkan kolom "Dibuat / Diubah"; semua aksi tampil di modul **Activity Log** (`docs/fr-nfr-activity-log.md` LOG-FR-01 – LOG-FR-08) `input/Master - Buat Program/2026-10-30-histori-activity-log/`. | Setelah 1 aksi ubah, Activity Log menampilkan tepat field yang berubah (Before/After); 1 simpan dengan 3 baris menghasilkan log yang menyebut 3 nomor paket | Semua UC **To be 12-11-2026:** kolom **Dibuat / Diubah** tidak ditampilkan di daftar mana pun; jejak perubahan cukup di **Activity Log** (menggantikan LOG-FR-02 as is untuk daftar to be). | `[STD]`, `[JPT-6]` |
| NFR-02 | Integritas data | Kode setiap master, Nomor Product, Kode Peserta, dan Kode Company **unik**; kombinasi 4 atribut Paket Produk juga **unik**. Keduanya dijaga di tingkat database (unique constraint/index). | Insert kode atau kombinasi duplikat langsung ke DB ditolak; 2 penyimpanan bersamaan tidak menghasilkan kode atau kombinasi kembar | FR-03, FR-13, FR-17, FR-22, FR-32 | `[WISH-1]`, `[KLR-Q-12]` + `[USULAN]` (constraint DB) |
| NFR-03 | Integritas data | Simpan Product berjalan dalam **satu transaksi database**: semua baris tersimpan atau tidak satu pun. | Memaksa gagal pada baris ke-3 dari 3 → tidak ada paket baru di DB | FR-13, FR-22 | `[USULAN]` |
| NFR-04 | Integritas data | Master atribut produk, Paket Produk, dan item Master Rate tidak memiliki operasi hapus (tidak ada endpoint/tombol Delete); status diganti lewat aktif/nonaktif. | Tidak ada route hapus untuk 4 master, Paket Produk, dan Master Rate; jumlah baris tabel tidak berkurang setelah nonaktif | FR-08, FR-23, FR-35 | `[KLR-Q-05]`, `[KPG-3]` |
| NFR-05 | Validasi | Field wajib divalidasi di sisi klien **dan** server; penyimpanan ditolak dengan pesan yang menyebut field yang kosong. | Request POST tanpa field wajib (melewati JS) ditolak server dan tidak ada baris tersimpan | FR-09, FR-16, FR-19, FR-20, FR-31 | `[MD:Master Peserta]` (tanda *) + `[USULAN]` |
| NFR-06 | Validasi | Email (Peserta, Company, PIC) harus berformat email yang valid; No Telp/WA hanya angka (boleh diawali `+`), 9–15 digit. NPWP / NPWP Instansi 15 atau 16 digit angka (tanda baca diabaikan). | `abc@` ditolak; `08123456789` diterima; NPWP `01.234.567.8-901.000` diterima | FR-16, FR-31 | `[USULAN]` |
| NFR-07 | Kinerja | Halaman daftar memakai paginasi dan pencarian sisi server. | Daftar dengan 10.000 baris tampil < 2 detik (p95) | Semua daftar | `[USULAN]` |
| NFR-08 | Keamanan | Akses menu dan aksi (lihat/tambah/ubah/aktif-nonaktif/hapus) dikendalikan oleh mekanisme permission yang sudah ada di aplikasi. | Pengguna tanpa hak ubah status tidak melihat tombol Nonaktifkan dan request langsung ditolak (HTTP 403 / redirect) | Semua UC | `[USULAN]` |
| NFR-09 | Platform | Dibangun di CodeIgniter 3 (HMVC, `application/modules/`), MySQL, Bootstrap 4/5 + jQuery. Nama tabel/kolom identik dengan ERD. | Code review: modul berada di `application/modules/`, nama tabel = ERD | Semua | `[STD]` |
| NFR-10 | Usability | Semua pilihan master pada form (Produk, Bidang, Varian, Sertifikat, Satuan, COA, Provinsi, Kota, Jenis Instansi) memakai dropdown yang dapat dicari. Mockup draf 11n menerapkannya di Product (tambah & edit) `input/Master - Buat Program/2026-10-29-edit-master-sertifikat-cari/`. | Mengetik 3 huruf menyaring opsi pada dropdown | FR-12, FR-16, FR-19, FR-20, FR-31 | `[USULAN]` |
| NFR-11 | Usability | Logo dan kolom search menu di sidebar **tetap terlihat (freeze)**; hanya daftar menu yang dapat digulir. | Dengan daftar menu lebih tinggi dari layar, setelah daftar menu digulir sampai bawah kolom search masih terlihat dan dapat diketik tanpa menggulir kembali; berlaku juga di layar ≤ 900 px (sidebar sebagai panel) | FR-29 | `[RMD-6]` |
| NFR-12 | Validasi / Usability | **Semua isian uang** (Default Cost, Harga jual / peserta, Target profit, nominal biaya Master Program & Penjualan Program, Harga all-in, Diskon, Biaya lain-lain Quotation) berupa angka rupiah ≥ 0 tanpa desimal dan **tampil dengan pemisah ribuan titik saat diketik** (600.000, bukan 600000); hanya angka diterima, nilai disimpan sebagai angka tanpa titik. | Ketik `1600000` → tampil `1.600.000`, tersimpan 1600000; `-1` atau `abc` ditolak | FR-19, FR-20, semua modul | `[CM]`, `input/Master - Buat Program/2026-10-30-format-rupiah-titik/` + `[USULAN]` (simpan tanpa titik) |
| NFR-13 | Keamanan | Hak Role & Permission **diperiksa di server** (`auth->restrict()` / `has_permission()`), bukan hanya menyembunyikan menu. Role **Superadmin** tidak dapat dikurangi haknya dan halaman Role & Permission selalu terbuka untuknya agar tidak ada keadaan terkunci; hak role Admin dapat dikurangi. | User role Staff membuka URL Approval Pengajuan langsung → 403; simpan hak untuk Superadmin ditolak | FR-54 – FR-58 | `[MAM]`, `[MAM2]` + `[USULAN]` |

---

## 6. Pertanyaan Terbuka

### 6.1 Masih terbuka

| ID | Pertanyaan | Mempengaruhi |
|---|---|---|
| Q-01 | Bagaimana **format penomoran** kode untuk Jenis Produk, Jenis Sertifikat, Jenis Bidang, Varian Pelaksanaan, Nomor Product, Kode Peserta, Kode Company, dan Kode Fixed/Variable Cost? (prefix, panjang digit, reset per tahun/tidak) **Jawaban 12-11-2026** `[JPT-7]`: tidak perlu reset tahunan. **Masih terbuka:** prefix dan jumlah digit tiap kode (mockup PREFIX-001). | FR-03, FR-13, FR-17, FR-19, FR-20, FR-32 |
| Q-06 | Siapa pengguna yang mengelola **Master Peserta**, **Master Company**, **Master Rate**, dan yang **membuat Katalog**? *(masih ditanyakan Labmania)* **Jawaban 12-11-2026** `[JPT-4]`: Manajemen, Admin, dan Sales/Marketing (FR-58). **Masih terbuka:** apakah ketiganya berlaku untuk semua master dan Katalog. | UC-06 – UC-09, UC-11 |
| Q-07b | Kolom **Lokasi** di daftar peserta menampilkan Provinsi, Kota, atau keduanya? Dari mana sumber data Provinsi & Kota/Kabupaten (tabel wilayah yang ada di sistem, atau diinput Labmania)? **Jawaban 12-11-2026** `[JPT-10]`: Lokasi ditampilkan; sumber data provinsi/kota ditentukan tim developer. **Masih terbuka:** provinsi, kota, atau keduanya (mockup: keduanya). | FR-15, FR-16, FR-31 |
| Q-36 | Perlu tambahan isi per paket di **Katalog Produk** (deskripsi singkat, durasi, harga, foto)? Saat ini Paket Produk hanya punya Judul + Produk/Bidang/Varian/Sertifikat, jadi hanya itu yang tampil. **Jawaban 12-11-2026** `[JPT-11]`: tidak perlu, seperti saat ini. **Selesai.** | FR-14, FR-61, FR-62 |
| Q-13 | **Pain Point** proses saat ini *(masih digali Labmania)*. **Jawaban 12-11-2026** `[JPT-5]`: proses belum terdigitalisasi: tidak ada batasan proses, banyak celah, tidak teratur. **Selesai.** | Cakupan |
| Q-14 | ~~Setelah tersimpan, apakah Paket Produk masih dapat diubah?~~ **Terjawab** `input/Master - Buat Program/2026-10-29-edit-master-sertifikat-cari/`: Paket Produk punya aksi **Edit** (FR-43). | FR-10, UC-10 |
| Q-16 | Apakah **Peserta** dan **Company** juga hanya boleh dinonaktifkan (tidak dihapus), seperti master lain dan Master Rate? (saat ini mengikuti mockup: Edit/Delete) **Jawaban 12-11-2026** `[JPT-8]`: Peserta dan Company boleh dihapus (FR-15, FR-32; batas "belum dipakai" usulan analis). **Selesai.** | FR-15, FR-32 |
| Q-17 | Apakah paket yang **nonaktif** ikut dihitung dalam cek keunikan kombinasi? (mis. paket lama dinonaktifkan, lalu kombinasi yang sama dibuat lagi) **Jawaban 12-11-2026** `[JPT-9]`: hanya Product aktif (FR-22). **Selesai.** | FR-22 |
| Q-18 | ~~Apakah nama sub modul **Atribut Produk**, **Produk**, **Customer**, dan **Biaya** sudah sesuai?~~ **Terjawab (to be)** `[SMD]`: sub menu **Product**, **User**, **Peserta & Trainer**. | FR-27 |
| Q-39 | **Template Keterangan Quotation** dan **Template Formulir Konfirmasi** masuk sub menu mana? Tidak disebut di `[SMD]`; sementara tetap sub **Dokumen** (usulan). **Jawaban 12-11-2026** `[JPT-13]`: sub menu **Dokumen**. **Selesai.** | FR-27 |
| Q-20 | Aplikasi sudah punya tabel **`customer`** (nama, alamat, provinsi, kota, telepon, email, NPWP, PIC, status aktif). Boleh Master Company memakai tabel itu (ditambah kolom Jenis Instansi), atau perlu tabel baru? *(ERD draf 4 memakai `customer`)* | FR-30 – FR-32, ERD |
| Q-25 | Master User: (a) ~~isi pilihan Group Akses~~ — diganti Role dari Master Role `[MRO]`; (b) ~~data Division, Jabatan~~ — diganti Role `[MRO]`; isi **Master Role** final dan Employee Type — **menyusul dari Labmania** (`input/Master - Buat Program/2026-10-28-master-user-data-menyusul/`); mockup memakai contoh. Department tidak dipakai `[RNP]`. Employee Type dan Religion — memakai master aplikasi atau diisi Labmania?; (c) **Employee ID** diinput manual (seperti form acuan) atau dibuat otomatis seperti modul `employees` (EMP…); (d) boleh memakai tabel existing `employees` / `users`? **Jawaban 12-11-2026** `[JPT-1]`: Master Role mengikuti role contoh dulu (Q-37). | FR-36 – FR-39, FR-64, ERD |
| Q-26 | Master Trainer: field mana yang **wajib** selain Nama lengkap (mis. telepon, bidang keahlian)? Siapa pengguna yang mengelola? **Jawaban 12-11-2026** `[JPT-14]`: seperti saat ini (hanya Nama lengkap wajib). **Selesai.** | FR-45, UC-38 |
| Q-27 | Lampiran Dokumen trainer perlu **file unggahan** (ijazah, sertifikat), atau cukup nama dokumen & tahun seperti Excel? (usulan: dapat diunggah, opsional) **Jawaban 12-11-2026** `[JPT-14]`: seperti saat ini. **Selesai.** | FR-45 |
| Q-28 | Template Keterangan: apakah lima **kategori** (Syarat keikutsertaan, Termasuk, Tidak termasuk, Dokumen persyaratan, Klausul tanda tangan) sudah lengkap? Perlu kategori lain (mis. Catatan lain-lain)? **Jawaban 12-11-2026** `[JPT-15]`: "Default awal" (perlu klarifikasi: belum menjawab kategori, Public vs B2B, dan isi default). | FR-48 |
| Q-29 | Butir default **dibedakan Public vs B2B** (usulan: kolom Berlaku untuk), atau sama untuk semua? **Jawaban 12-11-2026** `[JPT-15]`: lihat Q-28, perlu klarifikasi. | FR-48 |
| Q-30 | **Isi default awal** dari Labmania untuk tiap kategori? Mockup memakai isi template + butir bertanda "contoh". **Jawaban 12-11-2026** `[JPT-15]`: lihat Q-28, perlu klarifikasi. | FR-50 |
| Q-31 | Template Formulir Konfirmasi default dipilih dari **Jenis Produk** paket (usulan: Pelatihan → Workshop, Sertifikasi → Training & Sertifikasi). **Resertifikasi** dipilih manual — perlu atribut produk tersendiri (mis. Jenis Produk "Resertifikasi") agar otomatis? **Jawaban 12-11-2026** `[JPT-16]`: tidak; template Resertifikasi dipilih manual. **Selesai.** | FR-51, FR-52 |
| Q-32 | Isian yang tidak ada di Master Peserta (NIK, Jabatan, **Nomor Hp**, Masa Pengalaman, LSP, Tanggal Habis Sertifikat) dibiarkan kosong untuk diisi tangan (usulan), atau perlu ditambahkan ke Master Peserta agar terisi otomatis? **Jawaban 12-11-2026** `[JPT-17]`: tidak ditambahkan ke Master Peserta. **Selesai.** Catatan: tanggal habis sertifikat untuk pengingat resertifikasi disimpan di data sertifikat (Q-MON-05). | FR-51, FR-16 |
| Q-33 | **Sedang dicek user (04-11-2026).** **Role & Permission**: isi awal hak per role (FR-58) sudah sesuai? Apakah aksi cukup View / Add / Manage / Delete (konvensi aplikasi), atau perlu aksi **Approve** terpisah? **Jawaban 12-11-2026** `[JPT-3]`: perlu hak **Approve** terpisah (FR-55). **Masih terbuka:** menu lain yang perlu Approve selain Finalkan program dan Approval Pengajuan. | FR-55, FR-58, Q-25 |
| Q-35 | **Role & Permission** `[RNP]` — sisa yang belum dijawab: (a) role mana yang menjadi role **Admin** (user Labmania)? Mockup memakai contoh role Admin. Bagian Superadmin, (b) hapus role, dan (c) hak tambahan per user terjawab — lihat 6.2. **Jawaban 12-11-2026** `[JPT-2]`: role Admin ditentukan sendiri oleh Labmania saat setup. **Selesai** untuk spec. | FR-54, FR-56 |
| Q-38 | **Lingkup paket** (to be, FR-67): (a) perlu juga diisi/diubah dari form Product, bukan hanya dari Katalog? (b) Lingkup ikut tampil di Penawaran/Quotation atau Master Program? (c) batas 20 item × 200 karakter cukup? (d) siapa yang boleh mengubah (Q-06)? **Jawaban 12-11-2026** `[JPT-12]`: boleh, diubah oleh role yang punya akses ke master (FR-67). **Masih terbuka:** batas 20 × 200 karakter. | FR-67, FR-68 |
| Q-37 | **Master Role & Override Permission** `[MRO]`: (a) daftar role final Labmania (mockup: Direktur, Admin, Sales Public, Sales B2B, Trainer, Keuangan)? Role mana yang termasuk **Manajemen** (sementara Direktur, Q-PB-14)? (b) kolom Division / Position di tabel `employees` aplikasi tetap dipakai modul lain (HR) atau tidak dipakai lagi? Role disimpan di kolom baru `employees.id_group` agar user tanpa akun tetap punya role, setuju? (c) perlu Override yang **mengurangi** hak role (mis. role Sales tanpa menu tertentu untuk satu orang), atau cukup menambah? **Jawaban 12-11-2026** `[JPT-1]`: mengikuti usulan sementara: Direktur, Admin, Sales Public, Sales B2B, Trainer, Keuangan; Manajemen = Direktur. | FR-37, FR-64, FR-65, PB-FR-19, LOG-FR-09 |

### 6.2 Sudah terjawab

Sumber: 22-10-2026 `input/Master - Buat Program/2026-10-22-klarifikasi-produk/`; 23-10-2026 `input/Master - Buat Program/2026-10-23-review-mockup-master-data/` dan `input/Master - Buat Program/2026-10-23-klarifikasi-program/`.

| ID | Jawaban | Diterapkan di |
|---|---|---|
| Q-09 | 05-11-2026 `input/Master - Buat Program/2026-11-05-katalog-produk/`: katalog dibuat sekarang, **filter Bidang & Sertifikat**, cetak **PDF** bergaya katalog produk pada umumnya. Tata letak PDF usulan analis (FR-62). | FR-14, FR-60 – FR-62 |
| Q-35 (c) | 05-11-2026 `[MRO]`: hak tambahan per user **tetap ada**, diatur sebagai **Override Permission** di Master User (FR-65, `user_permissions`). | FR-54, FR-65 |
| Q-34 | 04-11-2026 `[MAM2]`: **1 Superadmin** (developer, semua akses) dan **1 Admin** (user Labmania, hak modul masih dapat dibatasi). | FR-56, FR-58, NFR-13 |
| Q-35 (sebagian) | 04-11-2026 `[RNP2]`: (a) **Superadmin** dibuat dari awal sebagai role bawaan, tidak dapat ditambah lagi; (b) role tanpa user **boleh dihapus**. Manajemen sementara jabatan **Direktur** (Q-PB-14). | FR-38, FR-55, FR-56 |
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

| Jenis Produk | Jenis Sertifikat | Varian Pelaksanaan `[TB-1]` |
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
| draf 11 | 28-10-2026 | `input/Master - Buat Program/2026-10-28-master-user/`: sub modul **Pengguna** → **Master User** (FR-36 – FR-39, UC-32, bagian 1); form mengikuti `input/Master - Buat Program/Master User.png` + bagian Akun Login (usulan); Q-25 baru. |
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
| draf 11l | 29-10-2026 | `input/Master - Buat Program/2026-10-29-master-trainer/`: **Master Trainer** baru (sub modul Trainer) mengikuti `input/Master - Buat Program/Master Trainer Ivan Sarifudin.xlsx` — FR-44 – FR-47, UC-38, bagian 4.12, Q-26, Q-27; dipilih di Penjualan Program (PJ-FR-23). ERD draf 10. |
| draf 11m | 29-10-2026 | `input/Master - Buat Program/2026-10-29-keterangan-quotation-default/`: **Template Keterangan Quotation** baru (sub modul Dokumen) — FR-48 – FR-50, UC-39, bagian 4.13, Q-28 – Q-30; ERD `ms_template_keterangan`. |
| draf 11n | 29-10-2026 | `input/Master - Buat Program/2026-10-29-template-formulir-konfirmasi/`: **Template Formulir Konfirmasi** baru (sub modul Dokumen) dengan tiga template awal Workshop, Training & Sertifikasi, Resertifikasi — FR-51 – FR-53, UC-40, bagian 4.14, Q-31, Q-32; ERD `ms_template_konfirmasi`. |
| draf 11o | 30-10-2026 | `input/Master - Buat Program/2026-10-30-format-rupiah-titik/`: **NFR-12** diperluas — semua isian uang di semua modul memakai pemisah ribuan titik saat diketik; mockup draf 11z. |
| draf 11p | 30-10-2026 | `input/Master - Buat Program/2026-10-30-histori-activity-log/`: kolom jejak (created/updated/deleted) di semua tabel dan modul **Activity Log** — rujukan ke `docs/fr-nfr-activity-log.md` di NFR audit; mockup draf 12. |
| draf 11q | 30-10-2026 | `input/Master - Buat Program/2026-10-30-activity-log-akses-retensi/`: group akses **Admin Developer** di Master User (FR-36) untuk akses Activity Log. |
| draf 11r | 04-11-2026 | `input/Invoice & Pengajuan/2026-11-04-jawaban-pengajuan-biaya/`: **Matrix Akses Menu** di sub modul Pengguna (bagian 4.15, FR-54 – FR-58, UC-49, NFR-13) memakai tabel hak akses existing; Q-33, Q-34 baru. Dipakai pertama oleh modul Pengajuan Biaya (`docs/fr-nfr-pengajuan-biaya.md`). Draf 11q: `input/backup/fr-nfr_sebelum-matrix-akses_04-11-2026.md`. |
| draf 11s | 04-11-2026 | `input/Invoice & Pengajuan/2026-11-04-jawaban-pengajuan-biaya-2/`: Q-34 terjawab — **Superadmin** (developer, terkunci) dan **Admin** (user Labmania, dapat dibatasi) (FR-56, FR-58, NFR-13, UC-49); **FR-59** akun login trainer di Master Trainer (Q-PB-13 disetujui). Q-33 masih dicek user. |
| draf 11t | 04-11-2026 | `input/Invoice & Pengajuan/2026-11-04-role-permission/` `[RNP]`: **Matrix Akses Menu** diganti **Role & Permission** (bagian 4.15, FR-54 – FR-58, UC-49, NFR-13) mengikuti video acuan; **role = Division + Jabatan**, terbentuk otomatis dari akun di Master User — pilihan Group Akses dihapus dari form (FR-38); **Department dihapus** dari Master User (FR-37, FR-39, UC-32); Q-35 baru. Draf 11s: `input/backup/fr-nfr_sebelum-role-permission_04-11-2026.md`. |
| draf 11u | 04-11-2026 | `input/Invoice & Pengajuan/2026-11-04-jawaban-role-permission/` `[RNP2]`: Q-35 terjawab sebagian — **Superadmin role bawaan sistem** (dibuat dari awal, tidak dapat ditambah, tidak dari Division + Jabatan; FR-38, FR-56); **hapus role tanpa user** jadi keputusan (FR-55). Q-35 tersisa: role Admin, hak per user. Draf 11t: `input/backup/fr-nfr_sebelum-superadmin-bawaan_04-11-2026.md`. |
| draf 11v | 05-11-2026 | `input/Master - Buat Program/2026-11-05-katalog-produk/`: **Katalog Produk** tidak lagi ditunda (bagian 1.1, 4.4, UC-06) — FR-14 diperbarui (isi = paket aktif, tidak disimpan), **FR-60 – FR-63** (filter Bidang & Sertifikat, tampilan per Bidang, PDF katalog, Activity Log); Q-09 terjawab, Q-36 baru. ERD tidak berubah. Draf 11u: `input/backup/fr-nfr_sebelum-katalog-produk_05-11-2026.md`. |
| draf 11w | 05-11-2026 | `[MRO]`: field **Division** dan **Jabatan (Position)** di Master User diganti satu field **Role** (FR-37 – FR-39, UC-32); menu baru **Master Role** input manual (FR-64, UC-55) jadi sumber pilihan Role di Master User dan daftar role di Role & Permission (FR-54 – FR-58, UC-49; role tidak lagi terbentuk dari Division + Jabatan); **Override Permission** per user di Master User (FR-65). Q-35 (c) terjawab, Q-25 diperbarui, Q-37 baru. ERD Matrix Akses: `employees.id_group` (usulan). Draf 11v: `input/backup/fr-nfr_sebelum-master-role_05-11-2026.md`. |
| draf 11x | 05-11-2026 | `input/Master - Buat Program/2026-11-05-sidebar-hide/`: **FR-66** tombol sembunyikan sidebar (bagian 4.9). Draf 11w: `input/backup/fr-nfr_sebelum-hide-sidebar_05-11-2026.md`. |
| **to be draf 1** | 07-11-2026 | Dokumen to be dipisah ke `docs-to-be/` (`input/Master - Buat Program/2026-11-07-dokumen-to-be-terpisah/`), berangkat dari as is draf 11x. `[TB-1]`: master **Varian Pelaksanaan kembali** — Paket Produk = Jenis Produk + Jenis Sertifikat + Jenis Bidang + Varian Pelaksanaan (bagian 1, 1.1, 1.2, UC-04, UC-05, FR-01, FR-02, FR-07, FR-08, FR-09, FR-10, FR-12, FR-22, FR-26, FR-43, NFR-10, Q-01, Q-36, lampiran 7). `[TB-3]`: Master Peserta bukan lagi sumber peserta Quotation (FR-15, UC-07). ERD to be: `ms_varian_pelaksanaan`, `paket_produk.id_varian_pelaksanaan`, UNIQUE paket 4 kolom. |
| **to be draf 1a** | 07-11-2026 | `input/Master - Buat Program/2026-11-07-katalog-lingkup/`: **Lingkup per Paket Produk** diisi dari Katalog Produk, boleh lebih dari satu item, dan tampil di PDF katalog (FR-61, FR-62 diperbarui, termasuk Varian di kartu katalog; **FR-67**, **FR-68** baru; Q-38 baru; Q-36 terjawab sebagian: isi tambahan = Lingkup). ERD to be: tabel `paket_produk_lingkup`. |
| **to be draf 1b** | 07-11-2026 | `input/Master - Buat Program/2026-11-07-surat-kerja-trainer/`, `input/Master - Buat Program/2026-11-07-jawaban-surat-kerja-trainer/`: Master Trainer, trainer **wajib** di Penjualan Program dan tujuan **Surat Kerja Trainer** (FR-47). |
| **to be draf 1c** | 07-11-2026 | `input/Master - Buat Program/2026-11-07-sub-menu-master-data/`: **sub menu Master Data** yang dapat dibuka/ditutup — Product, User, Peserta & Trainer (+ Dokumen, Q-39) (bagian 1, FR-27, FR-36, FR-54, FR-64; Q-18 terjawab, Q-39 baru). |
| **to be draf 1d** | 09-11-2026 | `input/Master - Buat Program/2026-11-09-product-multi-reschedule-evidence/`: menu **Product Combine** diganti nama **Product** (bagian 1 – 4, FR-10, FR-13, FR-21, FR-43, FR-61, NFR-02, NFR-03, Q-01, Q-38); Product dapat dipilih lebih dari satu per Master Program. |
| **to be draf 1e** | 11-11-2026 | `input/Master - Buat Program/2026-11-11-peserta-deal-ke-master-peserta/`: **peserta Deal disimpan ke Master Peserta** saat Deal (FR-15, UC-07); link tidak berubah, field kosong ditandai **Belum lengkap** dan dilengkapi admin; field **NIK** opsional (FR-16). ERD: `ms_peserta.nik`, kolom wajib form boleh NULL di tabel. Draf 1d: `input/backup/fr-nfr-to-be_sebelum-peserta-deal-ke-master_11-11-2026.md`. |
| **to be draf 1g** | 13-11-2026 | `input/Master - Buat Program/2026-11-13-multi-master-batal-top-refund/` `[MMB-5]`: **Harga start from** per Paket Produk di Katalog Produk dan PDF katalog (FR-69). ERD to be: `paket_produk.harga_mulai`. Draf 1f: `input/backup/fr-nfr-to-be_sebelum-multi-master-top-refund_13-11-2026.md`. |
| **to be draf 1f** | 12-11-2026 | `input/Master - Buat Program/2026-11-12-jawaban-pertanyaan-terbuka/` `[JPT]`: hak **Approve** (FR-55, FR-58); hak awal Sales/Marketing; kode master tanpa reset tahunan (FR-03); kombinasi hanya Product aktif (FR-22); Peserta/Company boleh dihapus bila belum dipakai (FR-15, FR-32); **NPWP Company wajib** (FR-31); kolom Dibuat / Diubah hanya di Activity Log (NFR-01); jawaban Q-01, Q-06, Q-07b, Q-13, Q-16, Q-17, Q-25 – Q-39. |
