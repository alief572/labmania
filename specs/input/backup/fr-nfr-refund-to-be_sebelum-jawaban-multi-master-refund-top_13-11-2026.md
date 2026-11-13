# Kebutuhan Fungsional & Non-Fungsional — Refund Labmania · TO BE

- **Versi:** **TO BE** draf 1 — 13-11-2026 (dokumen baru; sebelumnya tab Refund di Sales Order, `docs-to-be/fr-nfr-sales-order.md` SO-FR-17 – SO-FR-20)
- **Sumber:** permintaan BA 13-11-2026, `input/Master - Buat Program/2026-11-13-multi-master-batal-top-refund/` butir 6 `[MMB-6]` (dan butir 2 untuk refund karena program batal `[MMB-2]`); isi Proses Refund dari `[JRR-2]`, `[JRR-9]` (lihat SO-FR-17 – SO-FR-19)
- **Status:** DRAF TO BE, menunggu konfirmasi Labmania (pertanyaan di bagian 7)
- **Paket:** Invoice - Pengajuan (modul sidebar **Refund**)
- **Mockup:** `ui-mockup/produk-to-be/Labmania.html` → Refund › Daftar Refund, Approval Refund (`mod-refund.js`)
- **ERD:** `docs-to-be/diagrams/erd.drawio` halaman *ERD Quotation*, tabel `refund`

Penomoran memakai awalan **RF-**.

## Penanda sumber

| Penanda | Sumber |
|---|---|
| `[MMB-n]` | permintaan BA 13-11-2026, `input/Master - Buat Program/2026-11-13-multi-master-batal-top-refund/` butir n |
| `[JRR-n]` | jawaban reschedule & refund 12-11-2026, `input/Master - Buat Program/2026-11-12-jawaban-reschedule-refund-asisten/` |
| `[STD]` | standar proyek di `AGENTS.md` root repository (write_log) |
| `[USULAN]` | usulan analis, **belum** dinyatakan klien |

## 1. Ringkasan

Refund adalah pengembalian dana kepada peserta atau company yang **sudah membayar** invoice lalu tidak ikut program.
Refund punya modul sendiri dengan dua menu: **Daftar Refund** (semua refund, tempat Finance memproses transfer) dan
**Approval Refund** (refund yang menunggu persetujuan) `[MMB-6]`. Refund tidak pernah diinput manual: refund terbentuk
otomatis dari proses lain. Invoice tidak diedit; jurnal pengembalian dikerjakan Finance di akuntansi.

## 2. Aktor

| Aktor | Peran |
|---|---|
| Sales / Marketing | Memproses peserta tidak ikut di Quotation (sumber refund) |
| Pemegang hak Approve di Approval Refund | Menyetujui atau menolak refund (siapa orangnya: Q-RF-01) |
| Finance | Memproses transfer refund yang sudah disetujui |

## 3. Tabel Use Case

| ID | Use case | Aktor | Pemicu | Alur | Hasil |
|---|---|---|---|---|---|
| UC-RF-1 | Menyetujui / menolak refund | Pemegang hak Approve | Ada refund Menunggu Approval | Approval Refund → gear **Proses (Setujui / Tolak)** → pilih keputusan, catatan (wajib bila Tolak) → Simpan Keputusan | Disetujui, menunggu transfer / Ditolak |
| UC-RF-2 | Memproses transfer refund | Finance | Refund berstatus Disetujui | Daftar Refund → gear **Proses Refund** → nominal, bank, rekening, atas nama, bukti transfer → Simpan | Selesai |

## 4. Kebutuhan Fungsional (FR)

| ID | Kebutuhan | Sumber |
|---|---|---|
| RF-FR-01 | **Modul Refund** di sidebar (paket Invoice - Pengajuan) dengan menu **Daftar Refund** dan **Approval Refund**. Tab Refund di Sales Order dihapus. Hak akses lewat Role & Permission: Daftar Refund = Lihat + Manage (Proses Refund); Approval Refund = Lihat + Approve. | `[MMB-6]` + `[USULAN]` (jenis hak) |
| RF-FR-02 | **Pembentukan refund** (otomatis): (a) peserta yang sudah membayar memilih / diproses **Refund** setelah reschedule (QTN-FR-36), atau tidak dicentang saat Deal ulang (QTN-FR-42); (b) **Penjualan Program dibatalkan** dan invoice-nya sudah dibayar (PJ-FR-33): satu refund per invoice yang sudah dibayar `[MMB-2]` `[USULAN]`. Isi: nomor `LMI/RF/MM/yy-zzz` `[USULAN]`, tanggal, SO (boleh SO Batal), quotation, peserta (B2B / program batal dengan invoice gabungan: company), invoice, **sebab** (Tidak ikut setelah reschedule / Penjualan Program dibatalkan), alasan, **nominal tagihan** (tagihan peserta; TOP: nilai peserta × persentase termin yang sudah dibayar, QTN-FR-44; program batal: total invoice). Status awal **Menunggu Approval**. | `[MMB-2]`, `[MMB-6]`, `[JRR-2]` + `[USULAN]` |
| RF-FR-03 | **Daftar Refund**: kolom No, Nomor Refund (+ tanggal; klik = View), Peserta / Company (+ sebab dan alasan), SO / Invoice, Nominal (bila transfer berbeda: nominal transfer + tagihan), Status (+ siapa yang menyetujui dan kapan; Selesai: oleh siapa), Action (gear: **View**; **Proses Refund** hanya bila Disetujui dan punya hak Manage). Filter status dan cari (nomor, peserta, SO, invoice, quotation). Urutan: Disetujui (perlu ditransfer) di atas, lalu Menunggu Approval, Ditolak, Selesai. | `[MMB-6]` + `[USULAN]` (urutan) |
| RF-FR-04 | **Proses Refund** (Finance): tampilan dan isian sama dengan sebelumnya (SO-FR-19): nominal refund sesuai S&K (boleh kurang dari tagihan; keterangan potongan wajib bila kurang), bank, nomor rekening, atas nama, bukti transfer wajib (PDF/JPG/PNG maks. 5 MB). Ringkasan menampilkan siapa yang menyetujui. Hanya untuk refund **Disetujui**; refund lain menolak dengan pesan. Simpan → **Selesai**, tidak dapat diubah lagi. | `[MMB-6]`, SO-FR-19 |
| RF-FR-05 | **Approval Refund**: tab **Menunggu Approval** (jumlah) dan **Sudah diputuskan**. Kolom sama dengan Daftar Refund. Gear: View; **Proses (Setujui / Tolak)** bila Menunggu Approval dan punya hak Approve. Popup menampilkan peserta/company, sebab, alasan, invoice, nominal tagihan; keputusan **Setujui** (refund lanjut ke Finance) atau **Tolak** (refund tidak diproses, **catatan wajib**); catatan opsional bila Setujui. Keputusan dicatat (siapa, kapan, catatan) dan tampil di View. | `[MMB-6]` + `[USULAN]` (catatan wajib bila tolak) |
| RF-FR-06 | **Activity Log** modul "Refund": Tambah (refund terbentuk), Approval Refund (Setujui / Tolak, before/after status), Proses Refund (Selesai). | `[STD]` |

## 5. Kebutuhan Non-Fungsional (NFR)

| ID | Kebutuhan | Sumber |
|---|---|---|
| RF-NFR-01 | Refund tidak dapat dihapus atau dibuat manual; satu invoice per peserta paling banyak satu refund aktif. | `[USULAN]` |
| RF-NFR-02 | Invoice tidak diubah oleh refund (status bayar tetap); pengembalian tercatat hanya di refund dan jurnal Finance. | SO-FR-19 |

## 6. Status Refund

| Dari | Aksi | Ke |
|---|---|---|
| (terbentuk otomatis) | RF-FR-02 | **Menunggu Approval** |
| Menunggu Approval | Setujui | **Disetujui, menunggu transfer** |
| Menunggu Approval | Tolak (catatan wajib) | **Ditolak** (akhir) |
| Disetujui | Proses Refund | **Selesai** (akhir) |

## 7. Pertanyaan Terbuka

| ID | Pertanyaan | Terkait |
|---|---|---|
| Q-RF-01 | Siapa yang menyetujui refund (Manajemen? Finance manager?) Mockup: siapa pun dengan hak Approve di Approval Refund. | RF-FR-05 |
| Q-RF-02 | Refund yang **ditolak**: apa yang terjadi pada peserta (tetap Keluar tanpa pengembalian dana)? Bisakah diajukan ulang? | RF-FR-05 |
| Q-RF-03 | Program dibatalkan dan peserta sudah bayar: refund otomatis penuh (usulan), atau ada pilihan lain (dialihkan ke program lain)? | RF-FR-02 |
| Q-RF-04 | Quotation TOP: refund dihitung dari termin yang sudah dibayar (usulan)? | RF-FR-02, QTN-FR-44 |

## 8. Riwayat Perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| **to be draf 1** | 13-11-2026 | Dokumen baru `[MMB-6]`: Refund pindah dari tab Sales Order ke modul sendiri dengan **Approval Refund**; status Menunggu Approval → Disetujui → Selesai / Ditolak; refund karena Penjualan Program dibatalkan `[MMB-2]`. ERD to be: `refund.status` (menunggu/disetujui/ditolak/selesai), `refund.sebab`, `approval_by`, `approval_date`, `approval_catatan`. |
