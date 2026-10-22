<?php
$box_kasbon_subcont = 'd-none';
$box_kasbon_akomodasi = 'd-none';
$box_kasbon_others = 'd-none';
$box_expense = 'd-none';

$tipe2 = $tipe;
if ($tipe !== 'Expense') {
    $tipe2 = 'Kasbon';
}

$title_expense = (isset($title_expense)) ? $title_expense : '';

if ($tipe == 'Kasbon Subcont') {
    $box_kasbon_subcont = '';
}
if ($tipe == 'Kasbon Akomodasi') {
    $box_kasbon_akomodasi = '';
}
if ($tipe == 'Kasbon Others') {
    $box_kasbon_others = '';
}
if ($tipe == 'Expense') {
    $box_expense = '';
}

if (!isset($nm_created_by) || empty($nm_created_by)) {
    $nm_created_by = '-';
    $user_id_cr = isset($data_kasbon_header->created_by) ? $data_kasbon_header->created_by : '';
    if (!empty($user_id_cr)) {
        $u_cr = $this->db->query("SELECT nm_lengkap FROM users WHERE id_user = '" . $this->db->escape_str($user_id_cr) . "' OR username = '" . $this->db->escape_str($user_id_cr) . "'")->row();
        $nm_created_by = !empty($u_cr->nm_lengkap) ? $u_cr->nm_lengkap : $user_id_cr;
    }
}

$nm_approved_by = 'Imanuel Iman';

$tgl_app_direktur_formatted = (!empty($tgl_approve_direktur) && $tgl_approve_direktur != '0000-00-00 00:00:00') ? date('d F Y', strtotime($tgl_approve_direktur)) : '-';
$tgl_created = $tgl_app_direktur_formatted;
$tgl_approved = $tgl_app_direktur_formatted;

$d_none = '';
$d_none2 = 'style="display: none;"';
if(empty($tgl_approve_direktur)) {
    $d_none = 'style="display: none;"';
    $d_none2 = '';
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Print PDF Expense</title>
</head>
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

    .d-none {
        display: none;
    }

    .text-center {
        text-align: center;
    }

    .text-right {
        text-align: right;
    }

    .text-left {
        text-align: left;
    }
</style>

<body>
    <div class="text-center">
        <h2>Print Expense - <?= $id ?></h2>
    </div>
    <div class="box-body" style="z-index: 1 !important;">
        <table border="0" style="width: 100%; z-index: 1 !important;">
            <tr>
                <th class="pd-5 valign-top" width="150">No. SPK</th>
                <td class="pd-5 valign-top" width="400"><?= $data_spk_penawaran->id_spk_penawaran ?></td>
                <th class="pd-5 valign-top" width="150">Project Leader</th>
                <td class="pd-5 valign-top" width="400"><?= ucfirst($data_spk_penawaran->nm_project_leader) ?></td>
            </tr>
            <tr>
                <th class="pd-5 valign-top" width="150">Customer</th>
                <td class="pd-5 valign-top" width="400"><?= $data_spk_penawaran->nm_customer ?></td>
                <th class="pd-5 valign-top" width="150">Sales</th>
                <td class="pd-5 valign-top" width="400"><?= ucfirst($data_spk_penawaran->nm_sales) ?></td>
            </tr>
            <tr>
                <th class="pd-5 valign-top" width="150">Address</th>
                <td class="pd-5 valign-top" width="400"><?= $data_spk_penawaran->alamat ?></td>
                <th class="pd-5 valign-top" width="150">Waktu</th>
                <td class="pd-5 valign-top" width="400">
                    (<?= date('d F Y', strtotime($data_spk_penawaran->waktu_from)) ?>)
                    <span>-</span>
                    (<?= date('d F Y', strtotime($data_spk_penawaran->waktu_to)) ?>)
                </td>
            </tr>
            <tr>
                <th class="pd-5 valign-top" width="150">Project</th>
                <td class="pd-5 valign-top" width="400"><?= $data_spk_penawaran->nm_paket ?></td>
                <th class="pd-5 valign-top" width="150">Keperluan</th>
                <td class="pd-5 valign-top" width="400">
                    <?= $data_spk_penawaran->nm_customer . ', ' . $data_spk_penawaran->id_spk_penawaran . ', ' . $tipe ?>
                </td>
            </tr>
            <tr>
                <th class="pd-5 valign-top" width="150">Keterangan</th>
                <td class="pd-5 valign-top" width="400"><?= $data_kasbon_header->deskripsi ?></td>
                <th class="pd-5 valign-top" width="150"></th>
                <td class="pd-5 valign-top" width="400">
                </td>
            </tr>
        </table>
    </div>

    <div class="box <?= $box_expense ?>">
        <div class="box-header">
            <h4 style="font-weight: 800;"><?= $title_expense ?></h4>
        </div>

        <div class="box-body">
            <table border="1" style="width: 100%;">
                <thead>
                    <tr>
                        <th class="text-center" valign="top" rowspan="2">No.</th>
                        <th class="text-center" valign="top" rowspan="2">Item</th>
                        <th class="text-center" valign="top" colspan="2">Kasbon</th>
                        <th class="text-center" valign="top" colspan="2">Expense Report</th>
                    </tr>
                    <tr>
                        <th class="text-center">Qty</th>
                        <th class="text-center">Nominal</th>
                        <th class="text-center">Qty</th>
                        <th class="text-center">Nominal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $no = 0;
                    $ttl_kasbon_exp = 0;
                    $ttl_exp = 0;
                    if (isset($list_expense_detail)) {

                        foreach ($list_expense_detail as $item) : $no++;
                            $qty_kasbon = (!empty($list_detail_expense_detail[$item->id])) ? $list_detail_expense_detail[$item->id]['qty_kasbon'] : 0;
                            $nominal_kasbon = (!empty($list_detail_expense_detail[$item->id])) ? $list_detail_expense_detail[$item->id]['nominal_kasbon'] : 0;
                            $qty_expense = (!empty($list_detail_expense_detail[$item->id])) ? $list_detail_expense_detail[$item->id]['qty_expense'] : 0;
                            $nominal_expense = (!empty($list_detail_expense_detail[$item->id])) ? $list_detail_expense_detail[$item->id]['nominal_expense'] : 0;

                    ?>

                            <tr>
                                <td class="text-center"><?= $no ?></td>
                                <td class="text-left"><?= (!empty($list_detail_expense_detail[$item->id])) ? $list_detail_expense_detail[$item->id]['nama_expense'] : '' ?></td>
                                <td class="text-center"><?= number_format($qty_kasbon, 2) ?></td>
                                <td class="text-center"><?= number_format($nominal_kasbon, 2) ?></td>
                                <td class="text-center"><?= number_format($qty_expense, 2) ?></td>
                                <td class="text-center"><?= number_format($nominal_expense, 2) ?></td>
                            </tr>

                    <?php
                            if ($qty_kasbon > 0 && $qty_kasbon < 1) {
                                $ttl_kasbon_exp += $nominal_kasbon;
                            } else {
                                if ($qty_kasbon > 0) {
                                    $ttl_kasbon_exp += ($nominal_kasbon * $qty_kasbon);
                                }
                            }
                            if ($qty_expense > 0 && $qty_expense < 1) {
                                $ttl_exp += $nominal_expense;
                            } else {
                                if ($qty_expense > 0) {
                                    $ttl_exp += ($nominal_expense * $qty_expense);
                                }
                            }
                        endforeach;
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="5" class="text-right">Total Kasbon</td>
                        <td class="text-right col_ttl_kasbon"><?= number_format($ttl_kasbon_exp, 2) ?></td>
                    </tr>
                    <tr>
                        <td colspan="5" class="text-right">Total Expense Report</td>
                        <td class="text-right col_ttl_expense_report"><?= number_format($ttl_exp, 2) ?></td>
                    </tr>
                    <tr>
                        <td colspan="5" class="text-right">Selisih</td>
                        <td class="text-right col_selisih"><?= number_format($ttl_kasbon_exp - $ttl_exp, 2) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <br />
    <table style="width: 100%; font-size: 12px; margin-top: 15px; page-break-inside: avoid;" border="0" cellpadding="0" cellspacing="0">
        <tr>
            <!-- Kolom Kiri: Detail Bank -->
            <td style="width: 45%; vertical-align: top;">
                <table style="width: 100%; font-size: 12px;" border="0" cellpadding="2" cellspacing="0">
                    <tr>
                        <td style="width: 110px; font-weight: bold;">Bank</td>
                        <td style="width: 10px;">:</td>
                        <td><?= !empty($data_kasbon_header->bank) ? $data_kasbon_header->bank : '-' ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Bank Number</td>
                        <td>:</td>
                        <td><?= !empty($data_kasbon_header->bank_number) ? $data_kasbon_header->bank_number : '-' ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: bold;">Account Name</td>
                        <td>:</td>
                        <td><?= !empty($data_kasbon_header->bank_account) ? $data_kasbon_header->bank_account : '-' ?></td>
                    </tr>
                </table>
            </td>
            <!-- Spasi Pemisah -->
            <td style="width: 5%;"></td>
            <!-- Kolom Kanan: 3 Kolom TTD -->
            <td style="width: 50%; vertical-align: top;">
                <table style="width: 100%; font-size: 12px;" border="0" cellpadding="2" cellspacing="0">
                    <tr>
                        <td style="width: 31%; text-align: center; font-weight: bold;">Mengajukan</td>
                        <td style="width: 3%;"></td>
                        <td style="width: 31%; text-align: center; font-weight: bold;">Mengetahui</td>
                        <td style="width: 3%;"></td>
                        <td style="width: 31%; text-align: center; font-weight: bold;">Mengetahui</td>
                    </tr>
                    <tr>
                        <td style="height: 70px;"></td>
                        <td></td>
                        <td></td>
                        <td></td>
                        <td></td>
                    </tr>
                    <tr>
                        <td style="text-align: center; vertical-align: bottom; white-space: nowrap;">
                            <u>&nbsp; &nbsp; <?= !empty($nm_created_by) ? $nm_created_by : '-' ?> &nbsp; &nbsp;</u><br>
                            <?= !empty($tgl_created) ? $tgl_created : '-' ?>
                        </td>
                        <td></td>
                        <td style="text-align: center; vertical-align: bottom; white-space: nowrap;">
                            <u>&nbsp; &nbsp; Fikri &nbsp; &nbsp;</u><br>
                            <?= !empty($tgl_approved) ? $tgl_approved : '-' ?>
                        </td>
                        <td></td>
                        <td style="text-align: center; vertical-align: bottom; white-space: nowrap;">
                            <u>&nbsp; &nbsp; <?= !empty($nm_approved_by) ? $nm_approved_by : '-' ?> &nbsp; &nbsp;</u><br>
                            <?= !empty($tgl_approved) ? $tgl_approved : '-' ?>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <div class="box" <?= $d_none ?>>
        <div class="box-body">
            <div style="width: 50% !important;">
                <table style="width: 100%; font-size: 11px;">
                    <tr>
                        <th>Tgl Approve <?= $tipe2 ?> oleh Direktur</th>
                        <th>:</th>
                        <th>
                            <?= date('d F Y H:i:s', strtotime($tgl_approve_direktur)) ?>
                        </th>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    
    <?php if (isset($list_bukti_penggunaan) && !empty($list_bukti_penggunaan)) : ?>
    <div class="box" style="padding-top: 3vh;">
        <div class="box-body">
            <div style="width: 50% !important;">
                <?php 
                    foreach($list_bukti_penggunaan as $item) :
                        echo '<img src="'.base_url($item->upload_file).'" img="500">';
                    endforeach;
                ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
</body>

</html>