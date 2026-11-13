# Jawaban Q-PB-01 – Q-PB-12 (Pengajuan Biaya draf 1) + permintaan Matrix Akses Menu

- **Source:** user / BA proyek Labmania, disampaikan lewat sesi Claude Code (chat)
- **Date:** 2026-11-04
- **Channel:** chat (jawaban atas daftar pertanyaan `docs/fr-nfr-pengajuan-biaya.md` draf 1 bagian 8)
- **Contents:** pernyataan verbatim, tanpa lampiran
- **Supersedes:** usulan draf 1 berikut: field PIC Program di Penjualan Program (PJ-FR-26 draf 12c, tidak jadi dipakai); aktor Keuangan & menu Pencairan dalam cakupan sekarang; dua tombol Add Kasbon / Add Direct Payment; overbudget hanya untuk item costing.

## Verbatim

> PB-01 = PIC itu dari sales & marketing yang sudah di select saat buat quotation, dari trainer yang sudah di select saat penjualan program juga, dan yang positionnya manajemen. untuk setting maka harus ada dibuat matrix akses menu dong ya? kalo iya buatkan.
> PB-02 = Yang mencairkan finance, tapi saat ini buatkan dulu aja modul pengajuan biaya nya.
> PB-03 = Kalo di video kurang lebih ada 5, nah disini ada 2 aja yaitu dari fixed cost dan variable cost, approval 1 level dulu aja ya, sementara dulu ini
> PB-04 = Budget dihitung dari yang penjualan program final, karena walaupun pesertanya tidak sesuai target maka yang berubah hanya di profit labmania aja, budget biaya sudah konsekuensi dari awal saat buat penjualan program. boleh diajukan berbeda dari harga budget asalkan budget mencukupi
> PB-05 = Pengajuan PO gausah ada dulu, disini buat aja jalur pengajuan hanya 1 "Add Pengajuan" di dalam form pengajuannya baru select jenis jalurnya, buat 2 dulu aja Kasbon atau Direct Payment
> PB-06 = Pelaporan realisasi nanti dulu, cakupan ini hanya pengajuan dan approval dulu ya
> PB-07 = User terkait aja yang bisa buka, aku admin sehingga bisa buka semua
> PB-08 = Buatin dulu aja formatnya sementara
> PB-09 = Jurnal nanti dulu ya
> PB-10 = Boleh tambahin overbudget ya
> PB-11 = Saya kurang tau soalnya IT developer ga ada disini, yang saat ini sudah ada hubungan ke modul lainnya apa ya? soalnya ini program baru kenapa udah ada database kasbon?
> PB-12 = Selama program belum closed maka masih bisa ajukan. ini juga belum ada fiturnya, nyusul aja

## Catatan penafsiran

1. **Tim program** (yang boleh mengajukan dan membuka program): (a) **Sales** yang dipilih di Quotation program itu (`quotation.id_sales`; Public bisa banyak quotation, jadi bisa banyak sales); (b) **Trainer** yang dipilih di Penjualan Program; (c) user yang **posisinya Manajemen** — ditafsirkan sebagai **Group Akses Manajemen** (usulan). Trainer adalah data Master Trainer (`ms_trainer`), bukan akun login, jadi perlu kaitan trainer → akun user (usulan `ms_trainer.id_user`, pertanyaan baru).
2. **Matrix Akses Menu**: user minta dibuatkan. Aplikasi sudah punya tabel hak akses (`groups`, `user_groups`, `permissions` dengan aksi View/Add/Manage/Delete per menu, `group_permissions`, `user_permissions`, `menus`) dan halaman hak akses **per user**; halaman **per group** belum ada. Group id 1 = Admin, lolos semua permission (`Auth::is_admin()`).
3. Cakupan sekarang: **pengajuan + approval** saja. Pencairan (Finance), realisasi/pertanggungjawaban, jurnal → menyusul.
4. Kategori Fixed / Variable Cost dan approval 1 level (Manajemen) **sementara**.
5. Budget dari Penjualan Program Final dikonfirmasi; harga per unit boleh berbeda asal budget cukup.
6. Satu tombol **Add Pengajuan**; jenis (Kasbon / Direct Payment) dipilih di form.
7. Overbudget boleh menambah **item baru** di luar costing.
8. PB-11: dijawab analis di spec draf 2 (bagian 8) — tabel kasbon yang ada milik modul konsultan bawaan basis kode.
9. Batas pengajuan = program belum **Closed**; status Closed belum ada → menyusul.

## Extracted

- `docs/fr-nfr-pengajuan-biaya.md` draf 2
- `docs/fr-nfr.md` draf 11r: Matrix Akses Menu (FR-54 – FR-58, UC-49, NFR-13, Q-33, Q-34)
- `docs/fr-nfr-penjualan-program.md`: PJ-FR-26 (draf 12c) dibatalkan — file kembali ke draf 12b
- ERD halaman *ERD Pengajuan Biaya* draf 2 + halaman baru *ERD Matrix Akses*
- BPMN *Level 1 — Pengajuan Biaya* draf 2
- `docs/workbook.xlsx`, mockup draf 14a
