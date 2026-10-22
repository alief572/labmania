<?php
$ENABLE_ADD     = has_permission('SPK.Add');
$ENABLE_MANAGE  = has_permission('SPK.Manage');
$ENABLE_VIEW    = has_permission('SPK.View');
$ENABLE_DELETE  = has_permission('SPK.Delete');

$id_spk_penawaran = $data_spk_non_kons->id_spk_penawaran ?? '';
$id_penawaran = $data_spk_non_kons->id_penawaran ?? '';
$nm_customer = $data_spk_non_kons->nm_customer ?? '';
$address = $data_spk_non_kons->address ?? '';
$pic = $data_spk_non_kons->nm_pic ?? '';
$npwp_cust = $data_spk_non_kons->npwp_cust ?? '';
$nm_sales = $data_spk_non_kons->nm_sales ?? '';
$tipe_informasi_awal = $data_spk_non_kons->tipe_informasi_awal ?? '';
$detail_informasi_awal = $data_spk_non_kons->detail_informasi_awal ?? '';
$nm_project = $data_spk_non_kons->nm_project ?? '';
$nm_project_leader = $data_spk_non_kons->nm_project_leader ?? '';
$nm_konsultan_1 = $data_spk_non_kons->nm_konsultan_1 ?? '';
$nm_konsultan_2 = $data_spk_non_kons->nm_konsultan_2 ?? '';
$waktu_from = $data_spk_non_kons->waktu_from ?? '';
$waktu_to = $data_spk_non_kons->waktu_to ?? '';


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
                        <input type="text" name="customer" id="" class="form-control form-control-sm text-center" value="<?= $nm_customer ?>" readonly>
                    </td>
                    <!-- <td width="100"></td> -->
                    <td class="pd-5 semi-bold" valign="top" width="110">No. SPK</td>
                    <td class="pd-5" width="500" valign="top">
                        <input type="text" name="id_spk_penawaran" id="" class="form-control form-control-sm text-center" value="<?= $id_spk_penawaran ?>" readonly>
                        <input type="hidden" name="id_quotation" id="" value="<?= $id_penawaran ?>" readonly>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" rowspan="2">Alamat</td>
                    <td class="pd-5" width="400" valign="top" rowspan="2">
                        <textarea name="alamat" id="" class="form-control form-control-sm" rows="4" readonly><?= $address ?></textarea>
                    </td>
                    <!-- <td width="100"></td> -->
                    <td class="pd-5 semi-bold" valign="top">No. NPWP</td>
                    <td class="pd-5" width="500" valign="top">
                        <input type="text" name="no_npwp" id="" class="form-control form-control-sm text-center" value="<?= $npwp_cust ?>" readonly> <br>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top"></td>
                    <td class="pd-5" width="500" valign="top">

                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top">PIC</td>
                    <td class="pd-5" valign="top">
                        <input type="text" name="pic" id="" class="form-control form-control-sm" value="<?= $pic ?>" readonly>
                    </td>

                    <td class="pd-5" valign="top">

                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="box">
        <div class="box-body">
            <table border="0" style="width: 100%;">
                <tr>
                    <td colspan="4">
                        <h4>Marketing</h4>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="210">Sales</td>
                    <td class="pd-5" width="400" valign="top">
                        <input type="text" class="form-control form-control-sm" value="<?= $nm_sales ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top" style="max-width: 200px;">
                       
                    </td>
                    <td class="pd-5" width="500" valign="top">
                        
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="210">Informasi Awal</td>
                    <td class="pd-5" valign="top" style="max-width: 400px;">
                        <input type="text" name="pic" id="" class="form-control form-control-sm" value="<?= ucfirst($detail_informasi_awal) ?>" readonly>
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
                        <textarea name="nm_paket" id="" class="form-control form-control-sm" readonly><?= $nm_project ?></textarea>
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
                        <input type="text" class="form-control form-control-sm" value="<?= $nm_project_leader ?>" readonly>
                    </td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="135">Konsultan 1</td>
                    <td class="pd-5" width="390" valign="top">
                        <input type="text" class="form-control form-control-sm" value="<?= $nm_konsultan_1 ?>" readonly>
                    </td>
                    <td></td>
                    <td></td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="135">Konsultan 2</td>
                    <td class="pd-5" width="390" valign="top">
                        <input type="text" class="form-control form-control-sm" value="<?= $nm_konsultan_2 ?>" readonly>
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
                                <input type="date" name="waktu_from" id="" class="form-control form-control-sm" value="<?= $waktu_from ?>" readonly>
                            </div>
                            <div class="form-group text-center" style="width: 30px;" valign="middle">
                                -
                            </div>
                            <div class="form-group text-center">
                                <input type="date" name="waktu_to" id="" class="form-control form-control-sm" value="<?= $waktu_to ?>" readonly>
                            </div>
                        </div>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">Subtotal</td>
                    <td class="pd-5" width="400" valign="top">
                        <div class="form-inline">
                            <div class="form-group text-center">
                                <input type="text" name="nilai_kontrak" id="" class="form-control form-control-sm text-right" value="<?= number_format($subtotal, 2) ?>" readonly>
                            </div>
                        </div>
                    </td>
                    <td></td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top">Divisi</td>
                    <td class="pd-5" width="400" valign="top">
                        <input type="text" class="form-control form-control-sm" name="divisi" value="<?= $nm_divisi ?>" readonly>
                    </td>
                    <td class="pd-5 semi-bold" valign="top">PPn</td>
                    <td class="pd-5" width="400" valign="top">
                        <div class="form-inline">
                            <div class="form-group text-center">
                                <input type="text" name="ppn" id="" class="form-control form-control-sm text-right" value="<?= number_format($ppn, 2) ?>" readonly>
                            </div>
                        </div>
                    </td>
                    <td>

                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top"></td>
                    <td class="pd-5" width="400" valign="top">

                    </td>
                    <td class="pd-5 semi-bold" valign="top">Grand Total</td>
                    <td class="pd-5" width="400" valign="top">
                        <div class="form-inline">
                            <div class="form-group text-center">
                                <input type="text" name="grand_total" id="" class="form-control form-control-sm text-right" value="<?= number_format($grand_total, 2) ?>" readonly>
                            </div>
                        </div>
                    </td>
                    <td>

                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="box">
        <div class="box-header">
            <h4>Detail Penawaran</h4>
        </div>

        <div class="box-body">
            <table class="table custom-table">
                <thead>
                    <tr>
                        <th class="text-center">No.</th>
                        <th class="text-center">Item</th>
                        <th class="text-center">Qty</th>
                        <th class="text-center">Harga</th>
                        <th class="text-center">Total</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 0;
                    $ttl_detail_penawaran = 0;
                    if (!empty($data_detail_penawaran)) {
                        foreach ($data_detail_penawaran as $item_detail) {
                            $no++;

                            echo '<tr>';
                            echo '<td class="text-center">' . $no . '</td>';
                            echo '<td class="text-left">' . $item_detail->nm_item . '</td>';
                            echo '<td class="text-center">' . $item_detail->qty . '</td>';
                            echo '<td class="text-right">' . number_format($item_detail->harga) . '</td>';
                            echo '<td class="text-right">' . number_format($item_detail->total) . '</td>';
                            echo '</tr>';

                            $ttl_detail_penawaran += $item_detail->total;
                        }
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-right">Grand Total</th>
                        <th class="text-right"><?= number_format($ttl_detail_penawaran) ?></th>
                    </tr>
                </tfoot>
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
                                &nbsp;&nbsp;&nbsp;<span style="font-weight: bold; font-size: 20px;">Rp. <?= number_format($grand_total, 2) ?></span>
                                <input type="hidden" name="" id="" class="form-control form-control-sm text-right auto_num nilai_project" value="<?= $grand_total ?>">
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
        //         text: 'Total Persentase Komisi tidak boleh lebih dari 4% !'
        //     });

        //     return false;
        // }
        // else if (ttl_nominal_payment != nilai_kontrak_bersih) {
        // swal({
        // type: 'warning',
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
                        url: siteurl + active_controller + 'save_spk_penawaran_non_konsultasi',
                        data: formData,
                        cache: false,
                        dataType: "JSON",
                        success: function(result) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success !',
                                text: result.msg,
                                allowOutsideClick: false,
                                showConfirmButton: false,
                                timer: 3000
                            }).then(() => {
                                window.location.href = siteurl + active_controller;
                            });
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
        var nilai_project = "<?= $grand_total ?>";
        var persen = get_num($(this).val());

        var nilai_payment = parseFloat(nilai_project * persen / 100);

        $('input[name="pt[' + no + '][nominal_payment]"]').val(number_format(nilai_payment, 2));

        hitung_ttl_payment();
    });

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

    $(document).on('change', '.nominal_payment', function() {
        var no = $(this).data('no');
        var nilai_project = "<?= $grand_total ?>";
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