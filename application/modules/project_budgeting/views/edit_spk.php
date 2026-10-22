<?php
$ENABLE_ADD     = has_permission('SPK.Add');
$ENABLE_MANAGE  = has_permission('SPK.Manage');
$ENABLE_VIEW    = has_permission('SPK.View');
$ENABLE_DELETE  = has_permission('SPK.Delete');

$ttl_persen_komisi = ($list_spk_penawaran->persen_pemberi_informasi_1_komisi + $list_spk_penawaran->persen_pemberi_informasi_2_komisi + $list_spk_penawaran->persen_sales_1_komisi + $list_spk_penawaran->persen_sales_2_komisi);

$ttl_nominal_komisi = ($list_spk_penawaran->nominal_pemberi_informasi_1_komisi + $list_spk_penawaran->nominal_pemberi_informasi_2_komisi + $list_spk_penawaran->nominal_sales_1_komisi + $list_spk_penawaran->nominal_sales_2_komisi);

$nilai_akomodasi = $list_spk_penawaran->biaya_akomodasi;
$nilai_others = $list_spk_penawaran->biaya_others;
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
                        <select name="project_leader" id="" class="form-control form-control-sm select_project_leader">
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
                        <select name="konsultan_1" id="" class="form-control form-control-sm select_konsultan_1">
                            <option value="">- Select Konsultan 1 -</option>
                            <?php
                            foreach ($list_all_marketing as $item) {
                                $selected = '';
                                if ($item->id == $list_spk_penawaran->id_konsultan_1) {
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
                    <td class="pd-5 semi-bold" valign="top" width="120">Konsultan 2</td>
                    <td class="pd-5" width="390" valign="top">
                        <select name="konsultan_2" id="" class="form-control form-control-sm select_konsultan_2">
                            <option value="">- Select Konsultan 2 -</option>
                            <?php
                            foreach ($list_all_marketing as $item) {
                                $selected = '';
                                if ($item->id == $list_spk_penawaran->id_konsultan_2) {
                                    $selected = 'selected';
                                }
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
            <h4>Waktu</h4>
        </div>

        <div class="box-body">
            <table border="0" style="width: 100%">
                <tr>
                    <td class="pd-5 semi-bold" valign="top">Mulai</td>
                    <td class="pd-5" width="400" valign="top">
                        <div class="form-inline">
                            <div class="form-group text-center">
                                <input type="date" name="waktu_from" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->waktu_from ?>">
                            </div>
                            <div class="form-group text-center" style="width: 30px;" valign="middle">
                                -
                            </div>
                            <div class="form-group text-center">
                                <input type="date" name="waktu_to" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->waktu_to ?>">
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
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top">Mandays Internal</td>
                    <td class="pd-5" width="400" valign="top">
                        <input type="text" name="mandays_internal" id="" class="form-control form-control-sm text-right total_mandays_internal" value="<?= number_format($total_mandays) ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">Biaya Tandem</td>
                    <td class="pd-5" valign="top">
                        <input type="text" name="biaya_tandem" id="" class="form-control form-control-sm text-right biaya_tandem" value="<?= number_format($nilai_tandem, 2) ?>" readonly>
                    </td>


                </tr>
                <tr>
                    <?php
                    $nilai_kontrak_bersih = ($nilai_project - $nilai_akomodasi - $nilai_others - $nilai_tandem);
                    ?>
                    <td class="pd-5 semi-bold" valign="top">Mandays Rate</td>
                    <td class="pd-5" valign="top">
                        <input type="text" name="mandays_rate" id="" class="form-control form-control-sm text-right total_mandays_rate" value="<?= number_format($nilai_kontrak_bersih / ($total_mandays), 2) ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">Nilai Kontrak Bersih</td>
                    <td class="pd-5" valign="top">
                        <input type="text" name="nilai_kontrak_bersih" id="" class="form-control form-control-sm text-right total_nilai_kontrak_bersih" value="<?= number_format($nilai_kontrak_bersih, 2) ?>" readonly>
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
                    <?php
                    $no_payment = 1;

                    $ttl_persen_payment = 0;
                    $ttl_nominal_payment = 0;
                    foreach ($list_spk_penawaran_payment as $item) {
                        echo '<tr class="payment_' . $no_payment . '">';
                        echo '<td>';
                        echo '<input type="text" class="form-control form-control-sm" name="pt[' . $no_payment . '][term_payment]" value="' . $item->term_payment . '">';
                        echo '</td>';

                        echo '<td>';
                        echo '<input type="text" class="form-control form-control-sm auto_num text-right persen_payment" name="pt[' . $no_payment . '][persen_payment]" data-no="' . $no_payment . '" value="' . $item->persen_payment . '">';
                        echo '</td>';

                        echo '<td>';
                        echo '<input type="text" class="form-control form-control-sm auto_num text-right nominal_payment" name="pt[' . $no_payment . '][nominal_payment]" data-no="' . $no_payment . '" value="' . $item->nominal_payment . '">';
                        echo '</td>';

                        echo '<td>';
                        echo '<input type="text" class="form-control form-control-sm" name="pt[' . $no_payment . '][desc_payment]" value="' . $item->desc_payment . '">';
                        echo '</td>';

                        echo '<td class="text-center">';
                        echo '<button type="button" class="btn btn-sm btn-danger del_term_payment" data-no_payment="' . $no_payment . '"><i class="fa fa-trash"></i></button>';
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
                        <th class="text-right ttl_persentase_payment"><?= number_format($ttl_persen_payment, 2) ?></th>
                        <th class="text-right ttl_nominal_payment"><?= number_format($ttl_nominal_payment, 2) ?></th>
                        <th class="text-right"></th>
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
                            <input type="text" name="nm_pemberi_informasi_1_komisi" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->nm_pemberi_informasi_1_komisi; ?>">
                        </td>
                        <td>
                            <input type="text" name="persentase_pemberi_informasi_1_komisi" id="" class="form-control form-control-sm text-right" onchange="persen_komisi('pemberi_informasi_1')" value="<?= number_format($list_spk_penawaran->persen_pemberi_informasi_1_komisi, 2) ?>">
                        </td>
                        <td>
                            <input type="text" name="nominal_pemberi_informasi_1_komisi" id="" class="form-control form-control-sm text-right" value="<?= number_format($list_spk_penawaran->nominal_pemberi_informasi_1_komisi, 2) ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>Pemberi Informasi 2</td>
                        <td>
                            <input type="text" name="nm_pemberi_informasi_2_komisi" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->nm_pemberi_informasi_2_komisi ?>">
                        </td>
                        <td>
                            <input type="text" name="persentase_pemberi_informasi_2_komisi" id="" class="form-control form-control-sm text-right" onchange="persen_komisi('pemberi_informasi_2')" value="<?= number_format($list_spk_penawaran->persen_pemberi_informasi_2_komisi, 2) ?>">
                        </td>
                        <td>
                            <input type="text" name="nominal_pemberi_informasi_2_komisi" id="" class="form-control form-control-sm text-right" value="<?= number_format($list_spk_penawaran->nominal_pemberi_informasi_2_komisi, 2) ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>Sales 1</td>
                        <td>
                            <input type="text" name="nm_sales_1_komisi" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->nm_sales_1_komisi ?>">
                        </td>
                        <td>
                            <input type="text" name="persentase_sales_1_komisi" id="" class="form-control form-control-sm text-right" onchange="persen_komisi('sales_1')" value="<?= number_format($list_spk_penawaran->persen_sales_1_komisi, 2) ?>">
                        </td>
                        <td>
                            <input type="text" name="nominal_sales_1_komisi" id="" class="form-control form-control-sm text-right" value="<?= number_format($list_spk_penawaran->nominal_sales_1_komisi, 2) ?>">
                        </td>
                    </tr>
                    <tr>
                        <td>Sales 2</td>
                        <td>
                            <input type="text" name="nm_sales_2_komisi" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->nm_sales_2_komisi ?>">
                        </td>
                        <td>
                            <input type="text" name="persentase_sales_2_komisi" id="" class="form-control form-control-sm text-right" onchange="persen_komisi('sales_2')" value="<?= number_format($list_spk_penawaran->persen_sales_2_komisi, 2) ?>">
                        </td>
                        <td>
                            <input type="text" name="nominal_sales_2_komisi" id="" class="form-control form-control-sm text-right" value="<?= number_format($list_spk_penawaran->nominal_sales_2_komisi) ?>">
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
                <textarea name="isu_khusus" id="" class="form-control form-control-sm" rows="10"><?= $list_spk_penawaran->isu_khusus ?></textarea>
            </div>
        </div>
    </div>

    <a href="<?= base_url('spk_penawaran'); ?>" class="btn btn-sm btn-danger"><i class="fa fa-arrow-left"></i> Back</a>
    <button type="submit" class="btn btn-sm btn-primary">
        <i class="fa fa-save"></i> Save
    </button>
</form>

<input type="hidden" name="no_payment" value="<?= $no_payment ?>">
<script src="<?= base_url('assets/js/autoNumeric.js'); ?>"></script>
<script>
    var no_payment = parseFloat($('input[name="no_payment"]').val());
    $(document).ready(function() {
        $('.chosen_select').chosen({
            width: "300px"
        });

        $('.select_divisi').chosen();
        $('.select_project_leader').chosen();
        $('.select_konsultan_1').chosen();
        $('.select_konsultan_2').chosen();

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
            nilai = parseFloat(nilai);
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





    function persen_komisi(tipe) {
        var persentase = get_num($('input[name="persentase_' + tipe + '_komisi"]').val());
        // if (persentase > 2) {
        //     Swal.fire({
        //         icon: 'warning',
        //         title: 'Warning !',
        //         text: 'Persen komisi tidak boleh lebih dari 2% !'
        //     });

        //     persentase = 2;

        //     $('input[name="persentase_' + tipe + '_komisi"]').val(persentase);
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

        // if (ttl_persen_komisi > 5) {
        //     Swal.fire({
        //         icon: 'warning',
        //         title: 'Warning !',
        //         text: 'Total Persentase Komisi tidak boleh lebih dari 4% !'
        //     });

        //     return false;
        // } else 
        if (ttl_persentase_payment < 100 || ttl_persentase_payment > 100) {
            Swal.fire({
                icon: 'warning',
                title: 'Warning !',
                text: 'Persentase payment harus 100% !'
            });

            return false;
        } else if (waktu_from == '' || waktu_to == '') {
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
                        url: siteurl + active_controller + 'update_spk_penawaran',
                        data: formData,
                        cache: false,
                        dataType: "JSON",
                        success: function(result) {
                            if (result.status == 1) {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success !',
                                    text: result.msg
                                }).then((after) => {
                                    window.location.href = siteurl + active_controller;
                                });
                            } else {
                                Swal.fire({
                                    icon: 'warning',
                                    title: 'Failed !',
                                    text: result.msg
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
</script>