<?php
$ENABLE_ADD     = has_permission('Expense_Report_Project.Add');
$ENABLE_MANAGE  = has_permission('Expense_Report_Project.Manage');
$ENABLE_VIEW    = has_permission('Expense_Report_Project.View');
$ENABLE_DELETE  = has_permission('Expense_Report_Project.Delete');
?>

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
</style>

<input type="hidden" name="id_expense" value="<?= $header->id ?>">
<input type="hidden" name="id_header" value="<?= $id_header ?>">
<input type="hidden" name="id_spk_budgeting" value="<?= $id_spk_budgeting ?>">
<input type="hidden" name="id_spk_penawaran" value="<?= $id_spk_penawaran ?>">
<input type="hidden" name="id_penawaran" value="<?= $id_penawaran ?>">
<div class="box">
    <div class="box-header">
        <h3>List Item Subcont</h3>
    </div>

    <div class="box-body" style="z-index: 1 !important;">
        <table class="table custom-table mt-5">
            <thead>
                <tr>
                    <th class="text-center" rowspan="2">No.</th>
                    <th class="text-center" rowspan="2">Item</th>
                    <th class="text-center" colspan="2">Kasbon</th>
                    <th class="text-center" colspan="2">Expense Report</th>
                </tr>
                <tr>
                    <th class="text-center">Qty</th>
                    <th class="text-center">Nominal</th>
                    <th class="text-center">Qty</th>
                    <th class="text-center">Nominal</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $ttl_expense_report = 0;
                $ttl_kasbon = 0;

                $count_no = 0;
                foreach ($datalist_item as $item) {

                    $readonly_qty = 'readonly';
                    $readonly_nominal = 'readonly';

                    $qty_expense = (isset($datalist_item_expense[$item['id_detail_kasbon']])) ? $datalist_item_expense[$item['id_detail_kasbon']]['qty_expense'] : 0;
                    $nominal_expense = (isset($datalist_item_expense[$item['id_detail_kasbon']])) ? $datalist_item_expense[$item['id_detail_kasbon']]['nominal_expense'] : 0;
                    $total_expense = (isset($datalist_item_expense[$item['id_detail_kasbon']])) ? $datalist_item_expense[$item['id_detail_kasbon']]['total_expense'] : 0;

                    echo '<tr>';

                    echo '<td class="text-center">';
                    echo $item['no'];
                    echo '<input type="hidden" name="detail_subcont[' . $item['no'] . '][id_detail_kasbon]" value="' . $item['id_detail_kasbon'] . '">';
                    echo '</td>';

                    echo '<td width="500">' . $item['nm_item'] . '</td>';

                    echo '<td class="text-center" width="200">';
                    echo number_format($item['qty_kasbon'], 2);
                    echo '<input type="hidden" name="detail_subcont[' . $item['no'] . '][qty_kasbon]" value="' . $item['qty_kasbon'] . '">';
                    echo '</td>';

                    echo '<td class="text-center" width="200">';
                    echo number_format($item['nominal_kasbon'], 2);
                    echo '<input type="hidden" name="detail_subcont[' . $item['no'] . '][nominal_kasbon]" value="' . $item['nominal_kasbon'] . '">';
                    echo '</td>';

                    echo '<td width="200">';
                    echo '<input type="text" name="detail_subcont[' . $item['no'] . '][qty_expense]" class="form-control form-control-sm auto_num text-right qty_expense" value="' . $qty_expense . '" data-no="' . $item['no'] . '" onchange="hitung_total(' . $item['no'] . ')" ' . $readonly_qty . '>';
                    echo '</td>';

                    echo '<td width="200">';
                    echo '<input type="text" name="detail_subcont[' . $item['no'] . '][nominal_expense]" class="form-control form-control-sm auto_num text-right nominal_expense" value="' . $nominal_expense . '" data-no="' . $item['no'] . '" onchange="hitung_total(' . $item['no'] . ')" ' . $readonly_nominal . '>';
                    echo '</td>';

                    echo '</tr>';

                    $ttl_kasbon += ($item['total_kasbon']);
                    $ttl_expense_report += ($total_expense);

                    $count_no++;
                }
                ?>
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5" class="text-right">Total Kasbon</td>
                    <td class="text-right col_ttl_kasbon"><?= number_format($ttl_kasbon, 2) ?></td>
                </tr>
                <tr>
                    <td colspan="5" class="text-right">Total Expense Report</td>
                    <td class="text-right col_ttl_expense_report"><?= number_format($ttl_expense_report, 2) ?></td>
                </tr>
                <tr>
                    <td colspan="5" class="text-right">Selisih</td>
                    <td class="text-right col_selisih"><?= number_format($header->selisih, 2) ?></td>
                </tr>
            </tfoot>
        </table>

        <br><br>

        <div class="row">
            <div class="col-md-6">
                <table style="width: 100%">
                    <!-- <tr>
                        <th colspan="4">Informasi Bank Sentral</th>
                    </tr> -->
                    <tr>
                        <!-- <th style="padding: 5px;">Bank</th>
                        <td style="padding: 5px;">
                            <input type="text" name="kasbon_bank" id="" class="form-control form-control-sm" placeholder="- Bank -" value="<?= $header->bank ?>" readonly>
                        </td> -->
                        <th style="padding: 5px;">Bukti Pengembalian</th>
                        <td style="padding: 5px;">
                            <input type="file" name="bukti_pengembalian[]" id="" class="form-control form-control-sm" multiple disabled>
                            <?php
                            if (count($list_bukti_pengembalian) > 0) {
                                echo '<button type="button" class="btn btn-sm btn-primary" data-toggle="modal" data-target="#dialog-popup">';
                                echo '<i class="fa fa-list"></i> List Bukti Pengemblian';
                                echo '</button>';
                            }
                            ?>
                        </td>
                    </tr>
                    <!-- <tr>
                        <th style="padding: 5px;">Bank Number</th>
                        <td style="padding: 5px;">
                            <input type="text" name="kasbon_bank_number" id="" class="form-control form-control-sm" placeholder="- Bank Number -" value="<?= $header->bank_number ?>" readonly>
                        </td>
                        <td></td>
                        <td></td>
                    </tr> -->
                    <!-- <tr>
                        <th style="padding: 5px;">Account Name</th>
                        <td style="padding: 5px;">
                            <input type="text" name="kasbon_bank_account" id="" class="form-control form-control-sm" placeholder="- Account Name -" value="<?= $header->bank_account ?>" readonly>
                        </td>
                        <td></td>
                        <td></td>
                    </tr> -->
                </table>
            </div>
        </div>
        <a href="<?= base_url('expense_report_project/add/' . urlencode(str_replace('/', '|', $id_spk_budgeting))) ?>" class="btn btn-sm btn-danger">
            <i class="fa fa-arrow-left"></i> Back
        </a>
    </div>
</div>

<div class="modal modal-default fade" id="dialog-popup" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" style='width:70%; '>
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span aria-hidden="true">&times;</span><span class="sr-only">Close</span></button>
                <h4 class="modal-title" id="myModalLabel"><span class="fa fa-users"></span>&nbsp;List Bukti Pengembalian</h4>
            </div>
            <div class="modal-body" id="ModalView">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>Document Link</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        foreach ($list_bukti_pengembalian as $item) {
                            echo '<tr>';
                            echo '<td class="text-center">' . $no . '</td>';
                            echo '<td><a href="' . base_url($item->document_link) . '" target="_blank">' . $item->document_link . '</a></td>';
                            echo '</tr>';

                            $no++;
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('assets/js/autoNumeric.js'); ?>"></script>
<script src="https://cdn.datatables.net/2.1.8/js/dataTables.min.js"></script>

<script>
    $(document).ready(function() {
        $('.auto_num').autoNumeric();
    });

    $(document).on('submit', '#frm-data', function(e) {
        e.preventDefault();

        swal({
            type: 'warning',
            title: 'Are you sure ?',
            text: 'This data will be saved !',
            showCancelButton: true
        }, function(next) {
            if (next) {
                var formData = new FormData($('#frm-data')[0]);

                $.ajax({
                    type: 'post',
                    url: siteurl + active_controller + 'update_expense_report_subcont',
                    data: formData,
                    cache: false,
                    processData: false,
                    contentType: false,
                    dataType: 'JSON',
                    success: function(result) {
                        if (result.status == '1') {
                            swal({
                                type: 'success',
                                title: 'Success !',
                                text: result.pesan
                            }, function(lanjut) {
                                window.location.href = siteurl + active_controller + 'add/' + '<?= urlencode(str_replace('/', '|', $id_spk_budgeting)) ?>';
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

    function hitung_total(no) {
        var count_no = "<?= $count_no ?>";

        var ttl_expense_report = 0;
        var ttl_kasbon = 0;
        var selisih = 0;

        for (i = 1; i <= count_no; i++) {
            var qty_kasbon = get_num($('input[name="detail_subcont[' + i + '][qty_kasbon]"]').val());
            var nominal_kasbon = get_num($('input[name="detail_subcont[' + i + '][nominal_kasbon]"]').val());

            var qty_expense = get_num($('input[name="detail_subcont[' + i + '][qty_expense]"]').val());
            var nominal_expense = get_num($('input[name="detail_subcont[' + i + '][nominal_expense]"]').val());

            ttl_expense_report += (qty_expense * nominal_expense);
            ttl_kasbon += (qty_kasbon * nominal_kasbon);

            selisih += ((qty_kasbon * nominal_kasbon) - (qty_expense * nominal_expense));
        }

        $('.col_ttl_expense_report').html(number_format(ttl_expense_report, 2));
        $('.col_ttl_kasbon').html(number_format(ttl_kasbon, 2));
        $('.col_selisih').html(number_format(selisih, 2));
    }
</script>