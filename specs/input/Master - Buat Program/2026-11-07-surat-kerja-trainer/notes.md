# SO = surat konfirmasi ke customer; Surat Kerja Trainer di modul SO (tab); trainer wajib di Penjualan Program

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-07
- **Channel:** chat
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes (khusus to be):** PJ-FR-23 `docs/fr-nfr-penjualan-program.md` (trainer opsional `[USULAN]`, Q-PJ-20): trainer kini **wajib**.

## Verbatim

> nah iya tujuannya surat konfirmasi dari labmania ke customer sih.
>
> selain itu aku mau request ada surat kerja untuk trainer juga, jadi trainer ini wajib di select saat buat penjualan program. letak surat kerja trainer kayaknya bisa nih di modul SO juga, bedain tab aja?

Konteks: menjawab penjelasan di sesi bahwa Sales Order biasanya dokumen konfirmasi pesanan dan dasar penagihan.

## Catatan penafsiran

1. **Tujuan Sales Order = surat konfirmasi pesanan dari LabMania ke customer.** PDF SO dikirim ke customer; kolom "Disetujui oleh Pelanggan" tetap ada.
2. **Surat Kerja Trainer** baru, diletakkan di **modul Sales Order sebagai tab tersendiri** (tab "Sales Order" | "Surat Kerja Trainer").
3. **Trainer wajib dipilih** saat membuat Penjualan Program (sebelumnya opsional). Menjawab Q-PJ-20 bagian "wajib atau opsional".
4. Usulan analis (belum dinyatakan klien):
   - Satu surat per Penjualan Program (1 event = 1 trainer, `[K3M-1]`).
   - Surat terbentuk otomatis saat program mendapat Sales Order pertama, yaitu saat program pasti jalan (Q-SKT-02).
   - Nomor dan format menyusul, mengikuti pola jawaban SO (`input/Master - Buat Program/2026-11-07-jawaban-sales-order/`); mockup memakai nomor sementara `SKT-nnn` (Q-SKT-01).
   - Isi: trainer (dari Master Trainer), program, Varian, sertifikat, jadwal, waktu, lokasi, company (B2B), jumlah peserta Deal; tanda tangan LabMania dan "Diterima oleh Trainer". Belum memuat honor (Q-SKT-03).
   - Aksi: View, Print Out (PDF); tanpa aksi batal, seperti SO.

## Extracted

- `docs-to-be/fr-nfr-sales-order.md` (tujuan SO, SO-FR-11 – SO-FR-14, UC-64, Q-SKT-01 – Q-SKT-03), `docs-to-be/fr-nfr-penjualan-program.md` (PJ-FR-23, Q-PJ-20), `docs-to-be/README.md`
- ERD `surat_kerja_trainer`, `penjualan_program.id_trainer` wajib -> `docs-to-be/diagrams/erd.drawio`; BPMN -> `docs-to-be/diagrams/bpmn-to-be.drawio`
- mockup -> `ui-mockup/produk-to-be/mod-sales-order.js`, `mod-master-data.js` (trainerField), `mod-program.js` (validasi), `main.js`, `core.js`, `mod-activity-log.js`
