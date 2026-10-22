<?php
$ENABLE_ADD     = has_permission('Kasbon_Project.Add');
$ENABLE_MANAGE  = has_permission('Kasbon_Project.Manage');
$ENABLE_VIEW    = has_permission('Kasbon_Project.View');
$ENABLE_DELETE  = has_permission('Kasbon_Project.Delete');

$nm_pembuat = !empty($creator_user->nm_lengkap) ? $creator_user->nm_lengkap : (!empty($header->created_by) ? $header->created_by : '-');
$employee_id = !empty($creator_user->employee_id) ? $creator_user->employee_id : '';

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
<!-- <link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>"> -->
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

    .valign-middle {
        vertical-align: middle !important;
    }

    .tag-outside {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        font-size: 11px;
        color: #c76b00;
        background: #fff2df;
        border: 1px solid #f0d3a0;
        padding: 2px 8px;
        border-radius: 10px;
    }

    .input-error {
        border-color: #dc3545 !important;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
    }
</style>

<form action="" method="post" id="frm-data" enctype="multipart/form-data" novalidate>
    <input type="hidden" name="id_header" value="<?= $header->id ?>">
    <input type="hidden" name="id_spk_budgeting" value="<?= $header->id_spk_budgeting ?>">
    <input type="hidden" name="id_spk_penawaran" value="<?= $header->id_spk_penawaran ?>">
    <input type="hidden" name="id_penawaran" value="<?= $header->id_penawaran ?>">
    <input type="hidden" name="metode_pembayaran" value="<?= $header->metode_pembayaran ?>">

    <div class="box">
        <div class="box-header">

        </div>

        <div class="box-body">
            <?php if (!empty($header->sts_reject) || !empty($header->reject_reason)) : ?>
                <?php
                $rejecter = !empty($header->rejected_by) ? $this->db->get_where('users', ['id_user' => $header->rejected_by])->row() : null;
                $nm_rejecter = !empty($rejecter->nm_lengkap) ? $rejecter->nm_lengkap : (!empty($header->rejected_by) ? $header->rejected_by : 'Approver');
                $tgl_reject = !empty($header->rejected_date) ? date('d F Y', strtotime($header->rejected_date)) : '';
                ?>
                <div class="reject-banner" style="background:#fdeaea; border:1px solid #f6c6c6; border-radius:6px; padding:12px 16px; margin-bottom:20px;">
                    <div class="head" style="font-size:13px; font-weight:600; color:#c62e2e; display:flex; align-items:center; gap:8px;">
                        <i class="fa fa-times-circle" style="font-size:15px;"></i> Ditolak oleh <?= htmlspecialchars($nm_rejecter) ?><?= !empty($tgl_reject) ? ', ' . $tgl_reject : '' ?>
                    </div>
                    <div class="body" style="font-size:13px; color:#c62e2e; margin:4px 0 0 23px;">
                        <?= nl2br(htmlspecialchars($header->reject_reason)) ?>
                    </div>
                </div>
            <?php endif; ?>
            <table border="0" style="width: 100%;">
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
                    <td class="pd-5 valign-top" width="400"><?= $list_budgeting->nm_project ?></td>
                    <th class="pd-5 valign-top" width="150"></th>
                    <td class="pd-5 valign-top" width="400"></td>
                </tr>
                <tr>
                    <th class="pd-5 valign-top" width="150">Request By</th>
                    <td class="pd-5 valign-top" width="400">
                        <input type="hidden" name="request_by" id="request_by" value="<?= $header->created_by ?? $this->auth->user_id() ?>">
                        <div style="font-weight: 600; color: #333; padding-top: 5px;">
                            <i class="fa fa-user-circle text-primary"></i> <?= htmlspecialchars($nm_pembuat) ?>
                        </div>
                        <?php if (!$is_in_team) : ?>
                            <div class="tag-outside" style="margin-top: 5px;">
                                <i class="fa fa-exclamation-triangle"></i> <?= htmlspecialchars($nm_pembuat) ?> bukan bagian dari tim SPK ini<?= !empty($origin_spk) ? ' (tim asal: ' . htmlspecialchars($origin_spk) . ')' : '' ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <th class="pd-5 valign-top" width="150">Tanggal</th>
                    <td class="pd-5 valign-top" width="400">
                        <input type="date" name="tgl" id="" class="form-control form-control-sm" value="<?= $header->tgl ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <th class="pd-5 valign-top" width="150">Deskripsi / Keterangan <span class="text-danger">*</span></th>
                    <td class="pd-5 valign-top" width="400" colspan="3">
                        <textarea name="deskripsi" id="" class="form-control form-control-sm" required placeholder="Deskripsi / Keterangan"><?= $header->deskripsi ?></textarea>
                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="box">
        <div class="box-header">
            <h4 style="font-weight: 800;">Informasi Pengajuan</h4>
        </div>

        <div class="box-body">
            <table class="table custom-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="text-center">No.</th>
                        <th rowspan="2" class="text-center">Item</th>
                        <th colspan="3" class="text-center">Estimasi</th>
                        <th colspan="3" class="text-center">Terpakai</th>
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
                        <th class="text-center">Budget</th>
                        <th class="text-center">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 0;

                    $ttl_qty_estimasi = 0;
                    $ttl_total_estimasi = 0;
                    $ttl_qty_terpakai = 0;
                    $ttl_total_terpakai = 0;
                    $ttl_qty_overbudget = 0;
                    $ttl_total_overbudget = 0;

                    foreach ($list_data_others as $item) {
                        $no++;

                        $qty_pengajuan = (isset($list_arr_kasbon[$item->id_others])) ? $list_arr_kasbon[$item->id_others]['qty_pengajuan'] : 0;
                        $nominal_pengajuan = (isset($list_arr_kasbon[$item->id_others])) ? $list_arr_kasbon[$item->id_others]['nominal_pengajuan'] : 0;
                        $total_pengajuan = (isset($list_arr_kasbon[$item->id_others])) ? $list_arr_kasbon[$item->id_others]['total_pengajuan'] : 0;
                        $aktual_terpakai = (isset($list_arr_kasbon[$item->id_others])) ? $list_arr_kasbon[$item->id_others]['aktual_terpakai'] : 0;
                        if ($aktual_terpakai <= 0) {
                            $aktual_terpakai = $item->qty_final;
                        }
                        $sisa_budget = (isset($list_arr_kasbon[$item->id_others])) ? $list_arr_kasbon[$item->id_others]['sisa_budget'] : 0;
                        if ($sisa_budget <= 0) {
                            $sisa_budget = $item->total_final;
                        }

                        $qty_terpakai = (isset($list_arr_kasbon[$item->id_others]['qty_terpakai'])) ? $list_arr_kasbon[$item->id_others]['qty_terpakai'] : 0;
                        $nominal_terpakai = (isset($list_arr_kasbon[$item->id_others]['nominal_terpakai'])) ? $list_arr_kasbon[$item->id_others]['nominal_terpakai'] : 0;
                        $total_terpakai = (isset($list_arr_kasbon[$item->id_others]['total_terpakai'])) ? $list_arr_kasbon[$item->id_others]['total_terpakai'] : 0;

                        $qty_overbudget = (isset($list_arr_kasbon[$item->id_others]['qty_overbudget'])) ? $list_arr_kasbon[$item->id_others]['qty_overbudget'] : 0;
                        $nominal_overbudget = (isset($list_arr_kasbon[$item->id_others]['nominal_overbudget'])) ? $list_arr_kasbon[$item->id_others]['nominal_overbudget'] : 0;
                        $total_overbudget = (isset($list_arr_kasbon[$item->id_others]['total_overbudget'])) ? $list_arr_kasbon[$item->id_others]['total_overbudget'] : 0;

                        echo '<tr>';

                        echo '<td class="text-center">';
                        echo $no;
                        echo '<input type="hidden" name="detail_others[' . $no . '][id_others]" value="' . $item->id_others . '">';
                        echo '<input type="hidden" name="detail_others[' . $no . '][id_item]" value="' . $item->id_item . '">';
                        echo '<input type="hidden" name="detail_others[' . $no . '][nm_item]" value="' . (!empty($item->nm_biaya) ? $item->nm_biaya : $item->nm_item) . '">';
                        echo '</td>';

                        echo '<td>' . (!empty($item->nm_biaya) ? $item->nm_biaya : $item->nm_item) . '</td>';

                        echo '<td class="text-center">';
                        echo number_format($item->qty_final);
                        echo '<input type="hidden" name="detail_others[' . $no . '][qty_estimasi]" value="' . $item->qty_final . '">';
                        echo '</td>';

                        echo '<td class="text-right">';
                        echo number_format($item->price_unit_final, 2);
                        echo '<input type="hidden" name="detail_others[' . $no . '][price_unit_estimasi]" value="' . $item->price_unit_final . '">';
                        echo '</td>';

                        echo '<td class="text-right">';
                        echo number_format($item->total_final, 2);
                        echo '<input type="hidden" name="detail_others[' . $no . '][total_budget_estimasi]" value="' . $item->total_final . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo number_format($qty_terpakai);
                        echo '<input type="hidden" name="detail_others[' . $no . '][qty_terpakai]" value="' . $qty_terpakai . '">';
                        echo '</td>';

                        echo '<td class="text-right">';
                        echo ($qty_terpakai > 0) ? number_format($nominal_terpakai, 2) : '-';
                        echo '<input type="hidden" name="detail_others[' . $no . '][nominal_terpakai]" value="' . $nominal_terpakai . '">';
                        echo '</td>';

                        echo '<td class="text-right">';
                        echo ($qty_terpakai > 0) ? number_format($total_terpakai, 2) : '-';
                        echo '<input type="hidden" name="detail_others[' . $no . '][total_terpakai]" value="' . $total_terpakai . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo number_format($qty_overbudget);
                        echo '<input type="hidden" name="detail_others[' . $no . '][qty_overbudget]" value="' . $qty_overbudget . '">';
                        echo '</td>';

                        echo '<td class="text-right">';
                        echo ($qty_overbudget > 0) ? number_format($nominal_overbudget, 2) : '-';
                        echo '<input type="hidden" name="detail_others[' . $no . '][nominal_overbudget]" value="' . $nominal_overbudget . '">';
                        echo '</td>';

                        echo '<td class="text-right">';
                        echo ($qty_overbudget > 0) ? number_format($total_overbudget, 2) : '-';
                        echo '<input type="hidden" name="detail_others[' . $no . '][total_overbudget]" value="' . $total_overbudget . '">';
                        echo '<input type="hidden" name="detail_others[' . $no . '][sisa_budget]" value="' . $sisa_budget . '">';
                        echo '<input type="hidden" name="detail_others[' . $no . '][aktual_terpakai]" value="' . $aktual_terpakai . '">';
                        echo '</td>';

                        echo '</tr>';

                        // Sampai sini saja yang aktif

                        $ttl_qty_estimasi += $item->qty_final;
                        $ttl_total_estimasi += $item->total_final;
                        $ttl_qty_terpakai += $qty_terpakai;
                        $ttl_total_terpakai += ($qty_terpakai > 0) ? $total_terpakai : 0;
                        $ttl_qty_overbudget += $qty_overbudget;
                        $ttl_total_overbudget += ($qty_overbudget > 0) ? $total_overbudget : 0;
                    }

                    foreach ($list_data_others_custom as $item) {
                        $no++;

                        $qty_pengajuan = (isset($list_arr_kasbon[$item->id])) ? $list_arr_kasbon[$item->id]['qty_pengajuan'] : 0;
                        $nominal_pengajuan = (isset($list_arr_kasbon[$item->id])) ? $list_arr_kasbon[$item->id]['nominal_pengajuan'] : 0;
                        $total_pengajuan = (isset($list_arr_kasbon[$item->id])) ? $list_arr_kasbon[$item->id]['total_pengajuan'] : 0;
                        $aktual_terpakai = (isset($list_arr_kasbon[$item->id])) ? $list_arr_kasbon[$item->id]['aktual_terpakai'] : 0;
                        if ($aktual_terpakai <= 0) {
                            $aktual_terpakai = $item->estimasi_qty;
                        }
                        $sisa_budget = (isset($list_arr_kasbon[$item->id])) ? $list_arr_kasbon[$item->id]['sisa_budget'] : 0;
                        if ($sisa_budget <= 0) {
                            $sisa_budget = $item->estimasi_total;
                        }

                        $qty_terpakai = (isset($list_arr_kasbon[$item->id]['qty_terpakai'])) ? $list_arr_kasbon[$item->id]['qty_terpakai'] : 0;
                        $nominal_terpakai = (isset($list_arr_kasbon[$item->id]['nominal_terpakai'])) ? $list_arr_kasbon[$item->id]['nominal_terpakai'] : 0;
                        $total_terpakai = (isset($list_arr_kasbon[$item->id]['total_terpakai'])) ? $list_arr_kasbon[$item->id]['total_terpakai'] : 0;

                        $qty_overbudget = (isset($list_arr_kasbon[$item->id]['qty_overbudget'])) ? $list_arr_kasbon[$item->id]['qty_overbudget'] : 0;
                        $nominal_overbudget = (isset($list_arr_kasbon[$item->id]['nominal_overbudget'])) ? $list_arr_kasbon[$item->id]['nominal_overbudget'] : 0;
                        $total_overbudget = (isset($list_arr_kasbon[$item->id]['total_overbudget'])) ? $list_arr_kasbon[$item->id]['total_overbudget'] : 0;

                        echo '<tr>';

                        echo '<td class="text-center">';
                        echo $no;
                        echo '<input type="hidden" name="detail_others[' . $no . '][id_others]" value="' . $item->id . '">';
                        echo '<input type="hidden" name="detail_others[' . $no . '][id_item]" value="' . $item->id . '">';
                        echo '<input type="hidden" name="detail_others[' . $no . '][nm_item]" value="' . $item->nm_item . '">';
                        echo '<input type="hidden" name="detail_others[' . $no . '][custom_others]" value="1">';
                        echo '</td>';

                        echo '<td>' . $item->nm_item . '</td>';

                        echo '<td class="text-center">';
                        echo number_format($item->estimasi_qty);
                        echo '<input type="hidden" name="detail_others[' . $no . '][qty_estimasi]" value="' . $item->estimasi_qty . '">';
                        echo '</td>';

                        echo '<td class="text-right">';
                        echo number_format($item->estimasi_harga, 2);
                        echo '<input type="hidden" name="detail_others[' . $no . '][price_unit_estimasi]" value="' . $item->estimasi_harga . '">';
                        echo '</td>';

                        echo '<td class="text-right">';
                        echo number_format($item->estimasi_total, 2);
                        echo '<input type="hidden" name="detail_others[' . $no . '][total_budget_estimasi]" value="' . $item->estimasi_total . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo number_format($qty_terpakai);
                        echo '<input type="hidden" name="detail_others[' . $no . '][qty_terpakai]" value="' . $qty_terpakai . '">';
                        echo '</td>';

                        echo '<td class="text-right">';
                        echo ($qty_terpakai > 0) ? number_format($nominal_terpakai, 2) : '-';
                        echo '<input type="hidden" name="detail_others[' . $no . '][nominal_terpakai]" value="' . $nominal_terpakai . '">';
                        echo '</td>';

                        echo '<td class="text-right">';
                        echo ($qty_terpakai > 0) ? number_format($total_terpakai, 2) : '-';
                        echo '<input type="hidden" name="detail_others[' . $no . '][total_terpakai]" value="' . $total_terpakai . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo number_format($qty_overbudget);
                        echo '<input type="hidden" name="detail_others[' . $no . '][qty_overbudget]" value="' . $qty_overbudget . '">';
                        echo '</td>';

                        echo '<td class="text-right">';
                        echo ($qty_overbudget > 0) ? number_format($nominal_overbudget, 2) : '-';
                        echo '<input type="hidden" name="detail_others[' . $no . '][nominal_overbudget]" value="' . $nominal_overbudget . '">';
                        echo '</td>';

                        echo '<td class="text-right">';
                        echo ($qty_overbudget > 0) ? number_format($total_overbudget, 2) : '-';
                        echo '<input type="hidden" name="detail_others[' . $no . '][total_overbudget]" value="' . $total_overbudget . '">';
                        echo '<input type="hidden" name="detail_others[' . $no . '][sisa_budget]" value="' . $sisa_budget . '">';
                        echo '<input type="hidden" name="detail_others[' . $no . '][aktual_terpakai]" value="' . $aktual_terpakai . '">';
                        echo '</td>';

                        echo '</tr>';

                        // Sampai sini saja yang aktif

                        $ttl_qty_estimasi += $item->estimasi_qty;
                        $ttl_total_estimasi += $item->estimasi_total;
                        $ttl_qty_terpakai += $qty_terpakai;
                        $ttl_total_terpakai += ($qty_terpakai > 0) ? $total_terpakai : 0;
                        $ttl_qty_overbudget += $qty_overbudget;
                        $ttl_total_overbudget += ($qty_overbudget > 0) ? $total_overbudget : 0;
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" class="text-center">Total</th>
                        <th class="text-center"><?= number_format($ttl_qty_estimasi, 2) ?></th>
                        <th></th>
                        <th class="text-right"><?= number_format($ttl_total_estimasi, 2) ?></th>
                        <th class="text-center"><?= number_format($ttl_qty_terpakai, 2) ?></th>
                        <th></th>
                        <th class="text-right"><?= number_format($ttl_total_terpakai, 2) ?></th>
                        <th class="text-center"><?= number_format($ttl_qty_overbudget, 2) ?></th>
                        <th></th>
                        <th class="text-right"><?= number_format($ttl_total_overbudget, 2) ?></th>
                    </tr>
                </tfoot>
            </table>

            <br><br>

            <h4 style="font-weight: 800;">Pengajuan</h4>

            <table class="table custom-table">
                <thead>
                    <tr>
                        <th rowspan="2" class="text-center">No</th>
                        <th rowspan="2" class="text-center">Item</th>
                        <th colspan="3" class="text-center">Pengajuan</th>
                    </tr>
                    <tr>
                        <th class="text-center">Qty</th>
                        <th class="text-center">Price / Unit</th>
                        <th class="text-center">Total Pengajuan</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 0;

                    $ttl_qty_pengajuan = 0;
                    $ttl_total_pengajuan = 0;
                    foreach ($list_data_others as $item) {
                        if (isset($list_arr_kasbon[$item->id_others])) {
                            $no++;

                            $qty_tambahan = (isset($data_overbudget_others[$item->id_others])) ? $data_overbudget_others[$item->id_others]['qty_budget_tambahan'] : 0;
                            $nominal_tambahan = (isset($data_overbudget_others[$item->id_others])) ? $data_overbudget_others[$item->id_others]['budget_tambahan'] : 0;
                            $pengajuan_budget = (isset($data_overbudget_others[$item->id_others])) ? $data_overbudget_others[$item->id_others]['pengajuan_budget'] : 0;

                            $aktual_terpakai = (isset($data_kasbon_others[$item->id_others]['ttl_qty_pengajuan'])) ? $data_kasbon_others[$item->id_others]['ttl_qty_pengajuan'] : 0;
                            $sisa_budget = (isset($data_kasbon_others[$item->id_others]['ttl_total_pengajuan'])) ? (($item->price_unit_final * $item->qty_final) - $data_kasbon_others[$item->id_others]['ttl_total_pengajuan']) : ($item->price_unit_final * $item->qty_final);

                            $readonly = '';
                            if (($sisa_budget + ($qty_tambahan * $nominal_tambahan)) <= 0) {
                                $readonly = 'readonly';
                            }

                            $qty_pengajuan = (isset($list_arr_kasbon[$item->id_others]['qty_pengajuan'])) ? $list_arr_kasbon[$item->id_others]['qty_pengajuan'] : '0';
                            $nominal_pengajuan = (isset($list_arr_kasbon[$item->id_others]['nominal_pengajuan'])) ? $list_arr_kasbon[$item->id_others]['nominal_pengajuan'] : '0';
                            $total_pengajuan = (isset($list_arr_kasbon[$item->id_others]['total_pengajuan'])) ? $list_arr_kasbon[$item->id_others]['total_pengajuan'] : '0';

                            echo '<tr>';

                            echo '<td class="text-center">' . $no . '</td>';

                            echo '<td>';
                            echo (!empty($item->nm_biaya) ? $item->nm_biaya : $item->nm_item);
                            echo '<input type="hidden" name="detail_others[' . $no . '][id_others]" value="' . $item->id_others . '">';
                            echo '<input type="hidden" name="detail_others[' . $no . '][id_item]" value="' . $item->id_item . '">';
                            echo '<input type="hidden" name="detail_others[' . $no . '][nm_item]" value="' . $item->nm_item . '">';
                            echo '</td>';

                            echo '<td>';
                            echo '<input type="number" name="detail_others[' . $no . '][qty_pengajuan]" class="form-control form-control-sm text-right" onchange="hitung_all_pengajuan()" step="0.01" value="' . $qty_pengajuan . '" ' . $readonly . '>';
                            echo '</td>';

                            echo '<td>';
                            echo '<input type="text" name="detail_others[' . $no . '][nominal_pengajuan]" class="form-control form-control-sm text-right auto_num" onchange="hitung_all_pengajuan()" data-no="' . $no . '" data-budget="' . $item->price_unit_final . '" value="' . $nominal_pengajuan . '" ' . $readonly . '>';
                            echo '</td>';

                            echo '<td>';
                            echo '<input type="text" name="detail_others[' . $no . '][total_pengajuan]" class="form-control form-control-sm text-right auto_num" onchange="hitung_all_pengajuan()" value="' . $total_pengajuan . '" readonly>';
                            echo '</td>';

                            echo '</tr>';

                            $ttl_qty_pengajuan += $qty_pengajuan;
                            $ttl_total_pengajuan += $total_pengajuan;
                        }
                    }

                    foreach ($list_data_others_custom as $item) {
                        if (isset($list_arr_kasbon[$item->id])) {
                            $no++;

                            $qty_tambahan = (isset($data_overbudget_others[$item->id])) ? $data_overbudget_others[$item->id]['qty_budget_tambahan'] : 0;
                            $nominal_tambahan = (isset($data_overbudget_others[$item->id])) ? $data_overbudget_others[$item->id]['budget_tambahan'] : 0;
                            $pengajuan_budget = (isset($data_overbudget_others[$item->id])) ? $data_overbudget_others[$item->id]['pengajuan_budget'] : 0;

                            $aktual_terpakai = (isset($data_kasbon_others[$item->id]['ttl_qty_pengajuan'])) ? $data_kasbon_others[$item->id]['ttl_qty_pengajuan'] : 0;
                            $sisa_budget = (isset($data_kasbon_others[$item->id]['ttl_total_pengajuan'])) ? (($item->estimasi_harga * $item->estimasi_qty) - $data_kasbon_others[$item->id]['ttl_total_pengajuan']) : ($item->estimasi_harga * $item->estimasi_qty);

                            $readonly = '';
                            if (($sisa_budget + ($qty_tambahan * $nominal_tambahan)) <= 0) {
                                $readonly = 'readonly';
                            }

                            $qty_pengajuan = (isset($list_arr_kasbon[$item->id]['qty_pengajuan'])) ? $list_arr_kasbon[$item->id]['qty_pengajuan'] : '0';
                            $nominal_pengajuan = (isset($list_arr_kasbon[$item->id]['nominal_pengajuan'])) ? $list_arr_kasbon[$item->id]['nominal_pengajuan'] : '0';
                            $total_pengajuan = (isset($list_arr_kasbon[$item->id]['total_pengajuan'])) ? $list_arr_kasbon[$item->id]['total_pengajuan'] : '0';

                            echo '<tr>';

                            echo '<td class="text-center">' . $no . '</td>';

                            echo '<td>';
                            echo $item->nm_item;
                            echo '<input type="hidden" name="detail_others[' . $no . '][id_others]" value="' . $item->id . '">';
                            echo '<input type="hidden" name="detail_others[' . $no . '][id_item]" value="' . $item->id . '">';
                            echo '<input type="hidden" name="detail_others[' . $no . '][nm_item]" value="' . $item->nm_item . '">';
                            echo '</td>';

                            echo '<td>';
                            echo '<input type="number" name="detail_others[' . $no . '][qty_pengajuan]" class="form-control form-control-sm text-right" onchange="hitung_all_pengajuan()" step="0.01" value="' . $qty_pengajuan . '" ' . $readonly . '>';
                            echo '</td>';

                            echo '<td>';
                            echo '<input type="text" name="detail_others[' . $no . '][nominal_pengajuan]" class="form-control form-control-sm text-right auto_num hitung_per_price" data-no="' . $no . '" data-budget="' . $item->estimasi_harga . '" value="' . $nominal_pengajuan . '" ' . $readonly . '>';
                            echo '</td>';

                            echo '<td>';
                            echo '<input type="text" name="detail_others[' . $no . '][total_pengajuan]" class="form-control form-control-sm text-right auto_num" onchange="hitung_all_pengajuan()" value="' . $total_pengajuan . '" readonly>';
                            echo '</td>';

                            echo '</tr>';

                            $ttl_qty_pengajuan += $qty_pengajuan;
                            $ttl_total_pengajuan += $total_pengajuan;
                        }
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" class="text-center">Grand Total</th>
                        <th class="text-center ttl_qty_pengajuan"><?= number_format($ttl_qty_pengajuan, 2) ?></th>
                        <th></th>
                        <th class="text-center ttl_pengajuan"><?= number_format($ttl_total_pengajuan, 2) ?></th>
                    </tr>
                </tfoot>
            </table>

            <br><br>

            <div class="col-md-6">
                <table style="width: 100%">
                    <tr>
                        <th style="padding: 5px;">Bank <span class="text-danger">*</span></th>
                        <td style="padding: 5px;">
                            <input type="text" name="kasbon_bank" id="" class="form-control form-control-sm" placeholder="- Bank -" value="<?= $header->bank ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding: 5px;">Bank Number <span class="text-danger">*</span></th>
                        <td style="padding: 5px;">
                            <input type="text" name="kasbon_bank_number" id="" class="form-control form-control-sm" placeholder="- Bank Number -" value="<?= $header->bank_number ?>" required>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding: 5px;">Account Name <span class="text-danger">*</span></th>
                        <td style="padding: 5px;">
                            <input type="text" name="kasbon_bank_account" id="" class="form-control form-control-sm" placeholder="- Account Name -" value="<?= $header->bank_account ?>" required>
                        </td>
                    </tr>
                </table>
            </div>

            <div class="col-md-6">
                <div class="panel panel-default" style="border-radius: 6px; box-shadow: 0 1px 3px rgba(0,0,0,0.08); border-color: #e3e6f0;">
                    <div class="panel-heading" style="background-color: #f8f9fc; border-bottom: 1px solid #e3e6f0; font-weight: bold; display: flex; justify-content: space-between; align-items: center;">
                        <span><i class="fa fa-paperclip"></i> Bukti Penggunaan</span>
                        <button type="button" class="btn btn-xs btn-primary" id="btn-pilih-bukti">
                            <i class="fa fa-plus"></i> Upload Bukti
                        </button>
                    </div>
                    <div class="panel-body" style="padding: 10px;">
                        <input type="file" id="input-bukti-file" multiple style="display: none;">
                        
                        <?php if (!empty($list_bukti_penggunaan)) : ?>
                            <div style="margin-bottom: 10px;">
                                <small class="text-muted" style="font-weight: bold;">File Terupload:</small>
                                <div class="list-group" style="margin-top: 5px; margin-bottom: 5px;">
                                    <?php foreach ($list_bukti_penggunaan as $bp) : ?>
                                        <div class="list-group-item" id="row-bukti-<?= $bp->id ?>" style="display: flex; justify-content: space-between; align-items: center; padding: 6px 12px; margin-bottom: 4px; background: #fcfcfc; border: 1px solid #e3e6f0; border-radius: 4px;">
                                            <span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 80%;">
                                                <i class="fa fa-file text-success" style="margin-right: 8px;"></i>
                                                <a href="<?= base_url($bp->upload_file) ?>" target="_blank" style="font-weight: 600;">
                                                    <?= basename($bp->upload_file) ?>
                                                </a>
                                            </span>
                                            <button type="button" class="btn btn-xs btn-danger btn-del-bukti" data-id="<?= $bp->id ?>" title="Hapus File">
                                                <i class="fa fa-trash"></i>
                                            </button>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div id="dropzone-bukti" style="border: 2px dashed #b4c6dc; border-radius: 6px; background: #fdfdfe; padding: 18px; text-align: center; cursor: pointer; transition: all 0.2s ease-in-out;">
                            <i class="fa fa-cloud-upload" style="font-size: 28px; color: #3c8dbc; margin-bottom: 6px; display: block;"></i>
                            <span style="font-size: 13px; color: #555;"><b>Tarik & letakkan file di sini</b> atau klik untuk memilih file</span>
                        </div>

                        <div id="container-bukti-list" style="margin-top: 10px;"></div>
                    </div>
                </div>
            </div>

            <div class="col-md-12 mt-5">
                <a href="<?= base_url('kasbon_project/add_kasbon/' . urlencode(str_replace('/', '|', $list_budgeting->id_spk_budgeting))) ?>" class="btn btn-sm btn-danger">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
                <button type="submit" class="btn btn-sm btn-success">
                    <i class="fa fa-save"></i> Save
                </button>
            </div>
        </div>
    </div>
</form>


<script src="<?= base_url('assets/js/autoNumeric.js'); ?>"></script>
<script>
    $(document).ready(function() {
        $('.auto_num').autoNumeric();
        $('.select2').select2({
            width: '100%'
        });

        var spkTeamEmployeeIds = [
            <?= json_encode((string)($list_budgeting->id_project_leader ?? '')) ?>,
            <?= json_encode((string)($list_budgeting->id_sales ?? '')) ?>,
            <?= json_encode((string)($list_budgeting->id_konsultan_1 ?? '')) ?>,
            <?= json_encode((string)($list_budgeting->id_konsultan_2 ?? '')) ?>
        ].map(String).filter(Boolean);

        var spkTeamNames = [
            <?= json_encode(strtolower(trim($list_budgeting->nm_project_leader ?? ''))) ?>,
            <?= json_encode(strtolower(trim($list_budgeting->nm_sales ?? ''))) ?>,
            <?= json_encode(strtolower(trim($list_budgeting->nm_konsultan_1 ?? ''))) ?>,
            <?= json_encode(strtolower(trim($list_budgeting->nm_konsultan_2 ?? ''))) ?>
        ].filter(Boolean);

        function checkRequestByTeam() {
            var selectedEmployeeId = String($('#request_by_employee_id').val() || '').trim();
            var selectedName = ($('#request_by_name').val() || '').trim();
            var selectedNameLower = selectedName.toLowerCase();

            var isInTeam = false;
            if (selectedEmployeeId && spkTeamEmployeeIds.includes(selectedEmployeeId)) {
                isInTeam = true;
            } else if (!selectedEmployeeId && spkTeamNames.includes(selectedNameLower)) {
                // Fallback nama jika employee_id belum diisi di data user
                isInTeam = true;
            }

            if ($('#request_by').val() && !isInTeam) {
                $('#tag_outside_text').text(selectedName + ' bukan bagian dari tim SPK ini');
                $('#tag_outside_spk').show();
            } else {
                $('#tag_outside_spk').hide();
            }
        }

        checkRequestByTeam();
    });

    var selectedBuktiFiles = [];

    $(document).on('click', '#btn-pilih-bukti, #dropzone-bukti', function() {
        $('#input-bukti-file').click();
    });

    $(document).on('change', '#input-bukti-file', function() {
        var files = this.files;
        for (var i = 0; i < files.length; i++) {
            selectedBuktiFiles.push(files[i]);
        }
        this.value = '';
        renderSelectedBukti();
    });

    // Drag and drop handlers
    $(document).on('dragover dragenter', '#dropzone-bukti', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).css({
            'border-color': '#3c8dbc',
            'background': '#eef5fb'
        });
    });

    $(document).on('dragleave dragend drop', '#dropzone-bukti', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $(this).css({
            'border-color': '#b4c6dc',
            'background': '#fdfdfe'
        });
    });

    $(document).on('drop', '#dropzone-bukti', function(e) {
        var files = e.originalEvent.dataTransfer.files;
        if (files && files.length > 0) {
            for (var i = 0; i < files.length; i++) {
                selectedBuktiFiles.push(files[i]);
            }
            renderSelectedBukti();
        }
    });

    function formatBytes(bytes) {
        if (bytes === 0) return '0 Bytes';
        var k = 1024;
        var sizes = ['Bytes', 'KB', 'MB', 'GB'];
        var i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
    }

    function renderSelectedBukti() {
        var html = '';
        if (selectedBuktiFiles.length > 0) {
            html += '<small class="text-muted" style="font-weight: bold;">File Baru Dipilih (' + selectedBuktiFiles.length + '):</small><div class="list-group" style="margin-top: 5px; margin-bottom: 0;">';
            for (var i = 0; i < selectedBuktiFiles.length; i++) {
                var file = selectedBuktiFiles[i];
                html += '<div class="list-group-item" style="display: flex; justify-content: space-between; align-items: center; padding: 6px 12px; margin-bottom: 4px; background: #fff; border: 1px solid #e3e6f0; border-radius: 4px;">' +
                    '<span style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap; max-width: 80%;">' +
                    '<i class="fa fa-file-text-o text-primary" style="margin-right: 8px;"></i>' +
                    '<b>' + file.name + '</b> <small class="text-muted">(' + formatBytes(file.size) + ')</small>' +
                    '</span>' +
                    '<button type="button" class="btn btn-xs btn-danger btn-remove-selected-bukti" data-index="' + i + '" title="Hapus"><i class="fa fa-trash"></i></button>' +
                    '</div>';
            }
            html += '</div>';
        }
        $('#container-bukti-list').html(html);
    }

    $(document).on('click', '.btn-remove-selected-bukti', function(e) {
        e.stopPropagation();
        var index = $(this).data('index');
        selectedBuktiFiles.splice(index, 1);
        renderSelectedBukti();
    });

    $(document).on('click', '.btn-del-bukti', function() {
        var id = $(this).data('id');
        Swal.fire({
            icon: 'warning',
            title: 'Are you sure ?',
            text: 'File bukti penggunaan ini akan dihapus permanen !',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((res) => {
            if (res.isConfirmed) {
                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'del_bukti_penggunaan',
                    data: { id: id },
                    dataType: 'JSON',
                    success: function(result) {
                        if (result.status == 1) {
                            Swal.fire({
            icon: 'success',
            title: 'Success !',
            text: result.pesan,
            timer: 1500
        });
                            $('#row-bukti-' + id).remove();
                        } else {
                            Swal.fire({
            icon: 'warning',
            title: 'Failed !',
            text: result.pesan
        });
                        }
                    },
                    error: function() {
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

    function number_format(number, decimals, dec_point, thousands_sep) {
        // Strip all characters but numerical ones.
        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function(n, prec) {
                var k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
            };
        // Fix for IE parseFloat(0.55).toFixed(0) = 0;
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
        }
        return s.join(dec);
    }

    function get_num(nilai = null) {
        if (nilai !== '' && nilai !== null) {
            nilai = nilai.split(',').join('');
            nilai = parseFloat(nilai);
        } else {
            nilai = 0;
        }

        return nilai;
    }

    function hitung_all_pengajuan() {
        var no = "<?= $no ?>";

        var ttl_qty = 0;
        var ttl_price = 0;
        var ttl_total = 0;

        for (i = 1; i <= no; i++) {
            var qty_pengajuan = $('input[name="detail_others[' + i + '][qty_pengajuan]"]').val();
            if (isNaN(qty_pengajuan) || qty_pengajuan == '') {
                qty_pengajuan = 0;
            } else {
                qty_pengajuan = parseFloat(qty_pengajuan);
            }

            var nominal_pengajuan = get_num($('input[name="detail_others[' + i + '][nominal_pengajuan]"]').val());
            // if (qty_pengajuan < 1) {
            //     var total_pengajuan = get_num($('input[name="detail_others[' + i + '][total_pengajuan]"]').val());
            // } else {
            var total_pengajuan = (nominal_pengajuan * qty_pengajuan);
            // }

            $('input[name="detail_others[' + i + '][total_pengajuan]"]').autoNumeric('set', total_pengajuan);

            ttl_qty += qty_pengajuan;
            ttl_price += nominal_pengajuan;
            ttl_total += total_pengajuan;
        }

        $('.ttl_pengajuan').html(number_format(ttl_total));
        $('.ttl_qty_pengajuan').html(number_format(ttl_qty, 2));
    }

    $(document).on('change', '.hitung_per_price', function() {
        var no = $(this).data('no');
        var budget = $(this).data('budget');
        var pengajuan = get_num($(this).val());

        var qty = (pengajuan / budget);


        $('input[name="detail_others[' + no + '][qty_pengajuan]"]').val(qty.toFixed(2));
        $('input[name="detail_others[' + no + '][total_pengajuan]"]').autoNumeric('set', pengajuan);

        hitung_all_pengajuan();
    });

    $(document).on('input change', '.input-error', function() {
        if ($(this).val().trim() !== '') {
            $(this).removeClass('input-error');
        }
    });

    $(document).on('submit', '#frm-data', function(e) {
        e.preventDefault();

        $('.input-error').removeClass('input-error');
        var errors = [];

        var elDeskripsi = $('textarea[name="deskripsi"]');
        if (elDeskripsi.val().trim() === '') {
            errors.push({ name: 'Deskripsi / Keterangan', el: elDeskripsi });
            elDeskripsi.addClass('input-error');
        }

        var elBank = $('input[name="kasbon_bank"]');
        if (elBank.val().trim() === '') {
            errors.push({ name: 'Bank', el: elBank });
            elBank.addClass('input-error');
        }

        var elBankNumber = $('input[name="kasbon_bank_number"]');
        if (elBankNumber.val().trim() === '') {
            errors.push({ name: 'Bank Number (Nomor Rekening)', el: elBankNumber });
            elBankNumber.addClass('input-error');
        }

        var elBankAccount = $('input[name="kasbon_bank_account"]');
        if (elBankAccount.val().trim() === '') {
            errors.push({ name: 'Account Name (Nama Pemilik Rekening)', el: elBankAccount });
            elBankAccount.addClass('input-error');
        }

        var no = "<?= $no ?>";
        var ttl_propose = 0;

        for (i = 1; i <= no; i++) {
            var qty_pengajuan = get_num($('input[name="detail_others[' + i + '][qty_pengajuan]"]').val());
            var nominal_pengajuan = get_num($('input[name="detail_others[' + i + '][nominal_pengajuan]"]').val());

            if (qty_pengajuan > 0 && qty_pengajuan < 1) {
                qty_pengajuan = 1;
            }
            ttl_propose += (qty_pengajuan * nominal_pengajuan);
        }

        if (ttl_propose <= 0) {
            errors.push({ name: 'Item Pengajuan (minimal 1 item harus diajukan dengan nominal > 0)', el: $('input[name="detail_others[1][qty_pengajuan]"]') });
            $('input[name="detail_others[1][qty_pengajuan]"]').addClass('input-error');
        }

        if (errors.length > 0) {
            var htmlMsg = '<div style="text-align: left; padding: 0 10px;">' +
                '<p style="margin-bottom: 8px; font-weight: 600;">Masih ada inputan mandatory yang belum diisi:</p>' +
                '<ul style="margin-bottom: 0; padding-left: 20px; color: #d9534f;">';
            errors.forEach(function(item) {
                htmlMsg += '<li style="margin-bottom: 4px;"><b>' + item.name + '</b></li>';
            });
            htmlMsg += '</ul></div>';

            Swal.fire({
                icon: 'warning',
                title: 'Input Mandatory Belum Lengkap',
                html: htmlMsg,
                confirmButtonColor: '#3085d6',
                confirmButtonText: 'OK'
            }).then(function() {
                if (errors[0].el && errors[0].el.length) {
                    errors[0].el.focus();
                    $('html, body').animate({
                        scrollTop: errors[0].el.offset().top - 100
                    }, 300);
                }
            });
            return false;
        }

        var valid = 1;

        if (valid == '0') {
            Swal.fire({
                icon: 'warning',
                title: 'Warning !',
                text: 'Nominal pengajuan melebihi Sisa Budget !'
            });
        } else {
            Swal.fire({
            icon: 'warning',
            title: 'Are you sure ?',
            text: 'This data will be saved !',
            showCancelButton: true,
            confirmButtonColor: '#3085d6',
            cancelButtonColor: '#d33'
        }).then((res) => {
            if (res.isConfirmed) {
                    var formData = new FormData($('#frm-data')[0]);
                    for (var i = 0; i < selectedBuktiFiles.length; i++) {
                        formData.append('bukti_penggunaan[]', selectedBuktiFiles[i]);
                    }

                    $.ajax({
                        type: 'post',
                        url: siteurl + active_controller + 'update_kasbon_others',
                        data: formData,
                        cache: false,
                        processData: false,
                        contentType: false,
                        dataType: 'JSON',
                        success: function(result) {
                            if (result.status == '1') {
                                Swal.fire({
            icon: 'success',
            title: 'Success !',
            text: result.pesan
        }).then(() => {
                                    window.location.href = siteurl + active_controller + "add_kasbon/<?= urlencode(str_replace('/', '|', $list_budgeting->id_spk_budgeting)) ?>"
                                });
                            } else {
                                Swal.fire({
            icon: 'warning',
            title: 'Failed !',
            text: result.pesan
        });
                            }
                        },
                        error: function(result) {
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

    })
</script>