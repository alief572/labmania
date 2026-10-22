<?php
$ENABLE_ADD     = has_permission('Kasbon_Project.Add');
$ENABLE_MANAGE  = has_permission('Kasbon_Project.Manage');
$ENABLE_VIEW    = has_permission('Kasbon_Project.View');
$ENABLE_DELETE  = has_permission('Kasbon_Project.Delete');

$show_reject_reason = 'd-none';
if ($header->reject_reason !== null) {
    $show_reject_reason = '';
}

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

    .d-none {
        display: none;
    }
</style>

<form action="" method="post" id="frm-data" enctype="multipart/form-data">
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

                    foreach ($list_data_lab as $item) {
                        $no++;

                        $qty_terpakai = (isset($list_arr_kasbon[$item->id_lab]['qty_terpakai'])) ? $list_arr_kasbon[$item->id_lab]['qty_terpakai'] : 0;
                        $nominal_terpakai = (isset($list_arr_kasbon[$item->id_lab]['nominal_terpakai'])) ? $list_arr_kasbon[$item->id_lab]['nominal_terpakai'] : 0;
                        $total_terpakai = (isset($list_arr_kasbon[$item->id_lab]['total_terpakai'])) ? $list_arr_kasbon[$item->id_lab]['total_terpakai'] : 0;

                        $qty_overbudget = (isset($list_arr_kasbon[$item->id_lab]['qty_overbudget'])) ? $list_arr_kasbon[$item->id_lab]['qty_overbudget'] : 0;
                        $nominal_overbudget = (isset($list_arr_kasbon[$item->id_lab]['nominal_overbudget'])) ? $list_arr_kasbon[$item->id_lab]['nominal_overbudget'] : 0;
                        $total_overbudget = (isset($list_arr_kasbon[$item->id_lab]['total_overbudget'])) ? $list_arr_kasbon[$item->id_lab]['total_overbudget'] : 0;

                        echo '<tr>';

                        echo '<td class="text-center">' . $no . '</td>';
                        echo '<td>' . (!empty($item->nm_biaya) ? $item->nm_biaya : $item->nm_item) . '</td>';
                        echo '<td class="text-center">' . number_format($item->qty_final, 2) . '</td>';
                        echo '<td class="text-right">' . number_format($item->price_unit_final, 2) . '</td>';
                        echo '<td class="text-right">' . number_format($item->total_final, 2) . '</td>';
                        echo '<td class="text-center">' . number_format($qty_terpakai, 2) . '</td>';
                        echo '<td class="text-right">';
                        echo ($qty_terpakai > 0) ? number_format($nominal_terpakai, 2) : '-';
                        echo '</td>';
                        echo '<td class="text-right">';
                        echo ($qty_terpakai > 0) ? number_format($total_terpakai, 2) : '-';
                        echo '</td>';
                        echo '<td class="text-center">' . number_format($qty_overbudget, 2) . '</td>';
                        echo '<td class="text-right">';
                        echo ($qty_overbudget > 0) ? number_format($nominal_overbudget, 2) : '-';
                        echo '</td>';
                        echo '<td class="text-right">';
                        echo ($qty_overbudget > 0) ? number_format($total_overbudget, 2) : '-';
                        echo '</td>';

                        echo '</tr>';

                        $ttl_qty_estimasi += $item->qty_final;
                        $ttl_total_estimasi += $item->total_final;
                        $ttl_qty_terpakai += $qty_terpakai;
                        $ttl_total_terpakai += ($qty_terpakai > 0) ? $total_terpakai : 0;
                        $ttl_qty_overbudget += $qty_overbudget;
                        $ttl_total_overbudget += ($qty_overbudget > 0) ? $total_overbudget : 0;
                    }

                    foreach ($list_data_lab_custom as $item) {
                        $no++;

                        $qty_terpakai = (isset($list_arr_kasbon[$item->id]['qty_terpakai'])) ? $list_arr_kasbon[$item->id]['qty_terpakai'] : 0;
                        $nominal_terpakai = (isset($list_arr_kasbon[$item->id]['nominal_terpakai'])) ? $list_arr_kasbon[$item->id]['nominal_terpakai'] : 0;
                        $total_terpakai = (isset($list_arr_kasbon[$item->id]['total_terpakai'])) ? $list_arr_kasbon[$item->id]['total_terpakai'] : 0;

                        $qty_overbudget = (isset($list_arr_kasbon[$item->id]['qty_overbudget'])) ? $list_arr_kasbon[$item->id]['qty_overbudget'] : 0;
                        $nominal_overbudget = (isset($list_arr_kasbon[$item->id]['nominal_overbudget'])) ? $list_arr_kasbon[$item->id]['nominal_overbudget'] : 0;
                        $total_overbudget = (isset($list_arr_kasbon[$item->id]['total_overbudget'])) ? $list_arr_kasbon[$item->id]['total_overbudget'] : 0;

                        echo '<tr>';

                        echo '<td class="text-center">' . $no . '</td>';
                        echo '<td>' . $item->nm_item . '</td>';
                        echo '<td class="text-center">' . number_format($item->estimasi_qty, 2) . '</td>';
                        echo '<td class="text-right">' . number_format($item->estimasi_harga, 2) . '</td>';
                        echo '<td class="text-right">' . number_format($item->estimasi_total, 2) . '</td>';
                        echo '<td class="text-center">' . number_format($qty_terpakai, 2) . '</td>';
                        echo '<td class="text-right">';
                        echo ($qty_terpakai > 0) ? number_format($nominal_terpakai, 2) : '-';
                        echo '</td>';
                        echo '<td class="text-right">';
                        echo ($qty_terpakai > 0) ? number_format($total_terpakai, 2) : '-';
                        echo '</td>';
                        echo '<td class="text-center">' . number_format($qty_overbudget, 2) . '</td>';
                        echo '<td class="text-right">';
                        echo ($qty_overbudget > 0) ? number_format($nominal_overbudget, 2) : '-';
                        echo '</td>';
                        echo '<td class="text-right">';
                        echo ($qty_overbudget > 0) ? number_format($total_overbudget, 2) : '-';
                        echo '</td>';

                        echo '</tr>';

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
                        <th colspan="2" class="text-center">Grand Total</th>
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

                    foreach ($list_data_lab as $item) {
                        if (isset($list_arr_kasbon[$item->id_lab])) {
                            $no++;

                            $qty_pengajuan = (isset($list_arr_kasbon[$item->id_lab]['qty_pengajuan'])) ? $list_arr_kasbon[$item->id_lab]['qty_pengajuan'] : 0;
                            $nominal_pengajuan = (isset($list_arr_kasbon[$item->id_lab]['nominal_pengajuan'])) ? $list_arr_kasbon[$item->id_lab]['nominal_pengajuan'] : 0;
                            $total_pengajuan = (isset($list_arr_kasbon[$item->id_lab]['total_pengajuan'])) ? $list_arr_kasbon[$item->id_lab]['total_pengajuan'] : 0;

                            echo '<tr>';

                            echo '<td class="text-center">' . $no . '</td>';
                            echo '<td>' . (!empty($item->nm_biaya) ? $item->nm_biaya : $item->nm_item) . '</td>';
                            echo '<td class="text-center">' . number_format($qty_pengajuan, 2) . '</td>';
                            echo '<td class="text-right">' . number_format($nominal_pengajuan, 2) . '</td>';
                            echo '<td class="text-right">' . number_format($total_pengajuan, 2) . '</td>';

                            echo '</tr>';
                        }
                    }

                    foreach ($list_data_lab_custom as $item) {
                        if (isset($list_arr_kasbon[$item->id])) {
                            $no++;

                            $qty_pengajuan = (isset($list_arr_kasbon[$item->id]['qty_pengajuan'])) ? $list_arr_kasbon[$item->id]['qty_pengajuan'] : 0;
                            $nominal_pengajuan = (isset($list_arr_kasbon[$item->id]['nominal_pengajuan'])) ? $list_arr_kasbon[$item->id]['nominal_pengajuan'] : 0;
                            $total_pengajuan = (isset($list_arr_kasbon[$item->id]['total_pengajuan'])) ? $list_arr_kasbon[$item->id]['total_pengajuan'] : 0;

                            echo '<tr>';

                            echo '<td class="text-center">' . $no . '</td>';
                            echo '<td>' . $item->nm_item . '</td>';
                            echo '<td class="text-center">' . number_format($qty_pengajuan, 2) . '</td>';
                            echo '<td class="text-right">' . number_format($nominal_pengajuan, 2) . '</td>';
                            echo '<td class="text-right">' . number_format($total_pengajuan, 2) . '</td>';

                            echo '</tr>';
                        }
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" class="text-center">Grand Total</th>
                        <th class="text-center"><?= number_format($ttl_qty_pengajuan, 2) ?></th>
                        <th></th>
                        <th class="text-right"><?= number_format($ttl_total_pengajuan, 2) ?></th>
                    </tr>
                </tfoot>
            </table>

            <br><br>

            <div class="col-md-6">
                <table style="width: 100%">
                    <tr>
                        <th style="padding: 5px;">Bank</th>
                        <td style="padding: 5px;">
                            <input type="text" name="kasbon_bank" id="" class="form-control form-control-sm" placeholder="- Bank -" value="<?= $header->bank ?>" readonly>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding: 5px;">Bank Number</th>
                        <td style="padding: 5px;">
                            <input type="text" name="kasbon_bank_number" id="" class="form-control form-control-sm" placeholder="- Bank Number -" value="<?= $header->bank_number ?>" readonly>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding: 5px;">Account Name</th>
                        <td style="padding: 5px;">
                            <input type="text" name="kasbon_bank_account" id="" class="form-control form-control-sm" placeholder="- Account Name -" value="<?= $header->bank_account ?>" readonly>
                        </td>
                    </tr>
                </table>

                <br><br>

                <table class="<?= $show_reject_reason ?>" style="width: 100%">
                    <tr>
                        <th style="padding: 5px;">Reject Reason</th>
                        <td>
                            <textarea name="" id="" class="form-control form-control-sm" readonly><?= $header->reject_reason ?></textarea>
                        </td>
                    </tr>
                </table>
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

            <div class="col-md-12 mt-5">
                <a href="<?= base_url('kasbon_project/add_kasbon/' . urlencode(str_replace('/', '|', $list_budgeting->id_spk_budgeting))) ?>" class="btn btn-sm btn-danger">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
                <button type="button" class="btn btn-sm btn-info btn_history_kasbon" data-id_kasbon="<?= $header->id ?>">
                    <i class="fa fa-history"></i> History
                </button>
            </div>
        </div>
    </div>
</form>

<div class="modal" id="dialog-history-kasbon" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title" id="myModalLabel">History Approval</h4>
            </div>
            <div class="modal-body" id="historyModalBodyKasbon">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">
                    <span class="glyphicon glyphicon-remove"></span> Close</button>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/autoNumeric.js'); ?>"></script>
<script>
    $(document).ready(function() {
        $('.auto_num').autoNumeric();
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

        for (i = 1; i <= no; i++) {
            var qty_pengajuan = get_num($('input[name="detail_akomodasi[' + i + '][qty_pengajuan]"]').val());
            var nominal_pengajuan = get_num($('input[name="detail_akomodasi[' + i + '][nominal_pengajuan]"]').val());

            ttl_qty += qty_pengajuan;
            ttl_price += nominal_pengajuan;
        }

        $('.ttl_qty_pengajuan').html(number_format(ttl_qty));
        $('.ttl_nominal_pengajuan').html(number_format(ttl_price, 2));
    }

    $(document).on('click', '.btn_history_kasbon', function() {
        var id_kasbon = $(this).data('id_kasbon');

        $.ajax({
            type: 'get',
            url: siteurl + 'kasbon_project/history_approval',
            data: {
                'id_kasbon': id_kasbon
            },
            cache: false,
            dataType: 'JSON',
            success: function(result) {
                if (result.status == 1) {
                    $('#dialog-history-kasbon').modal();
                    $('#historyModalBodyKasbon').html(result.result);
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
            text: 'Please try again later!'
        });
            }
        });
    });

    $(document).on('submit', '#frm-data', function(e) {
        e.preventDefault();

        var no = "<?= $no ?>";

        var valid = 1;

        for (i = 1; i <= no; i++) {
            var qty_pengajuan = get_num($('input[name="detail_akomodasi[' + i + '][qty_pengajuan]"]').val());
            var nominal_pengajuan = get_num($('input[name="detail_akomodasi[' + i + '][nominal_pengajuan]"]').val());
            var sisa_budget = get_num($('input[name="detail_akomodasi[' + i + '][sisa_budget]"]').val());

            if (valid == '1' && (qty_pengajuan * nominal_pengajuan) > sisa_budget) {
                valid = 0;
            }
        }

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

                    $.ajax({
                        type: 'post',
                        url: siteurl + active_controller + 'save_kasbon_akomodasi',
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