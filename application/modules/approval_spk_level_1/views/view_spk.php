<?php
$ENABLE_ADD     = has_permission('Approval_SPK_Level_1.Add');
$ENABLE_MANAGE  = has_permission('Approval_SPK_Level_1.Manage');
$ENABLE_VIEW    = has_permission('Approval_SPK_Level_1.View');
$ENABLE_DELETE  = has_permission('Approval_SPK_Level_1.Delete');

$ttl_persen_komisi = ($list_spk_penawaran->persen_pemberi_informasi_1_komisi + $list_spk_penawaran->persen_pemberi_informasi_2_komisi + $list_spk_penawaran->persen_sales_1_komisi + $list_spk_penawaran->persen_sales_2_komisi);

$ttl_nominal_komisi = ($list_spk_penawaran->nominal_pemberi_informasi_1_komisi + $list_spk_penawaran->nominal_pemberi_informasi_2_komisi + $list_spk_penawaran->nominal_sales_1_komisi + $list_spk_penawaran->nominal_sales_2_komisi);

$status_sales = '';
$status_project_leader = '';
$status_konsultan_1 = '';
$status_konsultan_2 = '';

if ($list_spk_penawaran->approval_sales_sts == 1) {
    $status_sales = '<span class="btn btn-sm btn-success">Approved</span>';
}
if ($list_spk_penawaran->reject_sales_sts == 1) {
    $status_sales = '<span class="btn btn-sm btn-danger">Rejected</span>';
}

if ($list_spk_penawaran->approval_project_leader_sts == 1) {
    $status_project_leader = '<span class="btn btn-sm btn-success">Approved</span>';
}
if ($list_spk_penawaran->reject_project_leader_sts == 1) {
    $status_project_leader = '<span class="btn btn-sm btn-danger">Rejected</span>';
}

if ($list_spk_penawaran->approval_konsultan_1_sts == 1) {
    $status_konsultan_1 = '<span class="btn btn-sm btn-success">Approved</span>';
}
if ($list_spk_penawaran->reject_konsultan_1_sts == 1) {
    $status_konsultan_1 = '<span class="btn btn-sm btn-danger">Rejected</span>';
}

if ($list_spk_penawaran->approval_konsultan_2_sts == 1) {
    $status_konsultan_2 = '<span class="btn btn-sm btn-success">Approved</span>';
}
if ($list_spk_penawaran->reject_konsultan_2_sts == 1) {
    $status_konsultan_2 = '<span class="btn btn-sm btn-danger">Rejected</span>';
}

$date_sales = '';
if ($list_spk_penawaran->approval_sales_sts == 1) {
    $date_sales = date('d F Y H:i:s', strtotime($list_spk_penawaran->approval_sales_date));
}
if ($list_spk_penawaran->reject_sales_sts == 1) {
    $date_sales = date('d F Y H:i:s', strtotime($list_spk_penawaran->reject_sales_date));
}

$date_project_leader = '';
if ($list_spk_penawaran->approval_project_leader_sts == 1) {
    $date_project_leader = date('d F Y H:i:s', strtotime($list_spk_penawaran->approval_project_leader_date));
}
if ($list_spk_penawaran->reject_project_leader_sts == 1) {
    $date_project_leader = date('d F Y H:i:s', strtotime($list_spk_penawaran->reject_project_leader_date));
}

$date_konsultan_1 = '';
if ($list_spk_penawaran->approval_konsultan_1_sts == 1) {
    $date_konsultan_1 = date('d F Y H:i:s', strtotime($list_spk_penawaran->approval_konsultan_1_date));
}
if ($list_spk_penawaran->reject_konsultan_1_sts == 1) {
    $date_konsultan_1 = date('d F Y H:i:s', strtotime($list_spk_penawaran->reject_konsultan_1_date));
}

$date_konsultan_2 = '';
if ($list_spk_penawaran->approval_konsultan_2_sts == 1) {
    $date_konsultan_2 = date('d F Y H:i:s', strtotime($list_spk_penawaran->approval_konsultan_2_date));
}
if ($list_spk_penawaran->reject_konsultan_2_sts == 1) {
    $date_konsultan_2 = date('d F Y H:i:s', strtotime($list_spk_penawaran->reject_konsultan_2_date));
}

$reason_sales = '';
if ($list_spk_penawaran->reject_sales_sts == 1) {
    $reason_sales = $list_spk_penawaran->reject_sales_reason;
}

$reason_project_leader = '';
if ($list_spk_penawaran->reject_project_leader_sts == 1) {
    $reason_project_leader = $list_spk_penawaran->reject_project_leader_reason;
}

$reason_konsultan_1 = '';
if ($list_spk_penawaran->reject_konsultan_1_sts == 1) {
    $reason_konsultan_1 = $list_spk_penawaran->reject_konsultan_1_reason;
}

$reason_konsultan_2 = '';
if ($list_spk_penawaran->reject_konsultan_2_sts == 1) {
    $reason_konsultan_2 = $list_spk_penawaran->reject_konsultan_2_reason;
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
</style>


<div class="box">
    <div class="box-header">
        <h4>Data Client</h4>
    </div>

    <div class="box-body">

        <table border="0" style="width: 100%;">
            <tr>
                <td class="pd-5 semi-bold" valign="top" width="110">Customer</td>
                <td class="pd-5" width="400" valign="top">
                    <input type="text" name="customer" id="" class="form-control form-control-sm text-center" value="<?= $list_customer->nm_customer ?>" readonly>
                </td>
                <!-- <td width="100"></td> -->
                <td class="pd-5 semi-bold" valign="top" width="110">No. SPK</td>
                <td class="pd-5" width="500" valign="top">
                    <input type="text" name="id_quotation" id="" class="form-control form-control-sm text-center" value="<?= $list_spk_penawaran->id_spk_penawaran ?>" readonly>
                </td>
            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top" rowspan="2">Alamat</td>
                <td class="pd-5" width="400" valign="top" rowspan="2">
                    <textarea name="alamat" id="" class="form-control form-control-sm" rows="4" readonly><?= $list_customer->alamat ?></textarea>
                </td>
                <!-- <td width="100"></td> -->
                <td class="pd-5 semi-bold" valign="top">No. NPWP</td>
                <td class="pd-5" width="500" valign="top">
                    <input type="text" name="no_npwp" id="" class="form-control form-control-sm text-center" value="<?= $list_customer->npwp ?>" readonly> <br>
                </td>
            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top"></td>
                <td class="pd-5" width="500" valign="top">

                </td>
            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top">PIC</td>
                <td class="pd-5" width="400" valign="top">
                    <div class="form-inline">
                        <div class="form-group text-center">
                            <input type="text" name="pic" id="" class="form-control form-control-sm" value="<?= $list_customer->nm_pic ?>" readonly>
                        </div>
                        <div class="form-group text-center" style="width: 100px;">
                            <label for="">Jabatan</label>
                        </div>
                        <div class="form-group text-center">
                            <input type="text" name="jabatan_pic" id="" class="form-control form-control-sm" value="<?= strtoupper($list_customer->jabatan_pic) ?>" readonly>
                        </div>
                    </div>
                </td>

                <td class="pd-5" valign="top">

                </td>
            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top">Kontak PIC</td>
                <td class="pd-5" width="500" valign="top">
                    <input type="text" name="kontak_pic" id="" class="form-control form-control-sm" value="<?= $list_customer->no_hp_pic ?>" readonly>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="box">
    <div class="box-body">
        <table border="0" style="width: 100%;">
            <tr>
                <td colspan="2">
                    <h4>Marketing</h4>
                </td>
                <td colspan="2">
                    <h4>Informasi Awal Eksternal</h4>
                </td>
            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top" width="210">Sales</td>
                <td class="pd-5" width="400" valign="top">
                    <input type="text" name="customer" id="" class="form-control form-control-sm text-center" value="<?= ucfirst($list_marketing->nm_karyawan) ?>" readonly>
                </td>
                <td class="pd-5 semi-bold" valign="top" width="210">
                    <input type="radio" name="informasi_awal_eksternal" class="iae_bs" id="" value="bs" onclick="iae('bs')" <?= ($list_spk_penawaran->tipe_info_awal_eks == 'bs') ? 'checked' : '' ?> disabled> Badan Sertifikasi
                </td>
                <td class="pd-5" width="500" valign="top">
                    <div class="form-inline">
                        <div class="form-group text-center">
                            <input type="text" name="informasi_awal_eksternal_detail_bs" id="" class="form-control form-control-sm iae_bs" value="<?= ($list_spk_penawaran->tipe_info_awal_eks == 'bs') ? $list_spk_penawaran->detail_info_awal_eks : '' ?>" readonly>
                        </div>
                        <div class="form-group text-center" style="width: 95px;" valign="middle">
                            CP
                        </div>
                        <div class="form-group text-center">
                            <input type="text" name="informasi_awal_eksternal_cp_bs" id="" class="form-control form-control-sm iae_bs" value="<?= ($list_spk_penawaran->tipe_info_awal_eks == 'bs') ? $list_spk_penawaran->cp_info_awal_eks : '' ?>" readonly>
                        </div>
                    </div>
                </td>
            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top" width="210">Informasi Awal</td>
                <td class="pd-5" width="400" valign="top">
                    <div class="form-inline">
                        <div class="form-group text-center">
                            <input type="text" name="pic" id="" class="form-control form-control-sm" value="<?= ucfirst($detail_informasi_awal) ?>" readonly>
                        </div>
                        <div class="form-group text-center" style="width: 95px;">
                            <input type="radio" name="tipe_informasi_awal" id="" <?= ($list_penawaran->sts_cust == '1') ? 'checked' : null ?> disabled> RO
                        </div>
                        <div class="form-group text-center" style="width: 95px;">
                            <input type="radio" name="tipe_informasi_awal" id="" <?= ($list_penawaran->sts_cust == '0') ? 'checked' : null ?> disabled> NC
                        </div>
                    </div>
                </td>
                <td class="pd-5 semi-bold" valign="top" width="210">
                    <input type="radio" name="informasi_awal_eksternal" class="iae_lain" id="" onclick="iae('lain')" <?= ($list_spk_penawaran->tipe_info_awal_eks == 'lain') ? 'checked' : '' ?> disabled> Lain - lain
                </td>
                <td class="pd-5" width="500" valign="top">
                    <div class="form-inline">
                        <div class="form-group text-center">
                            <input type="text" name="informasi_awal_eksternal_detail_lain" id="" class="form-control form-control-sm iae_lain" value="<?= ($list_spk_penawaran->tipe_info_awal_eks == 'lain') ? $list_spk_penawaran->detail_info_awal_eks : '' ?>" readonly>
                        </div>
                        <div class="form-group text-center" style="width: 95px;" valign="middle">
                            CP
                        </div>
                        <div class="form-group text-center">
                            <input type="text" name="informasi_awal_eksternal_cp_lain" id="" class="form-control form-control-sm iae_lain" value="<?= ($list_spk_penawaran->tipe_info_awal_eks == 'lain') ? $list_spk_penawaran->cp_info_awal_eks : '' ?>" readonly>
                        </div>
                    </div>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="box">
    <div class="box-body">
        <table border="0" style="width: 100%">
            <tr>
                <td class="pd-5 semi-bold" valign="top" width="120">
                    Project
                </td>
                <td class="pd-5" width="390" valign="top">
                    <input type="text" name="nm_paket" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->nm_project ?>" readonly>
                </td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <!-- <td class="pd-5 semi-bold" valign="top" rowspan="2">Project</td>
                    <td class="pd-5" width="390" valign="top" rowspan="2">
                        <textarea name="" id="" class="form-control form-control-sm" readonly><?= $nm_paket ?></textarea>
                    </td> -->
                <td class="pd-5 semi-bold" valign="top" width="120">Project Leader</td>
                <td class="pd-5" width="390" valign="top">
                    <select name="project_leader" id="" class="form-control form-control-sm select_project_leader" disabled>
                        <option value="">- Select Project Leader -</option>
                        <?php
                        foreach ($list_all_marketing as $item) {
                            $selected = '';
                            if ($item->id == $list_spk_penawaran->id_project_leader) {
                                $selected = 'selected';
                            }
                            echo '<option value="' . $item->id . '" ' . $selected . '>' . ucfirst($item->nm_karyawan) . '</option>';
                        }
                        ?>
                    </select>
                </td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top" width="120">Konsultan 1</td>
                <td class="pd-5" width="390" valign="top">
                    <select name="konsultan_1" id="" class="form-control form-control-sm select_konsultan_1" disabled>
                        <option value="">- Select Konsultan 1 -</option>
                        <?php
                        foreach ($list_all_marketing as $item) {
                            $selected = '';
                            if ($list_spk_penawaran->id_divisi == $item->id) {
                                $selected = 'selected';
                            }
                            echo '<option value="' . $item->id . '" ' . $selected . '>' . ucfirst($item->nama) . '</option>';
                            // echo '<option value="' . $item->id . '">' . ucfirst($item->nama) . '</option>';
                        }
                        ?>
                    </select>
                </td>
                <td></td>
                <td></td>
            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top" width="120">Konsultan 2</td>
                <td class="pd-5" width="390" valign="top">
                    <select name="konsultan_2" id="" class="form-control form-control-sm select_konsultan_2" disabled>
                        <option value="">- Select Konsultan 2 -</option>
                        <?php
                        foreach ($list_all_marketing as $item) {
                            $selected = '';
                            if ($item->id == $list_spk_penawaran->id_konsultan_2) {
                                $selected = 'selected';
                            }
                            echo '<option value="' . $item->id . '" ' . $selected . '>' . ucfirst($item->nm_karyawan) . '</option>';
                        }
                        ?>
                    </select>
                </td>
                <td></td>
                <td></td>
            </tr>
        </table>
    </div>
</div>

<div class="box">
    <div class="box-header">
        <h4 style="font-weight: 600;">Subcont</h4>
    </div>
    <div class="box-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="text-center">Activity Name</th>
                    <th class="text-center">Mandays Internal</th>
                    <th class="text-center">Mandays Rate Internal</th>
                    <th class="text-center">Mandays Tandem</th>
                    <th class="text-center">Mandays Rate Tandem</th>
                    <th class="text-center">Mandays Subcont</th>
                    <th class="text-center">Price Subcont</th>
                    <th class="text-center">Total</th>
                    <th class="text-center">Grand Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                $total_mandays = 0;
                $total_mandays_rate = 0;

                $total_mandays_tandem = 0;
                $total_mandays_rate_tandem = 0;

                $ttl_grand_total = 0;

                $ttl_mandays_subcont = 0;
                $ttl_subcont = 0;
                foreach ($list_spk_penawaran_subcont as $item) {
                    $total_internal = ($item->mandays * $item->mandays_rate);
                    $total_tandem = ($item->mandays_tandem * $item->mandays_rate_tandem);
                    $total_subcont = ($item->mandays_subcont * $item->price_subcont);

                    $nilai_grand_total = ($total_internal + $total_tandem + $total_subcont);

                    echo '<tr class="subcont_' . $no . '">';
                    echo '<td>';
                    echo '<select class="form-control form-control-sm chosen_select" name="dt[' . $no . '][id_aktifitas]" disabled>';
                    foreach ($list_all_aktifitas as $item_aktifitas) {
                        if ($item_aktifitas->id_aktifitas == $item->id_aktifitas) {
                            echo '<option value="' . $item_aktifitas->id_aktifitas . '" ' . $selected . '>' . $item_aktifitas->nm_aktifitas . '</option>';
                        }
                    }
                    echo '</select>';
                    echo '</td>';
                    echo '<td class="text-center">' . $item->mandays . ' <input type="hidden" name="dt[' . $no . '][mandays]" value="' . $item->mandays . '"></td>';
                    echo '<td class="text-center">' . number_format($item->mandays_rate, 2) . ' <input type="hidden" name="dt[' . $no . '][mandays_rate]" value="' . $item->mandays_rate . '"></td>';
                    echo '</td>';
                    echo '<td class="text-center">' . $item->mandays_tandem . ' <input type="hidden" name="dt[' . $no . '][mandays_tandem]" value="' . $item->mandays_tandem . '"></td>';
                    echo '<td class="text-center">' . number_format($item->mandays_rate_tandem, 2) . ' <input type="hidden" name="dt[' . $no . '][mandays_rate_tandem]" value="' . $item->mandays_rate_tandem . '"></td>';
                    echo '<td>';
                    echo '<input type="text" class="form-control form-control-sm edit_mandays_subcont mandays_subcont_' . $item->id . '" name="dt[' . $no . '][mandays_subcont]" data-id="' . $item->id . '" value="' . $item->mandays_subcont . '" readonly>';
                    echo '</td>';
                    echo '<td>';
                    echo '<input type="text" class="form-control form-control-sm text-right edit_price_subcont price_subcont_' . $item->id . ' auto_num" name="dt[' . $no . '][price_subcont]" value="' . $item->price_subcont . '" data-id="' . $item->id . '" readonly>';
                    echo '</td>';
                    echo '<td>';
                    echo '<input type="text" class="form-control form-control-sm total_subcont_' . $item->id . ' auto_num text-right" name="dt[' . $no . '][total_subcont]" value="' . $item->total_subcont . '" readonly>';
                    echo '</td>';
                    echo '<td class="text-center">';
                    echo '<input type="text" class="form-control form-control-sm text-right auto_num" name="dt[' . $no . '][grand_total]" value="' . $nilai_grand_total . '" readonly>';
                    echo '</td>';
                    echo '</tr>';

                    $total_mandays += $item->mandays;
                    $total_mandays_rate += $item->mandays_rate;

                    $total_mandays_tandem += $item->mandays_tandem;
                    $total_mandays_rate_tandem += $item->mandays_rate_tandem;

                    $ttl_mandays_subcont += $item->mandays_subcont;

                    $ttl_subcont += $item->total_subcont;

                    $ttl_grand_total += $nilai_grand_total;

                    $no++;
                }
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>Total</th>
                    <th class="text-center ttl_mandays"><?= $total_mandays ?></th>
                    <th class="text-center ttl_mandays"><?= number_format($total_mandays_rate, 2) ?></th>
                    <th class="text-center ttl_mandays_tandem"><?= $total_mandays_tandem ?></th>
                    <th class="text-center ttl_mandays_rate_tandem"><?= number_format($total_mandays_rate_tandem, 2) ?></th>
                    <th class="text-center ttl_mandays_subcont"><?= $ttl_mandays_subcont ?></th>
                    <th class="text-center"></th>
                    <th class="text-center ttl_total_subcont"><?= number_format($ttl_subcont, 2) ?></th>
                    <th class="text-center ttl_grand_total"><?= number_format($ttl_grand_total, 2) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="box">
    <div class="box-header">
        <h4>Waktu</h4>
    </div>

    <div class="box-body">
        <table border="0" style="width: 100%">
            <tr>
                <td class="pd-5 semi-bold" valign="top">Mulai</td>
                <td class="pd-5" width="400" valign="top">
                    <div class="form-inline">
                        <div class="form-group text-center">
                            <input type="date" name="waktu_from" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->waktu_from ?>" readonly>
                        </div>
                        <div class="form-group text-center" style="width: 30px;" valign="middle">
                            -
                        </div>
                        <div class="form-group text-center">
                            <input type="date" name="waktu_to" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->waktu_to ?>" readonly>
                        </div>
                    </div>
                </td>
                <td class="pd-5 semi-bold" valign="top">Nilai Kontrak</td>
                <td class="pd-5" width="400" valign="top">
                    <div class="form-inline">
                        <div class="form-group text-center">
                            <input type="text" name="nilai_kontrak" id="" class="form-control form-control-sm text-right" value="<?= number_format($list_spk_penawaran->nilai_kontrak, 2) ?>" readonly>
                        </div>
                        <div class="form-group text-center" style="width: auto;">
                            <input type="checkbox" name="" id="" style="margin-left: 1rem;" <?= ($list_penawaran->ppn == '1') ? 'checked' : null ?> disabled> Include PPN
                        </div>
                    </div>
                </td>

            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top">Divisi</td>
                <td class="pd-5" width="400" valign="top">
                    <select name="divisi" id="" class="form-control form-control-sm select_divisi" disabled>
                        <option value="">- Select Divisi -</option>
                        <?php
                        foreach ($list_divisi as $item) {
                            if ($list_spk_penawaran->id_divisi == $item_id) {
                                echo '<option value="' . $item->id . '">' . ucfirst($item->nama) . '</option>';
                            }
                            // echo '<option value="' . $item->id . '">' . ucfirst($item->nama) . '</option>';
                        }
                        ?>
                    </select>
                </td>
                <td class="pd-5 semi-bold" valign="top">Biaya Akomodasi</td>
                <td class="pd-5" valign="top">
                    <input type="text" name="biaya_akomodasi" id="" class="form-control form-control-sm text-right" value="<?= number_format($list_spk_penawaran->biaya_akomodasi, 2) ?>" readonly>
                </td>

            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top">Total Mandays</td>
                <td class="pd-5" width="400" valign="top">
                    <input type="text" name="total_mandays" id="" class="form-control form-control-sm text-right" value="<?= number_format($list_spk_penawaran->total_mandays) ?>" readonly>
                </td>
                <td class="pd-5 semi-bold" valign="top">Biaya Subcont</td>
                <td class="pd-5" valign="top">
                    <input type="text" name="biaya_subcont" id="" class="form-control form-control-sm text-right biaya_subcont" value="<?= number_format($list_spk_penawaran->biaya_subcont, 2) ?>" readonly>
                </td>
            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top">Mandays Subcont</td>
                <td class="pd-5" width="400" valign="top">
                    <input type="text" name="mandays_subcont" id="" class="form-control form-control-sm text-right total_mandays_subcont" value="<?= number_format($ttl_mandays_subcont) ?>" readonly>
                </td>
                <td class="pd-5 semi-bold" valign="top">Biaya Others</td>
                <td class="pd-5" valign="top">
                    <input type="text" name="biaya_others" id="" class="form-control form-control-sm text-right" value="<?= number_format($list_spk_penawaran->biaya_others, 2) ?>" readonly>
                </td>

            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top">Mandays Internal</td>
                <td class="pd-5" width="400" valign="top">
                    <input type="text" name="mandays_internal" id="" class="form-control form-control-sm text-right total_mandays_internal" value="<?= number_format($list_spk_penawaran->mandays_internal) ?>" readonly>
                </td>
                <td class="pd-5 semi-bold" valign="top">Nilai Kontrak Bersih</td>
                <td class="pd-5" valign="top">
                    <input type="text" name="nilai_kontrak_bersih" id="" class="form-control form-control-sm text-right total_nilai_kontrak_bersih" value="<?= number_format($list_spk_penawaran->nilai_kontrak_bersih, 2) ?>" readonly>
                </td>

            </tr>
            <tr>

                <td class="pd-5 semi-bold" valign="top">Mandays Rate</td>
                <td class="pd-5" valign="top">
                    <input type="text" name="mandays_rate" id="" class="form-control form-control-sm text-right total_mandays_rate" value="<?= number_format($list_spk_penawaran->mandays_rate, 2) ?>" readonly>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="box">
    <div class="box-header">
        <table style="width: 100%;" border="0">
            <tr>
                <th valign="top">
                    <h4 style="font-weight: 600;">Term of Payment</h4>
                    <div class="top-total-project">
                        <span style="font-weight: 400">Grand Total Project</span> <br>
                        <div class="text-left">
                            &nbsp;&nbsp;&nbsp;<span style="font-weight: bold; font-size: 20px;">Rp. <?= number_format($list_penawaran->grand_total, 2) ?></span>
                            <input type="hidden" name="" id="" class="form-control form-control-sm text-right auto_num nilai_project" value="<?= $list_penawaran->grand_total ?>">
                        </div>
                    </div>
                    <!-- <table style="width: 250px; border:1px solid #ccc; border-radius: 100%;" border="0">
                            <tr>
                                <td style="padding: 10px; border:1px solid #ccc; border-radius: 100%;">
                                    
                                </td>
                            </tr>
                        </table> -->
                </th>
                <th class="text-right" valign="top">

                </th>
            </tr>
        </table>
    </div>

    <div class="box-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="text-center">Term of Payment</th>
                    <th class="text-center">Persentase</th>
                    <th class="text-center">Nominal</th>
                    <th class="text-center">Description</th>
                </tr>
            </thead>
            <tbody class="list_payment_term">
                <?php
                $ttl_persen_payment = 0;
                $ttl_nominal_payment = 0;
                foreach ($list_spk_penawaran_payment as $item) {
                    echo '<tr>';

                    echo '<td class="text-center">' . $item->term_payment . '</td>';
                    echo '<td class="text-center">' . number_format($item->persen_payment, 2) . '</td>';
                    echo '<td class="text-center">' . number_format($item->nominal_payment, 2) . '</td>';
                    echo '<td class>' . $item->desc_payment . '</td>';

                    $ttl_persen_payment += $item->persen_payment;
                    $ttl_nominal_payment += $item->nominal_payment;

                    echo '</tr>';
                }
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <th></th>
                    <th class="text-center ttl_persentase_payment"><?= number_format($ttl_persen_payment, 2) ?></th>
                    <th class="text-center ttl_nominal_payment"><?= number_format($ttl_nominal_payment, 2) ?></th>
                    <th class="text-center"></th>
                    <th class="text-center"></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="box">
    <div class="box-header">
        <h4 style="font-weight: 600;">Komisi</h4>
    </div>

    <div class="box-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="text-center">Komisi</th>
                    <th class="text-center">Nama</th>
                    <th class="text-center">Persentase Komisi</th>
                    <th class="text-center">Nominal</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Pemberi Informasi 1</td>
                    <td>
                        <input type="text" name="nm_pemberi_informasi_1_komisi" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->nm_pemberi_informasi_1_komisi; ?>" readonly>
                    </td>
                    <td>
                        <input type="text" name="persentase_pemberi_informasi_1_komisi" id="" class="form-control form-control-sm text-right" onchange="persen_komisi('pemberi_informasi_1')" value="<?= number_format($list_spk_penawaran->persen_pemberi_informasi_1_komisi, 2) ?>" readonly>
                    </td>
                    <td>
                        <input type="text" name="nominal_pemberi_informasi_1_komisi" id="" class="form-control form-control-sm text-right" value="<?= number_format($list_spk_penawaran->nominal_pemberi_informasi_1_komisi, 2) ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td>Pemberi Informasi 2</td>
                    <td>
                        <input type="text" name="nm_pemberi_informasi_2_komisi" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->nm_pemberi_informasi_2_komisi ?>" readonly>
                    </td>
                    <td>
                        <input type="text" name="persentase_pemberi_informasi_2_komisi" id="" class="form-control form-control-sm text-right" onchange="persen_komisi('pemberi_informasi_2')" value="<?= number_format($list_spk_penawaran->persen_pemberi_informasi_2_komisi, 2) ?>" readonly>
                    </td>
                    <td>
                        <input type="text" name="nominal_pemberi_informasi_2_komisi" id="" class="form-control form-control-sm text-right" value="<?= number_format($list_spk_penawaran->nominal_pemberi_informasi_2_komisi, 2) ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td>Sales 1</td>
                    <td>
                        <input type="text" name="nm_sales_1_komisi" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->nm_sales_1_komisi ?>" readonly>
                    </td>
                    <td>
                        <input type="text" name="persentase_sales_1_komisi" id="" class="form-control form-control-sm text-right" onchange="persen_komisi('sales_1')" value="<?= number_format($list_spk_penawaran->persen_sales_1_komisi, 2) ?>" readonly>
                    </td>
                    <td>
                        <input type="text" name="nominal_sales_1_komisi" id="" class="form-control form-control-sm text-right" value="<?= number_format($list_spk_penawaran->nominal_sales_1_komisi, 2) ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td>Sales 2</td>
                    <td>
                        <input type="text" name="nm_sales_2_komisi" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->nm_sales_2_komisi ?>" readonly>
                    </td>
                    <td>
                        <input type="text" name="persentase_sales_2_komisi" id="" class="form-control form-control-sm text-right" onchange="persen_komisi('sales_2')" value="<?= number_format($list_spk_penawaran->persen_sales_2_komisi, 2) ?>" readonly>
                    </td>
                    <td>
                        <input type="text" name="nominal_sales_2_komisi" id="" class="form-control form-control-sm text-right" value="<?= number_format($list_spk_penawaran->nominal_sales_2_komisi) ?>" readonly>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th></th>
                    <th></th>
                    <th class="text-right ttl_persen_komisi"><?= number_format($ttl_persen_komisi, 2) ?></th>
                    <th class="text-right ttl_nilai_komisi"><?= number_format($ttl_nominal_komisi, 2) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="box">
    <div class="box-header">
        <h4 style="font-weight: 600;">Isu Khusus dan Komitmen</h4>
    </div>

    <div class="box-body">
        <div class="form-group">
            <label for="">Isu Khusus / Permintaan khusus dari customer / Tujuan Program / 3 objective utama (khusus konsultasi)</label>
            <textarea name="isu_khusus" id="" class="form-control form-control-sm" rows="10" readonly><?= $list_spk_penawaran->isu_khusus ?></textarea>
        </div>
    </div>
</div>

<div class="box">
    <div class="box-header">
        <table style="width: 100%;" border="0">
            <tr>
                <th valign="top">
                    <h4 style="font-weight: 600;">Approval History</h4>
                </th>
                <th class="text-right" valign="top">

                </th>
            </tr>
        </table>
    </div>

    <div class="box-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="text-center">Posisi</th>
                    <th class="text-center">Nama</th>
                    <th class="text-center">Status</th>
                    <th class="text-center">Date</th>
                    <th class="text-center">Reason</th>
                </tr>
            </thead>
            <tbody class="list_payment_term">
                <tr>
                    <td class="text-center">Sales</td>
                    <td class="text-center"><?= $list_spk_penawaran->nm_sales ?></td>
                    <td class="text-center"><?= $status_sales ?></td>
                    <td class="text-center"><?= $date_sales ?></td>
                    <td class="text-center"><?= $reason_sales ?></td>
                </tr>
                <tr>
                    <td class="text-center">Project Leader</td>
                    <td class="text-center"><?= $list_spk_penawaran->nm_project_leader ?></td>
                    <td class="text-center"><?= $status_project_leader ?></td>
                    <td class="text-center"><?= $date_project_leader ?></td>
                    <td class="text-center"><?= $reason_project_leader ?></td>
                </tr>
                <tr>
                    <td class="text-center">Konsultan 1</td>
                    <td class="text-center"><?= $list_spk_penawaran->nm_konsultan_1 ?></td>
                    <td class="text-center"><?= $status_konsultan_1 ?></td>
                    <td class="text-center"><?= $date_konsultan_1 ?></td>
                    <td class="text-center"><?= $reason_konsultan_1 ?></td>
                </tr>
                <tr>
                    <td class="text-center">Konsultan 2</td>
                    <td class="text-center"><?= $list_spk_penawaran->nm_konsultan_2 ?></td>
                    <td class="text-center"><?= $status_konsultan_2 ?></td>
                    <td class="text-center"><?= $date_konsultan_2 ?></td>
                    <td class="text-center"><?= $reason_konsultan_2 ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<a href="<?= base_url('approval_spk_level_1'); ?>" class="btn btn-sm btn-danger"><i class="fa fa-arrow-left"></i> Back</a>



<input type="hidden" name="no_payment" value="1">
<script src="<?= base_url('assets/js/autoNumeric.js'); ?>"></script>
<script>
    var no_payment = parseFloat($('input[name="no_payment"]').val());
    $(document).ready(function() {
        $('.chosen_select').chosen({
            width: "250px"
        });

        $('.select_divisi').chosen();
        $('.select_project_leader').chosen();
        $('.select_konsultan_1').chosen();
        $('.select_konsultan_2').chosen();

        $('.auto_num').autoNumeric();
    });
</script>