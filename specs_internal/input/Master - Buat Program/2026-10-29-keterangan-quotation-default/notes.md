# Keterangan quotation: teks default yang dapat diedit (master + salin ke quotation)

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-10-29
- **Channel:** chat (permintaan, pertanyaan balik analis, persetujuan)
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes:** usulan `docs/fr-nfr-quotation.md` draf 3 — field Catatan (maks. 3) dan teks tetap "Biaya sudah termasuk…" serta dua kalimat persetujuan di blok Keterangan Penawaran (`input/Master - Buat Program/2026-10-29-cetak-quotation-snk-konfirmasi/`).

## Verbatim

> ada request terkait cetak quotation:
> Bagian Keterangan/Catatan dengan teks default yang editable (syarat keikutsertaan, termasuk/tidak termasuk, dokumen persyaratan, klausul tanda tangan).
>
> tapi aku bingung gmn caranya ya itu? apakah dibuatkan master dan user bisa select atau bagaimana?

Analis mengusulkan: master "Template Keterangan Quotation" per kategori (butir bertanda Default, Aktif, opsional Berlaku untuk Public/B2B/Semua); saat quotation dibuat, butir default tersalin ke quotation dan dapat diedit/dihapus/ditambah khusus quotation itu (snapshot, master tidak berubah); PDF Penawaran mengambil blok Keterangan dari sini. Tiga hal ditanyakan: daftar kategori, beda Public vs B2B, isi default awal.

> nah coba buatkan ya

## Catatan penafsiran

- Persetujuan atas pendekatan yang diusulkan. Tiga pertanyaan tidak dijawab → dibuat dengan usulan analis `[USULAN]`:
  - kategori: Syarat keikutsertaan, Termasuk, Tidak termasuk, Dokumen persyaratan, Klausul tanda tangan (sesuai kalimat permintaan);
  - kolom **Berlaku untuk** (Semua / Public / B2B) disediakan, default Semua;
  - isi awal: dari Template Penawaran ("Materi", "Sertifikat pelatihan", "Pajak", dua kalimat persetujuan) + S&K (formulir konfirmasi) + beberapa butir **contoh** yang ditandai.

## Extracted

- FR-48 – FR-50, UC-39, bagian 4.13, Q-28 – Q-30 -> docs/fr-nfr.md (draf 11m)
- QTN-FR-04, QTN-FR-14, QTN-FR-17 (baru) -> docs/fr-nfr-quotation.md (draf 4)
- ERD `ms_template_keterangan` -> docs/diagrams/erd.drawio
- mockup -> ui-mockup/produk/app.js (draf 11s)
