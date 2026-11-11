# Reschedule setelah Deal: Sales Order tetap, nomor SO direvisi (to be)

- **Source:** Labmania (jawaban ke BA proyek), lalu jawaban BA proyek atas pertanyaan analis, lewat sesi Claude Code
- **Date:** 2026-11-11
- **Channel:** chat (jawaban Labmania diteruskan BA), ditambah jawaban BA di sesi
- **Contents:** pesan verbatim, tanpa lampiran
- **Supersedes (khusus to be):** "Reschedule hanya sebelum ada quotation Deal" (`docs-to-be/fr-nfr-penjualan-program.md` PJ-FR-26, `input/Master - Buat Program/2026-11-08-jawaban-reschedule/`); Q-PJ-22 yang ditunda di `input/Master - Buat Program/2026-11-08-jawaban-reschedule-lanjutan/`.

## Verbatim

Pertanyaan BA ke Labmania: apakah tetap bisa reschedule apabila sudah deal.

> Bisa mba, biasanya di hapus PO nya dulu kemudian revisi tanggal setelah itu di PO ulang

Catatan BA: "yang dimaksudkan PO disitu adalah SO".

Jawaban BA atas pertanyaan analis di sesi:

1. Kenapa SO dihapus: "iya karena mereka masih manual belum sistem, sehingga perlu hapus. tapi karena sudah sistem maka otomatis ajaa berubah dengan penomoran ada revisinya"
2. Peserta yang tidak bisa ikut di tanggal baru: "aku masih perlu konfirmasi ya"
3. Invoice yang sudah terbit / dibayar: "aku masih perlu konfirmasi ya"
4. Nomor SO setelah reschedule: "buat ada rev aja"
5. Reschedule saat acara sudah berjalan / Closed: "tidak boleh, saat belum berjalan aja ya"
6. Surat konfirmasi peserta yang memuat tanggal lama: "aku perlu konformasi lagi ya"

## Catatan penafsiran

1. Menghapus SO lalu membuat ulang adalah cara manual Labmania, **bukan** aturan untuk sistem. Di sistem, SO **tidak dihapus** (tetap SO-FR-10, `[JSO-3]`); jadwal di SO ikut jadwal program yang baru secara otomatis.
2. Nomor SO tetap, ditambah penanda revisi (mis. `SO-001 Rev-1`). Format penanda `Rev-n` = `[USULAN]`, karena format nomor SO sendiri masih menyusul (Q-SO-01).
3. Revisi naik untuk **semua SO program itu**, satu kali per reschedule yang **mengubah tanggal**. Reschedule yang hanya mengganti trainer tidak menaikkan revisi, karena trainer tidak tercetak di SO `[USULAN]`.
4. "Saat belum berjalan" = hari ini masih sebelum tanggal mulai jadwal yang berlaku, dan program belum Closed. Berlaku untuk reschedule sebelum maupun sesudah Deal `[USULAN]` (sebelum Deal sebelumnya tidak dibatasi).
5. Butir 2, 3, 6 belum dijawab. Sementara: peserta Deal tetap, invoice tidak diubah, surat konfirmasi yang sudah diunggah tetap berlaku `[USULAN]`, menunggu konfirmasi.

## Extracted

- PJ-FR-26, PJ-FR-27, PJ-NFR-10, UC-65, bagian 6, Q-PJ-22 -> `docs-to-be/fr-nfr-penjualan-program.md`
- SO-FR-16 (baru), SO-FR-06, SO-FR-10, Q-SO-06 (baru) -> `docs-to-be/fr-nfr-sales-order.md`
- ERD to be: `sales_order.revisi` -> `docs-to-be/diagrams/erd.drawio`
- Mockup -> `ui-mockup/produk-to-be/mod-program.js`, `mod-sales-order.js`, `mod-quotation.js`
