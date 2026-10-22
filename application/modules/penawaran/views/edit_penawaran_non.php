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
$address = (!empty($data_penawaran->address)) ? $data_penawaran->address : '';
$keterangan_penawaran = (!empty($data_penawaran->keterangan_penawaran)) ? $data_penawaran->keterangan_penawaran : '';

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
                <div class="col-md-2">
                    <b>Number</b>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <input type="text" class="form-control form-control-sm" name="id_penawaran" value="<?= $id_penawaran ?>" readonly>
                    </div>
                </div>
                <div class="col-md-2">
                    <b> Divisi <span class="text-danger">*</span></b>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <select class="form-control form-control-sm select_divisi" name="divisi">
                            <!-- <option value="">- Select Divisi -</option> -->
                            <?php
                            if (isset($list_divisi)) :
                                foreach ($list_divisi as $item_divisi) :
                                    $selected = '';
                                    if ($item_divisi->id_divisi == $id_divisi) :
                                        $selected = 'selected';
                                    endif;
                                    echo '<option value="' . $item_divisi->id_divisi . '" ' . $selected . '>' . $item_divisi->nm_divisi . ' - ' . $item_divisi->nm_company . '</option>';
                                endforeach;
                            endif;
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
                        <select class="form-control form-control-sm select_customer get_detail_customer" name="customer">
                            <!-- <option value="">- Select Customer -</option> -->
                            <?php
                            if (isset($list_customer)) :
                                foreach ($list_customer as $item_customer) :
                                    $selected = '';
                                    if ($item_customer->id_customer == $id_customer) :
                                        $selected = 'selected';
                                    endif;
                                    echo '<option value="' . $item_customer->id_customer . '" ' . $selected . '>' . $item_customer->nm_customer . '</option>';
                                endforeach;
                            endif;
                            ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <b>Company <span class="text-danger">*</span></b>
                </div>
                <div class="col-md-4">
                    <select class="form-control form-control-sm select2" name="company">
                        <!-- <option value="">- Select Company -</option> -->
                        <?php
                        foreach ($list_company as $item_company) :
                            $selected = '';
                            if ($item_company->id_company == $id_company) :
                                $selected = 'selected';
                            endif;
                            echo '<option value="' . $item_company->id_company . '" ' . $selected . '>' . $item_company->nm_company . '</option>';
                        endforeach;
                        ?>
                    </select>
                </div>
            </div>
            <div class="row">
                <div class="col-md-2">
                    <b>PIC</b>
                </div>
                <div class="col-md-4">
                    <input type="text" class="form-control form-control-sm pic" name="pic" value="<?= $pic ?>">
                </div>
                <div class="col-md-2">
                    <b>Date <span class="text-danger">*</span></b>
                </div>
                <div class="col-md-4">
                    <input type="date" class="form-control form-control-sm" name="tgl_penawaran" value="<?= date('Y-m-d', strtotime($tgl_quotation)) ?>">
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
                        <input type="checkbox" name="informasi_awal_sales" value="1" <?= ($tipe_informasi_awal == 'Sales') ? 'checked' : '' ?>> Sales
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <select class="form-control form-control-sm select2" name="sales_informasi_awal" <?= ($tipe_informasi_awal !== 'Sales') ? 'disabled' : '' ?>>

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
                    <textarea class="form-control form-control-sm address" rows="2" name="address"><?= $address ?></textarea>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-2">

                </div>
                <div class="col-md-1">
                    <input type="checkbox" name="informasi_awal_medsos" value="2" <?= ($tipe_informasi_awal == 'Medsos') ? 'checked' : '' ?>> Medsos
                </div>
                <div class="col-md-3">
                    <select class="form-control form-control-sm select2" name="medsos_informasi_awal" <?= ($tipe_informasi_awal !== 'Medsos') ? 'disabled' : '' ?>>
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
                        <select class="form-control form-control-sm select2" name="pic_penawaran" required>
                            <option value="">- Select Employee -</option>
                            <?php
                            foreach ($list_employee as $item_employee) {
                                $selected = ($item_employee->id == $pic_penawaran) ? 'selected' : '';
                                echo '<option value="' . $item_employee->id . '" ' . $selected . '>' . $item_employee->nm_karyawan . '</option>';
                            }
                            ?>
                        </select>
                    </div>
                </div>
            </div>
            <br>
            <div class="row">
                <div class="col-md-2"></div>
                <div class="col-md-1">
                    <input type="checkbox" name="informasi_awal_others" value="3" <?= ($tipe_informasi_awal == 'Others') ? 'checked' : '' ?>> Others
                </div>
                <div class="col-md-3">
                    <select class="form-control form-control-sm select2" name="others_informasi_awal" <?= ($tipe_informasi_awal !== 'Others') ? 'disabled' : '' ?>>

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
                        <input type="text" name="keterangan_penawaran" id="" class="form-control form-control-sm" value="<?= $keterangan_penawaran ?>">
                    </div>
                </div>
            </div>
        </div>
        <!-- /.box-body -->
    </div>

    <div class="box">
        <div class="box-header">
            <h4 class="semi-bold">Detail Penawaran <button type="button" class="btn btn-sm btn-success" onclick="add_detail_penawaran_non_konsultasi();"><i class="fa fa-plus"></i> Add Detail</button></h4>
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
                        <th class="text-center">Action</th>
                    </tr>
                </thead>
                <tbody class="list_detail_penawaran">
                    <?php
                    $no_detail = 0;

                    $grand_total_detail = 0;
                    if (isset($data_detail_penawaran)) {
                        foreach ($data_detail_penawaran as $item) {
                            $no_detail++;

                            echo '<tr class="item_detail_' . $no_detail . '">';
                            echo '<td class="text-center">' . $no_detail . '</td>';
                            echo '<td>';
                            echo '<textarea class="form-control form-control-sm" name="detail[' . $no_detail . '][item]">' . $item->nm_item . '</textarea>';
                            echo '</td>';
                            echo '<td>';
                            echo '<input type="text" class="form-control form-control-sm text-right auto_num qty_' . $no_detail . '" name="detail[' . $no_detail . '][qty]" onchange="hitung_total_detail(' . $no_detail . ')" value="' . $item->qty . '">';
                            echo '</td>';
                            echo '<td>';
                            echo '<input type="text" class="form-control form-control-sm text-right auto_num harga_' . $no_detail . '" name="detail[' . $no_detail . '][harga]" onchange="hitung_total_detail(' . $no_detail . ')" value="' . $item->harga . '">';
                            echo '</td>';
                            echo '<td>';
                            echo '<input type="text" class="form-control form-control-sm text-right auto_num total_' . $no_detail . '" name="detail[' . $no_detail . '][total]" value="' . $item->total . '" readonly>';
                            echo '</td>';
                            echo '<td>';
                            echo '<button type="button" class="btn btn-sm btn-danger" onclick="del_item(' . $no_detail . ')" title="Delete Item"><i class="fa fa-trash"></i></button>';
                            echo '</td>';
                            echo '</tr>';

                            $grand_total_detail += $item->total;
                        }
                    }
                    ?>
                </tbody>
                <tfoot>
                    <tr>
                        <th colspan="4" class="text-right">Biaya Kirim</th>
                        <th class="text-right">
                            <input type="text" class="form-control form-control-sm text-right auto_num biaya_kirim" name="biaya_kirim" value="<?= !empty($data_penawaran->biaya_kirim) ? number_format($data_penawaran->biaya_kirim, 2) : '0.00' ?>">
                        </th>
                        <th></th>
                    </tr>
                    <tr>
                        <th colspan="4" class="text-right">Grand Total</th>
                        <th class="text-right grand_total_detail"><?= number_format($grand_total_detail, 2) ?></th>
                        <th></th>
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
                                <input type="number" name="disc_persen" id="" class="form-control form-control-sm text-right" value="<?= !empty($data_penawaran->persen_disc) ? $data_penawaran->persen_disc : '0' ?>" onchange="hitung_disc_by_persen();" step="0.01">
                                <input type="text" name="disc_nominal" id="" class="form-control form-control-sm auto_num text-right nominal_disc" value="<?= number_format($data_penawaran->nominal_disc, 2) ?>" onchange="hitung_disc_by_nominal();">
                            </div>
                        </td>
                    </tr>
                    <tr>
                        <td>PPn</td>
                        <td class="text-right">
                            <div class="form-inline">
                                <input type="number" name="persen_ppn" id="" class="form-control form-control-sm text-right" value="<?= !empty($data_penawaran->persen_ppn) ? $data_penawaran->persen_ppn : '0' ?>">
                                <input type="text" name="nominal_ppn" id="" class="form-control form-control-sm auto_num text-right nominal_ppn" value="<?= number_format($ppn, 2) ?>" readonly>
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

            <input type="hidden" name="subtotal" value="<?= $subtotal ?>">
            <input type="hidden" name="ppn" value="<?= $ppn ?>">
            <input type="hidden" name="grand_total" value="<?= $grand_total ?>">

            <br><br>
            <a href="<?= base_url('penawaran/') ?>" class="btn btn-sm btn-danger"><i class="fa fa-arrow-left"></i> Back</a>
            <button type="submit" class="btn btn-sm btn-primary"><i class="fa fa-save"></i> Save</button>
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
    var no_detail = "<?= $no_detail; ?>";

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

    function hitung_disc_by_persen() {
        var disc_persen = $('input[name="disc_persen"]').val();
        if (disc_persen !== '') {
            disc_persen = disc_persen.split(',').join('');
            disc_persen = parseFloat(disc_persen);
        } else {
            disc_persen = 0;
        }

        var subtotal = $('.td_subtotal').html();
        if (subtotal !== '') {
            subtotal = subtotal.split(',').join('');
            subtotal = parseFloat(subtotal);
        } else {
            subtotal = 0;
        }

        var disc_nominal = (subtotal * disc_persen / 100);
        // $('.td_disc_nominal').html(number_format(disc_nominal, 2));
        $('input[name="disc_nominal"]').autoNumeric('set', disc_nominal);

        hitung_grand_total();
    }

    function hitung_disc_by_nominal() {
        var disc_nominal = $('input[name="disc_nominal"]').val();
        if (disc_nominal !== '') {
            disc_nominal = disc_nominal.split(',').join('');
            disc_nominal = parseFloat(disc_nominal);
        } else {
            disc_nominal = 0;
        }

        var subtotal = $('.td_subtotal').html();
        if (subtotal !== '') {
            subtotal = subtotal.split(',').join('');
            subtotal = parseFloat(subtotal);
        } else {
            subtotal = 0;
        }

        var disc_persen = ((disc_nominal / subtotal) * 100);
        disc_persen = disc_persen.toFixed(2);

        $('input[name="disc_persen"]').val(disc_persen);

        hitung_grand_total();
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
        // $('.grand_total_detail').html(number_format(total, 2));

        hitung_grand_total_detail();
        hitung_grand_total();
    }

    function hitung_grand_total_detail() {
        var total_penawaran_non_konsultasi = 0;
        for (i = 1; i <= no_detail; i++) {
            if ($('.total_' + i).length > 0) {
                var nilai_total = $('.total_' + i).val();
                if (nilai_total !== '') {
                    nilai_total = nilai_total.split(',').join('');
                    nilai_total = parseFloat(nilai_total);
                } else {
                    nilai_total = 0;
                }

                total_penawaran_non_konsultasi += nilai_total;
            }
        }

        var biaya_kirim = $('.biaya_kirim').val();
        if (biaya_kirim !== '') {
            biaya_kirim = biaya_kirim.split(',').join('');
            biaya_kirim = parseFloat(biaya_kirim);
        } else {
            biaya_kirim = 0;
        }

        $('.total_penawaran_non_konsultasi').autoNumeric('set', (total_penawaran_non_konsultasi + biaya_kirim));
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

        var biaya_kirim = $('.biaya_kirim').val();
        if (biaya_kirim !== '') {
            biaya_kirim = biaya_kirim.split(',').join('');
            biaya_kirim = parseFloat(biaya_kirim);
        } else {
            biaya_kirim = 0;
        }

        var total = biaya_kirim;
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

        var disc_nominal = $('input[name="disc_nominal"]').val();
        if (disc_nominal !== '') {
            disc_nominal = disc_nominal.split(',').join('');
            disc_nominal = parseFloat(disc_nominal);
        } else {
            disc_nominal = 0;
        }

        var persen_ppn = $('input[name="persen_ppn"]').val();
        if (persen_ppn === '' || isNaN(persen_ppn)) {
            persen_ppn = 0;
        }

        var ppn = ((total - disc_nominal) * persen_ppn / 100);

        $('.td_subtotal').html(number_format(total, 2));
        $('input[name="nominal_ppn"]').autoNumeric('set', ppn);
        $('.td_grand_total').html(number_format(total - disc_nominal + ppn, 2));

        $('input[name="subtotal"]').val(total);
        $('input[name="ppn"]').val(ppn);
        $('input[name="grand_total"]').val((total - disc_nominal + ppn));
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
                    url: siteurl + active_controller + 'update_penawaran_non_konsultasi',
                    data: formdata,
                    cache: false,
                    dataType: 'json',
                    success: function(result) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Success !',
                            text: result.msg,
                            showConfirmButton: false,
                            showCancelButton: false,
                            allowOutsideClick: false,
                            allowEscapeKey: false,
                            timer: 3000
                        }).then(() => {
                            window.location.href = siteurl + active_controller;
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

    $(document).on('keyup', '.biaya_kirim', function() {
        hitung_grand_total_detail();
        hitung_grand_total();
    });

    $(document).on('keyup', 'input[name="persen_ppn"]', function() {
        hitung_grand_total();
    });
</script>
<script src="<?= base_url('assets/js/basic.js') ?>"></script>