<?php
$ENABLE_ADD     = has_permission('Kasbon_Project.Add');
$ENABLE_MANAGE  = has_permission('Kasbon_Project.Manage');
$ENABLE_VIEW    = has_permission('Kasbon_Project.View');
$ENABLE_DELETE  = has_permission('Kasbon_Project.Delete');

$metode_pembayaran = '';
if ($header->metode_pembayaran == '1') {
  $metode_pembayaran = 'Kasbon';
}
if ($header->metode_pembayaran == '2') {
  $metode_pembayaran = 'Direct Payment';
}
if ($header->metode_pembayaran == '3') {
  $metode_pembayaran = 'PO';
}

$nm_pembuat = !empty($creator_user->nm_lengkap) ? $creator_user->nm_lengkap : (!empty($header->created_by) ? $header->created_by : '-');
$employee_id = !empty($creator_user->employee_id) ? $creator_user->employee_id : '';

$words = explode(' ', trim($nm_pembuat));
$initials = '';
if (count($words) >= 2) {
    $initials = strtoupper(substr($words[0], 0, 1) . substr($words[1], 0, 1));
} else if (count($words) == 1 && strlen($words[0]) > 0) {
    $initials = strtoupper(substr($words[0], 0, 2));
} else {
    $initials = '??';
}

$is_in_team = false;
$emp_id = !empty($employee_id) ? trim((string)$employee_id) : '';
if (!empty($emp_id) && !empty($spk_team_info['team_employee_ids']) && in_array($emp_id, $spk_team_info['team_employee_ids'])) {
    $is_in_team = true;
} else if (!empty($nm_pembuat) && !empty($spk_team_info['team_names'])) {
    $pembuat_name = strtolower(trim($nm_pembuat));
    foreach ($spk_team_info['team_names'] as $tname) {
        if ($tname === $pembuat_name || strpos($tname, $pembuat_name) !== false || strpos($pembuat_name, $tname) !== false) {
            $is_in_team = true;
            break;
        }
    }
}

$origin_spk = '';
if (!$is_in_team) {
    $current_spk = !empty($list_budgeting->id_spk_penawaran) ? $list_budgeting->id_spk_penawaran : (!empty($spk_team_info['id_spk_penawaran']) ? $spk_team_info['id_spk_penawaran'] : '');
    $ci = &get_instance();
    if (method_exists($ci, '_get_user_origin_spk')) {
        $origin_spk = $ci->_get_user_origin_spk($emp_id, $nm_pembuat, $current_spk);
    }
}
?>

<link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.dataTables.min.css">

<style>
  .btn {
    border-radius: 10px;
  }

  .dropdown-menu {
    top: 100%;
    position: absolute;
    overflow: auto;
  }

  .pd-5 {
    padding: 5px;
  }

  .form-inline .form-control {
    width: auto;
    /* Let elements adjust automatically */
    max-width: 100%;
    /* Prevent overflow */
  }

  .form-inline {
    display: flex;
    /* Use flexbox for better alignment */
    justify-content: flex-start;
    /* Align items to the left */
    flex-wrap: nowrap;
    /* Prevent wrapping to the next line */
  }

  .top-total-project {
    width: 280px;
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 15px;
  }

  .pd-5 {
    padding: 5px;
  }

  .valign-top {
    vertical-align: top;
  }

  .mt-5 {
    margin-top: 5px;
  }

  .dropdown-menu {

    position: absolute;
    top: 100%;
    /* Position below the button */
    right: 0;
    /* Align with left edge */
  }

  .valign-middle {
    vertical-align: middle !important;
  }

  .d-none {
    display: none !important;
  }
</style>

<input type="hidden" name="id_spk_budgeting" value="<?= $list_budgeting->id_spk_budgeting ?>">
<input type="hidden" name="id_kasbon" value="<?= $id_kasbon ?>">

<div class="box">
  <div class="box-header">

  </div>

  <div class="box-body" style="z-index: 1 !important;">
    <table border="0" style="width: 100%; z-index: 1 !important;">
      <tr>
        <th class="pd-5 valign-top" width="150">No. SPK</th>
        <td class="pd-5 valign-top" width="400"><?= $list_budgeting->id_spk_penawaran ?></td>
        <th class="pd-5 valign-top" width="150">Project Leader</th>
        <td class="pd-5 valign-top" width="400"><?= ucfirst($list_budgeting->nm_project_leader) ?></td>
      </tr>
      <tr>
        <th class="pd-5 valign-top" width="150">Customer</th>
        <td class="pd-5 valign-top" width="400"><?= $list_budgeting->nm_customer ?></td>
        <th class="pd-5 valign-top" width="150">Sales</th>
        <td class="pd-5 valign-top" width="400"><?= ucfirst($list_budgeting->nm_sales) ?></td>
      </tr>
      <tr>
        <th class="pd-5 valign-top" width="150">Address</th>
        <td class="pd-5 valign-top" width="400"><?= $list_budgeting->alamat ?></td>
        <th class="pd-5 valign-top" width="150">Waktu</th>
        <td class="pd-5 valign-top" width="400">
          <div class="form-inline">
            <div class="form-group">
              <input type="date" name="" id="" class="form-control form-control-sm" value="<?= $list_budgeting->waktu_from ?>" readonly>
            </div>
            <div class="form-group text-center" style="width: 50px; padding-top: 8px;">
              <span>-</span>
            </div>
            <div class="form-group">
              <input type="date" name="" id="" class="form-control form-control-sm" value="<?= $list_budgeting->waktu_to ?>" readonly>
            </div>
          </div>
        </td>
      </tr>
      <tr>
        <th class="pd-5 valign-top" width="150">Project</th>
        <td class="pd-5 valign-top" width="400"><?= $list_budgeting->nm_paket ?></td>
        <th class="pd-5 valign-top" width="150">Request By</th>
        <td class="pd-5 valign-top" width="400">
          <div style="display:flex; align-items:flex-start;">
            <div style="width:26px; height:26px; border-radius:50%; background:#e3e6ea; color:#5c6470; font-size:11px; font-weight:600; display:inline-flex; align-items:center; justify-content:center; margin-right:8px; flex-shrink:0;">
              <?= $initials ?>
            </div>
            <div>
              <div style="font-weight: 600; color: #333;"><?= htmlspecialchars($nm_pembuat) ?></div>
              <?php if (!$is_in_team) : ?>
                <div style="margin-top: 4px;">
                  <span style="display:inline-flex; align-items:center; gap:4px; font-size:11px; color:#c76b00; background:#fff2df; border:1px solid #f0d3a0; padding:1px 8px; border-radius:10px;">
                    <i class="fa fa-exclamation-triangle"></i> <?= htmlspecialchars($nm_pembuat) ?> bukan bagian dari tim SPK ini<?= !empty($origin_spk) ? ' (tim asal: ' . htmlspecialchars($origin_spk) . ')' : '' ?>
                  </span>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </td>
      </tr>
      <tr>
        <th class="pd-5 valign-top" width="150">Tanggal</th>
        <td class="pd-5 valign-top" width="400"><?= date('d F Y', strtotime($header->tgl)) ?></td>
        <th class="pd-5 valign-top" width="150">Description</th>
        <td class="pd-5 valign-top" width="400"><?= $header->deskripsi ?></td>
      </tr>
      <tr>
        <th class="pd-5 valign-top" width="150">Metode Pembayaran</th>
        <td class="pd-5 valign-top" width="400"><?= $metode_pembayaran ?></td>
        <th colspan="2"></th>
      </tr>
    </table>
  </div>
</div>

<div class="box <?= ($tipe !== '1') ? 'd-none' : '' ?>">
  <div class="box-header">
    <h4 style="font-weight: bold;">Informasi Pengajuan</h4>
  </div>

  <div class="box-body">
    <table class="table custom-table">
      <thead>
        <tr>
          <th rowspan="2" class="text-center">No.</th>
          <th rowspan="2" class="text-center">Item</th>
          <th colspan="3" class="text-center">Estimasi</th>
          <th colspan="3" class="text-center">Terpakai</th>
          <th colspan="2" class="text-center">Sisa Sebelum Pengajuan</th>
          <th colspan="3" class="text-center">Overbudget</th>
        </tr>
        <tr>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total Budget</th>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total Terpakai</th>
          <th class="text-center">Qty</th>
          <th class="text-center">Sisa budget</th>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $no = 0;

        $ttl_qty_estimasi_subcont = 0;
        $ttl_total_estimasi_subcont = 0;
        $ttl_qty_terpakai_subcont = 0;
        $ttl_total_terpakai_subcont = 0;
        $ttl_qty_sebelum = 0;
        $ttl_sisa_budget_sebelum = 0;
        $ttl_qty_overbudget = 0;
        $ttl_total_overbudget = 0;

        foreach ($list_kasbon_subcont as $item) {
          $no++;

          $qty_tambahan = (isset($data_overbudget_subcont[$item->id_aktifitas])) ? $data_overbudget_subcont[$item->id_aktifitas]['qty_budget_tambahan'] : 0;
          $budget_tambahan = (isset($data_overbudget_subcont[$item->id_aktifitas])) ? $data_overbudget_subcont[$item->id_aktifitas]['budget_tambahan'] : 0;

          $qty_sisa_sebelum = (($item->qty_estimasi - $item->qty_terpakai) - $item->qty_overbudget);
          $sisa_budget_sebelum = (($item->total_budget_estimasi - $item->total_terpakai) - $item->total_overbudget);

          echo '<tr>';

          echo '<td class="text-center">' . $no . '</td>';
          echo '<td class="text-left">' . $item->nm_aktifitas . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_estimasi) . '</td>';
          echo '<td class="text-right">' . number_format($item->price_unit_estimasi, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->total_budget_estimasi, 2) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_terpakai, 2) . '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_terpakai > 0) ? number_format($item->nominal_terpakai, 2) : '-';
          echo '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_terpakai > 0) ? number_format($item->total_terpakai, 2) : '-';
          echo '</td>';
          echo '<td class="text-center">' . number_format($qty_sisa_sebelum, 2) . '</td>';
          echo '<td class="text-right">' . number_format($sisa_budget_sebelum, 2) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_overbudget, 2) . '</td>';
          echo '<td>';
          echo ($item->qty_overbudget > 0) ? number_format($item->nominal_overbudget, 2) : '-';
          echo '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_overbudget > 0) ? number_format($item->total_overbudget, 2) : '-';
          echo '</td>';
          echo '</tr>';

          $ttl_qty_estimasi_subcont += $item->qty_estimasi;
          $ttl_total_estimasi_subcont += $item->total_budget_estimasi;
          $ttl_qty_terpakai_subcont += $item->qty_terpakai;
          $ttl_total_terpakai_subcont += ($item->qty_terpakai > 0) ? $item->total_terpakai : 0;
          $ttl_qty_sebelum += ($item->aktual_terpakai - $item->qty_overbudget);
          $ttl_sisa_budget_sebelum += ($item->sisa_budget - $item->total_overbudget);
          $ttl_qty_overbudget += $item->qty_overbudget;
          $ttl_total_overbudget += ($item->qty_overbudget > 0) ? $item->total_terpakai : 0;
        }

        foreach ($list_kasbon_subcont_custom as $item) {
          $no++;

          $qty_tambahan = (isset($data_overbudget_subcont[$item->id_aktifitas])) ? $data_overbudget_subcont[$item->id_aktifitas]['qty_budget_tambahan'] : 0;
          $budget_tambahan = (isset($data_overbudget_subcont[$item->id_aktifitas])) ? $data_overbudget_subcont[$item->id_aktifitas]['budget_tambahan'] : 0;

          $qty_sisa_sebelum = (($item->qty_estimasi - $item->qty_terpakai) - $item->qty_overbudget);
          $sisa_budget_sebelum = (($item->total_budget_estimasi - $item->total_terpakai) - $item->total_overbudget);

          echo '<tr>';

          echo '<td class="text-center">' . $no . '</td>';
          echo '<td class="text-left">' . $item->nm_aktifitas . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_estimasi) . '</td>';
          echo '<td class="text-right">' . number_format($item->price_unit_estimasi, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->total_budget_estimasi, 2) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_terpakai, 2) . '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_terpakai > 0) ? number_format($item->nominal_terpakai, 2) : '-';
          echo '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_terpakai > 0) ? number_format($item->total_terpakai, 2) : '-';
          echo '</td>';
          echo '<td class="text-center">' . number_format($qty_sisa_sebelum, 2) . '</td>';
          echo '<td class="text-right">' . number_format($sisa_budget_sebelum, 2) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_overbudget, 2) . '</td>';
          echo '<td>';
          echo ($item->qty_overbudget > 0) ? number_format($item->nominal_overbudget, 2) : '-';
          echo '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_overbudget > 0) ? number_format($item->total_overbudget, 2) : '-';
          echo '</td>';
          echo '</tr>';

          $ttl_qty_estimasi_subcont += $item->qty_estimasi;
          $ttl_total_estimasi_subcont += $item->total_budget_estimasi;
          $ttl_qty_terpakai_subcont += $item->qty_terpakai;
          $ttl_total_terpakai_subcont += ($item->qty_terpakai > 0) ? $item->total_terpakai : 0;
          $ttl_qty_sebelum += ($item->aktual_terpakai - $item->qty_overbudget);
          $ttl_sisa_budget_sebelum += ($item->sisa_budget - $item->total_overbudget);
          $ttl_qty_overbudget += $item->qty_overbudget;
          $ttl_total_overbudget += ($item->qty_overbudget > 0) ? $item->total_terpakai : 0;
        }
        ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="2" class="text-center">Grand Total</th>
          <th class="text-center"><?= number_format($ttl_qty_estimasi_subcont, 2) ?></th>
          <th></th>
          <th class="text-right"><?= number_format($ttl_total_estimasi_subcont, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_qty_terpakai_subcont, 2) ?></th>
          <th></th>
          <th class="text-right"><?= number_format($ttl_total_terpakai_subcont, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_qty_sebelum, 2) ?></th>
          <th class="text-right"><?= number_format($ttl_sisa_budget_sebelum, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_qty_overbudget, 2) ?></th>
          <th></th>
          <th class="text-right"><?= number_format($ttl_total_overbudget, 2) ?></th>
        </tr>
      </tfoot>
    </table>

    <br><br>

    <table class="table custom-table">
      <thead>
        <tr>
          <th rowspan="2" class="text-center" valign="middle">No.</th>
          <th rowspan="2" class="text-center" valign="middle" width="170">Item</th>
          <th colspan="3" class="text-center">Pengajuan</th>
          <th colspan="2" class="text-center valign-middle">Sisa Setelah Pengajuan</th>
        </tr>
        <tr>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total Pengajuan</th>
          <th class="text-center">Sisa Qty</th>
          <th class="text-center">Sisa Budget</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $no = 0;
        foreach ($list_kasbon_subcont as $item) {
          if ($item->qty_pengajuan <= 0) continue;
          $no++;

          $sisa_qty_setelah = (($item->qty_estimasi - $item->qty_terpakai) + $item->qty_overbudget - $item->qty_pengajuan);
          $sisa_budget_setelah = (($item->total_budget_estimasi - $item->total_terpakai) + $item->total_overbudget - $item->total_pengajuan);

          echo '<tr>';

          echo '<td class="text-center">' . $no . '</td>';
          echo '<td>' . $item->nm_aktifitas . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_pengajuan, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->nominal_pengajuan, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->total_pengajuan, 2) . '</td>';
          echo '<td class="text-center">' . number_format($sisa_qty_setelah, 2) . '</td>';
          echo '<td class="text-right">' . number_format($sisa_budget_setelah, 2) . '</td>';

          echo '</tr>';
        }

        foreach ($list_kasbon_subcont_custom as $item) {
          if ($item->qty_pengajuan <= 0) continue;
          $no++;

          $sisa_qty_setelah = (($item->qty_estimasi - $item->qty_terpakai) + $item->qty_overbudget - $item->qty_pengajuan);
          $sisa_budget_setelah = (($item->total_budget_estimasi - $item->total_terpakai) + $item->total_overbudget - $item->total_pengajuan);

          echo '<tr>';

          echo '<td class="text-center">' . $no . '</td>';
          echo '<td>' . $item->nm_aktifitas . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_pengajuan, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->nominal_pengajuan, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->total_pengajuan, 2) . '</td>';
          echo '<td class="text-center">' . number_format($sisa_qty_setelah, 2) . '</td>';
          echo '<td class="text-right">' . number_format($sisa_budget_setelah, 2) . '</td>';

          echo '</tr>';
        }
        ?>
      </tbody>
      <tfoot>

      </tfoot>
    </table>
  </div>
</div>

<div class="box <?= ($tipe !== '2') ? 'd-none' : '' ?>">
  <div class="box-header">
    <h4>Informasi Pengajuan</h4>
    <hr>
  </div>

  <div class="box-body">
    <table class="table custom-table">
      <thead>
        <tr>
          <th rowspan="2" class="text-center">No.</th>
          <th rowspan="2" class="text-center">Item</th>
          <th colspan="3" class="text-center">Estimasi</th>
          <th colspan="3" class="text-center">Terpakai</th>
          <th colspan="2" class="text-center">Sisa Sebelum Pengajuan</th>
          <th colspan="3" class="text-center">Overbudget</th>
        </tr>
        <tr>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total Budget</th>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total Terpakai</th>
          <th class="text-center">Qty</th>
          <th class="text-center">Sisa budget</th>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $no = 0;
        $ttl_qty_estimasi_others = 0;
        $ttl_total_estimasi_others = 0;
        $ttl_qty_terpakai_others = 0;
        $ttl_total_terpakai_others = 0;
        $ttl_qty_sebelum = 0;
        $ttl_sisa_budget_sebelum = 0;
        $ttl_qty_overbudget = 0;
        $ttl_total_overbudget = 0;

        $ttl_qty_sebelum = 0;

        foreach ($list_kasbon_akomodasi as $item) {
          $no++;

          $qty_sisa_sebelum = (($item->qty_estimasi - $item->qty_terpakai) - $item->qty_overbudget);
          $sisa_budget_sebelum = (($item->total_budget_estimasi - $item->total_terpakai) - $item->total_overbudget);

          echo '<tr>';

          echo '<td class="text-center">' . $no . '</td>';
          echo '<td class="text-left">' . (!empty($item->nm_biaya) ? $item->nm_biaya : $item->nm_item) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_estimasi) . '</td>';
          echo '<td class="text-right">' . number_format($item->price_unit_estimasi, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->total_budget_estimasi, 2) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_terpakai) . '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_terpakai > 0) ? number_format($item->nominal_terpakai, 2) : '-';
          echo '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_terpakai > 0) ? number_format($item->total_terpakai, 2) : '-';
          echo '</td>';
          echo '<td class="text-center">' . number_format(($item->qty_estimasi - $item->qty_terpakai) - $item->qty_overbudget, 2) . '</td>';
          echo '<td class="text-right">' . number_format(($item->total_budget_estimasi - $item->total_terpakai) - $item->total_overbudget, 2) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_overbudget, 2) . '</td>';
          echo '<td>';
          echo ($item->qty_overbudget > 0) ? number_format($item->nominal_overbudget, 2) : '-';
          echo '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_overbudget > 0) ? number_format($item->total_overbudget, 2) : '-';
          echo '</td>';

          echo '</tr>';

          $ttl_qty_estimasi_others += $item->qty_estimasi;
          $ttl_total_estimasi_others += $item->total_budget_estimasi;
          $ttl_qty_terpakai_others += $item->qty_terpakai;
          $ttl_total_terpakai_others += ($item->qty_terpakai > 0) ? $item->total_terpakai : 0;
          $ttl_qty_sebelum += $qty_sisa_sebelum;
          $ttl_sisa_budget_sebelum += $sisa_budget_sebelum;
          $ttl_qty_overbudget += $item->qty_overbudget;
          $ttl_total_overbudget += ($item->qty_overbudget > 0) ? $item->total_terpakai : 0;
        }
        ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="2" class="text-center">Grand Total</th>
          <th class="text-center"><?= number_format($ttl_qty_estimasi_others, 2) ?></th>
          <th></th>
          <th class="text-right"><?= number_format($ttl_total_estimasi_others, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_qty_terpakai_others, 2) ?></th>
          <th></th>
          <th class="text-right"><?= number_format($ttl_total_terpakai_others, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_qty_sebelum, 2) ?></th>
          <th class="text-right"><?= number_format($ttl_sisa_budget_sebelum, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_qty_overbudget, 2) ?></th>
          <th></th>
          <th class="text-right"><?= number_format($ttl_total_overbudget, 2) ?></th>
        </tr>
      </tfoot>
    </table>

    <br>

    <h4 style="font-weight: bold;">Informasi</h4>

    <br>

    <table class="table custom-table">
      <thead>
        <tr>
          <th rowspan="2" class="text-center" valign="middle">No.</th>
          <th rowspan="2" class="text-center" valign="middle" width="170">Item</th>
          <th colspan="3" class="text-center">Pengajuan</th>
          <th colspan="2" class="text-center valign-middle">Sisa Setelah Pengajuan</th>
        </tr>
        <tr>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total Pengajuan</th>
          <th class="text-center">Sisa Qty</th>
          <th class="text-center">Sisa Budget</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $ttl_estimasi_akomodasi = 0;
        $ttl_pengajuan_akomodasi = 0;
        $ttl_aktual_akomodasi = 0;
        $ttl_sisa_akomodasi = 0;

        $no = 0;
        foreach ($list_kasbon_akomodasi as $item) {
          if ($item->qty_pengajuan <= 0) continue;
          $no++;

          $sisa_qty_setelah = (($item->qty_estimasi - $item->qty_terpakai) + $item->qty_overbudget - $item->qty_pengajuan);
          $sisa_budget_setelah = (($item->total_budget_estimasi - $item->total_terpakai) + $item->total_overbudget - $item->total_pengajuan);

          echo '<tr>';

          echo '<td class="text-center">' . $no . '</td>';
          echo '<td class="text-left">' . (!empty($item->nm_biaya) ? $item->nm_biaya : $item->nm_item) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_pengajuan, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->nominal_pengajuan, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->total_pengajuan, 2) . '</td>';
          echo '<td class="text-center">' . number_format($sisa_qty_setelah, 2) . '</td>';
          echo '<td class="text-right">' . number_format($sisa_budget_setelah, 2) . '</td>';
          echo '</tr>';

          $ttl_estimasi_akomodasi += $item->total_budget_estimasi;
          $ttl_pengajuan_akomodasi += $item->total_pengajuan;
          $ttl_aktual_akomodasi += ($item->aktual_terpakai - $item->qty_pengajuan);
          $ttl_sisa_akomodasi += ($item->sisa_budget - $item->total_pengajuan);
        }
        ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="4" class="text-right">Grand Total</th>
          <th class="text-right"><?= number_format($ttl_pengajuan_akomodasi, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_aktual_akomodasi, 2) ?></th>
          <th class="text-right"><?= number_format($ttl_sisa_akomodasi, 2) ?></th>
        </tr>
      </tfoot>
    </table>


  </div>
</div>

<div class="box <?= ($tipe !== '3') ? 'd-none' : '' ?>">
  <div class="box-header">
    <h4 style="font-weight: bold;">Informasi Pengajuan</h4>
  </div>

  <div class="box-body">
    <table class="table custom-table">
      <thead>
        <tr>
          <th rowspan="2" class="text-center">No.</th>
          <th rowspan="2" class="text-center">Item</th>
          <th colspan="3" class="text-center">Estimasi</th>
          <th colspan="3" class="text-center">Terpakai</th>
          <th colspan="2" class="text-center">Sisa Sebelum Pengajuan</th>
          <th colspan="3" class="text-center">Overbudget</th>
        </tr>
        <tr>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total Budget</th>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total Terpakai</th>
          <th class="text-center">Qty</th>
          <th class="text-center">Sisa budget</th>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $no = 0;
        $ttl_qty_estimasi_others = 0;
        $ttl_total_estimasi_others = 0;
        $ttl_qty_terpakai_others = 0;
        $ttl_total_terpakai_others = 0;
        $ttl_qty_sebelum = 0;
        $ttl_sisa_budget_sebelum = 0;
        $ttl_qty_overbudget = 0;
        $ttl_total_overbudget = 0;

        foreach ($list_kasbon_others as $item) {
          $no++;

          $qty_sisa_sebelum = (($item->qty_estimasi - $item->qty_terpakai) - $item->qty_overbudget);
          $sisa_budget_sebelum = (($item->total_budget_estimasi - $item->total_terpakai) - $item->total_overbudget);

          echo '<tr>';

          echo '<td class="text-center">' . $no . '</td>';
          echo '<td>' . (!empty($item->nm_biaya) ? $item->nm_biaya : $item->nm_item) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_estimasi, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->price_unit_estimasi, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->total_budget_estimasi, 2) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_terpakai, 2) . '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_terpakai > 0) ? number_format($item->nominal_terpakai, 2) : '-';
          echo '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_terpakai > 0) ? number_format($item->total_terpakai, 2) : '-';
          echo '</td>';
          echo '<td class="text-center">' . number_format($qty_sisa_sebelum, 2) . '</td>';
          echo '<td class="text-right">' . number_format($sisa_budget_sebelum, 2) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_overbudget, 2) . '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_overbudget > 0) ? number_format($item->nominal_overbudget, 2) : '-';
          echo '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_overbudget > 0) ? number_format($item->total_overbudget, 2) : '-';
          echo '</td>';

          echo '</tr>';

          $ttl_qty_estimasi_others += $item->qty_estimasi;
          $ttl_total_estimasi_others += $item->total_budget_estimasi;
          $ttl_qty_terpakai_others += $item->qty_terpakai;
          $ttl_total_terpakai_others += ($item->qty_terpakai > 0) ? $item->total_terpakai : 0;
          $ttl_qty_sebelum += $qty_sisa_sebelum;
          $ttl_sisa_budget_sebelum += $sisa_budget_sebelum;
          $ttl_qty_overbudget += $item->qty_overbudget;
          $ttl_total_overbudget += ($item->qty_overbudget > 0) ? $item->total_terpakai : 0;
        }
        ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="2" class="text-center">Grand Total</th>
          <th class="text-center"><?= number_format($ttl_qty_estimasi_others, 2) ?></th>
          <th></th>
          <th class="text-right"><?= number_format($ttl_total_estimasi_others, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_qty_terpakai_others, 2) ?></th>
          <th></th>
          <th class="text-right"><?= number_format($ttl_total_terpakai_others, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_qty_sebelum, 2) ?></th>
          <th class="text-right"><?= number_format($ttl_sisa_budget_sebelum, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_qty_overbudget, 2) ?></th>
          <th></th>
          <th class="text-right"><?= number_format($ttl_total_overbudget, 2) ?></th>
        </tr>
      </tfoot>
    </table>

    <br><br>

    <table class="table custom-table">
      <thead>
        <tr>
          <th rowspan="2" class="text-center" valign="middle">No.</th>
          <th rowspan="2" class="text-center" valign="middle" width="170">Item</th>
          <th colspan="3" class="text-center">Pengajuan</th>
          <th colspan="2" class="text-center valign-middle">Sisa Setelah Pengajuan</th>
        </tr>
        <tr>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total Pengajuan</th>
          <th class="text-center">Sisa Qty</th>
          <th class="text-center">Sisa Budget</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $ttl_estimasi_others = 0;
        $ttl_pengajuan_others = 0;
        $ttl_aktual_others = 0;
        $ttl_sisa_others = 0;

        $no = 0;
        foreach ($list_kasbon_others as $item) {
          if ($item->qty_pengajuan <= 0) continue;
          $no++;

          $qty_tambahan = (isset($data_overbudget_others[$item->id_others])) ? $data_overbudget_others[$item->id_others]['qty_budget_tambahan'] : 0;
          $nominal_tambahan = (isset($data_overbudget_others[$item->id_others])) ? $data_overbudget_others[$item->id_others]['budget_tambahan'] : 0;

          $sisa_qty_setelah = (($item->qty_estimasi - $item->qty_terpakai) + $item->qty_overbudget - $item->qty_pengajuan);
          $sisa_budget_setelah = (($item->total_budget_estimasi - $item->total_terpakai) + $item->total_overbudget - $item->total_pengajuan);

          echo '<tr>';

          echo '<td class="text-center">' . $no . '</td>';
          echo '<td class="text-left">' . (!empty($item->nm_biaya) ? $item->nm_biaya : $item->nm_item) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_pengajuan, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->nominal_pengajuan, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->total_pengajuan, 2) . '</td>';
          echo '<td class="text-center">' . number_format($sisa_qty_setelah, 2) . '</td>';
          echo '<td class="text-right">' . number_format($sisa_budget_setelah, 2) . '</td>';
          echo '</tr>';

          $ttl_estimasi_others += $item->total_budget_estimasi;
          $ttl_pengajuan_others += $item->total_pengajuan;
          $ttl_aktual_others += ($item->aktual_terpakai - $item->qty_pengajuan);
          $ttl_sisa_others += ($item->sisa_budget - $item->total_pengajuan);
        }
        ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="4" class="text-right">Grand Total</th>
          <th class="text-right"><?= number_format($ttl_pengajuan_others, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_aktual_others, 2) ?></th>
          <th class="text-right"><?= number_format($ttl_sisa_others, 2) ?></th>
        </tr>
      </tfoot>
    </table>

    <br><br>


  </div>
</div>

<div class="box <?= ($tipe !== '4') ? 'd-none' : '' ?>">
  <div class="box-header">
    <h4 style="font-weight: bold;">Informasi Pengajuan</h4>
  </div>

  <div class="box-body">
    <table class="table custom-table">
      <thead>
        <tr>
          <th rowspan="2" class="text-center">No.</th>
          <th rowspan="2" class="text-center">Item</th>
          <th colspan="3" class="text-center">Estimasi</th>
          <th colspan="3" class="text-center">Terpakai</th>
          <th colspan="2" class="text-center">Sisa Sebelum Pengajuan</th>
          <th colspan="3" class="text-center">Overbudget</th>
        </tr>
        <tr>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total Budget</th>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total Terpakai</th>
          <th class="text-center">Qty</th>
          <th class="text-center">Sisa budget</th>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $no = 0;
        $ttl_qty_estimasi_lab = 0;
        $ttl_total_estimasi_lab = 0;
        $ttl_qty_terpakai_lab = 0;
        $ttl_total_terpakai_lab = 0;
        $ttl_qty_sebelum = 0;
        $ttl_sisa_budget_sebelum = 0;
        $ttl_qty_overbudget = 0;
        $ttl_total_overbudget = 0;

        foreach ($list_kasbon_lab as $item) {
          $no++;

          $qty_sisa_sebelum = (($item->qty_estimasi - $item->qty_terpakai) - $item->qty_overbudget);
          $sisa_budget_sebelum = (($item->total_budget_estimasi - $item->total_terpakai) - $item->total_overbudget);

          echo '<tr>';

          echo '<td class="text-center">' . $no . '</td>';
          echo '<td>' . (!empty($item->nm_biaya) ? $item->nm_biaya : $item->nm_item) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_estimasi, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->price_unit_estimasi, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->total_budget_estimasi, 2) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_terpakai, 2) . '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_terpakai > 0) ? number_format($item->nominal_terpakai, 2) : '-';
          echo '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_terpakai > 0) ? number_format($item->total_terpakai, 2) : '-';
          echo '</td>';
          echo '<td class="text-center">' . number_format($qty_sisa_sebelum, 2) . '</td>';
          echo '<td class="text-right">' . number_format($sisa_budget_sebelum, 2) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_overbudget, 2) . '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_overbudget > 0) ? number_format($item->nominal_overbudget, 2) : '-';
          echo '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_overbudget > 0) ? number_format($item->total_overbudget, 2) : '-';
          echo '</td>';

          echo '</tr>';

          $ttl_qty_estimasi_lab += $item->qty_estimasi;
          $ttl_total_estimasi_lab += $item->total_budget_estimasi;
          $ttl_qty_terpakai_lab += $item->qty_terpakai;
          $ttl_total_terpakai_lab += ($item->qty_terpakai > 0) ? $item->total_terpakai : 0;
          $ttl_qty_sebelum += $qty_sisa_sebelum;
          $ttl_sisa_budget_sebelum += $sisa_budget_sebelum;
          $ttl_qty_overbudget += $item->qty_overbudget;
          $ttl_total_overbudget += ($item->qty_overbudget > 0) ? $item->total_terpakai : 0;
        }
        ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="2" class="text-center">Grand Total</th>
          <th class="text-center"><?= number_format($ttl_qty_estimasi_lab, 2) ?></th>
          <th></th>
          <th class="text-right"><?= number_format($ttl_total_estimasi_lab, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_qty_terpakai_lab, 2) ?></th>
          <th></th>
          <th class="text-right"><?= number_format($ttl_total_terpakai_lab, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_qty_sebelum, 2) ?></th>
          <th class="text-right"><?= number_format($ttl_sisa_budget_sebelum, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_qty_overbudget, 2) ?></th>
          <th></th>
          <th class="text-right"><?= number_format($ttl_total_overbudget, 2) ?></th>
        </tr>
      </tfoot>
    </table>

    <br><br>

    <table class="table custom-table">
      <thead>
        <tr>
          <th rowspan="2" class="text-center" valign="middle">No.</th>
          <th rowspan="2" class="text-center" valign="middle" width="170">Item</th>
          <th colspan="3" class="text-center">Pengajuan</th>
          <th colspan="2" class="text-center valign-middle">Sisa Setelah Pengajuan</th>
        </tr>
        <tr>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total Pengajuan</th>
          <th class="text-center">Sisa Qty</th>
          <th class="text-center">Sisa Budget</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $ttl_estimasi_lab = 0;
        $ttl_pengajuan_lab = 0;
        $ttl_aktual_lab = 0;
        $ttl_sisa_lab = 0;

        $no = 0;
        foreach ($list_kasbon_lab as $item) {
          if ($item->qty_pengajuan <= 0) continue;
          $no++;

          $qty_tambahan = (isset($data_overbudget_lab[$item->id_lab])) ? $data_overbudget_lab[$item->id_lab]['qty_budget_tambahan'] : 0;
          $nominal_tambahan = (isset($data_overbudget_lab[$item->id_lab])) ? $data_overbudget_lab[$item->id_lab]['budget_tambahan'] : 0;

          $sisa_qty_setelah = (($item->qty_estimasi - $item->qty_terpakai) + $item->qty_overbudget - $item->qty_pengajuan);
          $sisa_budget_setelah = (($item->total_budget_estimasi - $item->total_terpakai) + $item->total_overbudget - $item->total_pengajuan);

          echo '<tr>';

          echo '<td class="text-center">' . $no . '</td>';
          echo '<td class="text-left">' . (!empty($item->nm_biaya) ? $item->nm_biaya : $item->nm_item) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_pengajuan, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->nominal_pengajuan, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->total_pengajuan, 2) . '</td>';
          echo '<td class="text-center">' . number_format($sisa_qty_setelah, 2) . '</td>';
          echo '<td class="text-right">' . number_format($sisa_budget_setelah, 2) . '</td>';
          echo '</tr>';

          $ttl_estimasi_lab += $item->total_budget_estimasi;
          $ttl_pengajuan_lab += $item->total_pengajuan;
          $ttl_aktual_lab += ($item->aktual_terpakai - $item->qty_pengajuan);
          $ttl_sisa_lab += ($item->sisa_budget - $item->total_pengajuan);
        }
        ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="4" class="text-right">Grand Total</th>
          <th class="text-right"><?= number_format($ttl_pengajuan_lab, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_aktual_lab, 2) ?></th>
          <th class="text-right"><?= number_format($ttl_sisa_lab, 2) ?></th>
        </tr>
      </tfoot>
    </table>

    <br><br>


  </div>
</div>

<div class="box <?= ($tipe !== '5') ? 'd-none' : '' ?>">
  <div class="box-header">
    <h4 style="font-weight: bold;">Informasi Pengajuan</h4>
  </div>

  <div class="box-body">
    <table class="table custom-table">
      <thead>
        <tr>
          <th rowspan="2" class="text-center">No.</th>
          <th rowspan="2" class="text-center">Item</th>
          <th colspan="3" class="text-center">Estimasi</th>
          <th colspan="3" class="text-center">Terpakai</th>
          <th colspan="2" class="text-center">Sisa Sebelum Pengajuan</th>
          <th colspan="3" class="text-center">Overbudget</th>
        </tr>
        <tr>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total Budget</th>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total Terpakai</th>
          <th class="text-center">Qty</th>
          <th class="text-center">Sisa budget</th>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $no = 0;
        $ttl_qty_estimasi_subcont_tenaga_ahli = 0;
        $ttl_total_estimasi_subcont_tenaga_ahli = 0;
        $ttl_qty_terpakai_subcont_tenaga_ahli = 0;
        $ttl_total_terpakai_subcont_tenaga_ahli = 0;
        $ttl_qty_sebelum = 0;
        $ttl_sisa_budget_sebelum = 0;
        $ttl_qty_overbudget = 0;
        $ttl_total_overbudget = 0;

        foreach ($list_kasbon_subcont_tenaga_ahli as $item) {
          $no++;

          $qty_sisa_sebelum = (($item->qty_estimasi - $item->qty_terpakai) - $item->qty_overbudget);
          $sisa_budget_sebelum = (($item->total_budget_estimasi - $item->total_terpakai) - $item->total_overbudget);

          echo '<tr>';

          echo '<td class="text-center">' . $no . '</td>';
          echo '<td>' . (!empty($item->nm_biaya) ? $item->nm_biaya : $item->nm_item) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_estimasi, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->price_unit_estimasi, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->total_budget_estimasi, 2) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_terpakai, 2) . '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_terpakai > 0) ? number_format($item->nominal_terpakai, 2) : '-';
          echo '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_terpakai > 0) ? number_format($item->total_terpakai, 2) : '-';
          echo '</td>';
          echo '<td class="text-center">' . number_format($qty_sisa_sebelum, 2) . '</td>';
          echo '<td class="text-right">' . number_format($sisa_budget_sebelum, 2) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_overbudget, 2) . '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_overbudget > 0) ? number_format($item->nominal_overbudget, 2) : '-';
          echo '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_overbudget > 0) ? number_format($item->total_overbudget, 2) : '-';
          echo '</td>';

          echo '</tr>';

          $ttl_qty_estimasi_subcont_tenaga_ahli += $item->qty_estimasi;
          $ttl_total_estimasi_subcont_tenaga_ahli += $item->total_budget_estimasi;
          $ttl_qty_terpakai_subcont_tenaga_ahli += $item->qty_terpakai;
          $ttl_total_terpakai_subcont_tenaga_ahli += ($item->qty_terpakai > 0) ? $item->total_terpakai : 0;
          $ttl_qty_sebelum += $qty_sisa_sebelum;
          $ttl_sisa_budget_sebelum += $sisa_budget_sebelum;
          $ttl_qty_overbudget += $item->qty_overbudget;
          $ttl_total_overbudget += ($item->qty_overbudget > 0) ? $item->total_terpakai : 0;
        }
        ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="2" class="text-center">Grand Total</th>
          <th class="text-center"><?= number_format($ttl_qty_estimasi_subcont_tenaga_ahli, 2) ?></th>
          <th></th>
          <th class="text-right"><?= number_format($ttl_total_estimasi_subcont_tenaga_ahli, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_qty_terpakai_subcont_tenaga_ahli, 2) ?></th>
          <th></th>
          <th class="text-right"><?= number_format($ttl_total_terpakai_subcont_tenaga_ahli, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_qty_sebelum, 2) ?></th>
          <th class="text-right"><?= number_format($ttl_sisa_budget_sebelum, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_qty_overbudget, 2) ?></th>
          <th></th>
          <th class="text-right"><?= number_format($ttl_total_overbudget, 2) ?></th>
        </tr>
      </tfoot>
    </table>

    <br><br>

    <table class="table custom-table">
      <thead>
        <tr>
          <th rowspan="2" class="text-center" valign="middle">No.</th>
          <th rowspan="2" class="text-center" valign="middle" width="170">Item</th>
          <th colspan="3" class="text-center">Pengajuan</th>
          <th colspan="2" class="text-center valign-middle">Sisa Setelah Pengajuan</th>
        </tr>
        <tr>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total Pengajuan</th>
          <th class="text-center">Sisa Qty</th>
          <th class="text-center">Sisa Budget</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $ttl_estimasi_subcont_tenaga_ahli = 0;
        $ttl_pengajuan_subcont_tenaga_ahli = 0;
        $ttl_aktual_subcont_tenaga_ahli = 0;
        $ttl_sisa_subcont_tenaga_ahli = 0;

        $no = 0;
        foreach ($list_kasbon_subcont_tenaga_ahli as $item) {
          if ($item->qty_pengajuan <= 0) continue;
          $no++;

          $qty_tambahan = (isset($data_overbudget_subcont_tenaga_ahli[$item->id_subcont])) ? $data_overbudget_subcont_tenaga_ahli[$item->id_subcont]['qty_budget_tambahan'] : 0;
          $nominal_tambahan = (isset($data_overbudget_subcont_tenaga_ahli[$item->id_subcont])) ? $data_overbudget_subcont_tenaga_ahli[$item->id_subcont]['budget_tambahan'] : 0;

          $sisa_qty_setelah = (($item->qty_estimasi - $item->qty_terpakai) + $item->qty_overbudget - $item->qty_pengajuan);
          $sisa_budget_setelah = (($item->total_budget_estimasi - $item->total_terpakai) + $item->total_overbudget - $item->total_pengajuan);

          echo '<tr>';

          echo '<td class="text-center">' . $no . '</td>';
          echo '<td class="text-left">' . (!empty($item->nm_biaya) ? $item->nm_biaya : $item->nm_item) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_pengajuan, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->nominal_pengajuan, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->total_pengajuan, 2) . '</td>';
          echo '<td class="text-center">' . number_format($sisa_qty_setelah, 2) . '</td>';
          echo '<td class="text-right">' . number_format($sisa_budget_setelah, 2) . '</td>';
          echo '</tr>';

          $ttl_estimasi_subcont_tenaga_ahli += $item->total_budget_estimasi;
          $ttl_pengajuan_subcont_tenaga_ahli += $item->total_pengajuan;
          $ttl_aktual_subcont_tenaga_ahli += ($item->aktual_terpakai - $item->qty_pengajuan);
          $ttl_sisa_subcont_tenaga_ahli += ($item->sisa_budget - $item->total_pengajuan);
        }
        ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="4" class="text-right">Grand Total</th>
          <th class="text-right"><?= number_format($ttl_pengajuan_subcont_tenaga_ahli, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_aktual_subcont_tenaga_ahli, 2) ?></th>
          <th class="text-right"><?= number_format($ttl_sisa_subcont_tenaga_ahli, 2) ?></th>
        </tr>
      </tfoot>
    </table>

    <br><br>


  </div>
</div>

<div class="box <?= ($tipe !== '6') ? 'd-none' : '' ?>">
  <div class="box-header">
    <h4 style="font-weight: bold;">Informasi Pengajuan</h4>
  </div>

  <div class="box-body">
    <table class="table custom-table">
      <thead>
        <tr>
          <th rowspan="2" class="text-center">No.</th>
          <th rowspan="2" class="text-center">Item</th>
          <th colspan="3" class="text-center">Estimasi</th>
          <th colspan="3" class="text-center">Terpakai</th>
          <th colspan="2" class="text-center">Sisa Sebelum Pengajuan</th>
          <th colspan="3" class="text-center">Overbudget</th>
        </tr>
        <tr>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total Budget</th>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total Terpakai</th>
          <th class="text-center">Qty</th>
          <th class="text-center">Sisa budget</th>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $no = 0;
        $ttl_qty_estimasi_subcont_perusahaan = 0;
        $ttl_total_estimasi_subcont_perusahaan = 0;
        $ttl_qty_terpakai_subcont_perusahaan = 0;
        $ttl_total_terpakai_subcont_perusahaan = 0;
        $ttl_qty_sebelum = 0;
        $ttl_sisa_budget_sebelum = 0;
        $ttl_qty_overbudget = 0;
        $ttl_total_overbudget = 0;

        foreach ($list_kasbon_subcont_perusahaan as $item) {
          $no++;

          $qty_sisa_sebelum = (($item->qty_estimasi - $item->qty_terpakai) - $item->qty_overbudget);
          $sisa_budget_sebelum = (($item->total_budget_estimasi - $item->total_terpakai) - $item->total_overbudget);

          echo '<tr>';

          echo '<td class="text-center">' . $no . '</td>';
          echo '<td>' . (!empty($item->nm_biaya) ? $item->nm_biaya : $item->nm_item) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_estimasi, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->price_unit_estimasi, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->total_budget_estimasi, 2) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_terpakai, 2) . '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_terpakai > 0) ? number_format($item->nominal_terpakai, 2) : '-';
          echo '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_terpakai > 0) ? number_format($item->total_terpakai, 2) : '-';
          echo '</td>';
          echo '<td class="text-center">' . number_format($qty_sisa_sebelum, 2) . '</td>';
          echo '<td class="text-right">' . number_format($sisa_budget_sebelum, 2) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_overbudget, 2) . '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_overbudget > 0) ? number_format($item->nominal_overbudget, 2) : '-';
          echo '</td>';
          echo '<td class="text-right">';
          echo ($item->qty_overbudget > 0) ? number_format($item->total_overbudget, 2) : '-';
          echo '</td>';

          echo '</tr>';

          $ttl_qty_estimasi_subcont_perusahaan += $item->qty_estimasi;
          $ttl_total_estimasi_subcont_perusahaan += $item->total_budget_estimasi;
          $ttl_qty_terpakai_subcont_perusahaan += $item->qty_terpakai;
          $ttl_total_terpakai_subcont_perusahaan += ($item->qty_terpakai > 0) ? $item->total_terpakai : 0;
          $ttl_qty_sebelum += $qty_sisa_sebelum;
          $ttl_sisa_budget_sebelum += $sisa_budget_sebelum;
          $ttl_qty_overbudget += $item->qty_overbudget;
          $ttl_total_overbudget += ($item->qty_overbudget > 0) ? $item->total_terpakai : 0;
        }
        ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="2" class="text-center">Grand Total</th>
          <th class="text-center"><?= number_format($ttl_qty_estimasi_subcont_perusahaan, 2) ?></th>
          <th></th>
          <th class="text-right"><?= number_format($ttl_total_estimasi_subcont_perusahaan, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_qty_terpakai_subcont_perusahaan, 2) ?></th>
          <th></th>
          <th class="text-right"><?= number_format($ttl_total_terpakai_subcont_perusahaan, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_qty_sebelum, 2) ?></th>
          <th class="text-right"><?= number_format($ttl_sisa_budget_sebelum, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_qty_overbudget, 2) ?></th>
          <th></th>
          <th class="text-right"><?= number_format($ttl_total_overbudget, 2) ?></th>
        </tr>
      </tfoot>
    </table>

    <br><br>

    <table class="table custom-table">
      <thead>
        <tr>
          <th rowspan="2" class="text-center" valign="middle">No.</th>
          <th rowspan="2" class="text-center" valign="middle" width="170">Item</th>
          <th colspan="3" class="text-center">Pengajuan</th>
          <th colspan="2" class="text-center valign-middle">Sisa Setelah Pengajuan</th>
        </tr>
        <tr>
          <th class="text-center">Qty</th>
          <th class="text-center">Price / Unit</th>
          <th class="text-center">Total Pengajuan</th>
          <th class="text-center">Sisa Qty</th>
          <th class="text-center">Sisa Budget</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $ttl_estimasi_subcont_perusahaan = 0;
        $ttl_pengajuan_subcont_perusahaan = 0;
        $ttl_aktual_subcont_perusahaan = 0;
        $ttl_sisa_subcont_perusahaan = 0;

        $no = 0;
        foreach ($list_kasbon_subcont_perusahaan as $item) {
          if ($item->qty_pengajuan <= 0) continue;
          $no++;

          $qty_tambahan = (isset($data_overbudget_subcont_perusahaan[$item->id_subcont])) ? $data_overbudget_subcont_perusahaan[$item->id_subcont]['qty_budget_tambahan'] : 0;
          $nominal_tambahan = (isset($data_overbudget_subcont_perusahaan[$item->id_subcont])) ? $data_overbudget_subcont_perusahaan[$item->id_subcont]['budget_tambahan'] : 0;

          $sisa_qty_setelah = (($item->qty_estimasi - $item->qty_terpakai) + $item->qty_overbudget - $item->qty_pengajuan);
          $sisa_budget_setelah = (($item->total_budget_estimasi - $item->total_terpakai) + $item->total_overbudget - $item->total_pengajuan);

          echo '<tr>';

          echo '<td class="text-center">' . $no . '</td>';
          echo '<td class="text-left">' . (!empty($item->nm_biaya) ? $item->nm_biaya : $item->nm_item) . '</td>';
          echo '<td class="text-center">' . number_format($item->qty_pengajuan, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->nominal_pengajuan, 2) . '</td>';
          echo '<td class="text-right">' . number_format($item->total_pengajuan, 2) . '</td>';
          echo '<td class="text-center">' . number_format($sisa_qty_setelah, 2) . '</td>';
          echo '<td class="text-right">' . number_format($sisa_budget_setelah, 2) . '</td>';
          echo '</tr>';

          $ttl_estimasi_subcont_perusahaan += $item->total_budget_estimasi;
          $ttl_pengajuan_subcont_perusahaan += $item->total_pengajuan;
          $ttl_aktual_subcont_perusahaan += ($item->aktual_terpakai - $item->qty_pengajuan);
          $ttl_sisa_subcont_perusahaan += ($item->sisa_budget - $item->total_pengajuan);
        }
        ?>
      </tbody>
      <tfoot>
        <tr>
          <th colspan="4" class="text-right">Grand Total</th>
          <th class="text-right"><?= number_format($ttl_pengajuan_subcont_perusahaan, 2) ?></th>
          <th class="text-center"><?= number_format($ttl_aktual_subcont_perusahaan, 2) ?></th>
          <th class="text-right"><?= number_format($ttl_sisa_subcont_perusahaan, 2) ?></th>
        </tr>
      </tfoot>
    </table>

    <br><br>


  </div>
</div>

<div class="box">
  <div class="box-body">
    <div class="col-md-6">
      <table style="width: 100%; margin-bottom: 15px;">
        <tr>
          <th style="padding: 5px;" width="140">Bank</th>
          <td style="padding: 5px;">
            <input type="text" name="kasbon_bank" id="" class="form-control form-control-sm" placeholder="- Bank -" value="<?= $header->bank ?>" readonly>
          </td>
        </tr>
        <tr>
          <th style="padding: 5px;" width="140">Bank Number</th>
          <td style="padding: 5px;">
            <input type="text" name="kasbon_bank_number" id="" class="form-control form-control-sm" placeholder="- Bank Number -" value="<?= $header->bank_number ?>" readonly>
          </td>
        </tr>
        <tr>
          <th style="padding: 5px;" width="140">Account Name</th>
          <td style="padding: 5px;">
            <input type="text" name="kasbon_bank_account" id="" class="form-control form-control-sm" placeholder="- Account Name -" value="<?= $header->bank_account ?>" readonly>
          </td>
        </tr>
      </table>

      <div class="form-group">
        <label for="">Reject Reason</label>
        <textarea name="reject_reason" class="form-control form-control-sm" id="" cols="30" rows="3" placeholder="Masukkan alasan reject jika pengajuan ditolak..."></textarea>
      </div>
    </div>

    <div class="col-md-6">
      <div class="panel panel-default" style="border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border-color: #e3e6f0;">
        <div class="panel-heading" style="background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0; font-weight: bold;">
          <i class="fa fa-paperclip"></i> Bukti Penggunaan (<?= !empty($list_bukti_penggunaan) ? count($list_bukti_penggunaan) : 0 ?>)
        </div>
        <div class="panel-body" style="padding: 10px;">
          <?php if (!empty($list_bukti_penggunaan)) : ?>
            <div class="list-group" style="margin-bottom: 0;">
              <?php foreach ($list_bukti_penggunaan as $bp) : ?>
                <div class="list-group-item" style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; margin-bottom: 4px; background: #fff; border: 1px solid #e3e6f0; border-radius: 4px;">
                  <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 75%;">
                    <i class="fa fa-file text-success" style="margin-right: 8px;"></i>
                    <b><?= basename($bp->upload_file) ?></b>
                  </span>
                  <a href="<?= base_url($bp->upload_file) ?>" target="_blank" class="btn btn-xs btn-primary">
                    <i class="fa fa-download"></i> Download
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else : ?>
            <div class="text-center text-muted" style="padding: 15px; border: 1px dashed #ccc; border-radius: 4px; font-size: 13px;">
              <i class="fa fa-file-o" style="font-size: 24px; color: #aaa; margin-bottom: 5px; display: block;"></i>
              Tidak ada bukti penggunaan yang diupload
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <div class="col-md-12">
      <a href="<?= base_url('approval_kasbon_project') ?>" class="btn btn-sm btn-danger">
        <i class="fa fa-arrow-left"></i> Back
      </a>
      <button type="button" class="btn btn-sm btn-danger reject_kasbon">
        <i class="fa fa-close"></i> Reject
      </button>
      <button type="button" class="btn btn-sm btn-primary approve_kasbon">
        <i class="fa fa-check"></i> Approve
      </button>
    </div>
  </div>
</div>


<script src="<?= base_url('assets/js/autoNumeric.js'); ?>"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>

<script>
  $(document).ready(function() {

  });

  $(document).on('click', '.reject_kasbon', function(e) {
    e.preventDefault();

    var id_kasbon = $('input[name="id_kasbon"]').val();
    var reject_reason = $('[name="reject_reason"]').val().trim();

    if (reject_reason == '') {
      Swal.fire({
            icon: 'warning',
            title: 'Warning !',
            text: 'Please fill the reject reason first !'
        });
    } else {
      Swal.fire({
            icon: 'warning',
            title: 'Are you sure ?',
            text: 'This data will be rejected !',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((res) => {
            if (res.isConfirmed) {
          $.ajax({
            type: 'post',
            url: siteurl + active_controller + 'reject_kasbon',
            data: {
              'id_kasbon': id_kasbon,
              'reject_reason': reject_reason
            },
            cache: false,
            dataType: 'json',
            beforeSend: function(data) {
              $('.reject_kasbon').attr('disabled', true);
            },
            success: function(result) {
              if (result.status == '1') {
                Swal.fire({
            icon: 'success',
            title: 'Success !',
            text: result.pesan
        }).then(() => {
                  window.location.href = siteurl + active_controller;
                });
              } else {
                Swal.fire({
            icon: 'warning',
            title: 'Failed !',
            text: result.pesan
        });
              }
            },
            error: function() {
              $('.reject_kasbon').attr('disabled', false);

              Swal.fire({
            icon: 'error',
            title: 'Error !',
            text: 'Please try again later !'
        });
            }
          });
        }
      });
    }
  });

  $(document).on('click', '.approve_kasbon', function(e) {
    e.preventDefault();

    var id_kasbon = $('input[name="id_kasbon"]').val();

    Swal.fire({
            icon: 'warning',
            title: 'Are you sure ?',
            text: 'This data will be approved !',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((res) => {
            if (res.isConfirmed) {
        $.ajax({
          type: 'post',
          url: siteurl + active_controller + 'approve_kasbon',
          data: {
            'id_kasbon': id_kasbon
          },
          cache: false,
          dataType: 'json',
          beforeSend: function(data) {
            $('.approve_kasbon').attr('disabled', true);
          },
          success: function(result) {
            if (result.status == '1') {
              Swal.fire({
            icon: 'success',
            title: 'Success !',
            text: result.pesan
        }).then(() => {
                window.location.href = siteurl + active_controller;
              });
            } else {
              Swal.fire({
            icon: 'warning',
            title: 'Failed !',
            text: result.pesan
        });
            }
          },
          error: function() {
            $('.approve_kasbon').attr('disabled', false);
            Swal.fire({
            icon: 'error',
            title: 'Error !',
            text: 'Please try again later !'
        });
          }
        });
      }
    });
  });
</script>