# Fase 7 — kesiapan cutover lokal

Disetujui pengguna 14-11-2026 melalui “Implement the plan.”
Rencana rinci dan task bertanggal disimpan tanpa overwrite di
`.artifacts/fase7_kesiapan_cutover/` pada root repository.

Bangun rehearsal terisolasi, profil/pemetaan histori, import dengan simulasi
default, rekonsiliasi, backup/restore serta runbook. CI3 aktif tidak dimigrasikan.
Konfigurasi Apache boleh diperkeras; kode bisnis dan database CI3 dilindungi.

Acceptance: semua histori snapshot lengkap terhitung, dua rehearsal identik,
restore berhasil, akses/dokumen privat terjaga, regresi lulus, konfigurasi sah.
Tanpa snapshot/keputusan wajib, jangan tandai kesiapan cutover selesai.
