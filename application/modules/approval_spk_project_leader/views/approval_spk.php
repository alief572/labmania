<?php
$ENABLE_ADD     = has_permission('Approval_SPK_Level_1.Add');
$ENABLE_MANAGE  = has_permission('Approval_SPK_Level_1.Manage');
$ENABLE_VIEW    = has_permission('Approval_SPK_Level_1.View');
$ENABLE_DELETE  = has_permission('Approval_SPK_Level_1.Delete');

$ttl_persen_komisi = ($list_spk_penawaran->persen_pemberi_informasi_1_komisi + $list_spk_penawaran->persen_pemberi_informasi_2_komisi + $list_spk_penawaran->persen_sales_1_komisi + $list_spk_penawaran->persen_sales_2_komisi);

$ttl_nominal_komisi = ($list_spk_penawaran->nominal_pemberi_informasi_1_komisi + $list_spk_penawaran->nominal_pemberi_informasi_2_komisi + $list_spk_penawaran->nominal_sales_1_komisi + $list_spk_penawaran->nominal_sales_2_komisi);

$readonly_isu = 'readonly';
if ($data_user->employee_id == '168') {
    $readonly_isu = '';
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
                    <input type="text" name="id_spk_penawaran" id="" class="form-control form-control-sm text-center" value="<?= $list_spk_penawaran->id_spk_penawaran ?>" readonly>
                </td>
            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top" rowspan="2">Alamat</td>
                <td class="pd-5" width="400" valign="top" rowspan="2">
                    <textarea name="address" id="" class="form-control form-control-sm" rows="4" readonly><?= $list_customer->alamat ?></textarea>
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
                    <textarea name="nm_paket" id="" class="form-control form-control-sm" readonly><?= $nm_paket ?></textarea>
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
                        <?php
                        foreach ($list_all_marketing as $item) {
                            if ($item->id == $list_spk_penawaran->id_project_leader) {
                                echo '<option value="' . $item->id . '">' . ucfirst($item->nm_karyawan) . '</option>';
                            }
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
                        <?php
                        foreach ($list_all_marketing as $item) {
                            if ($item->id == $list_spk_penawaran->id_konsultan_1) {
                                echo '<option value="' . $item->id . '">' . ucfirst($item->nm_karyawan) . '</option>';
                            }
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
                        <?php
                        foreach ($list_all_marketing as $item) {
                            if ($item->id == $list_spk_penawaran->id_konsultan_2) {
                                echo '<option value="' . $item->id . '">' . ucfirst($item->nm_karyawan) . '</option>';
                            }
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
        <h4 style="font-weight: 600;">Biaya Konsultan</h4>
    </div>
    <div class="box-body">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th class="text-center" width="20">No.</th>
                    <th class="text-center" width="200">Activity Name</th>
                    <th class="text-center" width="10">Mandays Internal</th>
                    <th class="text-center" width="50">Mandays Rate Internal</th>
                    <th class="text-center" width="10">Mandays Tandem</th>
                    <th class="text-center" width="50">Mandays Rate Tandem</th>
                    <th class="text-center" width="50">Grand Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;

                $total_mandays = 0;
                $total_mandays_rate = 0;
                $total_mandays_tandem = 0;
                $total_mandays_rate_tandem = 0;
                $total_mandays_subcont = 0;
                $total_mandays_rate_subcont = 0;
                $total_activity = 0;

                $ttl_grand_total = 0;

                $nilai_tandem = 0;

                foreach ($list_spk_aktifitas as $item) {
                    $total_internal = ($item->mandays * $item->mandays_rate);
                    $total_tandem = ($item->mandays_tandem * $item->mandays_rate_tandem);

                    $nilai_grand_total = ($total_internal + $total_tandem);

                    echo '<tr class="subcontawd_' . $no . '">';
                    echo '<td class="text-center">' . $no . '</td>';
                    echo '<td>';
                    echo $item->nm_aktifitas;
                    echo '</td>';
                    echo '<td class="text-center">' . $item->mandays . ' <input type="hidden" name="dt[' . $no . '][mandays]" value="' . $item->mandays . '"></td>';
                    echo '<td class="text-center">' . number_format($item->mandays_rate, 2) . ' <input type="hidden" name="dt[' . $no . '][mandays_rate]" value="' . $item->mandays_rate . '"></td>';
                    echo '<td class="text-center">' . $item->mandays_tandem . ' <input type="hidden" name="dt[' . $no . '][mandays_tandem]" value="' . $item->mandays_tandem . '"></td>';
                    echo '<td class="text-center">' . number_format($item->mandays_rate_tandem, 2) . ' <input type="hidden" name="dt[' . $no . '][mandays_rate_tandem]" value="' . $item->mandays_rate_tandem . '"></td>';

                    echo '<td class="text-center">';
                    echo number_format($nilai_grand_total, 2);
                    echo '</td>';
                    echo '</tr>';

                    $total_mandays += $item->mandays;
                    $total_mandays_rate += $item->mandays_rate;
                    $total_mandays_tandem += $item->mandays_tandem;
                    $total_mandays_rate_tandem += $item->mandays_rate_tandem;

                    $nilai_tandem += ($item->mandays_rate_tandem * $item->mandays_tandem);

                    $ttl_grand_total += $nilai_grand_total;

                    $no++;
                }
                ?>
            </tbody>
            <tfoot>
                <th> </th>
                <th>Total</th>
                <th class="text-center ttl_mandays"><?= $total_mandays ?></th>
                <th class="text-center ttl_mandays_rate"><?= number_format($total_mandays_rate, 2) ?></th>
                <th class="text-center ttl_mandays_tandem"><?= $total_mandays_tandem ?></th>
                <th class="text-center ttl_mandays_rate_tandem"><?= number_format($total_mandays_rate_tandem, 2) ?></th>
                <th class="text-center ttl_grand_total"><?= number_format($ttl_grand_total, 2) ?></th>
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
                            <input type="text" name="nilai_kontrak" id="" class="form-control form-control-sm text-right" value="<?= number_format($nilai_project, 2) ?>" readonly>
                        </div>
                        <div class="form-group text-center" style="width: auto;">
                            <input type="checkbox" name="" id="" style="margin-left: 1rem;" <?= ($list_penawaran->ppn == '1') ? 'checked' : null ?> disabled> Include PPN
                        </div>
                    </div>
                </td>
                <td></td>
            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top">Divisi</td>
                <td class="pd-5" width="400" valign="top">
                    <select name="divisi" id="" class="form-control form-control-sm select_divisi" disabled>
                        <?php
                        foreach ($list_divisi as $item) {
                            if ($item->id == $list_spk_penawaran->id_divisi) {
                                echo '<option value="' . $item->id . '">' . ucfirst($item->nama) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </td>
                <td class="pd-5 semi-bold" valign="top">Biaya Akomodasi</td>
                <td class="pd-5" valign="top">
                    <input type="text" name="biaya_akomodasi" id="" class="form-control form-control-sm text-right" value="<?= number_format($nilai_akomodasi, 2) ?>" readonly>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-info btn_detail" data-type="akomodasi" data-id_spk_penawaran="<?= $list_spk_penawaran->id_spk_penawaran ?>"><i class="fa fa-eye"></i> Detail</button>
                </td>
            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top">Total Mandays</td>
                <td class="pd-5" width="400" valign="top">
                    <input type="text" name="total_mandays" id="" class="form-control form-control-sm text-right" value="<?= number_format($total_mandays) ?>" readonly>
                </td>
                <td class="pd-5 semi-bold" valign="top">Biaya Others</td>
                <td class="pd-5" valign="top">
                    <input type="text" name="biaya_others" id="" class="form-control form-control-sm text-right" value="<?= number_format($nilai_others, 2) ?>" readonly>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-info btn_detail" data-type="others" data-id_spk_penawaran="<?= $list_spk_penawaran->id_spk_penawaran ?>"><i class="fa fa-eye"></i> Detail</button>
                </td>
            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top">Mandays Tandem</td>
                <td class="pd-5" valign="top">
                    <input type="text" name="mandays_tandem" id="" class="form-control form-control-sm text-right " value="<?= number_format($total_mandays_tandem) ?>" readonly>
                </td>
                <td class="pd-5 semi-bold" valign="top">Biaya Lab</td>
                <td class="pd-5" valign="top">
                    <input type="text" name="biaya_lab" id="" class="form-control form-control-sm text-right biaya_lab" value="<?= number_format($nilai_lab, 2) ?>" readonly>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-info btn_detail" data-type="lab" data-id_spk_penawaran="<?= $list_spk_penawaran->id_spk_penawaran ?>"><i class="fa fa-eye"></i> Detail</button>
                </td>
            </tr>
            <tr>
                <?php
                $nilai_kontrak_bersih = ($nilai_project - $nilai_akomodasi - $nilai_others - $nilai_tandem - $nilai_lab - $nilai_subcont_tenaga_ahli - $nilai_subcont_perusahaan);
                ?>
                <td class="pd-5 semi-bold" valign="top">Mandays Rate</td>
                <td class="pd-5" valign="top">
                    <input type="text" name="mandays_rate" id="" class="form-control form-control-sm text-right total_mandays_rate" value="<?= number_format($nilai_kontrak_bersih / ($total_mandays), 2) ?>" readonly>
                </td>
                <td class="pd-5 semi-bold" valign="top">Biaya Tandem</td>
                <td class="pd-5" valign="top">
                    <input type="text" name="biaya_tandem" id="" class="form-control form-control-sm text-right biaya_tandem" value="<?= number_format($nilai_tandem, 2) ?>" readonly>
                </td>
                <td></td>
            </tr>
            <tr>
                <td colspan="2"></td>
                <td class="pd-5 semi-bold" valign="top">Biaya Subcont Tenaga Ahli</td>
                <td class="pd-5" valign="top">
                    <input type="text" name="biaya_subcont_tenaga_ahli" id="" class="form-control form-control-sm text-right total_biaya_subcont_tenaga_ahli" value="<?= number_format($nilai_subcont_tenaga_ahli, 2) ?>" readonly>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-info btn_detail" data-type="subcont_tenaga_ahli" data-id_spk_penawaran="<?= $list_spk_penawaran->id_spk_penawaran ?>"><i class="fa fa-eye"></i> Detail</button>
                </td>
            </tr>
            <tr>
                <td colspan="2"></td>
                <td class="pd-5 semi-bold" valign="top">Biaya Subcont Perusahaan</td>
                <td class="pd-5" valign="top">
                    <input type="text" name="biaya_subcont_perusahaan" id="" class="form-control form-control-sm text-right total_biaya_subcont_perusahaan" value="<?= number_format($nilai_subcont_perusahaan, 2) ?>" readonly>
                </td>
                <td>
                    <button type="button" class="btn btn-sm btn-info btn_detail" data-type="subcont_perusahaan" data-id_spk_penawaran="<?= $list_spk_penawaran->id_spk_penawaran ?>"><i class="fa fa-eye"></i> Detail</button>
                </td>
            </tr>
            <tr>
                <td colspan="2"></td>
                <td class="pd-5 semi-bold" valign="top">Nilai Kontrak Bersih</td>
                <td class="pd-5" valign="top">
                    <input type="text" name="nilai_kontrak_bersih" id="" class="form-control form-control-sm text-right total_nilai_kontrak_bersih" value="<?= number_format($nilai_kontrak_bersih, 2) ?>" readonly>
                </td>
                <td></td>
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
                            &nbsp;&nbsp;&nbsp;<span style="font-weight: bold; font-size: 20px;">Rp. <?= number_format($nilai_project, 2) ?></span>
                            <input type="hidden" name="" id="" class="form-control form-control-sm text-right auto_num nilai_project" value="<?= $nilai_project ?>">
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
                $no_payment = 1;

                $ttl_persen_payment = 0;
                $ttl_nominal_payment = 0;
                foreach ($list_spk_penawaran_payment as $item) {
                    echo '<tr class="payment_' . $no_payment . '">';
                    echo '<td>';
                    echo $item->term_payment;
                    echo '</td>';

                    echo '<td class="text-center">';
                    echo number_format($item->persen_payment);
                    echo '</td>';

                    echo '<td class="text-right">';
                    echo number_format($item->nominal_payment, 2);
                    echo '</td>';

                    echo '<td>';
                    echo $item->desc_payment;
                    echo '</td>';

                    echo '</tr>';

                    $ttl_persen_payment += $item->persen_payment;
                    $ttl_nominal_payment += $item->nominal_payment;

                    $no_payment++;
                }
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <th></th>
                    <th class="text-center ttl_persentase_payment"><?= number_format($ttl_persen_payment) ?></th>
                    <th class="text-right ttl_nominal_payment"><?= number_format($ttl_nominal_payment, 2) ?></th>
                    <th class="text-right"></th>
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
            <textarea name="isu_khusus" id="" class="form-control form-control-sm isu_khusus" rows="10" <?= $readonly_isu ?>><?= $list_spk_penawaran->isu_khusus ?></textarea>
        </div>
        <br><br>

        <div class="col-md-6">
            <div class="form-group">
                <label for="">Reject Reason</label>
                <textarea name="" id="" class="form-control form-control-sm reject_reason"><?= $list_spk_penawaran->reject_reason ?></textarea>
            </div>
        </div>
    </div>
</div>

<a href="<?= base_url('approval_spk_project_leader'); ?>" class="btn btn-sm btn-danger"><i class="fa fa-arrow-left"></i> Back</a>
<button type="button" class="btn btn-sm btn-danger reject_spk"><i class="fa fa-close"></i> Reject</button>
<button type="button" class="btn btn-sm btn-success approve_spk"><i class="fa fa-check"></i> Approve</button>

<div class="modal" id="dialog-rekap" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title" id="myModalLabel"></h4>
            </div>
            <div class="modal-body" id="MyModalBody">
                ...
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-danger" data-dismiss="modal">
                    <span class="glyphicon glyphicon-remove"></span> Close</button>
            </div>
        </div>
    </div>
</div>

<input type="hidden" name="no_payment" value="1">
<script src="<?= base_url('assets/js/autoNumeric.js'); ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    var no_payment = parseFloat($('input[name="no_payment"]').val());
    $(document).ready(function() {
        $('.chosen_select').select2({
            width: "100%"
        });

        $('.select_divisi').select2({
            width: '100%'
        });
        $('.select_project_leader').select2({
            width: '100%'
        });
        $('.select_konsultan_1').select2({
            width: '100%'
        });
        $('.select_konsultan_2').select2({
            width: '100%'
        });

        $('.auto_num').autoNumeric();
    });

    $(document).on('click', '.reject_spk', function() {
        var id_spk_penawaran = $('input[name="id_spk_penawaran"]').val();
        var reject_reason = $('.reject_reason').val();

        if (reject_reason == '') {
            Swal.fire({
                icon: 'warning',
                title: 'Warning !',
                text: 'Please input the reject reason first !',
                timer: 2000,
                showConfirmButton: false,
                showCancelButton: false,
                allowOutsideClick: false
            });
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Are you sure ?',
                text: 'You will reject this SPK !',
                showCancelButton: true,
                allowOutsideClick: false
            }).then((next) => {
                if (next.isConfirmed) {
                    $.ajax({
                        type: "POST",
                        url: siteurl + active_controller + 'reject_spk',
                        data: {
                            'id_spk_penawaran': id_spk_penawaran,
                            'reject_reason': reject_reason
                        },
                        cache: false,
                        dataType: "JSON",
                        success: function(result) {
                            if (result.status == 1) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success !',
                                    text: result.pesan,
                                    timer: 2000,
                                    showConfirmButton: false,
                                    showCancelButton: false,
                                    allowOutsideClick: false
                                }).then((after) => {
                                    window.location.href = siteurl + active_controller;
                                });
                            } else {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Failed !',
                                    text: result.pesan,
                                    timer: 2000,
                                    showConfirmButton: false,
                                    showCancelButton: false,
                                    allowOutsideClick: false
                                });
                            }
                        },
                        error: function(result) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error !',
                                text: 'Please try again later !',
                                timer: 2000,
                                showConfirmButton: false,
                                showCancelButton: false,
                                allowOutsideClick: false
                            });
                        }
                    });
                }
            });
        }
    });

    $(document).on('click', '.approve_spk', function() {
        var id_spk_penawaran = $('input[name="id_spk_penawaran"]').val();
        var isu_khusus = $('.isu_khusus').val();

        Swal.fire({
            icon: 'warning',
            title: 'Are you sure ?',
            text: 'You will Approve this SPK !',
            showCancelButton: true,
            allowOutsideClick: false
        }).then((next) => {
            if (next.isConfirmed) {
                $.ajax({
                    type: "POST",
                    url: siteurl + active_controller + 'approve_spk',
                    data: {
                        'id_spk_penawaran': id_spk_penawaran,
                        'isu_khusus': isu_khusus
                    },
                    cache: false,
                    dataType: "JSON",
                    success: function(result) {
                        if (result.status == 1) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success !',
                                text: result.pesan,
                                timer: 2000,
                                showConfirmButton: false,
                                showCancelButton: false,
                                allowOutsideClick: false
                            }).then((lanjut) => {
                                window.location.href = siteurl + active_controller;
                            });
                        } else {
                            Swal.fire({
                                icon: 'warning',
                                title: 'Failed !',
                                text: result.pesan,
                                timer: 2000,
                                showConfirmButton: false,
                                showCancelButton: false,
                                allowOutsideClick: false
                            });
                        }
                    },
                    error: function(result) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error !',
                            text: 'Please try again later !',
                            timer: 2000,
                            showConfirmButton: false,
                            showCancelButton: false,
                            allowOutsideClick: false
                        });
                    }
                });
            }
        });
    });

    $(document).on('click', '.btn_detail', function() {
        var id_spk_penawaran = $(this).data('id_spk_penawaran');
        var type = $(this).data('type');

        $.ajax({
            type: 'post',
            url: siteurl + active_controller + 'detail_sum',
            data: {
                'id_spk_penawaran': id_spk_penawaran,
                'type': type
            },
            cache: false,
            success: function(result) {
                if (type == 'akomodasi') {
                    $('#myModalLabel').html('Detail Akomodasi');
                }
                if (type == 'others') {
                    $('#myModalLabel').html('Detail Others');
                }
                if (type == 'lab') {
                    $('#myModalLabel').html('Detail Lab');
                }
                if (type == 'subcont_tenaga_ahli') {
                    $('#myModalLabel').html('Detail Subcont Tenaga Ahli');
                }
                if (type == 'subcont_perusahaan') {
                    $('#myModalLabel').html('Detail Subcont Perusahaan');
                }
                $('#MyModalBody').html(result);
                $('#dialog-rekap').modal('show');
            },
            error: function(result) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error !',
                    text: 'Please try again later !',
                    timer: 2000,
                    showConfirmButton: false,
                    showCancelButton: false,
                    allowOutsideClick: false
                });
            }
        });
    });
</script>