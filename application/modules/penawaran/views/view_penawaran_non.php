<?php
$ENABLE_ADD     = has_permission('Penawaran.Add');
$ENABLE_MANAGE  = has_permission('Penawaran.Manage');
$ENABLE_VIEW    = has_permission('Penawaran.View');
$ENABLE_DELETE  = has_permission('Penawaran.Delete');

$id_penawaran = (!empty($data_penawaran->id_penawaran)) ? $data_penawaran->id_penawaran : '';
$id_divisi = (!empty($data_penawaran->id_divisi)) ? $data_penawaran->id_divisi : '';
$id_company = (!empty($data_penawaran->id_company)) ? $data_penawaran->id_company : '';
$id_customer = (!empty($data_penawaran->id_customer)) ? $data_penawaran->id_customer : '';
$pic = (!empty($data_penawaran->pic)) ? $data_penawaran->pic : '';
$tgl_quotation = (!empty($data_penawaran->tgl_quotation)) ? $data_penawaran->tgl_quotation : '';
$tipe_informasi_awal = (!empty($data_penawaran->tipe_informasi_awal)) ? $data_penawaran->tipe_informasi_awal : '';
$detail_informasi_awal = (!empty($data_penawaran->detail_informasi_awal)) ? $data_penawaran->detail_informasi_awal : '';
$pic_penawaran = (!empty($data_penawaran->pic_penawaran)) ? $data_penawaran->pic_penawaran : '';
$nm_pic_penawaran = (!empty($data_penawaran->nm_pic_penawaran)) ? $data_penawaran->nm_pic_penawaran : '';
$address = (!empty($data_penawaran->address)) ? $data_penawaran->address : '';
$keterangan_penawaran = (!empty($data_penawaran->keterangan_penawaran)) ? $data_penawaran->keterangan_penawaran : '';
$biaya_kirim = $data_penawaran->biaya_kirim ?? 0;

$subtotal = (!empty($data_penawaran->subtotal)) ? $data_penawaran->subtotal : '';
$ppn = (!empty($data_penawaran->ppn)) ? $data_penawaran->ppn : '';
$grand_total = (!empty($data_penawaran->grand_total)) ? $data_penawaran->grand_total : '';

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


    .select2-container .select2-drop {
        z-index: 9999;
        /* Ensure the dropdown itself has a high z-index */
    }

    .select2-container-active {
        position: absolute;
    }

    .btn {
        font-weight: bold;
    }

    */
</style>
<div id="alert_edit" class="alert alert-success alert-dismissable" style="padding: 15px; display: none;"></div>

<form action="" id="frm-data" method="post" class="form-data" enctype="multipart/form-data">
    <div class="box">

        <!-- /.box-header -->
        <div class="box-body">
            <div class="row">
                <?php
                if ($data_penawaran->sts_quot == '2') :
                ?>
                    <div class="col-md-12">
                        <div class="alert alert-danger">
                            <h4>Reject Reason !</h4>
                            <?= $data_penawaran->reject_reason ?>
                        </div>
                    </div>
                <?php
                endif;
                ?>
                <div class="col-md-2">
                    <b>Number</b>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <input type="text" class="form-control form-control-sm" value="<?= $id_penawaran ?>" readonly>
                    </div>
                </div>
                <div class="col-md-2">
                    <b> Divisi <span class="text-danger">*</span></b>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <select class="form-control form-control-sm select_divisi" name="divisi" disabled>
                            <!-- <option value="">- Select Divisi -</option> -->
                            <?php
                            if (isset($list_divisi)) {
                                foreach ($list_divisi as $item_divisi) :
                                    if ($item_divisi->id_divisi == $id_divisi) {
                                        echo '<option value="' . $item_divisi->id_divisi . '">' . $item_divisi->nm_divisi . ' - ' . $item_divisi->nm_company . '</option>';
                                    }
                                endforeach;
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="row">
                <div class="col-md-2">
                    <b>Customer <span class="text-danger">*</span></b>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <select class="form-control form-control-sm select_customer get_detail_customer" name="customer" disabled>
                            <!-- <option value="">- Select Customer -</option> -->
                            <?php
                            if (isset($list_customer)) {
                                foreach ($list_customer as $item_customer) {
                                    if ($item_customer->id_customer == $id_customer) {
                                        echo '<option value="' . $item_customer->id_customer . '">' . $item_customer->nm_customer . '</option>';
                                    }
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <b>Company <span class="text-danger">*</span></b>
                </div>
                <div class="col-md-4">
                    <select class="form-control form-control-sm select2" name="company" disabled>
                        <!-- <option value="">- Select Company -</option> -->
                        <?php
                        foreach ($list_company as $item_company) {
                            if ($item_company->id_company == $id_company) {
                                echo '<option value="' . $item_company->id_company . '">' . $item_company->nm_company . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-2">
                    <b>PIC</b>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control form-control-sm pic" name="pic" value="<?= $pic ?>" disabled>
                </div>
                <div class="col-md-2">
                    <b>Date <span class="text-danger">*</span></b>
                </div>
                <div class="col-md-4">
                    <input type="date" class="form-control form-control-sm" name="tgl_penawaran" value="<?= date('Y-m-d', strtotime($tgl_quotation)) ?>" disabled>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <b>Informasi Awal</b>
                    </div>
                </div>
                <div class="col-md-1">
                    <div class="form-group">
                        <input type="checkbox" name="informasi_awal_sales" value="1" disabled <?= ($tipe_informasi_awal == 'Sales') ? 'checked' : '' ?>> Sales
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <select class="form-control form-control-sm select2" name="sales_informasi_awal" disabled>

                            <?php
                            if ($tipe_informasi_awal !== 'Sales') {
                                echo '<option value="">- Select Sales -</option>';
                            }
                            if (isset($list_sales)) {
                                foreach ($list_sales as $item_sales) {
                                    if ($tipe_informasi_awal == 'Sales' && $item_sales->nm_karyawan == $detail_informasi_awal) {
                                        echo '<option value="' . $item_sales->id . '" checked>' . $item_sales->nm_karyawan . '</option>';
                                    }
                                }
                            }
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <b>Address</b>
                </div>
                <div class="col-md-4">
                    <textarea class="form-control form-control-sm address" rows="2" name="address" disabled><?= $address ?></textarea>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-2">

                </div>
                <div class="col-md-1">
                    <input type="checkbox" name="informasi_awal_medsos" value="2" disabled <?= ($tipe_informasi_awal == 'Medsos') ? 'checked' : '' ?>> Medsos
                </div>
                <div class="col-md-3">
                    <select class="form-control form-control-sm select2" name="medsos_informasi_awal" disabled>
                        <option value="">- Select Medsos -</option>
                        <option value="Youtube" <?= ($tipe_informasi_awal == 'Medsos' && $detail_informasi_awal == 'Youtube') ? 'selected' : '' ?>>Youtube</option>
                        <option value="Instagram" <?= ($tipe_informasi_awal == 'Medsos' && $detail_informasi_awal == 'Instagram') ? 'selected' : '' ?>>Instagram</option>
                        <option value="Linkedin" <?= ($tipe_informasi_awal == 'Medsos' && $detail_informasi_awal == 'Linkedin') ? 'selected' : '' ?>>Linkedin</option>
                        <option value="Website" <?= ($tipe_informasi_awal == 'Medsos' && $detail_informasi_awal == 'Website') ? 'selected' : '' ?>>Website</option>
                        <option value="Facebook" <?= ($tipe_informasi_awal == 'Medsos' && $detail_informasi_awal == 'Facebook') ? 'selected' : '' ?>>Facebook</option>
                        <option value="Incoming Call" <?= ($tipe_informasi_awal == 'Medsos' && $detail_informasi_awal == 'Incoming Call') ? 'selected' : '' ?>>Incoming Call</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <b>Admin Sales <span class="text-danger">*</span></b>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <input type="text" class="form-control form-control-sm" name="pic_penawaran" value="<?= $nm_pic_penawaran ?>" disabled>
                    </div>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-2"></div>
                <div class="col-md-1">
                    <input type="checkbox" name="informasi_awal_others" value="3" disabled <?= ($tipe_informasi_awal == 'Others') ? 'checked' : '' ?>> Others
                </div>
                <div class="col-md-3">
                    <select class="form-control form-control-sm select2" name="others_informasi_awal" disabled>

                        <?php
                        if ($tipe_informasi_awal !== 'Others') {
                            echo '<option value="">- Select Employee -</option>';
                        }
                        foreach ($list_employee as $item_employee) {
                            if ($tipe_informasi_awal == 'Others' && $item_employee->nm_karyawan == $detail_informasi_awal) {
                                echo '<option value="' . $item_employee->id . '" selected>' . $item_employee->nm_karyawan . '</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-2">
                    <div class="form-group">
                        <b>Penawaran <span class="text-danger">*</span></b>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <input type="text" name="keterangan_penawaran" id="" class="form-control form-control-sm" value="<?= $keterangan_penawaran ?>" disabled>
                    </div>
                </div>
            </div>
        </div>
        <!-- /.box-body -->
    </div>

    <div class="box">
        <div class="box-header">
            <h4 class="semi-bold">Detail Penawaran</h4>
        </div>
        <div class="box-body">
            <table class="table custom-table-no">
                <thead>
                    <tr>
                        <th class="text-center">No.</th>
                        <th class="text-center">Item</th>
                        <th class="text-center">Qty</th>
                        <th class="text-center">Harga</th>
                        <th class="text-center">Total</th>
                        <!-- <th class="text-center">Action</th> -->
                    </tr>
                </thead>
                <tbody class="list_detail_penawaran">
                    <?php
                    $no_detail = 0;

                    $grand_total_detail = 0;
                    if (isset($data_detail_penawaran)) {
                        foreach ($data_detail_penawaran as $item) {
                            $no_detail++;

                            echo '<tr>
                                <td class="text-center">' . $no_detail . '</td>
                                <td class="text-left">' . $item->nm_item . '</td>
                                <td class="text-center">' . $item->qty . '</td>
                                <td class="text-right">' . number_format($item->harga, 2) . '</td>
                                <td class="text-right">' . number_format($item->total, 2) . '</td>
                            </tr>';

                            $grand_total_detail += $item->total;
                        }
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-right">Biaya Kirim</th>
                        <th class="text-right"><?= number_format($data_penawaran->biaya_kirim, 2) ?></th>
                    </tr>
                    <tr>
                        <th colspan="4" class="text-right">Grand Total</th>
                        <th class="text-right"><?= number_format($grand_total_detail, 2) ?></th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="box">
        <div class="box-header">
            <h4 class="semi-bold">Summary</h4>
        </div>
        <div class="box-body">
            <table class="table custom-table-no">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th class="text-right">Amount (Rp.)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>Subtotal</td>
                        <td class="text-right td_subtotal"><?= number_format($subtotal, 2) ?></td>
                    </tr>
                    <tr>
                        <td>Discount</td>
                        <td class="text-right">
                            <div class="form-inline">
                                <span>(%)</span>
                                <input type="number" name="persen_disc" id="" class="form-control form-control-sm text-right" value="<?= !empty($data_penawaran->persen_disc) ? $data_penawaran->persen_disc : '0' ?>" step="0.01" disabled>
                                <span>(Rp.)</span>
                                <input type="text" name="nominal_disc" id="" class="form-control form-control-sm auto_num text-right" value="<?= number_format($data_penawaran->nominal_disc, 2) ?>" readonly>
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>PPn</td>
                        <td class="text-right">
                            <div class="form-inline">
                                <span>(%)</span>
                                <input type="number" name="persen_ppn" id="" class="form-control form-control-sm text-right" value="<?= !empty($data_penawaran->persen_ppn) ? $data_penawaran->persen_ppn : '0' ?>" min="0" disabled>
                                <span>(Rp.)</span>
                                <input type="text" name="nominal_ppn" id="" class="form-control form-control-sm auto_num text-right" value="<?= number_format($ppn, 2) ?>" readonly>
                            </div>
                        </td>
                    </tr>
                </tbody>
                <tfoot>
                    <tr>
                        <th>Grand Total</th>
                        <th class="text-right td_grand_total"><?= number_format($grand_total, 2) ?></th>
                    </tr>
                </tfoot>
            </table>

            <br><br>
            <a href="<?= base_url('penawaran/') ?>" class="btn btn-sm btn-danger"><i class="fa fa-arrow-left"></i> Back</a>
        </div>
    </div>

</form>

<input type="hidden" class="no" value="1">
<input type="hidden" class="no_akomodasi" value="1">
<input type="hidden" class="no_others" value="1">
<input type="hidden" class="no_lab" value="1">
<input type="hidden" class="no_subcont_tenaga_ahli" value="1">
<input type="hidden" class="no_subcont_perusahaan" value="1">

<div id="form-data"></div>
<!-- DataTables -->
<!-- <script src="<?= base_url('assets/plugins/datatables/jquery.dataTables.min.js') ?>"></script>
<script src="<?= base_url('assets/plugins/datatables/dataTables.bootstrap.min.js') ?>"></script> -->

<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script src="https://cdn.datatables.net/2.1.7/js/dataTables.min.js"></script>
<script src="<?= base_url('assets/js/autoNumeric.js') ?>"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- page script -->
<script type="text/javascript">
    var no_detail = 1;

    $('.select2').select2({
        width: '100%'
    })
    $('.company').select2({
        width: '100%'
    });
    $('.select_customer').select2({
        width: '100%'
    });
    $('.select_marketing').select2({
        width: '100%'
    });
    $('.select_package').select2({
        width: '100%'
    });
    $('.select_divisi').select2({
        width: '100%'
    });
    $('.informasi_awal_sales').select2({
        width: "100%"
    });
    $('.informasi_awal_medsos').select2({
        width: "100%"
    });
    $('.informasi_awal_others').select2({
        width: "100%"
    });

    // initialize with defaults
    $("#input-id").checkboxX({
        threeState: false,
        size: 'sm'
    });

    function auto_num() {
        $('.auto_num').autoNumeric('init');
    }

    function add_detail_penawaran_non_konsultasi() {
        $.ajax({
            type: 'post',
            url: siteurl + active_controller + 'add_detail_penawaran_non_konsultasi',
            data: {
                no_detail
            },
            cache: false,
            dataType: 'json',
            success: function(result) {
                $('.list_detail_penawaran').append(result.item);
                auto_num();
                no_detail++;
            },
            error: function(xhr, status, error) {
                Swal.fire({
                    icon: 'error',
                    title: 'Error !',
                    text: "There's an error occured, please try again later !",
                    showConfirmButton: false,
                    showCancelButton: false,
                    timer: 3000
                });
            }
        });
    }

    function del_item(no) {
        $('.item_detail_' + no).remove();

        hitung_grand_total();
    }

    function getNum(nilai) {
        if (nilai !== '') {
            nilai = nilai.split(',').join('');
            nilai = parseFloat(nilai);

            return nilai;
        } else {
            return 0;
        }
    }

    function hitung_total_detail(no) {
        var qty = $('.qty_' + no).val();
        if (qty !== '') {
            qty = qty.split(',').join('');
            qty = parseFloat(qty);
        } else {
            qty = 0
        }

        var harga = $('.harga_' + no).val();
        if (harga !== '') {
            harga = harga.split(',').join('');
            harga = parseFloat(harga);
        } else {
            harga = 0
        }

        var total = (qty * harga);

        $('.total_' + no).autoNumeric('set', total);

        hitung_grand_total();
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

    function hitung_grand_total() {
        var total = 0;
        for (i = 1; i <= no_detail; i++) {
            if ($('.total_' + i).length > 0) {
                var nilai_total = $('.total_' + i).val();
                if (nilai_total !== '') {
                    nilai_total = nilai_total.split(',').join('');
                    nilai_total = parseFloat(nilai_total);
                } else {
                    nilai_total = 0;
                }

                total += nilai_total;
            }
        }

        var ppn = (total * 11 / 100);

        $('.td_subtotal').html(number_format(total, 2));
        $('.td_ppn').html(number_format(ppn, 2));
        $('.td_grand_total').html(number_format(total + ppn, 2));

        $('input[name="subtotal"]').val(total);
        $('input[name="ppn"]').val(ppn);
        $('input[name="grand_total"]').val((total + ppn));
    }

    $(document).ready(function() {
        auto_num();
    });

    $(document).on('click', 'input[name="informasi_awal_sales"]', function() {
        if ($(this).is(':checked')) {
            $('select[name="sales_informasi_awal"]').attr('disabled', false);
        } else {
            $('select[name="sales_informasi_awal"]').attr('disabled', true);
        }
    })

    $(document).on('click', 'input[name="informasi_awal_medsos"]', function() {
        if ($(this).is(':checked')) {
            $('select[name="medsos_informasi_awal"]').attr('disabled', false);
        } else {
            $('select[name="medsos_informasi_awal"]').attr('disabled', true);
        }
    })

    $(document).on('click', 'input[name="informasi_awal_others"]', function() {
        if ($(this).is(':checked')) {
            $('select[name="others_informasi_awal"]').attr('disabled', false);
        } else {
            $('select[name="others_informasi_awal"]').attr('disabled', true);
        }
    })

    $(document).on('change', '.get_detail_customer', function() {
        var customer = $(this).val();

        if (customer.length > 0) {
            $.ajax({
                type: 'get',
                url: siteurl + active_controller + 'get_detail_customer',
                data: {
                    'customer': customer
                },
                cache: false,
                dataType: 'json',
                success: function(result) {
                    $('.address').val(result.address);
                    $('.pic').val(result.pic);
                },
                error: function(xhr, status, error) {
                    // 1. Ambil response text dan parse ke JSON
                    let response = {};
                    try {
                        response = JSON.parse(xhr.responseText);
                    } catch (e) {
                        response = {
                            msg: 'Terjadi kesalahan sistem yang tidak terduga.'
                        };
                    }

                    // 2. Tampilkan pesan 'msg' dari JSON
                    Swal.fire({
                        icon: 'error',
                        title: 'Error !',
                        text: response.msg, // <--- Ini yang bakal nampilin isi pesan lu
                        showConfirmButton: false,
                        timer: 3000
                    });
                }
            });
        } else {
            $('.address').val('');
        }
    })

    $(document).on('submit', '#frm-data', function(e) {
        e.preventDefault();

        Swal.fire({
            icon: 'warning',
            title: 'Are you sure ?',
            text: 'This data will be deleted !',
            showConfirmButton: true,
            showCancelButton: true
        }).then((next) => {
            if (next.isConfirmed) {
                var formdata = $('#frm-data').serialize();

                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'save_penawaran_non_konsultasi',
                    data: formdata,
                    cache: false,
                    dataType: 'json',
                    success: function(result) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success !',
                            text: 'Data has been saved !',
                            showConfirmButton: false,
                            showCancelButton: false,
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            timer: 3000
                        }).then(() => {
                            window.location.href = siteurl + active_controller + '/penawaran';
                        });
                    },
                    error: function(xhr, status, error) {
                        // 1. Ambil response text dan parse ke JSON
                        let response = {};
                        try {
                            response = JSON.parse(xhr.responseText);
                        } catch (e) {
                            response = {
                                msg: 'Terjadi kesalahan sistem yang tidak terduga.'
                            };
                        }

                        // 2. Tampilkan pesan 'msg' dari JSON
                        Swal.fire({
                            icon: 'error',
                            title: 'Error !',
                            text: response.msg, // <--- Ini yang bakal nampilin isi pesan lu
                            showConfirmButton: false,
                            timer: 3000
                        });
                    }
                });
            }
        });
    });
</script>
<script src="<?= base_url('assets/js/basic.js') ?>"></script>