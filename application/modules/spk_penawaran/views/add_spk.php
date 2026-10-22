<?php
$ENABLE_ADD     = has_permission('SPK.Add');
$ENABLE_MANAGE  = has_permission('SPK.Manage');
$ENABLE_VIEW    = has_permission('SPK.View');
$ENABLE_DELETE  = has_permission('SPK.Delete');
?>
<!-- <link rel="stylesheet" href="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.css') ?>"> -->
<link rel="stylesheet" href="https://cdn.datatables.net/2.1.7/css/dataTables.dataTables.min.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

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

    .select2-container {
        width: 100% !important;
    }

    table {
        table-layout: fixed;
        width: 100%;
    }

    td,
    th {
        word-wrap: break-word;
        overflow: hidden;
    }
</style>

<form action="" method="post" id="frm-data">
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
                        <input type="text" name="id_spk_penawaran" id="" class="form-control form-control-sm text-center" value="">
                        <input type="hidden" name="id_quotation" id="" value="<?= $list_penawaran->id_quotation ?>" readonly>
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
                    <td class="pd-5 semi-bold" valign="top" style="max-width: 200px;">
                        <input type="radio" name="informasi_awal_eksternal" class="iae_bs" id="" value="bs" onclick="iae('bs')"> Badan Sertifikasi
                    </td>
                    <td class="pd-5" width="500" valign="top">
                        <div class="form-inline">
                            <div class="form-group text-center">
                                <input type="text" name="informasi_awal_eksternal_detail_bs" id="" class="form-control form-control-sm iae_bs" readonly>
                            </div>
                            <div class="form-group text-center" style="width: 95px;" valign="middle">
                                CP
                            </div>
                            <div class="form-group text-center">
                                <input type="text" name="informasi_awal_eksternal_cp_bs" id="" class="form-control form-control-sm iae_bs" readonly>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="210">Informasi Awal</td>
                    <td class="pd-5" valign="top" style="max-width: 400px;">
                        <div class="form-inline">
                            <div class="form-group text-center">
                                <input type="text" name="pic" id="" class="form-control form-control-sm" value="<?= ucfirst($detail_informasi_awal) ?>" readonly>
                            </div>
                            <div class="form-group text-center" style="width: 95px;">
                                <input type="radio" name="tipe_informasi_awal" value="1" id="" <?= ($list_penawaran->sts_cust == '1') ? 'checked' : null ?>> RO
                            </div>
                            <div class="form-group text-center" style="width: 95px;">
                                <input type="radio" name="tipe_informasi_awal" value="0" id="" <?= ($list_penawaran->sts_cust == '0') ? 'checked' : null ?>> NC
                            </div>
                        </div>
                    </td>
                    <td class="pd-5 semi-bold" valign="top" style="max-width: 200px;">
                        <input type="radio" name="informasi_awal_eksternal" class="iae_lain" id="" onclick="iae('lain')"> Lain - lain
                    </td>
                    <td class="pd-5" valign="top" style="max-width: 400px;">
                        <div class="form-inline">
                            <div class="form-group text-center">
                                <input type="text" name="informasi_awal_eksternal_detail_lain" id="" class="form-control form-control-sm iae_lain" readonly>
                            </div>
                            <div class="form-group text-center" style="width: 95px;" valign="middle">
                                CP
                            </div>
                            <div class="form-group text-center">
                                <input type="text" name="informasi_awal_eksternal_cp_lain" id="" class="form-control form-control-sm iae_lain" readonly>
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
                    <td class="pd-5 semi-bold" valign="top" width="135">
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
                    <td class="pd-5 semi-bold" valign="top" width="135">Project Leader <span class="text-red">*</span></td>
                    <td class="pd-5" width="390" valign="top">
                        <select name="project_leader" id="" class="form-control form-control-sm select_project_leader" required>
                            <option value="">- Select Project Leader -</option>
                            <?php
                            foreach ($list_all_marketing as $item) {
                                echo '<option value="' . $item->id . '">' . ucfirst($item->nm_karyawan) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="135">Konsultan 1</td>
                    <td class="pd-5" width="390" valign="top">
                        <select name="konsultan_1" id="" class="form-control form-control-sm select_konsultan_1">
                            <option value="">- Select Konsultan 1 -</option>
                            <?php
                            foreach ($list_all_marketing as $item) {
                                echo '<option value="' . $item->id . '">' . ucfirst($item->nm_karyawan) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="135">Konsultan 2</td>
                    <td class="pd-5" width="390" valign="top">
                        <select name="konsultan_2" id="" class="form-control form-control-sm select_konsultan_2">
                            <option value="">- Select Konsultan 2 -</option>
                            <?php
                            foreach ($list_all_marketing as $item) {
                                echo '<option value="' . $item->id . '">' . ucfirst($item->nm_karyawan) . '</option>';
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
                        <th class="text-center" width="180">Activity Name</th>
                        <th class="text-center" width="50">Mandays Internal</th>
                        <th class="text-center" width="50">Mandays Rate Internal</th>
                        <th class="text-center" width="50">Mandays Tandem</th>
                        <th class="text-center" width="50">Mandays Rate Tandem</th>
                        <th class="text-center" width="50">Grand Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 1;

                    $total_mandays = 0;
                    $total_mandays_rate = 0;
                    $total_mandays_subcont = 0;
                    $total_mandays_rate_subcont = 0;
                    $total_mandays_tandem = 0;
                    $total_mandays_rate_tandem = 0;
                    $total_activity = 0;

                    $ttl_grand_total = 0;

                    $nilai_tandem = 0;

                    foreach ($list_aktifitas as $item) {
                        $total_internal = ($item->mandays * $item->mandays_rate);
                        $total_tandem = ($item->mandays_tandem * $item->mandays_rate_tandem);
                        $total_subcont = ($item->mandays_subcont * $item->mandays_rate_subcont);

                        $nilai_grand_total = ($total_internal + $total_tandem + $total_subcont);

                        echo '<tr class="subcont_' . $no . '">';
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
                    <tr>
                        <th colspan="2">Total</th>
                        <th class="text-center ttl_mandays"><?= $total_mandays ?></th>
                        <th class="text-center "></th>
                        <th class="text-center ttl_mandays_tandem"><?= $total_mandays_tandem ?></th>
                        <th class="text-center "></th>
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
                                <input type="date" name="waktu_from" id="" class="form-control form-control-sm">
                            </div>
                            <div class="form-group text-center" style="width: 30px;" valign="middle">
                                -
                            </div>
                            <div class="form-group text-center">
                                <input type="date" name="waktu_to" id="" class="form-control form-control-sm">
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
                        <select name="divisi" id="" class="form-control form-control-sm select_divisi">
                            <option value="">- Select Divisi -</option>
                            <?php
                            foreach ($list_divisi as $item) {
                                echo '<option value="' . $item->id . '">' . ucfirst($item->nama) . '</option>';
                            }
                            ?>
                        </select>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">Biaya Akomodasi</td>
                    <td class="pd-5" valign="top">
                        <input type="text" name="biaya_akomodasi" id="" class="form-control form-control-sm text-right" value="<?= number_format($nilai_akomodasi, 2) ?>" readonly>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info btn_detail" data-type="akomodasi" data-id_quotation="<?= $list_penawaran->id_quotation ?>"><i class="fa fa-eye"></i> Detail</button>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top">Total Mandays</td>
                    <td class="pd-5" width="400" valign="top">
                        <input type="text" name="total_mandays" id="" class="form-control form-control-sm text-right" value="<?= number_format($total_mandays, 2) ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">Biaya Others</td>
                    <td class="pd-5" valign="top">
                        <input type="text" name="biaya_others" id="" class="form-control form-control-sm text-right" value="<?= number_format($nilai_others, 2) ?>" readonly>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info btn_detail" data-type="others" data-id_quotation="<?= $list_penawaran->id_quotation ?>"><i class="fa fa-eye"></i> Detail</button>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top">Mandays Tandem</td>
                    <td class="pd-5" valign="top">
                        <input type="text" name="mandays_tandem" id="" class="form-control form-control-sm text-right " value="<?= number_format($total_mandays_tandem, 2) ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">Biaya Lab</td>
                    <td class="pd-5" valign="top">
                        <input type="text" name="biaya_lab" id="" class="form-control form-control-sm text-right biaya_lab" value="<?= number_format($nilai_lab, 2) ?>" readonly>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info btn_detail" data-type="lab" data-id_quotation="<?= $list_penawaran->id_quotation ?>"><i class="fa fa-eye"></i> Detail</button>
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
                        <button type="button" class="btn btn-sm btn-info btn_detail" data-type="subcont_tenaga_ahli" data-id_quotation="<?= $list_penawaran->id_quotation ?>"><i class="fa fa-eye"></i> Detail</button>
                    </td>
                </tr>
                <tr>
                    <td colspan="2"></td>
                    <td class="pd-5 semi-bold" valign="top">Biaya Subcont Perusahaan</td>
                    <td class="pd-5" valign="top">
                        <input type="text" name="biaya_subcont_perusahaan" id="" class="form-control form-control-sm text-right total_biaya_subcont_perusahaan" value="<?= number_format($nilai_subcont_perusahaan, 2) ?>" readonly>
                    </td>
                    <td>
                        <button type="button" class="btn btn-sm btn-info btn_detail" data-type="subcont_perusahaan" data-id_quotation="<?= $list_penawaran->id_quotation ?>"><i class="fa fa-eye"></i> Detail</button>
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

    <!-- <div class="box">
        <div class="box-body">
            <table border="0" style="width: 100%;">
                <tr>
                    <td class="pd-5 semi-bold" valign="top">Nilai Kontrak</td>
                    <td class="pd-5" width="370" valign="top">
                        <input type="text" name="nilai_kontrak" id="" class="form-control form-control-sm auto_num text-right" value="<?= $nilai_kontrak ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">Total Mandays</td>
                    <td class="pd-5" width="370" valign="top">
                        <input type="text" name="total_mandays" id="" class="form-control form-control-sm text-right" value="<?= $total_mandays ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top">Biaya Subcont</td>
                    <td class="pd-5" width="370" valign="top">
                        <input type="text" name="biaya_subcont" id="" class="form-control form-control-sm auto_num text-right biaya_subcont" value="0" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">Mandays Subcont</td>
                    <td class="pd-5" width="370" valign="top">
                        <input type="text" name="mandays_subcont" id="" class="form-control form-control-sm text-right mandays_subcont" value="0" readonly>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top">Nilai Internal</td>
                    <td class="pd-5" width="370" valign="top">
                        <input type="text" name="nilai_internal" id="" class="form-control form-control-sm auto_num text-right nilai_internal" value="<?= $nilai_kontrak; ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">Mandays Internal</td>
                    <td class="pd-5" width="370" valign="top">
                        <input type="text" name="mandays_internal" id="" class="form-control form-control-sm text-right" value="<?= $total_mandays ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top">Mandays Rate</td>
                    <td class="pd-5" width="370" valign="top">
                        <input type="text" name="mandays_rate" id="" class="form-control form-control-sm auto_num text-right mandays_rate" value="<?= ($nilai_kontrak / $total_mandays) ?>" readonly>
                    </td>
                    <td colspan="3"></td>
                </tr>
            </table>
        </div>
    </div> -->

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
                        <button type="button" class="btn btn-sm btn-success add_payment_term">
                            <i class="fa fa-plus"></i> Add
                        </button>
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
                        <th class="text-center">Option</th>
                    </tr>
                </thead>
                <tbody class="list_payment_term">

                </tbody>
                <tfoot>
                    <tr>
                        <th></th>
                        <th class="text-center ttl_persentase_payment">0.00</th>
                        <th class="text-center ttl_nominal_payment">0.00</th>
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
                            <input type="text" name="nm_pemberi_informasi_1_komisi" id="" class="form-control form-control-sm" value="<?= ucfirst($detail_informasi_awal) ?>">
                        </td>
                        <td>
                            <input type="text" name="persentase_pemberi_informasi_1_komisi" id="" class="form-control form-control-sm text-right" onchange="persen_komisi('pemberi_informasi_1')" value="2">
                        </td>
                        <td>
                            <input type="text" name="nominal_pemberi_informasi_1_komisi" id="" class="form-control form-control-sm text-right" value="<?= number_format($nilai_kontrak_bersih * 2 / 100, 2) ?>" readonly>
                        </td>
                    </tr>
                    <tr>
                        <td>Pemberi Informasi 2</td>
                        <td>
                            <input type="text" name="nm_pemberi_informasi_2_komisi" id="" class="form-control form-control-sm">
                        </td>
                        <td>
                            <input type="text" name="persentase_pemberi_informasi_2_komisi" id="" class="form-control form-control-sm text-right" onchange="persen_komisi('pemberi_informasi_2')">
                        </td>
                        <td>
                            <input type="text" name="nominal_pemberi_informasi_2_komisi" id="" class="form-control form-control-sm text-right" readonly>
                        </td>
                    </tr>
                    <tr>
                        <td>Sales 1</td>
                        <td>
                            <input type="text" name="nm_sales_1_komisi" id="" class="form-control form-control-sm" value="<?= ucfirst($list_marketing->nm_karyawan) ?>">
                        </td>
                        <td>
                            <input type="text" name="persentase_sales_1_komisi" id="" class="form-control form-control-sm text-right" value="2" onchange="persen_komisi('sales_1')">
                        </td>
                        <td>
                            <input type="text" name="nominal_sales_1_komisi" id="" class="form-control form-control-sm text-right" value="<?= number_format($nilai_kontrak_bersih * 2 / 100, 2) ?>" readonly>
                        </td>
                    </tr>
                    <tr>
                        <td>Sales 2</td>
                        <td>
                            <input type="text" name="nm_sales_2_komisi" id="" class="form-control form-control-sm">
                        </td>
                        <td>
                            <input type="text" name="persentase_sales_2_komisi" id="" class="form-control form-control-sm text-right" onchange="persen_komisi('sales_2')">
                        </td>
                        <td>
                            <input type="text" name="nominal_sales_2_komisi" id="" class="form-control form-control-sm text-right" readonly>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th></th>
                        <th></th>
                        <th class="text-right ttl_persen_komisi"><?= number_format(4, 2) ?></th>
                        <th class="text-right ttl_nilai_komisi"><?= number_format($nilai_kontrak_bersih * 4 / 100, 2) ?></th>
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
                <textarea name="isu_khusus" id="" class="form-control form-control-sm" rows="10"></textarea>
            </div>
        </div>
    </div>

    <a href="<?= base_url('spk_penawaran'); ?>" class="btn btn-sm btn-danger"><i class="fa fa-arrow-left"></i> Back</a>
    <button type="submit" class="btn btn-sm btn-primary">
        <i class="fa fa-save"></i> Save
    </button>
</form>

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
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
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

    function iae(tipe) {
        if (tipe == 'bs') {
            $('.iae_' + tipe).attr('readonly', false);

            $('.iae_lain').val('');
            $('.iae_lain').attr('readonly', true);
        }
        if (tipe == 'lain') {
            $('.iae_' + tipe).attr('readonly', false);

            $('.iae_bs').val('');
            $('.iae_bs').attr('readonly', true);
        }
    }

    function get_num(nilai = null) {
        if (nilai !== '' && nilai !== null) {
            nilai = nilai.split(',').join('');
            if (isNaN(nilai)) {
                nilai = 0;
            } else {
                nilai = parseFloat(nilai);
            }
        } else {
            nilai = 0;
        }

        return nilai;
    }

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

    function hitung_total_subcont() {

        var nilai_kontrak = get_num($('input[name="nilai_kontrak"]').val());
        var biaya_akomodasi = get_num($('input[name="biaya_akomodasi"]').val());
        var biaya_others = get_num($('input[name="biaya_others"]').val());
        var biaya_tandem = get_num($('input[name="biaya_tandem"]').val());
        var biaya_lab = get_num($('input[name="biaya_lab"]').val());
        var biaya_subcont_tenaga_ahli = get_num($('input[name="biaya_subcont_tenaga_ahli"]').val());
        var biaya_subcont_perusahaan = get_num($('input[name="biaya_subcont_perusahaan"]').val());
        var total_mandays = get_num($('input[name="total_mandays"]').val());

        $('input[name="nilai_kontrak_bersih"]').val(number_format((nilai_kontrak - biaya_akomodasi - biaya_others - biaya_lab - biaya_subcont_tenaga_ahli - biaya_subcont_perusahaan), 2));

        var mandays_rate = parseFloat((nilai_kontrak - biaya_akomodasi - biaya_others - biaya_tandem - biaya_lab - biaya_subcont_tenaga_ahli - biaya_subcont_perusahaan) / total_mandays);

        $('.total_mandays_rate').val(number_format(mandays_rate, 2));
    }

    function persen_komisi(tipe) {
        var persentase = get_num($('input[name="persentase_' + tipe + '_komisi"]').val());
        // if (persentase > 2) {
        //    swal({
        //        type: 'warning',
        //        title: 'Warning !',
        //        text: 'Persen komisi tidak boleh lebih dari 2% !'
        //    });

        //    persentase = 2;

        //    $('input[name="persentase_' + tipe + '_komisi"]').val(persentase);
        // }
        var nilai_internal = get_num($('.total_nilai_kontrak_bersih').val());

        var nilai_komisi = parseFloat(nilai_internal * persentase / 100);

        $('input[name="nominal_' + tipe + '_komisi"]').val(number_format(nilai_komisi, 2));

        var persen_pemberi_informasi_1 = get_num($('input[name="persentase_pemberi_informasi_1_komisi"]').val());
        var persen_pemberi_informasi_2 = get_num($('input[name="persentase_pemberi_informasi_2_komisi"]').val());
        var persen_sales_1 = get_num($('input[name="persentase_sales_1_komisi"]').val());
        var persen_sales_2 = get_num($('input[name="persentase_sales_2_komisi"]').val());

        var nominal_pemberi_informasi_1 = get_num($('input[name="nominal_pemberi_informasi_1_komisi"]').val());
        var nominal_pemberi_informasi_2 = get_num($('input[name="nominal_pemberi_informasi_2_komisi"]').val());
        var nominal_sales_1 = get_num($('input[name="nominal_sales_1_komisi"]').val());
        var nominal_sales_2 = get_num($('input[name="nominal_sales_2_komisi"]').val());

        var ttl_persen = parseFloat(persen_pemberi_informasi_1 + persen_pemberi_informasi_2 + persen_sales_1 + persen_sales_2);
        var ttl_nominal = parseFloat(nominal_pemberi_informasi_1 + nominal_pemberi_informasi_2 + nominal_sales_1 + nominal_sales_2);

        $('.ttl_persen_komisi').html(number_format(ttl_persen, 2));
        $('.ttl_nilai_komisi').html(number_format(ttl_nominal, 2));
    }

    function hitung_ttl_payment() {
        var no_payment = get_num($('input[name="no_payment"]').val());

        var ttl_persen_payment = 0;
        for (i = 1; i <= no_payment; i++) {
            var persen_payment = get_num($('input[name="pt[' + i + '][persen_payment]"]').val());

            ttl_persen_payment += persen_payment;
        }

        var ttl_nominal_payment = 0;
        for (i = 1; i <= no_payment; i++) {
            var nominal_payment = get_num($('input[name="pt[' + i + '][nominal_payment]"]').val());

            ttl_nominal_payment += nominal_payment;
        }

        $('.ttl_persentase_payment').html(number_format(ttl_persen_payment, 2));
        $('.ttl_nominal_payment').html(number_format(ttl_nominal_payment, 2));
    }

    function hitung_grand_ttl_subcont() {
        var no = '<?= $no ?>';

        var ttl_grand_total = 0;
        for (i = 1; i <= no; i++) {
            var mandays = get_num($('input[name="dt[' + i + '][mandays]"]').val());
            var mandays_rate = get_num($('input[name="dt[' + i + '][mandays_rate]"]').val());
            var mandays_tandem = get_num($('input[name="dt[' + i + '][mandays_tandem]"]').val());
            var mandays_rate_tandem = get_num($('input[name="dt[' + i + '][mandays_rate_tandem]"]').val());

            var total_internal = (mandays * mandays_rate);
            var total_tandem = (mandays_tandem * mandays_rate_tandem);

            var grand_total = (total_internal + total_tandem);

            ttl_grand_total += grand_total;

            $('input[name="dt[' + i + '][grand_total]"]').val(number_format(grand_total, 2));
        }

        $('input[name="nilai_kontrak"]').val(number_format(ttl_grand_total, 2));
        $('.ttl_grand_total').html(number_format(ttl_grand_total, 2));

        var nilai_kontrak = ttl_grand_total;
        var biaya_akomodasi = get_num($('input[name="biaya_akomodasi"]').val());
        var biaya_others = get_num($('input[name="biaya_others"]').val());
        var biaya_tandem = get_num($('input[name="biaya_tandem"]').val());
        var biaya_lab = get_num($('input[name="biaya_lab"]').val());
        var biaya_subcont_tenaga_ahli = get_num($('input[name="biaya_subcont_tenaga_ahli"]').val());
        var biaya_subcont_perusahaan = get_num($('input[name="biaya_subcont_perusahaan"]').val());

        var nilai_kontrak_bersih = (nilai_kontrak - biaya_akomodasi - biaya_others - biaya_tandem - biaya_lab - biaya_subcont_tenaga_ahli - biaya_subcont_perusahaan);

        $('input[name="nilai_kontrak_bersih"]').val(number_format(nilai_kontrak_bersih, 2));

        $('input[name="nilai_kontrak_bersih"]').val(number_format(nilai_kontrak_bersih, 2));
    }

    $(document).on('click', '.add_payment_term', function() {
        var hasil = '<tr class="payment_' + no_payment + '">';

        hasil += '<td>';
        hasil += '<input type="text" class="form-control form-control-sm" name="pt[' + no_payment + '][term_payment]">';
        hasil += '</td>';

        hasil += '<td>';
        hasil += '<input type="text" class="form-control form-control-sm auto_num text-right persen_payment" name="pt[' + no_payment + '][persen_payment]" data-no="' + no_payment + '">';
        hasil += '</td>';

        hasil += '<td>';
        hasil += '<input type="text" class="form-control form-control-sm auto_num text-right nominal_payment" name="pt[' + no_payment + '][nominal_payment]" data-no="' + no_payment + '">';
        hasil += '</td>';

        hasil += '<td>';
        hasil += '<input type="text" class="form-control form-control-sm" name="pt[' + no_payment + '][desc_payment]">';
        hasil += '</td>';

        hasil += '<td class="text-center">';
        hasil += '<button type="button" class="btn btn-sm btn-danger del_term_payment" data-no_payment="' + no_payment + '"><i class="fa fa-trash"></i></button>';
        hasil += '</td>';

        hasil += '</tr>';

        $('.list_payment_term').append(hasil);

        $('.auto_num').autoNumeric();

        no_payment += 1;

        $('input[name="no_payment"]').val(no_payment);
    });

    $(document).on('click', '.del_term_payment', function() {
        var no_payment = $(this).data('no_payment');

        $('.payment_' + no_payment).remove();
    });

    $(document).on('submit', '#frm-data', function(e) {
        e.preventDefault();

        var ttl_persen_komisi = get_num($('.ttl_persen_komisi').html());
        var ttl_persentase_payment = get_num($('.ttl_persentase_payment').html());
        var waktu_from = $('input[name="waktu_from"]').val();
        var waktu_to = $('input[name="waktu_to"]').val();
        var project_leader = $('input[name="project_leader"]').val();

        var ttl_nominal_payment = get_num($('.ttl_nominal_payment').text());
        var nilai_kontrak_bersih = get_num($('.nilai_project').val());

        // if (ttl_persen_komisi > 5) {
        //     Swal.fire({
        //         icon: 'warning',
        //         title: 'Warning !',
        //         text: 'Total Persentase Komisi tidak boleh lebih dari 4%'
        //     });

        //     return false;
        // }
        // else if (ttl_nominal_payment != nilai_kontrak_bersih) {
        // Swal.fire({
        // icon: 'warning',
        // title: 'Warning !',
        // text: 'Persentase payment harus 100% !'
        // });

        // return false;
        //} 
        if (waktu_from == '' || waktu_to == '') {
            Swal.fire({
                icon: 'warning',
                title: 'Warning !',
                text: 'Pastikan Kolom waktu sudah terisi !'
            });

            return false;
        } else if (project_leader == '') {
            Swal.fire({
                icon: 'warning',
                title: 'Warning !',
                text: 'Project leader wajib diisi !'
            });

            return false;
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Are you sure ?',
                text: 'This data will be saved !',
                showCancelButton: true
            }).then((next) => {
                if (next.isConfirmed) {
                    var formData = $('#frm-data').serialize();

                    $.ajax({
                        type: "POST",
                        url: siteurl + active_controller + 'save_spk_penawaran',
                        data: formData,
                        cache: false,
                        dataType: "JSON",
                        success: function(result) {
                            if (result.status == 1) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success !',
                                    text: result.msg,
                                    allowOutsideClick: false,
                                    showConfirmButton: false,
                                    timer: 3000
                                }).then((after) => {
                                    window.location.href = siteurl + active_controller;
                                });
                            } else {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Failed !',
                                    text: result.msg,
                                    allowOutsideClick: false,
                                    showConfirmButton: false,
                                    timer: 3000
                                });
                            }
                        },
                        error: function(result) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error !',
                                text: 'Please try again later !',
                                allowOutsideClick: false,
                                showConfirmButton: false,
                                timer: 3000
                            });
                        }
                    })
                }
            });
        }
    });

    $(document).on('change', '.persen_payment', function() {
        var no = $(this).data('no');
        var nilai_project = "<?= $nilai_project ?>";
        var persen = get_num($(this).val());

        var nilai_payment = parseFloat(nilai_project * persen / 100);

        $('input[name="pt[' + no + '][nominal_payment]"]').val(number_format(nilai_payment, 2));

        hitung_ttl_payment();
    });

    $(document).on('change', '.nominal_payment', function() {
        var no = $(this).data('no');
        var nilai_project = "<?= $nilai_project ?>";
        var nilai_payment = get_num($(this).val());

        var persen_payment = parseFloat(nilai_payment / nilai_project * 100);

        $('input[name="pt[' + no + '][persen_payment]"]').val(number_format(persen_payment, 2));

        hitung_ttl_payment();
    });

    $(document).on('click', '.btn_detail', function() {
        var id_penawaran = $(this).data('id_quotation');
        var type = $(this).data('type');

        $.ajax({
            type: 'post',
            url: siteurl + active_controller + 'detail_sum',
            data: {
                'id_penawaran': id_penawaran,
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
                    text: 'Please try again later !'
                });
            }
        });
    });
</script>