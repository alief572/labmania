<?php
$e   = function ($v) { return htmlspecialchars((string)$v); };
$rp  = function ($v) { return 'Rp ' . number_format((float)$v, 0, ',', '.'); };
$tgl = function ($d) { return $d ? date('d-m-Y', strtotime($d)) : '—'; };
$rng = function ($a, $b) use ($tgl) { return (!$b || $a === $b) ? $tgl($a) : $tgl($a) . ' s.d. ' . $tgl($b); };
$stLabel  = ['draft' => 'Draft', 'terkirim' => 'Terkirim', 'deal' => 'Deal', 'batal' => 'Batal'];
$jenisTag = function ($j) { return '<span class="tag tag-' . ($j === 'b2b' ? 'b2b' : 'public') . '">' . ($j === 'b2b' ? 'B2B' : 'Public') . '</span>'; };
$base = site_url('quotation_program/index/' . $tab);
$bepTxt = function ($bep, $deal) {
    if ($bep === null) return '<span class="neg">BEP tidak tercapai</span>';
    return $deal >= $bep ? '<span class="pos">Lewat BEP (' . $bep . ')</span>' : 'BEP ' . $bep . ', kurang ' . ($bep - $deal);
};
// Menu gear: item berupa tombol; labmania.js menampilkannya sebagai menu melayang.
$gear = function ($items) use ($e) {
    $h = '<button type="button" class="gear" data-gear aria-haspopup="menu" aria-label="Opsi"></button><span class="gear-src" hidden>';
    // Hak akses: tiap item hanya tampil bila aksinya diizinkan
    $aksiOf = function ($title) {
        if ($title === 'Edit') return 'ubah';
        if ($title === 'Deal' || $title === 'Batalkan') return 'aktif';
        if ($title === 'Kirim dan Cetak Quotation' || $title === 'Confirmation Letter' || $title === 'Cetak Quotation') return 'cetak';
        if ($title === '+ Buat Quotation') return 'tambah';
        return null;
    };
    foreach ($items as $it) {
        $aksi = $aksiOf($it[0]);
        if ($aksi && !lm_can('quotation_program', $aksi)) continue;
        $h .= '<button type="button" class="icon-btn" onclick="' . $e($it[1]) . '" title="' . $e($it[0]) . '"></button>';
    }
    return $h . '</span>';
};
$go = function ($path) { return "location.href='" . site_url($path) . "'"; };
?>

<?php if ($pj): ?>
  <?php
    $info = $jenisTag($pj['jenis']) . ' <b>' . $e($pj['nama_paket']) . '</b> · ' . $rng($pj['tgl_mulai'], $pj['tgl_selesai']) . ' · ' . $e($pj['lokasi'] ?: '—')
          . ' · ' . $rp($pj['harga_jual_peserta']) . ' / peserta' . ($pj['jenis'] === 'b2b' ? ' · ' . $e($pj['nama_company'] ?: '—') : '')
          . '<br>Peserta deal <b>' . (int)$deal . '</b> / ' . (int)$pj['target_peserta'] . ($pend ? ' · +' . (int)$pend . ' menunggu' : '') . ' · ' . $bepTxt($pj['bep'], $deal);
  ?>
  <div class="page-head">
    <div>
      <h1>Quotation — <?= $e($pj['nomor']); ?><span class="ref">QTN-FR-02</span></h1>
      <p><?= $info; ?></p>
    </div>
    <div class="page-actions">
      <a class="btn btn-ghost" href="<?= $base; ?>">← Semua Penjualan Program</a>
      <?php if ($canAdd): ?>
        <?php if (lm_can('quotation_program', 'tambah')): ?><a class="btn btn-accent" href="<?= site_url('quotation_program/create_from_pj/' . $pj['id']); ?>">+ Create Quotation</a><?php endif; ?>
      <?php else: ?>
        <button class="btn btn-accent" disabled title="Penjualan B2B sudah punya quotation aktif">+ Create Quotation</button>
      <?php endif; ?>
    </div>
  </div>

  <div class="card">
    <div class="toolbar">
      <div class="search">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input type="search" class="input" id="table-search" placeholder="Cari nomor, kepada, peserta, atau sales…" aria-label="Cari">
      </div>
      <select class="input sm" id="f-tujuan" aria-label="Filter tujuan" onchange="applyFilters()">
        <?php foreach (['all' => 'Semua tujuan', 'company' => 'Company', 'personal' => 'Personal'] as $v => $l): ?>
          <option value="<?= $v; ?>" <?= $tujuan === $v ? 'selected' : ''; ?>><?= $l; ?></option>
        <?php endforeach; ?>
      </select>
      <select class="input sm" id="f-status" aria-label="Filter status" onchange="applyFilters()">
        <option value="all">Semua status</option>
        <?php foreach ($stLabel as $v => $l): ?>
          <option value="<?= $v; ?>" <?= $status === $v ? 'selected' : ''; ?>><?= $l; ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="table-wrap">
      <table class="table" id="data-table" data-dt>
        <thead>
          <tr>
            <th style="width:45px;">No</th>
            <th>Quotation</th>
            <th>Kepada</th>
            <th>Peserta</th>
            <th class="money">Nilai</th>
            <th>Status</th>
            <th class="act" style="width:80px;">Action</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!empty($qs)): $no = 1; foreach ($qs as $q): $c = $q['calc'];
          $isB2b = $pj['jenis'] === 'b2b';
          $nDeal = count(array_filter($q['peserta'], function ($p) { return $p['status'] === 'deal'; }));
          $items = [];
          if (in_array($q['status'], ['draft', 'terkirim'])) {
              if ($q['status'] === 'draft') $items[] = ['Edit', $go('quotation_program/edit/' . $q['id'])];
              else $items[] = ['Deal', "openDeal({$q['id']})"];
              $items[] = ['Kirim dan Cetak Quotation', "sendPrint({$q['id']})"];
              $items[] = ['Batalkan', "updateQtnStatus({$q['id']}, 'batal', '" . addslashes($q['nomor']) . "')"];
          } elseif ($q['status'] === 'deal') {
              if (!$isB2b) $items[] = ['Confirmation Letter', "window.open('" . site_url('quotation_program/confirmation/' . $q['id']) . "')"];
              $items[] = ['Cetak Quotation', "sendPrint({$q['id']})"];
          } else {
              $items[] = ['Lihat', "sendPrint({$q['id']})"];
          }
        ?>
          <tr>
            <td><?= $no++; ?></td>
            <td class="nowrap">
              <a href="<?= site_url('quotation_program/view/' . $q['id']); ?>"><span class="code"><?= $e($q['nomor']); ?></span></a><br>
              <span class="muted"><?= $tgl($q['tanggal']); ?><?= $q['berlaku_sampai'] ? ' s.d. ' . $tgl($q['berlaku_sampai']) : ''; ?><br>Sales: <?= $e($q['nama_sales'] ?: '—'); ?><?= $q['sumber_info'] ? ', ' . $e($q['sumber_info']) : ''; ?></span>
            </td>
            <td>
              <span class="tag tag-<?= $q['tujuan'] === 'company' ? 'b2b' : 'public'; ?>"><?= $q['tujuan'] === 'company' ? 'Company' : 'Personal'; ?></span>
              <?= $e($q['tujuan'] === 'company' ? ($q['nama_company'] ?: '—') : ($q['peserta'][0]['nama_peserta'] ?? '—')); ?>
              <?= $q['up_nama'] ? '<br><span class="muted">u.p. ' . $e($q['up_nama']) . '</span>' : ''; ?>
            </td>
            <td>
              <b><?= count($q['peserta']); ?></b> <span class="muted">peserta</span><br>
              <?php foreach ($q['peserta'] as $ps):
                $st = $q['status'] === 'deal' ? $ps['status'] : ($q['status'] === 'batal' ? 'batal' : ($q['status'] === 'terkirim' ? 'menunggu' : 'draft')); ?>
                <span class="ps-chip ps-<?= $st; ?>" title="<?= $e($ps['instansi'] ?? ''); ?>"><?= $e($ps['nama_peserta'] ?? '—'); ?> <span class="muted">· <?= $e($ps['instansi'] ?: '—'); ?></span></span>
              <?php endforeach; ?>
            </td>
            <td class="money">
              <?= $rp($c['grand_total']); ?><br>
              <span class="muted">
                <?= $c['skema'] === 'allin'
                    ? 'All-in ' . $rp($c['harga_allin']) . ($c['total_diskon'] ? ' − diskon ' . $rp($c['total_diskon']) : '') . ' (≈ ' . $rp($c['net_per_pax']) . ' / peserta)'
                    : $rp($c['net_per_pax']) . ' / peserta' . ($c['diskon'] ? ' (diskon ' . $rp($c['diskon']) . ')' : ''); ?>
                <?= $c['is_ppn'] ? ' + PPN ' . (float)$c['ppn_persen'] . '%' : ''; ?>
              </span>
            </td>
            <td class="nowrap">
              <span class="badge st-<?= $q['status']; ?>"><?= $stLabel[$q['status']]; ?></span>
              <?= $q['status'] === 'deal' ? '<br><span class="muted">' . $nDeal . ' dari ' . count($q['peserta']) . ' peserta deal</span>' : ''; ?>
            </td>
            <td class="act"><?= $gear($items); ?></td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="7" class="empty">Belum ada quotation untuk Penjualan Program ini.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

<?php else: ?>
  <div class="page-head">
    <div>
      <h1>Daftar Quotation<span class="ref">QTN-FR-01 – QTN-FR-13</span></h1>
      <p>Penjualan Program Final — Public maupun B2B — dibuatkan quotation sebagai persiapan invoice. Satu baris per Penjualan Program; Public dapat dibuatkan quotation berkali-kali. Klik <b>Lihat detail</b> untuk quotation-nya.</p>
    </div>
    <div class="page-actions"><?php if (lm_can('quotation_program', 'tambah')): ?><a class="btn btn-accent" href="<?= site_url('quotation_program/add'); ?>">+ Buat Quotation</a><?php endif; ?></div>
  </div>

  <?php
    $conv = ($pipe['deal']['n'] + $pipe['batal']['n']) ? round($pipe['deal']['n'] / ($pipe['deal']['n'] + $pipe['batal']['n']) * 100) : null;
    $card = function ($s, $cls, $sub) use ($pipe, $status, $base, $stLabel) {
        $href = $base . ($status === $s ? '' : '?status=' . $s);
        return '<a class="kpi dash-link ' . $cls . ' ' . ($status === $s ? 'on' : '') . '" href="' . $href . '" title="Saring menurut status" style="text-decoration:none;color:inherit;">'
             . '<div class="kpi-l">' . $stLabel[$s] . '</div><div class="kpi-v">' . $pipe[$s]['n'] . ' <small>quotation</small></div><div class="kpi-s">' . $sub . '</div></a>';
    };
  ?>
  <div class="qtn-flow">
    <?= $card('draft', '', $pipe['draft']['p'] . ' peserta, belum dikirim'); ?><span class="qf-arrow">›</span>
    <?= $card('terkirim', 'kwarn', $pipe['terkirim']['p'] . ' peserta, menunggu deal'); ?><span class="qf-arrow">›</span>
    <?= $card('deal', 'good', $pipe['deal']['dealPs'] . ' peserta deal · ' . $rp($pipe['deal']['nilai']) . ($conv !== null ? '<br>tingkat deal ' . $conv . '%' : '')); ?><span class="qf-gap"></span>
    <?= $card('batal', 'lost', $pipe['batal']['p'] . ' peserta'); ?>
  </div>

  <div class="card">
    <div class="tabs">
      <a class="tab <?= $tab === 'ongoing' ? 'active' : ''; ?>" href="<?= site_url('quotation_program/index/ongoing'); ?>">On going<span class="nav-count"><?= (int)$tabCount['ongoing']; ?></span></a>
      <a class="tab <?= $tab === 'done' ? 'active' : ''; ?>" href="<?= site_url('quotation_program/index/done'); ?>">Selesai<span class="nav-count"><?= (int)$tabCount['done']; ?></span></a>
    </div>
    <div class="toolbar">
      <div class="search">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round"><circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/></svg>
        <input type="search" class="input" id="table-search" placeholder="Cari program, nomor quotation, kepada, atau peserta…" aria-label="Cari">
      </div>
    </div>
    <div class="table-wrap">
      <table class="table" id="data-table" data-dt>
        <thead>
          <tr>
            <th style="width:45px;">No</th>
            <th>Penjualan Program</th>
            <th class="money">Peserta deal</th>
            <th>Progres</th>
            <th>Quotation</th>
            <th class="act" style="width:80px;">Action</th>
          </tr>
        </thead>
        <tbody>
        <?php if (!empty($rows)): $no = 1; foreach ($rows as $r):
          $target = max((int)$r['target_peserta'], 1);
          $pct = function ($v) use ($target) { return number_format(min(100, $v / $target * 100), 1, '.', ''); };
          $cnt = [];
          foreach ($stLabel as $s => $l) { $n = count(array_filter($r['qs'], function ($q) use ($s) { return $q['status'] === $s; })); if ($n) $cnt[] = $n . ' ' . strtolower($l); }
          $items = [['Lihat detail', $go('quotation_program/index/' . $tab . '?pj=' . $r['id'])]];
          if ($r['canAdd']) $items[] = ['+ Buat Quotation', $go('quotation_program/create_from_pj/' . $r['id'])];
          $search = implode(' ', array_merge([$r['nomor'], $r['nama_paket'], $r['lokasi']], array_map(function ($q) {
              return $q['nomor'] . ' ' . ($q['nama_company'] ?? '') . ' ' . ($q['up_nama'] ?? '') . ' ' . implode(' ', array_column($q['peserta'], 'nama_peserta'));
          }, $r['qs'])));
        ?>
          <tr>
            <td><?= $no++; ?></td>
            <td class="title">
              <span class="code"><?= $e($r['nomor']); ?></span> <?= $jenisTag($r['jenis']); ?><br>
              <?= $e($r['nama_paket']); ?><br>
              <span class="muted"><?= $rng($r['tgl_mulai'], $r['tgl_selesai']); ?> · <?= $e($r['lokasi'] ?: '—'); ?></span>
              <span hidden><?= $e($search); ?></span>
            </td>
            <td class="money"><b><?= (int)$r['deal']; ?></b> / <?= (int)$r['target_peserta']; ?><?= $r['pend'] ? '<br><span class="muted">+' . (int)$r['pend'] . ' menunggu</span>' : ''; ?></td>
            <td>
              <div class="qbar"><div class="qb-fill"><span class="qb-deal" style="width:<?= $pct($r['deal']); ?>%"></span><span class="qb-pend" style="width:<?= $pct($r['pend']); ?>%"></span></div>
                <?php if ($r['bep'] !== null): ?><i class="qb-bep" style="left:<?= $pct($r['bep']); ?>%" title="BEP <?= (int)$r['bep']; ?>"></i><?php endif; ?></div>
              <span class="muted"><?= $bepTxt($r['bep'], $r['deal']); ?></span>
            </td>
            <td><b><?= count($r['qs']); ?></b> quotation<br><span class="muted"><?= $cnt ? $e(implode(', ', $cnt)) : 'belum ada'; ?></span></td>
            <td class="act"><?= $gear($items); ?></td>
          </tr>
        <?php endforeach; else: ?>
          <tr><td colspan="6" class="empty">Belum ada Penjualan Program Final pada tab ini.</td></tr>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
<?php endif; ?>

<script>
<?php if ($pj): ?>
<?php $tplNama = array_column(get_instance()->db->get('ms_template_konfirmasi')->result_array(), 'nama', 'id'); ?>
var QTN = <?= json_encode(array_column(array_map(function ($q) use ($pj, $tplNama) {
    $n = $q['status'] === 'deal' ? count(array_filter($q['peserta'], function ($p) { return $p['status'] === 'deal'; })) : count($q['peserta']);
    $note = 'Penawaran (1 hlm) + Syarat & Ketentuan (2 hlm)' . ($pj['jenis'] === 'b2b' ? ' — B2B / in-house tanpa Formulir Konfirmasi' : ' + Formulir Konfirmasi ' . ($tplNama[$q['id_template_konfirmasi']] ?? '') . ' (' . $n . ' peserta)');
    return ['id' => (int)$q['id'], 'nomor' => $q['nomor'], 'b2b' => $pj['jenis'] === 'b2b', 'status' => $q['status'], 'note' => $note,
            'peserta' => array_map(function ($p) { return ['id' => (int)$p['id_peserta'], 'nama' => $p['nama_peserta'], 'instansi' => $p['instansi']]; }, $q['peserta'])];
}, $qs), null, 'id')); ?>;
<?php endif; ?>
var B2B_MIN = 10;

function esc(t) { return String(t == null ? '' : t).replace(/[&<>"']/g, function (c) { return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }

// Deal: pilih peserta yang deal (QTN-FR-11); yang tidak dicentang menjadi Batal.
function openDeal(id) {
  var k = QTN[id]; if (!k) return;
  var rows = k.peserta.map(function (p) {
    return '<label class="ps-row"><input type="checkbox" name="peserta[]" value="' + p.id + '" checked> <span><b>' + esc(p.nama) + '</b><br><span class="muted">' + esc(p.instansi || '—') + '</span></span></label>';
  }).join('');
  openModal('<div class="overlay" onclick="if(event.target===this)closeModal()"><form class="modal" id="deal-form" onsubmit="return submitDeal(event,' + id + ')">' +
    '<header><h2>Deal — ' + esc(k.nomor) + '</h2><button type="button" class="x" onclick="closeModal()" aria-label="Tutup">×</button></header>' +
    '<div class="modal-body"><p class="muted">Centang peserta yang deal. Peserta yang tidak dicentang menjadi Batal dan dapat dipakai di quotation lain.' + (k.b2b ? ' <b>B2B: minimal ' + B2B_MIN + ' peserta.</b>' : '') + '</p>' +
    '<div class="ps-pick">' + rows + '</div></div>' +
    '<footer><button type="button" class="btn btn-ghost" onclick="closeModal()">Batal</button><button type="submit" class="btn btn-primary">Simpan Deal</button></footer></form></div>');
}

function submitDeal(e, id) {
  e.preventDefault();
  fetch('<?= site_url("quotation_program/deal/"); ?>' + id, { method: 'POST', body: new FormData(e.target) })
    .then(function (r) { return r.json(); })
    .then(function (res) {
      toast(res.msg, res.status === 1 ? 'success' : 'error');
      if (res.status === 1) { closeModal(); setTimeout(function () { location.reload(); }, 800); }
    });
  return false;
}

function applyFilters() {
  var p = [];
  <?php if ($pj): ?>p.push('pj=<?= (int)$pj['id']; ?>');<?php endif; ?>
  var t = document.getElementById('f-tujuan'), s = document.getElementById('f-status');
  if (t && t.value !== 'all') p.push('tujuan=' + encodeURIComponent(t.value));
  if (s && s.value !== 'all') p.push('status=' + encodeURIComponent(s.value));
  location.href = '<?= $base; ?>' + (p.length ? '?' + p.join('&') : '');
}

function setQtnStatus(id, st) {
  return fetch('<?= site_url("quotation_program/set_status/"); ?>' + id + '/' + st, { method: 'POST' }).then(function (r) { return r.json(); });
}

// Hanya Batalkan; Deal lewat popup pilih peserta (QTN-FR-11/12).
function updateQtnStatus(id, newStatus, nomor) {
  lmConfirm({
    title: 'Batalkan Quotation',
    text: 'Batalkan ' + nomor + ' karena tidak jadi deal? Quotation Batal tidak dapat diubah; pesertanya dapat dibuatkan quotation baru.',
    confirmText: 'Batalkan',
    danger: true
  }).then(function (ok) {
    if (!ok) return;
    setQtnStatus(id, newStatus).then(function (res) {
      toast(res.msg, res.status === 1 ? 'success' : 'error');
      if (res.status === 1) setTimeout(function () { location.reload(); }, 800);
    });
  });
}

function sendLoaded(fr) { document.getElementById('send-load').style.display = 'none'; fr.style.display = 'block'; }

// Kirim dan Cetak / Cetak / Lihat (QTN-FR-10): pratinjau PDF dahulu; Draft baru menjadi Terkirim saat "Kirim & Cetak".
function sendPrint(id) {
  var k = QTN[id]; if (!k) return;
  var draft = k.status === 'draft', batal = k.status === 'batal';
  var title = batal ? 'Quotation ' + k.nomor + ' (Batal)' : (k.status === 'deal' ? 'Cetak Quotation' : 'Kirim dan Cetak Quotation');
  var save = batal ? '' : '<button type="button" class="btn btn-primary" id="btn-send" onclick="doSend(' + id + ')">' + (k.status === 'deal' ? 'Cetak' : 'Kirim &amp; Cetak') + '</button>';
  openModal('<div class="overlay" onclick="if(event.target===this)closeModal()"><div class="modal xwide">' +
    '<header><h2>' + esc(title) + '</h2><button type="button" class="x" onclick="closeModal()" aria-label="Tutup">×</button></header>' +
    '<div class="modal-body"><div class="pdf-tools"><span>' + (draft ? '<span class="badge st-draft">Preview — belum dikirim</span> ' : '') + 'PDF ' + esc(k.nomor) + ': ' + esc(k.note) + '</span></div>' +
    '<div class="lm-loading" id="send-load"><span class="lm-spin"></span> Memuat PDF…</div>' +
    '<iframe id="send-frame" class="pv-frame" style="display:none" src="<?= site_url("quotation_program/pdf/"); ?>' + id + '?pv=1" title="Preview quotation" onload="sendLoaded(this)"></iframe>' +
    (draft ? '<p class="hint">Setelah dikirim, quotation tidak dapat diedit dan berstatus <b>Terkirim</b> — menunggu peserta deal. Lalu tandai <b>Deal</b>, atau <b>Batalkan</b> bila tidak jadi.</p>' : '') + '</div>' +
    '<footer><button type="button" class="btn btn-ghost" onclick="closeModal()">Tutup</button>' + save + '</footer></div></div>');
}

function doSend(id) {
  var draft = QTN[id].status === 'draft';
  var base = '<?= site_url("quotation_program/pdf/"); ?>' + id;
  if (!draft) { window.open(base); return; }
  document.getElementById('btn-send').disabled = true;
  setQtnStatus(id, 'terkirim').then(function (res) {
    toast(res.msg, res.status === 1 ? 'success' : 'error');
    if (res.status !== 1) { document.getElementById('btn-send').disabled = false; return; }
    window.open(base); // dicatat sebagai Cetak
    setTimeout(function () { location.reload(); }, 1200);
  });
}
</script>
