# Jawaban Labmania: reschedule, refund, asisten trainer, nomor SO & revisi, konfirmasi nama sertifikat (to be)

- **Source:** Labmania (jawaban ke BA proyek), diteruskan BA proyek beserta komentarnya, lewat sesi Claude Code
- **Date:** 2026-11-12
- **Channel:** chat (jawaban Labmania diteruskan BA), ditambah komentar dan permintaan BA di sesi yang sama
- **Contents:** 12 pasang pertanyaan–jawaban verbatim + komentar BA verbatim, tanpa lampiran
- **Menjawab:** Q-PJ-22 (b)(c)(d), Q-SO-01, Q-SO-04, Q-SO-06, Q-SKT-02 (`docs-to-be/`), dan pertanyaan sertifikat (nama peserta salah ketik)
- **Supersedes (khusus to be):** "reschedule selama acara belum berjalan" (`input/Master - Buat Program/2026-11-11-reschedule-setelah-deal/` butir 5) menjadi batas **H-7**; usulan sementara "peserta tetap, invoice tidak diubah, surat lama berlaku" di `docs-to-be/fr-nfr-penjualan-program.md` PJ-FR-27; format usulan "SO-001 Rev-1"; "Surat Kerja Trainer satu per program, tidak dibuat ulang".
- **Lintas paket:** butir 1 (sertifikat, Manage Peserta) dan butir 2/9 (refund, dekat Invoice) menyentuh paket **Invoice - Pengajuan**; BA meminta keduanya diperbarui di sesi ini.

## Verbatim

Format: pertanyaan BA ke Labmania = jawaban Labmania, lalu komentar BA di sesi.

1. Nama peserta menjadi acuan sertifikat. Kalau salah ketik pada surat konfirmasi, bagaimana? = Untuk nama pada sertifkat jika ada kesalahan penulisan pada form konfirmasi, biasanya draft sertifikat akan kita konfirmasi lagi kepada pesertanya langsung apakah penulisan nama sudah benar atau belum. Jika sudah kita akan kirim sertifikat softcopy atau hardcopynya.
   > BA: "nah menurut kamu gimana? erp labmania saat ini apakah bisa mengakomodir seperti itu?"
2. Kalau acara direschedule dan ada peserta yang sudah deal ternyata tidak bisa ikut di tanggal baru, biasanya bagaimana? = Diberi opsi tanggal lain atau bisa refund berdasarkan syarat ketentuan.
   > BA: "nah tolong dipikirin bagaimana flow refund di erp labmania ini ya."
3. Kalau invoice untuk program itu sudah terbit (atau sudah dibayar), lalu apakah tetap bisa reschedule? Masih bisa reschedule mba kalau blm memenuhi kuota running. Untuk kuota TS minimal 4, kuota workshop minimal 4, kuota workshop lisensi minimal 25 (cttn untuk ini kadang 4 peserta pun sudah bisa running)
   > BA: "nah menurut aku ini agak kurang oke sih.. menurut kamu gimana?"
4. Peserta sudah mengunggah surat konfirmasi bertanda tangan yang mencantumkan tanggal lama. Setelah reschedule bagaimana mba apakah perlu isi surat konfirmasi kembali? = Perlu di isi ulang untuk surat form konfirmasi terbaru sesuai tanggal terbaru
   > BA: "nah berarti kalo ada reschedule maka harus ada quotation rev ya dan link konfirmasi baru"
5. Batasan reschedule sih mba, tidak boleh reschedule kalau sudah di tahap apa? terkait SO nanti bisa ga harus di hapus apabila reschedule, tetapi terkait pesertanya apakah seluruh pesert di tanggal awal dan tanggal reschedule akan tetap ikut semua atau biasanya akan ada yang ga jadi ikut, lalu konfirmasi letter nya juga bagaimana? = Tidak ada batasan kalau reschedule, SO/nomor po bisa dihapus terlebih dahulu kemudian konfirm ke finance jika ada perubahan.
   > BA: "kalo seperti ini terlalu dinamis menurut ku, gimana batasan secara umum menurut kamu? dan baiknya agar tidak ribet nih struktur data di erp labmania. selain memudahkan user, seharusnya secara struktur ERP juga gaboleh ruwet"
6. Pada saat reschedule yang diubah hanya tanggal dan ada kemungkinan perubahan di trainer saja kan? dari biaya tidak ada perubahan? = tidak ada perubahan biaya
   > BA: "nah kalo gitu berarti saat ini sudah oke tuh pas mau reschedule ya"
7. Surat Kerja Trainer dibuat otomatis saat SO pertama? Kalau trainer diganti, surat dibuat ulang dengan nomor baru, atau surat lama dibatalkan? = Surat tugas dibuat ketika trainer dan asisten trainer (staf labmania) sudah ditetapkan. Surat dibuat ulang dengan nomor baru.
   > BA: "nah saat ini belum ada nih isi asisten trainer, tambahin ya. berarti adanya di penjualan program dan di reshedule juga selain bisa ganti trainer, bisa ganti asisten juga"
8. Ada format resmi PDF Sales Order dan kode dokumennya? = tidak ada
   > BA: "buatin ya yang kurang lebih penomorannya mirip dengan quotation dan invoice, bedakan kode saja"
9. Kalau acara direschedule setelah deal, lalu ada peserta yang tidak bisa ikut di tanggal baru, bagaimana penanganannya? = Jika jadwal di reschedule oleh LabMania, customer bisa ttp ikut jadwal reschedule atau refund.
   > BA: "seperti yang poin sebelumnya diatas"
10. Setelah reschedule, dokumen yang masih memuat tanggal lama (surat konfirmasi yang sudah ditandatangani peserta, Penawaran yang sudah terkirim) perlu diulang? = Perlu di ulang karena di sesuaikan dgn yang terbaru.
    > BA: "berarti kalo ada reschedule pada penjualan program, maka semua next process nya harus ulang rengan \"rev\""
11. Penawaran (quotation) juga mencantumkan tanggal acara. Kalau acara direschedule, apakah Penawaran ikut direvisi (nomor Rev) dan dicetak ulang seperti Sales Order? = Penawaran perlu di revisi seperti LM-I/AM/PW/11/26-063 menjadi LM-I/AM/PW/11/26-063-rev1. No revisi disesuaikan brp kali surat penawaran tersebut di revisi.
    > BA: "nah itu ya format rev nya"
12. Reschedule boleh selama acara belum berjalan. Apakah ada batas waktu minimal, misalnya tidak boleh reschedule H-1 atau H-3 sebelum acara? = H-7 sebelum pelatihan dimulai

Permintaan BA: "tolong di update di FR & NFR nya ya, lalu update di mock up juga. jelasin ke aku juga gimana jadinya flow-flownya"

## Catatan penafsiran

1. **Format revisi** = nomor dasar + `-rev{n}` (butir 11). Contoh Labmania memakai format lama `LM-I/AM/PW/...`; format nomor yang berlaku sejak 02-11-2026 `LMI/PW/MM/yy-zzz` (`input/Master - Buat Program/2026-11-02-format-penomoran-penawaran-invoice/`), jadi contoh menjadi `LMI/PW/11/26-063-rev1`. Penanda yang sama dipakai Sales Order (menggantikan usulan " Rev-n").
2. **Nomor Sales Order** = `LMI/SO/MM/yy-zzz` (butir 8: "mirip quotation dan invoice, bedakan kode"). Kode `SO` dipilih analis `[USULAN]`. Format resmi PDF tidak ada, jadi tata letak usulan dipakai; kode dokumen di kaki halaman tidak dicetak.
3. **"Semua next process ulang dengan rev"** (butir 4, 10) ditafsirkan sebagai: reschedule yang **mengubah tanggal** menaikkan revisi semua quotation program yang bukan Batal dan semua Sales Order-nya, membuat **link konfirmasi baru** per quotation, dan meminta setiap peserta (Menunggu/Deal) **konfirmasi ulang** dengan surat bertanggal baru. Invoice **tidak** direvisi karena biaya tidak berubah (butir 6); selisih peserta yang keluar diselesaikan lewat Refund.
4. **Batas waktu H-7** (butir 12) menggantikan "selama acara belum berjalan". Jawaban butir 5 "tidak ada batasan" dibaca sebagai: tidak ada batas **tahap** dokumen (boleh setelah Deal/SO/invoice) dan tidak ada batas jumlah; batas waktu H-7 tetap berlaku.
5. **SO tidak dihapus** (butir 5: "SO bisa dihapus dulu kemudian konfirm ke finance") — tetap mengikuti `[JSO-3]` dan `input/Master - Buat Program/2026-11-11-reschedule-setelah-deal/`: di sistem SO direvisi, tidak dihapus. "Konfirm ke finance" diwujudkan dengan daftar Refund yang diproses Finance.
6. **Peserta yang tidak bisa ikut** (butir 2, 9): pilihan **pindah ke jadwal lain** atau **refund** sesuai S&K. Penanganan di sistem (status Keluar, Refund hanya bila sudah ditagih, pindah jadwal = quotation baru di program lain) adalah rancangan analis `[USULAN]` atas permintaan BA.
7. **Asisten trainer** (butir 7) = staf LabMania, dipilih dari Master User. Satu orang dan wajib = `[USULAN]`. Surat Tugas memuat trainer dan asisten; setiap pergantian trainer/asisten (dan tanggal) menerbitkan surat baru dengan nomor baru, surat lama berstatus Diganti.
8. **Kuota running** (butir 3) tidak dijadikan aturan sistem: Labmania sendiri menyebut kadang 4 peserta sudah jalan untuk workshop lisensi. Dicatat sebagai pertanyaan (Master Varian kuota minimal sebagai informasi). "TS" belum jelas varian yang mana.
9. **Konfirmasi nama sertifikat** (butir 1): sistem saat ini mengambil nama dari isian link tanpa langkah konfirmasi. Ditambah langkah Draft → Nama dikonfirmasi sebelum nomor sertifikat terbit `[USULAN]`.

## Extracted

- PJ-FR-26 – PJ-FR-28, PJ-FR-30, PJ-FR-31 (baru), UC-65, PJ-NFR-10, bagian 6, Q-PJ-22 terjawab, Q-PJ-27 – Q-PJ-29 -> `docs-to-be/fr-nfr-penjualan-program.md`
- QTN-FR-11, QTN-FR-34 – QTN-FR-36 (baru), UC-71, UC-72, bagian 6, Q-QTN-24 -> `docs-to-be/fr-nfr-quotation.md`
- SO-FR-01, SO-FR-06, SO-FR-12 – SO-FR-14, SO-FR-16, SO-FR-17 – SO-FR-20 (Refund, baru), UC-73, Q-SO-01, Q-SO-04, Q-SO-06, Q-SKT-02 terjawab, Q-SO-07 -> `docs-to-be/fr-nfr-sales-order.md`
- SRT-FR-04 (baru), UC-74, Q-MON-08 -> `docs-to-be/fr-nfr-manage-peserta.md`
- ERD to be: `penjualan_program.id_asisten`, `penjualan_program_reschedule.id_asisten_lama/baru`, `quotation.revisi`, `quotation_peserta.status` + `keluar`, tabel `quotation_peserta_konfirmasi_ulang`, `quotation_konfirmasi_company.revisi`, `surat_kerja_trainer.status/id_asisten/id_reschedule`, tabel `sales_order_refund`, `quotation_peserta_sertifikat.nama_sertifikat/...` -> `docs-to-be/diagrams/erd.drawio`
- Mockup -> `ui-mockup/produk-to-be/mod-program.js`, `mod-quotation.js`, `mod-sales-order.js`, `mod-manage-peserta.js`, `konfirmasi.html`, `main.js`, `style.css`
