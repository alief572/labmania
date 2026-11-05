# Pengajuan Biaya — 2 tab (Ajukan / Sudah Diajukan), nomor per jenis, progres sampai Paid

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-05
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa file lampiran
- **Supersedes:** status akhir "Disetujui" pengajuan Kasbon / Direct Payment (`docs/fr-nfr-pengajuan-biaya.md` draf 3c bagian 6) — kini dilanjutkan ke pembayaran (Paid)

## Verbatim

> di modul pengajuan dipisah jadi 2 tab ya, tab untuk mengajukan, dan tab list yang sudah diajukan, apabila kasbon maka penomorannya kasbon, apabila direct payment maka penomorannya direct payment. pada tab yang sudah diajukan ada progress si pengajuan, waiting approval, waiting payment, dan paid. coba buatin dulu

## Catatan penafsiran

1. Menu **Pengajuan Biaya** dibagi 2 tab:
   - **Ajukan Biaya** — daftar program (isi lama PB-FR-02) untuk masuk ke halaman program dan menekan Add Pengajuan.
   - **Sudah Diajukan** — daftar semua pengajuan Kasbon / Direct Payment pada program yang dapat dilihat user (aturan hak data PB-FR-20), lengkap dengan progres.
2. **Penomoran per jenis sudah berlaku** sejak draf 2 (PB-FR-11, Q-PB-08): Kasbon `LMI/KS/MM/yy-zzz`, Direct Payment `LMI/DP/MM/yy-zzz`, urutan masing-masing per bulan. Tidak ada perubahan; ditegaskan ulang.
3. Progres pengajuan (istilah klien dipakai apa adanya): **Waiting Approval → Waiting Payment → Paid**.
   - Waiting Approval = status lama *Menunggu Approval*; Waiting Payment = status lama *Disetujui*; **Paid** = status baru.
   - Ditolak dan Batal tetap ada; progres berhenti di langkah approval.
   - Overbudget tidak dibayar, jadi tetap memakai status lama (Menunggu Approval / Disetujui / Ditolak / Batal) dan tidak tampil di tab Sudah Diajukan `[USULAN]`.
4. Belum disebut klien, diisi usulan analis:
   - Paid ditandai lewat aksi **Tandai Paid** pada pengajuan Waiting Payment, oleh user dengan hak **Manage** di menu Pengajuan Biaya (Superadmin / Admin selalu dapat), sementara modul pencairan Finance belum dibuat (PB-FR-16) `[USULAN]`.
   - Isian Tandai Paid: **tanggal bayar** (wajib, tidak sebelum tanggal pengajuan, tidak di masa depan) dan **keterangan / bukti transfer** (opsional) `[USULAN]`.
   - Pengajuan Paid tetap dihitung sebagai budget terpakai (Aktual) `[USULAN]`.
   - Tab Sudah Diajukan: filter jenis (Kasbon / Direct Payment) dan progres, pencarian; urut tanggal terbaru `[USULAN]`.
5. Pertanyaan baru **Q-PB-17**: siapa yang menandai Paid (Finance / Admin)? Bukti transfer wajib? Tetap lewat modul pencairan nanti?

## Extracted

- PB-FR-02, PB-FR-04, PB-FR-16, PB-FR-21 – PB-FR-23, bagian 1, 6, 7, Q-PB-17 -> docs/fr-nfr-pengajuan-biaya.md (draf 4)
- ERD halaman *ERD Pengajuan Biaya* draf 4: `pengajuan_biaya.status` + `'paid'`, kolom `paid_by`, `paid_date`, `paid_keterangan` -> docs/diagrams/erd.drawio
- mockup -> ui-mockup/produk/app.js + style.css (tab, progres, Tandai Paid), draf 14g
