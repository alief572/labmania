/* Labmania mockup to be — data contoh untuk test case (permintaan BA 12-11 dan 13-11-2026). Nama lokasi dibuat wajar, tanpa label uji;
   daftar skenario dan langkah tes ada di specs/pm/test-case-mockup-labmania_13-11-2026.xlsx (sheet Data Siap Pakai).
   Tanggal dihitung dari hari ini sehingga kondisi setiap skenario tetap sama kapan pun mockup dibuka. Dipanggil main.js setelah rsDemo(). */
'use strict';
  function ujiKasus() {
    const hari = n => addDays(todayIso, n);
    const psId = nama => (db.peserta.find(p => p.nama === nama) || {}).id;
    const us = db.user.filter(u => u.aktif);
    const co2 = db.company[1];
    // Peserta khusus skenario ini (nama berbeda dari data contoh lain supaya mudah dicari saat tes).
    const bidang = (db.jenisBidang.find(x => x.nama === 'ISO 17025') || db.jenisBidang[0] || {}).id;
    [['Andi Pratama', 'Laki-laki', 'Laboratorium Klinik Sehat Sentosa'], ['Siti Rahmawati', 'Perempuan', 'PT Mutu Pangan Lestari'], ['Dewi Lestari', 'Perempuan', 'PT Mutu Pangan Lestari'],
     ['Rudi Hartono', 'Laki-laki', 'Laboratorium Lingkungan Bekasi'], ['Fajar Nugroho', 'Laki-laki', co2.nama], ['Maya Sari', 'Perempuan', co2.nama], ['Agus Setiawan', 'Laki-laki', co2.nama],
     ['Nina Kurnia', 'Perempuan', 'Laboratorium Kalibrasi Prima'], ['Hendra Wijaya', 'Laki-laki', 'Laboratorium Kalibrasi Prima'], ['Lina Marlina', 'Perempuan', 'PT Analitika Jaya'],
     ['Dimas Prakoso', 'Laki-laki', 'Laboratorium Kalibrasi Prima'], ['Yusuf Hidayat', 'Laki-laki', 'PT Air Bersih Nusantara'], ['Rizky Ramadhan', 'Laki-laki', 'PT Air Bersih Nusantara'], ['Putri Ayu', 'Perempuan', 'Laboratorium Klinik Sehat Sentosa'], ['Bayu Saputra', 'Laki-laki', co2.nama]]
      .forEach(([nama, gender, instansi], i) => db.peserta.push({ id: uid(), kode: nextCode('PS'), nama, gender, company: instansi === co2.nama ? co2.id : '', instansi, bidang,
        provinsi: instansi === co2.nama ? co2.provinsi : 'Jawa Barat', kota: instansi === co2.nama ? co2.kota : 'Kota Bekasi', telp: `08121000${String(20 + i).padStart(4, '0')}`,
        email: `${nama.toLowerCase().replace(/[^a-z]+/g, '.')}@contoh.id` }));
    const tim = { waktu: '09.00–15.15 WIB', trainer: (db.trainer[0] || {}).id || '', asisten: (us[2 % us.length] || {}).id || '' };
    const mkPj = (lokasi, mulai, mp = mp1, jenis = 'public', extra = {}) => seedQT(Object.assign(fromMP(mp, jenis), { status: 'final', hargaJual: jenis === 'public' ? 650000 : mp.hargaJual,
      tglMulai: mulai, tglSelesai: addDays(mulai, (mp.durasi || 2) - 1), lokasi }, tim, extra));
    const mkQ = (pj, nama, o = {}) => {
      const tgl = o.tanggal || addDays(pj.tglMulai, -30), tunggu = o.status === 'terkirim';
      const k = Object.assign({ id: uid(), nomor: qtnNo(tgl), pj: pj.id, tanggal: tgl, berlaku: addDays(pj.tglMulai, -1), sales: db.user[1].id, sumber: 'Sales', ppn: true, diskon: 0, lain: 0,
        up: '', tujuan: 'personal', company: '', alamat: 'Kota Bekasi, Jawa Barat', kuota: nama.length, status: 'deal',
        peserta: nama.map(n => ({ id: psId(n), st: tunggu ? 'menunggu' : 'deal' })) }, o);
      if (k.status === 'deal' && !k.dokumen) { k.dokumen = [{ nama: `Bukti-transfer-${k.nomor.slice(-3)}.jpg`, ukuran: 210000, oleh: 'Contoh User Dua', tgl: `${addDays(tgl, 2)} 10:00` }]; k.dokKet = 'Bukti transfer'; }
      k.ket = ketDefault(pj.jenis); k.kf = kfNew(kfDefaultFor(pj)); db.qtn.push(k); return k;
    };
    const kCo = { tujuan: 'company', company: co2.id, up: co2.picNama, alamat: `${co2.alamat}, ${co2.kota}` };
    const mkInv = (k, bayar) => {
      const tgl = addDays(k.tanggal, 3), pl = ivcPerlakuan(k), u = ivcNilaiUnit(k, 'satu'), hh = ivcHitung(u.penjualan, u.lain, pl), nomor = ivcNo(tgl), coa = coaSalesDefault(k);
      const i = Object.assign({ id: uid(), nomor, tanggal: tgl, qtn: k.id, prog: progNo(k), jenis: pjOfQtn(k).jenis, cara: 'satu', peserta: u.ps, perlakuan: pl,
        noFaktur: `0400260000${String(300 + db.inv.length)}`, coaPenjualan: coa, setor: null, status: 'invoiced', jurnal: ivcJurnal(hh, pl, coa, nomor, k, tgl) }, hh);
      if (bayar) i.bayar = { tanggal: addDays(tgl, 2), oleh: 'Admin Sistem', ket: 'Transfer BCA' };
      db.inv.push(i); Object.assign(soOfQtn(k) || {}, { status: 'siap', siapBy: 'Admin Sistem', siapAt: `${tgl} 09:00` });
      return i;
    };

    // Acara sudah lewat, satu-satunya invoice sudah dibayar: siap Close Program.
    const pjSiap = mkPj('Hotel Santika Bekasi', hari(-12));
    const qSiap = mkQ(pjSiap, ['Andi Pratama']);
    // Acara sudah lewat, belum bisa Close: satu invoice belum dibayar, satu quotation Deal belum ditagih.
    const pjBelum = mkPj('Hotel Horison Bekasi', hari(-8));
    const qBelumA = mkQ(pjBelum, ['Siti Rahmawati']), qBelumB = mkQ(pjBelum, ['Dewi Lestari']);
    // Program sudah Closed: quotation dan invoice terkunci.
    const pjClosed = mkPj('Online (Google Meet)', hari(-20));
    const qClosed = mkQ(pjClosed, ['Rudi Hartono']);
    // Acara 10 hari lagi: link Konfirmasi Kehadiran sudah tertutup (batas H-14).
    const pjH14 = mkPj('Hotel Aston Cirebon', hari(10));
    mkQ(pjH14, ['Fajar Nugroho'], Object.assign({ status: 'terkirim', kuota: 3, tanggal: hari(-6) }, kCo));
    // Kuota quotation penuh, link tertutup: dibuka lagi lewat Tambah kuota.
    const pjKuota = mkPj('Hotel Santika Surabaya', hari(45));
    mkQ(pjKuota, ['Maya Sari', 'Agus Setiawan'], Object.assign({ status: 'terkirim', kuota: 2, tanggal: hari(-3) }, kCo));
    // Sudah di-reschedule ke besok; tiga peserta belum menjawab konfirmasi ulang sampai H-1.
    const pjH1 = mkPj('Hotel Grand Dafam Bekasi', hari(30));
    const qH1a = mkQ(pjH1, ['Nina Kurnia'], { tanggal: hari(-10) }), qH1b = mkQ(pjH1, ['Hendra Wijaya'], { tanggal: hari(-10) }), qH1c = mkQ(pjH1, ['Lina Marlina'], { tanggal: hari(-10) });
    // Siap di-reschedule oleh penguji: Deal + SO + invoice (dibayar / belum dibayar / belum ditagih) dan satu quotation masih Terkirim.
    const pjResch = mkPj('Hotel Novotel Bogor', hari(30));
    const qRa = mkQ(pjResch, ['Yusuf Hidayat'], { tanggal: hari(-12) }), qRb = mkQ(pjResch, ['Rizky Ramadhan'], { tanggal: hari(-12) }), qRc = mkQ(pjResch, ['Putri Ayu'], { tanggal: hari(-12) });
    mkQ(pjResch, ['Bayu Saputra'], Object.assign({ status: 'terkirim', kuota: 3, tanggal: hari(-5) }, kCo));
    // Sudah di-reschedule dan sudah Deal ulang: SO lama di tab SO Batal, SO -rev1 berlaku, invoice dibayar tetap (penanda ikut jadwal baru).
    const pjDu = mkPj('Hotel Harris Bekasi', hari(25));
    const qDu = mkQ(pjDu, ['Dimas Prakoso'], { tanggal: hari(-15) });
    // Siap dibuatkan quotation: Public dan B2B Final tanpa quotation.
    mkPj('Hotel Ibis Jakarta Cawang', hari(40));
    mkPj('In house — Sidoarjo', hari(35), mp2, 'b2b', { company: co2.id, targetPeserta: 15 });

    qpsSeed(); // peserta quotation baru → isian Konfirmasi Kehadiran (surat contoh ikut dibuat)
    [qSiap, qBelumA, qBelumB, qClosed, qH1a, qH1b, qH1c, qRa, qRb, qRc, qDu].forEach(k => soCreate(k, addDays(k.tanggal, 2)));
    mkInv(qSiap, true); mkInv(qBelumA, false); mkInv(qClosed, true); mkInv(qH1a, true); mkInv(qH1b, false); mkInv(qRa, true); mkInv(qRb, false); mkInv(qDu, true);
    pjClosed.closed = { by: 'Contoh User Dua', at: `${hari(-5)}T16:00` };
    reschedApply(pjH1, { mulai: hari(1), trainer: pjH1.trainer, asisten: pjH1.asisten, alasan: 'Ruang pelatihan tidak tersedia', evidence: ['Surat-pembatalan-ruang.jpg'], oleh: 'Contoh User Dua', waktuUbah: `${hari(-2)}T10:00` });

    reschedApply(pjDu, { mulai: hari(32), trainer: pjDu.trainer, asisten: pjDu.asisten, alasan: 'Trainer berhalangan di tanggal lama', evidence: ['Surat-trainer.pdf'], oleh: 'Contoh User Dua', waktuUbah: `${hari(-3)}T09:00` });
    qDu.peserta.forEach(r => { r.konf = { rev: qDu.rev, keputusan: 'ikut', catatan: '', tgl: `${hari(-2)} 10:00`, sumber: 'link' }; });
    dealApply(qDu, qDu.peserta.map(r => r.id), { tgl: hari(-1) });
    // Product lama yang dinonaktifkan dengan kombinasi sama dengan Product aktif: tidak dapat diaktifkan kembali.
    const pk = db.paket.find(p => p.aktif);
    if (pk) db.paket.push(Object.assign({}, pk, { id: uid(), nomor: nextCode('PC'), judul: `${pk.judul} (versi lama)`, aktif: false }));
    // Company yang belum dipakai transaksi: boleh dihapus.
    db.company.push(Object.assign({ id: uid(), kode: nextCode('CO'), aktif: true }, { nama: 'PT Sinar Analitika Mandiri', jenis: 'Swasta', alamat: 'Jl. Ahmad Yani No. 21', provinsi: 'Jawa Barat',
      kota: 'Kota Bekasi', telp: '0218880021', email: 'info@sinaranalitika.co.id', picNama: 'Rina Kartika', picJabatan: 'Manajer Mutu', picTelp: '081300000021', picEmail: 'rina@sinaranalitika.co.id', npwp: '012345678921000', ppnPerlakuan: 'PKP' }));
  }
