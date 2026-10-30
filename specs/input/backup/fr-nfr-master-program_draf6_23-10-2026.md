# Kebutuhan Fungsional & Non-Fungsional — Modul Master Program Labmania

- **Versi:** draf 6 — 23-10-2026
- **Sumber:** `input/2026-10-23-requirements-3-modul/` (*Master Program - Labmania.xlsx*: sheet *Requirements Program*, *Cost Master*, *Simulasi Costing*), `input/2026-10-23-klarifikasi-program/`, `input/2026-10-23-konfirmasi-3-modul/`, `input/2026-10-23-arah-penjualan-program/`, `input/2026-10-23-revisi-rate-harga-simulasi/`, `input/2026-10-23-simulasi-bep/`, `input/2026-10-23-layout-program-public/` (layout)
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
| `[USR-3M]` | penjelasan user 23-10-2026, `input/2026-10-23-requirements-3-modul/` |
| `[KPG-n]` | klarifikasi chat 23-10-2026, `input/2026-10-23-klarifikasi-program/` |
| `[SIMX]` | tampilan simulasi, `input/2026-10-23-simulasi-hasil-html/` (`input/simulasi_hasil.html`) |
| `[BEP-1]` | permintaan data BEP 23-10-2026, `input/2026-10-23-simulasi-bep/` |
| `[REV-n]` | revisi 23-10-2026, `input/2026-10-23-revisi-rate-harga-simulasi/` |
| `[K3M-n]` | konfirmasi chat 23-10-2026, `input/2026-10-23-konfirmasi-3-modul/` |
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

Menu: modul **Master Program**, sub modul **Program**, menu **Daftar Master Program** (nama usulan, Q-MP-04).

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
| UC-19 | Menyusun Master Program | *TBD (Q-MP-01)* | Master Program | Program | Paket Produk aktif, Durasi (hari), Target peserta, Harga jual standar / peserta, Target profit (opsional), nominal item Fixed & Variable Cost, biaya manual | Aktor memilih Paket Produk dan mengisi angka baseline. Sistem menampilkan semua item Fixed Cost aktif dengan Default Cost; aktor menyesuaikan nominal bila perlu, menambah item Variable Cost satu per satu, dan bila perlu biaya manual di luar master. Simulasi (Total Fixed, Total Variable, HPP, Profit, Harga BEP / peserta, Jumlah peserta BEP) dihitung ulang setiap angka berubah. Aktor menyimpan sebagai Draft dan dapat mengubahnya lagi selama Draft. | Master Program berstatus Draft dengan Nomor Master Program, costing, dan simulasi | Master Program, Biaya Master Program, Paket Produk, Rate Fixed Cost, Rate Variable Cost, Satuan | MP-FR-01 – MP-FR-10 |
| UC-20 | Memfinalkan Master Program | *TBD (Q-MP-01)* | Master Program | Program | Master Program berstatus Draft | Aktor menekan "Finalkan program". Sistem menampilkan popup ringkasan (durasi, target peserta, harga jual, HPP, profit, BEP) dan peringatan bila profit minus atau BEP di atas target. Setelah dikonfirmasi status menjadi Final tanpa berpindah halaman; program terkunci dan muncul sebagai pilihan di Penjualan Program. | Master Program Final, terkunci, tersedia di Penjualan Program | Master Program | MP-FR-11 – MP-FR-13, MP-FR-15, MP-FR-16 |
| UC-21 | Membatalkan Master Program | *TBD (Q-MP-01)* | Master Program | Program | Master Program berstatus Draft atau Final | Aktor memilih Batalkan dan mengonfirmasi. Status menjadi Batal; program tidak muncul lagi di pilihan Penjualan Program. Penjualan Program yang sudah terbentuk dari program ini tidak terpengaruh. | Master Program berstatus Batal | Master Program | MP-FR-11, MP-FR-14, MP-FR-15 |

> Semua UC belum lolos uji admission no. 1 (aktor bernama) — Q-MP-01.

---

## 4. Kebutuhan Fungsional (FR)

### 4.1 Data Master Program

| ID | Kebutuhan | Sumber |
|---|---|---|
| MP-FR-01 | **Daftar Master Program** menampilkan: No, Nomor, Judul (dari Paket Produk), Durasi, Target peserta, Harga jual standar / peserta, HPP, Status, Action (menu gear sesuai status). Dapat disaring per status. | `[MP-1]` + `[USULAN]` (kolom daftar) |
| MP-FR-02 | Master Program berisi: (1) **Product Combine** — satu Paket Produk berstatus Aktif dari Master Data; (2) **Durasi (hari)**; (3) **Target peserta** (baseline); (4) **Harga jual standar / peserta** (baseline); (5) **Target profit** (opsional); (6) item costing Fixed Cost & Variable Cost. **Tidak ada** pilihan Public/B2B, customer, tanggal, atau lokasi acara. | `[MP-1]`, `[MP-3]`, `[MP-4]`, `[USR-3M]` |
| MP-FR-03 | Master Program mendapat **Nomor Master Program** otomatis saat pertama disimpan (format Q-MP-02). Satu Paket Produk boleh dipakai lebih dari satu Master Program. | `[USULAN]` |
| MP-FR-04 | Semua atribut hanya **dipilih** dari Master Data (Paket Produk, Master Rate), tidak diinput ulang. | `[MP-2]` |

### 4.2 Costing

| ID | Kebutuhan | Sumber |
|---|---|---|
| MP-FR-05 | Satu model costing untuk semua program: **Fixed Cost** (tidak bergantung jumlah peserta) dan **Variable Cost** (bergantung jumlah peserta). Berlaku sama untuk penjualan Public maupun B2B. | `[MP-12]` |
| MP-FR-06 | **Fixed Cost** langsung menampilkan **semua** item Fixed Cost aktif dari Master Rate, dikelompokkan per satuan: **Event** (sekali per acara) dan **Day** (× durasi). Biaya trainer yang di sumber "per jam" (Mandays Rate Charge, Honor Trainer) dihitung **sekali per event**; 1 event = 1 trainer. | `[MP-13]`, `[MP-16]`, `[KPG-1]`, `[K3M-1]` |
| MP-FR-07 | **Variable Cost** ditambah satu per satu (pilih dari Master Rate, lalu "+ Tambah") dan dapat dihapus selama Draft, dikelompokkan per satuan: **Peserta/Event** dan **Peserta/Day** (× durasi). Item yang sama tidak dapat ditambahkan dua kali. | `[MP-14]`, `[MP-16]` + `[USULAN]` (tanpa duplikat) |
| MP-FR-08 | Nominal item terisi **Default Cost** dari Master Rate dan **selalu dapat diedit** (tidak ada mode Fix / Input manual). Nama item, satuan, dan nominal disimpan sebagai salinan di Master Program. | `[MP-15]`, `[MD-8]`, `[REV-1]` + `[USULAN]` (salinan) |
| MP-FR-09 | Ada opsi **biaya manual di luar master** (tombol "+ Biaya lain") pada Fixed Cost dan Variable Cost: Nama biaya, Nominal; satuannya mengikuti kelompok tempat biaya ditambahkan. Biaya manual tidak menambah item ke Master Rate. | `[MP-16]` + `[USULAN]` (penempatan per kelompok) |
| MP-FR-10 | **Simulasi costing** dihitung ulang setiap angka berubah dan menampilkan: Total Fixed Cost, Total Variable Cost, HPP, Profit, Harga BEP / peserta, Jumlah peserta BEP, Target Profit, Harga jual (rumus bagian 8). Tampilan mengikuti `input/simulasi_hasil.html` `[SIMX]`: (1) **Titik impas (BEP)** — kotak *BEP jumlah peserta* = Fixed Cost ÷ (harga per peserta − biaya variable per peserta), dibulatkan ke atas, dan *BEP harga jual* = HPP ÷ jumlah peserta, masing-masing dengan kalimat penjelas (aman/kurang terhadap rencana); (2) **Hasil rencana simulasi** — kotak Pendapatan, Biaya (HPP), Profit (untung/rugi, margin % dari pendapatan), lalu baris **target profit** (tercapai / kurang) beserta **minimal peserta untuk capai target** = ⌈(Fixed Cost + target profit) ÷ (harga − biaya variable per peserta)⌉; (3) **Posisi di skala jumlah peserta** — batang zona rugi / untung belum capai target / target tercapai dengan penanda BEP, Target profit, Rencana; (4) **Rincian perhitungan** yang dapat dibuka/tutup — tabel komponen, cara hitung, nilai (termasuk *sumbangan per peserta*) dan tabel profit menurut jumlah peserta di sekitar BEP dan rencana. Peringatan (tidak menghalangi simpan Draft): profit minus; BEP > target peserta; profit < target profit; Hotel Trainer \*\* dan \*\*\* keduanya terisi (hanya peringatan `[K3M-6]`). | `[MP-17]`, `[MP-WISH-1]`, `[MP-WISH-2]`, `[MP-SIM]`, `[MP-9]`, `[MP-OP-2]`, `[REV-4]`, `[BEP-1]`, `[SIMX]` |

### 4.3 Status

| ID | Kebutuhan | Sumber |
|---|---|---|
| MP-FR-11 | Status: **Draft → Final**, atau **Batal** (transisi di bagian 6). Program baru tersimpan Draft; selama Draft semua isian dapat diubah. | `[MP-5]`, `[MP-6]` |
| MP-FR-12 | Aksi **"Finalkan program"** (status Draft) memunculkan popup ringkasan: durasi, target peserta, harga jual, HPP, profit, BEP, beserta peringatan bila profit minus atau BEP di atas target. Setelah dikonfirmasi, status menjadi **Final** tanpa berpindah halaman. | `[MP-9]` |
| MP-FR-13 | Master Program **Final**: angka menjadi baseline resmi, **terkunci** (hanya bisa dilihat), dan **muncul sebagai pilihan** di Penjualan Program. | `[MP-7]` |
| MP-FR-14 | Master Program **Batal**: tidak dipakai lagi dan **tidak muncul** di pilihan Penjualan Program. Penjualan Program yang sudah terbentuk dari program ini **tidak terpengaruh**. | `[MP-8]` |
| MP-FR-15 | Master Program Final **tidak bisa diubah**. Bila perlu perubahan master, batalkan lalu buat program baru. Penyesuaian harga/costing untuk penjualan dilakukan di Penjualan Program. | `[MP-10]` |
| MP-FR-16 | Tidak ada approval sebelum Final. | `[MP-11]` |
| MP-FR-17 | Master Program hanya menyimpan angka **baseline** yang disalin ke Penjualan Program. Peserta fix dan realisasi ada di modul Quotation (ditunda). Perbandingan As Is vs Aktual **dihapus** sampai konsepnya dijelaskan (Q-MP-07). | `[MP-18]`, `[REV-3]` |

---

## 5. Kebutuhan Non-Fungsional (NFR)

| ID | Kategori | Kebutuhan | Kriteria uji | Lingkup | Sumber |
|---|---|---|---|---|---|
| MP-NFR-01 | Audit | Simpan, ubah costing, finalkan, dan batal dicatat `write_log()` modul "Master Program"; ubah costing mencatat header `old`/`new` dan `detail_changes` per item (`Item [nama] - Harga`). | Mengubah 1 harga + durasi → 1 log dengan 2 perubahan | UC-19 – UC-21 | `[STD]` |
| MP-NFR-02 | Integritas data | Master Program Final/Batal tidak dapat diubah di server. | Request ubah costing ke program Final ditolak (403/422), data tetap | MP-FR-13 – MP-FR-15 | `[MP-7]`, `[MP-10]` |
| MP-NFR-03 | Integritas data | Simpan header + semua baris biaya dalam satu transaksi; konfirmasi Final memeriksa status di dalam transaksi. | Gagal di baris terakhir → tidak ada program baru; dua klik Final bersamaan → satu log | UC-19, UC-20 | `[USULAN]` |
| MP-NFR-04 | Akurasi | Uang DECIMAL(15,2), tampil rupiah tanpa desimal. Jumlah peserta BEP dibulatkan ke atas; bila harga jual ≤ biaya variable / peserta, BEP tampil "tidak tercapai". | Skenario `[MP-SIM]` no 1 sama dengan Excel setelah koreksi data awal; BEP 10,2 tampil 11 | MP-FR-10 | `[MP-WISH-2]` + `[USULAN]` |
| MP-NFR-05 | Validasi | Durasi dan target peserta bilangan bulat > 0; nominal ≥ 0; nama dan nominal biaya manual wajib diisi sebelum simpan. | Durasi 0 ditolak; biaya manual tanpa nama ditolak | MP-FR-02, MP-FR-09 | `[USULAN]` |
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
| Final | Batalkan | Batal | Hilang dari pilihan Penjualan Program; Penjualan Program lama tidak terpengaruh | `[MP-8]`, `[MP-10]` |

Final dan Batal tidak kembali ke Draft. Batal adalah status akhir.

---

## 7. Pertanyaan Terbuka

| ID | Pertanyaan | Mempengaruhi |
|---|---|---|
| Q-MP-01 | Siapa pengguna yang menyusun, memfinalkan, dan membatalkan Master Program? | UC-19 – UC-21 |
| Q-MP-02 | Format **Nomor Master Program**? | MP-FR-03 |
| Q-MP-04 | Nama modul/menu: "Master Program" / "Daftar Master Program" sudah sesuai? | Bagian 1 |
| Q-MP-07 | Bagaimana konsep **As Is vs Aktual** yang dimaksud? (perbandingan Master Program vs Penjualan Program sudah dihapus `[REV-3]`) | MP-FR-17 |
| Q-MP-06 | Bolehkan **menyalin** Master Program Final menjadi Draft baru (untuk revisi setelah dibatalkan)? | MP-FR-15 |

**Sudah terjawab dari Excel ini** (sebelumnya di `fr-nfr-program-penjualan.md`): biaya manual di luar master boleh (Q-PP-10 → MP-FR-09); program tidak lagi punya status Deal/Selesai (Q-PP-05, Q-PP-06 gugur); `[MP-OP-3]` item duplikat Transport dan `[MP-OP-4]` rate B2B vs Public sudah dijawab di `fr-nfr.md` bagian 6.2. *(23-10-2026, `input/2026-10-23-konfirmasi-3-modul/`)* Q-MP-03 — `[MP-OP-1]` Jam vs Hari: biaya trainer per event, 1 event = 1 trainer; *Simulasi Costing* di Excel yang mengalikan dengan hari tidak dipakai. Q-MP-05 — Hotel \*\*/\*\*\*: cukup peringatan.

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
| draf 2 | 23-10-2026 | `input/2026-10-23-konfirmasi-3-modul/`: biaya trainer "per jam" dihitung per event (MP-FR-06, Q-MP-03 terjawab); Hotel \*\*/\*\*\* cukup peringatan (MP-FR-10, Q-MP-05 terjawab). |
| draf 3 | 23-10-2026 | `input/2026-10-23-arah-penjualan-program/`: hilir Master Program adalah **Penjualan Program** (bukan quotation); MP-FR-17 — angka Master Program = As Is, Penjualan Program mengikuti perubahan; realisasi peserta fix pindah ke modul Quotation (ditunda). |
| draf 4 | 23-10-2026 | `input/2026-10-23-revisi-rate-harga-simulasi/`: tanpa mode harga — nominal item selalu dapat diedit (MP-FR-08, MP-NFR-05); simulasi ditata ulang agar mudah dibaca (MP-FR-10); As Is dihapus (MP-FR-17, Q-MP-07 baru). |
| draf 5 | 23-10-2026 | `input/2026-10-23-simulasi-bep/`: simulasi ditambah **Data BEP** — perhitungan BEP langkah demi langkah dan tabel profit menurut jumlah peserta (MP-FR-10). |
| draf 6 | 23-10-2026 | `input/2026-10-23-simulasi-hasil-html/`: tampilan simulasi mengikuti `input/simulasi_hasil.html` — kotak BEP, hasil rencana, skala peserta, rincian yang dapat dibuka/tutup; tambah margin % dan minimal peserta untuk target profit (MP-FR-10). |
