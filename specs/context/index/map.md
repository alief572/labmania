> Struktur aktif sejak 16-11-2026: Laravel di root repository; CI3 arsip di `ci3/`. Status fase di bawah adalah catatan historis. Lihat keputusan 2.

# Map — what owns what

Traceability index: a use case or feature, and the artifacts — and later, the
code — that realise it. Agents load this to navigate without re-reading the
whole repository; humans use it for tickets and onboarding. Update it when
ownership changes, not merely because a file exists.

> **Not machine-parsed, by design**: prose pointers, `.md` not `.yaml` on purpose. Do not
> convert it to YAML or build tooling that parses it (why: `context/guide/building-the-layers.md`).

**This file is the prose layer** — ownership, use cases, and the "why" that no
scanner can produce. It does not hold the inventory. `context/index/README.md`
explains the three index files and what each one answers.

---

## Use cases → artifacts

### Fondasi Laravel fase 1

Autentikasi, TOTP, permission/override, unduh privat dan Activity Log baru dimiliki aplikasi Laravel di root repository. Operasi dan pemeriksaan aman dijelaskan di `README.md` pada root repository; kontrak nama persistence di `../docs-to-be/fondasi-laravel.md`. Implementasi CI3 tetap pemilik alur bisnis aktif sampai cutover. Keputusan pemisahan aplikasi ada di [keputusan 1](decisions/1-laravel-terpisah.md).

### Master Data dan Master Program fase 2

Master atribut, Product, Katalog, Rate, Template, Peserta, Company, Trainer, User, Role, dan Master Program kini dimiliki aplikasi Laravel di root repository (controller `app/Http/Controllers`, halaman `resources/js/pages/Master`). Spesifikasi tetap `../docs-to-be/fr-nfr.md` dan `../docs-to-be/fr-nfr-master-program.md`; skema fisik direkonsiliasi di ERD halaman Master Data, Master Program, dan Master Trainer. CI3 tetap pemilik alur aktif sampai cutover. Bukti dan butir USULAN BA ada di `../.artifacts/fase2_master_data_program/walkthrough_fase2_master_data_program_13-11-2026.md` di root repository.

### Penjualan Program dan Quotation fase 3

Implementasi Laravel dimiliki `SalesController`, `QuotationController`, `ConfirmationController` dan support `Sales`, `Quotation`, `QuotationFiles`, `QuotationPdf`. Halaman berada di `resources/js/pages/Sales/`, `Quotation/`, dan `Confirmation.vue` pada root repository. Acuan bisnis: `docs-to-be/fr-nfr-penjualan-program.md`, `fr-nfr-quotation.md` dan kontrak pembentukan SO/Surat Tugas pertama di `fr-nfr-sales-order.md`. ERD Penjualan, Quotation, Activity Log memakai nama fisik salinan MySQL; audit publik mengikuti `docs-to-be/fondasi-laravel.md`. Lingkup berhenti setelah Deal membentuk Master Peserta, satu SO per quotation dan Surat Tugas pertama per program. CI3 tetap pemilik alur aktif sampai cutover. Operasi dan pemeriksaan dimiliki README Laravel.

### Sales Order dan Invoice fase 4

Implementasi Laravel dimiliki `SalesOrderController`, `InvoiceController` dan support `SalesOrder`, `Invoice`; dokumen privat memakai `PrivateDocumentController` dengan konteks SO. Halaman berada di `resources/js/pages/SalesOrder/` dan `Invoice/`, template cetak di `resources/views/pdf/`. Acuan: SO-FR-01–15 pada `docs-to-be/fr-nfr-sales-order.md` dan Invoice pada `docs/fr-nfr-invoice.md`, dengan B2B all-in terbaru di `docs-to-be/README.md`. Empat tabel baru berada di ERD Invoice; COA mereferensikan PK signed `coa_master.id` secara logis karena engine MyISAM, sedangkan FK Invoice–SO dan child–Invoice fisik. Operasi/configuration dimiliki README Laravel. Reschedule/refund, jurnal kas dan cutover belum termasuk fase 4.

One entry per use case or feature worth tracing, naming where its behaviour is
specified and what implements it. Only the lines that are true get written — a
UC with no diagram simply has no `bpmn:` line.

```
uc: UC-01
  name: {the workbook's own wording, in the client's language}
  bpmn: docs/diagrams/{file}.drawio → page "{Level 2 — ...}"
  entities: [{Entity}, {Entity}]
  state: docs/diagrams/{file}.drawio, when this UC drives a lifecycle
  code: ~
  note: {what a reader would otherwise have to reconstruct — a rule not visible
        in the diagram, an exception, the decision that shaped it}
```

_Empty. Add entries as use cases are confirmed._

## Laravel fase 5 — Reschedule, konfirmasi ulang dan Refund

Implementasi dimiliki `RescheduleController`, `ReconfirmationController`, `RefundController` serta support `Reschedule`, `Reconfirmation`, `Refund` di root repository. `InvoiceController`/`Invoice` menjaga tagih ulang dan pembatalan, `Quotation`/`SalesOrder` menyediakan status peserta dan riwayat revisi. Acuan bisnis: PJ-FR-26–28/31, QTN-FR-34–37, SO-FR-16–20; keputusan batas waktu/nominal/grant fase 5 dan prosedur operasi dimiliki README Laravel. Lima tabel baru dan metadata Keluar/Batal berada di ERD Penjualan Program, Quotation, Revisi Quotation dan Invoice. CI3 tetap pemilik alur aktif sebelum cutover; fase 6 operasional/Close dan fase 7 rehearsal/cutover terpisah.

## Laravel fase 6 — Operasional peserta dan biaya

Implementasi dimiliki `ParticipantController`, `ExpenseController`, `SurveyController` dan support `Participants`, `ParticipantPdf`, `Expenses`, `Survey`, `Xlsx` di root repository. Acuan peserta/sertifikat/monitoring/survey: `docs-to-be/fr-nfr-manage-peserta.md`; biaya: `docs/fr-nfr-pengajuan-biaya.md`, dengan keputusan fase 6 yang disetujui pengguna. Sepuluh tabel baru dimiliki ERD to-be halaman Quotation, Pengajuan Biaya dan Survey; cakupan Admin/Direktur dimiliki Matrix Akses. Prosedur operasi, gate sertifikat/survey, sumber konfirmasi audit, dan persyaratan font dimiliki README Laravel fase 6. CI3 tetap aplikasi aktif sebelum cutover; rehearsal dan cutover fase 7 belum dijalankan.

## Laravel fase 7 — rehearsal migrasi dan kesiapan cutover lokal

Perintah CLI `labmania:migrasi-profil`, `labmania:migrasi-impor`,
`labmania:migrasi-verifikasi` dimiliki `MigrationManifest`/`MigrationRehearsal`;
operasi sumber/kesiapan/backup/restore dimiliki `MigrationOperation`,
`MigrationOperations` dan `MigrationArchive`. Runbook dan lingkungan terisolasi
dimiliki `rehearsal/README.md`. Schema-only bootstrap berasal dari 62 tabel
Laravel fase 1–6, tanpa akun/grant/data bisnis. Tidak mengubah kontrak ERD atau
alur bisnis; CI3 tetap aktif. Kecocokan fixture tidak menggantikan snapshot nyata,
pemetaan/konfigurasi sah, dua rehearsal, restore, UAT dan keputusan go-live.

## Diagrams → what they own

Which diagram is authoritative for which part of the model, so that two diagrams
never quietly disagree about the same thing.

```
file: docs/diagrams/{file}.drawio
  pages: [{page name}, {page name}]
  owns: {the process / entities / lifecycle this is the source of truth for}
  derived_from: input/{YYYY-MM-DD-slug}/ or docs/workbook.xlsx
  note: {drift risk, known gaps, who confirmed it and when}
```

_Empty._

## Entities → where the rules live

The workbook's Entities sheet is the deduped list. This section is only for
entities carrying a rule that sheet has no column for — a lifecycle, an
invariant, a naming exception.

_Empty._
