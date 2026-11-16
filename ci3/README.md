# Arsip CodeIgniter 3

Sejak 16-11-2026, CI3 dipindahkan ke folder ini sebagai arsip. Aplikasi utama
Laravel berada satu tingkat di atas; jalankan Composer, npm dan Artisan dari
root repository, bukan folder ini.

Kode, aset, upload, SQL, dependency dan konfigurasi lokal CI3 dipertahankan.
File rahasia dan upload yang sebelumnya tidak dilacak tetap tidak dipublikasikan.
Panduan Docker lama ada di `README_DOCKER.md` sebagai referensi historis;
container `labmania_uji` sudah dihentikan dan tidak boleh dinyalakan langsung
karena bind mount lamanya menunjuk root Laravel.

Database `labmania_db` tetap berjalan untuk koneksi GL Laravel. Konfigurasi
bind mount `docker/mysql/` tetap berada di root repository. Pengarsipan CI3 tidak
menghapus container, database, volume, atau dokumen.
