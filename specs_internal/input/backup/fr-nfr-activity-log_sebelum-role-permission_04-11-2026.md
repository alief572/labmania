# Kebutuhan Fungsional & Non-Fungsional — Jejak Data & Modul Activity Log Labmania

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
