# Menu Product, Master Program lebih dari satu Product, Reschedule tanggal + trainer dengan evidence (to be)

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-09
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes:**
  - Diskusi terbuka `input/Master - Buat Program/2026-11-08-diskusi-multi-product-combine/` dan `.../2026-11-08-diskusi-multi-product-combine-lanjutan/`: diputuskan **Master Program memilih lebih dari satu Product, costing tetap satu** (catatan lanjutan menyebut opsi ini gugur; keputusan pengguna di sini yang berlaku).
  - `docs-to-be/fr-nfr-penjualan-program.md` PJ-FR-26 (isian reschedule waktu dan lokasi; tanpa evidence).

## Verbatim

> nih ada yang mau aku ubah ya:
> 1. menu "Product Combined" ganti jadi = Product
> 2. lalu pada master program, dia kan select product combined, nah bisa select lebih dari 1 ya, karena saat ini kan hanya 1 aja. untuk costing tetap jadi 1 aja ya
> 3. nah ada reschedule juga untuk programnya, ini hanya mengganti tanggal dan bisa select ulang trainer nya. lalu saat mau reschedule perlu upload evidence, jpg/png/docs/pdf ya
>
> coba ubah dulu ya

## Catatan penafsiran

1. Menu Master Data **Product Combine** diganti nama **Product** (judul halaman, label Nomor Product, nama modul di Activity Log). Isi dan tabel (`paket_produk`) tidak berubah.
2. Master Program memilih **satu atau lebih Product** (minimal satu, tidak boleh dobel). **Costing tetap satu** per Master Program, bukan per Product. Judul program = judul semua Product digabung "+". Atribut turunan yang hanya bisa satu (mis. Varian di quotation, template formulir default) diambil dari Product **pertama** `[USULAN]`.
3. "Reschedule untuk programnya" dibaca sebagai Reschedule **Penjualan Program** yang sudah ada (PJ-FR-26). Isiannya sekarang **hanya tanggal** (tanggal mulai; selesai dihitung dari durasi) dan **trainer** (dapat dipilih ulang). Waktu dan lokasi tidak lagi diubah lewat reschedule. Alasan tetap wajib (aturan 08-11-2026).
4. **Evidence wajib** saat reschedule: "docs" dibaca sebagai DOC/DOCX. Tipe JPG, PNG, DOC/DOCX, PDF; boleh lebih dari satu file; batas 5 MB per file `[USULAN]` (Q-PJ-26).

## Extracted

- Menu Product -> `docs-to-be/fr-nfr.md`
- MP-FR-01 – MP-FR-03 -> `docs-to-be/fr-nfr-master-program.md` (dokumen to be baru)
- UC-65, PJ-FR-26, PJ-FR-28, PJ-NFR-10, Q-PJ-26 -> `docs-to-be/fr-nfr-penjualan-program.md`
- ERD `master_program_produk`, `penjualan_program_reschedule_evidence` -> `docs-to-be/diagrams/erd.drawio`
- Mockup -> `ui-mockup/produk-to-be/core.js`, `main.js`, `mod-master-data.js`, `mod-program.js`, `mod-activity-log.js`, `style.css`
