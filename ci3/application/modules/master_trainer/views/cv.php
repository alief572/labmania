<?php
$e  = function ($v) { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); };
$or = function ($v) use ($e) { return trim((string)$v) === '' ? '—' : $e($v); };
// $summary dan $lists berasal dari Master_trainer::cv_sections() (dipakai bersama PDF).
?>
<div class="page-head">
  <div>
    <h1>CV Trainer — <?= $e($t['nama']); ?> <span class="ref"><?= $e($t['kode']); ?></span></h1>
    <p>Tampilan baca seluruh data trainer dalam urutan Excel: identitas, pendidikan, keahlian, pengesahan, lalu empat daftar bernomor.</p>
  </div>
  <div class="page-actions">
    <a href="<?= site_url('master_trainer/cv_pdf/' . $t['id']); ?>" target="_blank" rel="noopener" class="btn btn-accent" id="btn-cv-pdf">
      <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle; margin-right: 4px;"><path d="M14 3H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"/><polyline points="14 3 14 9 20 9"/></svg>
      Cetak PDF
    </a>
    <a href="<?= site_url('master_trainer'); ?>" class="btn btn-muted">&larr; Kembali</a>
  </div>
</div>

<div class="card cv-document" style="max-width: 900px; margin: 0 auto 40px auto; padding: 32px;">
  <p><b>MASTER TRAINER — IDENTITAS FASILITATOR</b><br><span class="muted"><?= $e($t['kode']); ?></span></p>

  <div class="summary">
    <?php foreach ($summary as $row): ?>
      <div class="sum-row"><span><?= $e($row[0]); ?></span><b><?= $or($row[1]); ?></b></div>
    <?php endforeach; ?>
  </div>

  <?php foreach ($lists as $l): list($key, $title, $cols) = $l; $rows = $t[$key] ?? []; ?>
    <p class="qtn-h"><?= $e($title); ?></p>
    <?php if ($rows): ?>
      <div class="table-wrap">
        <table class="table dash-prev">
          <thead><tr><th>No.</th><?php foreach ($cols as $c): ?><th><?= $e($c[1]); ?></th><?php endforeach; ?></tr></thead>
          <tbody>
            <?php foreach ($rows as $i => $r): ?>
              <tr>
                <td><?= $i + 1; ?></td>
                <?php foreach ($cols as $c): ?>
                  <td>
                    <?php if ($c[0] === 'file_path'): ?>
                      <?php if (!empty($r['file_path'])): ?><a href="<?= base_url('uploads/trainers/' . rawurlencode(basename($r['file_path']))); ?>" target="_blank" rel="noopener">Lihat berkas</a><?php else: ?>—<?php endif; ?>
                    <?php else: echo $or($r[$c[0]] ?? ''); endif; ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <p class="dash-empty">Belum ada data.</p>
    <?php endif; ?>
  <?php endforeach; ?>
</div>

<script>
// Pembuatan PDF butuh beberapa detik: tombol memberi tanda sibuk sebentar agar tidak terlihat macet.
document.getElementById('btn-cv-pdf').addEventListener('click', function () {
  var b = this, html = b.innerHTML;
  b.classList.add('is-busy'); b.textContent = 'Menyiapkan PDF…';
  setTimeout(function () { b.classList.remove('is-busy'); b.innerHTML = html; }, 4000);
});
</script>
