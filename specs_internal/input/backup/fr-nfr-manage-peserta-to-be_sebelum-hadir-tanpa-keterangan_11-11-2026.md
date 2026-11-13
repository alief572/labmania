# Kebutuhan Fungsional & Non-Fungsional — Modul Manage Peserta Labmania

- **Versi:** **TO BE** draf 1f — 10-11-2026 (berangkat dari as is draf 1, `docs/fr-nfr-manage-peserta.md`)
- **Perubahan to be:** `input/Invoice & Pengajuan/2026-11-09-monitoring-peserta/` — tab **Sertifikat** di halaman peserta program (bagian 5.2) dan sub menu baru **Monitoring Peserta** (bagian 5.3), mengacu ke file Labmania `input/Data Peserta In House Training, Public Training, Training dan Sertifikasi 2026.xlsx`.
- **Sumber:** `input/Invoice & Pengajuan/2026-11-04-manage-peserta/` (permintaan user + jawaban atas usulan analis)
- **Status:** DRAF. Cakupan **Fase 1**: daftar program, peserta per program, cetak Daftar Peserta & Daftar Hadir, export Excel, Close Program. **Fase 2 ditunda** (bagian 2). Pertanyaan terbuka di bagian 7.
- **Modul terkait:** hulu Quotation (`docs/fr-nfr-quotation.md`: peserta **Deal** per quotation, QTN-FR-11) dan Penjualan Program (`docs/fr-nfr-penjualan-program.md`: program Final, trainer, tanggal, lokasi); Master Peserta (`docs/fr-nfr.md` FR-15 – FR-17); hilir Pengajuan Biaya (`docs/fr-nfr-pengajuan-biaya.md` PB-FR-20: program Closed tidak dapat diajukan); hak menu lewat Role & Permission (`docs/fr-nfr.md` FR-54 – FR-58); Activity Log (`docs/fr-nfr-activity-log.md`)
- **ERD:** `docs/diagrams/erd.drawio` halaman *ERD Penjualan Program* (`penjualan_program.closed_by`, `closed_date`); **to be:** `docs-to-be/diagrams/erd.drawio` halaman *ERD Quotation* tabel baru `quotation_peserta_sertifikat`
- **Use case:** `docs/workbook.xlsx` sheet *UC Non-BPMN* (UC-50 – UC-52)
- **Mockup:** `ui-mockup/produk/Labmania.html`, modul **Manage Peserta** (draf 14e); **to be:** `ui-mockup/produk-to-be/Labmania.html` → Manage Peserta › **Monitoring Peserta**

Penomoran memakai awalan **MP-**; Sertifikat memakai **SRT-FR-nn**, Monitoring Peserta **MON-FR-nn** / **Q-MON-nn**; pertanyaan modul ini **Q-MPS-nn** (Q-MP-nn sudah dipakai `fr-nfr-master-program.md`).

| Penanda | Arti |
|---|---|
| `[MPS-1]` | permintaan user 04-11-2026: modul "Manage Peserta", setiap program terlist, bisa print daftar peserta dsb. |
| `[MPS-2]` | jawaban user 04-11-2026: setelah Deal quotation; Halaman 1 setuju + action **Close Program**; Halaman 2 setuju semua; Fase 2 nanti; hak pakai disetting sendiri; format cetak belum ada — dibuatkan |
| `[JPB-12]` | `input/Invoice & Pengajuan/2026-11-04-jawaban-pengajuan-biaya/` butir PB-12: pengajuan boleh selama program belum **Closed** |
| `[STD]` | standar proyek di `AGENTS.md` (`write_log()`) |
| `[MON]` | permintaan 09-11-2026 `input/Invoice & Pengajuan/2026-11-09-monitoring-peserta/`: sub menu **Monitoring Peserta** di Manage Peserta, isi "kurang lebih seperti" file Excel Labmania `input/Data Peserta In House Training, Public Training, Training dan Sertifikasi 2026.xlsx` |
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
| UC-51 | Mencetak & Mengekspor Peserta | Pengguna (hak View) | Program punya ≥ 1 peserta Deal | Di halaman peserta pilih Cetak Daftar Peserta / Cetak Daftar Hadir / **Cetak Name Table** (to be) / Export Excel | PDF / Excel peserta |
| UC-52 | Close Program | Pengguna (hak Manage) | Program Final belum Closed | Pilih **Close Program** di menu gear, baca peringatan, konfirmasi | Program Closed; pindah ke tab Selesai; tidak dapat diajukan biaya |
| UC-66 | Memantau Peserta | Pengguna (hak View) | Ada peserta Deal | Buka Manage Peserta › **Monitoring Peserta**, pilih tab / ringkasan tindak lanjut, cari / saring, Export Excel | Daftar peserta beserta status tindak lanjut tampil / terunduh `[MON]` |
| UC-67 | Mencatat Sertifikat Peserta | Pengguna (hak Manage) | Ada peserta Deal | Manage Peserta › Lihat Peserta › tab **Sertifikat** (atau Monitoring › **Kelola Sertifikat**) › menu gear **Input Sertifikat**, isi sertifikat pelatihan dan sertifikasi LSP/BNSP, unggah file, simpan | Data sertifikat tersimpan dan tampil di Monitoring; tercatat di Activity Log `input/Invoice & Pengajuan/2026-11-09-sertifikat-monitoring-sesuai-sistem/` |

## 5. Kebutuhan Fungsional

| ID | Kebutuhan | Sumber |
|---|---|---|
| MP-FR-01 | Menu: modul **Manage Peserta** dengan menu **Manage Peserta** (sidebar setelah Invoice). Hak membuka menu dan aksinya diatur di **Role & Permission** (`fr-nfr.md` FR-54 – FR-58): **View** = lihat daftar & peserta, cetak, export; **Manage** = Close Program. | `[MPS-1]`, `[MPS-2]` + `[USULAN]` (pemetaan aksi) |
| MP-FR-02 | **Daftar Program**: semua Penjualan Program **Final**, tab **On going** / **Selesai** (Selesai = tanggal selesai acara sudah lewat **atau** program sudah Closed `[USULAN]`, Q-MPS-01). Kolom: No, **No. Program**, **Judul Program** (+ Company untuk B2B), **Jenis** (Public / B2B), **Tanggal & Lokasi**, **Trainer**, **Peserta (Deal / Target)**, Status (Berjalan / Selesai / Closed `[USULAN]`), Action (menu gear: **Lihat Peserta**, **Close Program**). Cari nomor / judul / lokasi / trainer; filter jenis. Urut tanggal mulai. | `[MPS-2]` |
| MP-FR-03 | **Close Program** (menu gear, hanya program belum Closed dan pengguna punya hak Manage): popup konfirmasi; menampilkan **peringatan** (tidak memblokir `[USULAN]`, Q-MPS-02) bila: acara belum selesai, masih ada quotation Draft / Terkirim, quotation Deal belum dibuatkan invoice, atau pengajuan biaya Menunggu Approval. Setelah konfirmasi: program **Closed** (siapa & kapan disimpan), pindah ke tab Selesai, menu Close tidak tampil lagi, **tidak dapat dibuka kembali** `[USULAN]` (Q-MPS-02). | `[MPS-2]` + `[USULAN]` |
| MP-FR-04 | **Peserta per Program** (Lihat Peserta): kepala halaman berisi info program — jenis, judul, tanggal & durasi, lokasi, trainer, Company (B2B), peserta Deal / target, status. Tombol **← Semua Program**, **Export Excel**, **Cetak Name Table** (to be, MP-FR-11), **Cetak Daftar Hadir**, **Cetak Daftar Peserta** (nonaktif bila belum ada peserta Deal). Program Closed menampilkan pemberitahuan siapa & kapan di-Close; tetap dapat dilihat, dicetak, diekspor. | `[MPS-2]` |
| MP-FR-05 | **Tabel peserta**: No, **Kode**, **Nama**, **Instansi / Company**, **No. WA**, **Email**, **No. Quotation**. Sumber: peserta berstatus **Deal** di quotation berstatus **Deal** untuk program itu (QTN-FR-11) — peserta Batal / menunggu tidak tampil. Pencarian kode, nama, instansi, kontak, nomor quotation. **Tidak ada tambah / ubah / hapus** peserta di modul ini; data diperbaiki di Master Peserta. | `[MPS-2]` + `[USULAN]` (tanpa edit) |
| MP-FR-06 | **Cetak Daftar Peserta** (PDF A4, template usulan — format Labmania belum ada `[MPS-2]`, Q-MPS-03): kop PT LabMania Indonesia Jaya; judul **DAFTAR PESERTA**; info Program, No. Program, Tanggal, Lokasi, Trainer, Company (B2B), Jumlah peserta; tabel No, Nama Peserta, Instansi / Company, No. WA, Email; maksimal 18 baris per halaman; kaki: kode dokumen (menyusul), "Halaman x dari y". | `[MPS-2]` + `[USULAN]` |
| MP-FR-07 | **Cetak Daftar Hadir**. **To be: format Labmania** `FR/LMI-TR-05/17/Ed.Rev-1.0` (`input/FR LMI TR 05 17 Daftar Hadir Peserta OKk - Copy.pdf`, `input/Master - Buat Program/2026-11-10-format-cetak-skt-hadir-name-table/`), menggantikan template usulan (satu lembar per hari, tanda tangan selang-seling, tanda tangan Trainer & Penyelenggara). PDF **A4 landscape**: logo kiri, "PT LABMANIA INDONESIA" kanan; judul **DAFTAR HADIR PESERTA**; **Jenis Kegiatan** (= Varian Pelaksanaan program `input/Master - Buat Program/2026-11-10-jawaban-format-cetak/`), **Judul Kegiatan**, **Hari/Tanggal** = **hari dan tanggal pelaksanaan program** `input/Master - Buat Program/2026-11-10-daftar-hadir-tanggal-program/`, **Tempat** (lokasi program); tabel **No., Nama, Instansi, No. HP, Email, Tanda Tangan, Ket.**, **10 baris per halaman**, nomor berlanjut antar halaman; Nama, Instansi, No. HP, Email terisi dari peserta Deal, Tanda Tangan dan Ket. kosong; halaman terakhir digenapkan dengan baris kosong bernomor sampai 10 baris `[USULAN]`; di bawah tabel "Keterangan : Mohon isikan nama dengan lengkap boleh ditambahkan dengan gelar karena akan dijadikan acuan dalam penulisan di sertifikat."; kaki kode dokumen · "x dari y" · "Berlaku 01/08/2023". Sekali cetak menghasilkan **satu set per hari pelaksanaan** (durasi Penjualan Program; hari ke-n = tanggal mulai + n - 1) `input/Master - Buat Program/2026-11-10-daftar-hadir-tanggal-program/`; nomor halaman "x dari y" dihitung per set `[USULAN]`. | `[MPS-2]`, `input/Master - Buat Program/2026-11-10-format-cetak-skt-hadir-name-table/` + `[USULAN]` |
| MP-FR-08 | **Export Excel**: berkas `.xlsx` "Peserta_[No. Program]" berisi baris judul (nomor, judul, tanggal, lokasi program) dan kolom No, Kode, Nama, Instansi / Company, No. WA (format teks agar angka 0 di depan tidak hilang), Email, No. Quotation. | `[MPS-2]` + `[USULAN]` (kolom) |
| MP-FR-09 | **Dampak Close ke Pengajuan Biaya** `[JPB-12]`: program Closed tidak dapat ditambah pengajuan / overbudget baru (tombol Add tidak tampil, server menolak); pengajuan yang ada tetap dapat dilihat dan yang masih Menunggu tetap dapat diproses `[USULAN]`. Lihat `fr-nfr-pengajuan-biaya.md` PB-FR-20. Dampak ke Quotation / Invoice **belum ditetapkan** (Q-MPS-05). | `[JPB-12]` + `[USULAN]` |
| MP-FR-10 | **Activity Log** `[STD]`: `write_log()` modul "Manage Peserta" untuk **Close Program** (status Final → Closed, `old`/`new`), **Cetak** (Daftar Peserta / Daftar Hadir / Name Table) dan **Export** (nomor program). | `[STD]` + `[USULAN]` (catat cetak/export) |
| MP-FR-11 | **Cetak Name Table** (to be) `input/Master - Buat Program/2026-11-10-format-cetak-skt-hadir-name-table/`: format Labmania `input/Name Table.pptx`. PDF **A4 landscape**, **satu peserta Deal satu lembar**, semua peserta Deal program dicetak sekaligus. Lembar dilipat dua di garis tengah: separuh bawah **Nama** (Times New Roman 40 pt tebal, rata tengah) dan **Instansi / Company** (38 pt) di bawahnya; separuh atas isi yang sama diputar 180° agar terbaca dari sisi meja yang lain. Tanpa logo, kode dokumen, dan tanggal. Dicatat di Activity Log (MP-FR-10). | `input/Master - Buat Program/2026-11-10-format-cetak-skt-hadir-name-table/` |

### 5.2 Sertifikat peserta (to be)

Sistem belum punya tempat mencatat sertifikat `input/Invoice & Pengajuan/2026-11-09-sertifikat-monitoring-sesuai-sistem/`. Tab baru **Sertifikat** di halaman peserta program (Manage Peserta › Lihat Peserta), di samping tab **Daftar Peserta**.

| ID | Kebutuhan | Sumber |
|---|---|---|
| SRT-FR-01 | Halaman peserta program punya dua tab: **Daftar Peserta** (MP-FR-04 – MP-FR-08, tetap) dan **Sertifikat**. Tab Sertifikat: satu baris per peserta Deal program itu; kolom No, Peserta (nama, instansi), **Sertifikat Pelatihan** (nomor atau "Belum terbit", status kirim + tanggal + dikirim lewat, nama file), dan khusus program bertipe Sertifikasi (Jenis Produk Sertifikasi pada salah satu Product): **Sertifikasi LSP / BNSP** (hasil, metode, LSP, skema, nomor BNSP, tanggal diterima dari LSP dan dikirim ke peserta, ID card) dan **Berlaku sampai** (tanggal + sisa hari); Action (menu gear) **Buat Sertifikat** / **Lihat Sertifikat** dan **Pengiriman Sertifikat** (program Sertifikasi: **Pengiriman & Sertifikasi BNSP**); di atas tabel: "n dari m sertifikat pelatihan sudah dibuat", tombol **Buat sertifikat (k belum)** dan **Lihat semua** `input/Invoice & Pengajuan/2026-11-09-buat-sertifikat-di-sistem/`; pencarian nama, instansi, quotation, nomor sertifikat. | `input/Invoice & Pengajuan/2026-11-09-sertifikat-monitoring-sesuai-sistem/` + `[USULAN]` (kolom) |
| SRT-FR-03 | **Sertifikat pelatihan dibuat di sistem** `input/Invoice & Pengajuan/2026-11-09-buat-sertifikat-di-sistem/`: **Buat Sertifikat** (per peserta) atau **Buat sertifikat** (semua peserta yang belum) memberi **nomor otomatis** dan tanggal terbit = hari dibuat, lalu menampilkan pratinjau PDF (satu halaman per peserta) yang dapat dicetak / disimpan. Isi dari data sistem: nama peserta, instansi, judul program, varian, tanggal, lokasi, Jam Pembelajaran, trainer, tanggal terbit. **Format sertifikat dan nomor menyusul** dari LabMania; sementara template usulan dan nomor `SRT/LMI/MM/yy-zzz` urut per bulan terbit (Q-MON-06). Nomor tidak berubah setelah dibuat; Lihat Sertifikat mencetak ulang. Sertifikat **BNSP tidak dibuat** di sistem (diterbitkan LSP/BNSP) dan tetap diunggah. | `input/Invoice & Pengajuan/2026-11-09-buat-sertifikat-di-sistem/` + `[USULAN]` (template, nomor) |
| SRT-FR-02 | **Pengiriman Sertifikat** (form per peserta): **Sertifikat pelatihan** — nomor dan tanggal terbit tampil (dari SRT-FR-03, tidak diketik), status kirim (Belum dikirim / Dikirim softcopy / Dikirim hardcopy), tanggal kirim, dikirim lewat; **Sertifikasi LSP / BNSP** (hanya program bertipe Sertifikasi) — LSP (awal dari isian link "LSP" bila ada; teks, Q-MON-07), skema, metode (Uji Kompetensi / Portofolio), hasil (Belum diuji / Kompeten / Belum Kompeten), nomor sertifikat BNSP, berlaku sampai (awal dari isian "Tanggal Habis Sertifikat" bila ada), tanggal diterima dari LSP, tanggal dikirim ke peserta, ID card, unggah file. Validasi: status dikirim wajib tanggal kirim dan sertifikat sudah dibuat; tanggal dikirim ke peserta wajib ada tanggal diterima dan tidak sebelum tanggal itu. Perubahan dicatat Activity Log modul "Manage Peserta" sub "Sertifikat". | `input/Invoice & Pengajuan/2026-11-09-sertifikat-monitoring-sesuai-sistem/` + `[USULAN]` |

### 5.3 Monitoring Peserta (to be)

Sub menu baru di modul Manage Peserta `input/Invoice & Pengajuan/2026-11-09-monitoring-peserta/`, acuan kolom file Labmania `input/Data Peserta In House Training, Public Training, Training dan Sertifikasi 2026.xlsx`. Monitoring **hanya membaca data yang sudah ada di sistem** (peserta dari link, program, PIC, invoice, sertifikat dari tab Sertifikat) `input/Invoice & Pengajuan/2026-11-09-sertifikat-monitoring-sesuai-sistem/`; satu-satunya isian di sini adalah **catatan**.

| ID | Kebutuhan | Sumber |
|---|---|---|
| MON-FR-01 | Menu **Manage Peserta** mendapat sub menu **Monitoring Peserta**. Satu baris per **peserta Deal** dari semua Penjualan Program Final (sumber sama dengan MP-FR-04). | `[MON]` |
| MON-FR-02 | **Daftar**: No; **Peserta** (nama, jabatan, instansi, WA, email); **Program** (nomor, Public/B2B, judul, tanggal, **Tipe** = Pelatihan / Sertifikasi / Training & Sertifikasi dari Jenis Produk semua Product di Master Program, lokasi, **PIC** = Sales quotation, nomor quotation); **Invoice** (nomor invoice dari modul Invoice, atau "Uninvoiced"; status lunas belum ada di sistem, Q-MON-02); **Sertifikat Pelatihan** dan **Sertifikasi LSP / BNSP** dan **Berlaku sampai** (dari tab Sertifikat, SRT-FR-01); **Catatan**; Action (menu gear): **Kelola Sertifikat** (membuka halaman peserta program di tab Sertifikat), **Catatan**, **Lihat quotation**. | `[MON]`, `input/Invoice & Pengajuan/2026-11-09-sertifikat-monitoring-sesuai-sistem/` + `[USULAN]` (susunan) |
| MON-FR-03 | **Satu daftar tanpa tab** `input/Invoice & Pengajuan/2026-11-09-monitoring-satu-daftar/`: jenis kegiatan dipilih lewat **filter Tipe** (Semua / Pelatihan / Sertifikasi / Training & Sertifikasi, dengan jumlah peserta per tipe), ditambah filter tindak lanjut, Tahun, Bulan (tanggal acara), dan **pencarian** (peserta, instansi, kontak, program, quotation, PIC, LSP, nomor sertifikat). Daftar ini menggantikan sheet-sheet Excel Labmania; sheet Resertifikasi diwakili filter "Sertifikat habis ≤ 90 hari" (MON-FR-04). | `input/Invoice & Pengajuan/2026-11-09-monitoring-satu-daftar/` |
| MON-FR-04 | **Ringkasan tindak lanjut** di atas tabel (tombol yang sekaligus menyaring), semuanya dihitung dari data sistem: **Belum dibuatkan invoice**; **Sertifikat pelatihan belum dikirim** (acara sudah selesai); **Sertifikat BNSP belum diterima** (hasil Kompeten, tanggal diterima kosong); **Sertifikat habis ≤ 90 hari**. | `[USULAN]` |
| MON-FR-05 | **Catatan** monitoring per peserta (maks. 500 karakter), dicatat Activity Log modul "Manage Peserta" sub "Monitoring Peserta". Tidak ada isian status lain di Monitoring `input/Invoice & Pengajuan/2026-11-09-sertifikat-monitoring-sesuai-sistem/`. | `input/Invoice & Pengajuan/2026-11-09-sertifikat-monitoring-sesuai-sistem/` |
| MON-FR-06 | **Export Excel** semua baris sesuai tab/filter: No, Nama, Instansi, Jabatan, NIK KTP, Email, WA Aktif, Judul Program, No. Program, Tanggal Pelaksanaan, Tipe, Lokasi, PIC, Nama PIC Customer, No. Quotation, No Invoice, No. Sertifikat Pelatihan, Status Kirim Sertifikat, Tanggal Kirim, Ukom/Porto, LSP, Skema, Hasil, No. Sertifikat BNSP, Penerimaan BNSP, Pengiriman BNSP, ID Card, Berlaku Sampai, Catatan. NIK dan WA sebagai teks. | `[MON]` |

## 6. Kebutuhan Non-Fungsional

| ID | Kategori | Kebutuhan | Kriteria uji | Sumber |
|---|---|---|---|---|
| MP-NFR-01 | Keamanan | Hak View / Manage diperiksa di server (`has_permission()`), bukan hanya menyembunyikan tombol; Close Program ditolak bila program sudah Closed atau bukan Final. | Request Close tanpa hak Manage → 403; Close dua kali → ditolak | `[USULAN]` |
| MP-NFR-02 | Konsistensi | Peserta dibaca langsung dari quotation Deal (tanpa salinan), sehingga perubahan Master Peserta langsung tampil. | Ubah nama peserta di Master Peserta → halaman & cetak ikut berubah | `[USULAN]` |
| MP-NFR-03 | Dokumen | PDF dibuat server-side dari template HTML/CSS (A4), identik dengan pratinjau — pola QTN-NFR-06. Excel memakai pustaka spreadsheet aplikasi (mis. PhpSpreadsheet). | Pratinjau = PDF unduhan; Excel terbuka tanpa peringatan format | `[USULAN]` |

## 7. Data

**To be:** tabel baru `quotation_peserta_sertifikat` (1 : 1 dengan peserta Deal di `quotation_peserta`) menyimpan data sertifikat SRT-FR-02 dan catatan monitoring MON-FR-05; data peserta, program, PIC, dan invoice tetap dibaca dari tabelnya masing-masing (tanpa salinan). As is: tanpa tabel baru `[USULAN]`. Close Program memakai kolom yang sudah ada di `penjualan_program`:
`closed_by` (user) dan `closed_date` (waktu) — **terisi = Closed**; `status` tetap `final`. Peserta dibaca dari
`quotation` (status `deal`) → `quotation_peserta` (peserta Deal) → `ms_peserta`; trainer dari `penjualan_program.id_trainer`.

## 8. Pertanyaan Terbuka

| ID | Pertanyaan | Terkait |
|---|---|---|
| Q-MPS-01 | Kapan program masuk tab **Selesai**? Usulan: tanggal selesai acara lewat atau sudah Closed (sama dengan Quotation Q-QTN-09). | MP-FR-02 |
| Q-MPS-02 | **Close Program** boleh kapan saja dengan peringatan (usulan), atau hanya setelah acara selesai / semua invoice dibuat? Perlu tombol **Buka Kembali**? | MP-FR-03 |
| Q-MPS-03 | Format **Daftar Peserta**: kolom sudah cukup? Kode dokumen (FR/LMI-…)? | MP-FR-06 |
| Q-MPS-04 | ~~Format **Daftar Hadir**?~~ **Terjawab** `input/Master - Buat Program/2026-11-10-format-cetak-skt-hadir-name-table/`: format Labmania `FR/LMI-TR-05/17` (tanpa kolom jam/sesi, tanpa tanda tangan Trainer & Penyelenggara); Jenis Kegiatan = Varian Pelaksanaan `input/Master - Buat Program/2026-11-10-jawaban-format-cetak/`; Hari/Tanggal = **tanggal program**, satu set per hari pelaksanaan `input/Master - Buat Program/2026-11-10-daftar-hadir-tanggal-program/`. | MP-FR-07 |
| Q-MPS-05 | Setelah Closed, apakah **Quotation** (buat / Deal) dan **Invoice** juga dikunci? | MP-FR-09 |
| Q-MON-01 | Monitoring dibuat **satu daftar dengan filter Tipe** `input/Invoice & Pengajuan/2026-11-09-monitoring-satu-daftar/` menggantikan sheet-sheet Excel. Masih perlu dipastikan: Excel ini dipakai siapa dan untuk apa (Admin mengejar sertifikat, Finance menagih)? **Pendampingan** ikut dikelola di sistem ini (belum ada sebagai jenis produk)? | MON-FR-03 |
| Q-MON-02 | Status **lunas/belum bayar** belum ada di sistem (modul Invoice belum mencatat pelunasan), jadi Monitoring hanya menampilkan nomor invoice / Uninvoiced. Perlu fitur pelunasan di modul Invoice supaya status bayar tampil di Monitoring? | MON-FR-02 |
| Q-MON-03 | Sheet IHT/Pendampingan merekap **per program/instansi** (jumlah peserta, status bayar, sertifikat dikirim per batch). Masih perlu tampilan rekap per program terpisah, atau cukup daftar program di **Manage Peserta** (usulan)? | MON-FR-02 |
| Q-MON-04 | Siapa yang mengisi **Input Sertifikat** dan catatan monitoring (Sales / Admin / Operasional)? Hak diatur di Role & Permission (usulan: Manage pada Manage Peserta). | SRT-FR-02, MON-FR-05 |
| Q-MON-05 | Pengingat **resertifikasi**: cukup penanda ≤ 90 hari (usulan), atau perlu notifikasi / daftar tersendiri? Berapa hari sebelum habis? | MON-FR-04 |
| Q-MON-06 | **Format sertifikat dan nomor** menyusul dari LabMania `input/Invoice & Pengajuan/2026-11-09-buat-sertifikat-di-sistem/`. Sementara template usulan dan nomor `SRT/LMI/MM/yy-zzz` (urut per bulan terbit). Penanda tangan siapa (Trainer, Direktur)? Satu sertifikat per peserta per program, atau per Product bila program berisi lebih dari satu Product? Boleh dibuat sebelum acara selesai? | SRT-FR-03 |
| Q-MON-07 | **LSP** dan **skema** diketik bebas (usulan sementara). Perlu **Master LSP** dan **Master Skema** di Master Data supaya pilihan seragam? | SRT-FR-02 |

## 9. Riwayat

| Versi | Tanggal | Perubahan |
|---|---|---|
| draf 1 | 04-11-2026 | Penyusunan awal dari `input/Invoice & Pengajuan/2026-11-04-manage-peserta/` `[MPS-1]`, `[MPS-2]`: Fase 1 (MP-FR-01 – MP-FR-10), template cetak usulan, Close Program mengisi status Closed `[JPB-12]`; mockup draf 14e. |
| **to be draf 1** | 09-11-2026 | Dokumen to be dipisah dari as is draf 1. `input/Invoice & Pengajuan/2026-11-09-monitoring-peserta/`: sub menu **Monitoring Peserta** (bagian 5.2, MON-FR-01 – MON-FR-06, UC-66, UC-67, Q-MON-01 – Q-MON-05). ERD to be: `quotation_peserta_monitoring`. |
| **to be draf 1a** | 09-11-2026 | `input/Invoice & Pengajuan/2026-11-09-sertifikat-monitoring-sesuai-sistem/`: tab **Sertifikat** baru di halaman peserta program (bagian 5.2, SRT-FR-01, SRT-FR-02, UC-67); **Monitoring Peserta hanya membaca data sistem** + catatan, isian status bayar dan sertifikat di Monitoring dihapus (bagian 5.3, MON-FR-02 – MON-FR-05); Q-MON-02, -04 ditulis ulang, Q-MON-06, -07 baru. ERD: `quotation_peserta_monitoring` diganti `quotation_peserta_sertifikat`. |
| **to be draf 1b** | 09-11-2026 | `input/Invoice & Pengajuan/2026-11-09-buat-sertifikat-di-sistem/`: **sertifikat pelatihan dibuat di sistem** dengan nomor otomatis dan template sementara (SRT-FR-03 baru; SRT-FR-01, SRT-FR-02 disesuaikan; Q-MON-06 ditulis ulang). Sertifikat BNSP tetap diunggah. |
| **to be draf 1c** | 09-11-2026 | `input/Invoice & Pengajuan/2026-11-09-monitoring-satu-daftar/`: Monitoring Peserta **satu daftar tanpa tab**, filter **Tipe** menggantikan tab (MON-FR-03); Q-MON-01, Q-MON-03 ditulis ulang. |
| **to be draf 1d** | 10-11-2026 | `input/Master - Buat Program/2026-11-10-format-cetak-skt-hadir-name-table/`: **Daftar Hadir** memakai format Labmania `FR/LMI-TR-05/17` (A4 landscape, 10 baris/halaman, Hari/Tanggal = tanggal cetak; MP-FR-07 ditulis ulang, Q-MPS-04 terjawab); **Cetak Name Table** baru (MP-FR-11, MP-FR-04, MP-FR-10, UC-51). |
| **to be draf 1e** | 10-11-2026 | `input/Master - Buat Program/2026-11-10-jawaban-format-cetak/`: Jenis Kegiatan Daftar Hadir = Varian Pelaksanaan (dikonfirmasi); Q-MPS-04 bagian cetak per hari masih terbuka. |
| **to be draf 1f** | 10-11-2026 | `input/Master - Buat Program/2026-11-10-daftar-hadir-tanggal-program/`: Hari/Tanggal Daftar Hadir = **tanggal program**, sekali cetak satu set per hari pelaksanaan (MP-FR-07; Q-MPS-04 terjawab penuh). |
