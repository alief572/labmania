# Format resmi: Surat Kerja Trainer (Surat Tugas), Name Table, Daftar Hadir; tanggal = tanggal print (to be)

- **Source:** tim Labmania / BA proyek, disampaikan lewat sesi Claude Code
- **Date:** 2026-11-10
- **Channel:** chat + tiga berkas format
- **Contents:** pesan verbatim; berkas diletakkan user langsung di `input/` (tidak dipindah):
  - `input/FR LMI TR 05-01 Surat Tugas OK (1).doc` — Surat Tugas, A4 portrait, kode `FR/LMI-TR-05/01/Ed.Rev-1.0`, Berlaku 01/08/2023
  - `input/Name Table.pptx` — 30 slide, A4 landscape (29,7 × 21 cm), satu peserta per slide
  - `input/FR LMI TR 05 17 Daftar Hadir Peserta OKk - Copy.pdf` — 3 halaman A4 landscape, kode `FR/LMI-TR-05/17/Ed.Rev-1.0`, Berlaku 01/08/2023
- **Supersedes (khusus to be):** tata letak usulan Surat Kerja Trainer `docs-to-be/fr-nfr-sales-order.md` SO-FR-14; template usulan Daftar Hadir `docs-to-be/fr-nfr-manage-peserta.md` MP-FR-07 (satu lembar per hari, ttd selang-seling, ttd Trainer & Penyelenggara). Menjawab Q-MPS-04.

## Verbatim

> aku udah input format-format tambahan yang sebelumnya kamu tanyakan:
> 1. Format surat kerja trainer, nama file nya "FR LMI TR 05-01 Surat Tugas OK (1)"
> 2. Format Name table, nama file nya "Name Table" ini ada di manage peserta untuk di print out ya. 1 lembar 1 name table
> 3. Format daftar hadir/absensi, nama file nya "FR LMI TR 05 17 Daftar Hadir Peserta OKk - Copy"
>
> nah kalo didalamnya ada tanggal maka dibuat ke tanggal dimana dokumen tersebut di print ya

## Isi format (dibaca dari berkas)

**1. Surat Tugas** (`.doc`): kop logo LabMania + "PT. LABMANIA INDONESIA JAYA", alamat Ruby Commercial No. TC25, website, email `labjourneypartner@labmaniaindonesia.id`, Customer Care. Judul **SURAT TUGAS** (bergaris bawah), "No. ……". Paragraf: "Menindaklanjuti kegiatan …… "……", saya yang bertanda tangan di bawah ini: Nama : …, Jabatan : …". "Dengan ini menugaskan kepada :" tabel **No., Nama, NIK, Jabatan** (10 baris). "sebagaimana ditugaskan sebagai …… dalam acara …… "……" bertempat di …… yang dilaksanakan pada : Hari/ Tanggal : …/…, Waktu : ……". Penutup "Demikian Surat Tugas ini dibuat agar yang bersangkutan dapat melaksanakan dengan baik dan penuh tanggung jawab." Tanda tangan kanan: "……, ……" lalu **Operational Manager**, "(……)". Kaki: kode dokumen · "1 dari 1" · "Berlaku 01/08/2023".

**2. Name Table** (`.pptx`): tanpa logo dan tanpa tanggal. Halaman dilipat dua di tengah (tinggi 10,5 cm). Separuh bawah: **Nama** (Times New Roman 40 pt tebal, tengah) lalu **Instansi** (38 pt) di bawahnya. Separuh atas: isi yang sama diputar 180° (terbaca dari sisi lain meja). Satu slide = satu peserta.

**3. Daftar Hadir Peserta** (`.pdf`): logo kiri, "PT LABMANIA INDONESIA" kanan; judul **DAFTAR HADIR PESERTA**; **Jenis Kegiatan, Judul Kegiatan, Hari/Tanggal, Tempat**; tabel **No., Nama, Instansi, No. HP, Email, Tanda Tangan, Ket.**, **10 baris per halaman** (nomor berlanjut 1–30 di 3 halaman); di bawah tabel (miring): "Keterangan : Mohon isikan nama dengan lengkap boleh ditambahkan dengan gelar karena akan dijadikan acuan dalam penulisan di sertifikat."; kaki kode dokumen · "x dari y" · "Berlaku 01/08/2023".

## Catatan penafsiran

1. **Surat Kerja Trainer dicetak dengan format Surat Tugas** (judul tercetak "SURAT TUGAS"); nama menu dan tab di sistem tetap "Surat Kerja Trainer".
2. Isian Surat Tugas dari data sistem: kegiatan/acara = Varian + judul program; tabel penugasan = trainer program (satu baris, Jabatan "Trainer"); "ditugaskan sebagai" = Trainer; bertempat di = lokasi; Hari/Tanggal = **jadwal pelaksanaan** program; Waktu = waktu program; nomor = nomor surat. Penanda tangan berjabatan **Operational Manager** (dari format).
3. "Tanggal = tanggal print": tanggal di atas tanda tangan Surat Tugas = hari dicetak (sudah sejalan dengan SO-FR-15). **Hari/Tanggal pelaksanaan di Surat Tugas tetap jadwal program**, karena surat menugaskan trainer untuk tanggal acara; bila diganti tanggal cetak, isi surat salah. `[USULAN]` penafsiran analis.
4. **Daftar Hadir: Hari/Tanggal = tanggal dicetak.** Akibatnya cetak satu set (semua peserta Deal, 10 per halaman), **tidak lagi satu lembar per hari pelaksanaan**; untuk program beberapa hari, daftar hadir dicetak tiap hari. `[USULAN]` penafsiran analis.
5. Daftar Hadir: Nama, Instansi, No. HP, Email diisi dari data peserta Deal; Tanda Tangan dan Ket. kosong. Halaman terakhir ditambah baris kosong bernomor sampai 10 baris (format memakai 10 baris tetap) `[USULAN]`. Jenis Kegiatan = Varian Pelaksanaan `[USULAN]`.
6. Name Table di halaman peserta program (Manage Peserta): cetak semua peserta Deal, **satu peserta satu lembar**.
7. Belum ada di data sistem: **NIK trainer** (Master Trainer tidak punya field NIK) dan **nama Operational Manager**. Sementara keduanya dikosongkan untuk diisi tangan (Q-SKT-04).

## Extracted

- SO-FR-14, SO-FR-15, UC-64, Q-SKT-04 -> `docs-to-be/fr-nfr-sales-order.md`
- MP-FR-04, MP-FR-07, MP-FR-10, MP-FR-11 (Name Table), UC-51, Q-MPS-04 -> `docs-to-be/fr-nfr-manage-peserta.md`
- `docs-to-be/README.md`
- Mockup -> `ui-mockup/produk-to-be/mod-sales-order.js`, `mod-manage-peserta.js`, `main.js`, `style.css`
