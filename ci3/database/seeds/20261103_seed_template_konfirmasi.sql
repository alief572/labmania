-- Seed: data awal Template Formulir Konfirmasi sesuai FR-52 (specs/final/FR & NFR Master - Quotation.md)
-- Tanggal: 03-11-2026
-- 1) Catatan KF-001/KF-002 yang masih berisi teks karangan lama diganti tujuh butir FR-52 (sama untuk ketiganya).
-- 2) Template ketiga "Resertifikasi" (tanpa default Jenis Produk, 10 isian) ditambahkan bila belum ada.
-- Template yang sudah diubah user tidak disentuh (UPDATE hanya bila catatan masih teks lama).

SET @catatan = 'Konfirmasi kehadiran harap dikirimkan kembali ke email {email} atau ke nomor {telp} dengan subject email **Konfirmasi Peserta** kegiatan **{kegiatan}** dengan judul **{judul}**
Keikutsertaan kami hitung berdasarkan pengiriman formulir keikutsertaan ini yang tercepat.
Panitia berhak menolak jika kuota telah terpenuhi.
*) Nama lengkap akan menjadi acuan penulisan pada sertifikat.
Jika sudah mengisi formulir berikut, maka **peserta setuju untuk tidak membatalkan keikutsertaan dalam bentuk apapun.**
Formulir keikutsertaan ini maksimal diserahkan H-14 sebelum kegiatan.
Apabila ada yang ingin didiskusikan, silahkan segera menghubungi ke nomor {telp}';

UPDATE `ms_template_konfirmasi`
   SET `catatan` = @catatan, `updated_date` = NOW()
 WHERE `kode` IN ('KF-001', 'KF-002')
   AND `catatan` LIKE 'Catatan:%Pembayaran paling lambat%';

INSERT INTO `ms_template_konfirmasi` (`kode`, `nama`, `label_kegiatan`, `id_jenis_produk`, `isian`, `pernyataan`, `catatan`, `aktif`, `created_date`)
SELECT 'KF-003', 'Resertifikasi', 'Resertifikasi', NULL,
       '["nama","nik","jabatan","instansi","alamat","hp","wa","email","lsp","habis"]',
       'Saya **akan mengikuti** pada kegiatan **Resertifikasi {judul}** dan menyiapkan dokumen yang dibutuhkan dalam rentang waktu resertifikasi **(H-3 Bulan)**',
       @catatan, 1, NOW()
 WHERE NOT EXISTS (SELECT 1 FROM `ms_template_konfirmasi` WHERE `kode` = 'KF-003');
