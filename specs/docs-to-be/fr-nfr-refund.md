# Kebutuhan Fungsional & Non-Fungsional — Refund Labmania · TO BE

- **Versi:** **TO BE** draf 1a — 13-11-2026 (dokumen baru; sebelumnya tab Refund di Sales Order, `docs-to-be/fr-nfr-sales-order.md` SO-FR-17 – SO-FR-20)
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
| `[JMR-n]` | jawaban BA 13-11-2026, `input/Master - Buat Program/2026-11-13-jawaban-multi-master-refund-top/` jawaban n |
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
| Sales / Marketing | Memproses peserta tidak ikut di Quotation (sumber refund); memperbaiki dan mengajukan ulang refund yang ditolak `[JMR-5]` |
| Finance (hak Approve di Approval Refund) | Menyetujui atau menolak refund `[JMR-4]` |
| Finance | Memproses transfer refund yang sudah disetujui |

## 3. Tabel Use Case

| ID | Use case | Aktor | Pemicu | Alur | Hasil |
|---|---|---|---|---|---|
| UC-RF-1 | Menyetujui / menolak refund | Finance | Ada refund Menunggu Approval | Approval Refund → gear **Proses (Setujui / Tolak)** → pilih keputusan, catatan (wajib bila Tolak) → Simpan Keputusan | Disetujui, menunggu transfer / Ditolak, perlu diperbaiki (kembali ke Sales/Marketing) |
| UC-RF-2 | Memproses transfer refund | Finance | Refund berstatus Disetujui | Daftar Refund → gear **Proses Refund** → nominal, bank, rekening, atas nama, bukti transfer → Simpan | Selesai |
| UC-RF-3 | Memperbaiki dan mengajukan ulang refund ditolak | Sales / Marketing (hak Ajukan ulang) | Refund berstatus Ditolak, perlu diperbaiki | Daftar Refund → gear **Perbaiki & ajukan ulang** → baca alasan penolakan, perbaiki alasan dan/atau nominal tagihan, isi catatan perbaikan → **Ajukan ulang** | Kembali Menunggu Approval; riwayat approval & perbaikan tersimpan |

## 4. Kebutuhan Fungsional (FR)

| ID | Kebutuhan | Sumber |
|---|---|---|
| RF-FR-01 | **Modul Refund** di sidebar (paket Invoice - Pengajuan) dengan menu **Daftar Refund** dan **Approval Refund**. Tab Refund di Sales Order dihapus. Hak akses lewat Role & Permission: Daftar Refund = **View**, **Add** (Perbaiki & ajukan ulang refund ditolak; awalnya Sales/Marketing) dan **Manage** (Proses Refund; awalnya Finance); Approval Refund = View + **Approve** (awalnya **Finance** `[JMR-4]`). | `[MMB-6]`, `[JMR-4]`, `[JMR-5]` + `[USULAN]` (jenis hak) |
| RF-FR-02 | **Pembentukan refund** (otomatis): (a) peserta yang sudah membayar memilih / diproses **Refund** setelah reschedule (QTN-FR-36), atau tidak dicentang saat Deal ulang (QTN-FR-42); (b) **Penjualan Program dibatalkan** dan invoice-nya sudah dibayar (PJ-FR-33): satu refund per invoice yang sudah dibayar `[MMB-2]` `[USULAN]`. Isi: nomor `LMI/RF/MM/yy-zzz` `[USULAN]`, tanggal, SO (boleh SO Batal), quotation, peserta (B2B / program batal dengan invoice gabungan: company), invoice, **sebab** (Tidak ikut setelah reschedule / Penjualan Program dibatalkan), alasan, **nominal tagihan** (tagihan peserta; TOP: nilai peserta × persentase termin yang sudah dibayar, QTN-FR-44; program batal: total invoice). Status awal **Menunggu Approval**. | `[MMB-2]`, `[MMB-6]`, `[JRR-2]` + `[USULAN]` |
| RF-FR-03 | **Daftar Refund**: kolom No, Nomor Refund (+ tanggal; klik = View), Peserta / Company (+ sebab dan alasan), SO / Invoice, Nominal (bila transfer berbeda: nominal transfer + tagihan), Status (+ siapa yang menyetujui dan kapan; Selesai: oleh siapa), Action (gear: **View**; **Proses Refund** hanya bila Disetujui dan punya hak Manage). Filter status dan cari (nomor, peserta, SO, invoice, quotation). Urutan: Disetujui (perlu ditransfer) di atas, lalu Menunggu Approval, Ditolak, Selesai. | `[MMB-6]` + `[USULAN]` (urutan) |
| RF-FR-04 | **Proses Refund** (Finance): tampilan dan isian sama dengan sebelumnya (SO-FR-19): nominal refund sesuai S&K (boleh kurang dari tagihan; keterangan potongan wajib bila kurang), bank, nomor rekening, atas nama, bukti transfer wajib (PDF/JPG/PNG maks. 5 MB). Ringkasan menampilkan siapa yang menyetujui. Hanya untuk refund **Disetujui**; refund lain menolak dengan pesan. Simpan → **Selesai**, tidak dapat diubah lagi. | `[MMB-6]`, SO-FR-19 |
| RF-FR-05 | **Approval Refund** oleh **Finance** `[JMR-4]`: tab **Menunggu Approval** (jumlah) dan **Sudah diputuskan**. Kolom sama dengan Daftar Refund. Gear: View; **Proses (Setujui / Tolak)** bila Menunggu Approval dan punya hak Approve. Popup menampilkan peserta/company, sebab, alasan, invoice, nominal tagihan; keputusan **Setujui** (lanjut ke proses transfer) atau **Tolak** (**alasan wajib**, berisi apa yang harus diperbaiki); catatan opsional bila Setujui. Refund yang ditolak **kembali ke Daftar Refund** dengan status **Ditolak, perlu diperbaiki** (RF-FR-07) `[JMR-5]`. Setiap keputusan dicatat di riwayat (siapa, kapan, catatan) dan tampil di View. | `[MMB-6]`, `[JMR-4]`, `[JMR-5]` |
| RF-FR-06 | **Activity Log** modul "Refund": Tambah (refund terbentuk), Approval Refund (Setujui / Tolak, before/after status), Ajukan Ulang (before/after alasan, nominal, status), Proses Refund (Selesai). | `[STD]` |
| RF-FR-07 | **Perbaiki & ajukan ulang** `[JMR-5]`: refund **Ditolak, perlu diperbaiki** tampil paling atas di Daftar Refund dengan aksi gear **Perbaiki & ajukan ulang** (hak Add). Form menampilkan alasan penolakan (siapa, kapan), ringkasan peserta/sebab/invoice, lalu isian yang dapat diperbaiki: **alasan refund** (wajib) dan **nominal tagihan** (wajib, 1 s.d. nilai invoice) serta **catatan perbaikan** (wajib). **Ajukan ulang** mengembalikan status ke **Menunggu Approval**; tidak ada batas jumlah pengajuan ulang `[USULAN]`. Riwayat approval dan perbaikan tetap tersimpan dan tampil di View. | `[JMR-5]` + `[USULAN]` (isian yang dapat diperbaiki, tanpa batas) |

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
| Menunggu Approval | Tolak (alasan wajib) | **Ditolak, perlu diperbaiki** |
| Ditolak, perlu diperbaiki | Perbaiki & ajukan ulang (Sales/Marketing) | **Menunggu Approval** |
| Disetujui | Proses Refund | **Selesai** (akhir) |

## 7. Pertanyaan Terbuka

| ID | Pertanyaan | Terkait |
|---|---|---|
| Q-RF-01 | ~~Siapa yang menyetujui refund?~~ **Terjawab** `[JMR-4]`: Finance (untuk sementara). | RF-FR-05 |
| Q-RF-02 | ~~Refund ditolak: apa yang terjadi?~~ **Terjawab** `[JMR-5]`: kembali ke menu Refund, Sales/Marketing memperbaiki sesuai alasan lalu mengajukan ulang (RF-FR-07). | RF-FR-05, RF-FR-07 |
| Q-RF-03 | ~~Program batal, peserta sudah bayar?~~ **Terjawab** `[JMR-3]`: Refund. | RF-FR-02 |
| Q-RF-04 | ~~Refund TOP?~~ **Terjawab** `[JMR-7]`: dari termin yang sudah dibayar; termin belum dibayar dibatalkan. | RF-FR-02, QTN-FR-44 |
| Q-RF-05 | Faktur pajak / nota retur untuk refund: ditunda ("faktur belakangan", 13-11-2026). | RF-NFR-02 |

## 8. Riwayat Perubahan

| Versi | Tanggal | Perubahan |
|---|---|---|
| **to be draf 1a** | 13-11-2026 | `input/Master - Buat Program/2026-11-13-jawaban-multi-master-refund-top/` `[JMR]`: yang menyetujui **Finance** (RF-FR-01, RF-FR-05); refund **ditolak kembali ke Sales/Marketing** untuk diperbaiki dan diajukan ulang (RF-FR-07 baru, UC-RF-3 baru, status); Q-RF-01 – Q-RF-04 terjawab, Q-RF-05 (faktur) ditunda. ERD to be: status `ditolak` tidak lagi akhir; tabel `refund_riwayat`; `refund.jumlah_ajuan`. |
| **to be draf 1** | 13-11-2026 | Dokumen baru `[MMB-6]`: Refund pindah dari tab Sales Order ke modul sendiri dengan **Approval Refund**; status Menunggu Approval → Disetujui → Selesai / Ditolak; refund karena Penjualan Program dibatalkan `[MMB-2]`. ERD to be: `refund.status` (menunggu/disetujui/ditolak/selesai), `refund.sebab`, `approval_by`, `approval_date`, `approval_catatan`. |
