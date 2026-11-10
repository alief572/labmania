# Sub menu Master Data: Product, User, Peserta & Trainer

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-07
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes (khusus to be):**
  - `input/Master - Buat Program/2026-10-28-sidebar-tanpa-label/` `[SBL-1]` untuk modul Master Data: label sub modul kini tampil sebagai sub menu yang dapat dibuka/ditutup.
  - Pengelompokan sub modul Master Data (Atribut Produk, Produk, Customer, Biaya, Trainer, Pengguna) di `docs/fr-nfr.md` bagian 1 (Q-18).

## Verbatim

> nah aku juga mau minta di modul "Master Data" dibuatkan sub kali ya biar kalo di klik modul "Master Data" ga numpuk, sub nya dibagi ada "Product" isinya, jenis produk, jenis  sertifikat, jenis bidang, varian pelaksanaan, product combined, katalog produk, dan master rate. lalu ada "User" isinya Master User, Master Role, dan  Role & Permission. lalu ada "Peserta & Trainer" isinya Master Peserta, Master Company, dan Master Trainer.

## Catatan penafsiran

1. Modul **Master Data** di sidebar punya **sub menu yang dapat dibuka/ditutup**:
   - **Product**: Jenis Produk, Jenis Sertifikat, Jenis Bidang, Varian Pelaksanaan, Product Combine, Katalog Produk, Master Rate.
   - **User**: Master User, Master Role, Role & Permission.
   - **Peserta & Trainer**: Master Peserta, Master Company, Master Trainer.
2. **Template Keterangan Quotation** dan **Template Formulir Konfirmasi** tidak disebut. Untuk sementara tetap di sub **Dokumen** tersendiri `[USULAN]` (Q-39).
3. Perilaku usulan analis: sub yang berisi menu aktif terbuka otomatis; saat mencari menu, semua sub terbuka; breadcrumb menjadi Modul / Sub / Menu `[USULAN]`.
4. Modul lain tetap tanpa label sub (aturan `[SBL-1]` tetap berlaku untuk modul selain Master Data) `[USULAN]`.
5. Dibuat di **to be** (`ui-mockup/produk-to-be/`, `docs-to-be/`).

## Extracted

- `docs-to-be/fr-nfr.md` bagian 1 (tabel sub modul), FR-27, FR-36, FR-54, FR-64, Q-18, Q-39
- mockup `ui-mockup/produk-to-be/core.js` (MODULES, renderNav), `main.js`, `style.css`
