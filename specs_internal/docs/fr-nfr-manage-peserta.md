# Kebutuhan Fungsional & Non-Fungsional — Modul Manage Peserta Labmania

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
