<?php
$ENABLE_ADD     = has_permission('Kasbon_Project.Add');
$ENABLE_MANAGE  = has_permission('Kasbon_Project.Manage');
$ENABLE_VIEW    = has_permission('Kasbon_Project.View');
$ENABLE_DELETE  = has_permission('Kasbon_Project.Delete');

$nm_pembuat = !empty($current_user->nm_lengkap) ? $current_user->nm_lengkap : $this->auth->user_name();
$employee_id = !empty($current_user->employee_id) ? $current_user->employee_id : '';

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
    <input type="hidden" name="id_spk_budgeting" value="<?= $list_budgeting->id_spk_budgeting ?>">
    <input type="hidden" name="id_spk_penawaran" value="<?= $list_budgeting->id_spk_penawaran ?>">
    <input type="hidden" name="id_penawaran" value="<?= $list_budgeting->id_penawaran ?>">
    <input type="hidden" name="metode_pembayaran" value="<?= $metode_pembayaran ?>">
    <div class="box">
        <div class="box-header">

        </div>

        <div class="box-body">
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
                        <input type="hidden" name="request_by" id="request_by" value="<?= $this->auth->user_id() ?>">
                        <div style="font-weight: 600; color: #333; padding-top: 5px;">
                            <i class="fa fa-user-circle text-primary"></i> <?= htmlspecialchars($nm_pembuat) ?>
                        </div>
                        <?php if (!$is_in_team) : ?>
                            <div class="tag-outside" style="margin-top: 5px;">
                                <i class="fa fa-exclamation-triangle"></i> <?= htmlspecialchars($nm_pembuat) ?> bukan bagian dari tim SPK ini<?= !empty($origin_spk) ? ' (tim asal: ' . htmlspecialchars($origin_spk) . ')' : '' ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <th class="pd-5 valign-top" width="150">Tgl</th>
                    <td class="pd-5 valign-top" width="400">
                        <input type="date" class="form-control form-control-sm" name="tgl" value="<?= date('Y-m-d') ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <th class="pd-5 valign-top" width="150">Deskripsi / Keterangan <span class="text-danger">*</span></th>
                    <td class="pd-5 valign-top" width="400" colspan="3">
                        <textarea name="deskripsi" id="" class="form-control form-control-sm" required placeholder="Deskripsi / Keterangan"></textarea>
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
                    foreach ($list_akomodasi as $item) {
                        $no++;

                        $aktual_terpakai = (isset($data_kasbon_akomodasi[$item->id_akomodasi]['ttl_qty_pengajuan'])) ? $data_kasbon_akomodasi[$item->id_akomodasi]['ttl_qty_pengajuan'] : 0;

                        $total_budget_tambahan = (isset($data_ovb_akomodasi[$item->id])) ? $data_ovb_akomodasi[$item->id]['total_budget_tambahan'] : 0;
                        $ttl_qty_tambahan = (isset($data_ovb_akomodasi[$item->id])) ? $data_ovb_akomodasi[$item->id]['ttl_qty_tambahan'] : 0;

                        $sisa_budget = (isset($data_kasbon_akomodasi[$item->id_akomodasi]['ttl_total_pengajuan'])) ? (($item->price_unit_final * $item->qty_final) - $data_kasbon_akomodasi[$item->id_akomodasi]['ttl_total_pengajuan']) : ($item->price_unit_final * $item->qty_final);

                        $sisa_budget = ($sisa_budget + $total_budget_tambahan);

                        $readonly = '';
                        if ($sisa_budget <= 0) {
                            $readonly = 'readonly';
                        }

                        $sisa_qty = (($item->qty_final + $ttl_qty_tambahan) - $aktual_terpakai);

                        $qty_terpakai = $aktual_terpakai;
                        $nominal_terpakai = $item->price_unit_final;
                        $total_terpakai = (isset($data_kasbon_akomodasi[$item->id_akomodasi]['ttl_total_pengajuan'])) ? $data_kasbon_akomodasi[$item->id_akomodasi]['ttl_total_pengajuan'] : 0;

                        echo '<tr>';

                        echo '<td class="text-center">' . $no . '</td>';
                        echo '<td>';
                        echo (!empty($item->nm_biaya) ? $item->nm_biaya : $item->nm_item);
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][id_akomodasi]" value="' . $item->id_akomodasi . '">';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][id_item]" value="' . $item->id_item . '">';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][nm_item]" value="' . $item->nm_item . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo number_format($item->qty_final);
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][qty_estimasi]" value="' . $item->qty_final . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo number_format($item->price_unit_final, 2);
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][price_unit_estimasi]" value="' . $item->price_unit_final . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo number_format($item->total_final, 2);
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][total_estimasi]" value="' . $item->total_final . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo number_format($qty_terpakai, 2);
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][qty_terpakai]" value="' . $qty_terpakai . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo ($aktual_terpakai > 0) ? number_format($nominal_terpakai, 2) : '-';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][nominal_terpakai]" value="' . $nominal_terpakai . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo ($aktual_terpakai > 0) ? number_format($total_terpakai, 2) : '-';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][total_terpakai]" value="' . $total_terpakai . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo number_format($ttl_qty_tambahan, 2);
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][qty_overbudget]" value="' . $ttl_qty_tambahan . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo ($ttl_qty_tambahan > 0) ? number_format($item->price_unit_final, 2) : '-';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][nominal_overbudget]" value="' . $item->price_unit_final . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo ($ttl_qty_tambahan > 0) ? number_format($total_budget_tambahan, 2) : '-';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][total_overbudget]" value="' . $total_budget_tambahan . '">';
                        echo '</td>';

                        echo '</tr>';

                        $ttl_qty_estimasi += $item->qty_final;
                        $ttl_total_estimasi += $item->total_final;
                        $ttl_qty_terpakai += $qty_terpakai;
                        $ttl_total_terpakai += ($qty_terpakai > 0) ? $total_terpakai : 0;
                        $ttl_qty_overbudget += $ttl_qty_tambahan;
                        $ttl_total_overbudget += ($ttl_qty_tambahan > 0) ? $total_budget_tambahan : 0;
                    }

                    foreach ($list_akomodasi_custom as $item) {
                        $no++;

                        $aktual_terpakai = (isset($data_kasbon_akomodasi[$item->id]['ttl_qty_pengajuan'])) ? $data_kasbon_akomodasi[$item->id]['ttl_qty_pengajuan'] : 0;

                        $total_budget_tambahan = (isset($data_ovb_akomodasi[$item->id])) ? $data_ovb_akomodasi[$item->id]['total_budget_tambahan'] : 0;
                        $ttl_qty_tambahan = (isset($data_ovb_akomodasi[$item->id])) ? $data_ovb_akomodasi[$item->id]['ttl_qty_tambahan'] : 0;

                        $sisa_budget = (isset($data_kasbon_akomodasi[$item->id]['ttl_total_pengajuan'])) ? (($item->estimasi_harga * $item->estimasi_qty) - $data_kasbon_akomodasi[$item->id]['ttl_total_pengajuan']) : ($item->estimasi_harga * $item->estimasi_qty);

                        $sisa_budget = ($sisa_budget + $total_budget_tambahan);

                        $readonly = '';
                        if ($sisa_budget <= 0) {
                            $readonly = 'readonly';
                        }

                        $sisa_qty = (($item->estimasi_qty + $ttl_qty_tambahan) - $aktual_terpakai);

                        $qty_terpakai = $aktual_terpakai;
                        $nominal_terpakai = $item->estimasi_harga;
                        $total_terpakai = (isset($data_kasbon_akomodasi[$item->id]['ttl_total_pengajuan'])) ? $data_kasbon_akomodasi[$item->id]['ttl_total_pengajuan'] : 0;

                        echo '<tr>';

                        echo '<td class="text-center">' . $no . '</td>';
                        echo '<td>';
                        echo $item->nm_item;
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][id_akomodasi]" value="' . $item->id . '">';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][id_item]" value="' . $item->id . '">';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][nm_item]" value="' . $item->nm_item . '">';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][custom_akomodasi]" value="1">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo number_format($item->estimasi_qty);
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][qty_estimasi]" value="' . $item->estimasi_qty . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo number_format($item->estimasi_harga, 2);
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][price_unit_estimasi]" value="' . $item->estimasi_harga . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo number_format($item->estimasi_total, 2);
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][total_estimasi]" value="' . $item->estimasi_total . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo number_format($qty_terpakai, 2);
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][qty_terpakai]" value="' . $qty_terpakai . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo ($aktual_terpakai > 0) ? number_format($nominal_terpakai, 2) : '-';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][nominal_terpakai]" value="' . $nominal_terpakai . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo ($aktual_terpakai > 0) ? number_format($total_terpakai, 2) : '-';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][total_terpakai]" value="' . $total_terpakai . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo number_format($ttl_qty_tambahan, 2);
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][qty_overbudget]" value="' . $ttl_qty_tambahan . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo ($ttl_qty_tambahan > 0) ? number_format($item->estimasi_harga, 2) : '-';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][nominal_overbudget]" value="' . $item->estimasi_harga . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo ($ttl_qty_tambahan > 0) ? number_format($total_budget_tambahan, 2) : '-';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][total_overbudget]" value="' . $total_budget_tambahan . '">';
                        echo '</td>';

                        echo '</tr>';

                        $ttl_qty_estimasi += $item->estimasi_qty;
                        $ttl_total_estimasi += $item->estimasi_total;
                        $ttl_qty_terpakai += $qty_terpakai;
                        $ttl_total_terpakai += ($qty_terpakai > 0) ? $total_terpakai : 0;
                        $ttl_qty_overbudget += $ttl_qty_tambahan;
                        $ttl_total_overbudget += ($ttl_qty_tambahan > 0) ? $total_budget_tambahan : 0;
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" class="text-center">Total</th>
                        <th class="text-center"><?= number_format($ttl_qty_estimasi, 2) ?></th>
                        <th></th>
                        <th class="text-center"><?= number_format($ttl_total_estimasi, 2) ?></th>
                        <th class="text-center"><?= number_format($ttl_qty_terpakai, 2) ?></th>
                        <th></th>
                        <th class="text-center"><?= number_format($ttl_total_terpakai, 2) ?></th>
                        <th class="text-center"><?= number_format($ttl_qty_overbudget, 2) ?></th>
                        <th></th>
                        <th class="text-center"><?= number_format($ttl_total_overbudget, 2) ?></th>
                    </tr>
                </tfoot>
            </table>

            <br>

            <h4 style="font-weight: bold;">Informasi</h4>

            <br>

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

                    foreach ($list_akomodasi as $item) {
                        $no++;

                        $aktual_terpakai = (isset($data_kasbon_akomodasi[$item->id_akomodasi]['ttl_qty_pengajuan'])) ? $data_kasbon_akomodasi[$item->id_akomodasi]['ttl_qty_pengajuan'] : 0;

                        $total_budget_tambahan = (isset($data_ovb_akomodasi[$item->id])) ? $data_ovb_akomodasi[$item->id]['total_budget_tambahan'] : 0;
                        $ttl_qty_tambahan = (isset($data_ovb_akomodasi[$item->id])) ? $data_ovb_akomodasi[$item->id]['ttl_qty_tambahan'] : 0;

                        $sisa_budget = (isset($data_kasbon_akomodasi[$item->id_akomodasi]['ttl_total_pengajuan'])) ? (($item->price_unit_final * $item->qty_final) - $data_kasbon_akomodasi[$item->id_akomodasi]['ttl_total_pengajuan']) : ($item->price_unit_final * $item->qty_final);

                        $sisa_budget = ($sisa_budget + $total_budget_tambahan);

                        $readonly = '';
                        if ($sisa_budget <= 0) {
                            $readonly = 'readonly';
                        }

                        $sisa_qty = (($item->qty_final + $ttl_qty_tambahan) - $aktual_terpakai);

                        echo '<tr>';

                        echo '<td class="text-center">' . $no . '</td>';
                        echo '<td>';
                        echo (!empty($item->nm_biaya) ? $item->nm_biaya : $item->nm_item);
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][id_akomodasi]" value="' . $item->id_akomodasi . '">';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][id_item]" value="' . $item->id_item . '">';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][nm_item]" value="' . $item->nm_item . '">';
                        echo '</td>';

                        echo '<td>';
                        echo '<input type="number" name="detail_akomodasi[' . $no . '][qty_pengajuan]" class="form-control form-control-sm text-right qty_pengajuan qty_pengajuan_' . $no . '" step="0.01" onchange="hitung_all_pengajuan()" ' . $readonly . ' data-no_urut="' . $no . '" data-price_unit="' . $item->price_unit_final . '">';
                        echo '</td>';

                        echo '<td>';
                        echo '<input type="text" name="detail_akomodasi[' . $no . '][nominal_pengajuan]" class="form-control form-control-sm text-right auto_num" onchange="hitung_all_pengajuan()" value="' . $item->price_unit_final . '" data-no="' . $no . '" data-budget="' . ($item->price_unit_final) . '" ' . $readonly . '>';
                        echo '</td>';

                        echo '<td>';
                        echo '<input type="text" name="detail_akomodasi[' . $no . '][total_pengajuan]" class="form-control form-control-sm text-right auto_num" value="" readonly>';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][qty_budget_tambahan]" value="' . $ttl_qty_tambahan . '">';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][budget_tambahan]" value="' . $total_budget_tambahan . '">';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][aktual_terpakai]" value="' . ($sisa_qty) . '">';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][sisa_budget]" value="' . $sisa_budget . '">';
                        echo '</td>';

                        echo '</tr>';
                    }

                    foreach ($list_akomodasi_custom as $item) {
                        $no++;

                        $aktual_terpakai = (isset($data_kasbon_akomodasi[$item->id]['ttl_qty_pengajuan'])) ? $data_kasbon_akomodasi[$item->id]['ttl_qty_pengajuan'] : 0;

                        $total_budget_tambahan = (isset($data_ovb_akomodasi[$item->id])) ? $data_ovb_akomodasi[$item->id]['total_budget_tambahan'] : 0;
                        $ttl_qty_tambahan = (isset($data_ovb_akomodasi[$item->id])) ? $data_ovb_akomodasi[$item->id]['ttl_qty_tambahan'] : 0;

                        $sisa_budget = (isset($data_kasbon_akomodasi[$item->id]['ttl_total_pengajuan'])) ? (($item->estimasi_harga * $item->estimasi_qty) - $data_kasbon_akomodasi[$item->id]['ttl_total_pengajuan']) : ($item->estimasi_harga * $item->estimasi_qty);

                        $sisa_budget = ($sisa_budget + $total_budget_tambahan);

                        $readonly = '';
                        if ($sisa_budget <= 0) {
                            $readonly = 'readonly';
                        }

                        $sisa_qty = (($item->estimasi_qty + $ttl_qty_tambahan) - $aktual_terpakai);

                        echo '<tr>';

                        echo '<td class="text-center">' . $no . '</td>';
                        echo '<td>';
                        echo $item->nm_item;
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][id_akomodasi]" value="' . $item->id . '">';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][id_item]" value="' . $item->id . '">';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][nm_item]" value="' . $item->nm_item . '">';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][detail_akomodasi]" value="1">';
                        echo '</td>';

                        echo '<td>';
                        echo '<input type="number" name="detail_akomodasi[' . $no . '][qty_pengajuan]" class="form-control form-control-sm text-right qty_pengajuan qty_pengajuan_' . $no . '" step="0.01" onchange="hitung_all_pengajuan()" ' . $readonly . ' data-no_urut="' . $no . '" data-price_unit="' . $item->estimasi_harga . '">';
                        echo '</td>';

                        echo '<td>';
                        echo '<input type="text" name="detail_akomodasi[' . $no . '][nominal_pengajuan]" class="form-control form-control-sm text-right auto_num" onchange="hitung_all_pengajuan()" value="' . $item->estimasi_harga . '" data-no="' . $no . '" data-budget="' . ($item->estimasi_harga) . '" ' . $readonly . '>';
                        echo '</td>';

                        echo '<td>';
                        echo '<input type="text" name="detail_akomodasi[' . $no . '][total_pengajuan]" class="form-control form-control-sm text-right auto_num" value="" readonly>';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][qty_budget_tambahan]" value="' . $ttl_qty_tambahan . '">';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][budget_tambahan]" value="' . $total_budget_tambahan . '">';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][aktual_terpakai]" value="' . ($sisa_qty) . '">';
                        echo '<input type="hidden" name="detail_akomodasi[' . $no . '][sisa_budget]" value="' . $sisa_budget . '">';
                        echo '</td>';

                        echo '</tr>';
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="2" class="text-center">Grand Total</th>
                        <th class="text-center ttl_qty_pengajuan">0.00</th>
                        <th></th>
                        <th class="text-right ttl_pengajuan">0.00</th>
                    </tr>
                </tfoot>
            </table>

            <br><br>

            <input type="hidden" name="total_sisa" value="<?= ($ttl_total_estimasi - $ttl_total_terpakai) ?>">

            <div class="col-md-6">
                <table style="width: 100%">

                    <tr>
                        <th style="padding: 5px;">Bank <span class="text-danger">*</span></th>
                        <td style="padding: 5px;">
                            <input type="text" name="kasbon_bank" id="" class="form-control form-control-sm" placeholder="- Bank -" required>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding: 5px;">Bank Number <span class="text-danger">*</span></th>
                        <td style="padding: 5px;">
                            <input type="text" name="kasbon_bank_number" id="" class="form-control form-control-sm" placeholder="- Bank Number -" required>
                        </td>
                    </tr>
                    <tr>
                        <th style="padding: 5px;">Account Name <span class="text-danger">*</span></th>
                        <td style="padding: 5px;">
                            <input type="text" name="kasbon_bank_account" id="" class="form-control form-control-sm" placeholder="- Account Name -" required>
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
                    <div class="panel-body" style="padding: 12px;">
                        <input type="file" id="input-bukti-file" multiple style="display: none;">
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


<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            var qty_pengajuan = $('input[name="detail_akomodasi[' + i + '][qty_pengajuan]"]').val();
            if (isNaN(qty_pengajuan) || qty_pengajuan == '') {
                qty_pengajuan = 0;
            } else {
                qty_pengajuan = parseFloat(qty_pengajuan);
            }

            var nominal_pengajuan = get_num($('input[name="detail_akomodasi[' + i + '][nominal_pengajuan]"]').val());
            var total_pengajuan = (nominal_pengajuan * qty_pengajuan);

            $('input[name="detail_akomodasi[' + i + '][total_pengajuan]"]').autoNumeric('set', total_pengajuan);

            ttl_qty += qty_pengajuan;
            ttl_price += nominal_pengajuan;
            ttl_total += total_pengajuan;
        }

        $('.ttl_pengajuan').html(number_format(ttl_total, 2));
        $('.ttl_qty_pengajuan').html(number_format(ttl_qty, 2));
    }

    $(document).on('change', '.hitung_per_price', function() {
        var no = $(this).data('no');
        var budget = $(this).data('budget');
        var pengajuan = get_num($(this).val());

        var qty = (pengajuan / budget);

        $('input[name="detail_akomodasi[' + no + '][qty_pengajuan]"]').val(qty);
        $('input[name="detail_akomodasi[' + no + '][total_pengajuan]"]').autoNumeric('set', pengajuan);

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
        var total_sisa = $('input[name="total_sisa"]').val();
        var valid = 1;
        var ttl_propose = 0;

        for (i = 1; i <= no; i++) {
            var qty_pengajuan = get_num($('input[name="detail_akomodasi[' + i + '][qty_pengajuan]"]').val());
            var nominal_pengajuan = get_num($('input[name="detail_akomodasi[' + i + '][nominal_pengajuan]"]').val());
            var budget_tambahan = get_num($('input[name="detail_akomodasi[' + i + '][budget_tambahan]"]').val());
            var sisa_budget = get_num($('input[name="detail_akomodasi[' + i + '][sisa_budget]"]').val());

            if (qty_pengajuan > 0 && qty_pengajuan < 1) {
                qty_pengajuan = 1;
            }

            ttl_propose += (qty_pengajuan * nominal_pengajuan);
        }

        if (ttl_propose <= 0) {
            errors.push({ name: 'Item Pengajuan (minimal 1 item harus diajukan dengan nominal > 0)', el: $('.qty_pengajuan:first') });
            $('.qty_pengajuan:first').addClass('input-error');
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

        if (valid == '1' && ttl_propose > total_sisa) {
            valid = 0;
        }

        if (valid == '0') {
            Swal.fire({
                icon: 'warning',
                title: 'Warning !',
                text: 'Total pengajuan melebihi sisa budget !'
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Are you sure ?',
                text: 'This data will be saved !',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, save it!',
                cancelButtonText: 'Cancel'
            }).then((res) => {
                if (res.isConfirmed) {
                    var formData = new FormData($('#frm-data')[0]);
                    for (var i = 0; i < selectedBuktiFiles.length; i++) {
                        formData.append('bukti_penggunaan[]', selectedBuktiFiles[i]);
                    }

                    $.ajax({
                        type: 'post',
                        url: siteurl + active_controller + 'save_kasbon_akomodasi',
                        data: formData,
                        processData: false,
                        contentType: false,
                        cache: false,
                        dataType: 'JSON',
                        success: function(result) {
                            if (result.status == '1') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success !',
                                    text: result.pesan,
                                    timer: 2000
                                }).then(() => {
                                    window.location.href = siteurl + active_controller + "add_kasbon/<?= urlencode(str_replace('/', '|', $list_budgeting->id_spk_budgeting)) ?>";
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
    });
</script>