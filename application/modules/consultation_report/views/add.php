<?php
$ENABLE_ADD     = has_permission('SPK.Add');
$ENABLE_MANAGE  = has_permission('SPK.Manage');
$ENABLE_VIEW    = has_permission('SPK.View');
$ENABLE_DELETE  = has_permission('SPK.Delete');
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
        </div>

        <div class="box-body">

            <table border="0" style="width: 100%;">
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="110">No. SPK</td>
                    <td class="pd-5" width="400" valign="top">
                        <?= $header->id_spk_penawaran ?>
                    </td>
                    <!-- <td width="100"></td> -->
                    <td class="pd-5 semi-bold" valign="top" width="110">Project Leader</td>
                    <td class="pd-5" width="500" valign="top">
                        <?= ucfirst($header->nm_project_leader) ?>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="110">Customer</td>
                    <td class="pd-5" width="400" valign="top">
                        <?= $header->nm_customer ?>
                    </td>
                    <td class="pd-5 semi-bold" valign="top" width="110">Sales</td>
                    <td class="pd-5" width="400" valign="top">
                        <?= ucfirst($header->nm_sales) ?>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="110">Address</td>
                    <td class="pd-5" width="400" valign="top">
                        <?= $header->address ?>
                    </td>
                    <td class="pd-5 semi-bold" valign="top" width="110">Waktu</td>
                    <td class="pd-5" width="400" valign="top">
                        <?= date('d F Y', strtotime($header->waktu_from)) . ' - ' . date('d F Y', strtotime($header->waktu_to)) ?>
                    </td>
                </tr>
                <tr>
                    <td class="pd-5 semi-bold" valign="top" width="110">Project</td>
                    <td class="pd-5" width="400" valign="top">
                        <?= $header->nm_project ?>
                    </td>
                    <td class="pd-5 semi-bold" valign="top" width="110"></td>
                    <td class="pd-5" width="400" valign="top">

                    </td>
                </tr>
            </table>
        </div>
    </div>

    <div class="box">
        <div class="box-header">
            <h4 style="font-weight: bold;">Activity List</h4>
        </div>

        <div class="box-body">
            <table class="table custom-table">
                <thead>
                    <tr>
                        <th class="text-center">No.</th>
                        <th class="text-center">Activity</th>
                        <th class="text-center">Mandays</th>
                        <th class="text-center">Actual</th>
                        <th class="text-center">Date</th>
                        <th class="text-center">Start</th>
                        <th class="text-center">End</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $no = 0;
                    foreach ($detail as $item) {
                        $no++;

                        $ttl_mandays = ($item->mandays + $item->mandays_tandem + $item->mandays_subcont);

                        $sts = '<div class="badge bg-green">Open</div>';

                        $edit_btn = '<a href="' . base_url('consultation_report/add_detail/' . $item->id) . '" class="btn btn-sm btn-warning"><i class="fa fa-pencil"></i></a>';
                        $view_btn = '<a href="javascript:void(0);" class="btn btn-sm btn-info"><i class="fa fa-eye"></i></a>';
                        $action = $view_btn . ' ' . $edit_btn;

                        echo '<tr>';
                        echo '<td class="text-center">' . $no . '</td>';
                        echo '<td class="text-left" width="450">' . $item->nm_aktifitas . '</td>';
                        echo '<td class="text-center">' . number_format($ttl_mandays) . '</td>';
                        echo '<td class="text-center">' . number_format(0) . '</td>';
                        echo '<td class="text-center"></td>';
                        echo '<td class="text-center"></td>';
                        echo '<td class="text-center"></td>';
                        echo '<td class="text-center">';
                        echo $sts;
                        echo '</td>';
                        echo '<td class="text-center">' . $action . '</td>';
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</form>

<input type="hidden" name="no_payment" value="1">
<script src="<?= base_url('assets/js/autoNumeric.js'); ?>"></script>
<script>
    var no_payment = parseFloat($('input[name="no_payment"]').val());
    $(document).ready(function() {
        $('.chosen_select').chosen({
            width: "250px"
        });

        $('.select_divisi').chosen({
            width: '100%'
        });
        $('.select_project_leader').chosen({
            width: '100%'
        });
        $('.select_konsultan_1').chosen({
            width: '100%'
        });
        $('.select_konsultan_2').chosen({
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

    function hitung_mandays_subcont() {
        var no = "<?= $no ?>";

        var mandays = 0;
        for (i = 1; i <= no; i++) {
            var mandayss = get_num($('input[name="dt[' + i + '][mandays]"]').val());

            mandays += mandayss;
        }

        $('input[name="total_mandays"]').val(mandays);
        $('.ttl_mandays').html(mandays);

        var total_mandays = get_num($('input[name="total_mandays"]').val());

        var ttl_mandays_subcont = 0;
        for (i = 1; i <= no; i++) {
            var mandays_subcont = get_num($('input[name="dt[' + i + '][mandays_subcont]"]').val());

            ttl_mandays_subcont += mandays_subcont;
        }

        $('.ttl_mandays_subcont').html(ttl_mandays_subcont);
        $('input[name="mandays_subcont"]').val(ttl_mandays_subcont);

        var mandays_internal = parseFloat(total_mandays - ttl_mandays_subcont);

        $('.total_mandays_internal').val(mandays_internal);
    }

    function hitung_total_subcont() {
        var no = "<?= $no ?>";

        var nilai_kontrak = get_num($('input[name="nilai_kontrak"]').val());
        var biaya_akomodasi = get_num($('input[name="biaya_akomodasi"]').val());
        var biaya_others = get_num($('input[name="biaya_others"]').val());
        var biaya_tandem = get_num($('input[name="biaya_tandem"]').val());
        var total_mandays = "<?= $total_mandays ?>";

        var ttl_subcont = 0;
        for (i = 1; i <= no; i++) {
            var total_subcont = get_num($('input[name="dt[' + i + '][total_subcont]"]').val());

            ttl_subcont += parseFloat(total_subcont);
        }

        $('.biaya_subcont').val(number_format(ttl_subcont, 2));
        $('.ttl_total_subcont').html(number_format(ttl_subcont, 2));

        $('input[name="nilai_kontrak_bersih"]').val(number_format((nilai_kontrak - biaya_akomodasi - biaya_others - ttl_subcont), 2));

        var mandays_rate = parseFloat((nilai_kontrak - biaya_akomodasi - biaya_others - ttl_subcont - biaya_tandem) / total_mandays);

        $('.total_mandays_rate').val(number_format(mandays_rate, 2));
    }

    function persen_komisi(tipe) {
        var persentase = get_num($('input[name="persentase_' + tipe + '_komisi"]').val());
        // if (persentase > 2) {
        //     swal({
        //         type: 'warning',
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

    function hitung_grand_ttl_subcont() {
        var no = '<?= $no ?>';

        var ttl_grand_total = 0;
        for (i = 1; i <= no; i++) {
            var mandays = get_num($('input[name="dt[' + i + '][mandays]"]').val());
            var mandays_rate = get_num($('input[name="dt[' + i + '][mandays_rate]"]').val());
            var mandays_tandem = get_num($('input[name="dt[' + i + '][mandays_tandem]"]').val());
            var mandays_rate_tandem = get_num($('input[name="dt[' + i + '][mandays_rate_tandem]"]').val());
            var mandays_subcont = get_num($('input[name="dt[' + i + '][mandays_subcont]"]').val());
            var mandays_rate_subcont = get_num($('input[name="dt[' + i + '][price_subcont]"]').val());

            var total_internal = (mandays * mandays_rate);
            var total_tandem = (mandays_tandem * mandays_rate_tandem);
            var total_subcont = (mandays_subcont * mandays_rate_subcont);

            var grand_total = (total_internal + total_tandem + total_subcont);

            ttl_grand_total += grand_total;

            $('input[name="dt[' + i + '][grand_total]"]').val(number_format(grand_total, 2));
        }

        $('input[name="nilai_kontrak"]').val(number_format(ttl_grand_total, 2));
        $('.ttl_grand_total').html(number_format(ttl_grand_total, 2));

        var nilai_kontrak = ttl_grand_total;
        var biaya_akomodasi = get_num($('input[name="biaya_akomodasi"]').val());
        var biaya_subcont = get_num($('input[name="biaya_subcont"]').val());
        var biaya_others = get_num($('input[name="biaya_others"]').val());
        var biaya_tandem = get_num($('input[name="biaya_tandem"]').val());

        var nilai_kontrak_bersih = (nilai_kontrak - biaya_akomodasi - biaya_subcont - biaya_others - biaya_tandem);

        $('input[name="nilai_kontrak_bersih"]').val(number_format(nilai_kontrak_bersih, 2));
    }

    $(document).on('change', '.edit_mandays_subcont', function() {
        var id = $(this).data('id');
        var mandays_subcont = parseFloat($(this).val());
        var price_subcont = get_num($('.price_subcont_' + id).val());

        var total = parseFloat(mandays_subcont * price_subcont);

        $('.total_subcont_' + id).val(number_format(total, 2));
        hitung_total_subcont();
        hitung_mandays_subcont();
        hitung_grand_ttl_subcont();
    });

    $(document).on('change', '.edit_price_subcont', function() {
        var id = $(this).data('id');
        var price_subcont = get_num($(this).val());
        var mandays_subcont = get_num($('.mandays_subcont_' + id).val());

        var total = parseFloat(mandays_subcont * price_subcont);

        $('.total_subcont_' + id).val(number_format(total, 2));
        hitung_total_subcont();
        hitung_mandays_subcont();
        hitung_grand_ttl_subcont();
    });

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
        //     swal({
        //         type: 'warning',
        //         title: 'Warning !',
        //         text: 'Total Persentase Komisi tidak boleh lebih dari 4% !'
        //     });

        //     return false;
        // } else 
        if (ttl_persentase_payment < 100 || ttl_persentase_payment > 100) {
            swal({
                type: 'warning',
                title: 'Warning !',
                text: 'Persentase payment harus 100% !'
            });

            return false;
        } else if (waktu_from == '' || waktu_to == '') {
            swal({
                type: 'warning',
                title: 'Warning !',
                text: 'Pastikan Kolom waktu sudah terisi !'
            });

            return false;
        } else if (project_leader == '') {
            swal({
                type: 'warning',
                title: 'Warning !',
                text: 'Project leader wajib diisi !'
            });

            return false;
        } else {
            swal({
                type: 'warning',
                title: 'Are you sure ?',
                text: 'This data will be saved !',
                showCancelButton: true
            }, function(next) {
                if (next) {
                    var formData = $('#frm-data').serialize();

                    $.ajax({
                        type: "POST",
                        url: siteurl + active_controller + 'save_spk_penawaran',
                        data: formData,
                        cache: false,
                        dataType: "JSON",
                        success: function(result) {
                            if (result.status == 1) {
                                swal({
                                    type: 'success',
                                    title: 'Success !',
                                    text: result.msg
                                }, function(after) {
                                    window.location.href = siteurl + active_controller;
                                });
                            } else {
                                swal({
                                    type: 'warning',
                                    title: 'Failed !',
                                    text: result.msg
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
                    })
                }
            });
        }
    });

    $(document).on('click', '.del_subcont', function() {
        var no = $(this).data('no');

        $('.subcont_' + no).remove();

        hitung_total_subcont();
        hitung_mandays_subcont();
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