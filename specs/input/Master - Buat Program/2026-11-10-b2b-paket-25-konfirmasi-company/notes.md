# B2B: all-in paket s.d. 25 peserta, tambahan Rp300.000/orang, konfirmasi atas nama company (to be)

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-10
- **Channel:** chat, ditambah jawaban pilihan di sesi
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes (khusus to be):** skema harga B2B "per peserta atau all-in" (`docs-to-be/fr-nfr-quotation.md` QTN-FR-20, `[B2B-1]`); B2B memakai link dan Confirmation Letter per peserta seperti Public (`input/Master - Buat Program/2026-11-09-b2b-tetap-confirmation-letter/`, QTN-FR-25); untuk B2B, surat konfirmasi per peserta dari `input/Master - Buat Program/2026-11-10-surat-konfirmasi-ttd-basah/` diganti satu surat atas nama company.

## Verbatim

> untuk penjualan B2B ada beberapa catatan baru:
> 1. kuota maksimal 25 peserta.
> 2. Kalau kurang dari 25, biaya tidak berubah.
> 3. Kalau lebih dari 25, ada tambahan Rp300.000/orang.
> 4. konfirmasi B2B dibuat atas nama company (quotation tidak ada opsi pilih peserta lagi, langsung ke company)
> 5. Company cukup mengirim daftar peserta (biasanya nama saja), jadi informasinya tidak selengkap form konfirmasi public. Artinya template surat konfirmasi B2B berbeda dari public. (coba menurut kamu gimana itu)

Jawaban atas pertanyaan analis di sesi:

- Harga B2B, dari pilihan "Paket s.d. 25 / Tetap dua skema / 25 batas keras", user memilih "25 batas keras" dengan catatan: "memang all in aja jadinya, tapi dengan aturan baru dimana maks peserta 25. kalo melebihi maka akan ada tambahan biaya 300k/peserta. terkait minimal 10 peserta saat ini dibuat seperti itu dulu saja"
- Minimal 10 peserta: "Tetap minimal 10"
- Konfirmasi B2B: menyetujui usulan "Link diisi PIC" (link B2B diisi satu kali oleh PIC company: data company dan PIC, daftar peserta dengan nama wajib dan kolom lain opsional; satu surat konfirmasi atas nama company dengan lampiran daftar peserta, tanda tangan + cap, unggah wajib).

## Catatan penafsiran

1. **B2B hanya skema all-in.** Pilihan "per peserta" dihapus untuk B2B. Harga all-in = harga **paket untuk s.d. 25 peserta**; peserta kurang dari 25 harga tetap. Peserta ke-26 dan seterusnya **+ Rp300.000/orang** (catatan user mengoreksi pilihan "batas keras": lebih dari 25 tetap boleh, dengan tambahan biaya).
2. Tambahan dihitung dari jumlah peserta yang sama dengan dasar harga quotation: Kuota peserta sebelum Deal, peserta Deal setelah Deal (mengikuti usulan Q-TB-01). Tambahan masuk Sub Total dan DPP (kena PPN); diskon hanya dari harga paket `[USULAN]` (Q-QTN-21).
3. Batas 25 dan tarif Rp300.000 disimpan per quotation saat dibuat, agar perubahan aturan tidak mengubah quotation lama `[USULAN]` (Q-QTN-21).
4. Minimal 10 peserta B2B tetap (saat simpan quotation, di link, dan saat Deal).
5. **Konfirmasi B2B atas nama company.** Link B2B diisi **satu kali oleh PIC company**: data perusahaan dan PIC (terisi awal dari Master Company), **daftar peserta** (Nama Lengkap wajib; Jabatan, Email, Nomor WhatsApp, NIK opsional, dipilih Sales per quotation), pernyataan, lalu **surat konfirmasi company** dicetak (surat + lampiran daftar peserta), ditandatangani dan dicap, **diunggah wajib**. Setelah dikirim, link B2B tertutup; perubahan daftar lewat Sales `[USULAN]` (Q-QTN-22).
6. Template surat konfirmasi B2B baru, beda dari Public (formulir per orang). Format usulan analis; kode dokumen Labmania belum ada (Q-QTN-22).
7. Invoice B2B menjadi satu invoice ke company (all-in). Spec dan contoh data paket **Invoice - Pengajuan** tidak diubah di sini (paket terpisah); dicatat sebagai dampak.

## Extracted

- Bagian 1, UC-33, UC-56, QTN-FR-14, QTN-FR-18, QTN-FR-20, QTN-FR-21, QTN-FR-24, QTN-FR-25, QTN-FR-26, QTN-FR-28, QTN-FR-31 (baru), QTN-FR-32 (baru), QTN-NFR-05, Q-QTN-15, Q-QTN-21, Q-QTN-22 -> `docs-to-be/fr-nfr-quotation.md`
- SO-FR-05 -> `docs-to-be/fr-nfr-sales-order.md`
- ERD to be: `quotation.paket_maks_peserta`, `quotation.tambahan_per_peserta`, `quotation.kolom_peserta_b2b`, tabel `quotation_konfirmasi_company`, `quotation_peserta.sumber` + `company` -> `docs-to-be/diagrams/erd.drawio`
- `docs-to-be/README.md`
- Mockup -> `ui-mockup/produk-to-be/mod-quotation.js`, `konfirmasi.html`, `style.css`
