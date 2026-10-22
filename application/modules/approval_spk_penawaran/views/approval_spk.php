<?php
$ENABLE_ADD     = has_permission('SPK.Add');
$ENABLE_MANAGE  = has_permission('SPK.Manage');
$ENABLE_VIEW    = has_permission('SPK.View');
$ENABLE_DELETE  = has_permission('SPK.Delete');

$ttl_persen_komisi = ($list_spk_penawaran->persentase_pemberi_informasi_komisi + $list_spk_penawaran->persentase_sales_komisi + $list_spk_penawaran->persentase_others_komisi);

$ttl_nominal_komisi = ($list_spk_penawaran->nominal_pemberi_informasi_komisi + $list_spk_penawaran->nominal_sales_komisi + $list_spk_penawaran->nominal_others_komisi);

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
</style>


<div class="box">
    <div class="box-header">

    </div>

    <div class="box-body">
        <table border="0" style="width: 100%;">
            <tr>
                <td class="pd-5 semi-bold" valign="top">Number</td>
                <td class="pd-5" width="390" valign="top">
                    <input type="text" name="id_quotation" id="" class="form-control form-control-sm text-center id_spk_penawaran" value="<?= $list_spk_penawaran->id_spk_penawaran ?>" readonly>
                </td>
                <td class="pd-5 semi-bold" valign="top">Address</td>
                <td class="pd-5" width="390" valign="top">
                    <input type="text" name="address" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->address ?>" readonly>
                </td>
            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top">Customer</td>
                <td class="pd-5" width="390" valign="top">
                    <input type="text" name="customer" id="" class="form-control form-control-sm text-center" value="<?= $list_spk_penawaran->nm_customer ?>" readonly>
                </td>
                <td class="pd-5 semi-bold" valign="top">Waktu</td>
                <td class="pd-5" width="390" valign="top">
                    <table style="width: 100%">
                        <tr>
                            <td>
                                <input type="date" name="waktu_from" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->waktu_from ?>" readonly>
                            </td>
                            <td class="text-center"> - </td>
                            <td>
                                <input type="date" name="waktu_to" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->waktu_to ?>" readonly>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top">PIC</td>
                <td class="pd-5" width="390" valign="top">
                    <input type="text" name="pic" id="" class="form-control form-control-sm text-center" value="<?= $list_spk_penawaran->nm_pic ?>" readonly>
                </td>
                <td class="pd-5 semi-bold" valign="top">Sales</td>
                <td class="pd-5" width="390" valign="top">
                    <input type="text" name="sales" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->nm_sales ?>" readonly>
                </td>
            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top">Informasi Awal</td>
                <td class="pd-5" width="390" valign="top">
                    <table border="0" style="width: 100%">
                        <tr>
                            <td>
                                <input type="checkbox" name="" id="" checked disabled> <?= $list_penawaran->tipe_informasi_awal ?>
                            </td>
                            <td>
                                <input type="text" name="detail_informasi_awal" id="" class="form-control form-control-sm" value="<?= $detail_informasi_awal ?>" readonly>
                            </td>
                        </tr>
                    </table>
                </td>
                <td class="pd-5 semi-bold" valign="top">Upload</td>
                <td class="pd-5" width="390" valign="top">
                    <input type="file_upload" name="sales" id="" class="form-control form-control-sm" disabled>
                    <?php
                    if (
                        $list_spk_penawaran->upload_proposal !== '' &&
                        $list_spk_penawaran->upload_proposal !== null &&
                        file_exists('./uploads/proposal_penawaran/' . $list_spk_penawaran->upload_proposal)
                    ) {
                        echo '<a href="' . base_url('uploads/proposal_penawaran/' . $list_spk_penawaran->upload_proposal) . '" target="_blank" class="btn btn-sm btn-primary" style="margin-top: 1rem;">
                            <i class="fa fa-download"></i> Download Proposal
                        </a>';
                    }
                    ?>
                </td>
            </tr>
        </table>
    </div>
</div>

<div class="box">
    <div class="box-body">
        <table border="0" style="width: 100%">
            <tr>
                <td class="pd-5 semi-bold" valign="top" rowspan="2">Project</td>
                <td class="pd-5" width="390" valign="top" rowspan="2">
                    <textarea name="" id="" class="form-control form-control-sm" readonly><?= $list_spk_penawaran->nm_project ?></textarea>
                </td>
                <td class="pd-5 semi-bold" valign="top">Project Leader</td>
                <td class="pd-5" width="390" valign="top">
                    <select name="project_leader" id="" class="form-control form-control-sm select_project_leader">
                        <?php
                        foreach ($list_all_marketing as $item) {
                            if ($item->id == $list_spk_penawaran->id_project_leader) {
                                echo '<option value="' . $item->id . '">' . $item->nm_karyawan . '</option>';
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top">Konsultan 1</td>
                <td class="pd-5" width="390" valign="top">
                    <select name="konsultan_1" id="" class="form-control form-control-sm select_konsultan_1">
                        <?php
                        foreach ($list_all_marketing as $item) {
                            if ($item->id == $list_spk_penawaran->id_konsultan_1) {
                                echo '<option value="' . $item->id . '">' . $item->nm_karyawan . '</option>';
                            }
                        }
                        ?>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top">Divisi</td>
                <td class="pd-5" width="390" valign="top">
                    <select name="divisi" id="" class="form-control form-control-sm select_divisi">
                        <?php
                        foreach ($list_divisi as $item) {
                            if ($item->id == $list_spk_penawaran->id_divisi) {
                                echo '<option value="' . $item->id . '">' . strtoupper($item->nama) . '</option>';
                            }
                        }
                        ?>
                    </select>
                </td>
                <td class="pd-5 semi-bold" valign="top">Konsultan 2</td>
                <td class="pd-5" width="390" valign="top">
                    <select name="konsultan_2" id="" class="form-control form-control-sm select_konsultan_2">
                        <?php
                        foreach ($list_all_marketing as $item) {
                            if ($item->id == $list_spk_penawaran->id_konsultan_2) {
                                echo '<option value="' . $item->id . '">' . $item->nm_karyawan . '</option>';
                            }
                        }
                        ?>
                    </select>
                </td>
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
                    <th class="text-center">Mandays</th>
                    <th class="text-center">Mandays Subcont</th>
                    <th class="text-center">Price Subcont</th>
                    <th class="text-center">Total</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $no = 1;
                $total_mandays = 0;

                $ttl_mandays_subcont = 0;
                $ttl_subcont = 0;
                foreach ($list_spk_penawaran_subcont as $item) {
                    echo '<tr class="subcont_' . $no . '">';
                    echo '<td>';
                    echo '<select class="form-control form-control-sm chosen_select" name="dt[' . $no . '][id_aktifitas]">';
                    foreach ($list_all_aktifitas as $item_aktifitas) {
                        if ($item_aktifitas->id_aktifitas == $item->id_aktifitas) {
                            echo '<option value="' . $item_aktifitas->id_aktifitas . '" ' . $selected . '>' . $item_aktifitas->nm_aktifitas . '</option>';
                        }
                    }
                    echo '</select>';
                    echo '</td>';
                    echo '<td class="text-center">' . $item->mandays . ' <input type="hidden" name="dt[' . $no . '][mandays]" value="' . $item->mandays . '"></td>';
                    echo '<td>';
                    echo '<input type="text" class="form-control form-control-sm edit_mandays_subcont mandays_subcont_' . $item->id . '" name="dt[' . $no . '][mandays_subcont]" data-id="' . $item->id . '" value="' . $item->mandays_subcont . '" readonly>';
                    echo '</td>';
                    echo '<td>';
                    echo '<input type="text" class="form-control form-control-sm text-right edit_price_subcont price_subcont_' . $item->id . ' auto_num" name="dt[' . $no . '][price_subcont]" value="' . $item->price_subcont . '" data-id="' . $item->id . '" readonly>';
                    echo '</td>';
                    echo '<td>';
                    echo '<input type="text" class="form-control form-control-sm total_subcont_' . $item->id . ' auto_num text-right" name="dt[' . $no . '][total_subcont]" value="' . $item->total_subcont . '" readonly>';
                    echo '</td>';
                    echo '</tr>';

                    $total_mandays += $item->mandays;
                    $ttl_mandays_subcont += $item->mandays_subcont;

                    $ttl_subcont += $item->total_subcont;

                    $no++;
                }
                ?>
            </tbody>
            <tfoot>
                <th>Total</th>
                <th class="text-center ttl_mandays"><?= $total_mandays ?></th>
                <th class="text-center ttl_mandays_subcont"><?= $ttl_mandays_subcont ?></th>
                <th class="text-center"></th>
                <th class="text-center ttl_total_subcont"><?= number_format($ttl_subcont, 2) ?></th>
            </tfoot>
        </table>
    </div>
</div>

<div class="box">
    <div class="box-body">
        <table border="0" style="width: 100%;">
            <tr>
                <td class="pd-5 semi-bold" valign="top">Nilai Kontrak</td>
                <td class="pd-5" width="370" valign="top">
                    <input type="text" name="nilai_kontrak" id="" class="form-control form-control-sm auto_num text-right" value="<?= $list_spk_penawaran->nilai_kontrak ?>" readonly>
                </td>
                <td class="pd-5 semi-bold" valign="top">Total Mandays</td>
                <td class="pd-5" width="370" valign="top">
                    <input type="text" name="total_mandays" id="" class="form-control form-control-sm text-right" value="<?= $list_spk_penawaran->total_mandays ?>" readonly>
                </td>
            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top">Biaya Subcont</td>
                <td class="pd-5" width="370" valign="top">
                    <input type="text" name="biaya_subcont" id="" class="form-control form-control-sm auto_num text-right biaya_subcont" value="<?= $list_spk_penawaran->biaya_subcont ?>" readonly>
                </td>
                <td class="pd-5 semi-bold" valign="top">Mandays Subcont</td>
                <td class="pd-5" width="370" valign="top">
                    <input type="text" name="mandays_subcont" id="" class="form-control form-control-sm text-right mandays_subcont" value="<?= $list_spk_penawaran->mandays_subcont ?>" readonly>
                </td>
            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top">Nilai Internal</td>
                <td class="pd-5" width="370" valign="top">
                    <input type="text" name="nilai_internal" id="" class="form-control form-control-sm auto_num text-right nilai_internal" value="<?= $list_spk_penawaran->nilai_internal ?>" readonly>
                </td>
                <td class="pd-5 semi-bold" valign="top">Mandays Internal</td>
                <td class="pd-5" width="370" valign="top">
                    <input type="text" name="mandays_internal" id="" class="form-control form-control-sm text-right" value="<?= $list_spk_penawaran->mandays_internal ?>" readonly>
                </td>
            </tr>
            <tr>
                <td class="pd-5 semi-bold" valign="top">Mandays Rate</td>
                <td class="pd-5" width="370" valign="top">
                    <input type="text" name="mandays_rate" id="" class="form-control form-control-sm auto_num text-right mandays_rate" value="<?= $list_spk_penawaran->mandays_rate ?>" readonly>
                </td>
                <td colspan="3"></td>
            </tr>
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
                    <td>Pemberi Informasi</td>
                    <td>
                        <input type="text" name="nama_pemberi_informasi_komisi" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->nama_pemberi_informasi_komisi ?>" readonly>
                    </td>
                    <td>
                        <input type="text" name="persentase_pemberi_informasi_komisi" id="" class="form-control form-control-sm text-right" value="<?= $list_spk_penawaran->persentase_pemberi_informasi_komisi ?>" readonly>
                    </td>
                    <td>
                        <input type="text" name="nominal_pemberi_informasi_komisi" id="" class="form-control form-control-sm text-right" value="<?= $list_spk_penawaran->nominal_pemberi_informasi_komisi ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td>Sales</td>
                    <td>
                        <input type="text" name="nama_sales_komisi" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->nama_sales_komisi ?>" readonly>
                    </td>
                    <td>
                        <input type="text" name="persentase_sales_komisi" id="" class="form-control form-control-sm text-right" value="<?= $list_spk_penawaran->persentase_sales_komisi ?>" readonly>
                    </td>
                    <td>
                        <input type="text" name="nominal_sales_komisi" id="" class="form-control form-control-sm text-right" value="<?= $list_spk_penawaran->nominal_sales_komisi ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td>Others</td>
                    <td>
                        <input type="text" name="nama_others_komisi" id="" class="form-control form-control-sm" value="<?= $list_spk_penawaran->nama_others_komisi ?>" readonly>
                    </td>
                    <td>
                        <input type="text" name="persentase_others_komisi" id="" class="form-control form-control-sm text-right" value="<?= $list_spk_penawaran->persentase_others_komisi ?>" readonly>
                    </td>
                    <td>
                        <input type="text" name="nominal_others_komisi" id="" class="form-control form-control-sm text-right" value="<?= $list_spk_penawaran->nominal_others_komisi ?>" readonly>
                    </td>
                </tr>
            </tbody>
            <tfoot>
                <tr>
                    <th></th>
                    <th></th>
                    <th class="text-right ttl_persen_komisi"><?= $ttl_persen_komisi ?></th>
                    <th class="text-right ttl_nilai_komisi"><?= number_format($ttl_nominal_komisi, 2) ?></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<div class="box">
    <div class="box-header">
        <table style="width: 100%;" border="0">
            <tr>
                <th valign="top">
                    <h4 style="font-weight: 600;">Term of Payment</h4> <br>
                    <table style="width: 100%" border="0">
                        <tr>
                            <td width="100">Nilai Project</td>
                            <td>
                                <input type="text" name="" id="" class="form-control form-control-sm text-right auto_num nilai_project" value="<?= $list_penawaran->grand_total ?>" style="max-width: 200px;" readonly>
                            </td>
                        </tr>
                    </table>
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

                    echo '<td class>' . $item->term_payment . '</td>';
                    echo '<td class="text-right">' . number_format($item->persen_payment, 2) . '</td>';
                    echo '<td class="text-right">' . number_format($item->nominal_payment, 2) . '</td>';
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
                    <th class="text-right ttl_persentase_payment"><?= number_format($ttl_persen_payment, 2) ?></th>
                    <th class="text-right ttl_nominal_payment"><?= number_format($ttl_nominal_payment, 2) ?></th>
                    <th class="text-right"></th>
                    <th class="text-right"></th>
                </tr>
            </tfoot>
        </table>

        <br><br>

        <div class="col-md-6">
            <div class="form-group">
                <label for="">Reject Reason</label>
                <textarea name="" id="" class="form-control form-control-sm reject_reason"><?= $list_spk_penawaran->reject_reason ?></textarea>
            </div>
        </div>
    </div>
</div>

<a href="<?= base_url('approval_spk_penawaran'); ?>" class="btn btn-sm btn-danger"><i class="fa fa-arrow-left"></i> Back</a>
<button type="button" class="btn btn-sm btn-danger reject_spk"><i class="fa fa-close"></i> Reject</button>
<button type="button" class="btn btn-sm btn-success approve_spk"><i class="fa fa-check"></i> Approve</button>



<input type="hidden" name="no_payment" value="1">
<script src="<?= base_url('assets/js/autoNumeric.js'); ?>"></script>
<script>
    var no_payment = parseFloat($('input[name="no_payment"]').val());
    $(document).ready(function() {
        $('.chosen_select').chosen({
            width: "400px"
        });

        $('.select_divisi').chosen();
        $('.select_project_leader').chosen();
        $('.select_konsultan_1').chosen();
        $('.select_konsultan_2').chosen();

        $('.auto_num').autoNumeric();
    });

    $(document).on('click', '.reject_spk', function() {
        var id_spk_penawaran = $('.id_spk_penawaran').val();
        var reject_reason = $('.reject_reason').val();

        if (reject_reason == '') {
            swal({
                type: 'warning',
                title: 'Warning !',
                text: 'Please input the reject reason first !'
            });
        } else {
            swal({
                type: 'warning',
                title: 'Are you sure ?',
                text: 'You will reject this SPK !',
                showCancelButton: true
            }, function(next) {
                if (next) {
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
                                swal({
                                    type: 'success',
                                    title: 'Success !',
                                    text: result.pesan
                                }, function(aftter) {
                                    window.location.href = siteurl + active_controller;
                                });
                            } else {
                                swal({
                                    type: 'warning',
                                    title: 'Failed !',
                                    text: result.pesan
                                });
                            }
                        },
                        error: function(result) {
                            swal({
                                type: 'error',
                                title: 'Error !',
                                text: 'Please try again later !'
                            });
                        }
                    });
                }
            });
        }
    });

    $(document).on('click', '.approve_spk', function() {
        var id_spk_penawaran = $('.id_spk_penawaran').val();

        swal({
            type: 'warning',
            title: 'Are you sure ?',
            text: 'You will Approve this SPK !',
            showCancelButton: true
        }, function(next) {
            if (next) {
                $.ajax({
                    type: "POST",
                    url: siteurl + active_controller + 'approve_spk',
                    data: {
                        'id_spk_penawaran': id_spk_penawaran
                    },
                    cache: false,
                    dataType: "JSON",
                    success: function(result) {
                        if (result.status == 1) {
                            swal({
                                type: 'success',
                                title: 'Success !',
                                text: result.pesan
                            }, function(aftter) {
                                window.location.href = siteurl + active_controller;
                            });
                        } else {
                            swal({
                                type: 'warning',
                                title: 'Failed !',
                                text: result.pesan
                            });
                        }
                    },
                    error: function(result) {
                        swal({
                            type: 'error',
                            title: 'Error !',
                            text: 'Please try again later !'
                        });
                    }
                });
            }
        });
    });
</script>