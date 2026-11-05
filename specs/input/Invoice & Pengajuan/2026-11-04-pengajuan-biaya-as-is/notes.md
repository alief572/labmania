# Modul Pengajuan Biaya — acuan menu Pengajuan sistem lain (As Is)

- **Source:** user / BA proyek Labmania, disampaikan lewat sesi Claude Code (chat)
- **Date:** 2026-11-04
- **Channel:** chat + video rekaman layar
- **Contents:** pernyataan verbatim dan video `input/Invoice & Pengajuan/Menu Pengajuan Biaya - As Is.mp4` (39 detik, 1918×930, direkam 04-11-2026 07:58)
- **Supersedes:** —

## Verbatim

> nah sekarang aku mau tambahin modul pengajuan biaya, dimana disini PIC penanggungjawab program bisa mengajukan biaya program ke manajemen untuk dicairkan biayanya. nah kamu lihat file video dengan nama file "Menu Pengajuan Biaya - As Is", di sistem perusahaan lain menu pengajuan biaya nya seperti itu, aku mau kurang lebih seperti itu tapi disesuaikan aja ya dengan yang labmania ini. coba buatin, sekalian update juga ke FR & NFR, ERD Diagram, Use Case tabel, Flow Process, dan Mock Up nya. tolong ya

## Isi video (dibaca dari frame, bukan transkrip suara)

Aplikasi "CONSULTANT", menu **Project ▸ Pengajuan** (menu saudara: Expense Report Project, Monitoring Expense Report, Cashflow Project, Consultation Report).

1. **Daftar Pengajuan** (`[VID-1]`): satu baris per SPK. Kolom: No, **Nomor SPK & Paket** (nomor SPK + judul paket), **Customer**, **Team / PIC** (PL = Project Leader, Sales), **Status** (badge "Draft" di semua baris contoh), **Action** (ikon lihat, ikon edit). Pencarian, 10 entri per halaman.
2. **Halaman pengajuan per SPK** (`[VID-2]`): kepala No. SPK, Customer, Address, Project, Project Leader, Sales, Waktu (tanggal mulai – selesai). Lalu satu blok per **kategori biaya** (contoh: **Akomodasi**, **Others**), masing-masing:
   - kartu **Budget**, **Aktual**, **Sisa Budget** (contoh Akomodasi: Rp 212.500.000 / Rp 17.000.000 / Rp 195.500.000; Others: Rp 4.650.000 / Rp 4.650.000 / Rp 0);
   - tombol **+ Add Pengajuan Kasbon**, **+ Add Pengajuan Direct Payment**, **+ Add Pengajuan PO**;
   - tabel pengajuan: No, **Req Number** (contoh `KS-2026-00346`), Description, Total, **Tipe** (badge Kasbon), **Request By**, **Status** (badge Approved), Date, Option (gear);
   - sub-blok **Overbudget [kategori]** dengan tombol **+ Add Overbudget** dan tabel: No, ID Request, Amount, Status, Pembuat Pengajuan, Option.
3. **Form Add Pengajuan** (judul "Pengajuan Akomodasi", URL `kasbon_project/add_kasbon_akomodasi/...`) (`[VID-3]`):
   - kepala SPK (sama dengan halaman 2) + **Request By** (user login; peringatan kuning "Admin bukan bagian dari tim SPK ini"), **Tgl** (hari ini, terkunci), **Deskripsi / Keterangan** wajib;
   - **Informasi Pengajuan**: per item biaya — **Estimasi** (Qty, Price/Unit, Total Budget), **Terpakai** (Qty, Price/Unit, Total Terpakai), **Overbudget** (Qty, Budget, Total), baris Total;
   - **Pengajuan**: per item — Qty (isian), Price/Unit (terisi harga budget), Total Pengajuan; baris yang budget-nya sudah habis terpakai tampil abu-abu (tidak dapat diisi); Grand Total;
   - **Bank**, **Bank Number**, **Account Name** (wajib);
   - kotak **Bukti Penggunaan** (Upload Bukti, tarik & letakkan file);
   - tombol **Back**, **Save**.

## Catatan penafsiran

1. Pengaju = **PIC penanggung jawab program**; penyetuju = **Manajemen**; tujuan = biaya **dicairkan**. Siapa yang mencairkan (Manajemen atau Keuangan) tidak disebut.
2. "Disesuaikan dengan Labmania": SPK di acuan dipetakan ke **Penjualan Program Final**; Budget = costing Penjualan Program (Fixed / Variable Cost). Pemetaan lain adalah usulan analis.
3. Labmania belum punya PIC di Penjualan Program, modul pembelian (PO), maupun pertanggungjawaban kasbon (Expense Report). Semua itu usulan / pertanyaan terbuka, bukan pernyataan klien.

## Extracted

- Spec baru `docs/fr-nfr-pengajuan-biaya.md` draf 1 (PB-FR-01 – PB-FR-20, UC-43 – UC-48, Q-PB-01 – Q-PB-12)
- PJ-FR-26 (PIC Program) -> `docs/fr-nfr-penjualan-program.md` draf 12c
- ERD halaman *ERD Pengajuan Biaya* -> `docs/diagrams/erd.drawio`
- BPMN *Level 1 — Pengajuan Biaya* + Level 0 -> `docs/diagrams/bpmn-to-be.drawio`
- UC-43 – UC-48, modul & entity -> `docs/workbook.xlsx`
- Mockup modul Pengajuan Biaya -> `ui-mockup/produk/app.js` (draf 14)
