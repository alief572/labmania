<script>window.BASE_URL = '<?= base_url(); ?>';</script>
<script src="<?= base_url('assets/labmania/js/wilayah.js'); ?>"></script>
<?php
$q = $q ?? [];
$b2bAktif = $b2bAktif ?? [];
$id = $q['id'] ?? '';
$isEdit = !empty($id);
$isNew = empty($id);

$idPj = $q['id_penjualan'] ?? '';
$tujuan = $q['tujuan'] ?? 'personal';
$skema = $q['skema_harga'] ?? 'peserta';
$isPpn = !empty($q['is_ppn']);
$ppnPersen = (float)($q['ppn_persen'] ?? 11);
$status = $q['status'] ?? 'draft';

// Konfirmasi isian
$kfIsian = [];
if (!empty($q['konfirmasi_isian'])) {
    $kfIsian = is_string($q['konfirmasi_isian']) ? json_decode($q['konfirmasi_isian'], true) : $q['konfirmasi_isian'];
    if (!is_array($kfIsian)) $kfIsian = [];
}
?>
<div class="page-head">
  <div>
    <h1><?= $isEdit ? 'Edit Quotation (' . htmlspecialchars($q['nomor']) . ')' : 'Buat Quotation Baru'; ?> <span class="ref">QTN-FR-04 – QTN-FR-08 · QTN-FR-20 – QTN-FR-22</span></h1>
    <p>Penyiapan surat penawaran resmi, penetapan kuota dan skema harga, dan perumusan klausul syarat ketentuan. Peserta tidak dipilih di sini: calon peserta mengisi sendiri lewat link Konfirmasi Kehadiran yang dibuat saat quotation disimpan.</p>
  </div>
  <div class="page-actions">
    <a href="<?= site_url('quotation_program'); ?>" class="btn btn-muted">&larr; Kembali ke Daftar</a>
  </div>
</div>

<!-- STEPPER -->
<ol class="stepper" id="stepper-nav">
  <li class="on"><button type="button" class="step-btn" data-step="1" onclick="goToStep(1)"><span>1</span>Data Quotation</button></li>
  <li><button type="button" class="step-btn" data-step="2" onclick="goToStep(2)"><span>2</span>Keterangan</button></li>
  <li id="step3-btn"><button type="button" class="step-btn" data-step="3" onclick="goToStep(3)"><span>3</span><b id="step3-label" style="font-weight:inherit">Formulir Konfirmasi</b></button></li>
  <li><button type="button" class="step-btn" data-step="4" onclick="goToStep(4)"><span id="pv-no">4</span>Preview</button></li>
</ol>

<form id="qtn-form" onsubmit="event.preventDefault(); submitQtn('draft');">
  <input type="hidden" id="qtn-id" value="<?= $id; ?>">

  <!-- ==================== STEP 1: DATA QUOTATION & PESERTA ==================== -->
  <div class="step-panel" id="panel-step-1">
   <div class="card qtn-form">
    <p class="qtn-h">Data Quotation</p>
    <div class="grid4 qtn-top">
      <div class="field span3">
        <label>Penjualan Program <span class="req">*</span></label>
        <select class="input" id="id_penjualan" required onchange="onPenjualanChange()">
          <option value="">— Pilih Penjualan Program Final —</option>
          <?php foreach ($penjualans as $pj): ?>
            <option value="<?= $pj['id']; ?>"
                    data-jenis="<?= $pj['jenis']; ?>"
                    data-customer="<?= $pj['id_customer'] ?? ''; ?>"
                    data-company="<?= htmlspecialchars($pj['nama_company'] ?? ''); ?>"
                    data-harga="<?= (float)$pj['harga_jual_peserta']; ?>"
                    data-lokasi="<?= htmlspecialchars($pj['lokasi'] ?? ''); ?>"
                    data-tgl="<?= !empty($pj['tgl_mulai']) ? date('d M Y', strtotime($pj['tgl_mulai'])) . ' – ' . date('d M Y', strtotime($pj['tgl_selesai'])) : '—'; ?>"
                    data-target="<?= (int)$pj['target_peserta']; ?>"
                    data-judul="<?= htmlspecialchars($pj['nama_paket'] ?? ''); ?>"
                    data-produk="<?= (int)($pj['id_jenis_produk'] ?? 0); ?>"
                    data-waktu="<?= htmlspecialchars($pj['waktu'] ?? ''); ?>"
                    data-durasi="<?= (int)($pj['durasi_hari'] ?? 0); ?>"
                    <?= ($idPj == $pj['id']) ? 'selected' : ''; ?>
                    <?= (in_array((int)$pj['id'], $b2bAktif, true) && $idPj != $pj['id']) ? 'disabled' : ''; ?>>
              <?= htmlspecialchars($pj['nomor']); ?> · <?= htmlspecialchars($pj['nama_paket']); ?> · <?= $pj['jenis'] === 'b2b' ? 'B2B' : 'Public'; ?> · <?= date('d-m-Y', strtotime($pj['tgl_mulai'])); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <span class="hint" id="pj-info-box">Hanya Penjualan Program berstatus Final.</span>
      </div>

      <div class="field">
        <label>Nomor Quotation</label>
        <input type="text" class="input" id="nomor" value="<?= htmlspecialchars($q['nomor'] ?? ''); ?>" placeholder="otomatis saat disimpan" readonly>
        <span class="hint">Format LMI/PW/MM/yy-zzz dari tanggal quotation</span>
      </div>

      <div class="field">
        <label>Tanggal quotation <span class="req">*</span></label>
        <input type="date" class="input" id="tanggal" value="<?= htmlspecialchars($q['tanggal'] ?? date('Y-m-d')); ?>" required onchange="onTanggalChange()">
      </div>

      <div class="field">
        <label>Berlaku sampai</label>
        <input type="date" class="input" id="berlaku_sampai" value="<?= htmlspecialchars($q['berlaku_sampai'] ?? date('Y') . '-12-31'); ?>" onchange="beTouched = true">
        <span class="hint">Default 31 Desember tahun quotation; link konfirmasi tertutup setelah tanggal ini</span>
      </div>

      <div class="field">
        <label>Sales / marketing <span class="req">*</span></label>
        <select class="input" id="id_sales" required>
          <option value="">— Pilih —</option>
          <?php foreach ($users as $u):
            if (empty($u['id_user']) || (empty($u['aktif']) && ($q['id_sales'] ?? '') != $u['id_user'])) continue; ?>
            <option value="<?= $u['id_user']; ?>" <?= ($q['id_sales'] ?? '') == $u['id_user'] ? 'selected' : ''; ?>>
              <?= htmlspecialchars($u['nama'] ?: $u['username']); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <span class="hint">Dari Master User aktif</span>
      </div>

      <div class="field">
        <label>Sumber informasi</label>
        <select class="input" id="sumber_info">
          <option value="">—</option>
          <?php $sumberCur = $q['sumber_info'] ?? ''; $sumberList = ['Sales', 'Media sosial', 'Website', 'Referensi / repeat order', 'Lainnya'];
          if ($sumberCur !== '' && !in_array($sumberCur, $sumberList)) $sumberList[] = $sumberCur;
          foreach ($sumberList as $sv): ?>
            <option <?= $sumberCur === $sv ? 'selected' : ''; ?>><?= htmlspecialchars($sv); ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="qtn-grid">
      <!-- KIRI: DITUJUKAN KEPADA -->
      <div>
        <p class="qtn-h">Ditujukan kepada</p>
        <div class="field">
          <label>Quotation ke <span class="req">*</span></label>
          <div class="static seg">
            <label><input type="radio" name="tujuan" value="company" id="tujuan_company" <?= ($tujuan === 'company') ? 'checked' : ''; ?> onchange="onTujuanChange()"> Company</label>
            <label><input type="radio" name="tujuan" value="personal" id="tujuan_personal" <?= ($tujuan === 'personal') ? 'checked' : ''; ?> onchange="onTujuanChange()"> Personal</label>
          </div>
          <span class="hint" id="tujuan-hint">Company = surat ke satu company · Personal = surat ke satu orang (isi Kepada).</span>
        </div>

        <div class="field" id="wrap-company" style="display: <?= ($tujuan === 'company') ? 'block' : 'none'; ?>;">
          <label>Company <span class="req">*</span></label>
          <select class="input" id="id_customer" onchange="onCompanyChange()">
            <option value="">— Pilih Company —</option>
            <?php foreach ($companies as $co): ?>
              <option value="<?= $co['id_customer']; ?>"
                      data-alamat="<?= htmlspecialchars($co['address_office'] ?? ''); ?>"
                      data-kota="<?= htmlspecialchars($co['kota'] ?? ''); ?>"
                      <?= ($q['id_customer'] ?? '') == $co['id_customer'] ? 'selected' : ''; ?>>
                <?= htmlspecialchars($co['id_customer']); ?> — <?= htmlspecialchars($co['name_customer']); ?>
              </option>
            <?php endforeach; ?>
          </select>
          <span class="hint">Dari Master Company aktif</span>
        </div>

        <div class="field" id="wrap-kepada" style="display: <?= ($tujuan === 'company') ? 'none' : 'block'; ?>;">
          <label>Kepada <span class="req">*</span></label>
          <input type="text" class="input" id="kepada_nama" maxlength="100" value="<?= htmlspecialchars($q['kepada_nama'] ?? ''); ?>" placeholder="Nama orang yang dituju">
          <span class="hint">Peserta belum ada saat quotation dibuat, jadi nama yang dituju diisi di sini.</span>
        </div>

        <div class="field" id="wrap-up" style="display: <?= ($tujuan === 'company') ? 'block' : 'none'; ?>;">
          <label>u.p.</label>
          <input type="text" class="input" id="up_nama" maxlength="100" value="<?= htmlspecialchars($q['up_nama'] ?? ''); ?>" placeholder="Nama yang dituju (default PIC company)">
        </div>

        <div class="field">
          <label>Alamat</label>
          <textarea class="input" id="alamat" rows="2"><?= htmlspecialchars($q['alamat'] ?? ''); ?></textarea>
          <span class="hint">Terisi otomatis dari Company; dapat diubah untuk surat ini.</span>
        </div>
      </div>

      <!-- KANAN: KUOTA PESERTA (QTN-FR-22) -->
      <div>
        <p class="qtn-h">Kuota peserta</p>
        <div class="field">
          <label for="kuota_peserta">Kuota peserta <span class="req">*</span></label>
          <input type="number" class="input" id="kuota_peserta" min="1" step="1" inputmode="numeric" value="<?= (int)($q['kuota_peserta'] ?? 0) ?: ''; ?>" oninput="calcQtnValue(); updateKuotaInfo()">
          <span class="hint" id="kuota-min-hint"></span>
        </div>
        <div class="static kuota-box" id="kuota-info"></div>
        <p class="hint">Peserta tidak dipilih di form ini. Calon peserta mengisi sendiri lewat link Konfirmasi Kehadiran yang dibuat otomatis saat quotation disimpan. <span id="kuota-b2b-note"></span></p>
      </div>
    </div>

    <!-- HARGA -->
    <p class="qtn-h">Harga</p>
    <div class="qtn-grid">
      <div>
        <div class="field" id="wrap-skema" style="display:none;">
          <label>Skema harga</label>
          <div class="static"><b>All-in 1 company</b></div>
          <span class="hint">B2B hanya all-in: harga paket untuk s.d. <?= (int)Quotation_model::B2B_PAKET_MAKS; ?> peserta, peserta ke-<?= (int)Quotation_model::B2B_PAKET_MAKS + 1; ?> dst + Rp<?= number_format(Quotation_model::B2B_TAMBAHAN, 0, ',', '.'); ?>/orang.</span>
        </div>

        <div class="field" id="wrap-harga-allin" style="display:none;">
          <label>Harga all-in (Rp) <span class="req">*</span></label>
          <input type="text" class="input money-in" id="harga_allin" value="<?= number_format((float)($q['harga_allin'] ?? 0), 0, ',', '.'); ?>" oninput="calcQtnValue()">
          <span class="hint">Harga paket s.d. <?= (int)($q['paket_maks_peserta'] ?: Quotation_model::B2B_PAKET_MAKS); ?> peserta. Terisi awal harga / peserta × kuota (maks. <?= (int)Quotation_model::B2B_PAKET_MAKS; ?>); ubah sesuai kesepakatan.</span>
        </div>

        <div class="field" id="wrap-harga-peserta">
          <label>Harga / peserta (Rp)</label>
          <input type="text" class="input money-in" id="harga_peserta" readonly value="<?= number_format((float)($q['harga_peserta'] ?? 0), 0, ',', '.'); ?>" >
          <span class="hint">Dari Penjualan Program Final, tidak dapat diubah di sini.</span>
        </div>

        <div class="field">
          <label id="lbl-diskon">Diskon (Rp / peserta)</label>
          <input type="text" class="input money-in" id="diskon" value="<?= number_format((float)($q['diskon'] ?? 0), 0, ',', '.'); ?>" placeholder="0" oninput="calcQtnValue()">
          <span class="hint">Rupiah saja, tanpa %.</span>
        </div>

        <div class="field">
          <label>Biaya lain-lain (Rp)</label>
          <input type="text" class="input money-in" id="biaya_lain" value="<?= number_format((float)($q['biaya_lain'] ?? 0), 0, ',', '.'); ?>" placeholder="0" oninput="calcQtnValue()">
          <span class="hint">Baris "Biaya Lain-lain" di Penawaran</span>
        </div>
        <input type="hidden" id="ppn_persen" value="<?= $ppnPersen; ?>">
      </div>

      <div class="totals">
        <div class="tr" id="row-allin" style="display:none;"><span>Harga all-in (paket)</span><span id="tot-allin">Rp 0</span></div>
        <div class="tr" id="row-tambahan" style="display:none;"><span id="lbl-tambahan">Tambahan peserta</span><span id="tot-tambahan">Rp 0</span></div>
        <div class="tr"><span>Subtotal</span><span id="tot-subtotal">Rp 0</span></div>
        <div class="tr"><span>Diskon</span><span id="tot-diskon">- Rp 0</span></div>
        <div class="tr"><span>Setelah diskon</span><span id="tot-dpp">Rp 0</span></div>
        <div class="tr"><label class="chk"><input type="checkbox" id="is_ppn" <?= $isPpn ? 'checked' : ''; ?> onchange="calcQtnValue()"> PPN <?= $ppnPersen; ?>%</label><span id="tot-ppn">Rp 0</span></div>
        <div class="tr"><span>Biaya lain-lain</span><span id="tot-lain">Rp 0</span></div>
        <div class="tr grand"><span><b>Grand total</b></span><span><b id="tot-grand">Rp 0</b></span></div>
        <div class="tr"><span class="muted">Harga / peserta setelah diskon</span><span class="muted" id="tot-net-pax">Rp 0</span></div>
      </div>
    </div>
   </div>
  </div>

  <!-- ==================== STEP 2: KETERANGAN & S&K ==================== -->
  <div class="step-panel" id="panel-step-2" style="display:none;">
    <div class="card qtn-form">
      <div class="ket-tools">
        <span class="muted">Centang butir yang dicetak di Penawaran. Butir Default sudah tercentang; perubahan hanya untuk quotation ini.</span>
        <button type="button" class="btn btn-ghost sm" onclick="ketReset()">Kembalikan ke default</button>
      </div>
      <div id="ket-root"></div>
      <p class="hint">Klausul tanda tangan dicetak otomatis dari Template Keterangan — diubah lewat Master Data.</p>
    </div>
  </div>

  <!-- ==================== STEP 3: FORMULIR KONFIRMASI ==================== -->
  <div class="step-panel" id="panel-step-3" style="display:none;">
    <!-- B2B: Konfirmasi Company (link) — QTN-FR-31 -->
    <div class="card qtn-form" id="step3-b2b" style="display:none;">
      <div class="ket-tools"><span><b class="qtn-title">Konfirmasi Company (link)</b><br><span class="muted">Link B2B diisi satu kali oleh PIC company: data perusahaan dan PIC (terisi awal dari Master Company), daftar peserta, lalu surat konfirmasi company bertanda tangan dan cap. Tidak ada isian per orang.</span></span></div>
      <div class="field">
        <label>Isian tetap yang diisi PIC</label>
        <div class="kf-chips"><?php foreach (['Nama Perusahaan', 'Alamat Perusahaan', 'Nama PIC', 'Jabatan PIC', 'Nomor HP / WhatsApp PIC', 'Email PIC'] as $t): ?><span class="kf-chip on"><?= htmlspecialchars($t); ?></span><?php endforeach; ?></div>
      </div>
      <div class="field">
        <label>Kolom daftar peserta</label>
        <div class="kf-chips" id="wrap-kolom-b2b">
          <span class="kf-chip on" title="Selalu ada dan wajib">Nama Lengkap (wajib)</span>
          <?php $kolomCur = json_decode((string)($q['kolom_peserta_b2b'] ?? ''), true) ?: [];
          foreach (['jabatan' => 'Jabatan', 'email' => 'Email', 'wa' => 'Nomor WhatsApp', 'nik' => 'NIK'] as $k => $l): $on = in_array($k, $kolomCur, true); ?>
            <label class="kf-chip <?= $on ? 'on' : ''; ?>"><input type="checkbox" class="kolom-b2b" value="<?= $k; ?>" <?= $on ? 'checked' : ''; ?> onchange="this.parentElement.classList.toggle('on', this.checked)"> <?= $l; ?></label>
          <?php endforeach; ?>
        </div>
        <span class="hint">Default hanya Nama, karena company biasanya hanya mengirim nama. Centang kolom tambahan yang perlu diisi PIC.</span>
      </div>
      <p class="hint">Contoh surat konfirmasi company kosong dapat dicetak oleh PIC dari link. Format surat adalah usulan analis (kode dokumen menyusul, Q-QTN-22).</p>
    </div>

    <div class="card qtn-form" id="step3-public">
      <div class="ket-tools"><span><b class="qtn-title">Formulir Konfirmasi Keikutsertaan</b><br><span class="muted">Mengatur isian yang diminta di link Konfirmasi Kehadiran dan kalimat pernyataannya. Formulir tidak dicetak kosong: calon peserta mengisi lewat link, mencetak surat terisi, menandatangani, lalu mengunggahnya. Template default dari Jenis Produk paket.</span></span><button type="button" class="btn btn-ghost sm" onclick="kfDefault()">Kembalikan ke default</button></div>

      <div class="grid2">
        <div class="field">
          <label>Template Konfirmasi</label>
          <select class="input" id="id_template_konfirmasi" onchange="kfTouched = true; onKfTplChange()">
            <?php foreach ($kf_tpls as $kt): ?>
              <option value="<?= $kt['id']; ?>" 
                      data-isian='<?= htmlspecialchars($kt['isian'] ?? '[]'); ?>'
                      data-pernyataan='<?= htmlspecialchars($kt['pernyataan'] ?? ''); ?>'
                      data-produk="<?= (int)($kt['id_jenis_produk'] ?? 0); ?>"
                      <?= ($q['id_template_konfirmasi'] ?? '') == $kt['id'] ? 'selected' : ''; ?>>
                <?= htmlspecialchars($kt['nama']); ?> (<?= htmlspecialchars($kt['label_kegiatan']); ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="field">
        <label>Isian biodata peserta yang ditagih</label>
        <div class="kf-chips" id="wrap-kf-fields">
          <?php
            // Sumber tunggal 11 isian standar (QTN-FR-19, FR-51).
            $fieldList = array_map(function ($l) { return rtrim($l, '*'); }, Master_data_model::KF_FIELDS);
            foreach ($fieldList as $k => $l):
          ?>
            <label class="kf-chip <?= in_array($k, $kfIsian) ? 'on' : ''; ?>">
              <input type="checkbox" class="kf-check" value="<?= $k; ?>" onchange="this.parentElement.classList.toggle('on', this.checked)" <?= in_array($k, $kfIsian) ? 'checked' : ''; ?>> <?= $l; ?>
            </label>
          <?php endforeach; ?>
          <?php foreach ($kfIsian as $ex): if (isset($fieldList[$ex])) continue; ?>
            <label class="kf-chip on extra"><input type="checkbox" class="kf-check" value="<?= htmlspecialchars($ex); ?>" checked onchange="this.parentElement.classList.toggle('on', this.checked); kfTouched = true;"> <?= htmlspecialchars($ex); ?></label>
          <?php endforeach; ?>
        </div>
        <div class="ket-new" style="margin-top:10px;"><input class="input sm" id="kf-extra-in" placeholder="+ Tambah isian lain, lalu Enter" aria-label="Tambah isian lain" onkeydown="if(event.key==='Enter'){event.preventDefault();kfAddExtra();}"><button type="button" class="btn btn-ghost sm" onclick="kfAddExtra()">Tambah</button></div>
      </div>

      <div class="field">
        <label>Pernyataan konfirmasi kehadiran</label>
        <textarea class="input" id="konfirmasi_pernyataan" rows="3" oninput="kfTouched = true; kfPreview();"><?= htmlspecialchars($q['konfirmasi_pernyataan'] ?? ''); ?></textarea>
        <div class="hint" id="kf-preview"></div>
        <span class="hint">Placeholder seperti <code>{judul}</code>, <code>{tanggal}</code>, <code>{durasi}</code>, <code>{lokasi}</code> akan terisi otomatis saat dicetak.</span>
      </div>
    </div>
  </div>

  <!-- ==================== STEP 4: PREVIEW ==================== -->
  <div class="step-panel" id="panel-step-4" style="display:none;">
    <div class="card qtn-form">
      <div class="ket-tools">
        <span><span class="badge st-draft">Preview — belum disimpan</span> <span class="muted" id="pv-note">Periksa dokumen di bawah, lalu simpan atau kembali untuk mengubah.</span></span>
        <span id="pv-tabs"></span>
      </div>
      <p class="dash-empty" id="pv-msg"></p>
      <iframe id="pv-frame" class="pv-frame" title="Preview quotation"></iframe>
    </div>
  </div>

  <!-- NAVIGATION & ACTION BUTTONS -->
  <div class="qtn-actions">
    <div class="qa-left">
      <button type="button" class="btn btn-muted" id="btn-prev-step" onclick="prevStep()" style="display:none;">&larr; Kembali</button>
      <a href="<?= site_url('quotation_program'); ?>" class="btn btn-ghost" id="btn-cancel-qtn">Batal</a>
    </div>
    <div class="qa-right">
      <button type="button" class="btn btn-outline" id="btn-next-step" onclick="nextStep()">Lanjut &rarr;</button>
      <button type="button" class="btn btn-primary" id="btn-save-qtn" onclick="submitQtn('draft')" style="display:none;"><?= $isEdit ? 'Simpan Perubahan' : 'Simpan Draft'; ?></button>
    </div>
  </div>
</form>

<script>
// Data Master Referensi
const KUOTA_LAIN = <?= json_encode($kuotaLain); ?>;   // {id_penjualan: jumlah kuota quotation lain (selain Batal)}
const N_TERISI = <?= (int)($nTerisi ?? 0); ?>;       // peserta yang sudah mengisi lewat link (batas bawah kuota saat edit)
const B2B_MAKS = <?= (int)($q['paket_maks_peserta'] ?: Quotation_model::B2B_PAKET_MAKS); ?>, B2B_TARIF = <?= (float)($q['tambahan_per_peserta'] ?: Quotation_model::B2B_TAMBAHAN); ?>;
let beTouched = <?= $isEdit ? 'true' : 'false'; ?>;
const BEP_HARGA = <?= json_encode($bepHarga); ?>;   // {id_penjualan: harga BEP / peserta}
const KET_MASTER = <?= json_encode($ketMaster); ?>;
const KET_SAVED = <?= json_encode($isEdit ? ($q['keterangan'] ?? []) : null); ?>;
const B2B_MIN = 10;
const INITIAL_KETERANGAN = <?= json_encode($q['keterangan'] ?? []); ?>;

let currentStep = 1;

document.addEventListener('DOMContentLoaded', function() {
  onPenjualanChange(true);
  updateKuotaInfo();
  ketInit();
  kfPreview();
  calcQtnValue();
});

function isB2bPj() {
  const o = pjOpt();
  return !!o && o.dataset.jenis === 'b2b';
}

// Empat langkah untuk Public dan B2B; langkah 3 B2B = Konfirmasi Company (QTN-FR-18, QTN-FR-31).
function stepOrder() { return [1, 2, 3, 4]; }

function goToStep(step) {
  // Langkah 2-4 hanya terbuka bila langkah 1 valid.
  if (step > 1) {
    const err = validateStep1();
    if (err) { lmWarn(err, { title: 'Data belum lengkap' }); step = 1; }
  }
  if (!stepOrder().includes(step)) step = 4;
  currentStep = step;
  const order = stepOrder();
  document.querySelectorAll('.step-panel').forEach(p => p.style.display = 'none');
  document.querySelectorAll('.step-btn').forEach(b => {
    const n = parseInt(b.dataset.step);
    b.parentElement.classList.toggle('on', n === step);
    b.parentElement.classList.toggle('done', n < step);
  });
  document.getElementById('pv-no').textContent = order.length;
  document.getElementById('panel-step-' + step).style.display = 'block';

  const idx = order.indexOf(step);
  document.getElementById('btn-prev-step').style.display = idx > 0 ? 'inline-flex' : 'none';
  document.getElementById('btn-cancel-qtn').style.display = idx === 0 ? 'inline-flex' : 'none';
  document.getElementById('btn-next-step').style.display = step < 4 ? 'inline-flex' : 'none';
  document.getElementById('btn-save-qtn').style.display = step === 4 ? 'inline-flex' : 'none';
  if (step === 4) loadPreview();
}

function nextStep() {
  const order = stepOrder();
  goToStep(order[Math.min(order.indexOf(currentStep) + 1, order.length - 1)]);
}

function prevStep() {
  const order = stepOrder();
  goToStep(order[Math.max(order.indexOf(currentStep) - 1, 0)]);
}

// ----------------- LANGKAH 1: VALIDASI (QTN-FR-04 – 08, 20, 21) -----------------
function validateStep1() {
  if (!document.getElementById('id_penjualan').value) return 'Penjualan Program wajib dipilih';
  if (!document.getElementById('tanggal').value) return 'Tanggal quotation wajib diisi';
  if (!document.getElementById('id_sales').value) return 'Sales / marketing wajib dipilih';
  const company = document.getElementById('tujuan_company').checked;
  if (company && !document.getElementById('id_customer').value) return 'Company wajib dipilih';
  if (!company && !document.getElementById('kepada_nama').value.trim()) return 'Kepada wajib diisi';
  const n = kuota();
  if (n < 1) return 'Kuota peserta minimal 1';
  if (isB2bPj() && n < B2B_MIN) return `Penjualan B2B: Kuota peserta minimal ${B2B_MIN}`;
  if (N_TERISI && n < N_TERISI) return `Kuota tidak boleh kurang dari ${N_TERISI} peserta yang sudah mengisi`;
  const allin = isB2bPj();
  const diskon = parseMoney(document.getElementById('diskon').value);
  if (allin) {
    const h = parseMoney(document.getElementById('harga_allin').value);
    if (h <= 0) return 'Harga all-in wajib diisi (> 0)';
    if (diskon > h) return 'Diskon tidak boleh melebihi harga all-in';
  } else if (diskon > parseMoney(document.getElementById('harga_peserta').value)) {
    return 'Diskon per peserta tidak boleh melebihi harga per peserta';
  }
  return '';
}

// ----------------- LANGKAH 4: PREVIEW (QTN-FR-18) -----------------
let pvUrl = null;

function loadPreview() {
  const msg = document.getElementById('pv-msg'), frame = document.getElementById('pv-frame'), tabs = document.getElementById('pv-tabs');
  msg.textContent = 'Memuat preview…'; msg.style.display = 'block'; frame.style.display = 'none'; tabs.innerHTML = '';
  fetch('<?= site_url("quotation_program/preview"); ?>', { method: 'POST', body: toBody(collectPayload('draft')) })
    .then(r => (r.headers.get('Content-Type') || '').indexOf('application/pdf') === 0 ? r.blob() : r.json().then(j => { throw new Error(j.msg || 'Preview gagal dimuat'); }))
    .then(blob => {
      if (pvUrl) URL.revokeObjectURL(pvUrl);
      pvUrl = URL.createObjectURL(blob);
      frame.src = pvUrl + '#toolbar=0&navpanes=0&scrollbar=1'; // sembunyikan toolbar viewer (cetak/unduh); shortcut shortcut: Ctrl+P browser tetap bisa
      msg.style.display = 'none'; frame.style.display = 'block';
    })
    .catch(e => { msg.textContent = e.message || 'Terjadi kesalahan jaringan'; });
}

function onPenjualanChange(isInit = false) {
  const sel = document.getElementById('id_penjualan');
  const opt = sel.options[sel.selectedIndex];
  const infoBox = document.getElementById('pj-info-box');
  const step3Btn = document.getElementById('step3-btn');
  const wrapSkema = document.getElementById('wrap-skema');
  const wrapCompany = document.getElementById('wrap-company');

  if (!opt || !opt.value) {
    infoBox.textContent = '';
    return;
  }

  const isB2b = opt.dataset.jenis === 'b2b';
  infoBox.textContent = `${isB2b ? 'B2B (In-House)' : 'Public'} · Lokasi: ${opt.dataset.lokasi} · Jadwal: ${opt.dataset.tgl} · Target: ${opt.dataset.target} pax`;

  // B2B: tujuan Company terkunci, hanya all-in (QTN-FR-05, QTN-FR-20)
  if (isB2b) {
    document.getElementById('tujuan_company').checked = true;
    document.getElementById('tujuan_personal').disabled = true;
    wrapSkema.style.display = 'block';
    onTujuanChange();
    document.getElementById('step3-label').textContent = 'Konfirmasi Company (link)';
    document.getElementById('step3-b2b').style.display = 'block'; document.getElementById('step3-public').style.display = 'none';
    document.getElementById('kuota_peserta').min = B2B_MIN;
    if (!document.getElementById('kuota_peserta').value) document.getElementById('kuota_peserta').value = B2B_MIN; // terisi awal 10 (QTN-FR-22)

    if (opt.dataset.customer) {
      document.getElementById('id_customer').value = opt.dataset.customer;
      document.getElementById('id_customer').disabled = true;
      onCompanyChange();
    }
  } else {
    document.getElementById('tujuan_personal').disabled = false;
    document.getElementById('step3-label').textContent = 'Formulir Konfirmasi';
    document.getElementById('step3-b2b').style.display = 'none'; document.getElementById('step3-public').style.display = 'block';
    wrapSkema.style.display = 'none';
    document.getElementById('id_customer').disabled = false;
    document.getElementById('kuota_peserta').min = 1;
  }

  if (!isInit) {
    if (!ketTouched) { ketState = ketBuild(null); ketRender(); }
    if (!kfTouched) kfDefault(); else kfPreview();
    document.getElementById('harga_peserta').value = formatMoney(parseFloat(opt.dataset.harga) || 0);
  }

  updateKuotaInfo();
  calcQtnValue();
}

function onTujuanChange() {
  const isCompany = document.getElementById('tujuan_company').checked;
  document.getElementById('wrap-company').style.display = isCompany ? 'block' : 'none';
  document.getElementById('wrap-up').style.display = isCompany ? 'block' : 'none';
  document.getElementById('wrap-kepada').style.display = isCompany ? 'none' : 'block';
}

function onCompanyChange() {
  const sel = document.getElementById('id_customer');
  const opt = sel.options[sel.selectedIndex];
  if (opt && opt.value) {
    const alamat = (opt.dataset.alamat || '') + (opt.dataset.kota ? ', ' + opt.dataset.kota : '');
    document.getElementById('alamat').value = alamat;
  }
}

function pjOpt() {
  const sel = document.getElementById('id_penjualan');
  return sel.options[sel.selectedIndex] && sel.options[sel.selectedIndex].value ? sel.options[sel.selectedIndex] : null;
}

const kuota = () => parseInt(document.getElementById('kuota_peserta').value, 10) || 0;

// Info kuota (QTN-FR-22): target program, kuota di quotation lain, sisa target. Kuota boleh melebihi sisa target (tidak dicegah).
function updateKuotaInfo() {
  const opt = pjOpt(), box = document.getElementById('kuota-info');
  document.getElementById('kuota-min-hint').textContent = (isB2bPj() ? `B2B minimal ${B2B_MIN}` : 'Minimal 1') + (N_TERISI ? ` · ${N_TERISI} peserta sudah mengisi` : '');
  document.getElementById('kuota-b2b-note').textContent = isB2bPj() ? 'B2B: kuota adalah perkiraan untuk harga penawaran dan tidak menutup link; jumlah sebenarnya = daftar yang dikirim PIC company.' : '';
  if (!opt) { box.innerHTML = '<span class="muted">Pilih Penjualan Program dahulu.</span>'; return; }
  const target = parseInt(opt.dataset.target, 10) || 0, lain = KUOTA_LAIN[opt.value] || 0, sisa = target - lain;
  box.innerHTML = `<div class="sum-row"><span>Target program</span><b>${target} peserta</b></div><div class="sum-row"><span>Di quotation lain</span><b>${lain}</b></div>` +
    `<div class="sum-row"><span>Sisa target</span><b>${sisa}</b></div>`;
}

function calcQtnValue() {
  const count = kuota();
  const isAllin = isB2bPj(); // B2B hanya all-in; Public per peserta

  document.getElementById('wrap-harga-allin').style.display = isAllin ? 'block' : 'none';
  document.getElementById('wrap-harga-peserta').style.display = isAllin ? 'none' : 'block';
  document.getElementById('lbl-diskon').textContent = isAllin ? 'Diskon Total (Rp)' : 'Diskon (Rp / peserta)';

  const hargaPeserta = parseMoney(document.getElementById('harga_peserta').value);
  let hargaAllin = parseMoney(document.getElementById('harga_allin').value);
  const diskon = parseMoney(document.getElementById('diskon').value);
  const biayaLain = parseMoney(document.getElementById('biaya_lain').value);
  const isPpn = document.getElementById('is_ppn').checked;
  const ppnRate = parseFloat(document.getElementById('ppn_persen').value) || 11;

  let subtotal = 0;
  let totalDiskon = 0;
  let tambahan = 0, nTambahan = 0;

  if (isAllin) {
    if (hargaAllin === 0 && hargaPeserta > 0 && count > 0) {
      hargaAllin = hargaPeserta * Math.min(count, B2B_MAKS); // terisi awal harga/peserta x kuota (maks. 25)
      document.getElementById('harga_allin').value = formatMoney(hargaAllin);
    }
    nTambahan = Math.max(count - B2B_MAKS, 0);
    tambahan = nTambahan * B2B_TARIF; // ikut DPP dan PPN; diskon hanya dari harga paket
    subtotal = hargaAllin + tambahan;
    totalDiskon = diskon;
  } else {
    subtotal = hargaPeserta * count;
    totalDiskon = diskon * count;
  }

  const dpp = Math.max(0, subtotal - totalDiskon);
  const ppnNominal = isPpn ? Math.round(dpp * (ppnRate / 100)) : 0;
  const grandTotal = dpp + ppnNominal + biayaLain;
  const netPerPax = count > 0 ? Math.round(dpp / count) : 0;

  document.getElementById('row-allin').style.display = isAllin ? 'flex' : 'none';
  document.getElementById('row-tambahan').style.display = isAllin ? 'flex' : 'none';
  document.getElementById('tot-allin').textContent = 'Rp ' + formatMoney(hargaAllin);
  document.getElementById('lbl-tambahan').textContent = nTambahan ? `Tambahan peserta (${nTambahan} peserta di atas ${B2B_MAKS} × Rp ${formatMoney(B2B_TARIF)})` : 'Tambahan peserta (tidak ada)';
  document.getElementById('tot-tambahan').textContent = 'Rp ' + formatMoney(tambahan);
  document.getElementById('tot-subtotal').textContent = 'Rp ' + formatMoney(subtotal);
  document.getElementById('tot-diskon').textContent = '- Rp ' + formatMoney(totalDiskon);
  document.getElementById('tot-dpp').textContent = 'Rp ' + formatMoney(dpp);
  document.getElementById('tot-ppn').textContent = 'Rp ' + formatMoney(ppnNominal);
  document.getElementById('tot-lain').textContent = 'Rp ' + formatMoney(biayaLain);
  document.getElementById('tot-grand').textContent = 'Rp ' + formatMoney(grandTotal);
  const opt = pjOpt();
  const bep = opt ? (BEP_HARGA[opt.value] || 0) : 0;
  const low = bep > 0 && count > 0 && netPerPax < bep && (isAllin || diskon > 0);
  const net = document.getElementById('tot-net-pax');
  net.textContent = `Rp ${formatMoney(netPerPax)}` + (low ? ` (di bawah BEP harga Rp ${formatMoney(Math.round(bep))})` : '');
  net.className = low ? 'neg' : 'muted';
}

// ----------------- STEP 2: KETERANGAN (QTN-FR-17) -----------------
const KET_KAT = [['syarat', 'Syarat keikutsertaan'], ['termasuk', 'Termasuk'], ['tidak', 'Tidak termasuk'], ['dokumen', 'Dokumen persyaratan']];
let ketState = null, ketTouched = false, ketEdit = null;
const ketOpen = {};

function ketBuild(saved) {
  const opt = pjOpt();
  if (!opt) return null;
  const jenis = opt.dataset.jenis;
  const st = {};
  KET_KAT.forEach(([k]) => {
    const master = KET_MASTER.filter(r => r.kategori === k && (r.berlaku_untuk === 'semua' || r.berlaku_untuk === jenis));
    const cur = saved ? saved.filter(r => r.kategori === k).map(r => r.teks) : null;
    const items = master.map(r => ({ id: r.id, teks: r.teks, on: cur ? cur.includes(r.teks) : r.is_default == 1 }));
    (cur || []).filter(t => !master.some(r => r.teks === t)).forEach(t => items.push({ id: null, teks: t, on: true }));
    st[k] = items;
  });
  return st;
}

function ketRender() {
  const root = document.getElementById('ket-root');
  if (!ketState) {
    root.innerHTML = '<p class="dash-empty">Pilih Penjualan Program di langkah 1 dahulu — butir diisi sesuai jenis penjualan (Public / B2B).</p>';
    return;
  }
  root.innerHTML = KET_KAT.map(([k, label]) => {
    const list = ketState[k], on = list.filter(x => x.on).length;
    const rows = list.map((x, i) => ketEdit === k + ':' + i
      ? `<div class="ket-item editing"><input class="input sm" data-ket-edit="${k}:${i}" value="${escapeHtml(x.teks)}" aria-label="Ubah teks">
          <button type="button" class="btn btn-primary sm" data-act="edit-ok" data-k="${k}" data-i="${i}">Simpan</button><button type="button" class="btn btn-ghost sm" data-act="edit-cancel">Batal</button></div>`
      : `<div class="ket-item ${x.on ? '' : 'off'}"><label><input type="checkbox" data-act="toggle" data-k="${k}" data-i="${i}" ${x.on ? 'checked' : ''}> <span>${escapeHtml(x.teks)}</span>${x.id ? '' : ' <span class="tag tag-manual">khusus quotation ini</span>'}</label>
          <button type="button" class="ket-pen" data-act="edit" data-k="${k}" data-i="${i}" title="Ubah teks untuk quotation ini" aria-label="Ubah teks">✎</button></div>`).join('');
    return `<details class="ket-acc" data-k="${k}" ${ketOpen[k] === false ? '' : 'open'}><summary><b>${label}</b><span class="muted">${on} dari ${list.length} dipilih</span></summary>
      <div class="ket-list">${rows || '<p class="dash-empty">Belum ada butir di master untuk kategori ini.</p>'}
        <div class="ket-new"><input class="input sm" data-ket-new="${k}" placeholder="+ Tambah teks lain, lalu Enter" aria-label="Tambah teks ${label}"><button type="button" class="btn btn-ghost sm" data-act="add" data-k="${k}">Tambah</button></div></div></details>`;
  }).join('');
}

function ketReset() { ketTouched = false; ketEdit = null; ketState = ketBuild(null); ketRender(); }

function ketInit() {
  ketState = ketBuild(KET_SAVED);
  ketRender();
  const root = document.getElementById('ket-root');
  root.addEventListener('toggle', e => { if (e.target.matches('details.ket-acc')) ketOpen[e.target.dataset.k] = e.target.open; }, true);
  root.addEventListener('click', e => {
    const b = e.target.closest('[data-act]');
    if (!b) return;
    const k = b.dataset.k, i = Number(b.dataset.i), act = b.dataset.act;
    if (act === 'toggle') { ketState[k][i].on = b.checked; ketTouched = true; }
    else if (act === 'edit') ketEdit = k + ':' + i;
    else if (act === 'edit-cancel') ketEdit = null;
    else if (act === 'edit-ok') {
      const v = root.querySelector(`[data-ket-edit="${k}:${i}"]`).value.trim();
      if (v) { Object.assign(ketState[k][i], { teks: v, id: null }); ketTouched = true; }
      ketEdit = null;
    } else if (act === 'add') { ketAdd(k); return; }
    ketRender();
  });
  root.addEventListener('keydown', e => {
    if (e.key !== 'Enter') return;
    if (e.target.dataset.ketNew) { e.preventDefault(); ketAdd(e.target.dataset.ketNew); }
    else if (e.target.dataset.ketEdit) { e.preventDefault(); e.target.parentElement.querySelector('[data-act="edit-ok"]').click(); }
  });
}

function ketAdd(k) {
  const input = document.querySelector(`[data-ket-new="${k}"]`);
  const v = input.value.trim();
  if (!v) return;
  ketState[k].push({ id: null, teks: v, on: true });
  ketTouched = true;
  ketRender();
  document.querySelector(`[data-ket-new="${k}"]`).focus();
}

// ----------------- STEP 3: FORMULIR KONFIRMASI (QTN-FR-19) -----------------
let kfTouched = false;
const KF_STD = <?= json_encode(array_keys($fieldList)); ?>;

function kfChip(value, label, checked, extra) {
  const l = document.createElement('label');
  l.className = 'kf-chip' + (checked ? ' on' : '') + (extra ? ' extra' : '');
  l.innerHTML = `<input type="checkbox" class="kf-check" value="${escapeHtml(value)}" ${checked ? 'checked' : ''}> ${escapeHtml(label)}`;
  l.querySelector('input').addEventListener('change', e => { l.classList.toggle('on', e.target.checked); kfTouched = true; kfPreview(); });
  return l;
}

function kfSetIsian(list) {
  const wrap = document.getElementById('wrap-kf-fields');
  wrap.querySelectorAll('.kf-chip.extra').forEach(c => c.remove());
  wrap.querySelectorAll('.kf-check').forEach(c => { c.checked = list.includes(c.value); c.parentElement.classList.toggle('on', c.checked); });
  list.filter(v => !KF_STD.includes(v)).forEach(v => wrap.appendChild(kfChip(v, v, true, true)));
}

function kfAddExtra() {
  const input = document.getElementById('kf-extra-in');
  const v = input.value.trim();
  if (!v) return;
  const dup = Array.from(document.querySelectorAll('.kf-check')).some(c => c.value.toLowerCase() === v.toLowerCase());
  if (!dup) document.getElementById('wrap-kf-fields').appendChild(kfChip(v, v, true, true));
  input.value = '';
  kfTouched = true;
}

function onKfTplChange() {
  const sel = document.getElementById('id_template_konfirmasi');
  const opt = sel.options[sel.selectedIndex];
  if (!opt) return;
  let isian = [];
  try { isian = JSON.parse(opt.dataset.isian || '[]'); } catch (e) {}
  kfSetIsian(isian);
  document.getElementById('konfirmasi_pernyataan').value = opt.dataset.pernyataan || '';
  kfPreview();
}

// Template default dari Jenis Produk paket; bila tidak ada, template pertama.
function kfDefault() {
  const sel = document.getElementById('id_template_konfirmasi');
  if (!sel) return;
  const pj = pjOpt();
  const match = pj && Array.from(sel.options).find(o => o.dataset.produk && o.dataset.produk === pj.dataset.produk);
  sel.value = (match || sel.options[0] || {}).value || '';
  Array.from(sel.options).forEach(o => { if (o.dataset.t === undefined) o.dataset.t = o.textContent.trim(); o.textContent = o.dataset.t + (o === match ? ' — default' : ''); });
  kfTouched = false;
  onKfTplChange();
}

function kfPreview() {
  const box = document.getElementById('kf-preview');
  if (!box) return;
  const o = pjOpt();
  const raw = document.getElementById('konfirmasi_pernyataan').value;
  if (!o || !raw) { box.textContent = ''; return; }
  const txt = raw.replace(/\{judul\}/g, o.dataset.judul || '').replace(/\{tanggal\}/g, o.dataset.tgl || '').replace(/\{waktu\}/g, o.dataset.waktu || '')
    .replace(/\{durasi\}/g, (o.dataset.durasi || '') + ' hari').replace(/\{lokasi\}/g, o.dataset.lokasi || '');
  box.innerHTML = '<span class="muted">Tercetak:</span> ' + escapeHtml(txt).replace(/\*\*(.*?)\*\*/g, '<b>$1</b>');
}

function onTanggalChange() {
  const t = document.getElementById('tanggal').value, b = document.getElementById('berlaku_sampai');
  if (!t) return;
  // Default 31 Desember tahun tanggal quotation; ikut pindah tahun selama belum diubah manual (QTN-FR-04).
  if (!beTouched || !b.value || b.value < t) { b.value = t.slice(0, 4) + '-12-31'; beTouched = false; }
}

// ----------------- SUBMIT QUOTATION -----------------
function collectPayload(statusAction) {
  const keterangan = [];
  Object.keys(ketState || {}).forEach(k => ketState[k].forEach(x => {
    if (x.on && x.teks.trim()) keterangan.push({ kategori: k, teks: x.teks.trim(), id_template_keterangan: x.id || '' });
  }));

  const kfIsian = [];
  document.querySelectorAll('.kf-check:checked').forEach(c => kfIsian.push(c.value));

  return {
    id: document.getElementById('qtn-id').value,
    id_penjualan: document.getElementById('id_penjualan').value,
    nomor: document.getElementById('nomor').value,
    tanggal: document.getElementById('tanggal').value,
    berlaku_sampai: document.getElementById('berlaku_sampai').value,
    id_sales: document.getElementById('id_sales').value,
    sumber_info: document.getElementById('sumber_info').value,
    tujuan: document.querySelector('input[name="tujuan"]:checked')?.value || 'personal',
    id_customer: document.getElementById('id_customer').value || '',
    up_nama: document.getElementById('up_nama').value,
    kepada_nama: document.getElementById('kepada_nama').value,
    kuota_peserta: document.getElementById('kuota_peserta').value,
    alamat: document.getElementById('alamat').value,
    skema_harga: isB2bPj() ? 'allin' : 'peserta',
    harga_peserta: document.getElementById('harga_peserta').value,
    harga_allin: document.getElementById('harga_allin').value,
    diskon: document.getElementById('diskon').value,
    is_ppn: document.getElementById('is_ppn').checked ? 1 : 0,
    ppn_persen: document.getElementById('ppn_persen').value,
    biaya_lain: document.getElementById('biaya_lain').value,
    id_template_konfirmasi: document.getElementById('id_template_konfirmasi')?.value || '',
    konfirmasi_isian: kfIsian,
    kolom_b2b: Array.from(document.querySelectorAll('.kolom-b2b:checked')).map(c => c.value),
    konfirmasi_pernyataan: document.getElementById('konfirmasi_pernyataan')?.value || '',
    status: statusAction,
    keterangan: keterangan
  };
}

function toBody(p) {
  const o = Object.assign({}, p, {
    keterangan_json: JSON.stringify(p.keterangan),
    konfirmasi_isian_json: JSON.stringify(p.konfirmasi_isian),
    kolom_b2b_json: JSON.stringify(p.kolom_b2b)
  });
  delete o.keterangan; delete o.konfirmasi_isian; delete o.kolom_b2b;
  return new URLSearchParams(o);
}

function submitQtn(statusAction) {
  const err = validateStep1();
  if (err) { lmWarn(err, { title: 'Data belum lengkap' }); goToStep(1); return; }

  fetch('<?= site_url("quotation_program/save"); ?>', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: toBody(collectPayload(statusAction))
  })
  .then(res => res.json())
  .then(res => {
    if (res.status === 1) {
      toast(res.msg, 'success');
      setTimeout(() => {
        window.location.href = '<?= site_url("quotation_program/view"); ?>/' + res.id; // detail: link dapat langsung disalin
      }, 800);
    } else {
      toast(res.msg, 'error');
    }
  })
  .catch(() => toast('Terjadi kesalahan jaringan', 'error'));
}

function parseMoney(val) {
  if (!val) return 0;
  return parseFloat(String(val).replace(/\./g, '')) || 0;
}

function formatMoney(num) {
  return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function escapeHtml(text) {
  return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}
</script>
